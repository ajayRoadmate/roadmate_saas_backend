<?php

namespace App\Http\Controllers\Admin\Distributor;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Carbon\Carbon;


class DistributorController extends Controller
{

    //test change
    public function testFun(Request $request){

        $headerValue = $request->header('user-token');

        $appSecret = config('app.app_secret');

        $decoded = JWT::decode($headerValue, new Key($appSecret, 'HS256'));

        return response()->json([
            'status'=> 'success',
            'userToken'=> $decoded
        ]);

    }

    public function testFormSubmit(Request $request){

        $request->validate([
            'distributor_name' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
            'place_id' => 'required|integer'
        ]);
        
        $newDistributorsRow = [
            'distributor_name' => $request['distributor_name'],
            'address' => $request['address'],
            'phone' => $request['phone'],
            'email' => $request['email'],
            'password' => $request['password'],
            'place_id' => $request['place_id'],
            'gst_number' => $request['gst_number']
        ];
        
        DB::table('distributors')
        ->insert($newDistributorsRow);

        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully inserted data into the server.'
        ];

        return response()->json($responseArr);
        
    }

    public function admin_createDistributor(Request $request){

        $request->validate([
            'distributor_name' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
            'place_id' => 'required|integer',
            'channel_partner_id' => 'required'
        ]);

        $newUserRow = [
            'name' => $request['distributor_name'],
            'user_type' => 3,
            'email' => $request['email'],
            'phone' => $request['phone'],
            'password' => $request['password'],
            'user_token' => 'initial'
        ];

        $userId = DB::table('roadmate_users')
        ->insertGetId($newUserRow);
        
        $newDistributorsRow = [
            'user_id' => $userId,
            'channel_partner_id' => $request['channel_partner_id'],
            'distributor_name' => $request['distributor_name'],
            'address' => $request['address'],
            'phone' => $request['phone'],
            'email' => $request['email'],
            'password' => $request['password'],
            'place_id' => $request['place_id'],
            'gst_number' => $request['gst_number']
        ];

        
        DB::table('distributors')
        ->insert($newDistributorsRow);

        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully inserted data into the server.'
        ];

        return response()->json($responseArr);
        
    }


    public function  fetchCountryFilterData(Request $request) {

        $data = DB::table('countries')
        ->select('id as filter_value', 'country_name as filter_display_value')
        ->get();

        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully got data from the server.',
            'payload' => $data
        ];

        return response()->json($responseArr);

    }

    public function  fetchStateFilterData(Request $request) {

        $request->validate([
            'item_key' => 'required',
            'item_value'=> 'required'
        ]);

        $data = DB::table('states')
        ->select('id as filter_value', 'state_name as filter_display_value')
        ->where($request['item_key'],$request['item_value'])
        ->get();

        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully got data from the server.',
            'payload' => $data
        ];

        return response()->json($responseArr);

    }

    public function  fetchDistrictFilterData(Request $request) {

        $request->validate([
            'item_key' => 'required',
            'item_value'=> 'required'
        ]);

        $data = DB::table('districts')
        ->select('id as filter_value', 'district_name as filter_display_value')
        ->where($request['item_key'],$request['item_value'])
        ->get();

        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully got data from the server.',
            'payload' => $data
        ];

        return response()->json($responseArr);

    }

    public function  fetchPlaceFilterData(Request $request) {

        $request->validate([
            'item_key' => 'required',
            'item_value'=> 'required',
            'place_type_id' => 'required'
        ]);

        $data = DB::table('places')
        ->select('id as filter_value', 'place_name as filter_display_value')
        ->where($request['item_key'],$request['item_value'])
        ->where('place_type_id', $request['place_type_id'])
        ->get();

        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully got data from the server.',
            'payload' => $data
        ];

        return response()->json($responseArr);

    }

    public function  fetchPlaceTypeFilterData(Request $request) {


        $data = [
            ["filter_value" => 1, "filter_display_value" => "Panchayath"],
            ["filter_value" => 2, "filter_display_value" => "Municipality"],
            ["filter_value" => 3, "filter_display_value" => "Corporation"]
        ];

        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully got data from the server.',
            'payload' => $data
        ];

        return response()->json($responseArr);

    }

    public function fetchChannelPartnerFilterData(Request $request){

        $filterData = DB::table('channel_partners')
        ->select('channel_partners.id as filter_value', 'channel_partners.channel_partner_name as filter_display_value')
        ->where('channel_partner_status',1)
        ->get();

        if($filterData->isNotEmpty()){

            $responseArr = [
                'status' => 'success',
                'message' => 'Successfully added product into the database.',
                'payload' => $filterData
            ];
    
            return response($responseArr);
        }
        else{

            $responseArr = [
                'status' => 'failed',
                'message' => 'Failed to fetch data form the server',
                'payload' => $filterData
            ];
    
            return response($responseArr);
        }
        
    }

    public function  distributor_fetchInfo(Request $request) {


        $currentDate = Carbon::now()->toDateTimeString();

        $headerInfo = $this->task_getHeaderInfo($request);
        $distributorId = $headerInfo->distributorId;

        $data = DB::table('distributors')
        ->leftJoin('places','distributors.place_id','=','places.id')
        ->leftJoin('districts','places.district_id','=','districts.id')
        ->leftJoin('states','districts.state_id','=','states.id')
        ->where('distributors.id',$distributorId)
        ->select(
            'distributors.distributor_name',
            'distributors.address',
            'distributors.email',
            'distributors.phone'
        )
        ->get()
        ->first();

        if($data){

            $responseArr = [
                'status' => 'success',
                'message' => 'Successfully got data from the server.',
                'payload' => $data
            ];
        }
        else{

            $responseArr = [
                'status' => 'failed',
                'message' => 'Failed to get data from the server.',
                'payload' => $data
            ];
        }

        return response()->json($responseArr);

    }




    //distributer api----------------------------------------------------------------

    public function fetchDistributorTableData(Request $request){

        $tableColumns = ['distributors.id as distributor_id', 'distributors.distributor_name', 'distributors.address', 'distributors.phone'];
        $searchFields = ['distributors.id','distributors.distributor_name'];
        $itemStatus = ['status_column' => 'distributor_status', 'status_value' => 1];
         
        $table = DB::table('distributors')
        ->select( 'distributors.id as distributor_id', 'distributors.distributor_name', 'distributors.address', 'distributors.phone');

        return $this->task_queryTableData($table, $tableColumns, $searchFields, $request, $itemStatus);
         
    }

    public function  fetchDistributorsUpdateFormData(Request $request) {

        $request->validate([
            'item_key' => 'required',
            'item_value' => 'required'
        ]);


        $data = DB::table('distributors')
        ->leftJoin('places','distributors.place_id','=','places.id')
        ->leftJoin('districts','places.district_id','=','districts.id')
        ->leftJoin('states','districts.state_id','=','states.id')
        ->where($request['item_key'],$request['item_value'])
        ->select(
            'distributors.channel_partner_id',
            'distributors.distributor_name',
            'distributors.address',
            'distributors.email',
            'distributors.phone',
            'distributors.password',
            'distributors.place_id',
            'distributors.gst_number',
            'places.place_type_id',
            'places.district_id',
            'districts.state_id',
            'states.country_id'
        )
        ->get()
        ->first();

        if($data){

            $responseArr = [
                'status' => 'success',
                'message' => 'Successfully got data from the server.',
                'payload' => $data
            ];
        }
        else{

            $responseArr = [
                'status' => 'failed',
                'message' => 'Failed to get data from the server.',
                'payload' => $data
            ];
        }

        return response()->json($responseArr);

    }

    public function updateDistributorFormSubmit(Request $request){

        $request->validate([
            'distributor_name' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
            'channel_partner_id' => 'required',
            'place_id' => 'required|integer',
            'update_item_key' => 'required',
            'update_item_value' => 'required'
        ]);

        
        $newDistributorsRow = [
            'distributor_name' => $request['distributor_name'],
            'address' => $request['address'],
            'phone' => $request['phone'],
            'email' => $request['email'],
            'password' => $request['password'],
            'channel_partner_id' => $request['channel_partner_id'],
            'place_id' => $request['place_id'],
            'gst_number' =>$request['gst_number']
        ];
        
        $updatedRows = DB::table('distributors')
        ->where($request['update_item_key'], $request['update_item_value'])
        ->update($newDistributorsRow);


        if ($updatedRows > 0) {

            $responseArr = [
                'status' => 'success',
                'error' => false,
                'message' => 'Successfully updated data in the server.'
            ];
        } else {

            $responseArr = [
                'status' => 'failed',
                'error' => true,
                'message' => 'Data is already upto date in the server'
            ];
        }

        return response()->json($responseArr);


    }

    public function deleteDistributor(Request $request){

        $request->validate([
            'item_key' => 'required',
            'item_value' => 'required'
        ]);

        $IN_ACTIVE_STATUS = 0;
        $newDistributorsRow = [
            'distributor_status' => $IN_ACTIVE_STATUS
        ];

        $updatedRows = DB::table('distributors')
        ->where($request['item_key'],$request['item_value'])
        ->update($newDistributorsRow);


        if($updatedRows > 0){

            $responseArr = [
                'status' => 'success',
                'error' => false,
                'message' => 'Successfully deleted the distributor'
            ];
        }
        else{

            $responseArr = [
                'status' => 'failed',
                'error' => true,
                'message' => 'Failed to delete distributor.'
            ];
        }

        return response()->json($responseArr);


    }

    public function testAdminLogin(Request $request){

        $appSecret = config('app.app_secret');
        
        $payload = [
            'iss' => 'roadMate',  
            'iat' => time(),
            'userId' => 1,   
            'userType' => 1
        ];
        
        $userToken = JWT::encode($payload, $appSecret, 'HS256');

        $responseArr = [
            'status' => 'success',
            'payload' => [
                'userToken' => $userToken
            ]
        ];

        return response()->json($responseArr);

    }

    public function testDistributorLogin(Request $request){

        $appSecret = config('app.app_secret');
        
        $payload = [
            'iss' => 'roadMate',  
            'iat' => time(),
            'userId' => 1,   
            'userType' => 3,
            'distributorId' => 1
        ];
        
        $userToken = JWT::encode($payload, $appSecret, 'HS256');

        $responseArr = [
            'status' => 'success',
            'payload' => [
                'userToken' => $userToken
            ]
        ];

        return response()->json($responseArr);

    }

    public function testChannelPartnerLogin(Request $request){

        $appSecret = config('app.app_secret');
        
        $payload = [
            'iss' => 'roadMate',  
            'iat' => time(),
            'userId' => 11,   
            'userType' => 4,
            'channelPartnerId' => 1
        ];
        
        $userToken = JWT::encode($payload, $appSecret, 'HS256');

        $responseArr = [
            'status' => 'success',
            'payload' => [
                'userToken' => $userToken
            ]
        ];

        return response()->json($responseArr);

    }    

    public function channelPartner_fetchDistributorTableData(Request $request){

        $tableColumns = ['distributors.id as distributor_id', 'distributors.distributor_name', 'distributors.address', 'distributors.phone'];
        $searchFields = ['distributors.id','distributors.distributor_name'];
        $itemStatus = ['status_column' => 'distributor_status', 'status_value' => 1];
         
        $table = DB::table('distributors')
        ->select( 'distributors.id as distributor_id', 'distributors.distributor_name', 'distributors.address', 'distributors.phone');

        return $this->task_queryTableData($table, $tableColumns, $searchFields, $request, $itemStatus);
         
    }

    public function channelPartner_createDistributor(Request $request){

        $request->validate([
            'distributor_name' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
            'place_id' => 'required|integer'
        ]);

        $newUserRow = [
            'name' => $request['distributor_name'],
            'user_type' => 3,
            'email' => $request['email'],
            'phone' => $request['phone'],
            'password' => $request['password'],
            'user_token' => 'initial'
        ];

        $userId = DB::table('roadmate_users')
        ->insertGetId($newUserRow);

        $headerInfo = $this->task_getHeaderInfo($request);
        $channelPartnerId = $headerInfo->channelPartnerId;
        
        $newDistributorsRow = [
            'user_id' => $userId,
            'distributor_name' => $request['distributor_name'],
            'address' => $request['address'],
            'phone' => $request['phone'],
            'email' => $request['email'],
            'password' => $request['password'],
            'place_id' => $request['place_id'],
            'gst_number' => $request['gst_number'],
            'channel_partner_id' => $channelPartnerId
        ];


        DB::table('distributors')
        ->insert($newDistributorsRow);

        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully inserted data into the server.'
        ];

        return response()->json($responseArr);
        
    }

    public function channelPartner_updateDistributor(Request $request){

        $request->validate([
            'distributor_name' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
            'place_id' => 'required|integer',
            'update_item_key' => 'required',
            'update_item_value' => 'required'
        ]);

        
        $newDistributorsRow = [
            'distributor_name' => $request['distributor_name'],
            'address' => $request['address'],
            'phone' => $request['phone'],
            'email' => $request['email'],
            'password' => $request['password'],
            'place_id' => $request['place_id'],
            'gst_number' =>$request['gst_number']
        ];
        
        $updatedRows = DB::table('distributors')
        ->where($request['update_item_key'], $request['update_item_value'])
        ->update($newDistributorsRow);

        if ($updatedRows > 0) {

            $responseArr = [
                'status' => 'success',
                'error' => false,
                'message' => 'Successfully updated data in the server.'
            ];
        } else {

            $responseArr = [
                'status' => 'failed',
                'error' => true,
                'message' => 'Data is already upto date in the server'
            ];
        }

        return response()->json($responseArr);


    }

