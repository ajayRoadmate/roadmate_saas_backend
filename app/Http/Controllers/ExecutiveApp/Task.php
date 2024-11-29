<?php

namespace App\Http\Controllers\ExecutiveApp;

use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use App\Services\HttpApiService;
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
}
