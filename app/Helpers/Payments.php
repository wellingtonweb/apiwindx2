<?php


namespace App\Helpers;


use App\Helpers\Functions;
use App\Http\Resources\PaymentResource;
use App\Jobs\CouponMailPDF;
use App\Jobs\ProcessBillets;
use App\Jobs\ProcessCallback;
use App\Jobs\FindPaymentsPending;
use App\Models\Payment;
use App\Services\CieloClient;
use App\Services\PaygoClient;
use App\Services\PicpayClient;
use App\Services\VigoClient;
use DateTime;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Payments
{

//    public function ecommerce(Payment $payment, $validated)
//    {
//        $ecommercePayment = null;
////                    (object)$this->payment;
//
//        $cieloPayment = (new CieloClient($payment, $validated));
////        dd($payment->transaction);
//
//        $response = $cieloPayment->getStatus($payment->transaction);
//
//        dd($response);
//
//        if($response != null){
//            $payment->status = $response['status'];
//
//            if(Str::contains($payment->status, ['approved', 'canceled','chargeback']))
//            {
//                if(Str::contains($payment->payment_type,["credit", "debit"]))
//                {
//                    if ($payment->save() && $payment->status == "approved")
//                    {
//                        $payment->transaction = $ecommercePayment->Payment->AuthorizationCode;
//                        $payment->receipt = [
//                            'card_number' => $ecommercePayment->Payment->CreditCard->CardNumber,
//                            'flag' => $ecommercePayment->Payment->CreditCard->Brand,
//                            'card_ent_mode' => "TRANSACAO AUTORIZADA COM SENHA",//approximation or password -> criar função
//                            'payer' => $ecommercePayment->Payment->CreditCard->Holder,
//                            'in_installments' => $ecommercePayment->Payment->Installments,
//                            'transaction_code' => $ecommercePayment->Payment->PaymentId,
//                            'receipt' => null
//                        ];
//                    }
//                }
//
//                $payment->save();
//                self::proccessBillets();
//            }
//        }
//
//        return $payment;
//    }
////
//    public function tef(Payment $payment, $validated)
//    {
//        if(Str::contains($payment->status, ['approved', 'canceled','chargeback']))
//        {
//            $this->proccessBillets();
//        }
//        else
//        {
//            $response = (new PaygoClient())->getStatus($this->payment->reference);
//            $this->payment->status = $response['status'];
//            $this->payment->installment = $response['payment']->intencoesVendas[0]->quantidadeParcelas;
//
//            if ($this->payment->save() && $this->payment->status == "approved"){
//                $this->payment->transaction = $response['payment']->intencoesVendas[0]->pagamentosExternos[0]->autorizacao;
////                            $this->payment->customer_origin =
//
//                //"CRIAR FUNÇÃO PARA TRATAR OS DADOS DO CUPOM"
//                $this->payment->receipt = [
//                    'card_number' => null,
//                    'flag' => $response['payment']->intencoesVendas[0]->pagamentosExternos[0]->bandeira,
//                    'card_ent_mode' => null,//approximation or password
//                    'payer' => null,
//                    'transaction_code' => null,
//                    'receipt' => $response['payment']->intencoesVendas[0]->pagamentosExternos[0]->comprovanteAdquirente
//                ];
//            }
//
//            $this->payment->save();
//            $this->proccessBillets();
//            dd('TEf');
//        }
//
//    }
////
//    public function picpay(Payment $payment, $validated)
//    {
//        $payment->status = (new PicpayClient($payment))->getStatus()->status;
//
//        if(Str::contains($payment->status, ['approved', 'canceled','chargeback']))
//        {
//            self::proccessBillets($payment);
//        }
//
//        $payment->save();
//
//        return $payment;
//    }
////
////    public function processing()
////    {
////        try{
////            switch ($this->payment->method) {
////                case "tef":
////                {
////                    if(Str::contains($this->payment->status, ['approved', 'canceled','chargeback']))
////                    {
////                        $this->proccessBillets();
////                    }
////                    else
////                    {
////                        $response = (new PaygoClient())->getStatus($this->payment->reference);
////                        $this->payment->status = $response['status'];
////                        $this->payment->installment = $response['payment']->intencoesVendas[0]->quantidadeParcelas;
////
////                        if ($this->payment->save() && $this->payment->status == "approved"){
////                            $this->payment->transaction = $response['payment']->intencoesVendas[0]->pagamentosExternos[0]->autorizacao;
//////                            $this->payment->customer_origin =
////
////                            //"CRIAR FUNÇÃO PARA TRATAR OS DADOS DO CUPOM"
////                            $this->payment->receipt = [
////                                'card_number' => null,
////                                'flag' => $response['payment']->intencoesVendas[0]->pagamentosExternos[0]->bandeira,
////                                'card_ent_mode' => null,//approximation or password
////                                'payer' => null,
////                                'transaction_code' => null,
////                                'receipt' => $response['payment']->intencoesVendas[0]->pagamentosExternos[0]->comprovanteAdquirente
////                            ];
////                        }
////
////                        $this->payment->save();
////                        $this->proccessBillets();
////                        dd('TEf');
////                    }
////                    break;
////                }
////                case "ecommerce":
////                {
////                    $ecommercePayment = null;
//////                    (object)$this->payment;
////
////                    $response = CieloClient::getStatus($this->payment->transaction);
//////                        dd($response);
////
////                    $this->payment->status = $response['status'];
////
////                    if(Str::contains($this->payment->status, ['approved', 'canceled','chargeback']))
////                    {
//////                        self::proccessBillets();
//////                    }
//////                    else
//////                    {
////
////
////                        if(Str::contains($this->payment->payment_type,["credit", "debit"]))
////                        {
////                            if ($this->payment->save() && $this->payment->status == "approved")
////                            {
////                                $this->payment->transaction = $ecommercePayment->Payment->AuthorizationCode;
////                                $this->payment->receipt = [
////                                    'card_number' => $ecommercePayment->Payment->CreditCard->CardNumber,
////                                    'flag' => $ecommercePayment->Payment->CreditCard->Brand,
////                                    'card_ent_mode' => "TRANSACAO AUTORIZADA COM SENHA",//approximation or password -> criar função
////                                    'payer' => $ecommercePayment->Payment->CreditCard->Holder,
////                                    'in_installments' => $ecommercePayment->Payment->Installments,
////                                    'transaction_code' => $ecommercePayment->Payment->PaymentId,
////                                    'receipt' => null
////                                ];
////                            }
////                        }
////
////                        $this->payment->save();
////                        self::proccessBillets();
////                    }
//////                    return $this->payment;
////                    break;
////                }
////                case "picpay": {
////                    $this->payment->status = (new PicpayClient($this->payment))->getStatus()->status;
////
////                    if(Str::contains($this->payment->status, ['approved', 'canceled','chargeback']))
////                    {
////                        $this->payment->save();
////                        self::proccessBillets();
//////                    }
//////                    else
//////                    {
////
////
////
//////                        if(Str::contains($this->payment->status, ['approved', 'canceled','chargeback'])){
//////                            $this->proccessBillets();
//////                        }
////
//////                        dd('picpay', $this->payment->status);
////
//////                        return $this->payment;
////
////                    }
////                    break;
////                }
////                default: {
////                    break;
////                }
////
////            }
////
//////            switch ($this->payment->method){
//////                case "tef":
//////                    //$this->payment->status = (new PaygoClient())->getPaymentById($this->payment->reference);
//////                    break;
//////                case "ecommerce":
//////                    $this->payment->status = (new CieloClient())->getStatus($this->payment->reference);
//////                    break;
//////                case "picpay":
//////                    $this->payment->status = (new PicpayClient($this->payment))->getStatus()->status;
//////                    break;
//////                default:
//////                    break;
//////            }
////
//////            if (Str::contains($this->payment->status, ['approved', 'canceled','chargeback'])){
//////                $this->proccessBillets();
////////                $this->proccessBillets();
//////            }
////            return $this->payment;
////
////        }catch (Exception $ex){
////            //Armazenar estes erros no banco de dados
////            Log::error("Erro ao efetuar o Callback do pagamento com o ID: #{$this->payment->id}");
////        }
////    }
//
//    public function proccessBillets($payment){
////        dd('Status process: '.$this->payment->status);
//        if (Str::contains($payment->status, ['approved', 'canceled','chargeback'])) {
//            $action = ($payment->status === "approved") ? true : false;
//
//            $paymentDataSlim = [
//                'customerId' => $payment->customer,
//                'paymentId' => $payment->id,
//                'reference' => $payment->reference,
//                'method' => $payment->method,
//                'payment_type' => $payment->payment_type,
//                'place' => (isset($payment->terminal)) ? "autoatendimento" : "central"
//            ];
////            (new VigoClient())->unlockAccount($action);
//
//            foreach ($payment->billets as $billet) {
//                //Informar o caixa aqui caso a baixa seja realmente separada por modalidade
////                ProcessBillets::dispatch((array)$billet, $action, "893");
////                ProcessBillets::dispatch((array)$billet, $action, $this->payment->id);
//                ProcessBillets::dispatch((array)$billet, $action, $paymentDataSlim);
//            }
//        }
//    }

//    public function proccessingBillets($payment){
////        dd($payment->billets);
//
//        if (Str::contains($payment->status, ['approved', 'canceled','chargeback'])) {
//            $action = ($payment->status === "approved") ? true : false;
////            (new VigoClient())->unlockAccount($action);
//
//            foreach ($payment->billets as $billet) {
//                //Informar o caixa aqui caso a baixa seja realmente separada por modalidade
////                ProcessBillets::dispatch((array)$billet, $action, "893");
////                ProcessBillets::dispatch((array)$billet, $action, $this->payment->id);
//                ProcessBillets::dispatch((array)$billet, $action);
//            }
//        }
//    }

    public function findPending()
    {
        $today = Carbon::now()->toDateString();

        $payments = Payment::whereDate('created_at', $today)
            ->where('status', 'created')
            ->get();

        FindPaymentsPending::dispatch($payments);
    }

//    public function runnerJobPaymentsPending()
//    {
//        $payments = $this->findPending();
//        $jobs = [];
//
//        foreach ($payments as $payment){
//            //Processar fila para buscar o pagamento
//
//            $job = ProcessCallback::dispatch($payment);
//
//            $jobs[] = array(
//                'payment_id' => $payment->id,
//                'job'    => ''//Bus::dispatcher($job)->first()->result
//            );
//
//            Log::alert("Pagamento #{$payment->id} processado");
//
////            $job = FindPaymentsPending::dispatch($payment)->onQueue('nome_da_fila');
////            array_push($jobs['payment_id'], $payment);
////            array_push($jobs['job'], Bus::dispatched($job)->first()->result);
//            //Escrever o job para buscar/atualizar o status usando o ProcessCallBack ou buscando manualmente
//        }
//
//        // Obtendo o resultado dos jobs
//        return $jobs;
//    }


}
