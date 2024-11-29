<?php

namespace App\Http\Controllers\Admin\Distributor;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\DB;

class DistributorController extends Controller
{

    public function testFun(Request $request){

        $newDistributorsRow = [
            'distributor_name' => 'test distributor2',
            'address' => 'test address2',
            'phone' => 9098767899,
            'email' => 'tes2t@email.com',
            'place_id' => 1
        ];

        DB::table('distributors')
        ->insert($newDistributorsRow);

        $responseArr = [
            'status' => 'success'
        ];

        return response()->json($responseArr);

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

    public function fetchDistributorTableData(Request $request){

        $tableColumns = ['distributors.id as distributor_id', 'distributors.distributor_name', 'distributors.address', 'distributors.phone'];
        $searchFields = ['distributors.id','distributors.distributor_name'];
         
        $table = DB::table('distributors')
        ->select( 'distributors.id as distributor_id', 'distributors.distributor_name', 'distributors.address', 'distributors.phone');

        return $this->task_queryTableData($table, $tableColumns, $searchFields, $request);
         
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

        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully got data from the server.',
            'payload' => $data
        ];

        return response()->json($responseArr);

    }

    public function updateDistributorFormSubmit(Request $request){

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
            'gst_number' =>$request['gst_number']
        ];
        
        // DB::table('distributors')
        // ->insert($newDistributorsRow);

        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully updated data in the server.',
            'payload' => $newDistributorsRow
        ];

        return response()->json($responseArr);


    }


//--------------------tasks------------------ 


    public function task_queryTableData($table, $tableColumns, $searchFields, $request){



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



        $filterInfo = $this->task_getRequestFilterInfo($request, $tableColumns);
        $rowsCount = $this->task_getRequestRowsCount($request);


        $data = $table->orderBy($filterInfo['column'],$filterInfo['state'])
        ->paginate($rowsCount);  

        // return response()->json([
        //     "status" => "success",
        //     "error" => 0,
        //     "message" => "Successfully got table data.",
        //     // "payload" => $data
        // ]); 

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

    
}

