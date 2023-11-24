<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerCollection;
use App\Http\Resources\PaymentCollection;
use App\Models\Payment;
use App\Services\VigoClient;
use App\Services\VigoServer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

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

    public function calls($customer)
    {
        $response =  $this->vigoClient->getCalls($customer);

        if(!empty($response)){
            return response()->json($response);
        }else{
            return response()->json(false);
        }
    }

    /**
     * @param  $customer_id
     * @return PaymentCollection
     */
    public function payments($customer_id)
    {
        $payments = Payment::where('customer', $customer_id)->get();

        return new PaymentCollection($payments);
    }

    public function release(Request $request)
    {
        $response =  $this->vigoClient->releaseCustomerById($request);

        if(!empty($response)){
            return response()->json($response);
        }else{
            return response()->json(false);
        }
    }

    public function callNew(Request $request)
    {
        $response = $this->vigoClient->callInsert($request);

        if(!empty($response)){
            return response()->json($response);
        }else{
            return response()->json(false);
        }
    }

    public function checkLoginCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|email:rfc,dns',
        ]);

        if($validator->fails()) {
            return response()->json(false);
        }

        $response = (new VigoServer())->checkLoginCustomer($request->login);

        return $response;

//        if(!empty($response)){
//            return response()->json($response);
//        }else{
//            return response()->json(false);
//        }
    }

    public function resetPassword(Request $request)
    {
        $response = (new VigoServer())
            ->setNewPasswordCustomer([
                'customer_id' => $request->customer_id,
                'customer_password' => $request->customer_password
            ]);

        return $response;

//        if(!empty($response)){
//            return response()->json($response);
//        }else{
//            return response()->json(false);
//        }
    }

}
