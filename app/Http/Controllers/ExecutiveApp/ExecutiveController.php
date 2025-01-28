<?php

namespace App\Http\Controllers\ExecutiveApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExecutiveController extends Controller
{

    public function excutiveLogin(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|integer'
        ]);

        $phoneNumber = $request->phone_number;

        $status = Task::checkExecutiveStatus($phoneNumber);

        if ($status === 'not_registered') {

            return handleError('PHONE_NUMBER_NOT_REGISTERED');
        } elseif ($status === 'inactive') {

            return handleError('NOT_ACTIVE_USER');
        } else {

            //check if the user is admin, then send static otp:5252 else dynamic otp
            if ($phoneNumber == config('app.admin.PHONE_NUMBER')) {

                $otp = config('app.admin.OTP');
            } else {
                $otp = rand(1000, 9999);
            }

            $rowsAffected = Task::updateOtp($phoneNumber, $otp);
            $isOtpSend = Task::sendOtp($phoneNumber, $otp);
            if ($isOtpSend) {

                return  handleSuccess('Successfully sent otp to the user');
            } else {

                return  handleError('OTP_NOT_SENT');
            }
        }
    }

    public function executiveOtpVerify(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|integer',
            'otp' => 'required|integer'
        ]);

        $otp = $request->otp;
        $phoneNumber = $request->phone_number;

        $executive = DB::table('executives')
            ->where('phone', $phoneNumber)
            ->where('otp', $otp)
            ->first();

        if ($executive) {

            $secretKey = config('app.app_secret');
            $executiveId = $executive->id;

            $apiToken = Task::createApiToken($executiveId, $secretKey);
            $isUserTokenSaved = Task::saveUserToken($phoneNumber, $apiToken);

            if ($isUserTokenSaved) {

                $executive = DB::table('executives')
                    ->leftjoin('distributors', 'executives.distributor_id', '=', 'distributors.id')
                    ->select(
                        'executives.id',
                        'executives.executive_name',
                        'executives.executive_status',
                        'distributors.id as distributor_id',
                        'distributors.distributor_name',
                        'distributors.distributor_status',
                        DB::raw('NULL as image')
                    )
                    ->where('executives.phone', $phoneNumber)
                    // ->where('executives.executive_status', 1)
                    ->get();

                $responseArr = [
                    'status' => 'success',
                    'error'  => false,
                    'message' => 'Successfully validated the OTP',
                    'apiToken' => $apiToken,
                    "payload" => $executive
                ];

                return response()->json($responseArr);
            } else {

                return handleError('TOKEN_NOT_SAVED');
            }
        } else {

            return handleError('OTP_INVALID');
        }
    }

    public function fetchDistributorShops(Request $request)
    {
        $request->validate([
            'distributor_id' => 'required|integer|min:1',
            'index' => 'required|integer|min:0',
            'executive_id' => 'nullable|integer|min:1',
            'search' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $distributorId = $request->distributor_id;
        $index = $request->index;
        $executive_id = $request->executive_id;
        $search = $request->search;
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        $offset = ($index * 20);
        $limit = 20;

        $query = Task::initializeShopQuery($distributorId);

        $query = Task::shopQueryFilter($query, $executive_id, $latitude, $longitude, $search);

        $shopArr = $query->clone()->offset($offset)
            ->limit($limit)
            ->get();

        if ($shopArr->isNotEmpty()) {

            $shopArr = $shopArr->map(function ($shop) {
                $latLong = explode(',', $shop->lat_long);
                $shop->latitude = $latLong[0] ?? null;
                $shop->longitude = $latLong[1] ?? null;
                unset($shop->lat_long);
                return $shop;
            });

            $totalShops = 0;
            $todayShops = 0;
            $today = Carbon::today();

            $totalShops = $query->count();
            $todayShops = $query->whereDate('shops_distributors.created_at', $today)->count();

            return response()->json([
                'status' => 'success',
                'error' => false,
                'totalshops' => $totalShops,
                'todayshops' => $todayShops,
                'payload' => $shopArr,
                'message' => 'Successfully got data from the server'
            ]);
        } else {

            return handleError('DATA_NOT_FOUND');
        }
    }

    public function searchShopByNumber(Request $request)
    {
        $request->validate([
            'distributor_id' => 'required|integer|min:1',
            'phone_number' => 'required|integer|digits:10',
        ]);

        $phoneNumber = $request->phone_number;
        $distributorId = $request->distributor_id;

        $shopArr = Task::getShopByPhoneNumber($phoneNumber);

        if ($shopArr->isNotEmpty()) {

            $isMapped = Task::checkIfShopMappedToDistributor($shopArr->first()->id, $distributorId);

            if ($isMapped) {

                foreach ($shopArr as $shop) {
                    $shop->is_mapped = true;
                }
                $message = 'Shop found and already mapped to a distributor.';
                return handleSuccess($message, $shopArr);
            } else {

                foreach ($shopArr as $shop) {
                    $shop->is_mapped = false;
                }

                $message = 'Shop found but not yet mapped to a distributor.';
                return handleSuccess($message, $shopArr);
            }
        } else {

            return handleError('DATA_NOT_FOUND');
        }
    }

    public function shopOnboarding(Request $request)
    {

        $request->validate([
            "shop_name" => "required|max:225",
            "description" => "required|max:255",
            "phone_number" => "required|integer|digits:10",
            "shop_open_time" => "required",
            "shop_close_time" => "required",
            "shop_type" => "required|integer",
            "place_id" => "required|integer",
            "address" => "required|max:225",
            "pincode" => "required|integer",
            "latitude" => "required|numeric",
            "longitude" => "required|numeric",
            "executive_id" => "required|integer",
            "distributor_id" => "required|integer",
            "image" => "required|image|mimes:jpeg,png,jpg,webp|max:5120", // max size 5MB
        ]);

        //check shop already onboarded
        $shopExists = Task::checkShopExists($request->phone_number);

        if ($shopExists) {
            return handleCustomError("Shop already onboarded");
        }

        //shop onboard on old server
        $response = Task::shopOnboardingOldServer($request);
        if ($response) {

            $shopOnboard = Task::shopOnboardingNewServer($request);

            if ($shopOnboard) {

                return handleSuccess('Successfully onboarded the shop.');
            } else {

                return handleCustomError("Failed to onboard new shop.");
            }
        } else {
            return handleCustomError("Failed to onboard new shop.");
        }
    }

    public function distributorShopMapping(Request $request)
    {
        $request->validate([
            'distributor_id' => 'required|integer|exists:distributors,id',
            'executive_id' => 'required|integer|exists:executives,id',
            'shop_id' => 'required|integer|exists:shops,id',
        ]);

        //check shop already mapped
        $isMapped = Task::checkIfShopMappedToDistributor($request->shop_id, $request->distributor_id);

        if ($isMapped) {

            return handleCustomError("Shop already mapped to the distributor.");
        } else {

            $newTableRow = [
                'distributor_id' => $request->distributor_id,
                'executive_id' => $request->executive_id,
                'shop_id' => $request->shop_id,

            ];
            $newId = DB::table('shops_distributors')->insertGetId($newTableRow);
            if ($newId) {

                return handleSuccess('Successfully mapped the shop.');
            } else {

                return handleCustomError("Failed to map shop.");
            }
        }
    }

    public function fetchDistributorProducts(Request $request)
    {
        $request->validate([
            "distributor_id" => "required|integer",
            "index" => "required|integer|min:0",
            "category_id" => "nullable|integer|min:1",
            "sub_category_id" => "nullable|integer|min:1",
            "brand_id" => "nullable|integer|min:1",
            "search" => "nullable|string"
        ]);
        $distributorId = $request->distributor_id;
        $categoryId = $request->category_id;
        $subCategoryId = $request->sub_category_id;
        $brandId = $request->brand_id;
        $search = $request->search;
        $index = $request->index;
        $offset = ($index * 20);
        $limit = 20;

        $query = Task::initializeProductQuery($distributorId);
        $query = Task::productQueryFilter($query, $categoryId, $subCategoryId, $brandId, $search);


        $productArr = $query
            ->offset($offset)
            ->limit($limit)
            ->get();

        $products = [];
        if ($productArr->isNotEmpty()) {
            foreach ($productArr as $product) {

                $product->images = Task::fetchProductImage($product->variant_id);
                $products[] = $product;
            }
        }
        return handleFetchResponse(collect($products));
    }

    public function executivePlaceorder(Request $request)
    {
        $request->validate([
            'distributor_id' => 'required|integer',
            'executive_id' => 'required|integer',
            'shop_id' => 'required|integer|exists:shops,id',
            'delivery_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.product_variant_id' => 'required|integer|exists:product_variants,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.purchase_price' => 'required|numeric|min:0',
            'products.*.mrp' => 'required|numeric|min:0',
            'products.*.selling_price' => 'required|numeric|min:0',

        ]);

        DB::beginTransaction();
        try {

            $orderId = Task::createB2BOrder($request);

            if (!$orderId) {
                return handleCustomError("Failed to place new order.");
            }

            $orderDetailsCreated = Task::createB2BOrderDetails($request, $orderId);

            if (!$orderDetailsCreated) {

                return handleCustomError("Failed to place new order.");
            }
            DB::commit();

            Task::deleteCartItem($request->shop_id);
            return response()->json([
                'status' => 'success',
                'error' => false,
                'order_id' => $orderId,
                'message' => 'Successfully placed the order.'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return handleCustomError("Failed to place new order.");
        }
    }

    public function fetchOrdersByExecutive(Request $request)
    {
        $request->validate([
            "executive_id" => "required|integer",
            "index" => "required|integer|min:0",

        ]);

        $executiveId = $request->executive_id;
        $index = $request->index;
        $offset = ($index * 10);
        $limit = 10;

        $orderArr = Db::table('b2b_orders')
            ->leftJoin('b2b_order_details', 'b2b_orders.id', '=', 'b2b_order_details.order_master_id')
            ->leftJoin('shops', 'b2b_orders.shop_id', '=', 'shops.id')
            ->select(
                'b2b_orders.id',
                'b2b_orders.order_date',
                'b2b_orders.total_amount',
                'b2b_orders.shop_id',
                'shops.shop_name',
                DB::raw('COUNT(b2b_order_details.id) as total_items'),
                DB::raw('SUM(b2b_order_details.quantity) as total_units')
            )
            ->where('b2b_orders.executive_id', $executiveId)
            ->groupBy('b2b_orders.id')
            ->offset($offset)
            ->limit($limit)
            ->orderBy('b2b_orders.id', 'DESC')
            ->get();

        return handlefetchResponse($orderArr);
    }

    public function fetchOrdersByShop(Request $request)
    {
        $request->validate([
            'executive_id' => 'required|integer',
            'shop_id' => 'required|integer',
            'index' => 'required|integer|min:0',
        ]);

        $executiveId = $request->executive_id;
        $shopId = $request->shop_id;
        $index = $request->index;
        $offset = ($index * 10);
        $limit = 10;

        $orderArr = Db::table('b2b_orders')
            ->leftJoin('b2b_order_details', 'b2b_orders.id', '=', 'b2b_order_details.order_master_id')
            ->leftJoin('shops', 'b2b_orders.shop_id', '=', 'shops.id')
            ->select(
                'b2b_orders.id',
                'b2b_orders.order_date',
                'b2b_orders.total_amount',
                'b2b_orders.shop_id',
                'shops.shop_name',
                DB::raw('COUNT(b2b_order_details.id) as total_items'),
                DB::raw('SUM(b2b_order_details.quantity) as total_units')
            )
            ->where('b2b_orders.executive_id', $executiveId)
            ->where('b2b_orders.shop_id', $shopId)
            ->groupBy('b2b_orders.id')
            ->offset($offset)
            ->limit($limit)
            ->orderBy('b2b_orders.id', 'DESC')
            ->get();

        return handlefetchResponse($orderArr);
    }

    public function fetchOrderDetailsById(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|min:1',

        ]);
        $orderId = $request->order_id;

        $orderArr = DB::table('b2b_orders')
            ->leftJoin('b2b_order_details', 'b2b_orders.id', '=', 'b2b_order_details.order_master_id')
            ->join('product_variants', 'b2b_order_details.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->select('b2b_orders.*', 'b2b_order_details.*', 'product_variants.variant_name', 'products.product_name',)
            ->where('b2b_orders.id', $orderId)
            ->get();

        if ($orderArr->isNotEmpty()) {

            $orderDetails = [
                'id' => $orderArr[0]->id,
                'shop_id' => $orderArr[0]->shop_id,
                'executive_id' => $orderArr[0]->executive_id,
                'distributor_id' => $orderArr[0]->distributor_id,
                'total_amount' => $orderArr[0]->total_amount,
                'order_date' => $orderArr[0]->order_date,
                'shipping_date' => $orderArr[0]->shipping_date,
                'delivery_date' => $orderArr[0]->delivery_date,
                'payment_status' => $orderArr[0]->payment_status,
                'order_status' => $orderArr[0]->b2b_order_status,
                'ordered_items' => $orderArr->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'quantity' => $item->quantity,
                        'purchase_price' => $item->purchase_price,
                        'mrp' => $item->mrp,
                        'selling_price' => $item->selling_price,
                        'product_name' => $item->product_name,
                        'variant_name' => $item->variant_name,
                        'product_order_status' => $item->b2b_order_details_status,
                        'image' => Task::fetchProductImage($item->product_variant_id),
                    ];
                })->toArray()
            ];

            return handleSuccess('Successfully retrieved data from the server', [$orderDetails]);
        } else {

            return handleError('DATA_NOT_FOUND');
        }
    }

    public function createCartItem(Request $request)
    {

        $request->validate([
            'shop_id' => 'required|integer',
            'product_id' => 'required|integer',
            'product_variant_id' => 'required|integer',
        ]);

        $shopId = $request->shop_id;
        $productId = $request->product_id;
        $productVariantId = $request->product_variant_id;

        $cartItemExists = Task::checkCartItemExists($request);

        if ($cartItemExists) {

            return handleCustomError("Item already added in cart.");
        }

        $newTableRow = [
            'product_id' => $productId,
            'product_variant_id' => $productVariantId,
            'user_type' => 1,
            'user_id' => $shopId,
            'quantity' => 1,
        ];
        $newCartId = DB::table('carts')->insertGetId($newTableRow);

        if ($newCartId) {
            return handleSuccess('Successfully added item into cart.');
        } else {
            return handleCustomError("Failed to add item into cart.");
        }
    }

    public function deleteCartItem(Request $request)
    {
        $request->validate([
            'cart_id' => 'required|integer|exists:carts,id',
        ]);

        $deletedRowCount = DB::table('carts')->where('id', $request->cart_id)->delete();

        if ($deletedRowCount) {

            return handleSuccess('Successfully removed item from cart.');
        } else {

            return handleCustomError("Failed to remove item from cart.");
        }
    }

    public function updateCartItem(Request $request)
    {

        $request->validate([
            'cart_id' => 'required|integer|exists:carts,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $cartId = $request->cart_id;
        $quantity = $request->quantity;

        $updatedRowCount = DB::table('carts')
            ->where('id', $cartId)
            ->update([
                'quantity' => $quantity,
                'updated_at' => Carbon::now(),
            ]);

        if ($updatedRowCount > 0) {

            return handleSuccess('Successfully updated the cart.');
        } else {

            return handleCustomError("Failed to update the cart.");
        }
    }

    public function fetchCartItems(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|integer',
            'distributor_id' => 'required|integer',

        ]);
        $shopId = $request->shop_id;
        $distributorId = $request->distributor_id;
        $userType = 1;

        $cartArr = DB::table('carts')
            ->join('product_variants', 'carts.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->leftJoin('product_images', function ($join) {
                $join->on('product_variants.id', '=', 'product_images.product_variant_id')
                    ->where('product_images.product_image_status', '=', 1);
            })
            ->select(
                'carts.id',
                'carts.user_id as shop_id',
                'carts.product_id',
                'carts.product_variant_id',
                'carts.quantity',
                'products.product_name',
                'product_variants.variant_name',
                'products.distributor_id',
                'product_variants.b2b_selling_price',
                'product_images.image'
            )
            ->where('carts.user_type', $userType)
            ->where('carts.user_id', $shopId)
            ->where('products.distributor_id', $distributorId)
            ->where('product_variants.b2b_status', 1)
            ->where('product_variants.approve_status', 1)
            ->groupBy('product_variants.id')
            ->get();

        return handlefetchResponse($cartArr);
    }

    public function fetchPendingPayments(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|integer',
            'executive_id' => 'required|integer',

        ]);

        $shopId = $request->shop_id;
        $executiveId = $request->executive_id;

        $pendingOrderArr = DB::table('b2b_orders')
            ->select(
                'id as order_id',
                'shop_id',
                'distributor_id',
                'executive_id',
                'order_date',
                'delivery_date',
                DB::raw('CAST(total_amount AS DECIMAL(10, 2)) as total_amount'),
                'discount',
                'payment_amount as paid_amount',
                DB::raw('(total_amount - payment_amount) as pending_amount'),
                'payment_status',
                'b2b_order_status as order_status'
            )
            ->where('shop_id', $shopId)
            ->where('executive_id', $executiveId)
            ->whereIn('payment_status', [0, 2])
            ->get();

        return handlefetchResponse($pendingOrderArr);
    }

    public function fetchPaymentDetails(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
        ]);

        $orderId = $request->order_id;

        $orderArr = DB::table('b2b_orders')
            ->join('b2b_transactions', 'b2b_orders.id', '=', 'b2b_transactions.order_id')
            ->select(
                'b2b_orders.id as order_id',
                'b2b_orders.shop_id',
                'b2b_orders.distributor_id',
                'b2b_orders.executive_id',
                'b2b_orders.order_date',
                'b2b_orders.delivery_date',
                'b2b_orders.total_amount',
                'b2b_orders.discount',
                'b2b_orders.payment_amount as paid_amount',
                DB::raw('(total_amount - payment_amount) as pending_amount'),
                'b2b_orders.payment_status',
                'b2b_orders.b2b_order_status',
                'b2b_transactions.transaction_id',
                'b2b_transactions.transaction_amount',
                'b2b_transactions.payment_mode',
                'b2b_transactions.created_at'
            )
            ->where('b2b_orders.id', $orderId)
            ->get();

        if ($orderArr->isNotEmpty()) {

            $orderDetails = [
                'order_id' => $orderArr[0]->order_id,
                'shop_id' => $orderArr[0]->shop_id,
                'executive_id' => $orderArr[0]->executive_id,
                'distributor_id' => $orderArr[0]->distributor_id,
                'order_date' => $orderArr[0]->order_date,
                'delivery_date' => $orderArr[0]->delivery_date,
                'total_amount' => $orderArr[0]->total_amount,
                'discount' => $orderArr[0]->discount,
                'paid_amount' => $orderArr[0]->paid_amount,
                'pending_amount' => $orderArr[0]->pending_amount,
                'payment_status' => $orderArr[0]->payment_status,
                'order_status' => $orderArr[0]->b2b_order_status,
                'payment_history' => $orderArr->map(function ($item) {
                    return [
                        'txn_date' => $item->created_at,
                        'payment_mode' => $item->payment_mode,
                        'transaction_amount' => $item->transaction_amount,
                        'transaction_id' => $item->transaction_id,
                    ];
                })->toArray()
            ];

            return handleSuccess('Successfully retrieved data from the server', [$orderDetails]);
        } else {

            return handleError('DATA_NOT_FOUND');
        }
    }

    public function executiveOrderPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'shop_id' => 'required|integer',
            'transaction_id' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'payment_mode' => 'required|integer',
        ]);

        $orderExists = Task::checkOrderExist($request->order_id);

        if (!$orderExists) {
            return handleCustomError("No order found.");
        }

        try {
            DB::beginTransaction();

            // Create a transaction record
            $newTxnId = Task::createB2BTransactions($request);
            if (!$newTxnId) {
                DB::rollBack();
                return handleCustomError("Failed to create a transaction record.");
            }

            // Update the order payment
            $update = Task::updateB2BOrder($request->order_id, $request->amount);
            if (!$update) {
                DB::rollBack();
                return handleCustomError("Failed to update the payment in the order.");
            }

            DB::commit();
            return handleSuccess('Successfully updated the payment.');
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error("Payment Update Error: " . $e->getMessage(), ['request' => $request->all()]);
            return handleCustomError("An unexpected error occurred. Please try again.");
        }
    }

    public function fetchShopNotes(Request $request)
    {

        $request->validate([
            'shop_id' => 'required|integer|exists:shops,id',
            'distributor_id' => 'required|integer|exists:distributors,id',
        ]);
        $shopId = $request->shop_id;
        $distributorId = $request->distributor_id;

        $shopNoteArr = DB::table('shop_notes')
            ->where('distributor_id', $distributorId)
            ->where('shop_id', $shopId)
            ->orderBy('id', 'desc')
            ->get();

        return handlefetchResponse($shopNoteArr);
    }

    public function createShopNote(Request $request)
    {

        $request->validate([
            'shop_id' => 'required|integer|exists:shops,id',
            'notes' => 'required',
            'distributor_id' => 'required|integer|exists:distributors,id',
            'executive_id' => 'required|integer|exists:executives,id',

        ]);

        $newShopNotesRow = [
            'shop_id' => $request->shop_id,
            'note' => $request->notes,
            'executive_id' => $request->executive_id,
            'distributor_id' => $request->distributor_id,
            'created_at' => Carbon::now(),
            'updated_at' =>  Carbon::now(),
        ];

        $id = DB::table('shop_notes')->insertGetId($newShopNotesRow);

        if ($id) {

            return handleSuccess("Successfully added the shop's note.");
        } else {

            return handleCustomError("Failed to add the shop's note.");
        }
    }

    public function updateShopNote(Request $request)
    {
        $request->validate([
            'shop_note_id' => 'required|integer',
            'note' => 'required',
            'executive_id' => 'required|integer',
        ]);
        $shopNoteId = $request->shop_note_id;

        $shopNoteExists = Task::checkShopNoteExist($shopNoteId);

        if (!$shopNoteExists) {
            return handleCustomError("Shop's note not found.");
        }

        $updateshopNotesField = [
            'note' => $request->note,
            'executive_id' => $request->executive_id,
            'updated_at' => Carbon::now()
        ];

        $updatedRows = DB::table('shop_notes')
            ->where('id', $shopNoteId)
            ->update($updateshopNotesField);

        if ($updatedRows > 0) {

            return handleSuccess("Successfully updated the shop's note.");
        } else {

            return handleCustomError("Failed to update the shop's note.");
        }
    }

    public function executiveSalesChart(Request $request)
    {

        $request->validate([
            'executive_id' => 'required|integer',
        ]);

        $executiveId = $request->executive_id;

        $salesArr = DB::table('b2b_orders')
            ->select(
                'order_date as date',
                DB::raw('SUM(total_amount) as amount')
            )
            ->where('executive_id', $executiveId)
            ->where('order_date', '>=', Carbon::now()->subDays(7))
            ->whereNotIn('b2b_order_status', [4, 5])
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();


        if ($salesArr->isNotEmpty()) {

            $salesData = [];
            foreach ($salesArr as $sale) {
                $salesData[$sale->date] =  (float)$sale->amount;
            }

            $sales = [];
            for ($i = 0; $i <= 6; $i++) {
                $date = Carbon::now()->subDays($i)->format('Y-m-d');
                $sales[] = [
                    'date' => $date,
                    'amount' => $salesData[$date] ?? 0
                ];
            }

            return handleSuccess('Successfully got data from the server', $sales);
        } else {

            return handleError('DATA_NOT_FOUND');
        }
    }
}
