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

use App\Helpers\Functions;
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

        $payment = (Payment::create($validated))->load('terminal');

        if ($payment) {
            switch ($payment->method) {
                case "tef": {
                    //                    $payment->installment = $payment['billets'][0]->installment;
//                    dd($payment);
//                    $payment = (new PaygoClient())->pay($payment);

                    $response = (new PaygoClient())->pay($payment);
//                    dd($response);
                    break;
                }
                case "ecommerce": {
                    $cieloPayment = (new CieloClient($payment, $validated));
                    $ecommercePayment = null;

                    switch($payment->payment_type) {
                        case 'credit': {
                            $ecommercePayment = $cieloPayment->credit();
                            $payment->status = $cieloPayment->rewriteStatus($ecommercePayment->Payment->Status);

                            if ($payment->save() && $payment->status == "approved"){
                                $payment->transaction = $ecommercePayment->Payment->AuthorizationCode;
                                $payment->save();
                                dd('ProcessCallback dispatch');
                                //ProcessCallback::dispatch($payment);
                            }
                            break;
                        }
                        case 'debit': {
                            $ecommercePayment = $cieloPayment->debit();
                            $payment->status = $cieloPayment->rewriteStatus($ecommercePayment->Payment->Status);

                            if ($payment->save() && $payment->status == "approved"){
                                $payment->transaction = $ecommercePayment->Payment->AuthorizationCode;
                                $payment->save();
                                dd('ProcessCallback dispatch');
                                //ProcessCallback::dispatch($payment);
                            }
                            break;
                        }
                        case 'pix': {
                            $ecommercePayment = $cieloPayment->pix();
                            $payment->status = $cieloPayment->rewriteStatus($ecommercePayment->Payment->Status);
                            $payment->save();

                            $payment->qrCode = $ecommercePayment->Payment->QrCodeBase64Image;
                            $payment->copyPaste = $ecommercePayment->Payment->QrCodeString;
                            $payment->PaymentId = $ecommercePayment->Payment->PaymentId;
                            break;
                        }
                    }

                    break;
                }
                case "picpay": {
                    $buyer = (object)$validated['buyer'];
                    $response = (new PicpayClient($payment))->pay($buyer);
                    $payment->qrCode = $response->qrcode->base64;
                    break;
                }
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

            $cieloPayment = CieloClient::getStatus($payment->transaction);

//            if($payment->terminal_id != '' || $payment->terminal_id != null){
//                $payment->method = 'tef';
//            }

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

                $payment->transaction = $response->intencoesVendas[0]->pagamentosExternos[0]->autorizacao;
                $payment->receipt = Functions::receiptFormat($response->intencoesVendas[0]->pagamentosExternos[0]);

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
