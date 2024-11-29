<?php

namespace App\Http\Controllers\ExecutiveApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;



class ExecutiveController extends Controller
{

    public function excutiveLogin(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|integer'
        ]);

        $phoneNumber = $request->phone_number;

        $status = Task::checkExecutiveStatus($phoneNumber);

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

            $rowsAffected = Task::updateOtp($phoneNumber, $otp);
            $isOtpSend = Task::sendOtp($phoneNumber, $otp);
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
            ->where('phone', $phoneNumber)
            ->where('otp', $otp)
            ->first();

        if ($executive) {

            $secretKey = config('app.app_secret');
            $executiveId = $executive->id;

            $apiToken = Task::createApiToken($executiveId, $secretKey);
            $isUserTokenSaved = Task::saveUserToken($phoneNumber, $apiToken);

            if ($isUserTokenSaved) {

                $executive = DB::table('executives')
                    ->leftjoin('distributers', 'executives.distributer_id', '=', 'distributers.id')
                    ->select('executives.id', 'executives.executive_name', 'distributers.id as distributer_id', 'distributers.distributer_name')
                    ->where('executives.phone', $phoneNumber)
                    ->where('executives.executive_status', 1)
                    ->get();

                $responseArr = [
                    'status' => 'success',
                    'error'  => false,
                    'message' => 'Successfully validated the OTP',
                    'apiToken' => $apiToken,
                    "payload" => $executive
                ];

                return response()->json($responseArr);
            } else {

                return handleError('TOKEN_NOT_SAVED');
            }
        } else {

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
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        $offset = ($index * 20);
        $limit = 20;

        $query = Task::initializeShopQuery($distributorId);

        $query = Task::shopQueryFilter($query, $executive_id, $latitude, $longitude, $search);

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
                'totalshops' => $totalShops,
                'todayshops' => $todayShops,
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

        $shopArr = Task::getShopByPhoneNumber($phoneNumber);

        if ($shopArr->isNotEmpty()) {

            $isMapped = Task::checkIfShopMappedToDistributor($shopArr->first()->id, $distributorId);

            if ($isMapped) {

                foreach ($shopArr as $shop) {
                    $shop->is_mapped = true;
                }
                $message = 'Shop found and already mapped to a distributor.';
                return handleSuccess($message, $shopArr);
            } else {

                foreach ($shopArr as $shop) {
                    $shop->is_mapped = false;
                }

                $message = 'Shop found but not yet mapped to a distributor.';
                return handleSuccess($message, $shopArr);
            }
        } else {

            return handleError('DATA_NOT_FOUND');
        }
    }



    public function shopOnboarding(Request $request)
    {

        $request->validate([
            "shop_name" => "required|max:225",
            "description" => "required|max:255",
            "phone_number" => "required|integer",
            "shop_open_time" => "required",
            "shop_close_time" => "required",
            "shop_type" => "required|integer",
            "place_id" => "required|integer",
            "address" => "required|max:225",
            "pincode" => "required|integer",
            "latitude" => "required|numeric",
            "longitude" => "required|numeric",
            "executive_id" => "required|integer",
            "distributor_id" => "required|integer",
            "image" => "required|image|mimes:jpeg,png,jpg,webp|max:5120", // max size 5MB
        ]);

        //check shop already onboarded
        $shopExists = Task::checkShopExists($request->phone_number);

        if ($shopExists) {
            return handleCustomError("Shop already onboarded");
        }

        //shop onboard on old server
        $response = Task::shopOnboardingOldServer($request);
        if ($response) {

            $shopOnboard = Task::shopOnboardingNewServer($request);

            if ($shopOnboard) {

                return handleSuccess('Successfully onboarded the shop.');
            } else {

                return handleCustomError("Failed to onboard new shop.");
            }
        } else {
            return handleCustomError("Failed to onboard new shop.");
        }
    }
}
