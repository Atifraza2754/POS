<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Order;
use App\Models\Sale\SaleOrder;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Customer;
use App\Models\OrderPayment;
use App\Models\OrderedProduct;
use App\Traits\FormatNumber;
use Yajra\DataTables\Facades\DataTables;


use Illuminate\Support\Number;

use App\Models\Sale\Sale;
use App\Models\Sale\SaleReturn;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Party\Party;
use App\Models\Party\PartyTransaction;
use App\Models\Party\PartyPayment;
use App\Models\Expenses\Expense;
use App\Models\Items\Item;
use App\Models\Items\ItemTransaction;
use Carbon\Carbon;

class DashboardController extends Controller
{
    use formatNumber;


    public function index()
    {
        $pendingSaleOrders          = SaleOrder::whereDoesntHave('sale')
                                                ->when(auth()->user()->hasPermissionTo('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                                                    return $query->where('created_by', auth()->user()->id);
                                                })
                                                ->count();
        $totalCompletedSaleOrders   = SaleOrder::whereHas('sale')
                                                ->when(auth()->user()->hasPermissionTo('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                                                    return $query->where('created_by', auth()->user()->id);
                                                })
                                                ->count();

        $partyBalance               = $this->paymentReceivables();
        $totalPaymentReceivables    = $this->formatWithPrecision($partyBalance['receivable']);
        $totalPaymentPaybles        = $this->formatWithPrecision($partyBalance['payable']);

        $pendingPurchaseOrders          = PurchaseOrder::whereDoesntHave('purchase')
                                                ->when(auth()->user()->hasPermissionTo('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                                                    return $query->where('created_by', auth()->user()->id);
                                                })
                                                ->count();
        $totalCompletedPurchaseOrders   = PurchaseOrder::whereHas('purchase')
                                                ->when(auth()->user()->hasPermissionTo('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                                                    return $query->where('created_by', auth()->user()->id);
                                                })
                                                ->count();

        $totalCustomers       = Party::where('party_type', 'customer')
                                                ->when(auth()->user()->hasPermissionTo('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                                                    return $query->where('created_by', auth()->user()->id);
                                                })
                                                ->count();

        $totalExpense         = Expense::when(auth()->user()->hasPermissionTo('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                                                    return $query->where('created_by', auth()->user()->id);
                                                })
                                                ->sum('grand_total');
        $totalExpense         = $this->formatWithPrecision($totalExpense);

        // $recentInvoices       = Sale::when(auth()->user()->hasPermissionTo('dashboard.can.view.self.dashboard.details.only'), function ($query) {
        //                                             return $query->where('created_by', auth()->user()->id);
        //                                         })
        //                                         ->orderByDesc('id')
        //                                         ->limit(10)
        //                                         ->get();

        //  $lowStock       = Item::when(auth()->user()->hasPermissionTo('dashboard.can.view.self.dashboard.details.only'), function ($query) {
        //                                             return $query->where('min_stock', auth()->user()->id);
        //                                         })
        //                                         ->orderByDesc('id')
        //                                         ->paginate(5);

        $lowStock = Item::whereColumn('current_stock', '<=', 'min_stock') // compare current stock to min stock
        ->when(auth()->user()->hasPermissionTo('dashboard.can.view.self.dashboard.details.only'), function ($query) {
            return $query->where('user_id', auth()->user()->id); // filter to user's items if needed
        })

        

    ->orderByDesc('id')
    ->paginate(5);

    // $sales = SaleOrder::with([
    //     'party',
    //     'itemTransaction.item',
       
    //     ])->where('created_at', '>=', Carbon::now('UTC')->subDay())
    // ->get();

    

    // dd($sales);



        $saleVsPurchase       = $this->saleVsPurchase();
        $trendingItems        = $this->trendingItems();


        return view('dashboard', compact(
                                            'pendingSaleOrders',
                                            'pendingPurchaseOrders',

                                            'totalCompletedSaleOrders',
                                            'totalCompletedPurchaseOrders',

                                            'totalCustomers',
                                            'totalPaymentReceivables',
                                            'totalPaymentPaybles',
                                            'totalExpense',

                                            'saleVsPurchase',
                                            'trendingItems',
                                            'lowStock',
                                            // 'sales'
                                        ));
    }

    public function saleVsPurchase()
    {
        $labels = [];
        $sales = [];
        $purchases = [];

        $now = now();
        for ($i = 0; $i < 6; $i++) {
            $month = $now->copy()->subMonths($i)->format('M Y');
            $labels[] = $month;

            // Get value for this month, e.g. from database
            $sales[] = Sale::whereMonth('sale_date', $now->copy()->subMonths($i)->month)
                   ->whereYear('sale_date', $now->copy()->subMonths($i)->year)
                   ->when(auth()->user()->hasPermissionTo('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                        return $query->where('created_by', auth()->user()->id);
                    })
                   ->count();

            $purchases[] = Purchase::whereMonth('purchase_date', $now->copy()->subMonths($i)->month)
                   ->whereYear('purchase_date', $now->copy()->subMonths($i)->year)
                   ->when(auth()->user()->hasPermissionTo('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                        return $query->where('created_by', auth()->user()->id);
                    })
                   ->count();

        }

        $labels = array_reverse($labels);
        $sales = array_reverse($sales);
        $purchases = array_reverse($purchases);

        $saleVsPurchase = [];

        for($i = 0; $i < count($labels); $i++) {
          $saleVsPurchase[] = [
            'label'     => $labels[$i],
            'sales'     => $sales[$i],
            'purchases' => $purchases[$i],
          ];
        }

        return $saleVsPurchase;
    }

