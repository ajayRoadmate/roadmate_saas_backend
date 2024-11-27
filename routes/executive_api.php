<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExecutiveController;
use App\Http\Middleware\ValidateExecutiveKey;


Route::middleware([ValidateExecutiveKey::class])->group(function () {

    Route::post('excutivelogin', [ExecutiveController::class, 'excutiveLogin']);
    Route::post('executiveOtpVerify', [ExecutiveController::class, 'executiveOtpVerify']);
});

Route::post('fetchDistributorShops', [ExecutiveController::class, 'fetchDistributorShops']);
Route::post('searchShopByNumber', [ExecutiveController::class, 'searchShopByNumber']);
Route::get('fetchCountries', [ExecutiveController::class, 'fetchCountries']);
Route::post('fetchStates', [ExecutiveController::class, 'fetchStates']);
Route::post('fetchDistricts', [ExecutiveController::class, 'fetchDistricts']);
Route::get('fetchPlaceTypes', [ExecutiveController::class, 'fetchPlaceTypes']);
Route::post('fetchPlaces', [ExecutiveController::class, 'fetchPlaces']);
Route::get('fecthShopServices', [ExecutiveController::class, 'fecthShopServices']);
Route::post('shopOnboarding', [ExecutiveController::class, 'shopOnboarding']);






