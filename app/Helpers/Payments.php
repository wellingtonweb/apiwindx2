<?php


namespace App\Helpers;


use App\Jobs\ProcessBillets;
use App\Models\Payment;
use Illuminate\Support\Facades\Bus;
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
            ->pluck('id');

        return $payments->toArray();
    }

    public function runnerJobPaymentsPending()
    {
        $payments = $this->findPending();
        $jobs = [];

        foreach ($payments as $payment){
            //Processar fila para buscar o pagamento
            $job = FindPaymentsPending::dispatch($payment)->onQueue('nome_da_fila');
            array_push($jobs['payment_id'], $payment);
            array_push($jobs['job'], Bus::dispatched($job)->first()->result);
            //Escrever o job para buscar/atualizar o status usando o ProcessCallBack ou buscando manualmente
        }

        // Obtendo o resultado dos jobs
        return $jobs;
    }
}
