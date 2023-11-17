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
use Cielo\API30\Ecommerce\Environment;
use Cielo\API30\Merchant;
use Illuminate\Support\Facades\Http;

class CieloClient
{
    private $environment;
    private $merchant;
    public $merchantId;
    public $merchantKey;
    public $apiUrl;
    public $apiQueryUrl;
    private $client;
    private $paymentData;
    private $order;
    private $sale;
    private $payment;
    private $returnUrl;
    private $response;

//    public function __construct()
    public function __construct(Payment $order, array $paymentData)
    {
        $this->order = $order;
        $this->paymentData = (object) $paymentData;

        if (getenv('APP_ENV') == 'local') {
            $this->merchantId = config('services.cielo.sandbox.api_merchant_id');
            $this->merchantKey = config('services.cielo.sandbox.api_merchant_key');
            $this->apiUrl = config('services.cielo.sandbox.api_url');
            $this->apiQueryUrl = config('services.cielo.sandbox.api_query_url');

//            $ev = [
//                'merchantId' => config('services.cielo.sandbox.api_merchant_id'),
//                'merchantKey' => config('services.cielo.sandbox.api_merchant_key'),
//                'apiUrl' => config('services.cielo.sandbox.api_url'),
//                'apiQueryUrl' => config('services.cielo.sandbox.api_query_url'),
//            ] ;
        } else {
            $this->merchantId = config('services.cielo.production.api_merchant_id');
            $this->merchantKey = config('services.cielo.production.api_merchant_key');
            $this->apiUrl = config('services.cielo.production.api_url');
            $this->apiQueryUrl = config('services.cielo.production.api_query_url');

//            $ev = [
//                'merchantId' => config('services.cielo.production.api_merchant_id'),
//                'merchantKey' => config('services.cielo.production.api_merchant_key'),
//                'apiUrl' => config('services.cielo.production.api_url'),
//                'apiQueryUrl' => config('services.cielo.production.api_query_url'),
//            ] ;
        }

//        dd($this->merchantId, $this->merchantKey, $this->apiUrl, $this->apiQueryUrl);

    }

    public function credentials()
    {
        if (getenv('APP_ENV') == 'local') {
            $ev = [
                'merchantId' => config('services.cielo.sandbox.api_merchant_id'),
                'merchantKey' => config('services.cielo.sandbox.api_merchant_key'),
                'apiUrl' => config('services.cielo.sandbox.api_url'),
                'apiQueryUrl' => config('services.cielo.sandbox.api_query_url'),
            ] ;
        } else {
            $ev = [
                'merchantId' => config('services.cielo.production.api_merchant_id'),
                'merchantKey' => config('services.cielo.production.api_merchant_key'),
                'apiUrl' => config('services.cielo.production.api_url'),
                'apiQueryUrl' => config('services.cielo.production.api_query_url'),
            ] ;
        }

        return $ev;
    }

    public function credit()
    {
        $ev = self::credentials();

//        $initTransInd = [
//            'Category' => null,
//            'Subcategory' => null,
//        ];
//
//        if($paymentData->card['bandeira'] == 'Master'){
//            if($order->recurrent == true){
//                $initTransInd = [
//                    'Category' => "M1",
//                    'Subcategory' => "Subscription",
//                ];
//            }
//        }



        $response = Http::withHeaders([
            "Content-Type" => "application/json",
            "MerchantId" => $this->merchantId,
            "MerchantKey" => $this->merchantKey,
            "RequestId" => $this->paymentData->reference
        ])->post("{$this->apiUrl}1/sales/", [
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
                "Installments" => "{$this->paymentData->installment}",//se for recorrencia = 1
                "Capture" => true,
//                "InitiatedTransactionIndicator" => $initTransInd
            ]
        ]);

        return $response;
    }

    public function debit(Payment $order, array $paymentData)
    {
//        $ev = self::credentials();

//        dd($this->merchantId, $this->merchantKey, $this->apiUrl, $this->apiQueryUrl);

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
//        $ev = self::credentials();
//        dd($this->merchantId, $this->merchantKey, $this->apiUrl, $this->apiQueryUrl);

        $identityType = preg_replace('/[^0-9]/', '', $this->paymentData->buyer['cpf_cnpj']);

        $response = Http::withHeaders([
            "Content-Type" => "application/json",
            "MerchantId" => $this->merchantId,
            "MerchantKey" => $this->merchantKey,
            'RequestId' => $this->paymentData->reference

        ])->post($this->apiUrl, [
            "MerchantOrderId" => $this->order->reference,
            "Customer" => [
                "Name" => "{$this->paymentData->buyer['first_name']} {$this->paymentData->buyer['last_name']}",
                "Identity" => $this->paymentData->buyer['cpf_cnpj'],
                "IdentityType" => strlen($identityType) == 11 ? 'CPF' : 'CNPJ'
            ],
            "Payment" => [
                "Type" => "Pix",
                "Amount" => $this->order->amount * 100
            ]
        ]);

        return $response->object();
    }

    public function getStatus($transaction)
    {
//        $ev = self::credentials();
//        dd($this->merchantId, $this->merchantKey, $this->apiUrl, $this->apiQueryUrl);

        $response = Http::withHeaders([
            "Content-Type" => "application/json",
            "MerchantId" => $this->merchantId,
            "MerchantKey" => $this->merchantKey,
        ])->get("{$this->apiQueryUrl}1/sales/{$transaction}");

        $typePayment = $response->object()->Payment->Type;

        return [
            'status' => self::rewriteStatus($response->object()->Payment->Status),
            'payment' => $response->object(),
            'receipt' => ($typePayment != "Pix") ? self::receiptFormat($response->object()) : null
        ];
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

    public function cancelSale($paymentId, $amount)
    {
        return'Cancelamento';

//        $ev = self::credentials();

//        dd($this->merchantId, $this->merchantKey, $this->apiUrl, $this->apiQueryUrl);

//        dd($ev, $transaction);
        $response = Http::withHeaders([
            "Content-Type" => "application/json",
            "MerchantId" => $this->merchantId,
            "MerchantKey" => $this->merchantKey,
        ])->put("{$this->apiUrl}1/sales/{$paymentId}/void?amount={$amount}");

        return $response->object();
    }

    public function receiptFormat($receipt)
    {
        $receiptFormatted = null;

        if(!empty($receipt))
        {
            $receiptFormatted = [
                'card_number' => $receipt->Payment->CreditCard->CardNumber,
                'flag' => strtoupper($receipt->Payment->CreditCard->Brand),
                'card_ent_mode' => "TRANSAÇÃO AUTORIZADA COM SENHA",
                'payer' => strtoupper($receipt->Payment->CreditCard->Holder),
                'in_installments' => $receipt->Payment->Installments,
                'transaction_code' => $receipt->Payment->AuthorizationCode,
                'capture_date' => date("d/m/Y H:i", strtotime($receipt->Payment->CapturedDate)),
                'receipt' => null
            ];
        }

        return $receiptFormatted;
    }

//    private function pay()
//    {
////        $this->sale = (new CieloEcommerce($this->merchant, $this->environment))->createSale($this->sale);
////        $response = $this->sale->getPayment();
//
//        dd('Teste: ',$response);
//
//        return $response;
//    }

}
