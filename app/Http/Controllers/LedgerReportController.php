<?php

namespace App\Http\Controllers;

use App\Models\Sale\SaleOrder;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CustomerPayment;
use App\Models\Party\Party;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;


class LedgerReportController extends Controller
{
    public function index()
    {
        return view('report.ledger-report');
    }

//    public function getLedgerRecords(Request $request): JsonResponse
//     {
//         try {
//             // ✅ Validation rules
//             // $rules = [
//             //     'from_date' => ['required', 'date_format:' . implode(',', $this->getDateFormats())],
//             //     'to_date'   => ['required', 'date_format:' . implode(',', $this->getDateFormats())],
//             // ];

//             // $validator = Validator::make($request->all(), $rules);

//             // if ($validator->fails()) {
//             //     throw new \Exception($validator->errors()->first());
//             // }

//             $fromDate = Carbon::createFromFormat('d/m/Y', $request->from_date)->format('Y-m-d');
//             $toDate   = Carbon::createFromFormat('d/m/Y', $request->to_date)->format('Y-m-d');

//             $partyId = $request->party_id;
//             $userId  = $request->user_id;

//             $data = SaleOrder::with('party', 'user')
//                 // ->when($partyId, fn($q) => $q->where('party_id', $partyId))
//                 // ->when($userId, fn($q) => $q->where('created_by', $userId))
//                 ->whereDate('order_date', '>=', $fromDate)
//                 ->whereDate('order_date', '<=', $toDate)
//                 ->get();

//                  dd($data->party);

//             // --------------------------
//             // GROUP DATA BY CUSTOMER
//             // --------------------------
//             $output = [];

//             foreach ($data as $order) {

//                 $partyName = $order->party->first_name . ' ' . $order->party->last_name;
//                 $userName  = $order->user->first_name . ' ' . $order->user->last_name;

//                 if (!isset($output[$order->party_id])) {

//                     $output[$order->party_id] = [
//                         "party_name" => $partyName,
//                         "user_name"  => $userName,
//                         "records"    => []
//                     ];
//                 }

//                 $output[$order->party_id]["records"][] = [
//                     "date"   => $order->order_date,
//                     "amount" => (float)$order->grand_total,
//                 ];
//             }

//             // Re-index array
//             $output = array_values($output);

//             return response()->json([
//                 'status'  => true,
//                 'message' => "Records fetched successfully!",
//                 'data'    => $output
//             ]);

//         } catch (\Exception $e) {
//             return response()->json([
//                 'status' => false,
//                 'message' => $e->getMessage(),
//             ], 409);
//         }
//     }



