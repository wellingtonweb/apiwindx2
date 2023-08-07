<?php

namespace App\Services;

use App\Models\Payment;
use DateTime;
#use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class WindxClient 
{
    public function getCheckPaymentsToday()
    {
        $today = Carbon::now()->format('Y-m-d');

        return Payment::where('status', 'created')
                            ->whereDate('created_at', $today)
                            ->get();
    }

    public function getPaymentsCustomerToday(int $customer_id){

        $today = Carbon::now()->format('Y-m-d');

        return Payment::where('customer', $customer_id)
                            ->where('status', 'approved')
                            ->whereDate('created_at', $today)
                            ->get();

    }

    
}