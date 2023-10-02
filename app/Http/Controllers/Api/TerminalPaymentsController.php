<?php

namespace App\Http\Controllers\Api;

use App\Models\Payment;
use App\Models\Terminal;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\PaymentCollection;

class TerminalPaymentsController extends Controller
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Terminal  $terminal
     * @return PaymentCollection
     */
    public function index(Request $request, Terminal $terminal)
    {
        $this->authorize('view', $terminal);

        $search = $request->get('search', '');

        $payments = $terminal
            ->payments()
            ->search($search)
            ->latest()
            ->get();

        return new PaymentCollection($payments);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Terminal  $terminal
     * @return PaymentResource
     */
    public function store(Request $request, Terminal $terminal)
    {
        $this->authorize('create', Payment::class);

        $validated = $request->validate([
            'customer' => ['required', 'max:255', 'string'],
            'reference' => ['required', 'unique:payments,reference', 'max:255'],
            'billets' => ['required', 'json'],
            'amount' => ['required', 'numeric'],
            'transaction' => ['nullable', 'string'],
            'method' => ['required', 'in:tef,ecommerce'],
            'payment_type' => [
                'required',
                'in:credit,debit,voucher,picpay,pix',
            ],
            'status' => [
                'required',
                'in:created,approved,canceled,refused,chargeback',
            ],
            'receipt' => ['nullable', 'string'],
        ]);

        $payment = $terminal->payments()->create($validated);

        return new PaymentResource($payment);
    }
}
