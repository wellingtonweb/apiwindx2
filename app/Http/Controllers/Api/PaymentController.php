<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Functions;
use App\Helpers\Payments;
use App\Helpers\WorkingDays;
use App\Jobs\CouponMailPDF;
use App\Jobs\FindPaymentsPending;
use App\Jobs\ProcessBillets;
use App\Jobs\ProcessCallback;
use App\Jobs\ProcessRevertPayment;
use App\Models\Payment;
use App\Services\CieloClient;
use App\Services\GetnetClient;
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
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Bus;
use App\Events\PaymentApproved;
use Illuminate\Support\Facades\Storage;

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

        $customer_origin = null;

        if(isset($request->customer_origin) && json_decode($request->customer_origin)[0]->origin != null){
            $customer_origin = json_decode($request->customer_origin)[0]->origin;
        }

        $isPaidBillet = [];
        $validated['amount'] = 0;
        $validated['fees'] = false;

        if(isset($customer_origin) && $customer_origin === 'bot'){
            foreach ($validated['billets'] as $billet)
            {
                $billet->addition = 0;
                $billet->discount = 0;
                $billet->addition = WorkingDays::calcFees($billet->duedate, $billet->value);
                $billet->total = (($billet->value + $billet->addition) - $billet->discount);
                $validated['amount'] = $validated['amount'] + $billet->total;
                $validated['reference'] = (Str::uuid())->toString();
                $isPaidBillet = (new VigoClient())->isPaidBillet($validated['customer'], $billet->billet_id);
            }
        }else{
            foreach ($validated['billets'] as $billet)
            {
                $billet->total = (($billet->value + $billet->addition) - $billet->discount);

                $validated['amount'] = $validated['amount'] + $billet->total;
                $validated['reference'] = (Str::uuid())->toString();
                $isPaidBillet = (new VigoClient())->isPaidBillet($validated['customer'], $billet->billet_id);
            }
        }

        if(count($isPaidBillet['billets']) > 0){

            if(count($isPaidBillet['billets']) > 1){
                $message = "As faturas IDs ".implode(', ', $isPaidBillet['billets']).", já foram pagas!";
            }else{
                $message = "A fatura ID ".implode(', ', $isPaidBillet['billets']).", já foi paga!";
            }

            return response()->json(['message' => $message], 404);
        }

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
                        return response()->json([
                            'message' => 'Servidor indisponível!'
                        ], 500);
                    }

                    $payment->status = $cieloPayment->rewriteStatus($ecommercePayment->object()->Payment->Status);
                    $payment->save();

                    if($payment->status != 'approved')
                    {
                        $payment->transaction = $ecommercePayment->object()->Payment->PaymentId;
                        $payment->save();

                        return new PaymentResource($payment);
                    }
                    elseif ($payment->save() && $payment->status == "approved")
                    {
                        $payment->transaction = $ecommercePayment->object()->Payment->PaymentId;
                        $payment->installment = $ecommercePayment->object()->Payment->Installments;
                        $payment->customer_origin = !empty($request->customer_origin) ? $request->customer_origin : null;
                        $payment->receipt = CieloClient::receiptFormat($ecommercePayment->object());
                        $payment->save();

                        ProcessCallback::dispatch($payment);
                    }
                }

                if($payment->payment_type === 'debit')
                {
//                    dd('Função Débito desabilitada temporariamente!');
                    $ecommercePayment = $cieloPayment->debit();

//                    if($ecommercePayment->failed()){
//                        return response()->json([
//                            'message' => 'Servidor indisponível!'
//                        ], $ecommercePayment->status());
//                    }

                    dd($ecommercePayment->object(), $ecommercePayment->status());

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

                if($payment->payment_type === 'pix')
                {
                    $ecommercePayment = $cieloPayment->pix();

//                        dd($ecommercePayment);
//                        $ecommercePayment = (new CieloClient())->pix($payment, $validated);

                    if($ecommercePayment->Payment->Status === 12){
                        $payment->transaction = $ecommercePayment->Payment->PaymentId;
                        $payment->status = CieloClient::rewriteStatus($ecommercePayment->Payment->Status);
                        $payment->customer_origin = !empty($request->customer_origin) ? $request->customer_origin : null;
                        $payment->save();

                        $payment->qrCode = "data:image/png;base64,".$ecommercePayment->Payment->QrCodeBase64Image;
//                            $payment->qrCode = "data:image\/png;base64,{$ecommercePayment->Payment->QrCodeBase64Image}";
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
                $payment->customer_origin = !empty($request->customer_origin) ? $request->customer_origin : null;
                $payment->save();
                dd($payment);
                $response = (new PicpayClient($payment))->pay($buyer);

                $payment->save();

                $payment->qrCode = $response->qrcode->base64;
                $payment->paymentUrl = $response->paymentUrl;
            }
        }

        return new PaymentResource($payment);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payment  $payment
     * @return PaymentResource
     */
    public function show(Request $request, Payment $payment)
    {
        $this->authorize('view', $payment);

//        $data = ["id" => $payment->id,"status" => $payment->status];

        //Teste de notificação do pusher
//        event(new PaymentApproved($payment));
//        dd('Teste',$data);

        if ($payment) {
            if($payment->method === "tef")
            {
                $response = (new PaygoClient())->getStatus($payment->reference);

                if($response){
                    $payment->status = $response['status'];

                    if(Str::contains($payment->status, ['approved', 'canceled','chargeback']))
                    {
                        $payment->installment = $response['payment']->intencoesVendas[0]->quantidadeParcelas;

                        if ($payment->save() && $payment->status == "approved"){
                            $payment->transaction = $response['payment']->intencoesVendas[0]->pagamentosExternos[0]->autorizacao;
                            $payment->receipt = $response['receipt'];
                        }

                        $payment->save();

                        ProcessCallback::dispatch($payment);
                    }
                }
            }

            if($payment->method === "ecommerce")
            {
                $ecommercePayment = (new CieloClient($payment, $request->all()))->getStatus();

                if($ecommercePayment != null){
                    $payment->status = $ecommercePayment['status'];
//                    dd($ecommercePayment['payment']);

                    if(Str::contains($payment->status, ['approved', 'canceled','chargeback']))
                    {
                        if(Str::contains($payment->payment_type,["credit", "debit"]))
                        {
                            if ($payment->save() && $payment->status == "approved")
                            {
                                $payment->transaction = $ecommercePayment['payment']->Payment->PaymentId;
                                $payment->receipt = $ecommercePayment['receipt'];
                            }
                        }

                        //Notificação do pusher
//                        if($payment->status === 'approved'){
//                            event(new PaymentApproved("Pagamento {$payment->id} aprovado com sucesso!"));
//                        }

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
                    //Notificação do pusher
//                    if($payment->status === 'approved'){
//                        event(new PaymentApproved($payment));
//                    }

                    ProcessCallback::dispatch($payment);
                }

                $payment->save();
            }
        }

        return new PaymentResource($payment);
    }

    public function paymentsPending(Payment $payment)
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

//        Payments::findPending();

        if ($payment) {
            if($payment->method === "tef")
            {
                $response = (new PaygoClient())->getStatus($payment->reference);

                if($response){
                    $payment->status = $response['status'];

                    if(Str::contains($payment->status, ['approved', 'canceled','chargeback']))
                    {
                        $payment->installment = $response['payment']->intencoesVendas[0]->quantidadeParcelas;

                        if ($payment->save() && $payment->status == "approved"){
                            $payment->transaction = $response['payment']->intencoesVendas[0]->pagamentosExternos[0]->autorizacao;
                            $payment->receipt = $response['receipt'];
                        }

                        $payment->save();

                        ProcessCallback::dispatch($payment);
                    }
                }
            }

            if($payment->method === "ecommerce")
            {
                $ecommercePayment = (new CieloClient($payment, $request->all()))->getStatus();

                if($ecommercePayment != null){
                    $payment->status = $ecommercePayment['status'];
//                    dd($ecommercePayment['payment']);

                    if(Str::contains($payment->status, ['approved', 'canceled','chargeback']))
                    {
                        if(Str::contains($payment->payment_type,["credit", "debit"]))
                        {
                            if ($payment->save() && $payment->status == "approved")
                            {
                                $payment->transaction = $ecommercePayment['payment']->Payment->PaymentId;
                                $payment->receipt = $ecommercePayment['receipt'];
                            }
                        }

                        //Notificação do pusher
//                        if($payment->status === 'approved'){
//                            event(new PaymentApproved("Pagamento {$payment->id} aprovado com sucesso!"));
//                        }

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
                    //Notificação do pusher
//                    if($payment->status === 'approved'){
//                        event(new PaymentApproved($payment));
//                    }

                    ProcessCallback::dispatch($payment);
                }

                $payment->save();
            }
        }

        return response()->json('Iniciado com sucesso!');

    }

    public function sendMailCouponPDF($payment_id)
    {
        CouponMailPDF::dispatch($payment_id);

//        dd((new Functions())->convertDateTime('2023-11-10 14:37:50'));


        return response()->json('E-mail enviado com sucesso!');

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

//    public function renderQrcode()
//    {
//        $qrcode = "data:image/png;base64,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;";
//
////      Validar o código para gerar e salvar PNG no storage
////        $dadosBinarios = base64_decode(substr($qrcode, strpos($qrcode, ',') + 1));
////        Storage::disk('public')->put('qrcodes/qrcode_comprimido.png', $dadosBinarios);
////        $caminhoArquivo = Storage::disk('public')->url('qrcodes/qrcode_comprimido.png');
//
//        return view('qrcode', compact('caminhoArquivo'));
//    }

    public function authgetnet()
    {
//        $response = GetnetClient::get_token();
        $response = (new GetnetClient())->card_tokenization();

        dd($response);
//        return GetnetClient::token();
    }

}
