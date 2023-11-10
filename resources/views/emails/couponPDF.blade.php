@component('mail::message')
{{$data['date_full']}}<br><br>

Olá <b>{{$data['first_name']}}</b>, segue em anexo seu comprovante de pagamento!
{{--{{ $data['body'] }}--}}


<b>Pagamento nº: </b>{{ $data['payment_id'] }}<br>
<b>{{count($data['billets']) > 1 ? 'Faturas Nº: ' : 'Fatura Nº: '}}</b>
@foreach($data['billets'] as $info)
    @if(count($data['billets']) >= 1)
        {{ $info['reference'] }} {{!empty($info['duedate']) ? '('.$info['duedate'].')' : '' }} {{!$loop->last ? ',':''}}
    @endif
@endforeach
<br>
<b>Data do pagamento: </b>{{ $data['payment_created'] }}<br>
<b>Valor pago: R$ {{ $data['value'] }}</b>

@component('mail::button', ['url' => getenv('WHATSAPP_FINANCIAL')])
    Dúvidas?
@endcomponent

Atenciosamente,<br>
{{ config('app.name') }}
@endcomponent