    public function getLedgerRecords(Request $request): JsonResponse
    {
        try {

            // -------------------------------
            // Validate
            // -------------------------------
            $validator = Validator::make($request->all(), [
                'from_date' => ['required'],
                'to_date'   => ['required'],
                'user_id'   => ['nullable', 'exists:users,id'],
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            // -------------------------------
            // Convert dates (dd/mm/yyyy → Carbon)
            // -------------------------------
            $fromDate = Carbon::createFromFormat('d/m/Y', $request->from_date)->startOfDay();
            $toDate   = Carbon::createFromFormat('d/m/Y', $request->to_date)->endOfDay();

            $userId = $request->user_id;

            // -------------------------------
            // Fetch ALL customers having orders or payments
            // -------------------------------
            $customers = Party::where('party_type', 'customer')
                ->with([
                            'saleOrders' => function ($q) use ($fromDate, $toDate, $userId) {
                        $q->whereBetween('order_date', [$fromDate, $toDate]);
                        if ($userId) {
                            $q->where('created_by', $userId);
                        }
                    },
                            'saleReturns' => function ($q) use ($fromDate, $toDate, $userId) {
                                $q->whereBetween('return_date', [$fromDate, $toDate]);
                                if ($userId) {
                                    $q->where('created_by', $userId);
                                }
                            },
                            'customerPayments' => function ($q) use ($fromDate, $toDate, $userId) {
                        $q->whereBetween('payment_date', [$fromDate, $toDate]);
                        if ($userId) {
                            $q->where('created_by', $userId);
                        }
                    },
                    'user'
                ])
                ->get();

            if ($customers->isEmpty()) {
                throw new \Exception("No Records Found!");
            }

            // -------------------------------
            // Build Ledger Output
            // -------------------------------
            $output = [];

            foreach ($customers as $c) {

                $totalSale = $c->saleOrders->sum('grand_total');
                $totalReturns = $c->saleReturns->sum('grand_total') ?? 0;
                $totalPaid = $c->customerPayments->sum('amount');
                $remaining = $totalSale - $totalReturns - $totalPaid;

                // Only add customers who have ANY activity
                if ($totalSale == 0 && $totalPaid == 0) {
                    continue;
                }

                $output[] = [
                    "customer_name"   => $c->first_name . ' ' . $c->last_name,
                    "mobile"          => $c->mobile,
                    "total_sale"      => (float)$totalSale,
                    "total_paid"      => (float)$totalPaid,
                    "total_returns"   => (float)$totalReturns,
                    "remaining"       => (float)$remaining,
                    "created_by"      => $c->createdBy->username ?? "—",
                    "records" => [

                        // List of orders in detail
                        "orders" => $c->saleOrders->map(function ($o) {
                            return [
                                "date"   => Carbon::parse($o->order_date)->format('d/m/Y'),
                                "amount" => (float)$o->grand_total
                            ];
                        })->toArray(),

                        // List of payments in detail
                        "payments" => $c->customerPayments->map(function ($p) {
                            return [
                                "date"   => Carbon::parse($p->payment_date)->format('d/m/Y'),
                                "amount" => (float)$p->amount,
                                "receipt_no" => $p->receipt_no
                            ];
                        })->toArray(),
                        // List of sale returns in detail
                        "returns" => $c->saleReturns->map(function ($r) {
                            return [
                                "date"   => Carbon::parse($r->return_date)->format('d/m/Y'),
                                "amount" => (float)$r->grand_total,
                                "return_code" => $r->return_code ?? null
                            ];
                        })->toArray(),

                    ]
                ];
            }

            return response()->json([
                "status"  => true,
                "message" => "Customer Ledger Retrieved Successfully!",
                "data"    => $output
            ]);

        } catch (\Exception $e) {
            return response()->json([
                "status" => false,
                "message" => $e->getMessage(),
            ], 409);
        }
    }


    public function customerLedger()
    {
        return view('report.customer-ledger-report');
    }


    // public function customerLedgerReport(Request $request): JsonResponse
    // {
    //     try {

    //         // -------------------------------
    //         // Validate
    //         // -------------------------------
    //         $validator = Validator::make($request->all(), [
    //             'from_date' => ['required'],
    //             'to_date'   => ['required'],
    //             'party_id'  => ['required', 'exists:parties,id'],
    //         ]);

    //         if ($validator->fails()) {
    //             throw new \Exception($validator->errors()->first());
    //         }

    //         // Format dates
    //         $fromDate = Carbon::createFromFormat('d/m/Y', $request->from_date)->startOfDay();
    //         $toDate   = Carbon::createFromFormat('d/m/Y', $request->to_date)->endOfDay();
    //         $partyId  = $request->party_id;

    //         // -------------------------------
    //         // Fetch Orders (Debit)
    //         // -------------------------------
    //         $orders = SaleOrder::where('party_id', $partyId)
    //             ->whereBetween('order_date', [$fromDate, $toDate])
    //             ->get()
    //             ->map(function ($o) {
    //                 return [
    //                     "date"        => $o->order_date,
    //                     "description" => "Order #{$o->order_code}",
    //                     "debit"       => (float)$o->grand_total,
    //                     "credit"      => null
    //                 ];
    //             });

    //         // -------------------------------
    //         // Fetch Payments (Credit)
    //         // -------------------------------
    //         $payments = CustomerPayment::where('party_id', $partyId)
    //             ->whereBetween('payment_date', [$fromDate, $toDate])
    //             ->get()
    //             ->map(function ($p) {
    //                 return [
    //                     "date"        => $p->payment_date,
    //                     "description" => "Payment",
    //                     "debit"       => null,
    //                     "credit"      => (float)$p->amount
    //                 ];
    //             });

    //         // -------------------------------
    //         // Merge + Sort by Date
    //         // -------------------------------
    //         $ledger = $orders->merge($payments)
    //             ->sortBy('date')
    //             ->values();

    //         if ($ledger->isEmpty()) {
    //             throw new \Exception("No Ledger Records Found!");
    //         }

    //         // -------------------------------
    //         // Running Balance
    //         // -------------------------------
    //         $runningBalance = 0;

    //         $ledger = $ledger->map(function ($row) use (&$runningBalance) {

    //             // Debit increases balance
    //             if ($row["debit"]) {
    //                 $runningBalance += $row["debit"];
    //             }

    //             // Credit decreases balance
    //             if ($row["credit"]) {
    //                 $runningBalance -= $row["credit"];
    //             }

    //             $row["balance"] = $runningBalance;
    //             return $row;
    //         });

    //         return response()->json([
    //             "status"  => true,
    //             "message" => "Ledger Retrieved Successfully!",
    //             "data"    => $ledger
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             "status" => false,
    //             "message" => $e->getMessage(),
    //         ], 409);
    //     }
    // }



    // public function customerLedgerReport(Request $request): JsonResponse
    // {
    //     dd($request->all());
    //     try {

    //         // ---------------------------------
    //         // VALIDATION
    //         // ---------------------------------
    //         $validator = Validator::make($request->all(), [
    //             'from_date' => ['required'],
    //             'to_date'   => ['required'],
    //             'party_id'  => ['required', 'exists:parties,id'],
    //         ]);

    //         if ($validator->fails()) {
    //             throw new \Exception($validator->errors()->first());
    //         }

    //         // ---------------------------------
    //         // DATE FORMAT
    //         // ---------------------------------
    //         $fromDate = Carbon::createFromFormat('d/m/Y', $request->from_date)->startOfDay();
    //         $toDate   = Carbon::createFromFormat('d/m/Y', $request->to_date)->endOfDay();
    //         $partyId  = $request->party_id;

    //         // ---------------------------------
    //         // CUSTOMER DETAILS (NEW)
    //         // ---------------------------------
    //         $customer = Party::select(
    //                 'id',
    //                 'first_name',
    //                 'address',
    //                 'phone',
    //                 'cnic',
    //                 'email'
    //             )
    //             ->findOrFail($partyId);

    //         // ---------------------------------
    //         // OPENING BALANCE
    //         // ---------------------------------
    //         $openingDebit = SaleOrder::where('party_id', $partyId)
    //             ->where('order_date', '<', $fromDate)
    //             ->sum('grand_total');

    //         $openingCredit = CustomerPayment::where('party_id', $partyId)
    //             ->where('payment_date', '<', $fromDate)
    //             ->sum('amount');

    //         $openingBalance = $openingDebit - $openingCredit;

    //         // ---------------------------------
    //         // SALES (DEBIT)
    //         // ---------------------------------
    //         $orders = SaleOrder::where('party_id', $partyId)
    //             ->whereBetween('order_date', [$fromDate, $toDate])
    //             ->get()
    //             ->map(function ($o) {
    //                 return [
    //                     'date'        => Carbon::parse($o->order_date)->format('d/m/Y'),
    //                     'description' => "Invoice ({$o->order_code})",
    //                     'debit'       => (float) $o->grand_total,
    //                     'credit'      => 0,
    //                 ];
    //             });

    //         // ---------------------------------
    //         // PAYMENTS (CREDIT)
    //         // ---------------------------------
    //         $payments = CustomerPayment::where('party_id', $partyId)
    //             ->whereBetween('payment_date', [$fromDate, $toDate])
    //             ->get()
    //             ->map(function ($p) {
    //                 return [
    //                     'date'        => Carbon::parse($p->payment_date)->format('d/m/Y'),
    //                     'description' => "Collection ({$p->payment_type})",
    //                     'debit'       => 0,
    //                     'credit'      => (float) $p->amount,
    //                 ];
    //             });

    //         // ---------------------------------
    //         // MERGE & SORT
    //         // ---------------------------------
    //         $ledger = $orders->merge($payments)
    //             ->sortBy('date')
    //             ->values();

    //         if ($ledger->isEmpty() && $openingBalance == 0) {
    //             throw new \Exception("No Ledger Records Found!");
    //         }

    //         // ---------------------------------
    //         // RUNNING BALANCE
    //         // ---------------------------------
    //         $runningBalance = $openingBalance;
    //         $totalDebit = 0;
    //         $totalCredit = 0;

    //         $ledger = $ledger->map(function ($row) use (&$runningBalance, &$totalDebit, &$totalCredit) {

    //             $totalDebit  += $row['debit'];
    //             $totalCredit += $row['credit'];

    //             $runningBalance += $row['debit'];
    //             $runningBalance -= $row['credit'];

    //             $row['balance'] = $runningBalance;
    //             return $row;
    //         });

    //         return response()->json([
    //             'status'          => true,

    //             // ✅ CUSTOMER INFO
    //             'customer'        => $customer,

    //             // ✅ LEDGER INFO
    //             'opening_balance' => $openingBalance,
    //             'total_debit'     => $totalDebit,
    //             'total_credit'    => $totalCredit,
    //             'closing_balance' => $runningBalance,
    //             'data'            => $ledger,
    //         ]);

    //     } catch (\Exception $e) {

    //         return response()->json([
    //             'status'  => false,
    //             'message' => $e->getMessage(),
    //         ], 409);
    //     }
    // }



    public function customerLedgerReport(Request $request): JsonResponse
    {
        try {

            // ---------------------------------
            // VALIDATION
            // ---------------------------------
            $validator = Validator::make($request->all(), [
                'from_date' => ['required'],
                'to_date'   => ['required'],
                'party_id'  => ['required', 'exists:parties,id'],
                'user_id'   => ['nullable', 'exists:users,id'], // ✅ added
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            // ---------------------------------
            // DATE FORMAT
            // ---------------------------------
            $fromDate = Carbon::createFromFormat('d/m/Y', $request->from_date)->startOfDay();
            $toDate   = Carbon::createFromFormat('d/m/Y', $request->to_date)->endOfDay();
            $partyId  = $request->party_id;

            // ---------------------------------
            // CUSTOMER DETAILS
            // ---------------------------------
            $customer = Party::select(
                    'id',
                    'first_name',
                    'last_name',
                    'phone',
                )
                ->findOrFail($partyId);

            // ---------------------------------
            // USER DETAILS (NEW – THIS WAS MISSING)
            // ---------------------------------
            $user = null;

            if ($request->filled('user_id')) {
                $user = User::select(
                        'id',
                        'username',
                        'email',
                    )
                    ->find($request->user_id);
            }

            // ---------------------------------
            // OPENING BALANCE
            // ---------------------------------
            $openingDebit = SaleOrder::where('party_id', $partyId)
                ->where('order_date', '<', $fromDate)
                ->sum('grand_total');

            $openingCredit = CustomerPayment::where('party_id', $partyId)
                ->where('payment_date', '<', $fromDate)
                ->sum('amount');

            // Include sale returns in opening calculation (returns reduce the customer's balance)
            $openingReturns = \App\Models\Sale\SaleReturn::where('party_id', $partyId)
                ->where('return_date', '<', $fromDate)
                ->sum('grand_total');

            $openingBalance = $openingDebit - $openingCredit - $openingReturns;

            // ---------------------------------
            // SALES (DEBIT)
            // ---------------------------------
            $orders = SaleOrder::where('party_id', $partyId)
                ->whereBetween('order_date', [$fromDate, $toDate])
                ->get()
                ->map(function ($o) {
                    $ts = Carbon::parse($o->order_date)->timestamp;
                    return [
                        'date'          => Carbon::parse($o->order_date)->format('d/m/Y'),
                        'description'   => "Invoice ({$o->order_code})",
                        'debit'         => (float) $o->grand_total,
                        'credit'        => 0,
                        'date_timestamp'=> $ts,
                        'type_order'    => 1, // sales first
                    ];
                });

            // ---------------------------------
            // PAYMENTS (CREDIT)
            // ---------------------------------
            $payments = CustomerPayment::where('party_id', $partyId)
                ->whereBetween('payment_date', [$fromDate, $toDate])
                ->get()
                ->map(function ($p) {
                    $ts = Carbon::parse($p->payment_date)->timestamp;
                    return [
                        'date'          => Carbon::parse($p->payment_date)->format('d/m/Y'),
                        'description'   => "Collection ({$p->payment_type})",
                        'debit'         => 0,
                        'credit'        => (float) $p->amount,
                        'date_timestamp'=> $ts,
                        'type_order'    => 3, // payments last
                    ];
                });

                // ---------------------------------
                // SALE RETURNS (CREDIT)
                // ---------------------------------
                $returns = \App\Models\Sale\SaleReturn::where('party_id', $partyId)
                    ->whereBetween('return_date', [$fromDate, $toDate])
                    ->get()
                    ->map(function ($r) {
                        $ts = Carbon::parse($r->return_date)->timestamp;
                        return [
                            'date'          => Carbon::parse($r->return_date)->format('d/m/Y'),
                            'description'   => "Sale Return ({$r->return_code})",
                            'debit'         => 0,
                            'credit'        => (float) $r->grand_total,
                            'date_timestamp'=> $ts,
                            'type_order'    => 2, // returns after sales, before payments
                        ];
                    });

            // ---------------------------------
            // MERGE & SORT
            // ---------------------------------
            // Merge orders, payments and returns so return records are visible and affect balance
            // Merge and sort by timestamp and type_order so payments appear last when on same date
            $ledger = $orders->merge($payments)->merge($returns)
                ->sortBy(function ($row) {
                    return ($row['date_timestamp'] ?? 0) * 10 + ($row['type_order'] ?? 0);
                })
                ->values();

            if ($ledger->isEmpty() && $openingBalance == 0) {
                throw new \Exception("No Ledger Records Found!");
            }

            // ---------------------------------
            // RUNNING BALANCE
            // ---------------------------------
            $runningBalance = $openingBalance;
            $totalDebit = 0;
            $totalCredit = 0;

            $ledger = $ledger->map(function ($row) use (&$runningBalance, &$totalDebit, &$totalCredit) {

                $totalDebit  += $row['debit'];
                $totalCredit += $row['credit'];

                $runningBalance += $row['debit'];
                $runningBalance -= $row['credit'];

                $row['balance'] = $runningBalance;
                // remove helper keys before returning
                if (isset($row['date_timestamp'])) unset($row['date_timestamp']);
                if (isset($row['type_order'])) unset($row['type_order']);
                return $row;
            });

            return response()->json([
                'status'          => true,

                // ✅ CUSTOMER
                'customer'        => $customer,

                // ✅ USER (NOW INCLUDED)
                'user'            => $user,
                'from_date'       => $fromDate->format('d/m/Y'),
                'to_date'         => $toDate->format('d/m/Y'),

                // ✅ LEDGER
                'opening_balance' => $openingBalance,
                'total_debit'     => $totalDebit,
                'total_credit'    => $totalCredit,
                'closing_balance' => $runningBalance,
                'data'            => $ledger,
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }




    







}
