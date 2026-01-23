<?php

namespace App\Http\Controllers\Reports;

use App\Traits\FormatNumber; 
use App\Traits\FormatsDateInputs;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

use App\Models\Items\ItemTransaction;
// use App\Models\Items\ItemBatchTransaction;
// use App\Models\Items\ItemSerialTransaction;
use App\Models\Sale\Sale;
use App\Enums\ItemTransactionUniqueCode;
use App\Models\Sale\SaleOrder;
use App\Models\User;
use Carbon\Carbon;

class SaleTransactionReportController extends Controller
{
    use FormatsDateInputs;

    use FormatNumber;

    // public function getSaleRecords(Request $request) : JsonResponse{
    //     try{
    //         // Validation rules
    //         $rules = [
    //             'from_date'         => ['required', 'date_format:'.implode(',', $this->getDateFormats())],
    //             'to_date'           => ['required', 'date_format:'.implode(',', $this->getDateFormats())],
    //         ];

    //         $validator = Validator::make($request->all(), $rules);

    //         if ($validator->fails()) {
    //             throw new \Exception($validator->errors()->first());
    //         }

    //         $fromDate           = $request->input('from_date');
    //         $fromDate           = $this->toSystemDateFormat($fromDate);
    //         $toDate             = $request->input('to_date');
    //         $toDate             = $this->toSystemDateFormat($toDate);
    //         $partyId             = $request->input('party_id');

    //         $preparedData = Sale::with('party')
    //                                             ->when($partyId, function ($query) use ($partyId) {
    //                                                 return $query->where('party_id', $partyId);
    //                                             })
    //                                             ->whereBetween('sale_date', [$fromDate, $toDate])
    //                                             ->get();

            
    //         if($preparedData->count() == 0){
    //             throw new \Exception('No Records Found!!');
    //         }

    //         $recordsArray = [];

    //         foreach ($preparedData as $data) {
    //             $recordsArray[] = [  
    //                                 'sale_date'         => $this->toUserDateFormat($data->sale_date),
    //                                 'invoice_or_bill_code'  => $data->sale_code,
    //                                 'party_name'            => $data->party->getFullName(),
    //                                 'grand_total'           => $this->formatWithPrecision($data->grand_total, comma:false),
    //                                 'paid_amount'           => $this->formatWithPrecision($data->paid_amount, comma:false),
    //                                 'balance'               => $this->formatWithPrecision($data->grand_total - $data->paid_amount , comma:false),
    //                             ];
    //         }
            
    //         return response()->json([
    //                     'status'    => true,
    //                     'message' => "Records are retrieved!!",
    //                     'data' => $recordsArray,
    //                 ]);
    //     } catch (\Exception $e) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => $e->getMessage(),
    //             ], 409);

    //     }
    // }



    // public function getSaleRecords(Request $request) : JsonResponse{
    //     try{
    //         // Validation rules
    //         $rules = [
    //             'from_date'         => ['required', 'date_format:'.implode(',', $this->getDateFormats())],
    //             'to_date'           => ['required', 'date_format:'.implode(',', $this->getDateFormats())],
    //         ];

    //         $validator = Validator::make($request->all(), $rules);

    //         if ($validator->fails()) {
    //             throw new \Exception($validator->errors()->first());
    //         }

    //         $fromDate           = $request->input('from_date');
    //         $fromDate           = $this->toSystemDateFormat($fromDate);
    //         $toDate             = $request->input('to_date');
    //         $toDate             = $this->toSystemDateFormat($toDate);
    //         $partyId             = $request->input('party_id');

    //         $preparedData = SaleOrder::with('party')
    //                                             ->when($partyId, function ($query) use ($partyId) {
    //                                                 return $query->where('party_id', $partyId);
    //                                             })
    //                                             ->whereBetween('created_at', [$fromDate, $toDate])
    //                                             ->get();

            
    //         if($preparedData->count() == 0){
    //             throw new \Exception('No Records Found!!');
    //         }

    //         $recordsArray = [];

    //         foreach ($preparedData as $data) {
    //             $recordsArray[] = [  
    //                                 'sale_date'         => $this->toUserDateFormat($data->sale_date),
    //                                 'invoice_or_bill_code'  => $data->sale_code,
    //                                 'party_name'            => $data->party->getFullName(),
    //                                 'grand_total'           => $this->formatWithPrecision($data->grand_total, comma:false),
    //                                 'paid_amount'           => $this->formatWithPrecision($data->paid_amount, comma:false),
    //                                 'balance'               => $this->formatWithPrecision($data->grand_total - $data->paid_amount , comma:false),
    //                             ];
    //         }
            
