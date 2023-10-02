<?php
namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class TerminalUpdateRequest extends FormRequest
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
            'name' => [
                'required',
                Rule::unique('terminals', 'name')->ignore($this->terminal),
                'string',
            ],
            'ip_address' => [
                'required',
                Rule::unique('terminals', 'ip_address')->ignore(
                    $this->terminal
                ),
            ],
            'remote_id' => ['nullable', 'string'],
            'remote_password' => ['nullable', 'string'],
            'active' => ['nullable', 'boolean'],
            'responsible_name' => ['required', 'string'],
            'contact_primary' => ['required', 'string'],
            'contact_secondary' => ['nullable', 'string'],
            'street' => ['required', 'string'],
            'number' => ['nullable', 'numeric'],
            'complement' => ['nullable', 'string'],
            'district' => ['required', 'string'],
            'city' => ['required', 'string'],
            'state' => ['required', 'string'],
            'zipcode' => ['required', 'string'],
            'paygo_id' => [
                'required',
                Rule::unique('terminals', 'paygo_id')->ignore($this->terminal),
                'string',
            ],
            'paygo_login' => ['required', 'string'],
            'paygo_password' => ['required', 'string'],
        ];
    }
}
