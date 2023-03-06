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
    private $vigoClient;
//    private $paymentType;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $billet,$action)
//    public function __construct(array $billet,$action, $payment_type)
    {
        $this->billet = $billet;
        $this->action = $action;
        $this->vigoClient = new VigoClient();
//        $this->paymentType;
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
            $this->vigoClient->checkoutBillet($this->billet);
        }else{
//            $this->vigoClient->reverseBillet((array)$this->billet, $this->paymentType);
            $this->vigoClient->reverseBillet((array)$this->billet);
        }
    }
}
