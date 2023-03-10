<?php

namespace App\Services;

//use App\Models\Payment;
//use Cielo\API30\Ecommerce\CieloEcommerce;
//use Cielo\API30\Ecommerce\Customer;
//use Cielo\API30\Ecommerce\Environment;
//use Cielo\API30\Ecommerce\Sale;
//use Cielo\API30\Merchant;

use App\Models\Payment;
use Cielo\API30\Ecommerce\CieloEcommerce;
use Cielo\API30\Ecommerce\Customer;
use Cielo\API30\Ecommerce\Environment;
use Cielo\API30\Ecommerce\Request\CieloRequestException;
use Cielo\API30\Ecommerce\Sale;
use Cielo\API30\Ecommerce\Payment as CieloPayment;
use Cielo\API30\Merchant;
use Illuminate\Support\Facades\Http;

class CieloClient
{
    protected $environment;
    protected $merchant;
    protected $merchantKey;
    protected $client;
    protected $paymentData;
    protected $order;
    protected $sale;
    protected $payment;
    protected $returnUrl;
    protected $response;

    public function __construct(Payment $order, array $paymentData)
    {
        if (getenv('APP_ENV') == 'local') {
            $this->environment = Environment::sandbox();
            $this->merchant = (new Merchant(getenv('CIELO_SANDBOX_MERCHANT_ID'), getenv('CIELO_SANDBOX_MERCHANT_KEY')));
        } else {
            $this->environment = Environment::production();
            $this->merchant = (new Merchant(getenv('CIELO_PROD_MERCHANT_ID'), getenv('CIELO_PROD_MERCHANT_KEY')));
        }

//        $this->order = $order;
//        $this->paymentData = (object) $paymentData;
//        $this->sale = new Sale($this->order->reference);
//        $this->sale->customer($this->paymentData->holder_name);
//        $this->payment = $this->sale
//            ->payment($this->order->amount * 100)
//            ->setCapture(1);

        $this->order = $order;
        $this->paymentData = (object) $paymentData;
        $this->sale = new Sale($this->order->reference);

//        dd($this->sale, $this->paymentData, $this->order);
    }

//    public function debit()
//    {
//        $this->payment->setAuthenticate(1)
//            ->setType('DebitCard')
//            ->debitCard($this->paymentData->securityCode, $this->paymentData->bandeira)
//            ->setExpirationDate($this->paymentData->expiration_date)
//            ->setCardNumber($this->paymentData->card_number)
//            ->setHolder($this->paymentData->full_name);
//        return $this->pay();
//    }

    public function debit()
    {
        $this->sale->customer($this->paymentData->card['holder_name']);
        $this->payment = $this->sale
            ->payment($this->order->amount * 100)
            ->setCapture(1);
        $this->payment->setAuthenticate(1)
            ->setType('DebitCard')
            ->debitCard($this->paymentData->card['cvv'], $this->paymentData->card['bandeira'])
//            ->debitCard($this->paymentData->card['securityCode'], $this->paymentData['bandeira'])
            ->setExpirationDate($this->paymentData->card['expiration_date'])
            ->setCardNumber($this->paymentData->card['card_number'])
            ->setHolder($this->paymentData->card['holder_name']);
//        dd($this->pay());
        return $this->pay();
    }

    public function credit()
    {
//        dd($this->paymentData);

        $this->sale->customer($this->paymentData->card['holder_name']);
        $this->payment = $this->sale
            ->payment($this->order->amount * 100)
            ->setCapture(1);
        $this->payment->setType("CreditCard")
            ->creditCard($this->paymentData->card['cvv'], $this->paymentData->card['bandeira'])
            ->setExpirationDate($this->paymentData->card['expiration_date'])
            ->setCardNumber($this->paymentData->card['card_number'])
            ->setHolder($this->paymentData->card['holder_name']);




        //Verificar origem dos metodos setHolder e setCardNumber






//        ->creditCard($this->paymentData->card->cvv, $this->paymentData->card->bandeira)
//        ->setExpirationDate($this->paymentData->card->expiration_date)
//        ->setCardNumber($this->paymentData->card->card_number)
//        ->setHolder($this->paymentData->card->holder_name);

//        dd($this->pay());
        return $this->pay();
    }

    public function pix()
    {
        $response = Http::withHeaders([
            "Content-Type" => "application/json",
            "MerchantId" => $this->merchant->getId(),
            "MerchantKey" => $this->merchant->getKey(),
            'RequestId' => $this->paymentData->reference

        ])->post($this->environment->getApiUrl() . "1/sales/", [
            "MerchantOrderId" => $this->order->reference,
            "Customer" => [
                "Name" => $this->paymentData->buyer['first_name'] . ' ' . $this->paymentData->buyer['last_name'],
//                "Name" => "Nome do Pagador",
                "Identity" => $this->paymentData->buyer['cpf_cnpj'],
//                "Identity" => "12345678909",
                "IdentityType" => strlen($this->paymentData->buyer['cpf_cnpj']) == 11 ? 'CPF' : 'CNPJ'
//                "IdentityType" => "CPF"
            ],
            "Payment" => [
                "Type" => "Pix",
                "Amount" => $this->order->amount * 100
            ]
        ]);

//        dd($response->object()->Payment->PaymentId);
        return $response->object();
    }

    private function pay()
    {
        $this->sale = (new CieloEcommerce($this->merchant, $this->environment))->createSale($this->sale);
        $response = $this->sale->getPayment();

//        dd('Teste: ',$response);

        return $response;
    }

}
