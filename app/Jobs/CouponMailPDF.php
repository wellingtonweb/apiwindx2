<?php

namespace App\Jobs;

use App\Helpers\Functions;
use App\Mail\CouponPDF;
use App\Models\Payment;
use App\Services\VigoClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CouponMailPDF implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

//    public $tries = 5;

    private $payment_id;
    private $payment;
    private $customer;
    private $pay;
    private $customerFirstName;
    private $mailContent;
    private $billets;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payment_id)
    {
        $this->payment = Payment::where('id', '=', $payment_id)->firstOrFail();
        $this->customer = (new VigoClient())->getCustomer($this->payment['customer']);
        $this->pay = date("d/m/Y", strtotime($this->payment['created_at']));
        $this->customerFirstName = explode(" ", $this->customer->customer[0]['full_name']);
        $this->billets = json_decode(($this->payment->getAttributes())['billets'], true);
        $this->mailContent = [
            "full_name" => $this->customer->customer[0]['full_name'],
            "first_name" => $this->customerFirstName[0],
            "email" => "sup.windx@gmail.com",
//            "email" => $data->customer[0]['email'],
            "title" => "Comprovante de pagamento nº ".$this->payment->id." - Pago em ".$this->pay,
            "payment_id" => $payment_id,
            "payment_created" => $this->pay,
            "billets" => $this->billets,
            "value" => number_format($this->payment->amount, 2, ',', ''),
            "payment" => $this->payment->getAttributes(),
            "date_full" => (new Functions)->getDateFull(),
            "date_time_full" => (new Functions)->getDateTimeFull()
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->mailContent["email"])->send(new CouponPDF($this->mailContent));
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
