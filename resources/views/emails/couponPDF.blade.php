@component('mail::message')

{{ $data['body'] }}

<b>{{ $data['payment_id'] }}<br>
    {{ $data['payment_created'] }}<br>
    {{ $data['value'] }}</b>

@component('mail::button', ['url' => getenv('WHATSAPP_FINANCIAL')])
    Dúvidas?
@endcomponent

Atenciosamente,<br>
{{ config('app.name') }}
@endcomponent
