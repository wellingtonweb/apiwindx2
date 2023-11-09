<?php


namespace App\Helpers;


use App\Helpers\Functions;
use App\Http\Resources\PaymentResource;
use App\Jobs\CouponMailPDF;
use App\Jobs\ProcessBillets;
use App\Jobs\ProcessCallback;
use App\Models\Payment;
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

    public function proccessingBillets($payment){
//        dd($payment->billets);

        if (Str::contains($payment->status, ['approved', 'canceled','chargeback'])) {
            $action = ($payment->status === "approved") ? true : false;
//            (new VigoClient())->unlockAccount($action);

            foreach ($payment->billets as $billet) {
                //Informar o caixa aqui caso a baixa seja realmente separada por modalidade
//                ProcessBillets::dispatch((array)$billet, $action, "893");
//                ProcessBillets::dispatch((array)$billet, $action, $this->payment->id);
                ProcessBillets::dispatch((array)$billet, $action);
            }
        }
    }

    public function findPending()
    {
        $today = Carbon::now()->toDateString();

        $payments = Payment::whereDate('created_at', $today)
            ->where('status', 'created')
            ->get();
//            ->pluck('id');

        if (empty($payments->count())) {
            return [];
        }

        return $payments;
    }

    public function runnerJobPaymentsPending()
    {
        $payments = $this->findPending();
        $jobs = [];

        foreach ($payments as $payment){
            //Processar fila para buscar o pagamento

            $job = ProcessCallback::dispatch($payment);

            $jobs[] = array(
                'payment_id' => $payment->id,
                'job'    => ''//Bus::dispatcher($job)->first()->result
            );

            Log::alert("Pagamento #{$payment->id} processado");

//            $job = FindPaymentsPending::dispatch($payment)->onQueue('nome_da_fila');
//            array_push($jobs['payment_id'], $payment);
//            array_push($jobs['job'], Bus::dispatched($job)->first()->result);
            //Escrever o job para buscar/atualizar o status usando o ProcessCallBack ou buscando manualmente
        }

        // Obtendo o resultado dos jobs
        return $jobs;
    }

    public function sendCoupon($payment_id)
    {
//        $payment = Payment::where('id', '=', $payment_id)->firstOrFail();
//
//        $mailContent = [];
//
//        $pay = date("d/m/Y", strtotime($payment->created_at));
//        $data = (new VigoClient())->getCustomer($payment->customer);
//        $customerFirstName = explode(" ", $data->customer[0]['full_name']);
//
//        $mailContent = [
//            "full_name" => $data->customer[0]['full_name'],
//            "email" => "sup.windx@gmail.com",
////            "email" => $data->customer[0]['email'],
//            "title" => "Comprovante de pagamento nº ".$payment->id." - Pago em ".$pay,
//            "body" => "Olá ".$customerFirstName[0].", segue em anexo seu comprovante de pagamento!",
//            "payment_id" => "Pagamento nº: ".$payment->id,
//            "payment_created" => "Data do pagamento: ".$pay,
//            "value" => "Valor pago: R$ ".number_format($payment->amount, 2, ',', ''),
//            "payment" => $payment->getAttributes(),
//            "date_full" => (new Functions)->getDateFull()
//        ];

        CouponMailPDF::dispatch($payment_id);

        return response()->json('E-mail enviado com sucesso!');

    }
}