    //         return response()->json([
    //                     'status'    => true,
    //                     'message' => "Records are retrieved!!",
    //                     'data' => $recordsArray,
    //                 ]);
    //     } catch (\Exception $e) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => $e->getMessage(),
    //             ], 409);

    //     }
    // }

    // public function getSaleRecords(Request $request): JsonResponse
    // {
    //     try {
    //         // Validation rules
    //         $rules = [
    //             'from_date' => ['required', 'date_format:' . implode(',', $this->getDateFormats())],
    //             'to_date'   => ['required', 'date_format:' . implode(',', $this->getDateFormats())],
    //         ];

    //         $validator = Validator::make($request->all(), $rules);

    //         if ($validator->fails()) {
    //             throw new \Exception($validator->errors()->first());
    //         }

    //         $fromDate = $this->toSystemDateFormat($request->input('from_date'));
    //         $toDate   = $this->toSystemDateFormat($request->input('to_date'));

    //         // Group by party and sum grand_total
    //         $topCustomers = SaleOrder::select('party_id')
    //             ->selectRaw('SUM(grand_total) as total_purchase')
    //             ->with('party')
    //             ->whereBetween('created_at', [$fromDate, $toDate])
    //             ->groupBy('party_id')
    //             ->orderByDesc('total_purchase')
    //             ->get();

    //         if ($topCustomers->isEmpty()) {
    //             throw new \Exception('No Records Found!!');
    //         }

    //         $recordsArray = [];

    //         foreach ($topCustomers as $data) {
    //             $recordsArray[] = [
    //                 'party_name'    => $data->party->getFullName(),
    //                 'total_purchase' => $this->formatWithPrecision($data->total_purchase, comma: false),
    //             ];
    //         }

    //         return response()->json([
    //             'status'  => true,
    //             'message' => "Top customers retrieved successfully!",
    //             'data'    => $recordsArray,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => $e->getMessage(),
    //         ], 409);
    //     }
    // }


    // public function getSaleRecords(Request $request): JsonResponse
    // {
    //     try {
    //         // Validation rules
    //         $rules = [
    //             'from_date' => ['required', 'date_format:' . implode(',', $this->getDateFormats())],
    //             'to_date'   => ['required', 'date_format:' . implode(',', $this->getDateFormats())],
    //         ];

    //         $validator = Validator::make($request->all(), $rules);

    //         if ($validator->fails()) {
    //             throw new \Exception($validator->errors()->first());
    //         }

    //         $fromDate = Carbon::parse($this->toSystemDateFormat($request->input('from_date')))->startOfDay();
    //         $toDate   = Carbon::parse($this->toSystemDateFormat($request->input('to_date')))->endOfDay();
    //         $partyId            = $request->input('party_id');
    //         // Group by party and sum grand_total
    //         $topCustomers = SaleOrder::select('party_id')
    //             ->selectRaw('SUM(grand_total) as total_purchase')
    //             ->with('party')
    //             ->whereNotNull('party_id')
    //             ->whereBetween('order_date', [$fromDate, $toDate])
    //             ->groupBy('party_id')
    //             ->orderByDesc('total_purchase')
    //             ->limit(10) // optional
    //             ->get();

    //         if ($topCustomers->isEmpty()) {
    //             throw new \Exception('No Records Found!!');
    //         }

    //         $recordsArray = [];

    //         foreach ($topCustomers as $data) {
    //             $recordsArray[] = [
    //                 'party_name'     => $data->party?->getFullName() ?? 'Unknown',
    //                 'total_purchase' => $this->formatWithPrecision($data->total_purchase, comma: false),
    //             ];
    //         }

    //         return response()->json([
    //             'status'  => true,
    //             'message' => "Top customers retrieved successfully!",
    //             'data'    => $recordsArray,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => $e->getMessage(),
    //         ], 409);
    //     }
    // }

    // public function getSaleRecords(Request $request): JsonResponse
    // {
    //     dd($request->all());
    //     try {
    //         // Validation rules
    //         $rules = [
    //             'from_date' => ['required', 'date_format:' . implode(',', $this->getDateFormats())],
    //             'to_date'   => ['required', 'date_format:' . implode(',', $this->getDateFormats())],
    //         ];

