<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\CieloClient;
use App\Services\PaygoClient;
use App\Services\PicpayClient;
use App\Services\VigoClient;
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
    public $tries = 3;

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
            switch ($this->payment->method){
                case "tef":
                    //$response = (new PaygoClient())->getPaymentById($this->payment->reference);
                    break;
                case "ecommerce":
                    $response = (new CieloClient())->getStatus($this->payment->reference);

                    break;
                case "picpay":
                    $this->paymentg = (new PicpayClient($this->payment))->getStatus();
                    break;
                default:
                    break;
            }

            if (Str::contains($this->payment->status, ['approved', 'canceled','chargeback'])){
                $this->proccessBillets();
            }
        }catch (Exception $ex){
            Log::alert("Erro ao efetuar o Callback do pagamento com o ID: #{$this->payment->id}");

        }

    }

    private function proccessBillets(){
        if (Str::contains($this->payment->status, ['approved', 'canceled','chargeback'])) {
            $action = ($this->payment->status === "approved") ? true : false;
//            (new VigoClient())->unlockAccount($action);

            foreach ($this->payment->billets as $billet) {
                //Informar o caixa aqui caso a baixa seja realmente separada por modalidade
//                ProcessBillets::dispatch((array)$billet, $action, "893");
//                ProcessBillets::dispatch((array)$billet, $action, $this->payment->id);
                ProcessBillets::dispatch((array)$billet, $action);
            }
        }
    }
}
