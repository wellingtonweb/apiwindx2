<?php

namespace App\Jobs;

use App\Events\PaymentApproved;
use App\Models\Payment;
use App\Services\CieloClient;
use App\Services\PaygoClient;
use App\Services\PicpayClient;
use App\Services\VigoClient;
use App\Jobs\ProcessBillets;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessCallback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $payment;
//    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /*
            ENUMS DO BANCO DE DADOS:
                created
                approved
                canceled
                expired
                chargeback
        */

        try{
//            switch ($this->payment->method) {
//                case "tef":
//                {
//                    if(Str::contains($this->payment->status, ['approved', 'canceled','chargeback']))
//                    {
//                        $this->proccessBillets();
//                    }
//                    else
//                    {
//                        $response = (new PaygoClient())->getStatus($this->payment->reference);
//                        $this->payment->status = $response['status'];
//                        $this->payment->installment = $response['payment']->intencoesVendas[0]->quantidadeParcelas;
//
//                        if ($this->payment->save() && $this->payment->status == "approved"){
//                            $this->payment->transaction = $response['payment']->intencoesVendas[0]->pagamentosExternos[0]->autorizacao;
////                            $this->payment->customer_origin =
//
//                            //"CRIAR FUNÇÃO PARA TRATAR OS DADOS DO CUPOM"
//                            $this->payment->receipt = [
//                                'card_number' => null,
//                                'flag' => $response['payment']->intencoesVendas[0]->pagamentosExternos[0]->bandeira,
//                                'card_ent_mode' => null,//approximation or password
//                                'payer' => null,
//                                'transaction_code' => null,
//                                'receipt' => $response['payment']->intencoesVendas[0]->pagamentosExternos[0]->comprovanteAdquirente
//                            ];
//                        }
//
//                        $this->payment->save();
//                        $this->proccessBillets();
//                        dd('TEf');
//                    }
//                    break;
//                }
//                case "ecommerce":
//                {
//                    $ecommercePayment = null;
////                    (object)$this->payment;
//
//                    $response = CieloClient::getStatus($this->payment->transaction);
//                        dd($response);
//
//                    $this->payment->status = $response['status'];
//
//                    if(Str::contains($this->payment->status, ['approved', 'canceled','chargeback']))
//                    {
////                        self::proccessBillets();
////                    }
////                    else
////                    {
//
//
//                        if(Str::contains($this->payment->payment_type,["credit", "debit"]))
//                        {
//                            if ($this->payment->save() && $this->payment->status == "approved")
//                            {
//                                $this->payment->transaction = $ecommercePayment->Payment->AuthorizationCode;
//                                $this->payment->receipt = [
//                                    'card_number' => $ecommercePayment->Payment->CreditCard->CardNumber,
//                                    'flag' => $ecommercePayment->Payment->CreditCard->Brand,
//                                    'card_ent_mode' => "TRANSACAO AUTORIZADA COM SENHA",//approximation or password -> criar função
//                                    'payer' => $ecommercePayment->Payment->CreditCard->Holder,
//                                    'in_installments' => $ecommercePayment->Payment->Installments,
//                                    'transaction_code' => $ecommercePayment->Payment->PaymentId,
//                                    'receipt' => null
//                                ];
//                            }
//                        }
//
//                        $this->payment->save();
//                        self::proccessBillets();
//                    }
////                    return $this->payment;
//                    break;
//                }
//                case "picpay": {
//                    $this->payment->status = (new PicpayClient($this->payment))->getStatus()->status;
//
//                    if(Str::contains($this->payment->status, ['approved', 'canceled','chargeback']))
//                    {
//                        $this->payment->save();
//                        $this->proccessBillets();
////                    }
////                    else
////                    {
//
//
//
////                        if(Str::contains($this->payment->status, ['approved', 'canceled','chargeback'])){
////                            $this->proccessBillets();
////                        }
//
////                        dd('picpay', $this->payment->status);
//
////                        return $this->payment;
//
//                    }
//                    break;
//                }
//                default: {
//                    break;
//                }
//
//            }

            if (Str::contains($this->payment->status, ['approved'])) {
                $action = ($this->payment->status === "approved") ? true : false;



                $paymentDataSlim = [
                    'customerId' => $this->payment->customer,
                    'paymentId' => $this->payment->id,
                    'reference' => $this->payment->reference,
                    'method' => $this->payment->method,
                    'paymentType' => $this->payment->payment_type,
                    'place' => (isset($this->payment->terminal)) ? "autoatendimento" : "central"
                ];

                foreach ($this->payment->billets as $billet) {
                    //Informar o caixa aqui caso a baixa seja realmente separada por modalidade
//                ProcessBillets::dispatch((array)$billet, $action, "893");
//                ProcessBillets::dispatch((array)$billet, $action, $this->payment->id);
                    ProcessBillets::dispatch((array)$billet, $action, $paymentDataSlim);
                }
            }

            return $this->payment;

        }catch (Exception $ex){
            //Armazenar estes erros no banco de dados
            Log::error("Erro ao efetuar o Callback do pagamento com o ID: #{$this->payment->id}");
        }

    }

    public function proccessBillets(){
//        dd('Status process: '.$this->payment->status);
        if (Str::contains($this->payment->status, ['approved'])) {
//        if (Str::contains($this->payment->status, ['approved', 'canceled','chargeback'])) {
            $action = ($this->payment->status === "approved") ? true : false;

            $paymentDataSlim = [
                'customerId' => $this->payment->customer,
                'paymentId' => $this->payment->id,
                'reference' => $this->payment->reference,
                'method' => $this->payment->method,
                'payment_type' => $this->payment->payment_type,
                'place' => (isset($this->payment->terminal)) ? "autoatendimento" : "central"
            ];
//            (new VigoClient())->unlockAccount($action);

            foreach ($this->payment->billets as $billet) {
                //Informar o caixa aqui caso a baixa seja realmente separada por modalidade
//                ProcessBillets::dispatch((array)$billet, $action, "893");
//                ProcessBillets::dispatch((array)$billet, $action, $this->payment->id);
                ProcessBillets::dispatch((array)$billet, $action, $paymentDataSlim);
            }
        }
    }

//    /**
//     * Determine the time at which the listener should timeout.
//     *
//     * @return \DateTime
//     */
//    public function retryUntil()
//    {
//        return now()->addMinutes(1);
//    }
}