    // public function trendingItems() : array
    // {
    //     // Get top 4 trending items (adjust limit as needed)
    //     return ItemTransaction::query()
    //         ->select([
    //             'items.name',
    //             DB::raw('SUM(item_transactions.quantity) as total_quantity')
    //         ])
    //         ->join('items', 'items.id', '=', 'item_transactions.item_id')
    //         ->where('item_transactions.transaction_type', getMorphedModelName(Sale::class))
    //         ->when(auth()->user()->hasPermissionTo('dashboard.can.view.self.dashboard.details.only'), function ($query) {
    //             return $query->where('item_transactions.created_by', auth()->user()->id);
    //         })
    //         ->groupBy('item_transactions.item_id', 'items.name')
    //         ->orderByDesc('total_quantity')
    //         ->limit(4)
    //         ->get()
    //         ->toArray();
    // }


    public function trendingItems(): array
    {
        return ItemTransaction::query()
            ->select([
                'items.name as name',
                DB::raw('SUM(item_transactions.quantity) as total_quantity')
            ])
            ->join('items', 'items.id', '=', 'item_transactions.item_id')
            // ->where('item_transactions.transaction_type', getMorphedModelName(Sale::class))
            ->when(auth()->user()->hasPermissionTo('dashboard.can.view.self.dashboard.details.only'), function ($query) {
            
            })
            // Optional: limit to recent X days (for trending logic)
            // ->where('item_transactions.created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('items.name')
            ->orderByDesc('total_quantity')
            ->limit(20)
            ->get()
            ->toArray();
    }




    public function paymentReceivables(){
        // Retrieve opening balance from PartyTransaction
        $openingBalance = PartyTransaction::selectRaw('COALESCE(SUM(to_receive) - SUM(to_pay), 0) as opening_balance')
                                            ->when(auth()->user()->hasPermissionTo('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                                                return $query->where('created_by', auth()->user()->id);
                                            })
                                            ->first()
                                            ->opening_balance ?? 0;

        // Get total amount received from customers (Sale Adjustments)
        $partyPaymentReceiveSum = PartyPayment::where('payment_direction', 'receive')
            ->leftJoin('party_payment_allocations', 'party_payments.id', '=', 'party_payment_allocations.party_payment_id')
            ->leftJoin('payment_transactions', 'party_payment_allocations.payment_transaction_id', '=', 'payment_transactions.id')
            ->selectRaw('SUM(party_payments.amount) - COALESCE(SUM(payment_transactions.amount), 0) AS total_amount')
            ->when(auth()->user()->hasPermissionTo('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                return $query->where('party_payments.created_by', auth()->user()->id);
            })
            ->value('total_amount') ?? 0;

        // Get total amount paid to suppliers (Purchase Adjustments)
        $partyPaymentPaySum = PartyPayment::where('payment_direction', 'pay')
            ->leftJoin('party_payment_allocations', 'party_payments.id', '=', 'party_payment_allocations.party_payment_id')
            ->leftJoin('payment_transactions', 'party_payment_allocations.payment_transaction_id', '=', 'payment_transactions.id')
            ->selectRaw('SUM(party_payments.amount) - COALESCE(SUM(payment_transactions.amount), 0) AS total_amount')
            ->when(auth()->user()->hasPermissionTo('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                return $query->where('party_payments.created_by', auth()->user()->id);
            })
            ->value('total_amount') ?? 0;

        // Sale balance (grand_total - paid_amount)
        $saleBalance = Sale::selectRaw('coalesce(sum(grand_total - paid_amount), 0) as total')
            ->when(auth()->user()->hasPermissionTo('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                return $query->where('created_by', auth()->user()->id);
            })
            ->value('total');

        // Sale Return balance
        $saleReturnBalance = SaleReturn::selectRaw('coalesce(sum(grand_total - paid_amount), 0) as total')
            ->when(auth()->user()->hasPermissionTo('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                return $query->where('created_by', auth()->user()->id);
            })
            ->value('total');

        // Purchase balance
        $purchaseBalance = Purchase::selectRaw('coalesce(sum(grand_total - paid_amount), 0) as total')
            ->when(auth()->user()->hasPermissionTo('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                return $query->where('created_by', auth()->user()->id);
            })
            ->value('total');

        // Purchase Return balance
        $purchaseReturnBalance = PurchaseReturn::selectRaw('coalesce(sum(grand_total - paid_amount), 0) as total')
            ->when(auth()->user()->hasPermissionTo('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                return $query->where('created_by', auth()->user()->id);
            })
            ->value('total');

        // Calculate balance for party
        $partyReceivable = $openingBalance + $partyPaymentReceiveSum + $saleBalance - $saleReturnBalance;
        $partyPayable = $partyPaymentPaySum + $purchaseBalance - $purchaseReturnBalance;

        return [
                'payable' => abs($partyPayable),
                'receivable' => abs($partyReceivable),
            ];
    }



