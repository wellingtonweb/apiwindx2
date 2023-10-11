<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\VigoServer;

class VigoClient
{

    private $apiUrl;
    private $login;
    private $password;
    private $fields;
    private $endpoint;
    private $token;
    private $employee_id;
    public $customer;
    private $billets;
    private $caixa;
//    private $caixa;
//    private $caixa;

    public function __construct()
    {
        if (getenv('APP_ENV') == 'local') {
            $this->apiUrl = getenv('VIGO_SANDBOX_API_URL');
            $this->login = getenv('VIGO_SANDBOX_LOGIN');
            $this->password = getenv('VIGO_SANDBOX_PASSWORD');
            $this->caixa = getenv('VIGO_SANDBOX_CAIXA');
        } else {
            $this->apiUrl = getenv('VIGO_PROD_API_URL');
            $this->login = getenv('VIGO_PROD_LOGIN');
            $this->password = getenv('VIGO_PROD_PASSWORD');
            $this->caixa = getenv('VIGO_PROD_CAIXA_CARTAO');
        }

//        dd($this->apiUrl, $this->login, $this->password, $this->caixa);

        $this->customer = [];
        $this->login();
    }

    public function login(): VigoClient
    {
        $response = Http::accept('application/json')
            ->post("{$this->apiUrl}/api/auth", [
                'login' => "{$this->login}",
                'senha' => "{$this->password}"
            ]);

        if ($response->successful()) {
            $this->token = $response->object()->senha;
            $this->employee_id = $response->object()->id;
        } else {
            $response->throw();
        }

        return $this;
    }

    /**
     * @param string|null $type
     * @param string $field
     * @return mixed|void
     */
    public function getCustomer(?string $field, ?string $type = 'ID'): VigoClient
    {
        /*
         * "Type": 1 to CPF or CNPJ | 2 - to ID;
         *  "Field": string for search
         * */

        $response = Http::accept('application/json')
            ->withToken($this->token)
            ->post("{$this->apiUrl}/api/app_getcliente", [
//                'campo1' => ($type == 1) ? "CPFCGC" : "ID",
//                'campo1_valor' => "{$id}",
                'campo1' => $type,
                'campo1_valor' => "{$field}",
                'campo2' => 'none',
                'campo2_valor' => 'none',

            ]);
        if ($response->successful() && $response->object() != "ERRO") {

            $customers = $response->object();
            if (is_array($customers)) {
                foreach ($customers as $customer) {
                    array_push($this->customer, [
                        "id" => $customer->id,
                        "full_name" => $customer->nome,
                        "gender" => ($customer->sexo === "M") ? "Masculino" : "Feminino",
                        "document" => $customer->cpfcgc,
                        "street" => $customer->endereco,
                        "district" => $customer->bairro,
                        "city" => $customer->cidade,
                        "state" => $customer->uf,
                        "cep" => $customer->cep,
                        "reference" => $customer->referencia,
                        "phone" => $customer->telefone,
                        "cell" => $customer->celular,
                        "email" => $customer->email,
                        "status" => $customer->situacao,
                        "dt_trust" => $customer->dt_confianca,
                        "company_id" => $customer->idempresa,
                        "account_plan" => $customers->plano_conta,
                        "billets" => $this->getBillets($customer->id)
                    ]);
//                    $this->releaseCustomerById($this->customer);
                }
            } else {

//                dd($this->getBillets($customers->id));

                array_push($this->customer, [
                    "id" => $customers->id,
                    "full_name" => $customers->nome,
                    "gender" => ($customers->sexo === "M") ? "Masculino" : "Feminino",
                    "document" => $customers->cpfcgc,
                    "street" => $customers->endereco,
                    "district" => $customers->bairro,
                    "city" => $customers->cidade,
                    "state" => $customers->uf,
                    "cep" => $customers->cep,
                    "reference" => $customers->referencia,
                    "phone" => $customers->telefone,
                    "cell" => $customers->celular,
                    "email" => $customers->email,
                    "status" => $customers->situacao,
                    "dt_trust" => $customers->dt_confianca,
                    "company_id" => $customers->idempresa,
                    "account_plan" => $customers->plano_conta,
                    "billets" => $this->getBillets($customers->id)
                ]);

//                dd($this->customer);
//                $this->releaseCustomerById($this->customer);
            }
        }
        return $this;
    }


