<?php

namespace App\Http\Controllers\Admin\Product;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ProductController extends Controller
{


    public function admin_fetchProductUpdateFormData(Request $request){

        $request->validate([
            'product_id' => 'required'
        ]);

        $productData = DB::table('products')
        ->select(['id as product_id', 'product_name', 'description', 'category_id', 'sub_category_id', 'brand_id', 'distributor_id', 'hsn_code_id'])
        ->where('id', $request['product_id'])
        ->get();

        $variantData = DB::table('product_variants')
        ->select(['id as variant_id', 'product_id', 'unit_id', 'unit_quantity', 'color_variant_id', 'stock_quantity', 'purchase_price', 'mrp', 'b2c_selling_price', 'b2b_selling_price'])
        ->where('product_id', $request['product_id'])
        ->get();



        if($productData->isNotEmpty() && $variantData->isNotEmpty()){

            $data = [
                'product_data' => $productData->first(),
                'variant_data' => $variantData
            ];

            $responseArr  = [
                'status' => 'success',
                'message' => 'Successfully got data from the server.',
                'payload' => $data
            ];
    
            return response()->json($responseArr);
        }
        else{

            $responseArr  = [
                'status' => 'failed',
                'message' => 'Failed to fetch data from the server'
            ];
            
            return response()->json($responseArr);
        }


    }

    public function admin_fetchProductTableData(Request $request){

        $tableColumns = ['products.id as product_id', 'products.product_name', 'brands.brand_name', 'distributors.distributor_name', 'categories.category_name' ];
        $searchFields = ['products.id'];

        $table = DB::table('products')
        ->leftJoin('distributors', 'products.distributor_id', 'distributors.id')
        ->leftJoin('brands', 'products.brand_id', 'brands.id')
        ->leftJoin('categories', 'products.category_id', 'categories.id')
        ->select($tableColumns);

        return $this->task_queryTableData($table, $tableColumns, $searchFields, $request);
    
    }

    public function admin_fetchProductDetailsTableData(Request $request){

        $tableColumns = ['id as variant_id', 'product_id', 'purchase_price', 'mrp', 'b2b_selling_price', 'b2c_selling_price', 'stock_quantity as stock'];
        $searchFields = ['products.id'];

        $table = DB::table('product_variants')
        ->where($request['item_key'], $request['item_value'])
        ->select($tableColumns);

        return $this->task_queryTableData($table, $tableColumns, $searchFields, $request);
    
    }

    public function admin_createProduct(Request $request){

        $newProductRow =[
            'product_name' => $request['product_name'],
            'description' => $request['description'],
            'category_id' => $request['category_id'],
            'sub_category_id' => $request['sub_category_id'],
            'brand_id' => $request['brand_id'],
            'distributor_id' => $request['distributor_id'],
            'hsn_code_id' => $request['hsn_code_id'],
        ];


        $productId  = DB::table('products')
        ->insertGetId($newProductRow);

        $variants = $this->task_getVariants($request);

        $imageList = [];

        $newProductVariantRow = [];

        $testArr = [];
        foreach($variants as $variant){

            $newProductVariantRow = [
                'product_id' => $productId,
                'unit_id' => $variant['unit_id'],
                'unit_quantity' => $variant['unit_quantity'],
                'stock_quantity' => $variant['stock_quantity'],
                'purchase_price' => $variant['purchase_price'],
                'mrp' => $variant['mrp'],
                'b2c_selling_price' => $variant['b2c_selling_price'],
                'b2b_selling_price' => $variant['b2b_selling_price'],
                'b2b_status' => 1,
                'b2c_status' => 1,
                'approve_status' => 1
            ];

            array_push($testArr, $newProductVariantRow);

            $productVariantId = DB::table('product_variants')
            ->insertGetId($newProductVariantRow);

            $imageList = $this->task_uploadFiles($variant['variant_image'], 'img/products');

            foreach($imageList as $image){

                $newVariantImageRow = [
                    'product_variant_id' => $productVariantId,
                    'image' => $image,
                    'product_image_status' =>  1
                ];

                DB::table('product_images')
                ->insert($newVariantImageRow);
            }

        }


        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully added product into the database.',
            'payload' => $testArr
        ];

