<?php
namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class PaymentUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'customer' => ['required', 'max:255', 'string'],
            'reference' => [
                'required',
                Rule::unique('payments', 'reference')->ignore($this->payment),
                'max:255',
            ],
            'billets' => ['required', 'json'],
            'amount' => ['required', 'numeric'],
            'transaction' => ['nullable', 'string'],
            'method' => ['required', 'in:tef,ecommerce,picpay'],
            'payment_type' => ['required','in:credit,debit'],
            'receipt' => ['nullable', 'string'],
            'terminal_id' => ['required', 'exists:terminals,id'],
        ];
    }
}