    //         $validator = Validator::make($request->all(), $rules);

    //         if ($validator->fails()) {
    //             throw new \Exception($validator->errors()->first());
    //         }

    //         $fromDate = Carbon::parse($this->toSystemDateFormat($request->input('from_date')))->startOfDay();
    //         $toDate   = Carbon::parse($this->toSystemDateFormat($request->input('to_date')))->endOfDay();
    //         $partyId  = $request->input('party_id');

    //         // Build query
    //         $query = SaleOrder::select('party_id')
    //             ->selectRaw('SUM(grand_total) as total_purchase')
    //             ->with('party')
    //             ->whereNotNull('party_id')
    //             ->whereBetween('order_date', [$fromDate, $toDate])
    //             ->groupBy('party_id')
    //             ->orderByDesc('total_purchase');

    //         // 🔹 Apply party filter if provided
    //         if (!empty($partyId)) {
    //             $query->where('party_id', $partyId);
    //         }

    //         // Optional: Limit top 10 only if not filtering by one party
    //         if (empty($partyId)) {
    //             $query->limit(10);
    //         }

    //         $topCustomers = $query->get();

    //         if ($topCustomers->isEmpty()) {
    //             throw new \Exception('No Records Found!!');
    //         }

    //         $recordsArray = [];

    //         foreach ($topCustomers as $data) {
    //             $recordsArray[] = [
    //                 'party_name'     => $data->party?->getFullName() ?? 'Unknown',
    //                 'total_purchase' => $this->formatWithPrecision($data->total_purchase, comma: false),
    //             ];
    //         }

    //         return response()->json([
    //             'status'  => true,
    //             'message' => "Top customers retrieved successfully!",
    //             'data'    => $recordsArray,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => $e->getMessage(),
    //         ], 409);
    //     }
    // }



    // public function getSaleRecords(Request $request): JsonResponse
    // {
    //     try {

            
    //         $fromDate = Carbon::createFromFormat('d/m/Y', $request->from_date)->format('Y-m-d');
    //         $toDate   = Carbon::createFromFormat('d/m/Y', $request->to_date)->format('Y-m-d');

    //         // dd($fromDate, $toDate);

    //         $partyId = $request->party_id;
    //         $userId  = $request->user_id;

    //         $data = SaleOrder::with('party', 'user')
    //         ->when($partyId, fn($q) => $q->where('party_id', $partyId))
    //         ->when($userId, fn($q) => $q->where('created_by', $userId))
    //         ->whereDate('order_date', '>=', $fromDate)
    //         ->whereDate('order_date', '<=', $toDate)
    //         ->get();

    //         dd($data);

           

    //         return response()->json([
    //             'status'  => true,
    //             'message' => "Records fetched successfully!",
    //             'data'    => $output
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $e->getMessage(),
    //         ], 409);
    //     }
    // }


    // public function getSaleRecords(Request $request): JsonResponse
    // {
    //     try {

    //         // Convert dd/mm/yyyy → yyyy-mm-dd
    //         $fromDate = Carbon::createFromFormat('d/m/Y', $request->from_date)->format('Y-m-d');
    //         $toDate   = Carbon::createFromFormat('d/m/Y', $request->to_date)->format('Y-m-d');

    //         $partyId = $request->party_id;
    //         $userId  = $request->user_id;

    //         $data = SaleOrder::with('party', 'user')
    //             ->when($partyId, fn($q) => $q->where('party_id', $partyId))
    //             ->when($userId, fn($q) => $q->where('created_by', $userId))
    //             ->whereDate('order_date', '>=', $fromDate)
    //             ->whereDate('order_date', '<=', $toDate)
    //             ->get();

    //         // --------------------------
    //         // GROUP DATA BY CUSTOMER
    //         // --------------------------
    //         $output = [];

    //         foreach ($data as $order) {

    //             // $partyName = $order->party->first_name . ' ' . $order->party->last_name;
    //             // $userName  = $order->user->first_name . ' ' . $order->user->last_name;
    //             $partyName = $order->party
    //                 ? $order->party->first_name . ' ' . $order->party->last_name
    //                 : 'N/A';

    //             $userName = $order->user
    //                 ? $order->user->first_name . ' ' . $order->user->last_name
    //                 : 'N/A';


