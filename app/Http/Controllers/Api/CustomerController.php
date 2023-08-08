<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PaymentController;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerCollection;
use App\Http\Resources\PaymentCollection;
use App\Models\Payment;
use App\Services\VigoClient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use App\Services\WindxClient;

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

    /**
     * @param  $customer_id
     * @return PaymentCollection
     */
    public function payments($customer_id)
    {
        $payments = Payment::where('customer', $customer_id)->get();

        return new PaymentCollection($payments);
    }

    
    /**
     * @return PaymentCollection
     */
    public function checkPaymentsToday()
    {
        return (new WindxClient())->scanPaymentsToday();

        //return (new WindxClient())->getCheckPaymentsToday();

        //return new PaymentCollection($payments);
    }

    /**
     * @param  $customer_id
     * @return PaymentCollection
     */
    public function paymentsCustomerToday($customer_id)
    {
        return (new WindxClient())->getPaymentsCustomerToday($customer_id);

        //return new PaymentCollection($payments);
    }

    public function release(Request $request)
    {

//        $data = (array)$request->all();
//        return response()->json($request);

        $response =  $this->vigoClient->releaseCustomerById($request);
//        return response()->json($response);
////        dd($response);
//
        if(!empty($response)){
            return response()->json($response);
        }else{
            return response()->json(false);
        }
    }
}
