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
        if (App::environment(['local', 'staging'])) {
            $this->url = getenv('CONTROLPAY_SANDBOX_APIURL');
            $this->login = getenv('CONTROLPAY_SANDBOX_LOGIN');
            $this->senha = getenv('CONTROLPAY_SANDBOX_PASSWORD');
            $this->senhaTecnica = getenv('CONTROLPAY_SANDBOX_TECHNICAL_PASSWORD');
        }else{
            $this->url = getenv('CONTROLPAY_PROD_APIURL');
            $this->login = getenv('CONTROLPAY_PROD_LOGIN');
            $this->senha = getenv('CONTROLPAY_PROD_PASSWORD');
            $this->senhaTecnica = getenv('CONTROLPAY_PROD_TECHNICAL_PASSWORD');
        }



        $response = Http::withHeaders([
            "Content-Type" => "application/json"
        ])->post("{$this->url}/Login/login/",[
            "cpfCnpj" => "{$this->login}",
            "senha" => "{$this->senha}"
        ]);

        if($response->successful()){
            $this->key = $response->object()->pessoa->key;
            $this->pessoaId = $response->object()->pessoa->id;
        }else{
            return $response->toException();
        }
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

    public function pay(Payment $payment)
    {
        $response = Http::withHeaders([
            "Content-Type" => "application/json"
        ])->post("{$this->url}/Venda/Vender/?key={$this->key}", [
            "formaPagamentoId" => ($payment->payment_type === "credit") ? "21" : "22",
            "terminalId" => $payment['terminal']->paygo_id,
            "referencia" => $payment->reference,
            "iniciarTransacaoAutomaticamente" => true,
            "parcelamentoAdmin" => null,
            "quantidadeParcelas" => 1,
            "adquirente" => "REDE",
            "valorTotalVendido" => "{$payment->amount}"
        ]);

        if($response->successful()){
            $payment->token = $response->object()->intencaoVenda->token;
            $payment->save();
            return $payment;
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

    public function cancelPayment()
    {
        $response = Http::withHeaders([
            "Content-Type" => "application/json"
        ])->post("{$this->url}/Venda/CancelarVenda?key={$this->key}", $body);

        if($response->successful()){
            return $response->json();
        }else{
            return $response->toException();
        }

    }

}
