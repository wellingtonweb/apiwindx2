<?php

use App\Http\Controllers\Api\CallbackController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\VigoController;
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

Route::post('callback', [CallbackController::class, "index"]);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
})->name('api.user');

Route::name('api.')->middleware('auth:sanctum')->group(function () {
    Route::get('payments/revert/{payment}', [PaymentController::class, "revertPayment"]);
    Route::apiResource('payments', PaymentController::class);
    Route::apiResource('terminals', TerminalController::class);
    Route::get('payments/cancel/{payment}', [PaymentController::class, "cancelPayment"]);
    Route::get('/payments-pending', [PaymentController::class, "runnerJob"]);
    Route::get('/send-mail-coupon-pdf/{payment}', [PaymentController::class, "sendMailCouponPDF"]);

    // Customer Payments
    Route::get('/customer/{customer}/payments', [CustomerController::class, "payments"]);

    // Terminal Payments
    Route::get('/terminals/{terminal}/payments', [TerminalPaymentsController::class, 'index']);
    Route::get('/terminals/{terminal}/payments', [TerminalPaymentsController::class, 'index']);

    // Vigo customers
    Route::post('/customer/login', [CustomerController::class, "login"]);
    Route::post('/customer/central/login', [CustomerController::class, "loginCentral"]);
    Route::get('/customer/{customer}', [CustomerController::class, "find"]);
    Route::get('/customer/calls/{customer}', [CustomerController::class, "calls"]);
    Route::post('/customer/call/new', [CustomerController::class, "callNew"]);
    Route::post('/customer/reset-password', [CustomerController::class, "resetPassword"]);
    Route::post('/customer/check-login-customer', [CustomerController::class, "checkLoginCustomer"]);

    Route::post('/customer/release', [CustomerController::class, "release"]);
//    Route::get('/customer/{customer}/release', [CustomerController::class, "release"]);

    // Vigo informations old payments
    Route::get('/old/cielo/payments', [VigoController::class, 'cielo']);
    Route::get('/old/paygo/payments', [VigoController::class, 'paygo']);
    Route::get('/old/picpay/payments', [VigoController::class, 'picpay']);
    Route::get('/old/terminals', [VigoController::class, 'terminals']);

});

