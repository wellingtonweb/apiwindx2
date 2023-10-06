<?php


namespace App\Helpers;


use App\Jobs\ProcessBillets;
use Illuminate\Support\Str;

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
}
