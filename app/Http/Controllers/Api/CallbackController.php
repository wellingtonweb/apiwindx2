<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessCallback;
use App\Models\Payment;
use App\Services\PicpayClient;
use Illuminate\Http\Request;

class CallbackController extends Controller
{
    public function index(Request $request)
    {
        //NÃO MEXER NO CALLBACK PARA NÃO INFLUENCIAR A QUEUE

        $referenceId = array_values($request->only([
            'intencaoVendaReferencia',
            'referenceId',
            'MerchantOrderId'
        ]))[0];

        if(!empty($referenceId)){
            $payment = Payment::where(['reference' => $referenceId])->first();
            if ($payment){
                ProcessCallback::dispatch($payment);
                return response()->json('success',200);
            }else{
                return response()->json('error',422);
            }
        }else{
            return response()->json('error',400);
        }

    }
}