    public function getBillets(int $idCustomer)
    {

        $billets = [];
        $response = json_decode(Http::accept('application/json')
            ->withToken($this->token)
            ->post("{$this->apiUrl}/api/app_getboletosid", [
                'id' => "{$idCustomer}"
            ])->body());
        $response = json_decode($response);

        usort($response, function ($a, $b) {
            return strtotime($a->Vencimento) - strtotime($b->Vencimento);
        });

        foreach ($response as $key => $billet) {

            if ($billet->Pago == 0) {
                array_push($billets, $billet);
            }
        }
        return $billets;
    }

    public function central(string $login, string $password)
    {
        $response = Http::accept('application/json')
            ->withToken($this->token)
            ->post("{$this->apiUrl}/api/hotspot_valida", [
//                'campo1' => ($type == 1) ? "CPFCGC" : "ID",
//                'campo1_valor' => "{$id}",
                'campo1' => "LOGIN",
                'campo1_valor' => "{$login}",
                'campo2' => "SENHA",
                'campo2_valor' => "{$password}",

            ]);

        if ($response->successful()) {
            $customers = $response->object();

            $this->customer = (object)[
                "id" => $customers->id,
                "full_name" => $customers->nome,
                "gender" => ($customers->sexo === "M") ? "Masculino" : "Feminino",
                "document" => $customers->cpfcgc,
                "street" => $customers->endereco,
                "district" => $customers->bairro,
                "city" => $customers->cidade,
                "state" => $customers->uf,
                "cep" => $customers->cep,
                "reference" => $customers->referencia,
                "phone" => $customers->telefone,
                "cell" => $customers->celular,
                "email" => $customers->email,
                "status" => $customers->situacao,
                "dt_trust" => $customers->dt_confianca,
                "company_id" => $customers->idempresa,
                "plans" => $this->getPlans($customers->id),
                "invoices" => $this->getInvoices($customers->id, $customers->cpfcgc),
                "billets" => $this->getBillets($customers->id)
            ];
        }
        return $this;
    }

    public function checkoutBillet($billet, $payment)
    {
        $billet = (object)$billet;
        $payment = (object)$payment;
        $caixa = self::getCaixa($billet->company_id, $payment->place);

        $auditInfo = [
            'payment' => $payment,
            'billet' => $billet,
            'caixa' => $caixa,
        ];

//        $textPayment = "Boleto {$billet->billet_id}/{$billet->reference}, liquidado com valor R$ {$billet->total} no caixa nº {$caixa}.
//        Pagamento referente {$payment->reference}, usando o método ".strtoupper($payment_type)." via ".strtoupper($place).".";

        $response = Http::accept('application/json')
            ->withToken($this->token)
            ->post($this->apiUrl . "/api/app_liquidaboleto", [
                "id_boleto" => "{$billet->billet_id}",
//                "id_caixa" => "37",//oficial
//                "id_caixa" => "39",//sandbox
                "id_caixa" => "{$caixa}",
                "valor_pago" => "{$billet->total}"
            ]);

        if ($response->successful()) {
//            Log::alert(json_encode($auditInfo));
            (new VigoServer())->setAuditPayment($auditInfo);

            return $response->object();
        } else {
            return $response->throw();
        }
    }

//    public function reverseBillet($billet, $payment_type)
    public function reverseBillet($billet)
    {
        $billet = (object)$billet;
        $response = Http::accept('application/json')
            ->withToken($this->token)
            ->post($this->apiUrl . "/api/app_estornaboleto", [
                "id_boleto" => "{$billet->billet_id}",
                "id_caixa" => "{$this->caixa}"
            ]);
        if ($response->successful()) {
            return $response->object();
        } else {
            return $response->throw();
        }
    }

    public function search(int $idCustomer)
    {
        return [
            "customer" => $this->getCustomer($idCustomer),
            "billets" => json_decode($this->getBillets($idCustomer))
        ];
    }

    public function getPlans(int $idCustomer)
    {
        $response = Http::accept('application/json')
            ->withToken($this->token)
            ->post($this->apiUrl . "/api/app_getplanosid", [
                "id" => "{$idCustomer}",
            ]);

        return json_decode($response->object());
    }

    public function getInvoices(int $idCustomer, string $document)
    {
        $response = Http::accept('application/json')
            ->withToken($this->token)
            ->post($this->apiUrl . "/api/app_getnotas", [
                "cpf_cnpj" => "{$document}",
                "id" => "{$idCustomer}",
            ]);

        /*usort($response, function ($a, $b) {
            return strtotime($a->emissao) - strtotime($b->emissao);
        });*/

        return json_decode($response->object());
    }

    public function getCalls(int $idCustomer)
    {
        $response = Http::accept('application/json')
            ->withToken($this->token)
            ->post($this->apiUrl . "/api/app_getatendimentosid", [
                "id" => "{$idCustomer}",
            ]);

        /*usort($response, function ($a, $b) {
            return strtotime($a->emissao) - strtotime($b->emissao);
        });*/

        return json_decode($response->object());
    }

