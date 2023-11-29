<?php

namespace App\Listeners;

use App\Events\PaymentApproved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPaymentApprovalNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\PaymentApproved  $event
     * @return void
     */
    public function handle(PaymentApproved $event)
    {
        $payment = $event->payment;

        // Enviar notificação via websocket
        event(new \App\Events\PaymentApprovedNotification($payment));
    }
}
