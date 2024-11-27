<?php

namespace App\Http\Controllers;

use App\Services\OTPService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Services\HttpApiService;

use Intervention\Image\Laravel\Facades\Image;
class ExecutiveController extends Controller
{

    protected $httpApiService;

    public function __construct(HttpApiService $httpApiService)
    {
        $this->httpApiService = $httpApiService;
    }

    public function excutiveLogin(Request $request, OTPService $otpService)
    {
        $request->validate([
            'phone_number' => 'required|integer'
        ]);

        $phoneNumber = $request->phone_number;
        
        $status = $this->task_checkExecutiveStatus($phoneNumber);

        if ($status === 'not_registered') {

            return handleError('PHONE_NUMBER_NOT_REGISTERED');
        } elseif ($status === 'inactive') {

            return handleError('NOT_ACTIVE_USER');
        } else {

            //check if the user is admin, then send static otp:5252 else dynamic otp
            if ($phoneNumber == config('app.admin.PHONE_NUMBER')) {

                $otp = config('app.admin.OTP');  
            } else {
                $otp = rand(1000, 9999);
            }

            $rowsAffected = $this->task_updateOtp($phoneNumber, $otp);
            $isOtpSend = $this->task_sendOtp($phoneNumber, $otp);                
            if ($isOtpSend) {

                return  handleSuccess('Successfully sent otp to the user');
            } else {

                return  handleError('OTP_NOT_SENT');
            }
        } 
    }

    public function executiveOtpVerify(Request $request)
    {
            $request->validate([
                'phone_number' => 'required|integer',
                'otp' => 'required|integer'
            ]);

            $otp = $request->otp;
            $phoneNumber = $request->phone_number;
    
            $executive = DB::table('executives')
                ->where('phone',$phoneNumber)
                ->where('otp',$otp)
                ->first(); 

            if ($executive) {

                $secretKey = config('app.app_secret');
                $executiveId = $executive->id;

                $apiToken = $this->task_createApiToken($executiveId, $secretKey);
                $isUserTokenSaved = $this->task_saveUserToken($phoneNumber, $apiToken);
      
                if ($isUserTokenSaved) {

                    $executive = DB::table('executives')
                        ->leftjoin('distributers','executives.distributer_id', '=', 'distributers.id')
                        ->where('executives.phone',$phoneNumber)
                        ->where('executives.executive_status',1)
                        ->get(['executives.id','executives.executive_name',
                                 'distributers.id as distributer_id','distributers.distributer_name'
                                ]);
                 
                    $responseArr = [
                        'status' => 'success',
                        'error'  => false,
                        'message' => 'Successfully validated the OTP',
                        'apiToken' => $apiToken,
                        "payload" => $executive
                    ];
    
                    return response()->json($responseArr);
                } else{

                    return handleError('TOKEN_NOT_SAVED');
                }
            } else{

                return handleError('OTP_INVALID');
            }
    }