//tasks---------------------------------------------------------------------------------------- 


    public function task_createApiToken($userId, $userType, $secretKey){


        $payload = [
            'iss' => 'roadMate',  
            'iat' => time(),
            'userId' => $userId,   
            'userType' => $userType  
        ];
        
        $jwt = JWT::encode($payload, $secretKey, 'HS256');

        return $jwt;

    }

    public function task_queryTableData($table, $tableColumns, $searchFields, $request, $itemStatus = null ){

        //status
        if($itemStatus){

            $table->where($itemStatus['status_column'], $itemStatus['status_value']);
        }

        //search
        if($request->query('search')){

            if(count($searchFields) > 0){

                for ($i=0; $i < count($searchFields); $i++) { 
    
                    if($i == 0){

                        $table->where($searchFields[$i], 'LIKE', '%' . $request->query('search') . '%');
                    }
                    else{
                        $table->orWhere($searchFields[$i], 'LIKE', '%' . $request->query('search') . '%');
                    }
    
                }
            }
        }

        //filter
        $filterInfo = $this->task_getRequestFilterInfo($request, $tableColumns);
        $rowsCount = $this->task_getRequestRowsCount($request);

        $data = $table->orderBy($filterInfo['column'],$filterInfo['state'])
        ->paginate($rowsCount);  

        if($data->isNotEmpty()){

            return response()->json([
                "status" => "success",
                "error" => 0,
                "message" => "Successfully got table data.",
                "payload" => $data
            ]);
        }
        else{
            return $this->handleError('DATA_NOT_FOUND');
        }

    }

    public function task_getRequestFilterInfo($request, $tableColumns){

        if(($request->query('filter_column'))&&($request->query('filter_state'))){

            $validFilterStates = ['asc','desc'];

            $isFilterColumnValid = $this->check_isFilterColumnValid($request->query('filter_column'), $tableColumns);
            $isFilterStateValid = in_array($request->query('filter_state'), $validFilterStates);

            $filterColumn = $this->task_getFilterColumn($request->query('filter_column'), $tableColumns);

            if($isFilterColumnValid && $isFilterStateValid){

                $filterInfo = [
                    'column' => $filterColumn,
                    'state' => $request->query('filter_state')
                ];
    
                return $filterInfo;
            }
            else{

                $filterInfo = [
                    'column' => 'id',
                    'state' => 'desc'
                ];
    
                return $filterInfo;
            }

        }
        else{

            $filterInfo = [
                'column' => 'id',
                'state' => 'desc'
            ];

            return $filterInfo;
        }
    }

    public function task_getRequestRowsCount($request){

        $DEFAULT_TABLE_ROW_COUNT = 10;

        if($request->query('rows_count')){

            return $request->query('rows_count');
        }
        else{

            return $DEFAULT_TABLE_ROW_COUNT;
        }

    }

    public function check_isFilterColumnValid($filterColumn, $columnList){

        
        foreach ($columnList as $column) {

            $columnName = explode(' as ',$column);

            if(count($columnName) > 1){

                if($filterColumn == $columnName[1]){
                    return true;
                }
            }
            else{

                $columnName = explode('.',$column);

                if($filterColumn == $columnName[1]){
    
                    return true;
                }
            }

        }

        return false;

    }

    public function task_getFilterColumn($filterColumn, $columnList){

        
        foreach ($columnList as $column) {

            $columnName = explode(' as ',$column);

            if(count($columnName) > 1){

                if($filterColumn == $columnName[1]){
                    return $columnName[0];
                }
            }
            else{

                $columnName = explode('.',$column);

                if($filterColumn == $columnName[1]){
    
                    return $column;
                }
            }

        }

    }

    public function task_getHeaderInfo($request){

        $headerValue = $request->header('user-token');

        $appSecret = config('app.app_secret');

        return JWT::decode($headerValue, new Key($appSecret, 'HS256'));
    }

    public function handleError($errorName){

        $errorCodes = config('app.error_codes');

        if(isset($errorCodes[$errorName])){

            $responseArr = [
                'status' => 'failed',
                'error' => true, 
                'error_code' => $errorCodes[$errorName]['code'],
                'message' => $errorCodes[$errorName]['message']
            ];

            return response()->json($responseArr);

        }
        else{

            $responseArr = [
                'status' => 'failed',
                'error' => true, 
                'error_code' => $errorCodes['UNKNOWN_ERROR']['code'],
                'message' => $errorCodes['UNKNOWN_ERROR']['message']
            ];

            return response()->json($responseArr);
        }

    }
    

}

