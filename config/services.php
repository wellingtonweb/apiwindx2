<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'cielo' => [
        'sandbox' => [
            'api_merchant_id' => env('CIELO_SANDBOX_MERCHANT_ID'),
            'api_merchant_key' => env('CIELO_SANDBOX_MERCHANT_KEY'),
            'api_url' => env('CIELO_SANDBOX_API_URL'),
            'api_query_url' => env('CIELO_SANDBOX_API_QUERY_URL'),
        ],
        'production' => [
            'api_merchant_id' => env('CIELO_PROD_MERCHANT_ID'),
            'api_merchant_key' => env('CIELO_PROD_MERCHANT_KEY'),
            'api_url' => env('CIELO_PROD_API_URL'),
            'api_query_url' => env('CIELO_PROD_API_QUERY_URL'),
        ],
    ],

    'controlpay' => [
        'sandbox' => [
            'api_login' => env('CONTROLPAY_SANDBOX_LOGIN'),
            'api_password' => env('CONTROLPAY_SANDBOX_PASSWORD'),
            'api_technical_password' => env('CONTROLPAY_SANDBOX_TECHNICAL_PASSWORD'),
            'api_url' => env('CONTROLPAY_SANDBOX_API_URL'),
        ],
        'production' => [
            'api_login' => env('CONTROLPAY_PROD_LOGIN'),
            'api_password' => env('CONTROLPAY_PROD_PASSWORD'),
            'api_technical_password' => env('CONTROLPAY_PROD_TECHNICAL_PASSWORD'),
            'api_key' => env('CONTROLPAY_PROD_KEY'),
            'api_url' => env('CONTROLPAY_PROD_API_URL'),
        ],
    ],

    'picpay' => [
        'production' => [
            'api_token' => env('PICPAY_TOKEN_PROD'),
            'api_seller' => env('PICPAY_SELLER_PROD'),
            'api_url' => env('PICPAY_PROD_URL'),
            'api_url_callback' => env('PICPAY_PROD_URL_CALLBACK'),
        ],
    ],

    'vigo' => [
        'sandbox' => [
            'api_login' => env('VIGO_SANDBOX_LOGIN'),
            'api_password' => env('VIGO_SANDBOX_PASSWORD'),
            'api_caixa' => env('VIGO_SANDBOX_CAIXA'),
            'api_url' => env('VIGO_SANDBOX_API_URL'),
        ],
        'production' => [
            'api_login' => env('VIGO_PROD_LOGIN'),
            'api_password' => env('VIGO_PROD_PASSWORD'),
            'api_caixa_cartao' => env('VIGO_PROD_CAIXA_CARTAO'),
            'api_caixa_picpay' => env('VIGO_PROD_CAIXA_PICPAY'),
            'api_caixa_pix' => env('VIGO_PROD_CAIXA_PIX'),
            'api_technical_password' => env('VIGO_PROD_TECHNICAL_PASSWORD'),
            'api_url' => env('VIGO_PROD_API_URL'),
        ],
    ],

];
