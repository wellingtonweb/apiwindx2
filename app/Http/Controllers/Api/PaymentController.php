<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Functions;
use App\Helpers\Payments;
use App\Jobs\ProcessBillets;
use App\Jobs\ProcessCallback;
use App\Jobs\ProcessRevertPayment;
use App\Models\Payment;
use App\Services\CieloClient;
use App\Services\PaygoClient;
use App\Services\PicpayClient;
use App\Services\VigoClient;
use App\Services\VigoServer;
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

//        dd($validated);
        $billetIsPay = [];

        $validated['amount'] = 0;
        foreach ($validated['billets'] as $billet) {
            $billet->total = (($billet->value + $billet->addition) - $billet->discount);
            $validated['amount'] = $validated['amount'] + $billet->total;
            $validated['reference'] = (Str::uuid())->toString();
            $billetIsPay = (new VigoClient())->billetIsPay($validated['customer'], $billet->billet_id);
        }

//                dd($billetIsPay);

        if(count($billetIsPay['billets']) === 0){
            $payment = (Payment::create($validated))->load('terminal');

            if ($payment) {
                if($payment->method === 'tef'){
                    $response = (new PaygoClient())->pay($payment);
//                    dd($response);
                }
                elseif ($payment->method === 'ecommerce')
                {
                    $cieloPayment = (new CieloClient($payment, $validated));
                    $ecommercePayment = null;

                    if($payment->payment_type === 'credit')
                    {
                        $ecommercePayment = $cieloPayment->credit();
//                            dd($ecommercePayment);

                        $payment->status = $cieloPayment->rewriteStatus($ecommercePayment->Payment->Status);
//                            dd($payment, $ecommercePayment);

                        if ($payment->save() && $payment->status == "approved"){
                            $payment->transaction = $ecommercePayment->Payment->AuthorizationCode;
                            $payment->installment = $ecommercePayment->Payment->Installments;
                            $payment->receipt = [
                                'card_number' => $ecommercePayment->Payment->CreditCard->CardNumber,
                                'payer' => $ecommercePayment->Payment->CreditCard->Holder,
                                'flag' => $ecommercePayment->Payment->CreditCard->Brand,
                                'transaction_code' => $ecommercePayment->Payment->PaymentId,
                                'card_ent_mode' => "TRANSACAO AUTORIZADA COM SENHA",//approximation or password -> criar função
                                'in_installments' => $ecommercePayment->Payment->Installments,
                                'receipt' => null
                            ];
                            $payment->save();
//
//                                foreach ($payment->billets as $billet) {
//                                    ProcessBillets::dispatch((array)$billet, true);
//                                }
//                                dd('ProcessCallback dispatch - credit ',(array)$payment);

                            ProcessCallback::dispatch($payment);
//                                Payments::proccessingBillets($payment);
                        }else{
                            $payment->installment = $ecommercePayment->Payment->Installments;
                            $payment->save();
                            ProcessCallback::dispatch($payment);
                        }
                    }
                    elseif($payment->payment_type === 'debit')
                    {
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
                    }
                    else
                    {
                        $ecommercePayment = $cieloPayment->pix();

                        if($ecommercePayment->Payment->Status === 12){
                            $payment->transaction = $ecommercePayment->Payment->PaymentId;
                            $payment->status = $cieloPayment->rewriteStatus($ecommercePayment->Payment->Status);
                            $payment->save();

                            $payment->qrCode = "data:image\/png;base64,{$ecommercePayment->Payment->QrCodeBase64Image}";
                            $payment->copyPaste = $ecommercePayment->Payment->QrCodeString;
                            $payment->PaymentId = $ecommercePayment->Payment->PaymentId;

//                            $paymentUpdate = Payment::find($payment->id);
//                            $paymentUpdate->transaction = $ecommercePayment->Payment->PaymentId;
//                            $paymentUpdate->status = $cieloPayment->rewriteStatus($ecommercePayment->Payment->Status);
//                            $paymentUpdate->save();

//                            dd($paymentUpdate);


                        }else{
                            $payment = [
                                'status' => 500,
                                'message' => 'Erro na geração do pagamento!'
                            ];
                        }
                    }
                }else{
                    $buyer = (object)$validated['buyer'];
                    $response = (new PicpayClient($payment))->pay($buyer);
                    $payment->qrCode = $response->qrcode->base64;
                }

//                switch ($payment->method) {
//                    case "tef": {
//                        $response = (new PaygoClient())->pay($payment);
////                    dd($response);
//                        break;
//                    }
//                    case "ecommerce": {
//                        $cieloPayment = (new CieloClient($payment, $validated));
//                        $ecommercePayment = null;
//
//                        switch($payment->payment_type) {
//                            case 'credit': {
//                                $ecommercePayment = $cieloPayment->credit();
////                            dd($ecommercePayment);
//
//                                $payment->status = $cieloPayment->rewriteStatus($ecommercePayment->Payment->Status);
////                            dd($payment, $ecommercePayment);
//
//                                if ($payment->save() && $payment->status == "approved"){
//                                    $payment->transaction = $ecommercePayment->Payment->AuthorizationCode;
//                                    $payment->installment = $ecommercePayment->Payment->Installments;
//                                    $payment->receipt = [
//                                        'card_number' => $ecommercePayment->Payment->CreditCard->CardNumber,
//                                        'payer' => $ecommercePayment->Payment->CreditCard->Holder,
//                                        'flag' => $ecommercePayment->Payment->CreditCard->Brand,
//                                        'transaction_code' => $ecommercePayment->Payment->PaymentId,
//                                        'card_ent_mode' => "TRANSACAO AUTORIZADA COM SENHA",//approximation or password -> criar função
//                                        'in_installments' => $ecommercePayment->Payment->Installments,
//                                        'receipt' => null
//                                    ];
//                                    $payment->save();
////
////                                foreach ($payment->billets as $billet) {
////                                    ProcessBillets::dispatch((array)$billet, true);
////                                }
////                                dd('ProcessCallback dispatch - credit ',(array)$payment);
//
//                                    ProcessCallback::dispatch($payment);
////                                Payments::proccessingBillets($payment);
//                                }else{
//                                    $payment->installment = $ecommercePayment->Payment->Installments;
//                                    $payment->save();
//                                    ProcessCallback::dispatch($payment);
//                                }
//                                break;
//                            }
//                            case 'debit': {
//                                $ecommercePayment = $cieloPayment->debit();
//                                $payment->status = $cieloPayment->rewriteStatus($ecommercePayment->Payment->Status);
//
//                                if ($payment->save() && $payment->status == "approved"){
//                                    $payment->transaction = $ecommercePayment->Payment->AuthorizationCode;
//                                    $payment->receipt = [
//                                        'card_number' => $ecommercePayment->Payment->CreditCard->CardNumber,
//                                        'flag' => $ecommercePayment->Payment->CreditCard->Brand,
//                                        'approximation' => null,
//                                        'with_password' => "TRANSACAO AUTORIZADA COM SENHA",
//                                        'payer' => $ecommercePayment->Payment->CreditCard->Holder,
//                                        'in_installments' => null,
//                                        'transaction_code' => $ecommercePayment->Payment->PaymentId,
//                                        'receipt' => null
//                                    ];
//                                    $payment->save();
//                                    dd('ProcessCallback dispatch - debit', $payment, $ecommercePayment);
//                                    ProcessCallback::dispatch($payment);
//                                }
//                                break;
//                            }
//                            case 'pix': {
//                                $ecommercePayment = $cieloPayment->pix();
//
//                                if($ecommercePayment->Payment->Status === 12){
//                                    $payment->qrCode = "data:image\/png;base64,{$ecommercePayment->Payment->QrCodeBase64Image}";
//                                    $payment->copyPaste = $ecommercePayment->Payment->QrCodeString;
//                                    $payment->PaymentId = $ecommercePayment->Payment->PaymentId;
//
//                                    $paymentUpdate = Payment::find($payment->id);
//                                    $paymentUpdate->transaction = $ecommercePayment->Payment->PaymentId;
//                                    $paymentUpdate->status = $cieloPayment->rewriteStatus($ecommercePayment->Payment->Status);
//                                    $paymentUpdate->save();
//
//                                    dd($paymentUpdate);
//
//
//                                }else{
//                                    $payment = [
//                                        'status' => 500,
//                                        'message' => 'Erro na geração do pagamento!'
//                                    ];
//                                }
//                                break;
//                            }
//                        }
//
//                        break;
//                    }
//                    case "picpay": {
//                        $buyer = (object)$validated['buyer'];
//                        $response = (new PicpayClient($payment))->pay($buyer);
//                        $payment->qrCode = $response->qrcode->base64;
//                        break;
//                    }
//                }
            }
        }else{
            if(count($billetIsPay['billets']) > 1){
                $message = "As faturas IDs ".json_encode($billetIsPay['billets']).", já foram pagas!";
            }else{
                $message = "A fatura ID ".json_encode($billetIsPay['billets']).", já foi paga!";
            }

            $payment = [
                'status' => 404,
                'message' => $message
            ];
        }

        return new PaymentResource($payment);
    }

    public function revertPayment(Payment $payment)
    {
        if($payment){
            ProcessRevertPayment::dispatch($payment);
        }
    }


    public function cancelPayment(Payment $payment)
    {
//        dd($payment->method);

        if($payment){

            $response = null;

            switch ($payment->method){
                case 'ecommerce':
                    //            $paymentId = $payment->transaction;
                    $paymentId = "153d7927-d443-4999-ba86-99f16b23ed0c";//pix 1,00 teste
//                    $paymentId = "1a35ae17-7898-4209-811f-63a153e201d5";//pix do cliente
                    //            $paymentAmount = $payment->amount;
                    $paymentAmount = 1.00 * 100;

                    $ev = [
                        'merchantId' => config('services.cielo.production.api_merchant_id'),
                        'merchantKey' => config('services.cielo.production.api_merchant_key'),
                        'apiUrl' => config('services.cielo.production.api_url'),
                        'apiQueryUrl' => config('services.cielo.production.api_query_url'),
                    ] ;

                    dd($ev['apiUrl']."1/sales/{".$paymentId."}/void?amount=".$paymentAmount);

                    $response = Http::withHeaders([
                        "Content-Type" => "application/json",
                        "MerchantId" => $ev['merchantId'],
                        "MerchantKey" => $ev['merchantKey'],
                    ])->put($ev['apiUrl']."1/sales/{$paymentId}/void?amount=".$paymentAmount);

                    break;
                case 'tef':
                    //2
                    break;
                case 'picpay':
                    //3
                    break;
            }


            return collect($response->body());

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
