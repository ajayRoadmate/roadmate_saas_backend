<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExecutiveApp\ExecutiveController;
use App\Http\Middleware\ValidateExecutiveKey;


Route::middleware([ValidateExecutiveKey::class])->group(function () {

    Route::post('excutivelogin', [ExecutiveController::class, 'excutiveLogin']);
    Route::post('executiveOtpVerify', [ExecutiveController::class, 'executiveOtpVerify']);
});

Route::post('fetchDistributorShops', [ExecutiveController::class, 'fetchDistributorShops']);
Route::post('searchShopByNumber', [ExecutiveController::class, 'searchShopByNumber']);
Route::post('shopOnboarding', [ExecutiveController::class, 'shopOnboarding']);
Route::post('fetchDistributorProducts', [ExecutiveController::class, 'fetchDistributorProducts']);
Route::post('executivePlaceorder', [ExecutiveController::class, 'executivePlaceorder']);
Route::post('fetchOrdersByExecutive', [ExecutiveController::class, 'fetchOrdersByExecutive']);
Route::post('fetchOrdersByShop', [ExecutiveController::class, 'fetchOrdersByShop']);
Route::post('fetchOrderDetailsById', [ExecutiveController::class, 'fetchOrderDetailsById']);
Route::post('createCartItem', [ExecutiveController::class, 'createCartItem']);
Route::post('deleteCartItem', [ExecutiveController::class, 'deleteCartItem']);
Route::post('updateCartItem', [ExecutiveController::class, 'updateCartItem']);
Route::post('fetchCartItems', [ExecutiveController::class, 'fetchCartItems']);
Route::post('fetchPendingPayments', [ExecutiveController::class, 'fetchPendingPayments']);
Route::post('fetchPaymentDetails', [ExecutiveController::class, 'fetchPaymentDetails']);
Route::post('executiveOrderPayment', [ExecutiveController::class, 'executiveOrderPayment']);


Route::get('testExecutives', [ExecutiveController::class, 'testExecutives']);