    public function dashboardDataList(Request $request){

        $data = SaleOrder::with('user', 'party', 'sale')
                        ->where('created_at', '>=', Carbon::today())
                        ->when($request->party_id, function ($query) use ($request) {
                            return $query->where('party_id', $request->party_id);
                        })
                        ->when($request->user_id, function ($query) use ($request) {
                            return $query->where('created_by', $request->user_id);
                        })
                        ->when($request->from_date, function ($query) use ($request) {
                            return $query->where('order_date', '>=', $this->toSystemDateFormat($request->from_date));
                        })
                        ->when($request->to_date, function ($query) use ($request) {
                            return $query->where('order_date', '<=', $this->toSystemDateFormat($request->to_date));
                        })
                        ->when(auth()->user()->role_id != 1, function ($query) {
                            return $query->where('created_by', auth()->user()->id);
                        });

        return DataTables::of($data)
                    ->filter(function ($query) use ($request) {
                        if ($request->has('search') && $request->search['value']) {
                            $searchTerm = $request->search['value'];
                            $query->where(function ($q) use ($searchTerm) {
                                $q->where('order_code', 'like', "%{$searchTerm}%")
                                  ->orWhere('grand_total', 'like', "%{$searchTerm}%")
                                  ->orWhereHas('party', function ($partyQuery) use ($searchTerm) {
                                      $partyQuery->where('first_name', 'like', "%{$searchTerm}%")
                                            ->orWhere('last_name', 'like', "%{$searchTerm}%");
                                  })
                                  ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                                      $userQuery->where('username', 'like', "%{$searchTerm}%");
                                  });
                            });
                        }
                    })
                    ->addIndexColumn()
                    ->addColumn('created_at', function ($row) {
                        return $row->created_at->format(app('company')['date_format']);
                    })
                    ->addColumn('username', function ($row) {
                        return $row->user->username??'';
                    })
                    ->addColumn('order_date', function ($row) {
                        return $row->formatted_order_date;
                    })
                    ->addColumn('due_date', function ($row) {
                        return $row->formatted_order_date;
                    })
                    ->addColumn('order_code', function ($row) {
                        return $row->order_code;
                    })
                    ->addColumn('party_name', function ($row) {
                        return $row->party->first_name." ".$row->party->last_name;
                    })
                    ->addColumn('grand_total', function ($row) {
                        return $this->formatWithPrecision($row->grand_total);
                    })
                    ->addColumn('balance', function ($row) {
                        return $this->formatWithPrecision($row->grand_total - $row->paid_amount);
                    })
                    
                    ->addColumn('action', function($row){
                            $id = $row->id;

                            $editUrl = route('sale.order.edit', ['id' => $id]);

                            //Verify is it converted or not
                            if($row->sale){
                                $convertToSale = route('sale.invoice.details', ['id' => $row->sale->id]);
                                $convertToSaleText = __('app.view_bill');
                                $convertToSaleIcon = 'check-double';
                            }else{
                                $convertToSale = route('sale.invoice.convert', ['id' => $id]);
                                $convertToSaleText = __('sale.convert_to_sale');
                                $convertToSaleIcon = 'transfer-alt';
                            }

                            $detailsUrl = route('sale.order.details', ['id' => $id]);
                            $printUrl = route('sale.order.print', ['id' => $id]);
                            $pdfUrl = route('sale.order.pdf', ['id' => $id]);

                            $actionBtn = '<div class="dropdown ms-auto">
                            <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="' . $editUrl . '"><i class="bx bx-edit"></i> '.__('app.edit').'</a>
                                </li>
                                
                                <li>
                                    <a class="dropdown-item" href="' . $detailsUrl . '"></i><i class="bx bx-show-alt"></i> '.__('app.details').'</a>
                                </li>
                                <li>
                                    <a target="_blank" class="dropdown-item" href="' . $printUrl . '"></i><i class="bx bx-printer "></i> '.__('app.print').'</a>
                                </li>
                                <li>
                                    <a target="_blank" class="dropdown-item" href="' . $pdfUrl . '"></i><i class="bx bxs-file-pdf"></i> '.__('app.pdf').'</a>
                                </li>
                                
                                <li>
                                    <button type="button" class="dropdown-item text-danger deleteRequest" data-delete-id='.$id.'><i class="bx bx-trash"></i> '.__('app.delete').'</button>
                                </li>
                            </ul>
                        </div>';
                            return $actionBtn;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
    }
}
