<?php

namespace App\Http\Controllers;

use App\Enums\App;
use App\Models\CustomerPayment;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Party\Party;
use App\Models\Prefix;
use App\Models\Sale\SaleOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class OrderPaymentController extends Controller
{
    protected $companyId;

    public function __construct()
    {
        $this->companyId = App::APP_SETTINGS_RECORD_ID->value;
    }

    public function getLastCountId(){
        return Order::select('count_id')->orderBy('id', 'desc')->first()?->count_id ?? 0;
    }

    public function create()
    {
        $prefix = Prefix::findOrNew($this->companyId);
        $categories = DB::table('party_categories')->where('status', 1)->pluck('name', 'id');
        $lastCountId = $this->getLastCountId();
        $data = [
            'prefix_code' => $prefix->order,
            'count_id' => ($lastCountId+1),
        ];
        // $customers = DB::table('parties')->where('party_type','customer')->where('status',1)->get();
        $user = auth()->user();

        $customersQuery = DB::table('parties')
            ->where('party_type', 'customer')
            ->where('status', 1);

        if ($user->role_id != 1) {
            // Not admin, so only get customers created by this user
            $customersQuery->where('created_by', $user->id);
        }

        $customers = $customersQuery->get();

        $users = DB::table('users')->where('status', 1)->get();
        return view('order_payments.create',compact('data','categories','customers','users'));
    }

    // public function getCustomerOrders(Request $request)
    // {
    //     $customerId = $request->customer_id;

    //     $orders = SaleOrder::where('party_id', $customerId)
            
    //         ->get();

    //     // You can return a Blade partial or raw HTML
    //     // return view('partials.customer_orders', compact('orders'));

    //     return response()->json([
    //         'html' => view('order_payments.partials.customer_orders', compact('orders'))->render(),
    //         'orders' => $orders,
    //     ]);



    // }


    // public function getCustomerOrders(Request $request)
    // {
    //     $customerId = $request->customer_id;

    //     // Fetch customer orders
    //     $orders = SaleOrder::where('party_id', $customerId)->get();

    //     // Total order amount
    //     $totalOrders = $orders->sum('grand_total');

    //     // Total paid from payments table
    //     $totalPaid = CustomerPayment::where('party_id', $customerId)->sum('paid_amount');

    //     // Remaining
    //     $remaining = $totalOrders - $totalPaid;

    //     return response()->json([
    //         'html' => view('order_payments.partials.customer_orders', compact('orders', 'totalOrders', 'totalPaid', 'remaining'))->render(),
    //         'orders' => $orders,
    //     ]);
    // }


    public function getCustomerOrders(Request $request)
    {
        $customerId = $request->customer_id;

        // Fetch customer orders
        $orders = SaleOrder::where('party_id', $customerId)->get();

        // Total order amount
        $totalOrders = $orders->sum('grand_total');

        // Total paid (sum of payments.amount)
        $totalPaid = CustomerPayment::where('party_id', $customerId)->sum('amount');

        // Remaining
        $remaining = $totalOrders - $totalPaid;

        return response()->json([
            'html' => view('order_payments.partials.customer_orders', compact('orders', 'totalOrders', 'totalPaid', 'remaining'))->render(),
            'orders' => $orders,
        ]);
    }



    // public function CustomerOrdersPaymentStore(Request $request)
    // {
    //     // dd($request->all());
    //     $request->validate()
    // }


    // public function CustomerOrdersPaymentStore(Request $request)
    // {
    //     // dd($request->all());
    //     $validated = $request->validate([
    //         'party_id' => 'required|exists:parties,id',
    //         'amount' => 'required|numeric',
    //         'payment_type_id' => 'required',
    //         'payment_note' => 'nullable|string|max:255',
    //         'payment_date' => 'required',
    //     ]);


    //     // Proceed to save the payment
       

    //     // Get all active orders for this customer
    //     $orders = SaleOrder::where('party_id', $validated['party_id'])->get();

    //     // Sum total due from orders
    //     $totalAmount = $orders->sum('grand_total');

    //     // Sum total paid so far by the customer
    //     $totalPaid = CustomerPayment::where('party_id', $validated['party_id'])->sum('amount');

    //     // Add current payment to total paid
    //     $newTotalPaid = $totalPaid + $validated['amount'];

    //     // Calculate remaining
    //     $remainingAmount = max($totalAmount - $newTotalPaid, 0);

    //     // Save payment
    //     $payment = CustomerPayment::create([
    //         'party_id' => $validated['party_id'],
    //         'amount' => $validated['amount'],
    //         'payment_type' => $validated['payment_type_id'], // adjust key name if needed
    //         'total_amount' => $totalAmount,
    //         'paid_amount' => $newTotalPaid,
    //         'remaining_amount' => $remainingAmount,
    //         'payment_note' => $validated['payment_note'],
    //         'payment_date' => $validated['payment_date'],
    //     ]);

    //     return redirect()->back()->with('success', 'Payment recorded successfully.');

    // }

    public function CustomerOrdersPaymentStore(Request $request)
    {
        $validated = $request->validate([
            'party_id' => 'required|exists:parties,id',
            'amount' => 'required|numeric',
            'payment_type_id' => 'required',
            'payment_note' => 'nullable|string|max:255',
            'payment_date' => 'required',
            'user' => 'nullable',
        ]);
        try {
        DB::beginTransaction();

        // Get all orders of customer
        $orders = SaleOrder::where('party_id', $validated['party_id'])->get();
        $totalAmount = $orders->sum('grand_total');

        // Already paid before this new payment
        $alreadyPaid = CustomerPayment::where('party_id', $validated['party_id'])->sum('amount');

        // Remaining before this payment
        $remainingBefore = $totalAmount - $alreadyPaid;

        // Check if already fully paid
        if ($remainingBefore <= 0) {
             DB::rollBack();
            return redirect()->back()->with('info', 'Customer has already paid all dues. No remaining balance.');
        }

        // If entered amount is more than remaining, cap it
        $paymentAmount = min($validated['amount'], $remainingBefore);

        // New totals
        $newTotalPaid = $alreadyPaid + $paymentAmount;
        $remainingAmount = $totalAmount - $newTotalPaid;
         $validated['payment_date'] = Carbon::createFromFormat('d/m/Y', $validated['payment_date'])->format('Y-m-d');
        // Save payment
        $payment = CustomerPayment::create([
            'party_id' => $validated['party_id'],
            'amount' => $paymentAmount,
            'payment_type' => $validated['payment_type_id'],
            'total_amount' => $totalAmount,
            'paid_amount' => $newTotalPaid,
            'remaining_amount' => $remainingAmount,
            'payment_note' => $validated['payment_note'],
            'payment_date' => $validated['payment_date'],
            'created_by' => $validated['user'] ?? auth()->id(),
            'updated_by' => $validated['user'] ?? auth()->id(),
        ]);

          DB::commit();

        // Message if capped
        if ($validated['amount'] > $remainingBefore) {
            return redirect()->back()->with('error', 'Customer tried to pay more than remaining. Only ' . number_format($remainingBefore, 2) . ' was accepted.');
        }

        return redirect()->route('order.payment.history.list')->with('info', 'Payment recorded successfully.');
         } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Payment failed: ' . $e->getMessage());
        }
    }


    public function CustomerOrdersPaymentHistory()
    {
        $data = CustomerPayment::with('party')->get();
        //  dd($data);
        return view('order_payments.list');
    }

    // public function paymentHistoryDatatable(Request $request)
    // {
    //     $data = CustomerPayment::with('party')->get();
    //     //  dd($data);
    //     return DataTables::of($data)
    //         ->addIndexColumn()
    //         ->addColumn('customer_name', function ($row) {
    //             return $row->party->first_name .' '. $row->party->last_name ;
    //         })
    //         ->addColumn('mobile', function ($row) {
    //             return $row->party->mobile ?? '';
    //         })
    //         ->addColumn('paid_amount', function ($row) {
    //             return number_format($row->paid_amount, 2);
    //         })
    //         ->addColumn('remaining_amount', function ($row) {
    //             return number_format($row->remaining_amount, 2);
    //         })
    //         ->addColumn('created_at', function ($row) {
    //             return $row->created_at->format(app('company')['date_format']);
    //         })
    //         ->addColumn('payment_date', function ($row) {
    //             return $row->payment_date;
    //         })
    //         ->addColumn('total_amount', function ($row) {
    //             return $row->total_amount ?? '';
    //         })
    //         ->addColumn('action', function ($row) {
    //             $id = $row->id;
    //             $deleteUrl = route('order.payment.delete', ['id' => $id]);
    //             // $viewUrl = route('order.payment.view', ['id' => $id]);
    //             //  <li>
    //             //         <a class="dropdown-item" href="' . $viewUrl . '"></i><i class="bx bx-show-alt"></i> '.__('Details').'</a>
    //             //     </li>

    //             $actionBtn = '<div class="dropdown ms-auto">
    //                 <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown">
    //                     <i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
    //                 </a>
    //                 <ul class="dropdown-menu">
                   
    //                     <li>
    //                         <button type="button" class="dropdown-item text-danger deleteRequest" data-delete-id='.$id.'>
    //                             <i class="bx bx-trash"></i> '.__('app.delete').'
    //                         </button>
    //                     </li>
    //                 </ul>
    //             </div>';
    //             return $actionBtn;
    //         })
    //         ->rawColumns(['action'])
    //         ->make(true);
    // }


    // public function paymentHistoryDatatable(Request $request)
    // {
    //     $data = CustomerPayment::with('party')->get();

    //     return DataTables::of($data)
    //         ->addIndexColumn()
    //         ->addColumn('customer_name', function ($row) {
    //             return $row->party->first_name . ' ' . $row->party->last_name;
    //         })
    //         ->addColumn('mobile', function ($row) {
    //             return $row->party->mobile ?? '';
    //         })
    //         ->addColumn('paid_amount', function ($row) {
    //             // This row's payment (atomic transaction)
    //             return number_format($row->amount, 2);
    //         })
    //         ->addColumn('total_amount', function ($row) {
    //             // Sum all orders for this customer
    //             return number_format(
    //                 SaleOrder::where('party_id', $row->party_id)->sum('grand_total'),
    //                 2
    //             );
    //         })
    //         ->addColumn('remaining_amount', function ($row) {
    //             $totalOrders = SaleOrder::where('party_id', $row->party_id)->sum('grand_total');
    //             $totalPaid   = CustomerPayment::where('party_id', $row->party_id)->sum('amount');
    //             $remaining   = $totalOrders - $totalPaid;
    //             return number_format(max($remaining, 0), 2);
    //         })
    //         ->addColumn('created_at', function ($row) {
    //             return $row->created_at->format(app('company')['date_format']);
    //         })
    //         ->addColumn('payment_date', function ($row) {
    //             return $row->payment_date;
    //         })
    //         ->addColumn('action', function ($row) {
    //             $id = $row->id;
    //             $deleteUrl = route('order.payment.delete', ['id' => $id]);

    //             $actionBtn = '<div class="dropdown ms-auto">
    //                 <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown">
    //                     <i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
    //                 </a>
    //                 <ul class="dropdown-menu">
    //                     <li>
    //                         <button type="button" class="dropdown-item text-danger deleteRequest" data-delete-id='.$id.'>
    //                             <i class="bx bx-trash"></i> '.__('app.delete').'
    //                         </button>
    //                     </li>
    //                 </ul>
    //             </div>';
    //             return $actionBtn;
    //         })
    //         ->rawColumns(['action'])
    //         ->make(true);
    // }

    public function getDateFormats(): array
    {
        return ['d-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d'];
    }
    protected function toSystemDateFormat($dateInput)
    {
        foreach ($this->getDateFormats() as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateInput);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                // Skip to the next format
            }
        }

        return null;
    }


    public function paymentHistoryDatatable(Request $request)
    {
        $user = auth()->user();

        // Base query
        $query = CustomerPayment::with('party');
        // dd($query);


        if ($user->role_id == 1 && $request->filled('user_id')) {
            $salesmanId = $request->input('user_id');

            // ✅ Filtering directly on CustomerPayment.created_by
            $query->where('created_by', $salesmanId);
        }

        if ($user->role_id != 1) {
            $query->where('created_by', auth()->user()->id);
        }

        // 📅 Filter by from_date
        if ($request->filled('from_date')) {
            $query->whereDate('payment_date', '>=', $this->toSystemDateFormat($request->from_date));
        }

        // 📅 Filter by to_date
        if ($request->filled('to_date')) {
            $query->whereDate('payment_date', '<=', $this->toSystemDateFormat($request->to_date));
        }

        $data = $query->get();

        // Optional filter: show only customers who reached credit limit
        if ($request->filled('reached_credit_limit') && $request->reached_credit_limit == true) {
            $data = $data->filter(function ($row) {
                $totalOrders = SaleOrder::where('party_id', $row->party_id)->sum('grand_total');
                $totalPaid   = CustomerPayment::where('party_id', $row->party_id)->sum('amount');
                $remaining   = $totalOrders - $totalPaid;

                $creditLimit = Party::where('id', $row->party_id)->value('credit_limit');

                return $remaining >= $creditLimit; // or $remaining > $creditLimit based on your preference
            });
        }


        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('customer_name', function ($row) {
                return $row->party->first_name . ' ' . $row->party->last_name;
            })
            ->addColumn('mobile', function ($row) {
                return $row->party->mobile ?? '';
            })
            ->addColumn('paid_amount', function ($row) {
                return number_format($row->amount, 2);
            })
            ->addColumn('total_amount', function ($row) {
                return number_format(
                    SaleOrder::where('party_id', $row->party_id)->sum('grand_total'),
                    2
                );
            })
            ->addColumn('remaining_amount', function ($row) {
                // $totalOrders = SaleOrder::where('party_id', $row->party_id)->sum('grand_total');
                // $totalPaid   = CustomerPayment::where('party_id', $row->party_id)->sum('amount');
                // $remaining   = $totalOrders - $totalPaid;
                return number_format(max($row->remaining_amount, 0), 2);
            })
            ->addColumn('credit_limit', function ($row) {
               $totalLimit = Party::where('party_type', 'customer')
                ->where('id', $row->party_id)
                ->sum('credit_limit');

            if ($totalLimit == 0) {
                return '<span style="background-color: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px;">No credit limit</span>';
            } else {
                $formatted = number_format($totalLimit, 2);
                return '<span style="background-color: #67b0f0; color: #fff; padding: 4px 8px; border-radius: 4px;">' . $formatted . '</span>';
            }

            })
            ->addColumn('created_by', function ($row) {
                return $row->createdBy->username ?? '—';
            })
            ->addColumn('created_at', function ($row) {
                return $row->created_at->format(app('company')['date_format']);
            })
            ->addColumn('payment_date', function ($row) {
                return $row->payment_date;
            })
            ->addColumn('action', function ($row) {
                $id = $row->id;
                $deleteUrl = route('order.payment.delete', ['id' => $id]);

                return '<div class="dropdown ms-auto">
                    <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown">
                        <i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <button type="button" class="dropdown-item text-danger deleteRequest" data-delete-id='.$id.'>
                                <i class="bx bx-trash"></i> '.__('app.delete').'
                            </button>
                        </li>
                    </ul>
                </div>';
            })
            ->rawColumns(['credit_limit','action'])
            ->make(true);
    }



    public function CustomerOrdersPaymentDelete(Request $request) : JsonResponse
    {

        $selectedRecordIds = $request->input('record_ids');

        // Perform validation for each selected record ID
        foreach ($selectedRecordIds as $recordId) {
            $record = CustomerPayment::find($recordId);
            if (!$record) {
                // Invalid record ID, handle the error (e.g., show a message, log, etc.)
                return response()->json([
                    'status'    => false,
                    'message' => __('app.invalid_record_id',['record_id' => $recordId]),
                ]);

            }
            // You can perform additional validation checks here if needed before deletion
        }

        /**
         * All selected record IDs are valid, proceed with the deletion
         * Delete all records with the selected IDs in one query
         * */
        CustomerPayment::whereIn('id', $selectedRecordIds)->delete();

        return response()->json([
            'status'    => true,
            'message' => __('app.record_deleted_successfully'),
        ]);
    }


    public function CustomerOrdersPaymentDetail($id)
    {
        dd($id);
    }


}
