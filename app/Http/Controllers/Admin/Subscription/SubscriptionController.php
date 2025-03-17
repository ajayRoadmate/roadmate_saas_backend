<?php

namespace App\Http\Controllers\Admin\Subscription;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{

    public function admin_createSubscription(Request $request){

        $request->validate([
            'subscription_name' => 'required|string',
            'description' => 'required|string',
            'validity' => 'required|integer',
            'subscription_price' => 'required|numeric|min:0',
        ]);

        $newSaasSubscriptionsRow = [
            'subscription_name' => $request['subscription_name'],
            'description' => $request['description'],
            'validity' => $request['validity'],
            'subscription_price' => $request['subscription_price']
        ];

        DB::table('saas_subscriptions')
        ->insert($newSaasSubscriptionsRow);

        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully added subscription in the database.'
        ];

        return response()->json($responseArr);
    }

    public function admin_fetchSubscriptionTableData(Request $request){

        $tableColumns = ['saas_subscriptions.id as subscription_id', 'saas_subscriptions.subscription_name', 'saas_subscriptions.description', 'saas_subscriptions.validity', 'saas_subscriptions.subscription_price'];
        $searchFields = ['saas_subscriptions.id','saas_subscriptions.subscription_name'];
        $itemStatus = ['status_column' => 'saas_subscriptions.subscription_status', 'status_value' => 1];
         
        $table = DB::table('saas_subscriptions')
        ->select( 'saas_subscriptions.id as subscription_id', 'saas_subscriptions.subscription_name', 'saas_subscriptions.description', 'saas_subscriptions.validity', 'saas_subscriptions.subscription_price');

        return $this->task_queryTableData($table, $tableColumns, $searchFields, $request, $itemStatus);

    }

    public function admin_fetchSubscriptionUpdateFormData(Request $request){

        $request->validate([
            'item_key' => 'required',
            'item_value' => 'required'
        ]);

        $data = DB::table('saas_subscriptions')
        ->where($request['item_key'],$request['item_value'])
        ->select(
            'saas_subscriptions.subscription_name',
            'saas_subscriptions.description',
            'saas_subscriptions.validity',
            'saas_subscriptions.subscription_price'
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

    public function admin_updateSubscription(Request $request){

        $request->validate([
            'subscription_name' => 'required|string',
            'description' => 'required|string',
            'validity' => 'required|integer',
            'subscription_price' => 'required|numeric|min:0',
            'update_item_key' => 'required',
            'update_item_value' => 'required'
        ]);

        $newSaasSubscriptionsRow = [
            'subscription_name' => $request['subscription_name'],
            'description' => $request['description'],
            'validity' => $request['validity'],
            'subscription_price' => $request['subscription_price']
        ];


        $updatedRows = DB::table('saas_subscriptions')
        ->where($request['update_item_key'], $request['update_item_value'])
        ->update($newSaasSubscriptionsRow);


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

    public function admin_deleteSubscription(Request $request){

        $request->validate([
            'item_key' => 'required',
            'item_value' => 'required'
        ]);

        $IN_ACTIVE_STATUS = 0;
        $newSubscriptionRow = [
            'subscription_status' => $IN_ACTIVE_STATUS
        ];

        $updatedRows = DB::table("saas_subscriptions")
        ->where($request['item_key'], $request['item_value'])
        ->update($newSubscriptionRow);

        if ($updatedRows > 0) {

            $responseArr = [
                'status' => 'success',
                'error' => false,
                'message' => 'Successfully deleted the subscription.'
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