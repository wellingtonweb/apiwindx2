<?php


namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;


class GetnetClient
{
    public $clientId;
    public $clientSecret;
    public $apiUrl;
    private $paymentData;
    private $order;

    public function __construct(){
//        public function __construct(Payment $order, array $paymentData)
        //

        $this->clientId = "e4166dad-e9ee-49a6-8648-a46e343d2496";
        $this->clientSecret = "689b3268-b241-4b98-a306-fd948b9bf90b";
        $this->apiUrl = "https://api-sandbox.getnet.com.br/";
//        $this->order = $order;
//        $this->paymentData = (object) $paymentData;
//        $this->paymentData->card_number = "5155901222280001";


    }

    public function get_auth_token()
    {
        $response = Http::asForm()->withHeaders([
            "Accept" => "application/json, text/plain, */*",
            "Authorization" => "Basic ".base64_encode("$this->clientId:$this->clientSecret"),
            "Content-Type" => "application/x-www-form-urlencoded"
        ])->post("{$this->apiUrl}/auth/oauth/v2/token", [
            "scope" => "oob",
            "grant_type" => "client_credentials"
        ]);

        return $response->object()->access_token;
    }

    public function card_tokenization()
    {
        $response = Http::withHeaders([
            "Accept" => "application/json, text/plain, */*",
            "Authorization" => "Bearer ".$this->get_auth_token(),
            "Content-Type" => "application/json"
        ])->post("{$this->apiUrl}/v1/tokens/card", [
            "card_number" => "5155901222280001",
//            "card_number" => $this->paymentData->card_number,
        ]);

        return $response->object()->number_token;
    }

    public function debit()
    {
        $response = Http::withHeaders([
            "Accept" => "application/json, text/plain, */*",
            "Authorization" => "Bearer ".$this->get_auth_token(),
            "Content-Type" => "application/json"
        ])->post("{$this->apiUrl}/v1/payments/debit", [
            'seller_id' => 'b9e48556-9d7b-43a2-b73a-37a7c127368d',
            'amount' => 100,
            'currency' => 'BRL',
            'order' => [
                'order_id' => '6d2e4380-d8a3-4ccb-9138-c289182818a3',
            ],
            'customer' => [
                'customer_id' => '34258',
                'first_name' => 'Wellington',
                'name' => 'Wellington Dias',
                'document_type' => 'CPF',
                'document_number' => '12345678912',
                'phone_number' => '5551999887766',
                'billing_address' => [
                    'street' => 'Av. Brasil',
                    'complement' => 'Sala 1',
                    'number' => '1000',
                    'district' => 'São Geraldo',
                    'city' => 'Porto Alegre',
                    'state' => 'RS',
                    'country' => 'Brasil',
                    'postal_code' => '90230060',
                ],
            ],
            'debit' => [
                'cardholder_mobile' => '5528999887766',
                'soft_descriptor' => 'WINDX TELECOMUNICAÇÕES',
                'dynamic_mcc' => 4816,
                'card' => [
                    'number_token' => $this->card_tokenization(),
                    'security_code' => '123',
                    'expiration_month' => '12',
                    'expiration_year' => '28',
                    'cardholder_name' => 'WELLINGTON DIAS',
                ],
            ],
        ]);

        return $response->object();
    }

    public function safe()
    {
        //
    }

    public function recurrence()
    {
        //
    }
}
