<?php

namespace App\Http\Controllers\Admin\ChannelPartner;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;


class ChannelPartnerController extends Controller
{

    public function admin_createChannelPartner(Request $request){

        $request->validate([
            'channel_partner_name' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
            'place_id' => 'required|integer',
            'gst_number' => 'nullable|string'
        ]);

        $newUserRow = [
            'name' => $request['channel_partner_name'],
            'user_type' => 4,
            'email' => $request['email'],
            'phone' => $request['phone'],
            'password' => $request['password'],
            'user_token' => 'initial'
        ];

        $userId = DB::table('roadmate_users')
        ->insertGetId($newUserRow);

        $newChannelPartnersRow = [
            'user_id' => $userId,
            'channel_partner_name' => $request['channel_partner_name'],
            'address' => $request['address'],
            'phone' => $request['phone'],
            'email' => $request['email'],
            'password' => $request['password'], 
            'place_id' => $request['place_id'],
            'gst_number' => $request['gst_number'] ?? null
        ];

        DB::table('channel_partners')
        ->insert($newChannelPartnersRow);

        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully added channel partner in the database.'
        ];

        return response()->json($responseArr);

    }

    public function admin_fetchChannelPartnerTableData(Request $request){

        $tableColumns = ['channel_partners.id as channel_partner_id', 'channel_partners.channel_partner_name', 'channel_partners.address', 'channel_partners.phone'];
        $searchFields = ['channel_partners.id','channel_partners.channel_partner_name'];
        $itemStatus = ['status_column' => 'channel_partners.channel_partner_status', 'status_value' => 1];
         
        $table = DB::table('channel_partners')
        ->select( 'channel_partners.id as channel_partner_id', 'channel_partners.channel_partner_name', 'channel_partners.address', 'channel_partners.phone');

        return $this->task_queryTableData($table, $tableColumns, $searchFields, $request, $itemStatus);

    }

    public function admin_updateChannelPartner(Request $request){


        $request->validate([
            'channel_partner_name' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
            'place_id' => 'required|integer',
            'gst_number' => 'nullable|string'
        ]);

        $newChannelPartnersRow = [
            'channel_partner_name' => $request['channel_partner_name'],
            'address' => $request['address'],
            'phone' => $request['phone'],
            'email' => $request['email'],
            'password' => $request['password'], 
            'place_id' => $request['place_id'],
            'gst_number' => $request['gst_number'] ?? null
        ];

        $updatedRows = DB::table('channel_partners')
        ->where($request['update_item_key'], $request['update_item_value'])
        ->update($newChannelPartnersRow);


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

    public function admin_fetchChannelPartnerUpdateFormData(Request $request){

        $request->validate([
            'item_key' => 'required',
            'item_value' => 'required'
        ]);

        $data = DB::table('channel_partners')
        ->leftJoin('places','channel_partners.place_id','=','places.id')
        ->leftJoin('districts','places.district_id','=','districts.id')
        ->leftJoin('states','districts.state_id','=','states.id')
        ->where($request['item_key'],$request['item_value'])
        ->select(
            'channel_partners.channel_partner_name',
            'channel_partners.address',
            'channel_partners.email',
            'channel_partners.phone',
            'channel_partners.password',
            'channel_partners.gst_number',
            'channel_partners.place_id',
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
                'status' => 'success',
                'message' => 'Failed to get data from the server.',
                'payload' => $data
            ];
        }

        return response()->json($responseArr);
        
    }

    public function channelPartner_fetchInfo(Request $request){

        $headerInfo = $this->task_getHeaderInfo($request);
        $channelPartnerId = $headerInfo->channelPartnerId;

        $channelPartnerInfo = DB::table('channel_partners')
        ->where('id', $channelPartnerId)
        ->select([
            'channel_partner_name', 'email', 'phone', 'password', 'address'
        ])
        ->get()
        ->first();

        if($channelPartnerInfo){

            $responseArr = [
                'status' => 'success',
                'message' => 'Succfully got data form the server.',
                'payload' => $channelPartnerInfo
            ];

            return response()->json($responseArr);

        }
        else{

            $responseArr = [
                'status' => 'failed',
                'message' => 'Failed to fetch data form the server.'
            ];
            return response()->json($responseArr);
        }



    }

    public function admin_deleteChannelPartner(Request $request){

        $request->validate([
            'item_key' => 'required',
            'item_value' => 'required'
        ]);

        $IN_ACTIVE_STATUS = 0;
        $newChannelPartnerRow = [
            'channel_partner_status' => $IN_ACTIVE_STATUS
        ];

        $updatedRows = DB::table('channel_partners')
        ->where($request['item_key'],$request['item_value'])
        ->update($newChannelPartnerRow);


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




