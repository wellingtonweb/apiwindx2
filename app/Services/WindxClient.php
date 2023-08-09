<?php

namespace App\Services;

//use App\Models\Payment;
use DateTime;
#use Illuminate\Support\Collection;
use App\Http\Resources\CustomerCollection;
use App\Http\Resources\PaymentCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use App\Http\Controllers\PaymentController;
use Carbon\Carbon;
use App\Models\Payment;
use App\Jobs\ProcessCallback;
use App\Services\CieloClient;
use App\Services\PaygoClient;
use App\Services\PicpayClient;

class WindxClient 
{

    public function scanPaymentsToday(){
        //$payments = self::getCheckPaymentsToday();
        //return $payments;

        $today = Carbon::now()->format('Y-m-d');

        $payments = Payment::where('status', 'created')
                            ->whereDate('created_at', $today)
                            ->get();

        $paymentsFilter = [];

        foreach ($payments as $payment){
            self::checkStatusPayment($payment);
            array_push($paymentsFilter, $payment);
        }

        return $paymentsFilter;

    }

    public function checkDuplicatePayment(){
        //
    }

    public function getCheckPaymentsToday()
    {
        $today = Carbon::now()->format('Y-m-d');

        $payments = Payment::where('status', 'created')
                            ->whereDate('created_at', $today)
                            ->get();

        return new PaymentCollection($payments);
    }

    public function getPaymentsCustomerToday(int $customer_id){

        $today = Carbon::now()->format('Y-m-d');

        $payments = Payment::where('customer', $customer_id)
                            ->where('status', 'approved')
                            ->whereDate('created_at', $today)
                            ->get();
        
        return new PaymentCollection($payments);
    }

    public function checkStatusPayment(Payment $payment)
    {
        if ($payment->payment_type == "pix"){

            $cieloPayment = CieloClient::getPixStatus($payment->transaction);

            if($payment->terminal_id != '' || $payment->terminal_id != null){
                $payment->method = 'tef';
            }

            switch ($cieloPayment->object()->Payment->Status){
                case 2:
                    $payment->status = "approved";
                    break;
                case 3:
                case 10:
                case 13:
                    $payment->status = "refused";
                    break;
                default:
                    $payment->status = "created";
                    break;
            }

            $payment->save();

            if ($payment->save() && $payment->status == "approved"){

                ProcessCallback::dispatch($payment);
            }
        }
        elseif($payment->method == "tef"){

            $response = (new PaygoClient())->getPaymentStatus($payment->reference);

            switch ($response->intencoesVendas[0]->intencaoVendaStatus->id){
                case 10:
                    $payment->status = "approved";
                    break;
                case 18:
                case 19:
                case 20:
                    $payment->status = "canceled";
                    break;
                case 15:
                    $payment->status = "expired";
                    break;
                case 25:
                    $payment->status = "refused";
                    break;
                default:
                    $payment->status = "created";
                    break;
            }

            if ($payment->save() && $payment->status == "approved"){

                    $payment->receipt = $response->intencoesVendas[0]->pagamentosExternos[0]->comprovanteAdquirente;
                    $payment->transaction = $response->intencoesVendas[0]->pagamentosExternos[0]->autorizacao;

                $payment->save();

                ProcessCallback::dispatch($payment);

            }else{
                $payment->receipt = null;
                $payment->save();
            }

        }elseif($payment->method == "picpay"){
            (new PicpayClient($payment))->getStatus();
        }

        return $payment;
    }
}