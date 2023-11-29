<?php

namespace App\Jobs;

use App\Events\PaymentApproved;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


class FindPaymentsPending implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payments;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payments)
    {
        $this->payments = $payments;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

//        dd($this->payments);

        foreach ($this->payments as $payment) {
//            dd($payment);
//            Log::alert($payment['id']);
            if($payment->status === 'approved'){
                event(new PaymentApproved("Pagamento {$payment->id} aprovado com sucesso!"));
            }

            ProcessCallback::dispatch($payment);
        }
    }
}
