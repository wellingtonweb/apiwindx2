<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Functions;
use App\Helpers\Payments;
use App\Jobs\ProcessCallback;
use App\Jobs\ProcessRevertPayment;
use App\Models\Payment;
use App\Services\CieloClient;
use App\Services\PaygoClient;
use App\Services\PicpayClient;
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
//                            dd($payment->status, $ecommercePayment);

                            if ($payment->save() && $payment->status == "approved"){
                                $payment->transaction = $ecommercePayment->Payment->AuthorizationCode;
                                $payment->installment = $ecommercePayment->Payment->Installments;
                                $payment->receipt = [
                                    'card_number' => $ecommercePayment->Payment->CreditCard->CardNumber,
                                    'flag' => $ecommercePayment->Payment->CreditCard->Brand,
                                    'card_ent_mode' => "TRANSACAO AUTORIZADA COM SENHA",//approximation or password -> criar função
                                    'payer' => $ecommercePayment->Payment->CreditCard->Holder,
                                    'in_installments' => $ecommercePayment->Payment->Installments,
                                    'transaction_code' => $ecommercePayment->Payment->PaymentId,
                                    'receipt' => null
                                ];
                                $payment->save();
//                                dd('ProcessCallback dispatch - credit '.$payment->status);
                                ProcessCallback::dispatch($payment);
//                                Payments::proccessingBillets($payment);
                            }else{
                                $payment->installment = $ecommercePayment->Payment->Installments;
                                $payment->save();
                                ProcessCallback::dispatch($payment);
                            }
                            break;
                        }
                        case 'debit': {
                            $ecommercePayment = $cieloPayment->debit();
                            $payment->status = $cieloPayment->rewriteStatus($ecommercePayment->Payment->Status);

                            if ($payment->save() && $payment->status == "approved"){
                                $payment->transaction = $ecommercePayment->Payment->AuthorizationCode;
                                $payment->receipt = [
                                    'card_number' => $ecommercePayment->Payment->CreditCard->CardNumber,
                                    'flag' => $ecommercePayment->Payment->CreditCard->Brand,
                                    'approximation' => null,
                                    'with_password' => "TRANSACAO AUTORIZADA COM SENHA",
                                    'payer' => $ecommercePayment->Payment->CreditCard->Holder,
                                    'in_installments' => null,
                                    'transaction_code' => $ecommercePayment->Payment->PaymentId,
                                    'receipt' => null
                                ];
                                $payment->save();
                                dd('ProcessCallback dispatch - debit', $payment, $ecommercePayment);
                                ProcessCallback::dispatch($payment);
                            }
                            break;
                        }
                        case 'pix': {
                            $ecommercePayment = $cieloPayment->pix();
                            $payment->transaction = $ecommercePayment->Payment->PaymentId;
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

        if ($payment) {
//        if ($payment && $payment['status'] === "created") {
            ProcessCallback::dispatch($payment);
        }

        return new PaymentResource($payment);
    }

}
