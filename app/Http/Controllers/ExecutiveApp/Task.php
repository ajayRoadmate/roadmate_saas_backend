<?php

namespace App\Http\Controllers\ExecutiveApp;

use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use App\Services\HttpApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Laravel\Facades\Image;

class Task
{

    public static function checkExecutiveStatus($phoneNumber)
    {
        $executive  = DB::table('executives')
            ->where('phone', $phoneNumber)
            ->first();

        if (!$executive) {
            return 'not_registered';
        }
        if (!$executive->executive_status) {
            return 'inactive';
        }
        return 'active';
    }

    public static function updateOtp($phoneNumber, $otp)
    {
        $rowsAffected = DB::table('executives')
            ->where('phone', $phoneNumber)
            ->update(['otp' => $otp]);

        return $rowsAffected;
    }

    public static function sendOtp($mob, $otp)
    {
        $curl = curl_init();

        // $authkey = '293880AcF2Yveev2266449e95P1';
        $authkey = '293880AGohKEuy6Vl66717195P1'; //new
        $email = 'alwinespylabs@gmail.com';
        $template_id = '64cb55efd6fc05417a720692';
        // $otp = rand(1000,9999);
        $url = "https://api.msg91.com/api/v5/otp?";

        $params = array(

            'extra_param' => '{"section":"login"}',
            'unicode' => 0,
            'authkey' => $authkey,
            'template_id' => $template_id,
            'mobile' => $mob,
            'invisible' => 0,
            'mobile' => $mob,
            'otp' => $otp,
            'email' => $email,
            'otp_length' => 4,
        );

        $url_with_params = $url . http_build_query($params);

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url_with_params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => array(
                "content-type: application/json"
            ),
        ));


        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {

            return false;
        } else {

            return true;
        }
    }

    public static function createApiToken($executiveId, $secretKey)
    {
        $payload = [
            'iss' => 'roadMate',
            'iat' => time(),
            'sub' => $executiveId
        ];
        $jwt = JWT::encode($payload, $secretKey, 'HS256');

        return $jwt;
    }

    public static function saveUserToken($phoneNumber, $apiToken)
    {
        $updatedRowCount = DB::table('executives')
            ->where('phone', $phoneNumber)
            ->update(['user_token' => $apiToken]);

        return $updatedRowCount > 0;
    }

    public static function initializeShopQuery($distributorId)
    {
        $query = DB::table('shops_distributors')
            ->join('shops', 'shops_distributors.shop_id', '=', 'shops.id')
            ->select(
                'shops.id',
                'shops.shop_name',
                'shops.image',
                'shops.shop_type',
                'shops.phone_primary',
                'shops.phone_secondary',
                'shops.address',
                'shops.lat_long'
            )
            ->where('shops_distributors.distributor_id', $distributorId)
            ->where('shops_distributors.shops_distributor_status', 1);

        return $query;
    }
    public static function shopQueryFilter($query, $executive_id, $latitude, $longitude, $search)
    {
        if (!empty($executive_id)) {

            $query->where('shops_distributors.executive_id', $executive_id);
        }

        if (!empty($search)) {

            $words_explode = explode(' ', $search);
            $pattern = ".*";
            for ($i = 0; $i < count($words_explode); $i++) {
                $pattern = $pattern . $words_explode[$i] . ".*";
            }

            $query->where(function ($q) use ($pattern) {
                $q->where('shops.shop_name', 'REGEXP', $pattern)
                    ->orWhere('shops.id', 'REGEXP', $pattern);
            });
        }


        if (!empty($latitude) && !empty($longitude)) {
            $query->selectRaw("
                ROUND(
                    6371 * acos(
                        cos(radians(?)) * cos(radians(SUBSTRING_INDEX(lat_long, ',', 1))) 
                        * cos(radians(SUBSTRING_INDEX(lat_long, ',', -1)) - radians(?)) 
                        + sin(radians(?)) * sin(radians(SUBSTRING_INDEX(lat_long, ',', 1)))
                    ), 2
                ) AS distance", [$latitude, $longitude, $latitude])
                ->orderBy('distance', 'ASC');
        } else {


            $query->selectRaw('0 AS distance')
                ->orderBy('shops_distributors.id', 'desc');
        }
        return $query;
    }

    public static function getShopByPhoneNumber($phoneNumber)
    {
        $shopArr = DB::table('shops')
            ->select('id', 'shop_name', 'image', 'address')
            ->whereAny(['phone_primary', 'phone_secondary'], $phoneNumber)
            ->get();

        return $shopArr;
    }

    public static function checkIfShopMappedToDistributor($shopId, $distributorId)
    {
        $isMapped = DB::table('shops_distributors')
            ->where('shop_id', $shopId)
            ->where('distributor_id', $distributorId)
            ->exists();

        return $isMapped;
    }

    public static function checkShopExists($phoneNumber)
    {
        $isExists = DB::table('shops')
            ->whereAny(['phone_primary', 'phone_secondary'], $phoneNumber)
            ->exists();

        return $isExists;
    }

    public static function uploadResizeImage($file)
    {
        try {
            $uploadFolderName = 'img/shops';
            $image = Image::read($file->getRealPath())->resize(350, 350);
            $extension = $file->extension();
            $fileName = time() . '_' . rand(1000, 9999) . '.' . $extension;
            $image->save(public_path("$uploadFolderName/$fileName"));

            return $fileName;
        } catch (\Exception $e) {

            return $e->getMessage();
        }
    }

    public static function createNewShop(Request $request, $uploadedFilePath)
    {

        $newShopRow = [
            'shop_name' => $request->shop_name,
            'image' => $uploadedFilePath,
            'shop_type' => $request->shop_type,
            'phone_primary' => $request->phone_number,
            'phone_secondary' => $request->phone_number2,
            'address' => $request->address,
            'pincode' => $request->pincode,
            'place_id' => $request->place_id,
            'lat_long' => $request->latitude . "," . $request->longitude,
        ];

        $shopId = DB::table('shops')->insertGetId($newShopRow);

        return $shopId;
    }
    public static function createDeliveryAddress($shopId, $address, $pincode)
    {
        $newAddressRow = [
            'shop_id' => $shopId,
            'delivery_address' => $address,
            'pincode' => $pincode,
        ];

        $deliveryAddressId = DB::table('shop_delivery_addresses')->insertGetId($newAddressRow);

        return $deliveryAddressId;
    }

    public static function updateDeliveryIdonShops($shopId, $deliveryAddressId)
    {
        $updatedRowCount = DB::table('shops')
            ->where('id', $shopId)
            ->update(['delivery_id' => $deliveryAddressId]);

        return $updatedRowCount > 0;
    }

    public static function mapShopToDistributor($shopId, $distributorId, $executiveId)
    {

        $newRow = [
            'shop_id' => $shopId,
            'distributor_id' => $distributorId,
            'executive_id' => $executiveId,
        ];

        DB::table('shops_distributors')->insert($newRow);

        return true;
    }

    public static function shopOnboardingOldServer(Request $request)
    {
        $file = $request->file('image');
        $url = 'https://demo.roadmateapp.com/api/v2/shopOnboarding';
        $requestData = $request->all();

        $response = httpApiService::postRequest($url, $requestData, $file, 'image');

        if ($response['success']) {

            if (!$response['data']['error']) {

                return true; // shop successfully onboarded on old server
            } else {

                return false;
            }
        } else {

            return false;
        }
    }

    public static function shopOnboardingNewServer(Request $request)
    {
        $address = $request->address;
        $pincode = $request->pincode;
        $distributorId = $request->distributor_id;
        $executiveId = $request->executive_id;

        $uploadedFilePath = NULL;
        if ($request->hasFile('image')) {

            $uploadedFilePath =  self::uploadResizeImage($request->file('image'));
        }

        $shopId =  self::createNewShop($request, $uploadedFilePath);

        if (!$shopId) {

            return false;
        }

        self::mapShopToDistributor($shopId, $distributorId, $executiveId);

        $deliveryAddressId =  self::createDeliveryAddress($shopId, $address, $pincode);

        if ($deliveryAddressId) {

            self::updateDeliveryIdonShops($shopId, $deliveryAddressId);
        }
        return true;
    }

    public static function initializeProductQuery($distributorId)
    {
        $query = DB::table('products')
            ->leftjoin('product_variants', 'products.id', '=', 'product_variants.product_id')
            ->leftjoin('units', 'product_variants.unit_id', '=', 'units.id')
            ->select(
                'products.id as product_id',
                'products.product_name',
                'product_variants.id as variant_id',
                'product_variants.unit_id',
                'units.unit_name',
                'product_variants.unit_quantity',
                'units.unit_abbreviation',
                'product_variants.color_variant_id',
                'product_variants.stock_quantity',
                'product_variants.purchase_price',
                'product_variants.mrp',
                'product_variants.b2b_selling_price'
            )
            ->where('products.distributor_id', $distributorId)
            ->where('product_variants.b2b_status', 1)
            ->where('product_variants.approve_status', 1);

        return $query;
    }

    public static function productQueryFilter($query, $categoryId, $subCategoryId, $brandId, $search)
    {
        if (!empty($categoryId)) {

            $query->where('products.category_id', $categoryId);
        }
        if (!empty($subCategoryId)) {

            $query->where('products.sub_category_id', $subCategoryId);
        }
        if (!empty($brandId)) {

            $query->where('products.brand_id', $brandId);
        }

        if (!empty($search)) {

            $words_explode = explode(' ', $search);
            $pattern = ".*";
            for ($i = 0; $i < count($words_explode); $i++) {
                $pattern = $pattern . $words_explode[$i] . ".*";
            }

            $query->where(function ($q) use ($pattern) {
                $q->where('products.product_name', 'REGEXP', $pattern)
                    ->orWhere('products.id', 'REGEXP', $pattern);
            });
        }

        return $query;
    }

    public static function fetchProductImage($productVariantId)
    {
        $imageDetail = DB::table('product_images')
            ->where('product_variant_id', $productVariantId)
            ->where('product_image_status', 1)
            ->first();

        if ($imageDetail) {
            return $imageDetail->image;
        } else {
            return "";
        }
    }

    public static function createB2BOrder(Request $request)
    {

        $newOrderRow = [
            'shop_id' => $request->shop_id,
            'order_date' => date('Y-m-d'),
            'total_amount' => $request->total_amount,
            'delivery_date' => $request->delivery_date,
            'distributor_id' => $request->distributor_id,
            'executive_id' => $request->executive_id,
            'payment_status' => 0,
            'payment_amount' => 0,
            'order_note' => $request->order_note,
            'b2b_order_status' => 1
        ];

        $newOrderId = DB::table('b2b_orders')->insertGetId($newOrderRow);

        return $newOrderId;
    }

    public static function createB2BOrderDetails($request, $orderId)
    {

        $newOrderTxnRow = [];

        foreach ($request->products as $product) {

            $newOrderTxnRow[] = [
                'order_master_id' => $orderId,
                'product_id' => $product['product_id'],
                'product_variant_id' => $product['product_variant_id'],
                'purchase_price' => $product['purchase_price'],
                'mrp' =>  $product['mrp'],
                'selling_price' =>  $product['selling_price'],
                'quantity' => $product['quantity'],
                'b2b_order_details_status' =>  1
            ];
        }

        DB::table('b2b_order_details')->insert($newOrderTxnRow);

        return true;
    }

    public static function deleteCartItem($shopId)
    {

        DB::table('carts')
            ->where('user_id', $shopId)
            ->where('user_type', 1)
            ->delete();

        return true;
    }
    public  static function checkCartItemExists($request)
    {
        return DB::table('carts')
            ->where('user_type', 1)
            ->where('user_id', $request->shop_id)
            ->where('product_id', $request->product_id)
            ->where('product_variant_id', $request->product_variant_id)
            ->first();
    }

    public  static function checkOrderExist($orderId)
    {
        return DB::table('b2b_orders')
            ->where('id', $orderId)
            ->exists();
    }

    public static function createB2BTransactions($request)
    {

        $orderId = $request->order_id;
        $paymentMode = $request->payment_mode;
        $transactionId =  $request->transaction_id;

        if ($paymentMode == 0) {
            $transactionId = "COD" . $orderId . time();
        }

        $newOrderRow = [
            'order_id' => $orderId,
            'shop_id' => $request->shop_id,
            'transaction_id' => $transactionId,
            'transaction_amount' => $request->amount,
            'payment_mode' => $paymentMode,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

        ];
        return  DB::table('b2b_transactions')->insertGetId($newOrderRow);
    }

    public static function updateB2BOrder($orderId, $amount)
    {

        $order = DB::table('b2b_orders')
            ->where('id', $orderId)
            ->first();
        if ($order) {

            $newPaymentAmount = $order->payment_amount + $amount;

            if ($newPaymentAmount > $order->total_amount) return false;

            $paymentStatus = $newPaymentAmount == $order->total_amount
                ? 1 // Paid
                : 2; // Partially Paid

            $updatedRowCount = DB::table('b2b_orders')
                ->where('id', $orderId)
                ->update([
                    'payment_amount' => $newPaymentAmount,
                    'payment_status' => $paymentStatus,
                    'updated_at' => Carbon::now(),
                ]);

            return $updatedRowCount > 0;
        } else {

            return false;
        }
    }

    public  static function checkShopNoteExist($shopNoteId)
    {
        return DB::table('shop_notes')
            ->where('id', $shopNoteId)
            ->exists();
    }
}
