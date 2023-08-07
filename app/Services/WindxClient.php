<?php

namespace App\Services;

use App\Models\Payment;
use DateTime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class WindxClient {

    public function getPaymentsCustomerToday($customer_id){

        $today = Carbon::now()->format('Y-m-d');

        $payments = Payment::where('customer', $customer_id)
                            ->where('status', 'approved')
                            ->whereDate('created_at', $today)
                            ->get();

        return new PaymentCollection($payments);
    }

    public function getCheckPaymentsToday()
    {
        $today = Carbon::now()->format('Y-m-d');

        $payments = Payment::where('status', 'created')
                            ->whereDate('created_at', $today)
                            ->get();

        return new PaymentCollection($payments);
    }
}