    public function fetchDistributorShops(Request $request)
    {
        $request->validate([
            'distributor_id' => 'required|integer|min:1',
            'index' => 'required|integer|min:0',
            'executive_id' => 'nullable|integer|min:1',
            'search' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $distributorId = $request->distributor_id;
        $index = $request->index;
        $executive_id = $request->executive_id;
        $search = $request->search;
        $latitude=$request->latitude;
        $longitude=$request->longitude;

        $offset = ($index * 20);
        $limit = 20;

        $query = $this->task_initializeShopQuery( $distributorId );
      
        $query = $this->task_shopQueryFilter($query,$executive_id,$latitude,$longitude,$search);
       
        $shopArr = $query->clone()->offset($offset)
                ->limit($limit)
                ->get();

        if ($shopArr->isNotEmpty()) {

            $totalShops = 0;
            $todayShops = 0;
            $today = Carbon::today();

            $totalShops = $query->count(); 
            $todayShops = $query->whereDate('shops_distributors.created_at', $today)->count(); 

            return response()->json([
                'status' => 'success',
                'error' => false, 
                'totalshops'=>$totalShops,
                'todayshops' =>$todayShops,
                'payload' => $shopArr,
                'message' => 'Successfully got data from the server'
            ]);

        } else {

            return handleError('DATA_NOT_FOUND');
        }        
    }

    public function searchShopByNumber(Request $request)
    {
        $request->validate([
            'distributor_id' => 'required|integer|min:1',
            'phone_number' => 'required|integer',
        ]);

        $phoneNumber = $request->phone_number;
        $distributorId = $request->distributor_id;

        $shopArr = $this->task_getShopByPhoneNumber($phoneNumber);

        if ($shopArr ->isNotEmpty()) {

            $isMapped = $this->task_checkIfShopMappedToDistributor($shopArr->first()->id,$distributorId);

            if($isMapped) {

                foreach ($shopArr as $shop) {
                    $shop->is_mapped = true;
                }
                $message = 'Shop found and already mapped to a distributor.';
                return handleSuccess( $message, $shopArr);
            } else {

                foreach ($shopArr as $shop) {
                    $shop->is_mapped = false;
                }
               
                $message = 'Shop found but not yet mapped to a distributor.';
                return handleSuccess( $message, $shopArr);
            }
        } else {

            return handleError('DATA_NOT_FOUND');
        }
    }

    public function fetchCountries() 
    {
        $countryArr = DB::table('countries')
            ->where('country_status',1)
            ->get(['id','country_name']);

        return handleFetchResponse($countryArr);
    }

    public function fetchStates(Request $request) 
    {
        $request->validate([
            'country_id' => 'required|integer'
        ]);

        $countryId = $request->country_id;

        $stateArr = DB::table('states')
            ->where('country_id',$countryId)
            ->where('state_status',1)
            ->get(['id','state_name']);

        return handleFetchResponse($stateArr);
    }

    public function fetchDistricts(Request $request)
    {
        $request->validate([
            'state_id' => 'required|integer'
        ]);

        $stateId = $request->state_id;

        $districtArr = DB::table('districts')
            ->where('state_id',$stateId)
            ->where('district_status',1)
            ->get(['id','district_name']);

        return handleFetchResponse($districtArr);
    }

    public function fetchPlaceTypes()
    {

        $placeTypeArr = DB::table('place_types')
            ->where('place_type_status',1)
            ->get(['id','place_type_name']);

        return handleFetchResponse($placeTypeArr);
    }

    public function fetchPlaces(Request $request)
    {
        $request->validate([
            'district_id' => 'required|integer',
            'place_type_id' => 'required|integer'
        ]);

        $districtId = $request->district_id;
        $placeTypeId = $request->place_type_id;

        $placeArr = DB::table('places')
            ->where('district_id',$districtId)
            ->where('place_type_id', $placeTypeId)
            ->where('place_status',1)
            ->get(['id','place_name']);

        return handleFetchResponse($placeArr);

    }

    public function fecthShopServices()
    {
        $shopServiceArr = DB::table('services')
            ->where('service_type',1)
            ->where('service_status',1)
            ->orderBy('order_number', 'ASC')
            ->get(['id','service_name','image']);

        return handleFetchResponse($shopServiceArr);

    }

    public function shopOnboarding(Request $request)
    {

        $request->validate([
            "shop_name"=>"required|max:225",
            "description"=>"required|max:255",
            "phone_number"=>"required|integer",
            "shop_open_time"=>"required",
            "shop_close_time"=>"required",
            "shop_type"=>"required|integer",
            "place_id"=>"required|integer",
            "address"=>"required|max:225",
            "pincode"=>"required|integer",
            "latitude"=>"required|numeric",
            "longitude"=>"required|numeric",
            "executive_id"=>"required|integer",
            "distributor_id"=>"required|integer",
            "image"=>"required|image|mimes:jpeg,png,jpg,webp|max:5120", // max size 5MB
        ]);
        
        $phoneNumber = $request->phone_number;
        $address = $request->address;
        $pincode = $request->pincode;
        $distributorId = $request->distributor_id;
        $executiveId = $request->executive_id;

        // $shopArr = $this->task_getShopByPhoneNumber($phoneNumber);

        // if ($shopArr->isNotEmpty()) {
        //     return handleCustomError("Shop already onboarded");
        // }
        // $uploadedFilePath = "test";
        // if ($request->hasFile('image')) {
            
        //     $uploadedFilePath = $this->task_uploadResizeImage($request->file('image'));     
        // }
            
        // $shopId = $this->task_createNewShop($request, $uploadedFilePath);

        // if (!$shopId) {

        //     return handleCustomError("Failed to onboard new shop.");
        // }

        // $this->task_mapShopToDistributor($shopId, $distributorId, $executiveId);

        // $deliveryAddressId = $this->task_createDeliveryAddress($shopId, $address, $pincode);

        // if ($deliveryAddressId) {

        //     $this->task_updateDeliveryIdonShops($shopId, $deliveryAddressId);
        // }

        $response = $this->task_shopOnboardingOldServer($request);
        return $response;
         return handleSuccess('Successfully onboarded the shop.');
         
    }





//-----------------task functions -------------------
    public function task_checkExecutiveStatus($phoneNumber)
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

    public function task_updateOtp($phoneNumber, $otp)
    {
        $rowsAffected = DB::table('executives')
            ->where('phone', $phoneNumber)
            ->update(['otp' => $otp]);

        return $rowsAffected;
    }

    public function task_sendOtp($mob, $otp)
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

    public function task_createApiToken($executiveId, $secretKey)
    {
        $payload = [
            'iss' => 'roadMate',  
            'iat' => time(),         
            'sub' => $executiveId
        ];
        $jwt = JWT::encode($payload, $secretKey, 'HS256');

        return $jwt;
    }

    public function task_saveUserToken($phoneNumber, $apiToken)
    {
        $updatedRowCount = DB::table('executives')
        ->where('phone', $phoneNumber)
        ->update(['user_token' => $apiToken]);

        return $updatedRowCount > 0;
    }

    public function task_initializeShopQuery($distributorId)
    {
        $query = DB::table('shops_distributors')
            ->join('shops', 'shops_distributors.shop_id', '=', 'shops.id')
            ->select('shops.id','shops.shop_name','shops.image','shops.shop_type',
                'shops.phone_primary','shops.phone_secondary','shops.address',
                'shops.lat_long')
            ->where('shops_distributors.distributor_id',$distributorId )
            ->where('shops_distributors.shops_distributor_status',1 );
        
        return $query;

    }
    public function task_shopQueryFilter($query,$executive_id,$latitude,$longitude,$search)
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
        

        if (!empty($latitude)&& !empty($longitude)) {
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
                ->orderBy('shops_distributors.id','desc');
        }
        return $query;
    }

    public function task_getShopByPhoneNumber($phoneNumber)
    {
        $shopArr = DB::table('shops')
            ->whereAny(['phone_primary','phone_secondary'],$phoneNumber)
            ->get(['id','shop_name','image','address']);

        return $shopArr;
    }

    public function task_checkIfShopMappedToDistributor($shopId, $distributorId)
    {
        $isMapped = DB::table('shops_distributors')
            ->where('shop_id', $shopId)
            ->where('distributor_id', $distributorId)
            ->exists(); 
        
        return $isMapped;
    }

    public function task_uploadResizeImage($file)
    { 
        try {
                $uploadFolderName = 'img/shops';
                $image = Image::read($file->getRealPath())->resize(350, 350);
                $extension = $file->extension();
                $fileName = time() . '_'. uniqid() .'.' . $extension;
                $image->save(public_path("$uploadFolderName/$fileName"));
                
                return $fileName; 

        } catch (\Exception $e) {
           
            return $e->getMessage();
        }
    }

    public function task_createNewShop(Request $request, $uploadedFilePath )
    {

        $newShopRow = [
            'shop_name' => $request->shop_name ,
            'image' => $uploadedFilePath,
            'shop_type' => $request->shop_type,
            'phone_primary' => $request->phone_number,
            'phone_secondary' => $request->phone_number2,
            'address' => $request->address ,
            'pincode' => $request->pincode,
            'place_id' => $request->place_id,
            'lat_long' => $request->latitude.",".$request->longitude,
        ];

        $shopId = DB::table('shops')->insertGetId($newShopRow);
      
        return $shopId;
    }
    public function task_createDeliveryAddress($shopId, $address, $pincode) 
    {
        $newAddressRow = [
            'shop_id' => $shopId ,
            'delivery_address' => $address,
            'pincode' => $pincode,
        ];

        $deliveryAddressId = DB::table('shop_delivery_addresses') ->insertGetId($newAddressRow);

        return $deliveryAddressId; 
    }

    public function task_updateDeliveryIdonShops($shopId, $deliveryAddressId)
    {
       $updatedRowCount = DB::table('shops')
            ->where('id', $shopId)
            ->update(['delivery_id' => $deliveryAddressId]);

        return $updatedRowCount > 0;
    }

    public function task_mapShopToDistributor( $shopId, $distributorId, $executiveId)
    {

        $newRow = [
            'shop_id' => $shopId ,
            'distributor_id' => $distributorId,
            'executive_id' => $executiveId,
        ];

        DB::table('shops_distributors')->insert($newRow);
      
        return true;
    }

    public function task_shopOnboardingOldServer(Request $request) 
    {

        $requestData= [
            'phnum' =>$request->phone_number,
            'type' =>$request->shop_type,
            'shopname' =>$request->shop_name,
            'phnum2' =>$request->phone_number2,
            'desc' =>$request->description,
            'opentime' =>$request->open_time,
            'closetime' =>$request->close_time,
            'agrimentverification_status' =>1,
            'address' =>$request->address,
            'pincode' =>$request->pincode,
            'latitude' =>$request->latitude,
            'logitude' =>$request->longitude,
            'trans_id' =>0,
            'pay_status' =>1,
        ];

        $file = $request->file('image');
        $url = 'https://demo.roadmateapp.com/api/shopreg_exe_authorised';
       
        $response = $this->httpApiService->postRequest($url, $requestData, $file, 'image');

        if ($response['success']) {
            return $response['data'];
        } else {
           return false;
        }
    }

}
