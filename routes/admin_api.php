<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Distributor\DistributorController;
use App\Http\Controllers\Admin\Executive\ExecutiveController;


// distributor module
Route::get('testFun', [DistributorController::class, 'testFun']);
Route::post('testFormSubmit', [DistributorController::class, 'testFormSubmit']);
Route::get('fetchDistributorTableData', [DistributorController::class, 'fetchDistributorTableData']);
Route::get('fetchCountryFilterData', [DistributorController::class, 'fetchCountryFilterData']);
Route::get('fetchStateFilterData', [DistributorController::class, 'fetchStateFilterData']);
Route::get('fetchDistrictFilterData', [DistributorController::class, 'fetchDistrictFilterData']);
Route::get('fetchPlaceFilterData', [DistributorController::class, 'fetchPlaceFilterData']);
Route::get('fetchPlaceTypeFilterData', [DistributorController::class, 'fetchPlaceTypeFilterData']);
Route::get('fetchDistributorsUpdateFormData', [DistributorController::class, 'fetchDistributorsUpdateFormData']);
Route::post('updateDistributorFormSubmit', [DistributorController::class, 'updateDistributorFormSubmit']);



//executive module
Route::get('testFunExec', [ExecutiveController::class, 'testFunExec']);
