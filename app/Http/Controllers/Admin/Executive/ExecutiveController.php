<?php

namespace App\Http\Controllers\Admin\Executive;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ExecutiveController extends Controller
{

    public function testFunExec(Request $request){

        return response("hello ExecutiveController");
    }

    public function fetchDistributerFilterData(Request $request){

        $data = DB::table('distributors')
        ->select('id as filter_value', 'distributor_name as filter_display_value')
        ->get();

        if($data->isNotEmpty()){

            $responseArr = [
                'status' => 'success',
                'message' => 'Successfully got data from the server.',
                'payload' => $data
            ];
    

        }
        else{

            $responseArr = [
                'status' => 'failed',
                'message' => 'Failed to get data from the server.'
            ];
    
        }

        return response()->json($responseArr);


    }

    public function admin_createExecutive(Request $request){

        
        $request->validate([
            'executive_name' => 'required|string',
            'email' => 'required|email',
            'address' => 'required|string',
            'phone' => 'required|string',
            'place_id' => 'required|integer',
            'distributor_id' => 'required|integer',
        ]);


        $newExecutivesRow = [
            'executive_name' => $request['executive_name'],
            'email' => $request['email'],
            'address' => $request['address'],
            'phone' => $request['phone'],
            'place_id' => $request['place_id'],
            'distributor_id' => $request['distributor_id']
        ];

        DB::table('executives')
        ->insert($newExecutivesRow);

        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully added executive in the database.'
        ];

        return response()->json($responseArr);


    }

    public function admin_fetchExecutiveTableData(Request $request){

        $tableColumns = ['executives.id as executive_id', 'executives.executive_name', 'executives.address', 'executives.phone'];
        $searchFields = ['executives.id','executives.executive_name'];
        $itemStatus = ['status_column' => 'executives.executive_status', 'status_value' => 1];
         
        $table = DB::table('executives')
        ->select( 'executives.id as executive_id', 'executives.executive_name', 'executives.address', 'executives.phone');

        return $this->task_queryTableData($table, $tableColumns, $searchFields, $request, $itemStatus);

    }

    public function admin_fetchExecutiveUpdateFormData(Request $request){

        $request->validate([
            'item_key' => 'required',
            'item_value' => 'required'
        ]);


        $data = DB::table('executives')
        ->leftJoin('places','executives.place_id','=','places.id')
        ->leftJoin('districts','places.district_id','=','districts.id')
        ->leftJoin('states','districts.state_id','=','states.id')
        ->where($request['item_key'],$request['item_value'])
        ->select(
            'executives.executive_name',
            'executives.address',
            'executives.email',
            'executives.phone',
            'executives.distributor_id',
            'executives.place_id',
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

    public function admin_updateExecutive(Request $request){

        $request->validate([
            'executive_name' => 'required|string',
            'email' => 'required|email',
            'address' => 'required|string',
            'phone' => 'required|string',
            'place_id' => 'required|integer',
            'distributor_id' => 'required|integer',
            'update_item_key' => 'required',
            'update_item_value' => 'required'
        ]);

        $newExecutivesRow = [
            'executive_name' => $request['executive_name'],
            'email' => $request['email'],
            'address' => $request['address'],
            'phone' => $request['phone'],
            'place_id' => $request['place_id'],
            'distributor_id' => $request['distributor_id']
        ];

        $updatedRows = DB::table('executives')
        ->where($request['update_item_key'], $request['update_item_value'])
        ->update($newExecutivesRow);


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

    public function admin_deleteExecutive(Request $request){

        $request->validate([
            'item_key' => 'required',
            'item_value' => 'required'
        ]);

        $IN_ACTIVE_STATUS = 0;
        $newExecutivesRow = [
            'executive_status' => $IN_ACTIVE_STATUS
        ];

        $updatedRows = DB::table("executives")
        ->where($request['item_key'], $request['item_value'])
        ->update($newExecutivesRow);

        if ($updatedRows > 0) {

            $responseArr = [
                'status' => 'success',
                'error' => false,
                'message' => 'Successfully deleted the executive.'
            ];
        } else {

            $responseArr = [
                'status' => 'failed',
                'error' => true,
                'message' => 'Data is already deleted'
            ];
        }

        return response()->json($responseArr);

    }


    public function distributor_fetchExecutiveTableData(Request $request){

        $tableColumns = ['executives.id as executive_id', 'executives.executive_name', 'executives.address', 'executives.phone'];
        $searchFields = ['executives.id','executives.executive_name'];
        $itemStatus = ['status_column' => 'executives.executive_status', 'status_value' => 1];
         
        $table = DB::table('executives')
        ->select( 'executives.id as executive_id', 'executives.executive_name', 'executives.address', 'executives.phone');

        return $this->task_queryTableData($table, $tableColumns, $searchFields, $request, $itemStatus);

    }

    public function distributor_createExecutive(Request $request){

        
        $request->validate([
            'executive_name' => 'required|string',
            'email' => 'required|email',
            'address' => 'required|string',
            'phone' => 'required|string',
            'place_id' => 'required|integer'
        ]);

        $headerInfo = $this->task_getHeaderInfo($request);
        $distributorId = $headerInfo->distributorId;

        $newExecutivesRow = [
            'executive_name' => $request['executive_name'],
            'email' => $request['email'],
            'address' => $request['address'],
            'phone' => $request['phone'],
            'place_id' => $request['place_id'],
            'distributor_id' => $distributorId
        ];

        DB::table('executives')
        ->insert($newExecutivesRow);

        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully added executive in the database.'
        ];

        return response()->json($responseArr);


    }

    public function distributor_updateExecutive(Request $request){

        $request->validate([
            'executive_name' => 'required|string',
            'email' => 'required|email',
            'address' => 'required|string',
            'phone' => 'required|string',
            'place_id' => 'required|integer',
            'update_item_key' => 'required',
            'update_item_value' => 'required'
        ]);

        $headerInfo = $this->task_getHeaderInfo($request);
        $distributorId = $headerInfo->distributorId;

        $newExecutivesRow = [
            'executive_name' => $request['executive_name'],
            'email' => $request['email'],
            'address' => $request['address'],
            'phone' => $request['phone'],
            'place_id' => $request['place_id'],
            'distributor_id' => $distributorId
        ];


        $updatedRows = DB::table('executives')
        ->where($request['update_item_key'], $request['update_item_value'])
        ->update($newExecutivesRow);


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

