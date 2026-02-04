<?php

namespace App\Http\Controllers;

use App\Models\Items\Item;
use App\Models\Items\ItemTransaction;
use App\Models\PaymentTransaction;
use App\Models\Sale\SaleOrder;
use App\Models\User;
use App\Models\Party\Party;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    Public function SaleReportPage()
    {
        return view('reports.sale_report');
    }

    public function stockReportPage()
    {
        return view('report.stock-report');
    }

    // public function getStockRecords(Request $request): JsonResponse
    // {
    //     try {
    //         // Validate input
    //         $validator = Validator::make($request->all(), [
    //             'item_category_id' => ['required', 'exists:item_categories,id'],
    //         ]);

    //         if ($validator->fails()) {
    //             throw new \Exception($validator->errors()->first());
    //         }

    //         $categoryId = $request->input('item_category_id');

    //         // Fetch all items belonging to this category
    //         $items = Item::where('item_category_id', $categoryId)->get();

    //         if ($items->isEmpty()) {
    //             throw new \Exception("No Records Found!!");
    //         }

    //         // Build response
    //         $stockData = [];
    //         foreach ($items as $item) {
    //             $stockData[] = [
    //                 'item_name'      => $item->name,
    //                 'remaining_qty'  => $item->current_stock,
    //                 'purchase_price' => $item->purchase_price,
    //                 'stock_value'    => $item->purchase_price * $item->current_stock,
    //             ];
    //         }

    //         return response()->json([
    //             'status'  => true,
    //             'message' => "Stock Records Retrieved Successfully!",
    //             'data'    => $stockData,
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $e->getMessage(),
    //         ], 409);
    //     }
    // }


    public function getStockRecords(Request $request): JsonResponse
    {
        try {

            $all = $request->input('all'); // "on" if checkbox selected
            $categoryId = $request->input('item_category_id');

            // -------------------------------
            // VALIDATION
            // -------------------------------
            if ($all !== "on") {
                // Category must be selected
                $validator = Validator::make($request->all(), [
                    'item_category_id' => ['required', 'exists:item_categories,id'],
                ]);

                if ($validator->fails()) {
                    throw new \Exception($validator->errors()->first());
                }
            }

            // -------------------------------
            // FETCH ITEMS
            // -------------------------------
            $items = Item::when($all !== "on", function($q) use ($categoryId) {
                    return $q->where('item_category_id', $categoryId);
                })
                ->get();

            if ($items->isEmpty()) {
                throw new \Exception("No Records Found!!");
            }

            // -------------------------------
            // FORMAT DATA
            // -------------------------------
            $stockData = [];

            foreach ($items as $item) {
                $stockData[] = [
                    'item_name'      => $item->name,
                    'remaining_qty'  => $item->current_stock,
                    'purchase_price' => $item->purchase_price,
                    'stock_value'    => $item->purchase_price * $item->current_stock,
                    'category_name'  => $item->category->name ?? "N/A",
                ];
            }

            return response()->json([
                'status'  => true,
                'message' => "Stock Records Retrieved Successfully!",
                'data'    => $stockData,
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }



    public function cashInReportPage()
    {
        return view('report.cashin-report');
    }

    // public function getCashinRecords(Request $request): JsonResponse
    // {
    //     try {

    //         // -------------------------------
    //         // Validate
    //         // -------------------------------
    //         $validator = Validator::make($request->all(), [
    //             'from_date' => ['required'],
    //             'to_date'   => ['required'],
    //             'user_id'   => ['nullable', 'exists:users,id'],
    //         ]);

    //         if ($validator->fails()) {
    //             throw new \Exception($validator->errors()->first());
    //         }

    //         // -------------------------------
    //         // Convert dates dd/mm/yyyy → Y-m-d
    //         // -------------------------------
    //         $fromDate = Carbon::createFromFormat('d/m/Y', $request->from_date)->startOfDay();
    //         $toDate   = Carbon::createFromFormat('d/m/Y', $request->to_date)->endOfDay();

    //         $userId = $request->user_id;

    //         // -------------------------------
    //         // Fetch Payments
    //         // -------------------------------
    //         $payments = PaymentTransaction::with('paymentType', 'user')
    //             ->whereBetween('transaction_date', [$fromDate, $toDate])->
    //             where('transaction_type','Purchase')
    //             ->when($userId, fn($q) => $q->where('created_by', $userId))
    //             ->orderBy('transaction_date', 'ASC')
    //             ->get();

    //         if ($payments->isEmpty()) {
    //             throw new \Exception("No Records Found!");
    //         }

    //         // -------------------------------
    //         // Format Response
    //         // -------------------------------
    //         $output = [];

    //         foreach ($payments as $p) {

    //             $output[] = [
    //                 "date"          => Carbon::parse($p->transaction_date)->format('d/m/Y'),
    //                 "receipt_no"    => $p->reference_no,
    //                 "payment_type"  => $p->paymentType->name ?? 'N/A',
    //                 "amount"        => (float)$p->amount,
    //                 "user_name"     => $p->user->first_name . ' ' . $p->user->last_name,
    //             ];
    //         }

    //         return response()->json([
    //             "status"  => true,
    //             "message" => "Cash Collection Records Retrieved Successfully!",
    //             "data"    => $output
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             "status" => false,
    //             "message" => $e->getMessage(),
    //         ], 409);
    //     }
    // }

    // public function getCashinRecords(Request $request): JsonResponse
    // {
    //     try {

    //         // -------------------------------
    //         // Validate inputs
    //         // -------------------------------
    //         $validator = Validator::make($request->all(), [
    //             'from_date' => ['required'],
    //             'to_date'   => ['required'],
    //             'user_id'   => ['nullable', 'exists:users,id'],
    //         ]);

    //         if ($validator->fails()) {
    //             throw new \Exception($validator->errors()->first());
    //         }


    //         $fromDate = Carbon::createFromFormat('d/m/Y', $request->from_date)->format('Y-m-d');
    //         $toDate   = Carbon::createFromFormat('d/m/Y', $request->to_date)->format('Y-m-d');

    //         $userId = $request->user_id;


    //         // ---------------------------------------------
    //         // FETCH CUSTOMER PAYMENTS (Correct Cash-In Source)
    //         // ---------------------------------------------
    //         $payments = DB::table('customer_payments')
    //             ->select(
    //                 'customer_payments.*',
    //                 'users.first_name',
    //                 'users.last_name',
    //                 'payment_types.name as payment_type'
    //             )
    //             ->leftJoin('users', 'users.id', '=', 'customer_payments.created_by')
    //             ->leftJoin('payment_types', 'payment_types.id', '=', 'customer_payments.payment_type_id')
    //             ->whereBetween(DB::raw('DATE(customer_payments.payment_date)'), [$fromDate, $toDate])
    //             ->when($userId, fn($q) => $q->where('customer_payments.created_by', $userId))
    //             ->orderBy('customer_payments.payment_date', 'ASC')
    //             ->get();

    //         if ($payments->isEmpty()) {
    //             throw new \Exception("No Records Found!");
    //         }


    //         // ---------------------------------------------
    //         // FORMAT OUTPUT
    //         // ---------------------------------------------
    //         $output = [];

    //         foreach ($payments as $p) {

    //             $output[] = [
    //                 "date"         => Carbon::parse($p->payment_date)->format('d/m/Y'),
    //                 "receipt_no"   => $p->reference_no ?? '—',
    //                 "payment_type" => $p->payment_type ?? 'N/A',
    //                 "amount"       => (float)$p->amount,
    //                 "user_name"    => trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? '')) ?: 'N/A',
    //             ];
    //         }


    //         return response()->json([
    //             "status"  => true,
    //             "message" => "Cash-In Records Retrieved Successfully!",
    //             "data"    => $output
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             "status" => false,
    //             "message" => $e->getMessage(),
    //         ], 409);
    //     }
    // }


    public function getCashinRecords(Request $request): JsonResponse
    {
        try {

            // -------------------------------
            // Validate inputs
            // -------------------------------
            $validator = Validator::make($request->all(), [
                'from_date' => ['required'],
                'to_date'   => ['required'],
                'user_id'   => ['nullable', 'exists:users,id'],
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            $fromDate = Carbon::createFromFormat('d/m/Y', $request->from_date)->format('Y-m-d');
            $toDate   = Carbon::createFromFormat('d/m/Y', $request->to_date)->format('Y-m-d');
            $userId = $request->user_id;


            // ---------------------------------------------
            // FETCH CUSTOMER PAYMENTS (Correct Cash-In)
            // ---------------------------------------------
            $payments = DB::table('customer_payments')
                ->select(
                    'customer_payments.*',
                    'users.first_name',
                    'users.last_name',
                    'payment_types.name as payment_type_name'
                )
                ->leftJoin('users', 'users.id', '=', 'customer_payments.created_by')

                // FIX: join to payment_types using payment_type column
                ->leftJoin('payment_types', 'payment_types.id', '=', 'customer_payments.payment_type')

                ->whereBetween(DB::raw('DATE(customer_payments.payment_date)'), [$fromDate, $toDate])
                ->when($userId, fn($q) => $q->where('customer_payments.created_by', $userId))
                ->orderBy('customer_payments.payment_date', 'ASC')
                ->get();

            if ($payments->isEmpty()) {
                throw new \Exception("No Records Found!");
            }


            // ---------------------------------------------
            // FORMAT OUTPUT
            // ---------------------------------------------
            $output = [];

            foreach ($payments as $p) {

                $output[] = [
                    "date"         => Carbon::parse($p->payment_date)->format('d/m/Y'),
                    "receipt_no"   => $p->id, // payment has no receipt_no, using id unless provided
                    "payment_type" => $p->payment_type_name ?? 'N/A',
                    "amount"       => (float)$p->amount,
                    "user_name"    => trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? '')) ?: 'N/A',
                ];
            }


            return response()->json([
               "status"     => true,
                "message"    => "Cash-In Records Retrieved Successfully!",
                "from_date"  => $request->from_date,
                "to_date"    => $request->to_date,
                "user_name"  => $payments->first()->first_name
                                ? trim($payments->first()->first_name . ' ' . $payments->first()->last_name)
                                : 'All Users',
                "data"       => $output
            ]);

        } catch (\Exception $e) {
            return response()->json([
                "status" => false,
                "message" => $e->getMessage(),
            ], 409);
        }
    }



    public function customerTotalSalePage()
    {
        return view('report.customer-total-report');
    }

    public function customerTotalSaleReport(Request $request): JsonResponse
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

            // Fetch customer info if a single customer was selected
            $customer = null;
            if ($partyId) {
                $c = Party::select('id', 'first_name', 'last_name')->find($partyId);
                $customer = $c ? [
                    'id' => $c->id,
                    'first_name' => $c->first_name,
                    'last_name' => $c->last_name,
                    'full_name' => trim($c->first_name . ' ' . $c->last_name),
                ] : null;
            }

            return response()->json([
                'status'  => true,
                'message' => "Records fetched successfully!",
                'data'    => $output,
                'customer' => $customer,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }


    public function DailyReportPage()
    {
        return view('report.daily-report'); 
    }


    // public function DailyReportData(Request $request)
    // {
    //     try {

    //         // Convert dd/mm/yyyy → yyyy-mm-dd
    //         $date = Carbon::createFromFormat('d/m/Y', $request->date)->format('Y-m-d');

    //         /* --------------------
    //         TODAY'S SALE
    //         -------------------- */
    //         $totalSale = DB::table('sale_orders')
    //             ->whereDate('order_date', $date)
    //             ->sum('grand_total');

    //         /* --------------------
    //         TODAY'S AMOUNT RECEIVED
    //         -------------------- */
    //         $amountReceived = DB::table('customer_payments')
    //             ->whereDate('payment_date', $date)
    //             ->sum('amount');

    //         /* --------------------
    //         TODAY'S EXPENSE
    //         -------------------- */
    //         $totalExpense = DB::table('expenses')
    //             ->whereDate('expense_date', $date)
    //             ->sum('paid_amount');

    //         /* --------------------
    //         TODAY'S TOTAL QUANTITY SOLD
    //         -------------------- */
    //         $totalQuantitySold = DB::table('item_transactions')
    //             ->where('transaction_type', 'Sale Order')
    //             ->whereDate('transaction_date', $date)
    //             ->sum('quantity');

    //         /* --------------------
    //         PURCHASE PAYMENT GIVEN
    //         -------------------- */
    //         $purchasePayment = DB::table('purchases')
    //             ->whereDate('purchase_date', $date)
    //             ->sum('paid_amount');

    //         return response()->json([
    //             'status'  => true,
    //             'message' => 'Daily report fetched successfully',
    //             'data' => [
    //                 'date'                  => $date,
    //                 'total_sale'            => (float) $totalSale,
    //                 'amount_received'       => (float) $amountReceived,
    //                 'total_expense'         => (float) $totalExpense,
    //                 'total_quantity_sold'   => (float) $totalQuantitySold,
    //                 'purchase_payment'      => (float) $purchasePayment,
    //             ]
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $e->getMessage()
    //         ], 409);
    //     }
    // }




    // public function DailyReportData(Request $request)
    // {
    //     try {

    //         $date = Carbon::createFromFormat('d/m/Y', $request->date)->format('Y-m-d');

    //         /* ===============================
    //         OPENING CASH (Before Selected Date)
    //         =============================== */

    //         $saleBefore = DB::table('sale_orders')
    //             ->whereDate('order_date', '<', $date)
    //             ->sum('grand_total');

    //         $recoveryBefore = DB::table('customer_payments')
    //             ->whereDate('payment_date', '<', $date)
    //             ->sum('amount');
            


    //         $expenseBefore = DB::table('expenses')
    //             ->whereDate('expense_date', '<', $date)
    //             ->sum('paid_amount');

    //         $supplierPaymentBefore = DB::table('purchases')
    //             ->whereDate('purchase_date', '<', $date)
    //             ->sum('paid_amount');

    //         $openingCash =
    //             $saleBefore
    //             + $recoveryBefore
    //             - $expenseBefore
    //             - $supplierPaymentBefore;

    //         /* ===============================
    //         TODAY'S VALUES
    //         =============================== */

    //         $totalSale = DB::table('sale_orders')
    //             ->whereDate('order_date', $date)
    //             ->sum('grand_total');

    //         $totalRecovery = DB::table('customer_payments')
    //             ->whereDate('payment_date', $date)
    //             ->sum('amount');
                

    //         $totalExpense = DB::table('expenses')
    //             ->whereDate('expense_date', $date)
    //             ->sum('paid_amount');

    //         $supplierPayment = DB::table('purchases')
    //             ->whereDate('purchase_date', $date)
    //             ->sum('paid_amount');

    //         $totalQuantitySold = DB::table('item_transactions')
    //             ->where('transaction_type', 'Sale Order')
    //             ->whereDate('transaction_date', $date)
    //             ->sum('quantity');

    //         /* ===============================
    //         CLOSING CASH
    //         =============================== */

    //         $closingCash =
    //             $openingCash
    //             + $totalSale
    //             + $totalRecovery
    //             - $totalExpense
    //             - $supplierPayment;

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Daily cash report fetched successfully',
    //             'data' => [
    //                 'date' => $date,
    //                 'opening_cash' => round($openingCash, 2),
    //                 'total_sale' => round($totalSale, 2),
    //                 'total_recovery' => round($totalRecovery, 2),
    //                 'total_expense' => round($totalExpense, 2),
    //                 'supplier_payment' => round($supplierPayment, 2),
    //                 'closing_cash' => round($closingCash, 2),
    //                 'total_quantity_sold' => $totalQuantitySold,
    //             ]
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $e->getMessage()
    //         ], 409);
    //     }
    // }





    public function DailyReportData(Request $request)
    {
        try {

            $date = Carbon::createFromFormat('d/m/Y', $request->date)->format('Y-m-d');

            /* ===============================
            OPENING CASH
            =============================== */

            $saleBefore = DB::table('sale_orders')
                ->whereDate('order_date', '<', $date)
                ->sum('grand_total');

            $recoveryBefore = DB::table('customer_payments')
                ->whereDate('payment_date', '<', $date)
                ->sum('amount');

            $expenseBefore = DB::table('expenses')
                ->whereDate('expense_date', '<', $date)
                ->sum('paid_amount');

            $supplierPaymentBefore = DB::table('purchases')
                ->whereDate('purchase_date', '<', $date)
                ->sum('paid_amount');

            // $openingCash =
            //     $saleBefore
            //     + $recoveryBefore
            //     - $expenseBefore
            //     - $supplierPaymentBefore;
            $openingCash =
                $recoveryBefore
                - $expenseBefore
                - $supplierPaymentBefore;

            /* ===============================
            TODAY VALUES
            =============================== */

            $totalSale = DB::table('sale_orders')
                ->whereDate('order_date', $date)
                ->sum('grand_total');

            $totalRecovery = DB::table('customer_payments')
                ->whereDate('payment_date', $date)
                ->sum('amount');

            // ✅ Recovery breakdown by user
            $recoveryByUser = DB::table('customer_payments')
                ->join('users', 'users.id', '=', 'customer_payments.created_by')
                ->whereDate('customer_payments.payment_date', $date)
                ->select(
                    'users.username as user_name',
                    DB::raw('SUM(customer_payments.amount) as total_collected')
                )
                ->groupBy('users.username')
                ->get();

            $totalExpense = DB::table('expenses')
                ->whereDate('expense_date', $date)
                ->sum('paid_amount');

            $supplierPayment = DB::table('purchases')
                ->whereDate('purchase_date', $date)
                ->sum('paid_amount');

            $totalQuantitySold = DB::table('item_transactions')
                ->where('transaction_type', 'Sale Order')
                ->whereDate('transaction_date', $date)
                ->sum('quantity');

            /* ===============================
            CLOSING CASH
            =============================== */

            // $closingCash =
            //     $openingCash
            //     + $totalSale
            //     + $totalRecovery
            //     - $totalExpense
            //     - $supplierPayment;

            $closingCash =
                $openingCash
                + $totalRecovery
                - $totalExpense
                - $supplierPayment;

            return response()->json([
                'status' => true,
                'message' => 'Daily cash report fetched successfully',
                'data' => [
                    'date' => $date,
                    'opening_cash' => round($openingCash, 2),
                    'total_sale' => round($totalSale, 2),
                    'total_recovery' => round($totalRecovery, 2),
                    'total_expense' => round($totalExpense, 2),
                    'supplier_payment' => round($supplierPayment, 2),
                    'closing_cash' => round($closingCash, 2),
                    'total_quantity_sold' => $totalQuantitySold,
                    'recovery_by_user' => $recoveryByUser
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 409);
        }
    }




    


}
