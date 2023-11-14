<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Functions;
use App\Helpers\Payments;
use App\Jobs\CouponMailPDF;
use App\Jobs\FindPaymentsPending;
use App\Jobs\ProcessBillets;
use App\Jobs\ProcessCallback;
use App\Jobs\ProcessRevertPayment;
use App\Models\Payment;
use App\Services\CieloClient;
use App\Services\PaygoClient;
use App\Services\PicpayClient;
use App\Services\VigoClient;
use App\Services\VigoServer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\PaymentCollection;
use App\Http\Requests\PaymentRequest;
use App\Http\Requests\PaymentUpdateRequest;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Bus;

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

//        dd(json_decode($request->customer_origin, true));

        $billetIsPay = [];

        $validated['amount'] = 0;
        foreach ($validated['billets'] as $billet) {
            $billet->total = (($billet->value + $billet->addition) - $billet->discount);
            $validated['amount'] = $validated['amount'] + $billet->total;
            $validated['reference'] = (Str::uuid())->toString();
            $billetIsPay = (new VigoClient())->billetIsPay($validated['customer'], $billet->billet_id);
        }

//        dd(count($billetIsPay['billets']));

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

                        if($ecommercePayment->failed()){
//                            return new PaymentResource([
//                                'message' => $ecommercePayment->body()
//                            ]);

                            return response()->json([
                                'message' => 'Servidor indisponível!'
                            ], 500);
                        }
//                        dd($ecommercePayment, $ecommercePayment->object()->Payment->Status);

                        $payment->status = $cieloPayment->rewriteStatus($ecommercePayment->object()->Payment->Status);
//                            dd($payment, $ecommercePayment);

                        if ($payment->save() && $payment->status == "approved"){
//                            $payment->transaction = $ecommercePayment->object()->Payment->AuthorizationCode;
                            $payment->transaction = $ecommercePayment->object()->Payment->PaymentId;
                            $payment->installment = $ecommercePayment->object()->Payment->Installments;
                            $payment->customer_origin = !empty($request->customer_origin) ? $request->customer_origin : null;
                            $payment->receipt = [
                                'card_number' => $ecommercePayment->object()->Payment->CreditCard->CardNumber,
                                'payer' => $ecommercePayment->object()->Payment->CreditCard->Holder,
                                'flag' => $ecommercePayment->object()->Payment->CreditCard->Brand,
                                'transaction_code' => $ecommercePayment->object()->Payment->PaymentId,
                                'card_ent_mode' => "TRANSACAO AUTORIZADA COM SENHA",//approximation or password -> criar função
                                'in_installments' => $ecommercePayment->object()->Payment->Installments,
                                'receipt' => null
                            ];
                            $payment->save();

                            ProcessCallback::dispatch($payment);
                        }else{
                            $payment->installment = $ecommercePayment->Payment->Installments;
                            $payment->save();
                            ProcessCallback::dispatch($payment);
                        }
                    }
                    elseif($payment->payment_type === 'debit')
                    {
                        dd('Função Débito desabilitada temporariamente!');
                        $ecommercePayment = $cieloPayment->debit();
                        $payment->status = $cieloPayment->rewriteStatus($ecommercePayment->Payment->Status);

                        if ($payment->save() && $payment->status == "approved"){
                            $payment->transaction = $ecommercePayment->Payment->AuthorizationCode;
                            $payment->customer_origin = !empty($request->customer_origin) ? $request->customer_origin : null;
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
//                        $ecommercePayment = $cieloPayment->pix();
                        $ecommercePayment = (new CieloClient())->pix($payment, $validated);

                        if($ecommercePayment->Payment->Status === 12){
                            $payment->transaction = $ecommercePayment->Payment->PaymentId;
                            $payment->status = CieloClient::rewriteStatus($ecommercePayment->Payment->Status);
                            $payment->customer_origin = !empty($request->customer_origin) ? $request->customer_origin : null;
                            $payment->save();

                            $payment->qrCode = "data:image\/png;base64,{$ecommercePayment->Payment->QrCodeBase64Image}";
                            $payment->copyPaste = $ecommercePayment->Payment->QrCodeString;
                            $payment->PaymentId = $ecommercePayment->Payment->PaymentId;

                        }else{
                            $payment = [
                                'status' => 500,
                                'message' => 'Erro na geração do pagamento com PIX!'
                            ];
                        }
                    }
                }else{
                    $buyer = (object)$validated['buyer'];
                    $response = (new PicpayClient($payment))->pay($buyer);
                    $payment->customer_origin = !empty($request->customer_origin) ? $request->customer_origin : null;
                    $payment->save();

                    $payment->qrCode = $response->qrcode->base64;
                }

                return new PaymentResource($payment);
            }
        }else{
            if(count($billetIsPay['billets']) > 1){
                $message = "As faturas IDs ".implode(', ', $billetIsPay['billets']).", já foram pagas!";
            }else{
                $message = "A fatura ID ".implode(', ', $billetIsPay['billets']).", já foi paga!";
            }

            return response()->json([
                'message' => $message
            ], 404);
        }

//        return new PaymentResource($payment);
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
                    $paymentId = $payment->transaction;
