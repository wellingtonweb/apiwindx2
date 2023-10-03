<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;

class PicpayClient
{
    protected $apiUrl;
    protected $callbackUrl;
    protected $picpayToken;
    protected $sellerToken;
    protected $payment;

    public function __construct(Payment $payment)
    {
//        $this->apiUrl = getenv("PICPAY_PROD_URL");
//        $this->callbackUrl = getenv("PICPAY_PROD_URL_CALLBACK");
//        $this->picpayToken = getenv("PICPAY_TOKEN_PROD");
//        $this->sellerToken = getenv("PICPAY_SELLER_PROD");
        $this->apiUrl = config('services.picpay.production.api_url');
        $this->callbackUrl = config('services.picpay.production.api_url_callback');
        $this->picpayToken = config('services.picpay.production.api_token');
        $this->sellerToken = config('services.picpay.production.api_seller');

        $this->payment = $payment;
    }

    public function pay(object $buyer)
    {
        $response = Http::withHeaders([
            'x-picpay-token' => $this->picpayToken
        ])->post("{$this->apiUrl}/payments", [
            "callbackUrl" => $this->callbackUrl,
            "referenceId" => $this->payment->reference,
            "value" => $this->payment->amount,
            "expiresAt" => now('America/Sao_Paulo')->addMinutes(3)->toString(),
            "buyer" => [
                "firstName" => $buyer->first_name,
                "lastName" => $buyer->last_name,
                "document" => $buyer->cpf_cnpj,
                "email" => $buyer->email,
                "phone" => $buyer->phone
            ]
        ]);
        return $response->object();
    }

    public function getStatus()
    {
        $response = Http::withHeaders([
            'x-picpay-token' => $this->picpayToken
        ])->get("$this->apiUrl/payments/{$this->payment->reference}/status")->object();
        //return $response->object();

        if ($response && !empty($response->status)) {
            switch ($response->status) {
                case "expired":
                    $this->payment->status = "expired";
                case "refunded":
                case "chargeback":
                    $this->payment->status = "canceled";
                    break;
                case "paid":
                case "completed":
                    $this->payment->status = "approved";
                    break;
                default:
                    break;
            }

            if($this->payment->save()){
                return $this->payment;
            }else{
                return false;
            }
        }
    }

}
