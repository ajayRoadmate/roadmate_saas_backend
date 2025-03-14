<?php

namespace App\Http\Controllers\Admin\SharedModule;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;


class SharedModuleController extends Controller
{


    public function Login(Request $request){

        $userInfo = DB::table('roadmate_users')
        ->where('phone',$request['phone'])
        ->get()
        ->first();

        if($userInfo){

            $remotePassword = $userInfo->password;
            $userPassword = $request['password'];
    
            if($remotePassword == $userPassword){

                $userToken = $this->getUserToken($userInfo);

                if($userToken){

                    $this->storeUserToken($userToken,$userInfo->id);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Successfully loggedin',
                        'user_token' => $userToken
                    ]);

                }
                else{

                    return response()->json([
                        'status' => 'failed',
                        'message' => 'Failed to login, Could not generate user token.'
                    ]);
                }
    
            }
            else{
    
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Failed to login, Password is invalid.'
                ]);
            }
            
        }
        else{

            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to login, Phone number invalid.'
            ]);
        }

    }

    function storeUserToken($userToken,$userId){

        $userArr = [
            'user_token' => $userToken
        ];

        DB::table('roadmate_users')
        ->where('id',$userId)
        ->update($userArr);

    }

    function getUserToken($userInfo){

        if($userInfo->user_type == 1){

            $appSecret = config('app.app_secret');
        
            $payload = [
                'iss' => 'roadMate',  
                'iat' => time(),
                'userId' => $userInfo->id,   
                'userType' => $userInfo->user_type
            ];
            
            $userToken = JWT::encode($payload, $appSecret, 'HS256');
            return $userToken;

        }
        else if($userInfo->user_type == 2){

            $appSecret = config('app.app_secret');
        
            $payload = [
                'iss' => 'roadMate',  
                'iat' => time(),
                'userId' => $userInfo->id,   
                'userType' => $userInfo->user_type
            ];
            
            $userToken = JWT::encode($payload, $appSecret, 'HS256');
            return $userToken;

        }
        else if($userInfo->user_type == 3){

            $appSecret = config('app.app_secret');

            $distributorInfo = DB::table('distributors')
            ->where('user_id',$userInfo->id)
            ->get()
            ->first();
        
            $payload = [
                'iss' => 'roadMate',  
                'iat' => time(),
                'userId' => $userInfo->id,   
                'userType' => $userInfo->user_type,
                'distributorId' => $distributorInfo->id
            ];
            
            $userToken = JWT::encode($payload, $appSecret, 'HS256');
            return $userToken;

        }  
        else if($userInfo->user_type == 4){

            $appSecret = config('app.app_secret');

            $channelPartnerInfo = DB::table('channel_partners')
            ->where('user_id',$userInfo->id)
            ->get()
            ->first();
        
            $payload = [
                'iss' => 'roadMate',  
                'iat' => time(),
                'userId' => $userInfo->id,   
                'userType' => $userInfo->user_type,
                'channelPartnerId' => $channelPartnerInfo->id
            ];
            
            $userToken = JWT::encode($payload, $appSecret, 'HS256');
            return $userToken;

        }           
        else{
            return '';
        }
    }



    //tasks---------------------------------------------------------------------------------------- 


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




}




