<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;

class PaygoClient
{
    protected $url;
    protected $login;
    protected $senha;
    protected $key;
    protected $pessoaId;
    protected $senhaTecnica;

    public function __construct()
    {
        if (getenv('APP_ENV') == 'local') {
            $this->url = getenv('CONTROLPAY_SANDBOX_API_URL');
            $this->login = getenv('CONTROLPAY_SANDBOX_LOGIN');
            $this->senha = getenv('CONTROLPAY_SANDBOX_PASSWORD');
            $this->senhaTecnica = getenv('CONTROLPAY_SANDBOX_TECHNICAL_PASSWORD');
        }else{
            $this->url = getenv('CONTROLPAY_PROD_API_URL');
            $this->login = getenv('CONTROLPAY_PROD_LOGIN');
            $this->senha = getenv('CONTROLPAY_PROD_PASSWORD');
            $this->senhaTecnica = getenv('CONTROLPAY_PROD_TECHNICAL_PASSWORD');
            $this->key = getenv('CONTROLPAY_PROD_KEY');
        }

//        $response = Http::withHeaders([
//            "Content-Type" => "application/json"
//        ])->post("{$this->url}/Login/Login/",[
//            "cpfCnpj" => $this->login,
//            "senha" => $this->senha
//        ]);
//
//        if($response->successful()){
//            $this->key = $response->object()->pessoa->key;
//            $this->pessoaId = $response->object()->pessoa->id;
//        }else{
//            return $response->toException();
//        }
    }

    public function getTerminals($pessoaId = null)
    {
        $response = Http::withHeaders([
            "Content-Type" => "application/json"
        ])->get("{$this->url}/Terminal/GetByPessoaId?key={$this->key}&pessoaId={$pessoaId}" );

        if($response->successful()){
            return $response->json();
        }else{
            return $response->toException();
        }
    }

//    public function pay(Payment $payment)
//    {
//        $response = Http::withHeaders([
//            "Content-Type" => "application/json"
//        ])->post("{$this->url}/Venda/Vender/?key={$this->key}", [
//            "formaPagamentoId" => ($payment->payment_type === "credit") ? 21 : 22,
//            "terminalId" => $payment['terminal']->paygo_id,
//            "referencia" => $payment->reference,
//            "iniciarTransacaoAutomaticamente" => true,
//            "parcelamentoAdmin" => null,
//            "quantidadeParcelas" => 1,
//            "adquirente" => "",
//            "valorTotalVendido" => $payment->amount
//        ]);
//
//        if($response->successful()){
//            $payment->token = $response->object()->intencaoVenda->token;
//
//            $payment->save();
//            return $payment;
//        }else{
//            return $response->toException();
//        }
//    }

    //Implementação do parcelamento
    public function pay(Payment $payment)
    {
        if(isset($payment['billets'][0]->installment)){
            if($payment['billets'][0]->installment > 1){
                $payment->installment = $payment['billets'][0]->installment;
            }
        }

        $payment->save();

        $response = Http::withHeaders([
            "Content-Type" => "application/json"
        ])->post("{$this->url}/Venda/Vender/?key={$this->key}", [
            "formaPagamentoId" => ($payment->payment_type === "credit") ? 21 : 22,
            "terminalId" => $payment['terminal']->paygo_id,
            "referencia" => $payment->reference,
            "iniciarTransacaoAutomaticamente" => true,
            "parcelamentoAdmin" => null,
            "quantidadeParcelas" => $payment['billets'][0]->installment > 1 ? $payment['billets'][0]->installment : 1,
            /*"quantidadeParcelas" => 1,*/
            "adquirente" => "",
            "valorTotalVendido" => $payment->amount
        ]);

        if($response->successful()){
            $paymentUpdate = Payment::find($payment->id);
            $paymentUpdate->token = $response->object()->intencaoVenda->token;
            $paymentUpdate->save();

            return $paymentUpdate;
        }else{
            return $response->toException();
        }
    }

    public function getStatus($reference)
    {
        $response = Http::withHeaders([
            "Content-Type" => "application/json"
        ])->post("{$this->url}/IntencaoVenda/GetByFiltros?key={$this->key}", [
            "referencia" => $reference
        ]);

        if($response->successful()){
            return [
                'status' => self::rewriteStatus($response->object()->intencoesVendas[0]->intencaoVendaStatus->id),
                'payment' => $response->object(),
                'receipt' => self::receiptFormat($response->object()->intencoesVendas[0]->pagamentosExternos[0])
            ];

//            return $response->object();
        }else{
            return $response->toException();
        }
    }

    public function getPaymentById(array $body)
    {
        $response = Http::withHeaders([
            "Content-Type" => "application/json"
        ])->post("{$this->url}/IntencaoVenda/GetByFiltros?key={$this->key}", $body);

        if($response->successful()){
            return $response->object();
        }else{
            return $response->toException();
        }

    }

    public function getDailyPayments(int $terminalId)
    {
        $response = Http::withHeaders([
            "Content-Type" => "application/json"
        ])->post("{$this->url}/IntencaoVenda/GetByFiltros?key={$this->key}", [
            "formaPagamentoId" => null,
            "terminalId" => $terminalId,
            "vendasDia" => true
        ]);

        if($response->successful()){
            return $response->json();
        }else{
            return $response->toException();
        }
    }

    //Enviar $paymentId
    public function cancelPayment($paymentId)
    {

        $response = Http::withHeaders([
            "Content-Type" => "application/json"
        ])->get("{$this->url}/Venda/CancelarVenda?key={$this->key}&pedidoId={$paymentId}");

        if($response->successful()){
            return $response->json();
        }else{
            return $response->toException();
        }

    }

    public function rewriteStatus($status){

        $statusUpdated = "created";

        switch ($status){
            case 10:
                $statusUpdated = "approved";
                break;
            case 18:
            case 19:
            case 20:
                $statusUpdated = "canceled";
                break;
            case 15:
                $statusUpdated = "expired";
                break;
            case 25:
                $statusUpdated = "refused";
                break;
            default:
                $statusUpdated = "created";
                break;
        }

        return $statusUpdated;
    }

    public function receiptFormat($externalPayments)
    {
        if(empty($externalPayments)){
            return null;
        }
        $arrayReceipt = explode("\n", $externalPayments->comprovanteAdquirente);

        return [
            'card_number' => self::getCardNumber($arrayReceipt),
            'flag' => $externalPayments->bandeira,
            'card_ent_mode' => self::getCardEntMode($arrayReceipt)['with_password'],
            'payer' => self::getCardEntMode($arrayReceipt)['payer'],
            'transaction_code' => $externalPayments->autorizacao,
            'receipt_full' => $externalPayments->comprovanteAdquirente
        ];
    }

    public function getCardEntMode($array) {
        $isPassword = array_search("TRANSACAO AUTORIZADA COM SENHA", $array);

        if ($isPassword !== false && isset($array[$isPassword + 1])) {
            $payer = $array[$isPassword + 1];

            return array('with_password' => "TRANSAÇÃO AUTORIZADA COM SENHA", 'payer' => $payer);
        } else {
            return array('with_password' => "TRANSAÇÃO AUTORIZADA POR APROXIMAÇÃO", 'payer' => null);
        }
    }

    public function getCardNumber($array) {
        $cardFormat = '/\d{6}\*{6}\d{4}/';

        foreach ($array as $item) {
            if (preg_match($cardFormat, $item, $matches)) {
                return $matches[0];
            }
        }

        return null;
    }

}
