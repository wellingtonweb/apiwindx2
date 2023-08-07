<?php

namespace App\Http\Controllers\Api;

//use App\Jobs\ProcessCallback;
//use App\Jobs\ProcessRevertPayment;
//use App\Models\Payment;
//use App\Services\CieloClient;
//use App\Services\PaygoClient;
//use App\Services\PicpayClient;
//use Illuminate\Http\Request;
//use App\Http\Controllers\Controller;
//use App\Http\Resources\PaymentResource;
//use App\Http\Resources\PaymentCollection;
//use App\Http\Requests\PaymentRequest;
//use App\Http\Requests\PaymentUpdateRequest;
//use Illuminate\Support\Str;
//use Ramsey\Uuid\Uuid;

use App\Jobs\ProcessCallback;
use App\Jobs\ProcessRevertPayment;
use App\Models\Payment;
use App\Services\CieloClient;
use App\Services\PaygoClient;
use App\Services\PicpayClient;
use Cielo\API30\Ecommerce\Environment;
use Cielo\API30\Ecommerce\Request\QuerySaleRequest;
use Cielo\API30\Ecommerce\CieloEcommerce;
use Cielo\API30\Merchant;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\PaymentCollection;
use App\Http\Requests\PaymentRequest;
use App\Http\Requests\PaymentUpdateRequest;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class PaymentController extends Controller
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return PaymentCollection
     */
    public function index(Request $request)
    {
        $this->authorize('view-any', Payment::class);

        $search = $request->get('search', '');

        $payments = Payment::search($search)
            ->latest()
            ->get();

        return new PaymentCollection($payments);
    }



    /**
     * @param  \App\Http\Requests\PaymentRequest  $request
     * @return PaymentResource
     */
    public function store(PaymentRequest $request)
    {
        $this->authorize('create', Payment::class);

        $validated = $request->validated();

        $validated['amount'] = 0;
        foreach ($validated['billets'] as $billet) {
            $billet->total = (($billet->value + $billet->addition) - $billet->discount);
            $validated['amount'] = $validated['amount'] + $billet->total;
            $validated['reference'] = (Str::uuid())->toString();
        }

//        dd($validated);

        $payment = (Payment::create($validated))->load('terminal');

        if ($payment) {
            switch ($payment->method) {

                case "tef":

//                    dd($payment);
//                    $payment = (new PaygoClient())->pay($payment);

                    $response = (new PaygoClient())->pay($payment);
//                    dd($response);
                    break;

                case "ecommerce":
                    $cieloPayment = (new CieloClient($payment, $validated));

                    if(Str::contains($payment->payment_type,["credit", "debit"])){
                        if($payment->payment_type == "credit"){
                            $ecommercePayment = $cieloPayment->credit();
                        }else{
                            $ecommercePayment = $cieloPayment->debit();
                        }

                        switch ($ecommercePayment->getStatus()){
                            case 2:
                                $payment->status = "approved";
                                break;
                            case 3:
                            case 10:
                            case 13:
                                $payment->status = "refused";
                                break;
                            default:
                                $payment->status = $payment->status;
                                break;
                        }
                        if ($payment->save() && $payment->status == "approved"){
                            ProcessCallback::dispatch($payment);
                        }
                        $payment->message = "{$ecommercePayment->getReturnCode()} - {$ecommercePayment->getReturnMessage()}";
                    }else{
                        $ecommercePayment = $cieloPayment->pix();
                        $payment->qrCode = $ecommercePayment->Payment->QrCodeBase64Image;
                        $payment->copyPaste = $ecommercePayment->Payment->QrCodeString;
                        $payment->PaymentId = $ecommercePayment->Payment->PaymentId;


//                        dd($payment->terminal_id);

                        $paymentUpdate = Payment::find($payment->id);
                        $paymentUpdate->transaction = $payment->PaymentId;

//                        if($payment->terminal_id != '' || $payment->terminal_id != null){
//                            $paymentUpdate->method = 'tef';
//                        }

                        $paymentUpdate->save();

//                        dd($paymentUpdate, $payment);
                    }
                    break;

                case "picpay":
                    $buyer = (object)$validated['buyer'];
                    $response = (new PicpayClient($payment))->pay($buyer);
                    $payment->qrCode = $response->qrcode->base64;
                    break;
            }

        }

        return new PaymentResource($payment);
    }

    public function revertPayment(Payment $payment)
    {
        if($payment){
            ProcessRevertPayment::dispatch($payment);
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payment  $payment
     * @return PaymentResource
     */
    public function show(Request $request, Payment $payment)
    {
        $this->authorize('view', $payment);

//        if (
//            $payment->method == "ecommerce" and $payment->payment_type == "pix" )
////            ($payment->method == "tef" and $payment->payment_type == "pix"))
//        {
//        dd($payment);

        if ($payment->payment_type == "pix"){

            $cieloPayment = CieloClient::getPixStatus($payment->transaction);

//            dd($cieloPayment->object());
//            if (getenv('APP_ENV') == 'local') {
//                $environment = Environment::sandbox();
//                $merchant = (new Merchant(getenv('CIELO_SANDBOX_MERCHANT_ID'), getenv('CIELO_SANDBOX_MERCHANT_KEY')));
//            } else {
//                $environment = Environment::production();
//                $merchant = (new Merchant(getenv('CIELO_PROD_MERCHANT_ID'), getenv('CIELO_PROD_MERCHANT_KEY')));
//            }
//
//            $cieloPayment = Http::withHeaders([
//                "Content-Type" => "application/json",
//                "MerchantId" => $merchant->getId(),
//                "MerchantKey" => $merchant->getKey(),
//            ])->get($environment->getApiQueryURL(). "1/sales/". $payment->transaction);

            if($payment->terminal_id != '' || $payment->terminal_id != null){
                $payment->method = 'tef';
            }

            switch ($cieloPayment->object()->Payment->Status){
                case 2:
                    $payment->status = "approved";
                    break;
                case 3:
                case 10:
                case 13:
                    $payment->status = "refused";
                    break;
                default:
                    $payment->status = "created";
                    break;
            }

//            dd($cieloPayment->object());


                $payment->save();

            if ($payment->save() && $payment->status == "approved"){

                ProcessCallback::dispatch($payment);
            }
        }
        elseif($payment->method == "tef"){
//        elseif($payment->method == "tef" && $payment->payment_type != "pix"){

//            dd($payment);

            $response = (new PaygoClient())->getPaymentStatus($payment->reference);

//            dd($response);

            switch ($response->intencoesVendas[0]->intencaoVendaStatus->id){
                case 10:
                    $payment->status = "approved";
                    break;
                case 18:
                case 19:
                case 20:
                    $payment->status = "canceled";
                    break;
                case 15:
                    $payment->status = "expired";
                    break;
                case 25:
                    $payment->status = "refused";
                    break;
                default:
                    $payment->status = "created";
                    break;
            }

            if ($payment->save() && $payment->status == "approved"){

                    $payment->receipt = $response->intencoesVendas[0]->pagamentosExternos[0]->comprovanteAdquirente;
                    $payment->transaction = $response->intencoesVendas[0]->pagamentosExternos[0]->autorizacao;

                $payment->save();

//                dd($payment);

                ProcessCallback::dispatch($payment);

//                dd($payment->getAttributes());
//                dd($payment->getAttributes()['customer']);
            }else{
                $payment->receipt = null;
                $payment->save();
            }

        }

        return new PaymentResource($payment);
    }

}