    public function callInsert($call)
    {
        $response = Http::accept('application/json')
            ->withToken($this->token)
            ->post($this->apiUrl . "/api/app_insert", [
                "id_atendimento" => "0",
                "id_funcionario" => "{$call->customer_id}",
                "texto" => "{$call->texto}"
            ]);
        if ($response->successful()) {
            return $response->object();
        } else {
            return $response->throw();
        }
    }

//    public function checkExpiredTickets(int $idCustomer)
//    {
//
//        $customer = $this->search($idCustomer);
//
//
//
//    }

    public function unlockAccount()
//    public function unlockAccount($action)
    {
//        if($action){
            //        dd($this->customer->id, $this->customer->document);
            $search = $this->getBillets($this->customer->id);

//        dd(count($search), $search[0]->CpfCgc, $search[0]->Id_Cliente);

            $expired = false;

            foreach ($search as $key => $billet) {
                if ($billet->Pago == 0 && $billet->Vencimento < now()) {
                    $expired = true;
                }
            }

            if(!$expired){
                $client = [
                    "cpf_cnpj" => str_replace(['/', '-', '.'], '', $this->customer->document),
                    "id" => "{$this->customer->id}"
                ];

                $this->releaseCustomerById($client);
            }else{
                return null;
            }
//        }
    }

    public function releaseCustomerById($customer)
    {
//        dd($custom);

//        $customer = $this->getCustomer($customerID);

//        dd($customer->customer[0]);

//        $customer = [
//            "cpf_cnpj" => str_replace(['/', '-', '.'], '', $customer->customer[0]['document']),
//            "id" => "{$customer->customer[0]['id']}"
//        ];

//        dd($customer->id, $customer->cpf_cnpj);

        $response = Http::accept('application/json')
            ->withToken($this->token)
            ->post($this->apiUrl . "/api/app_liberacomid", [
                "cpf_cnpj" => str_replace(['/', '-', '.'], '', $customer->cpf_cnpj),
//                "id" => '34334'
                "id" => "{$customer->id}"
            ]);

//        dd($response->object());

        if ($response->successful()) {
            return $response->object();
        } else {
            return $response->throw();
        }
    }



//    public function serviceStore()
    public function serviceStore(int $payment_id)
    {

        //Transformar tudo em array antes de entrar com os dados
//        $payment = $payment->getAttributes();

//        $payment = json_encode((object)$payment);

//            $billet = (object)$billet

//        dd($payment);

//        $billets = "";
//
//        foreach($payment->billets as $key => $info){
//            if($payment->billets > 1){
//                $billets += $info->reference ." ". $info->duedate  ." ". !$loop->last ? ',':'';
//            }
//        }


        $text = "";
        $teste = "010101-2";
        $terminal = 2;

//        if($payment->method === 'tef'){
            $text = "Pagamento ".$payment_id.", do boleto ".$teste." pago via AUTOATENDIMENTO (ref: #010101".$terminal.") pelo terminal: ".$terminal;
//            $text = "Boleto ".$teste." pago via AUTOATENDIMENTO (ref: #".$payment.serialize().") pelo terminal: ".$terminal;
//            $text = "Boleto ".$teste." pago via AUTOATENDIMENTO (ref: #".$payment['reference'].") pelo terminal: ".$payment['terminal_id'];
        /*}elseif ($payment->method === 'ecommerce'){
            $text = "Boleto ".$teste." pago via Ecommerce (ref: #".$teste.") pela modalidade: ".$terminal;
        }else{
            $text = "Boleto ".$teste." pago via Picpay (ref: #".$teste.")";
        }*/

        $response = Http::accept('application/json')
            ->withToken($this->token)
            ->post($this->apiUrl . "/api/app_insert", [
                "id_atendimento" => "0",
                "id_funcionario" => "34334",
                "texto" => "{$text}"
            ]);
        if ($response->successful()) {
            return $response->object();
        } else {
            return $response->throw();
        }
    }



    public function getCaixa($company_id, $place)
    {
        switch($company_id) {
            case 1:
            {
                //Penha
                $caixa = ($place === "autoatendimento") ? '37' : '38';
                break;
            }
            case 5:
            {
                //JDS
                $caixa = ($place === "autoatendimento") ? '37' : '38';
                break;
            }
            case 6:
            {
                //Antonio
                $caixa = ($place === "autoatendimento") ? '37' : '38';
                break;
            }
        }

        return $caixa;
    }



}