    //             if (!isset($output[$order->party_id])) {

    //                 $output[$order->party_id] = [
    //                     "party_name" => $partyName,
    //                     "user_name"  => $userName,
    //                     "records"    => []
    //                 ];
    //             }

    //             $output[$order->party_id]["records"][] = [
    //                 "date"   => $order->order_date,
    //                 "amount" => (float)$order->grand_total,
    //             ];
    //         }

    //         // Re-index array
    //         $output = array_values($output);

    //         return response()->json([
    //             'status'  => true,
    //             'message' => "Records fetched successfully!",
    //             'data'    => $output
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $e->getMessage(),
    //         ], 409);
    //     }
    // }



    public function getSaleRecords(Request $request): JsonResponse
    {
        try {

            // Convert dd/mm/yyyy → yyyy-mm-dd
            $fromDate = Carbon::createFromFormat('d/m/Y', $request->from_date)->format('Y-m-d');
            $toDate   = Carbon::createFromFormat('d/m/Y', $request->to_date)->format('Y-m-d');

            $partyId = $request->party_id;   // Customer
            $userId  = $request->user_id;    // User

            $data = SaleOrder::with('party', 'user')
                ->when($partyId, fn($q) => $q->where('party_id', $partyId))
                ->when($userId, fn($q) => $q->where('created_by', $userId))
                ->whereDate('order_date', '>=', $fromDate)
                ->whereDate('order_date', '<=', $toDate)
                ->get();

            // --------------------------
            // GROUP DATA (User + Customer)
            // --------------------------
            $output = [];

            foreach ($data as $order) {

                $partyName = $order->party
                    ? $order->party->first_name . ' ' . $order->party->last_name
                    : 'N/A';

                $userName = $order->user
                    ? $order->user->first_name . ' ' . $order->user->last_name
                    : 'N/A';

                // Unique key for grouping (User + Customer)
                $key = $order->created_by . '_' . $order->party_id;

                if (!isset($output[$key])) {

                    $output[$key] = [
                        "party_id"   => $order->party_id,
                        "party_name" => $partyName,

                        "user_id"    => $order->created_by,
                        "user_name"  => $userName,

                        "total_amount" => 0,
                        "records"      => []
                    ];
                }

                $output[$key]["records"][] = [
                    "date"   => $order->order_date,
                    "amount" => (float) $order->grand_total,
                ];

                // Increase total
                $output[$key]["total_amount"] += (float) $order->grand_total;
            }

            // Re-index array
            $output = array_values($output);

            return response()->json([
                'status'  => true,
                'message' => "Records fetched successfully!",
                'data'    => $output
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }







    /**
     * Item Sale Report
     * */
    // function getSaleItemRecords(Request $request): JsonResponse{
    //     // dd($request->all());
        
    //     try{
    //         // Validation rules
    //         $rules = [
    //             'from_date'         => ['required', 'date_format:'.implode(',', $this->getDateFormats())],
    //             'to_date'           => ['required', 'date_format:'.implode(',', $this->getDateFormats())],
    //         ];

    //         $validator = Validator::make($request->all(), $rules);

    //         if ($validator->fails()) {
    //             throw new \Exception($validator->errors()->first());
    //         }

    //         $fromDate           = $request->input('from_date');
    //         $fromDate           = $this->toSystemDateFormat($fromDate);
    //         $toDate             = $request->input('to_date');
    //         $toDate             = $this->toSystemDateFormat($toDate);
    //         $partyId            = $request->input('party_id');
    //         $itemId             = $request->input('item_id');
    //         $warehouseId        = $request->input('warehouse_id');

    //         $preparedData = ItemTransaction::
    //                                             whereBetween('created_at', [$fromDate, $toDate])
    //                                             ->when($itemId, function ($query) use ($itemId) {
    //                                                 return $query->where('item_id', $itemId);
    //                                             })
    //                                             ->when($itemId, function ($query) use ($itemId) {
    //                                                 return $query->whereHas('itemTransaction', function ($query) use ($itemId) {
    //                                                     return $query->where('item_id', $itemId)
    //                                                                 ->where('transaction_type', 'Sale Order');
    //                                                 });
    //                                             })

    //                                             ->when($warehouseId, function ($query) use ($warehouseId) {
    //                                                     return $query->whereHas('itemTransaction', function ($query) use ($warehouseId) {
    //                                                         return $query->where('warehouse_id', $warehouseId);
    //                                                     });
    //                                                 })
    //                                              ->get();

    //                                              dd($preparedData);
                                                
        
    //         if($preparedData->count() == 0){
    //             throw new \Exception('No Records Found!!');
    //         }
    //         $recordsArray = [];

    //         foreach ($preparedData as $data) {
    //             foreach($data->itemTransaction as $transaction){
    //                 $recordsArray[] = [  
    //                                 'sale_date'         => $this->toUserDateFormat($data->sale_date),
    //                                 'invoice_or_bill_code'  => $data->sale_code,
    //                                 'party_name'            => $data->party->getFullName(),
    //                                 'warehouse'             => $transaction->warehouse->name,
    //                                 'item_name'             => $transaction->item->name,
    //                                 'unit_price'            => $this->formatWithPrecision($transaction->unit_price, comma:false),
    //                                 'quantity'              => $this->formatWithPrecision($transaction->quantity, comma:false),
    //                                 'discount_amount'       => $this->formatWithPrecision($transaction->discount_amount, comma:false),
    //                                 'tax_amount'            => $this->formatWithPrecision($transaction->tax_amount, comma:false),
    //                                 'total'                 => $this->formatWithPrecision($transaction->total , comma:false),
    //                             ];

    //             }
                
    //         }
            
    //         return response()->json([
    //                     'status'    => true,
    //                     'message' => "Records are retrieved!!",
    //                     'data' => $recordsArray,
    //                 ]);
    //     } catch (\Exception $e) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => $e->getMessage(),
    //             ], 409);

    //     }
    // }



    // public function getSaleItemRecords(Request $request): JsonResponse
    // {
    //     dd($request->all());
    //     try {
    //         // Validate input
    //         $rules = [
    //             'from_date' => ['required', 'date_format:' . implode(',', $this->getDateFormats())],
    //             'to_date'   => ['required', 'date_format:' . implode(',', $this->getDateFormats())],
    //         ];

    //         $validator = Validator::make($request->all(), $rules);

    //         if ($validator->fails()) {
    //             throw new \Exception($validator->errors()->first());
    //         }

    //         // Format input
    //         $fromDate = Carbon::parse($this->toSystemDateFormat($request->input('from_date')))->startOfDay();
    //         $toDate   = Carbon::parse($this->toSystemDateFormat($request->input('to_date')))->endOfDay();

    //         $itemId   = $request->input('item_id');

    //         // Get only relevant item transactions
    //         $transactions = ItemTransaction::where('item_id', $itemId)
                
    //             ->whereBetween('created_at', [$fromDate, $toDate])
    //             ->get();

    //         if ($transactions->isEmpty()) {
    //             throw new \Exception('No Records Found!!');
    //         }

            
    //         // Calculate total and get item name
    //         $totalQuantity = $transactions->sum('quantity');
    //         $itemName = optional($transactions->first()->item)->name ?? 'Unknown Item';

    //         // Return response
    //         return response()->json([
    //             'status'  => true,
    //             'message' => "Records are retrieved!!",
    //             'data'    => [[
    //                 'item_name'            => $itemName,
    //                 'total_quantity_sold'  => $this->formatWithPrecision($totalQuantity, comma: false),
    //             ]],
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => $e->getMessage(),
    //         ], 409);
    //     }
    // }




    public function getSaleItemRecords(Request $request): JsonResponse
    {
        try {

            // Validate input
            $validator = Validator::make($request->all(), [
                'from_date' => ['required', 'date_format:' . implode(',', $this->getDateFormats())],
                'to_date'   => ['required', 'date_format:' . implode(',', $this->getDateFormats())],
                'item_category_id' => ['required'],
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            // Convert date formats
            $fromDate = Carbon::parse($this->toSystemDateFormat($request->from_date))->startOfDay();
            $toDate   = Carbon::parse($this->toSystemDateFormat($request->to_date))->endOfDay();

            $categoryId = $request->item_category_id;
            $userId     = $request->user_id ?? null;

            // Query
            $transactions = ItemTransaction::with('item.category')
                ->where('transaction_type', 'Sale Order')
                ->whereBetween('transaction_date', [$fromDate, $toDate])
                ->whereHas('item', function ($q) use ($categoryId) {
                    $q->where('item_category_id', $categoryId);
                })
                ->when($userId, function ($q) use ($userId) {
                    $q->where('created_by', $userId);
                })
                ->get();

            if ($transactions->isEmpty()) {
                throw new \Exception("No Records Found!!");
            }

            // GROUP RESULTS: date → item → totals
            $groupedResults = [];
            $user = $userId ? User::find($userId) : null;
            $userName = $user ? $user->username : 'N/A';

            foreach ($transactions as $tx) {
                $date = Carbon::parse($tx->transaction_date)->format('Y-m-d');
                $item = $tx->item;
                $categoryName = $item->category->name ?? 'Unknown Category';
                if (!isset($groupedResults[$date][$item->id])) {
                    $groupedResults[$date][$item->id] = [
                        'item_name'      => $item->name,
                        'qty'            => 0,
                        'category_name'  => $categoryName,
                        'sale_total'     => 0,
                        'purchase_total' => 0,
                        'profit'         => 0,
                         'user_name'      => $userName,
                    ];
                }

                $sale = $tx->unit_price * $tx->quantity;
                $purchase = $item->purchase_price * $tx->quantity;

                $groupedResults[$date][$item->id]['qty']            += $tx->quantity;
                $groupedResults[$date][$item->id]['sale_total']     += $sale;
                $groupedResults[$date][$item->id]['purchase_total'] += $purchase;
                $groupedResults[$date][$item->id]['profit']         += ($sale - $purchase);
            }

            return response()->json([
                'status'  => true,
                'message' => "Records Retrieved Successfully!",
                'data'    => $groupedResults,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }













    /**
     * Item Sale Report
     * */
    // function getSalePaymentRecords(Request $request): JsonResponse{

    //     try{
    //         // Validation rules
    //         $rules = [
    //             'from_date'         => ['required', 'date_format:'.implode(',', $this->getDateFormats())],
    //             'to_date'           => ['required', 'date_format:'.implode(',', $this->getDateFormats())],
    //         ];

    //         $validator = Validator::make($request->all(), $rules);

    //         if ($validator->fails()) {
    //             throw new \Exception($validator->errors()->first());
    //         }

    //         $fromDate           = $request->input('from_date');
    //         $fromDate           = $this->toSystemDateFormat($fromDate);
    //         $toDate             = $request->input('to_date');
    //         $toDate             = $this->toSystemDateFormat($toDate);
    //         $partyId            = $request->input('party_id');
    //         $paymentTypeId      = $request->input('payment_type_id');

    //         $preparedData = Sale::with('party', 'paymentTransaction')
    //                                             ->when($fromDate, function ($query) use ($fromDate, $toDate) {
    //                                                 return $query->whereHas('paymentTransaction', function ($query) use ($fromDate, $toDate) {
    //                                                     $query->whereBetween('transaction_date', [$fromDate, $toDate]);
    //                                                 });
    //                                             })
    //                                             ->when($partyId, function ($query) use ($partyId) {
    //                                                 return $query->where('party_id', $partyId);
    //                                             })
    //                                             ->when($paymentTypeId, function ($query) use ($paymentTypeId) {
    //                                                     return $query->whereHas('paymentTransaction', function ($query) use ($paymentTypeId) {
    //                                                         return $query->where('payment_type_id', $paymentTypeId);
    //                                                     });
    //                                                 })
    //                                             ->get();
        
    //         if($preparedData->count() == 0){
    //             throw new \Exception('No Records Found!!');
    //         }
    //         $recordsArray = [];

    //         foreach ($preparedData as $data) {
    //             foreach($data->paymentTransaction as $transaction){
    //                 $recordsArray[] = [  
    //                                 'transaction_date'      => $this->toUserDateFormat($transaction->transaction_date),
    //                                 'invoice_or_bill_code'  => $data->sale_code,
    //                                 'party_name'            => $data->party->getFullName(),
    //                                 'payment_type'          => $transaction->paymentType->name,
    //                                 'amount'                => $this->formatWithPrecision($transaction->amount, comma:false),
    //                             ];

    //             }
                
    //         }
            
    //         return response()->json([
    //                     'status'    => true,
    //                     'message' => "Records are retrieved!!",
    //                     'data' => $recordsArray,
    //                 ]);
    //     } catch (\Exception $e) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => $e->getMessage(),
    //             ], 409);

    //     }
    // }




    // public function getSalePaymentRecords(Request $request): JsonResponse
    // {
    //     // dd($request->all());
    //     try {
    //         // ✅ Validation rules
    //         $rules = [
    //             'from_date' => ['required', 'date_format:' . implode(',', $this->getDateFormats())],
    //             'to_date'   => ['required', 'date_format:' . implode(',', $this->getDateFormats())],
    //         ];

    //         $validator = Validator::make($request->all(), $rules);

    //         if ($validator->fails()) {
    //             throw new \Exception($validator->errors()->first());
    //         }

    //         // ✅ Convert dates
    //         $fromDate = Carbon::parse($this->toSystemDateFormat($request->input('from_date')))->startOfDay();
    //         $toDate   = Carbon::parse($this->toSystemDateFormat($request->input('to_date')))->endOfDay();


    //         $userId = $request->input('user_id');

    //         // 1️⃣ Get the totals grouped by date
    //         $salesSummary = SaleOrder::selectRaw('DATE(order_date) as order_date, SUM(grand_total) as total_sales')
    //             ->where('created_by', $userId)
    //             ->whereBetween('order_date', [$fromDate, $toDate])
    //             ->groupBy('order_date')
    //             ->orderBy('order_date', 'ASC')
    //             ->get();

    //         // 2️⃣ Load the user via relationship (just once)
    //         $user = User::find($userId);
    //         $userName = $user ? $user->username : null;




    //         if ($salesSummary->isEmpty()) {
    //             throw new \Exception('No Records Found!!');
    //         }

    //         // ✅ Prepare response array
    //         $recordsArray = [];
    //         $grandTotal = 0;

    //         foreach ($salesSummary as $data) {
    //             $recordsArray[] = [
    //                 'order_date'    => $this->toUserDateFormat($data->order_date),
    //                 'total_sales'  => $this->formatWithPrecision($data->total_sales, comma: false),
    //                 'user_name'    => $userName,
    //             ];
    //             $grandTotal += $data->total_sales;
    //         }

    //         return response()->json([
    //             'status'       => true,
    //             'message'      => "Total Sales Report Retrieved Successfully!",
    //             'data'         => $recordsArray,
    //             'grand_total'  => $this->formatWithPrecision($grandTotal, comma: false),
                
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => $e->getMessage(),
    //         ], 409);
    //     }
    // }




    public function getSalePaymentRecords(Request $request): JsonResponse
    {
        try {
            // Validation
            $rules = [
                'from_date' => ['required', 'date_format:' . implode(',', $this->getDateFormats())],
                'to_date'   => ['required', 'date_format:' . implode(',', $this->getDateFormats())],
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            // Convert dates
            $fromDate = Carbon::parse($this->toSystemDateFormat($request->input('from_date')))->startOfDay();
            $toDate   = Carbon::parse($this->toSystemDateFormat($request->input('to_date')))->endOfDay();

            // 🟩 Check if "all" checkbox is selected
            $all = $request->input('all') === 'on';

            $query = SaleOrder::selectRaw('DATE(order_date) as order_date, SUM(grand_total) as total_sales')
                ->whereBetween('order_date', [$fromDate, $toDate]);

            // 🟨 If NOT "all", filter by user_id
            if (!$all) {
                $userId = $request->input('user_id');

                $query->where('created_by', $userId);

                // Load user only if needed
                $user = User::find($userId);
                $userName = $user ? $user->username : null;
            } else {
                $userName = "All Users";
            }

            // Run query
            $salesSummary = $query->groupBy('order_date')
                ->orderBy('order_date', 'ASC')
                ->get();

            if ($salesSummary->isEmpty()) {
                throw new \Exception('No Records Found!!');
            }

            // Prepare response
            $recordsArray = [];
            $grandTotal = 0;

            foreach ($salesSummary as $data) {
                $recordsArray[] = [
                    'order_date'   => $this->toUserDateFormat($data->order_date),
                    'total_sales'  => $this->formatWithPrecision($data->total_sales, comma: false),
                    'user_name'    => $userName,
                ];
                $grandTotal += $data->total_sales;
            }

            return response()->json([
                'status'       => true,
                'message'      => "Total Sales Report Retrieved Successfully!",
                'data'         => $recordsArray,
                'grand_total'  => $this->formatWithPrecision($grandTotal, comma: false),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }




    
}