        return response()->json($responseArr);
    
    }

    public function admin_updateProduct(Request $request){

        $newProductRow =[
            'product_name' => $request['product_name'],
            'description' => $request['description'],
            'category_id' => $request['category_id'],
            'sub_category_id' => $request['sub_category_id'],
            'brand_id' => $request['brand_id'],
            'distributor_id' => $request['distributor_id'],
            'hsn_code_id' => $request['hsn_code_id']
        ];

        DB::table('products')
        ->where('id', $request['product_id'])
        ->update($newProductRow);

        $variants = $this->task_getVariants($request);

        $imageList = [];

        $newProductVariantRow = [];

        $testArr = [];

        foreach($variants as $key => $variant){

            $variantId = $key;

            $newProductVariantRow = [
                'product_id' => $request['product_id'],
                'unit_id' => $variant['unit_id'],
                'unit_quantity' => $variant['unit_quantity'],
                'stock_quantity' => $variant['stock_quantity'],
                'purchase_price' => $variant['purchase_price'],
                'mrp' => $variant['mrp'],
                'b2c_selling_price' => $variant['b2c_selling_price'],
                'b2b_selling_price' => $variant['b2b_selling_price']
            ];

            DB::table('product_variants')
            ->where('id', $variantId)
            ->update($newProductVariantRow);

            if(array_key_exists('variant_image', $variant)){

                array_push($testArr, $variant['variant_image']);

                $imageList = $this->task_uploadFiles($variant['variant_image'], 'img/products');

                foreach($imageList as $image){
    
                    $newVariantImageRow = [
                        'product_variant_id' => $variantId,
                        'image' => $image,
                        'product_image_status' =>  1
                    ];
    
                    DB::table('product_images')
                    ->where('product_variant_id', $variantId)
                    ->update($newVariantImageRow);
                }
            }



        }

        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully updated product in the database.'
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

    public function admin_fetchSubCategoryFilterData(Request $request){


        $filterData = DB::table('sub_categories')
        ->select('sub_categories.id as filter_value', 'sub_categories.sub_category_name as filter_display_value')
        ->where($request['item_key'], $request['item_value'])
        ->where('sub_category_status',1)
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

    public function admin_fetchHsnCodeFilterData(Request $request){


        $filterData = DB::table('hsn_codes')
        ->select('hsn_codes.id as filter_value', 'hsn_codes.hsn_code as filter_display_value')
        ->where('hsn_code_status',1)
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

    public function admin_unitFilterData(Request $request){

        $filterData = DB::table('units')
        ->select('units.id as filter_value', 'units.unit_name as filter_display_value')
        ->where('unit_status',1)
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

    public function distributor_fetchProductTableData(Request $request){

        $tableColumns = ['products.id as product_id', 'products.product_name', 'brands.brand_name', 'distributors.distributor_name', 'categories.category_name' ];
        $searchFields = ['products.id'];

        $table = DB::table('products')
        ->leftJoin('distributors', 'products.distributor_id', 'distributors.id')
        ->leftJoin('brands', 'products.brand_id', 'brands.id')
        ->leftJoin('categories', 'products.category_id', 'categories.id')
        ->select($tableColumns);

        return $this->task_queryTableData($table, $tableColumns, $searchFields, $request);
    
    }

    public function distributor_createProduct(Request $request){

        $headerInfo = $this->task_getHeaderInfo($request);
        $distributorId = $headerInfo->distributorId;

        $newProductRow =[
            'product_name' => $request['product_name'],
            'description' => $request['description'],
            'category_id' => $request['category_id'],
            'sub_category_id' => $request['sub_category_id'],
            'brand_id' => $request['brand_id'],
            'distributor_id' => $distributorId,
            'hsn_code_id' => $request['hsn_code_id'],
        ];


        $productId  = DB::table('products')
        ->insertGetId($newProductRow);

        $variants = $this->task_getVariants($request);

        $imageList = [];

        $newProductVariantRow = [];

        $testArr = [];
        foreach($variants as $variant){

            $newProductVariantRow = [
                'product_id' => $productId,
                'unit_id' => $variant['unit_id'],
                'unit_quantity' => $variant['unit_quantity'],
                'stock_quantity' => $variant['stock_quantity'],
                'purchase_price' => $variant['purchase_price'],
                'mrp' => $variant['mrp'],
                'b2c_selling_price' => $variant['b2c_selling_price'],
                'b2b_selling_price' => $variant['b2b_selling_price'],
                'b2b_status' => 1,
                'b2c_status' => 1,
                'approve_status' => 1
            ];

            array_push($testArr, $newProductVariantRow);

            $productVariantId = DB::table('product_variants')
            ->insertGetId($newProductVariantRow);

            $imageList = $this->task_uploadFiles($variant['variant_image'], 'img/products');

            foreach($imageList as $image){

                $newVariantImageRow = [
                    'product_variant_id' => $productVariantId,
                    'image' => $image,
                    'product_image_status' =>  1
                ];

                DB::table('product_images')
                ->insert($newVariantImageRow);
            }

        }


        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully added product into the database.'
        ];

        return response()->json($responseArr);
    
    }

    public function distributor_updateProduct(Request $request){

        $headerInfo = $this->task_getHeaderInfo($request);
        $distributorId = $headerInfo->distributorId;

        $newProductRow =[
            'product_name' => $request['product_name'],
            'description' => $request['description'],
            'category_id' => $request['category_id'],
            'sub_category_id' => $request['sub_category_id'],
            'brand_id' => $request['brand_id'],
            'distributor_id' => $distributorId,
            'hsn_code_id' => $request['hsn_code_id']
        ];

        DB::table('products')
        ->where('id', $request['product_id'])
        ->update($newProductRow);

        $variants = $this->task_getVariants($request);

        $imageList = [];

        $newProductVariantRow = [];

        $testArr = [];

        foreach($variants as $key => $variant){

            $variantId = $key;

            $newProductVariantRow = [
                'product_id' => $request['product_id'],
                'unit_id' => $variant['unit_id'],
                'unit_quantity' => $variant['unit_quantity'],
                'stock_quantity' => $variant['stock_quantity'],
                'purchase_price' => $variant['purchase_price'],
                'mrp' => $variant['mrp'],
                'b2c_selling_price' => $variant['b2c_selling_price'],
                'b2b_selling_price' => $variant['b2b_selling_price']
            ];

            DB::table('product_variants')
            ->where('id', $variantId)
            ->update($newProductVariantRow);

            if(array_key_exists('variant_image', $variant)){

                array_push($testArr, $variant['variant_image']);

                $imageList = $this->task_uploadFiles($variant['variant_image'], 'img/products');

                foreach($imageList as $image){
    
                    $newVariantImageRow = [
                        'product_variant_id' => $variantId,
                        'image' => $image,
                        'product_image_status' =>  1
                    ];
    
                    DB::table('product_images')
                    ->where('product_variant_id', $variantId)
                    ->update($newVariantImageRow);
                }
            }



        }

        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully updated product in the database.'
        ];

        return response()->json($responseArr);
    
    }

    public function distributor_fetchBrandTableData(Request $request){

        $tableColumns = ['brands.id as brand_id', 'brands.brand_name'];
        $searchFields = ['brands.id','brands.brand_name'];

        $table = DB::table('brands')
        ->select($tableColumns)
        ->where('brand_status',1);

        return $this->task_queryTableData($table, $tableColumns, $searchFields, $request);
    }

    public function distributor_deleteBrand(Request $request){
        
        $request->validate([
            'item_key' => 'required',
            'item_value' => 'required'
        ]);

        $IN_ACTIVE_STATUS = 0;
        $newBrandsRow = [
            'brand_status' => $IN_ACTIVE_STATUS
        ];

        $updatedRows = DB::table('brands')
        ->where($request['item_key'],$request['item_value'])
        ->update($newBrandsRow);


        if($updatedRows > 0){

            $responseArr = [
                'status' => 'success',
                'error' => false,
                'message' => 'Successfully deleted the brand'
            ];
        }
        else{

            $responseArr = [
                'status' => 'failed',
                'error' => true,
                'message' => 'Failed to delete brand.'
            ];
        }

        return response()->json($responseArr);
        
    }

    public function distributor_createBrand(Request $request){

        $request->validate([
            'brand_name' => 'required|string'
        ]);

        $newBrandsRow = [
            'brand_name' => $request['brand_name']
        ];

        DB::table('brands')
        ->insert($newBrandsRow);

        $responseArr = [
            'status' => 'success',
            'message' => 'Successfully added executive in the database.'
        ];

        return response()->json($responseArr);
    }

    public function distributor_fetchBrandUpdateFormData(Request $request){

        $request->validate([
            'item_key' => 'required',
            'item_value' => 'required'
        ]);


        $data = DB::table('brands')
        ->where($request['item_key'],$request['item_value'])
        ->select(
            'brands.brand_name'
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

    public function distributor_updateBrand(Request $request){

        $request->validate([
            'brand_name' => 'required|string',
            'update_item_key' => 'required',
            'update_item_value' => 'required'
        ]);

        $newBrandsRow = [
            'brand_name' => $request['brand_name']
        ];

        $updatedRows = DB::table('brands')
        ->where($request['update_item_key'], $request['update_item_value'])
        ->update($newBrandsRow);


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

    public function task_getHeaderInfo($request){

        $headerValue = $request->header('user-token');

        $appSecret = config('app.app_secret');

        return JWT::decode($headerValue, new Key($appSecret, 'HS256'));
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