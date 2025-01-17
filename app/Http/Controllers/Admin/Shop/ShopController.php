<?php

namespace App\Http\Controllers\Admin\Shop;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ShopController extends Controller
{

    public function admin_fetchShopTableData(Request $request){
         
        $tableColumns = ['shops.id as shop_id', 'shops.shop_name', 'shops.address', 'shops.phone_primary'];
        $searchFields = ['shops.id','shops.shop_name'];
        $itemStatus = ['status_column' => 'shops.shop_status', 'status_value' => 1];
         
        $table = DB::table('shops')
        ->select( 'shops.id as shop_id', 'shops.shop_name', 'shops.address', 'shops.phone_primary');
    
        return $this->task_queryTableData($table, $tableColumns, $searchFields, $request, $itemStatus);
    
    }

    public function distributor_fetchShopTableData(Request $request){

        $headerInfo = $this->task_getHeaderInfo($request);
        $distributorId = $headerInfo->distributorId;

        $tableColumns = ['shops_distributors.id as id', 'shops.id as shop_id', 'shops.shop_name', 'shops.address', 'shops.phone_primary'];
        $searchFields = ['shops_distributors.id','shops_distributors.shop_name'];
        $itemStatus = ['status_column' => 'shops.shop_status', 'status_value' => 1];
         
        $table = DB::table('shops_distributors')
        ->leftJoin('shops','shops_distributors.shop_id', '=', 'shops.id')
        ->where('shops_distributors.distributor_id', $distributorId)
        ->select($tableColumns);
    
        return $this->task_queryTableData($table, $tableColumns, $searchFields, $request);
        
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