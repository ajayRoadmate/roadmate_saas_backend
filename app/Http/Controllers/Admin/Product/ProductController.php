<?php

namespace App\Http\Controllers\Admin\Product;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{

    

    public function admin_fetchProductTableData(Request $request){

        $tableColumns = ['products.id as product_id'];
        $searchFields = ['products.id'];

        $table = DB::table('products')
        ->select(
            'products.id as product_id'
        );

        return $this->task_queryTableData($table, $tableColumns, $searchFields, $request);
    
    }

    public function admin_createProduct(Request $request){



                
        // $variants = $this->task_getVariants($request);

        // $imageList = [];

        // foreach($variants as $variant){

        //     $imageList = $this->task_uploadFiles($variant['variant_image'], 'images');

        //     foreach($imageList as $image){

        //         $newVariantImageRow = [
        //             'product_variant_id' => 10,
        //             'image' => $image,
        //             'product_image_status' =>  1
        //         ];

        //         DB::table('product_images')
        //         ->insert($newVariantImageRow);
        //     }

        // }


        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully added product into the database.'
        ];

        return response()->json($responseArr);
    
    }

    public function task_getVariants($request){

        $variants = [];
        foreach ($request->all() as $key => $value) {

            if (preg_match('/variant_(\d+)_(\w+)/', $key, $matches)) {
                $variantIndex = $matches[1]; 
                $fieldType = $matches[2];   
                $variants[$variantIndex][$fieldType] = $value;
            }
        }

        return $variants;
    }

    public function admin_fetchCategoryFilterData(Request $request){


        $filterData = DB::table('categories')
        ->select('categories.id as filter_value', 'categories.category_name as filter_display_value')
        ->where('category_status',1) 
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

    public function admin_fetchBrandFilterData(Request $request){


        $filterData = DB::table('brands')
        ->select('brands.id as filter_value', 'brands.brand_name as filter_display_value')
        ->where('brand_status',1)
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

    public function admin_fetchDistributorFilterData(Request $request){


        $filterData = DB::table('distributors')
        ->select('distributors.id as filter_value', 'distributors.distributor_name as filter_display_value')
        ->where('distributor_status',1)
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


    public function task_uploadFiles($fileInput, $storagePath){

        if(is_array($fileInput)){

            $files = $fileInput;
        }
        else{

            $files = [$fileInput];
        }

        $uploadedFiles = [];

        foreach ($fileInput as $file) {

            $extension = $file->getClientOriginalExtension();
            $uniqueFilename = time() . '_' . uniqid() . '.' . $extension;

            $file->move(public_path($storagePath), $uniqueFilename);
            array_push($uploadedFiles, $uniqueFilename);

        }

        return $uploadedFiles;

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