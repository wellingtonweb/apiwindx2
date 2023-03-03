<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerCollection;
use App\Services\VigoClient;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CustomerController extends Controller
{

    private $vigoClient;

    public function __construct()
    {
        $this->vigoClient = (new VigoClient())->login();
    }

    public function login(Request $request)
    {
        $document = $request->get('document');

        $response =  $this->vigoClient->getCustomer($document,"CPFCGC");
        if(!empty($response->customer)){
            return response()->json($response->customer);
        }else{
            return response()->json(false);
        }
    }

    public function loginCentral(CustomerRequest $request)
    {
        $login = $request->get('login');
        $password = $request->get('password');

        $response =  (object)$this->vigoClient->central($login,$password);

        if(!empty($response->customer)){
            return response()->json($response->customer);
        }else{
            return response()->json(false);
        }
    }

    public function find($customer)
    {
        $response =  $this->vigoClient->getCustomer($customer);

        if(!empty($response->customer)){
            return response()->json($response->customer);
        }else{
            return response()->json(false);
        }
    }
}
