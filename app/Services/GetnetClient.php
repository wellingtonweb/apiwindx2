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
        $this->paymentData->card_number = "5155901222280001";


    }

    public function get_token()
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
            "Authorization" => "Bearer ".$this->get_token(),
            "Content-Type" => "application/json"
        ])->post("{$this->apiUrl}/v1/tokens/card", [
            "card_number" => $this->paymentData->card_number,
        ]);

        return $response->object();
    }

    public function safe()
    {
        //
    }

    public function debit()
    {
        /*
         *
         * {
                amount:"1000"
                    customer:{
                        billing_address:{}
                        customer_id:"12345"
                        email:"aceitei@getnet.com.br"
                    }
                debit:{
                    card:{}
                    }
                    device:{}
                    order:{}
                    seller_id:"b9e48556-9d7b-43a2-b73a-37a7c127368d"
                    shippings:[{}]
                    sub_merchant:{}
                }
         *
         * */
    }

    public function recurrence()
    {
        //
    }
}
