<?php

namespace App\Http\Controllers\Admin\Order;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{

    public function admin_fetchOrderDetailsTableData(Request $request){

        $request->validate([
            'order_id' => 'required'
        ]);
         
        $tableColumns = ['b2b_order_details.id as order_details_id', 'b2b_order_details.order_master_id as order_id', 'products.product_name', 'product_variants.b2b_selling_price', 'product_variants.mrp', 'product_variants.purchase_price'];
        $searchFields = ['b2b_order_details.id'];

        $table = DB::table('b2b_order_details')
        ->leftJoin('products','b2b_order_details.product_id','=','products.id')
        ->leftJoin('product_variants', 'b2b_order_details.product_variant_id', '=', 'product_variants.id')
        ->select($tableColumns);

        return $this->task_queryTableData($table, $tableColumns, $searchFields, $request);
    
    }

    public function admin_fetchAllOrderTableData(Request $request){
         
        $tableColumns = ['b2b_orders.id as order_id', 'b2b_orders.order_date', 'b2b_orders.b2b_order_status as order_status', 'b2b_orders.total_amount', 'b2b_orders.discount', 'shops.shop_name', 'distributors.distributor_name', 'executives.executive_name'];
        $searchFields = ['b2b_orders.id'];

        $table = DB::table('b2b_orders')
        ->leftJoin('shops', 'b2b_orders.shop_id', '=', 'shops.id')
        ->leftJoin('distributors', 'b2b_orders.distributor_id', '=', 'distributors.id')
        ->leftJoin('executives','b2b_orders.executive_id', '=', 'executives.id')
        ->select(
            'b2b_orders.id as order_id',
            'b2b_orders.order_date',
            'b2b_orders.total_amount',
            'b2b_orders.discount',
            'shops.shop_name',
            'distributors.distributor_name',
            'executives.executive_name',
            DB::raw("
                CASE 
                    WHEN b2b_orders.b2b_order_status = 0 THEN 'Pending'
                    WHEN b2b_orders.b2b_order_status = 1 THEN 'Confirmed'
                    WHEN b2b_orders.b2b_order_status = 2 THEN 'Shipped'
                    WHEN b2b_orders.b2b_order_status = 3 THEN 'Delivered'
                    WHEN b2b_orders.b2b_order_status = 4 THEN 'Canceled'
                    WHEN b2b_orders.b2b_order_status = 5 THEN 'Returned'
                    ELSE 'Inactive'
                END AS order_status
            ")
        )
        ->where('b2b_orders.b2b_order_status', '!=',  4);

        //0-pending,1-confirmed , 2-shipped,3-delivered ,4- cancel,5 -return

        return $this->task_queryTableData($table, $tableColumns, $searchFields, $request);
    
    }

    public function admin_fetchOrderUpdateFormData(Request $request){

        $request->validate([
            'item_key' => 'required',
            'item_value' => 'required'
        ]);


        $data = DB::table('b2b_orders')
        ->where($request['item_key'],$request['item_value'])
        ->select(
            'b2b_orders.discount',
            'b2b_orders.b2b_order_status'
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
                'message' => 'Failed to get data from the server.'
            ];
        }

        return response()->json($responseArr);


    }

    public function admin_fetchOrderStatusFilterData(Request $request){

        //0-pending,1-confirmed , 2-shipped,3-delivered ,4- cancel,5 -return

        $data = [
            ['filter_value' => 0, 'filter_display_value' => 'pending'],
            ['filter_value' => 1, 'filter_display_value' => 'confirmed'],
            ['filter_value' => 2, 'filter_display_value' => 'shipped'],
            ['filter_value' => 3, 'filter_display_value' => 'delivered']
        ];

        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully got data from the server.',
            'payload' => $data
        ];

        return response()->json($responseArr);

    }

    public function admin_updateOrder(Request $request){

        $request->validate([
            'discount' => 'required|numeric',
            'b2b_order_status' => 'required|integer',
            'update_item_key' => 'required',
            'update_item_value' => 'required'
        ]);


        $newOrderRow = [
            'discount' => $request['discount'],
            'b2b_order_status' => $request['b2b_order_status']
        ];

        $updatedRows = DB::table('b2b_orders')
        ->where($request['update_item_key'], $request['update_item_value'])
        ->update($newOrderRow);


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

    public function admin_cancelOrder(Request $request){

        $request->validate([
            'item_key' => 'required',
            'item_value' => 'required'
        ]);

        $orderId = $request['item_value'];

        $orderMasterCancelStatus = 4;

        $masterTableUpdate = DB::table('b2b_orders')
        ->where('id',$orderId)
        ->update(['b2b_order_status'=> $orderMasterCancelStatus]);
         
        $orderDetailsTableUpdate = DB::table('b2b_order_details')
        ->where('order_master_id',$orderId)
        ->update(['b2b_order_details_status' => 0]);
         
        if ( ($masterTableUpdate > 0) && ($orderDetailsTableUpdate > 0) ) {

            $responseArr = [
                'status' => 'success',
                'error' => false,
                'message' => 'Successfully deleted data in the server.'
            ];
        }
        else{

            $responseArr = [
                'status' => 'failed',
                'error' => true,
                'message' => 'There was and error while deleting data form the server.'
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
        $filterInfo = $this->task_getRequestFilterInfo($request, $table, $tableColumns);
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

    public function task_getRequestFilterInfo($request, $table, $tableColumns){

        $tableName = $table->from;

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
                    'column' => $tableName.'.id',
                    'state' => 'desc'
                ];
    
                return $filterInfo;
            }

        }
        else{

            $filterInfo = [
                'column' => $tableName.'.id',
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

    //order tasks--------------------------------------------------------------------

    public function mapOrderStatus($orderStatus){

        if($orderStatus == 1){
            return "Active";
        }
        else{
            return "Inactive";
        }

    }


}