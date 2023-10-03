<?php

namespace App\Services;

//use App\Models\Payment;
//use Cielo\API30\Ecommerce\CieloEcommerce;
//use Cielo\API30\Ecommerce\Customer;
//use Cielo\API30\Ecommerce\Environment;
//use Cielo\API30\Ecommerce\Sale;
//use Cielo\API30\Merchant;

use App\Models\Payment;
//use Cielo\API30\Ecommerce\CieloEcommerce;
//use Cielo\API30\Ecommerce\Customer;
//use Cielo\API30\Ecommerce\Environment;
//use Cielo\API30\Ecommerce\Request\CieloRequestException;
//use Cielo\API30\Ecommerce\Sale;
//use Cielo\API30\Ecommerce\Payment as CieloPayment;
//use Cielo\API30\Merchant;
use Illuminate\Support\Facades\Http;

class CieloClient
{
    protected $environment;
    protected $merchant;
    protected $merchantId;
    protected $merchantKey;
    protected $apiUrl;
    protected $apiQueryUrl;
    protected $client;
    protected $paymentData;
    protected $order;
    protected $sale;
    protected $payment;
    protected $returnUrl;
    protected $response;

    public function __construct(Payment $order, array $paymentData)
    {
        if (config('services.app.env') == 'local') {
            $this->merchantId = config('services.cielo.sandbox.api_merchant_id');
            $this->merchantKey = config('services.cielo.sandbox.api_merchant_key');
            $this->apiUrl = config('services.cielo.sandbox.api_url');
            $this->apiQueryUrl = config('services.cielo.sandbox.api_query_url');
        } else {
            $this->merchantId = config('services.cielo.production.api_merchant_id');
            $this->merchantKey = config('services.cielo.production.api_merchant_key');
            $this->apiUrl = config('services.cielo.production.api_url');
            $this->apiQueryUrl = config('services.cielo.production.api_query_url');
        }

        $this->order = $order;
        $this->paymentData = (object) $paymentData;


//        dd($this->paymentData);

//        if(isset($payment['billets'][0]->installment)){
//            if($payment['billets'][0]->installment > 1){
//                $payment->installment = $payment['billets'][0]->installment;
//            }
//        }
//
//        $payment->save();
    }

    public function credit()
    {

//        dd($this->order, $this->paymentData);
        $response = Http::withHeaders([
            "Content-Type" => "application/json",
            "MerchantId" => $this->merchantId,
            "MerchantKey" => $this->merchantKey,
            "RequestId" => $this->paymentData->reference
        ])->post($this->apiUrl . "1/sales/", [
            "MerchantOrderId" => $this->order->reference,
            "Customer" => [
                "Name" => $this->paymentData->card['holder_name'],
            ],
            "Payment" => [
                "Type" => "CreditCard",
                "Authenticate" => false,
                "Amount" => $this->order->amount * 100,
                "CreditCard" => [
                    "CardNumber" => $this->paymentData->card['card_number'],
                    "Holder" => $this->paymentData->card['holder_name'],
                    "ExpirationDate" => $this->paymentData->card['expiration_date'],
                    "SecurityCode" => $this->paymentData->card['cvv'],
                    "Brand" => $this->paymentData->card['bandeira'],
                ],
                "Installments" => "{$this->paymentData->installment}",
                "Capture" => true,
            ],
        ]);

//        if($response->successful()){
//            $paymentUpdate = Payment::find($payment->id);
//            $paymentUpdate->token = $response->object()->intencaoVenda->token;
//            $paymentUpdate->save();

            return $response->object();
//        }else{
//            return $response->toException();
//        }

    }

    public function debit()
    {
//        $this->sale->customer($this->paymentData->card['holder_name']);
//        $this->payment = $this->sale
//            ->payment($this->order->amount * 100)
//            ->setCapture(1);
//        $this->payment->setAuthenticate(1)
//            ->setType('DebitCard')
//            ->debitCard($this->paymentData->card['cvv'], $this->paymentData->card['bandeira'])
////            ->debitCard($this->paymentData->card['securityCode'], $this->paymentData['bandeira'])
//            ->setExpirationDate($this->paymentData->card['expiration_date'])
//            ->setCardNumber($this->paymentData->card['card_number'])
//            ->setHolder($this->paymentData->card['holder_name']);
//        dd($this->pay());

        dd('debit');
        return $this->pay();
    }

    public function pix()
    {
        $identityType = preg_replace('/[^0-9]/', '', $this->paymentData->buyer['cpf_cnpj']);

        $response = Http::withHeaders([
            "Content-Type" => "application/json",
            "MerchantId" => $this->merchantId,
            "MerchantKey" => $this->merchantKey,
            'RequestId' => $this->paymentData->reference

        ])->post($this->apiUrl . "1/sales/", [
            "MerchantOrderId" => $this->order->reference,
            "Customer" => [
                "Name" => $this->paymentData->buyer['first_name'] . ' ' . $this->paymentData->buyer['last_name'],
                "Identity" => $this->paymentData->buyer['cpf_cnpj'],
                "IdentityType" => strlen($identityType) == 11 ? 'CPF' : 'CNPJ'
            ],
            "Payment" => [
                "Type" => "Pix",
                "Amount" => $this->order->amount * 100
            ]
        ]);

//        dd($response->object());
        return $response->object();
    }

    public function getStatus($transaction)
    {

        $payment = Http::withHeaders([
            "Content-Type" => "application/json",
            "MerchantId" => $this->merchantId,
            "MerchantKey" => $this->merchantKey,
        ])->get($this->apiQueryUrl . "1/sales/" . $transaction);

        return $payment;
    }

    public function rewriteStatus($status){

        $statusUpdated = "created";

        switch ($status){
            case 2:
                $statusUpdated = "approved";
                break;
            case 3:
            case 10:
            case 13:
                $statusUpdated = "refused";
                break;
            case 12:
            default:
                $statusUpdated = $statusUpdated;
                break;
        }

        return $statusUpdated;
    }

    private function pay()
    {
//        $this->sale = (new CieloEcommerce($this->merchant, $this->environment))->createSale($this->sale);
//        $response = $this->sale->getPayment();

        dd('Teste: ',$response);

        return $response;
    }

}
