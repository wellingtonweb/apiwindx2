<!doctype html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html charset=utf-8"/>
    <title>Windx Telecomunicações - 2ª via de comprovante</title>
    <style>
        @page { margin: 0; }

        body{
            background-color: #fff9f2 !important;
            color: black;
        }

        #content{
            display: none;
            justify-content: center;
            margin: 0 auto;
        }
        .comprovante{
            font-family: Courier, Lucida Console,Lucida Sans Typewriter,monaco,Bitstream Vera Sans Mono,monospace;
            margin: 0;
            width: 70mm;
            font-size: 10px !important;
            text-align: center;
        }

        .comprovante p{
            line-height: 12px;
        }
        .comprovante .table-coupon {
            text-align: center !important;
            line-height: 15px !important;
        }

        .comprovante table tbody, .comprovante table tfoot{
            line-height: 15px;
        }

        .comprovante .right {
            text-align: right !important;
            width: 40%;
            font-weight: bold;
        }

        .comprovante .left {
            text-align: left !important;
            width: 60%;
        }

        @media print{

            #loader{
                display: none;
            }

            #content{
                display: block;
                text-align: center;
                font-size: 10px !important;
                background-color: darkgrey !important;
            }

            p.coupon_customer_fullname {
                line-height: 12px !important;
                padding-top: 1rem
            }
        }
    </style>
</head>
<body>
<div id="container">
    <div class="document">
        <div id="coupon" class="d-flex justify-content-center comprovante">
            <div class="coupon-print-central">
                <div id="container-coupon" class="xpto">
                    <table id="example" class="printer-ticket">
                        <thead>
                        <tr>
                            <th class="text-center" colspan="2">
                                <img style="width: 20mm !important; margin-top: 1rem" src="{{ public_path('assets/img/logo2.png') }}" class="logo pt-2">
                            </th>
                        </tr>
                        <tr class="b-top">
                            <th class="ttu text-center" colspan="2">
                                <h3 style="text-transform: uppercase; letter-spacing: 1px "><strong>Comprovante de pagamento</strong></h3>
                                <span style="letter-spacing: 1px; padding-top: 1rem; padding-bottom: 1rem;">(Cupom não fiscal)</span>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr class="ttu b-top" >
                            <td class="right">Cliente: </td>
                            <td id="coupon_reference-" class="left" style="padding-top: 1rem">
                                <span id="coupon_customer_id">ID</span>
                                <span class="coupon_customer_fullname">- full name</span>
                            </td>
                        </tr>
                        <tr class="ttu b-top">
                            <td class="right">Pagamento ID: </td>
                            <td id="coupon_reference" class="left" style="max-width: 74mm">
                                {{$id}}
                            </td>
                        </tr>
                        <tr class="ttu b-top">
                            <td class="right">Referência: </td>
                            <td id="coupon_reference" class="left" style="max-width: 74mm">
                                {{$reference}}
                            </td>
                        </tr>
                        <tr class="ttu b-top">
                            <td class="right">Data Hora: </td>
                            <td id="coupon_created_at" class="left">
                                {{date("d/m/Y H:i:s", strtotime($created_at))}}
                            </td>
                        </tr>
                        <tr class="ttu b-top">
                            <td class="right">
                                {{sizeof($billets) > 1 ? 'Fatura(s):' : 'Fatura:'}}
                            </td>
                            <td id="coupon_billets" class="left">
                                @foreach($billets as $key => $info)
                                    {{--                                        {{ $info->reference }} {{ $info->due}}--}}
                                    @if($billets >= 1)
                                        {{ $info->reference }} {{!empty($info->duedate) ? '('.$info->duedate.')' : '' }} {{!$loop->last ? ',':''}}
                                    @endif
                                @endforeach
                            </td>
                        </tr>
                        <tr class="ttu b-top">
                            <td class="right">Pago via: </td>
                            <td id="coupon_method" class="left">
                                @if($method == 'ecommerce' && $terminal_id == null)
                                    Central do Assinante
                                @else
                                    Auto-atendimento
                                @endif
                            </td>
                        </tr>
                        <tr class="ttu b-top">
                            <td class="right">Modalidade: </td>
                            <td id="coupon_payment_type" class="left">
                                @if($payment_type == 'credit')
                                    Crédito
                                @elseif($payment_type == 'debit')
                                    Débito
                                @elseif($payment_type == 'pix')
                                    Pix
                                @else
                                    Picpay
                                @endif
                            </td>
                        </tr>
                        @if($payment_type == 'credit' || $payment_type == 'debit')
                            @if($receipt != null)
                                <tr class="ttu b-top">
                                    <td class="right">Cartão: </td>
                                    <td id="coupon_card_number" class="left" style="max-width: 74mm">
                                        {{ (json_decode($receipt))->card_number }}
                                    </td>
                                </tr>
                                <tr class="ttu b-top">
                                    <td class="right">Bandeira: </td>
                                    <td id="coupon_flag" class="left" style="max-width: 74mm">
                                        {{ (json_decode($receipt))->flag }}
                                    </td>
                                </tr>
                                <tr class="ttu b-top">
                                    <td class="right">Titular: </td>
                                    <td id="coupon_payer" class="left" style="max-width: 74mm">
                                        {{ (json_decode($receipt))->payer }}
                                    </td>
                                </tr>
                                @if((json_decode($receipt))->in_installments > 1)
                                    <tr class="ttu b-top">
                                        <td class="right">Acordo: </td>
                                        <td id="coupon_flag" class="left" style="max-width: 74mm">
                                            Parcelado em {{ (json_decode($receipt))->in_installments }}x
                                        </td>
                                    </tr>
                                @endif
                            @endif
                        @endif
                        <tr class="ttu b-top">
                            <td class="right">Valor: </td>
                            <td class="left">R$ <span id="coupon_value">
                                        @php
                                            $total = 0;
                                            foreach ($billets as $billet)
                                            {
                                                $total += $billet->value;
                                            }
                                        @endphp
                                    {{number_format($total, 2, ',', '.') }}
                                    </span>
                            </td>
                        </tr>
                        <tr class="ttu b-top">
                            <td class="right">Juros + Multas: </td>
                            <td class="left">R$ <span id="coupon_fees">
                                        @php
                                            $totalAdition = 0;
                                            foreach ($billets as $billet)
                                            {
                                                $totalAdition += $billet->addition;
                                            }
                                        @endphp
                                    {{number_format($totalAdition, 2, ',', '.') }}
                                    </span>
                            </td>
                        </tr>
                        <tr class="ttu b-top pb-4" style="font-weight: bold">
                            <td class="right">Valor pago: </td>
                            <td class="left">
                                R$ <span id="coupon_amount">
                                            {{number_format($amount, 2, ',', '.') }}
                                        </span>
                            </td>
                        </tr>
                        </tbody>
                        <tfoot>
                        @if(isset((json_decode($receipt))->card_ent_mode))
                            <tr class="ttu b-top text-center ">
                                <td colspan="4" style="text-align: center; padding-top: 1rem; letter-spacing: 1px">
                                    {{ (json_decode($receipt))->card_ent_mode }}
                                </td>
                            </tr>
                        @endif
                        <tr class="sup b-top pt-2 font-weight-bold">
                            <td colspan="4" style="text-align: center; padding-top: 1rem">
                                <b>Ouvidoria: 0800 028 2309</b></br>
                                <b>Financeiro: (28)3532-2309</b></br></br>
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
