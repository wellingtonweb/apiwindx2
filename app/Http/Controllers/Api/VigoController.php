<?php

namespace App\Http\Controllers\Api;

use App\Services\VigoServer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VigoController extends Controller
{
    public function cielo()
    {
        return (new VigoServer())->getPaymentsCieloOld();
    }

    public function paygo()
    {
        return (new VigoServer())->getPaymentsPaygoOld();
    }

    public function picpay()
    {
        return (new VigoServer())->getPaymentsPicpayOld();
    }

    public function terminals()
    {
        return (new VigoServer())->getPaymentsTerminalsOld();
    }
}
