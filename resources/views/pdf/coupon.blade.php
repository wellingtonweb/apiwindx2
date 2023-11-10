<!doctype html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html charset=utf-8"/>
    <title>Windx Telecomunicações - 2ª via de comprovante</title>
    <style>
        @page { margin: 0; }

        body{
            background-color: #fff9f2 !important;
            color: #0a1520;
        }

        #content{
            display: none;
            justify-content: center;
            margin: 0 auto;
        }
        .comprovante{
            font-family: Courier, Lucida Console,Lucida Sans Typewriter,monaco,Bitstream Vera Sans Mono,monospace;
            margin: 0;
            width: 68mm;
            font-size: .5rem !important;
            text-align: center;
            padding: .33rem;
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

        .line-1{
            padding-top: 1rem !important;
        }

        .line-1-5{
            padding-top: 1.5rem !important;
        }

        .line-2{
            padding-top: 2rem !important;
        }

        .comprovante .right {
            text-align: right !important;
            width: 35%;
            font-weight: bold;
        }

        .comprovante .left {
            text-align: left !important;
            width: 65%;
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
                                <img style="width: 20mm !important; margin-top: 1rem" src="https://terminal.windx.com.br/assets/img/logo2.png" class="logo pt-2">
                            </th>
                        </tr>
                        <tr class="b-top" >
                            <th class="ttu text-center" colspan="2" style="border-bottom: 1px solid #cfcfcf">
                                <h3 style="text-transform: uppercase; letter-spacing: 1px "><strong>Comprovante de pagamento</strong></h3>
                                <span style="letter-spacing: 1px; padding-top: 1rem; padding-bottom: 1rem;">(Cupom não fiscal)</span><br>
                                <p>
                                <small style="color: #5b5b5b; padding-top: 1rem; padding-bottom: 1rem;">
                                    {{ $date_time_full }}
                                </small>
                                </p>
                            </th>
                        </tr>
                        </thead>
                        <tbody>

                        <tr class="ttu b-top">
                            <td class="right" style="padding-top: 1rem">Pagamento Nº: </td>
                            <td id="coupon_reference" class="left" style="max-width: 74mm; padding-top: 1rem">
                                {{$payment['id']}}
                            </td>
                        </tr>
                        <tr class="ttu b-top">
                            <td class="right">Referência: </td>
                            <td id="coupon_reference" class="left" style="max-width: 74mm">
                                {{$payment['reference']}}
                            </td>
                        </tr>
                        <tr class="ttu b-top">
                            <td class="right">Data Hora: </td>
                            <td id="coupon_created_at" class="left">
                                {{date("d/m/Y H:i:s", strtotime($payment['created_at']))}}
                            </td>
                        </tr>
                        <tr class="ttu b-top" >
                            <td class="right">Cliente: </td>
                            <td id="coupon_reference-" class="left" >
                                <span id="coupon_customer_id">{{ $payment['customer'] }}</span>
                                <span class="coupon_customer_fullname">- {{ $full_name }}</span>
                            </td>
                        </tr>
                        <tr class="ttu b-top">
                            <td class="right">
                                {{count(json_decode($payment['billets'], true)) > 1 ? 'Faturas Nº: ' : 'Fatura Nº: '}}
                            </td>
                            <td id="coupon_billets" class="left">
                                @foreach(json_decode($payment['billets'], true) as $info)
                                    @if(json_decode($payment['billets'], true) > 1)
                                        {{ $info['reference'] }} {{!empty($info['duedate']) ? '('.$info['duedate'].'), ' : ', ' }}<br>
                                    @else
                                        {{ $info['reference'] }} {{!empty($info['duedate']) ? '('.$info['duedate'].')' : '' }}
                                    @endif
                                @endforeach
                            </td>
                        </tr>
                        <tr class="ttu b-top">
                            <td class="right">Pago via: </td>
                            <td id="coupon_method" class="left">
                                @if($payment['method'] == 'ecommerce' && $payment['terminal_id'] == null)
                                    Central do Assinante
                                @else
                                    Auto-atendimento
                                @endif
                            </td>
                        </tr>
                        <tr class="ttu b-top">
                            <td class="right">Modalidade: </td>
                            <td id="coupon_payment_type" class="left">
                                @if($payment['payment_type'] == 'credit')
                                    Crédito
                                @elseif($payment['payment_type'] == 'debit')
                                    Débito
                                @elseif($payment['payment_type'] == 'pix')
                                    Pix
                                @else
                                    Picpay
                                @endif
                            </td>
                        </tr>
                        @if($payment['payment_type'] == 'credit' || $payment['payment_type'] == 'debit')
                            @if(isset((json_decode($payment['receipt']))->card_number))
                                <tr class="ttu b-top">
                                    <td class="right">Cartão: </td>
                                    <td id="coupon_card_number" class="left" style="max-width: 74mm">
                                        {{ (json_decode($payment['receipt']))->card_number }}
                                    </td>
                                </tr>
                            @endif
                            @if(isset((json_decode($payment['receipt']))->flag))
                                <tr class="ttu b-top">
                                    <td class="right">Bandeira: </td>
                                    <td id="coupon_flag" class="left" style="max-width: 74mm">
                                        {{ (json_decode($payment['receipt']))->flag }}
                                    </td>
                                </tr>
                            @endif
                            @if(isset((json_decode($payment['receipt']))->payer))
                                <tr class="ttu b-top">
                                    <td class="right">Titular: </td>
                                    <td id="coupon_payer" class="left" style="max-width: 74mm">
                                        {{ (json_decode($payment['receipt']))->payer }}
                                    </td>
                                </tr>
                            @endif
                            @if(isset((json_decode($payment['receipt']))->in_installments) && (json_decode($payment['receipt']))->in_installments > 1)
                                <tr class="ttu b-top">
                                    <td class="right">Acordo: </td>
                                    <td id="coupon_flag" class="left" style="max-width: 74mm">
                                        Parcelado em {{ (json_decode($payment['receipt']))->in_installments }}x
                                    </td>
                                </tr>
                            @endif
                        @endif
                        <tr class="ttu b-top">
                            <td class="right">Valor: </td>
                            <td class="left">R$ <span id="coupon_value">
                                @php
                                    $total = 0;
                                    foreach (json_decode($payment['billets'], true) as $billet)
                                    {
                                        $total += $billet['value'];
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
                                    foreach (json_decode($payment['billets'], true) as $billet)
                                    {
                                        $totalAdition += $billet['addition'];
                                    }
                                @endphp
                                {{number_format($totalAdition, 2, ',', '.') }}
                                </span>
                            </td>
                        </tr>
                        <tr class="ttu b-top pb-4" style="font-weight: bold;">
                            <td class="right ">Valor pago: </td>
                            <td class="left ">
                                R$ <span id="coupon_amount">
                                      {{number_format($payment['amount'], 2, ',', '.') }}
                                   </span>
                            </td>
                        </tr>
                        @if(isset((json_decode($payment['receipt']))->card_ent_mode))
                        <tr class="ttu b-top text-center ">
                            <td colspan="4" style="text-align: center; letter-spacing: 1px">
                                <p>{{ (json_decode($payment['receipt']))->card_ent_mode }}</p>
                            </td>
                        </tr>
                        @endif
                        <tr><td colspan="4"class="line-1"></td></tr>
                        </tbody>
                        <tfoot>
                            <tr class="sup b-top pt-2 font-weight-bold">
                                <td colspan="4" style="text-align: center; padding-top: .5rem; border-top: 1px solid #cfcfcf">
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
