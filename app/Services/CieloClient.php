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
//use Cielo\API30\Ecommerce\Environment;
//use Cielo\API30\Merchant;
use Illuminate\Support\Facades\Http;

class CieloClient
{
    public $merchantId;
    public $merchantKey;
    public $apiUrl;
    public $apiQueryUrl;
    private $paymentData;
    private $order;

    public function __construct(Payment $order, array $paymentData)
    {
//        if (getenv('APP_ENV') == 'local')
//        {
//            $this->merchantId = config('services.cielo.sandbox.api_merchant_id');
//            $this->merchantKey = config('services.cielo.sandbox.api_merchant_key');
//            $this->apiUrl = config('services.cielo.sandbox.api_url');
//            $this->apiQueryUrl = config('services.cielo.sandbox.api_query_url');
//        }
//        else
//        {
            $this->merchantId = config('services.cielo.production.api_merchant_id');
            $this->merchantKey = config('services.cielo.production.api_merchant_key');
            $this->apiUrl = config('services.cielo.production.api_url');
            $this->apiQueryUrl = config('services.cielo.production.api_query_url');
//        }

        $this->order = $order;
        $this->paymentData = (object) $paymentData;

//        dd($this->order, $this->paymentData);
    }

    public function credit()
    {
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
                "InitiatedTransactionIndicator" => [
                    'Category' => "C1",
                    'Subcategory' => "Standingorder",
                ]
            ]
        ]);

        return $response;
    }

    public function debit()
    {
        dd($this->get_auth_token());
        $response = Http::withHeaders([
            "Content-Type" => "application/json",
            "MerchantId" => $this->merchantId,
            "MerchantKey" => $this->merchantKey,
            "RequestId" => $this->paymentData->reference
        ])->post("{$this->apiUrl}1/sales/", [
            "MerchantOrderId" => $this->order->reference,
            "Customer" => [
                "Name" => $this->paymentData->card['holder_name']
            ],
            "Payment" => [
                "Currency" => "BRL",
                "Country" => "BRA",
                "Type" => "DebitCard",
                "Authenticate" => true,
                "ReturnUrl" => "https://ambientedetestes.windx.com.br/cartao/debito/obrigado.php", //REDIRECIONA PARA A P�GINA DE OBRIGADO. FICA SEU CRITERIO. VOC� DIRECIONAR PARA ONDE DESEJAR.
                "Amount" => $this->order->amount * 100,
                "DebitCard" => [
                    "CardNumber" => $this->paymentData->card['card_number'],
                    "Holder" => $this->paymentData->card['holder_name'],
                    "ExpirationDate" => $this->paymentData->card['expiration_date'],
                    "SecurityCode" => $this->paymentData->card['cvv'],
                    "Brand" => $this->paymentData->card['bandeira']
                ],
                "ExternalAuthentication" => [
//                    "Cavv" => "AAABB2gHA1B5EFNjWQcDAAAAAAB=",
//                    "Xid" => "Uk5ZanBHcWw2RjRCbEN5dGtiMTB=",
//                    "Eci" => "5",
//                    "Version" => "2",
//                    "ReferenceID" => "a24a5d87-b1a1-4aef-a37b-2f30b91274a3",
                    "Eci" => "4",
                    "ReferenceID" => "a24a5d87-b1a1-4aef-a37b-2f30b91274a3",
                    "dataonly" => true
                ],
//                "Installments" => "{$this->paymentData->installment}",//se for recorrencia = 1
                "Capture" => true,
                "InitiatedTransactionIndicator" => [
                    'Category' => "C1",
                    'Subcategory' => "CredentialsOnFile",
                ]
            ]
        ]);

        return $response;
    }

    public function get_auth_token()
    {
        $id = config('services.cielo.production.api_merchant_id');
        $key = config('services.cielo.production.api_merchant_key');

        $response = Http::asForm()->withHeaders([
//            "Accept" => "application/json, text/plain, */*",
            "Authorization" => "Basic ".base64_encode("$id:$key"),
            "Content-Type" => "application/json"
        ])->post("{$this->apiUrl}v2/auth/token", [
            "EstablishmentCode" => "1106093345",
            "MerchantName" => "PENHA DE SOUZA JAMARI",
            "MCC" => "4816"
        ]);

        return $response->object();
//        return $response->object()->access_token;
    }

//    public function debit()
//    {
////        dd($this->order, $this->paymentData);
//
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
//
//        dd('debit');
//        return $this->pay();
//
//
//    }

    public function pix()
    {
        $identityType = preg_replace('/[^0-9]/', '', $this->paymentData->buyer['cpf_cnpj']);

        $response = Http::withHeaders([
            "Content-Type" => "application/json",
            "MerchantId" => $this->merchantId,
            "MerchantKey" => $this->merchantKey,
            'RequestId' => $this->paymentData->reference

        ])->post("{$this->apiUrl}1/sales/", [
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

//        return $response;
        return $response->object();
    }

    public function getStatus()
    {
//        dd($this->order->transaction);

        $response = Http::withHeaders([
            "Content-Type" => "application/json",
            "MerchantId" => $this->merchantId,
            "MerchantKey" => $this->merchantKey,
        ])->get("{$this->apiQueryUrl}1/sales/{$this->order->transaction}");

//        dd($response->object());

        if($response->object() === null){
            return [];
        }

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

    private function pay()
    {
        $this->sale = (new CieloEcommerce($this->merchant, $this->environment))->createSale($this->sale);
        $response = $this->sale->getPayment();

//        dd('Teste: ',$response);

        return $response;
    }

}
