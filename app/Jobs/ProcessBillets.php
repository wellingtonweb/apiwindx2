<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\VigoClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBillets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $billet;
    private $action;
    private $place;
    private $payment;
    private $vigoClient;
//    private $paymentType;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $billet,$action, array $payment)
    {
        $this->billet = $billet;
        $this->action = $action;
        $this->payment = $payment;
        $this->vigoClient = new VigoClient();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->action){
//            $this->vigoClient->checkoutBillet($this->billet, $this->paymentType);
            $this->vigoClient->checkoutBillet($this->billet, $this->payment);

//            $this->vigoClient->serviceStore($this->payment);
//            $this->vigoClient->serviceStore();
        }else{
//            $this->vigoClient->reverseBillet((array)$this->billet, $this->paymentType);

            //Corrigir bug que estorna boleto após retorno se cancelado sem verificar se existe algum pagamento aprovado.
            //$this->vigoClient->reverseBillet((array)$this->billet);
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
