<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SharedController;


Route::get('fetchCountries', [SharedController::class, 'fetchCountries']);
Route::post('fetchStates', [SharedController::class, 'fetchStates']);
Route::post('fetchDistricts', [SharedController::class, 'fetchDistricts']);
Route::get('fetchPlaceTypes', [SharedController::class, 'fetchPlaceTypes']);
Route::post('fetchPlaces', [SharedController::class, 'fetchPlaces']);
Route::get('fecthShopServices', [SharedController::class, 'fecthShopServices']);
