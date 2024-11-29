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

Route::get('testExecutives', [ExecutiveController::class, 'testExecutives']);





