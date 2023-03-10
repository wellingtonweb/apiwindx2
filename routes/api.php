<?php

use App\Http\Controllers\Api\CallbackController;
use App\Http\Controllers\Api\CustomerController;
use App\Jobs\ProcessBillets;
use App\Models\Payment;
use App\Services\VigoClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\TerminalController;
use App\Http\Controllers\Api\TerminalPaymentsController;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login', [AuthController::class, 'login'])->name('api.login');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
})->name('api.user');

Route::name('api.')->middleware('auth:sanctum')->group(function () {
    Route::get('payments/revert/{payment}', [PaymentController::class, "revertPayment"]);
    Route::apiResource('payments', PaymentController::class);
    Route::apiResource('terminals', TerminalController::class);

    // Terminal Payments
    Route::get('/terminals/{terminal}/payments', [TerminalPaymentsController::class, 'index']);

    // Vigo customers
    Route::post('/customer/login', [CustomerController::class, "login"]);
    Route::post('/customer/central/login', [CustomerController::class, "loginCentral"]);
    Route::get('/customer/{customer}', [CustomerController::class, "find"]);
});

Route::post('callback', [CallbackController::class, "index"]);

/*Route::get('teste', function () {
    $payment = Payment::find(3)->first();

foreach ($payment->billets as $billet){

        if (Str::contains($payment->status, ['approved', 'canceled','chargeback'])) {
//            $action = ($payment->status === "approved") ? true : false;
            $action = true;
            ProcessBillets::dispatch((array)$billet,$action);
//                ($action) ?  (new VigoClient())->checkoutBillet($billet) : (new VigoClient())->reverseBillet($billet);

        }


}

});*/
