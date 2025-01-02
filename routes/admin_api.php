<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Distributor\DistributorController;
use App\Http\Controllers\Admin\Executive\ExecutiveController;
use App\Http\Controllers\Admin\ChannelPartner\ChannelPartnerController;
use App\Http\Controllers\Admin\Subscription\SubscriptionController;
use App\Http\Controllers\Admin\Shop\ShopController;
use App\Http\Controllers\Admin\Order\OrderController;
use App\Http\Controllers\Admin\Product\ProductController;



//common module
Route::get('fetchCountryFilterData', [DistributorController::class, 'fetchCountryFilterData']);
Route::get('fetchStateFilterData', [DistributorController::class, 'fetchStateFilterData']);
Route::get('fetchDistrictFilterData', [DistributorController::class, 'fetchDistrictFilterData']);
Route::get('fetchPlaceFilterData', [DistributorController::class, 'fetchPlaceFilterData']);
Route::get('fetchPlaceTypeFilterData', [DistributorController::class, 'fetchPlaceTypeFilterData']);



// distributor module
Route::get('testFun', [DistributorController::class, 'testFun']);
Route::get('fetchDistributorTableData', [DistributorController::class, 'fetchDistributorTableData']);
Route::get('fetchDistributorsUpdateFormData', [DistributorController::class, 'fetchDistributorsUpdateFormData']);
Route::post('testFormSubmit', [DistributorController::class, 'testFormSubmit']);
Route::post('updateDistributorFormSubmit', [DistributorController::class, 'updateDistributorFormSubmit']);
Route::get('deleteDistributor', [DistributorController::class, 'deleteDistributor']);
Route::post('testLogin', [DistributorController::class, 'testLogin']);



//executive module
Route::post('admin_createExecutive', [ExecutiveController::class, 'admin_createExecutive']);
Route::get('fetchDistributerFilterData', [ExecutiveController::class, 'fetchDistributerFilterData']);
Route::get('admin_fetchExecutiveTableData', [ExecutiveController::class, 'admin_fetchExecutiveTableData']);
Route::get('admin_fetchExecutiveUpdateFormData', [ExecutiveController::class, 'admin_fetchExecutiveUpdateFormData']);
Route::post('admin_updateExecutive', [ExecutiveController::class, 'admin_updateExecutive']);
Route::get('admin_deleteExecutive', [ExecutiveController::class, 'admin_deleteExecutive']);



//channel partner
Route::post('admin_createChannelPartner', [ChannelPartnerController::class, 'admin_createChannelPartner']);
Route::get('admin_fetchChannelPartnerTableData', [ChannelPartnerController::class, 'admin_fetchChannelPartnerTableData']);
Route::get('admin_fetchChannelPartnerUpdateFormData', [ChannelPartnerController::class, 'admin_fetchChannelPartnerUpdateFormData']);
Route::post('admin_updateChannelPartner', [ChannelPartnerController::class, 'admin_updateChannelPartner']);
Route::get('admin_deleteChannelPartner', [ChannelPartnerController::class, 'admin_deleteChannelPartner']);


//subscription
Route::post('admin_createSubscription', [SubscriptionController::class, 'admin_createSubscription']);
Route::get('admin_fetchSubscriptionTableData', [SubscriptionController::class, 'admin_fetchSubscriptionTableData']);
Route::get('admin_fetchSubscriptionUpdateFormData', [SubscriptionController::class, 'admin_fetchSubscriptionUpdateFormData']);
Route::post('admin_updateSubscription', [SubscriptionController::class, 'admin_updateSubscription']);
Route::get('admin_deleteSubscription', [SubscriptionController::class, 'admin_deleteSubscription']);


//shop
Route::get('admin_fetchShopTableData', [ShopController::class, 'admin_fetchShopTableData']);

//order
Route::get('admin_fetchAllOrderTableData', [OrderController::class, 'admin_fetchAllOrderTableData']);
Route::get('admin_fetchOrderUpdateFormData', [OrderController::class, 'admin_fetchOrderUpdateFormData']);
Route::get('admin_fetchOrderStatusFilterData', [OrderController::class, 'admin_fetchOrderStatusFilterData']);
Route::post('admin_updateOrder', [OrderController::class, 'admin_updateOrder']);
Route::get('admin_cancelOrder', [OrderController::class, 'admin_cancelOrder']);
Route::get('admin_fetchOrderDetailsTableData', [OrderController::class, 'admin_fetchOrderDetailsTableData']);


//product
Route::get('admin_fetchProductTableData', [ProductController::class, 'admin_fetchProductTableData']);
Route::post('admin_createProduct', [ProductController::class, 'admin_createProduct']);
Route::get('admin_fetchCategoryFilterData', [ProductController::class, 'admin_fetchCategoryFilterData']);
Route::get('admin_fetchBrandFilterData', [ProductController::class, 'admin_fetchBrandFilterData']);
Route::get('admin_fetchDistributorFilterData', [ProductController::class, 'admin_fetchDistributorFilterData']);
Route::get('admin_fetchSubCategoryFilterData', [ProductController::class, 'admin_fetchSubCategoryFilterData']);
Route::get('admin_fetchHsnCodeFilterData', [ProductController::class, 'admin_fetchHsnCodeFilterData']);
Route::get('admin_unitFilterData', [ProductController::class, 'admin_unitFilterData']);
Route::get('admin_fetchProductUpdateFormData', [ProductController::class, 'admin_fetchProductUpdateFormData']);
Route::post('admin_updateProduct', [ProductController::class, 'admin_updateProduct']);
Route::get('admin_fetchProductDetailsTableData', [ProductController::class, 'admin_fetchProductDetailsTableData']);
