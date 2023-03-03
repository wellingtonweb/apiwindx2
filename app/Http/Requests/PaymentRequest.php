<?php

namespace App\Http\Requests;

use App\Rules\ArrayInspect;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
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
            'billets' => ['present', 'array'],
            'billets.*' => new ArrayInspect([
                'billet_id' => ["required","integer"],
                'reference' => 'required|string',
                'value' => 'required|numeric',
                'addition' => 'required|numeric',
                'discount' => 'required|numeric',
            ]),
            'installment' => 'integer',
            'method' => ['required', 'in:tef,ecommerce,picpay'],
            'terminal_id' => ['required_if:method,tef', 'exists:terminals,id'],
            //-------------Required if picpay|ecommerce method--------------
            'payment_type' => ['required_if:method,tef,ecommerce'],
            //--------------------------------------------------------------

            //-------------Required if picpay method------------------------
                'buyer' => ['required_if:method,picpay'],
                'buyer.first_name' => 'required_if:method,picpay|string|required_if:payment_tupe,pix',
                'buyer.last_name' => 'required_if:method,picpay|string|required_if:payment_tupe,pix',
                'buyer.email' => 'required_if:method,picpay|email',
                'buyer.cpf_cnpj' => 'required_if:method,picpay|required_if:payment_tupe,pix',
                'buyer.phone' => 'required_if:method,picpay|string',
            //--------------------------------------------------------------

            //-------------Required if ecommerce method------------------------
            /*
            'card' => ['required_if:method,ecommerce'],
            'card.holder_name' => 'required_if:method,ecommerce|string',
            'card.card_number' => 'required_if:method,ecommerce|string',
            'card.cvv' => 'required_if:method,ecommerce|integer',
            'card.bandeira' => 'required_if:method,ecommerce|string',
            'card.expiration_date' => 'required_if:method,ecommerce|date_format:m/Y',
            */

            'card' => ['required_if:method,ecommerce|required_if:payment_tupe,credit,debit'],
            'card.holder_name' => ['required_if:method,ecommerce|string|required_if:payment_tupe,credit,debit'],
            'card.card_number' => ['required_if:method,ecommerce|string|required_if:payment_tupe,credit,debit'],
            'card.cvv' => ['required_if:method,ecommerce|integer|required_if:payment_tupe,credit,debit'],
            'card.bandeira' => ['required_if:method,ecommerce|string|required_if:payment_tupe,credit,debit'],
            'card.expiration_date' => ['required_if:method,ecommerce|date_format:m/Y|required_if:payment_tupe,credit,debit'],
            //--------------------------------------------------------------

        ];

    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'billets' => json_decode($this->billets),
        ]);
    }
}