//                    $paymentId = "153d7927-d443-4999-ba86-99f16b23ed0c";//pix 1,00 teste
//                    $paymentId = "1a35ae17-7898-4209-811f-63a153e201d5";//pix do cliente
                    $paymentAmount = $payment->amount * 100;
//                    $paymentAmount = 1.00 * 100;

//                    $ev = [
//                        'merchantId' => config('services.cielo.production.api_merchant_id'),
//                        'merchantKey' => config('services.cielo.production.api_merchant_key'),
//                        'apiUrl' => config('services.cielo.production.api_url'),
//                        'apiQueryUrl' => config('services.cielo.production.api_query_url'),
//                    ] ;

                    $ev = [
                        'merchantId' => config('services.cielo.sandbox.api_merchant_id'),
                        'merchantKey' => config('services.cielo.sandbox.api_merchant_key'),
                        'apiUrl' => config('services.cielo.sandbox.api_url'),
                        'apiQueryUrl' => config('services.cielo.sandbox.api_query_url'),
                    ] ;

//                    dd($ev['apiUrl']."1/sales/{$paymentId}/void?amount=".$paymentAmount, $ev);

                    $response = Http::withHeaders([
                        "Content-Type" => "application/json",
                        "MerchantId" => $ev['merchantId'],
                        "MerchantKey" => $ev['merchantKey'],
                    ])->put($ev['apiUrl']."1/sales/{".$paymentId."}/void?amount=".$paymentAmount);

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



//        dd('Response: ', $response->object());

        if ($payment) {
            if($payment->method === "tef")
            {
//                $payment = Payments::tef($payment, $request->all());
//                $response = CieloClient::getStatus($payment->transaction);

                if(Str::contains($payment->status, ['approved', 'canceled','chargeback']))
                {
                    $this->proccessBillets();
                }
                else
                {
                    $response = (new PaygoClient())->getStatus($this->payment->reference);
                    $this->payment->status = $response['status'];
                    $this->payment->installment = $response['payment']->intencoesVendas[0]->quantidadeParcelas;

                    if ($this->payment->save() && $this->payment->status == "approved"){
                        $this->payment->transaction = $response['payment']->intencoesVendas[0]->pagamentosExternos[0]->autorizacao;
//                            $this->payment->customer_origin =

                        //"CRIAR FUNÇÃO PARA TRATAR OS DADOS DO CUPOM"
                        $this->payment->receipt = [
                            'card_number' => null,
                            'flag' => $response['payment']->intencoesVendas[0]->pagamentosExternos[0]->bandeira,
                            'card_ent_mode' => null,//approximation or password
                            'payer' => null,
                            'transaction_code' => null,
                            'receipt' => $response['payment']->intencoesVendas[0]->pagamentosExternos[0]->comprovanteAdquirente
                        ];
                    }

                    $this->payment->save();
                    $this->proccessBillets();
                    dd('TEf');
                }
            }

            if($payment->method === "ecommerce")
            {
                $ecommercePayment = CieloClient::getStatus($payment->transaction);

                if($ecommercePayment != null){
                    $payment->status = $ecommercePayment['status'];

                    if(Str::contains($payment->status, ['approved', 'canceled','chargeback']))
                    {
                        if(Str::contains($payment->payment_type,["credit", "debit"]))
                        {
                            if ($payment->save() && $payment->status == "approved")
                            {
                                $payment->transaction = $ecommercePayment['payment']->Payment->PaymentId;
                                $payment->receipt = [
                                    'card_number' => $ecommercePayment['payment']->Payment->CreditCard->CardNumber,
                                    'flag' => $ecommercePayment['payment']->Payment->CreditCard->Brand,
                                    'card_ent_mode' => "TRANSACAO AUTORIZADA COM SENHA",//approximation or password -> criar função
                                    'payer' => $ecommercePayment['payment']->Payment->CreditCard->Holder,
                                    'in_installments' => $ecommercePayment['payment']->Payment->Installments,
                                    'transaction_code' => $ecommercePayment['payment']->Payment->AuthorizationCode,
                                    'receipt' => null
                                ];
                            }
                        }

                        ProcessCallback::dispatch($payment);
                    }

                    $payment->save();
                }
            }

            if($payment->method === "picpay")
            {
                $payment->status = (new PicpayClient($payment))->getStatus()->status;

                if(Str::contains($payment->status, ['approved', 'canceled','chargeback']))
                {
                    ProcessCallback::dispatch($payment);
                }

                $payment->save();
            }
        }

        return new PaymentResource($payment);
    }

    public function paymentsPending()
    {
//        $today = Carbon::now()->toDateString();
//
//        $payments = Payment::whereDate('created_at', $today)
//            ->where('status', 'created')
//            ->get();
//
////        $response = $payments->getAttributes();
////
////        dd($payments[0]['customer']);
//
//        $job = FindPaymentsPending::dispatch($payments);
//
//        // Agora, você pode obter o resultado do job
////        $response = Bus::dispatched($job)->first()->result;
//
//        // Faça algo com o resultado
//        dd($job);

        Payments::findPending();

        return response()->json('Iniciado com sucesso!');

    }

    public function sendMailCouponPDF($payment_id)
    {
        CouponMailPDF::dispatch($payment_id);

//        dd((new Functions())->convertDateTime('2023-11-10 14:37:50'));


        return response()->json('E-mail enviado com sucesso!');

    }

}
