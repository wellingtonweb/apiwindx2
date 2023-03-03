<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


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

    public function __construct()
    {
        if (!App::environment('produtction')) {
            $this->apiUrl = getenv('VIGO_SANDBOX_APIURL');
            $this->login = getenv('VIGO_SANDBOX_LOGIN');
            $this->password = getenv('VIGO_SANDBOX_PASSWORD');
            $this->caixa = getenv('VIGO_SANDBOX_CAIXA');
        } else {
            $this->apiUrl = getenv('VIGO_PROD_APIURL');
            $this->login = getenv('VIGO_PROD_LOGIN');
            $this->password = getenv('VIGO_PROD_PASSWORD');
            $this->caixa = getenv('VIGO_PROD_CAIXA');
        }
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
                        "billets" => $this->getBillets($customer->id)
                    ]);
                }
            } else {
                array_push($this->customer, (object)[
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
                    "billets" => $this->getBillets($customers->id)
                ]);
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
                "billets" => $this->getBillets($customers->id)
            ];
        }
        return $this;
    }

    public function checkoutBillet($billet)
    {
        $billet = (object)$billet;

        $response = Http::accept('application/json')
            ->withToken($this->token)
            ->post($this->apiUrl . "/api/app_liquidaboleto", [
                "id_boleto" => "{$billet->billet_id}",
                "id_caixa" => "{$this->caixa}",
                "valor_pago" => "{$billet->total}"
            ]);
        if ($response->successful()) {
            return $response->object();
        } else {
            return $response->throw();
        }
    }

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
    /*public function reverseBillet($billet)
    {
        $billet = (object)$billet;
        $response = Http::accept('application/json')->withToken($this->token)
            ->post($this->apiUrl . "/api/app_estornaboleto", [
                "id_boleto" => "{$billet->billet_id}",
                "id_caixa" => "{$this->caixa}",
            ]);
        if ($response->successful()) {
            return $response->object();
        } else {
            return $response->throw();
        }

    }*/

    public function search(int $idCustomer)
    {
        return [
            "customer" => $this->getCustomer($idCustomer),
            "billets" => json_decode($this->getBillets($idCustomer))
        ];
    }
}
