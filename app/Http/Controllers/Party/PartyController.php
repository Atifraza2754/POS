<?php

namespace App\Http\Controllers\Party;

use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use App\Http\Requests\PartyRequest;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Database\QueryException;

use App\Services\AccountTransactionService;
use App\Services\PartyTransactionService;
use App\Services\PartyService;
use App\Models\Party\Party;
use App\Models\Party\PartyTransaction;
use App\Models\Party\PartyCategory;

use App\Enums\AccountUniqueCode;
use App\Models\Accounts\AccountGroup;
use App\Models\Accounts\Account;
use App\Models\Sale\SaleOrder;
use App\Models\CustomerPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PartyController extends Controller
{
    use FormatsDateInputs;

    use FormatNumber;

    public $accountTransactionService;

    public $partyTransactionService;

    public $partyService;

    public function __construct(PartyTransactionService $partyTransactionService, AccountTransactionService $accountTransactionService, PartyService $partyService)
    {
        $this->partyTransactionService = $partyTransactionService;
        $this->accountTransactionService = $accountTransactionService;
        $this->partyService = $partyService;
    }

    public function getLang($partyType) : array
    {
        if($partyType == 'customer'){
            $lang = [
                'party_list' => __('customer.list'),
                'party_create' => __('customer.create_customer'),
                'party_update' => __('customer.update_customer'),
                'party_type' => $partyType,
                'party_details' => __('customer.details'),
            ];
        }else{
            $lang = [
                'party_list' => __('supplier.list'),
                'party_create' => __('supplier.create_supplier'),
                'party_update' => __('supplier.update_supplier'),
                'party_type' => $partyType,
                'party_details' => __('supplier.details'),
            ];
        }
        return $lang;
    }

    /**
     * Create a new party.
     *
     * @return \Illuminate\View\View
     */
    public function create($partyType) : View {
        $lang = $this->getLang($partyType);
        $categories = DB::table('party_categories')->where('status', 1)->pluck('name', 'id');
        return view('party.create', compact('lang','categories',));
    }

    /**
     * Edit a party.
     *
     * @param int $id The ID of the party to edit.
     * @return \Illuminate\View\View
     */
    public function edit($partyType, $id) : View {
        $lang = $this->getLang($partyType);

        $party = Party::where('party_type', $partyType)->whereId($id)->get()->first();
        $categories = DB::table('party_categories')->where('status', 1)->pluck('name', 'id');

        if(!$party){
            return abort(403, 'Unauthorized');
        }


        $transaction = $party->transaction()->get()->first();//Used Morph

        $opening_balance_type = 'to_pay';
        $to_receive = false;
        if($transaction){
            $transaction->opening_balance = ($transaction->to_pay > 0) ? $this->formatWithPrecision($transaction->to_pay, comma:false) : $this->formatWithPrecision($transaction->to_receive, comma:false);

            $opening_balance_type = ($transaction->to_pay > 0) ? 'to_pay' : 'to_receive';
        }

        /**
         * Todays Date
         * */
        $todaysDate = $this->toUserDateFormat(now());

        return view('party.edit', compact('party', 'transaction', 'opening_balance_type', 'todaysDate', 'lang','categories'));
    }



    /**
     * Return JsonResponse
     * */
    public function store(PartyRequest $request)  {
        try {

            DB::beginTransaction();

            /**
             * Get the validated data from the ItemRequest
             * */
            $validatedData = $request->validated();

            /**
             * Know which party type
             * `supplier` or `customer`
             * */
            $partyType = $request->party_type;

            /**
             * Know which operation want
             * `save` or `update`
             * */
            $operation = $request->operation;

            /**
             * Save or Update the Items Model
             * */
            $recordsToSave = [
                'first_name'        =>  $request->first_name,
                'last_name'         =>  $request->last_name,
                'email'             =>  $request->email,
                'mobile'            =>  $request->mobile,
                'phone'             =>  $request->phone,
                'whatsapp'          =>  $request->whatsapp,
                'party_type'        =>  $partyType,
                'tax_number'        =>  $request->tax_number,
                'shipping_address'  =>  $request->shipping_address,
                'billing_address'   =>  $request->billing_address,
                'is_set_credit_limit'=>  $request->is_set_credit_limit,
                'credit_limit'      =>  $request->credit_limit,
                'status'            =>  $request->status,
                'default_party'     =>  $request->default_party,
                'category'     =>  $request->category,
            ];
            if($request->has('state_id')){
                $recordsToSave['state_id'] = $request->state_id??null;
            }

            /**
             * for Party_type = "Customer"
             * */
            if($request->has('is_wholesale_customer')){
                $recordsToSave['is_wholesale_customer'] = $request->is_wholesale_customer;
            }

            if($request->operation == 'save'){
                $partyModel = Party::create($recordsToSave);
            }else{
                $partyModel = Party::find($request->party_id);

                //Load Party Transactions
                // $partyTransactions = $partyModel->transaction;

                // foreach ($partyTransactions as $partyTransaction) {
                //     //Delete Account Transaction
                //     $partyTransaction->accountTransaction()->delete();

                //     //Delete Party Transaction
                //     $partyTransaction->delete();
                // }

                //Update the party records
                $partyModel->update($recordsToSave);
            }

            $request->request->add(['partyModel' => $partyModel]);

            /**
             * Update Party Transaction for opening Balance
             * */

            // $transaction = $this->partyTransactionService->recordPartyTransactionEntry($partyModel, [
            //         'transaction_date'      =>  $request->transaction_date,
            //         'to_pay'                =>  ($request->opening_balance_type == 'to_pay')? $request->opening_balance??0 : 0,
            //         'to_receive'                =>  ($request->opening_balance_type == 'to_receive')? $request->opening_balance??0 : 0,
            //     ]);
            // if(!$transaction){
            //     throw new \Exception(__('party.failed_to_record_party_transactions'). " " . $recordDetails);
            // }

            // $this->accountTransactionService->partyOpeningBalanceTransaction($partyModel);

            // //Account Create or Update
            // $acccountCreateOrUpdate = $this->accountTransactionService->createOrUpdateAccountOfParty(partyId: $request->partyModel->id, partyName: $request->partyModel->first_name." ".$request->partyModel->last_name, partyType: $request->partyModel->party_type );
            // if(!$acccountCreateOrUpdate){
            //     throw new \Exception(__('account.failed_to_create_or_update_account'));
            // }

            // //Update Other Default Party as a 0
            // if($request->default_party){
            //     Party::where('party_type', $partyType)
            //          ->whereNot('id', $request->partyModel->id)
            //          ->update(['default_party' => 0]);
            //  }

            DB::commit();


            return response()->json([
                'status' => true,
                'message' => __('app.record_saved_successfully'),
                'data'  => [
                    'id' => $partyModel->id,
                    'first_name' => $partyModel->first_name,
                    'last_name' => $partyModel->last_name??'',
                ]
            ]);
        } catch (\Exception $e) {
                DB::rollback();

                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                ], 409);

        }
    }


    /**
     * partyType: customer or supplier
     * */
    public function list($partyType) : View {
        $lang = $this->getLang($partyType);
        $customerCategories = PartyCategory::all();
        // dd($customerCategories);
        return view('party.list', compact('lang','customerCategories'));
    }

    // public function datatableList(Request $request, $partyType){
    //     /**
    //      * party_type == customer then filter wholesale or retail customer
    //      * */
    //     $category = $request->input('customer_category');

    //     $data = Party::query()->where('party_type', $partyType);
    //     return DataTables::of($data)
    //                 ->filter(function ($query) use ($request, $category) {
    //                     if ($request->has('search')) {
    //                         log::info('Category Filter:', ['category' => $category]);

    //                         $searchTerm = $request->search['value'];
    //                         $query->where(function ($q) use ($searchTerm) {
    //                             $q->where('first_name', 'like', "%{$searchTerm}%")
    //                               ->orWhere('last_name', 'like', "%{$searchTerm}%")
    //                               ->orWhere('whatsapp', 'like', "%{$searchTerm}%")
    //                               ->orWhere('phone', 'like', "%{$searchTerm}%")
    //                               ->orWhere('email', 'like', "%{$searchTerm}%")
    //                               ;
    //                         });
    //                     }
    //                     if($category!==null){
    //                         $query->where('category', $category);
    //                         log::info('Category Filter:', ['category' => $category]);

    //                     }
    //                 })
                    
    //                 ->addIndexColumn()
    //                 ->addColumn('created_at', function ($row) {
    //                     return $row->created_at->format(app('company')['date_format']);
    //                 })
    //                 ->addColumn('name', function ($row) {
    //                     return $row->first_name." ".$row->last_name;
    //                 })
    //                 ->addColumn('username', function ($row) {
    //                     return $row->user->username??'';
    //                 })
    //                 ->addColumn('credit_limit', function ($row) {
    //                     // Store the balance data in the row
    //                     // $row->balanceData = $this->partyService->getPartyBalance($row->id);

    //                     // Return the formatted balance
    //                     return $row->credit_limit;
    //                 })
    //                 // ->addColumn('balance_type', function ($row) {
    //                 //     // Return the status using the stored balance data
    //                 //     return $row->balanceData['status'];
    //                 // })
    //                 ->addColumn('action', function($row) use ($partyType){
    //                         $id = $row->id;

    //                         $editUrl = route('party.edit', ['id' => $id, 'partyType' => $partyType]);
    //                         $deleteUrl = route('party.delete', ['id' => $id, 'partyType' => $partyType]);
    //                         $transactionUrl = route('party.transaction.list', ['id' => $id, 'partyType' => $partyType]);
    //                         $paymentUrl = route('party.payment.create', ['id' => $id, 'partyType' => $partyType]);

    //                         $actionBtn = '<div class="dropdown ms-auto">
    //                         <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
    //                         </a>
    //                         <ul class="dropdown-menu">

    //                             <li>
    //                                 <a class="dropdown-item" href="' . $editUrl . '"><i class="bi bi-trash"></i><i class="bx bx-edit"></i> '.__('app.edit').'</a>
    //                             </li>
    //                             <li>
    //                                 <a class="dropdown-item" href="' . $paymentUrl . '"><i class="bi bi-trash"></i><i class="bx bx-money"></i> '.__('payment.payment').'</a>
    //                             </li>
    //                             <li>
    //                                 <a class="dropdown-item party-payment-history" data-party-id="' . $id . '" role="button"></i><i class="bx bx-table"></i> '.__('payment.history').'</a>
    //                             </li>
    //                             <li>
    //                                 <a class="dropdown-item" href="' . $transactionUrl . '"><i class="bi bi-trash"></i><i class="bx bx-transfer-alt"></i> '.__('app.transactions').'</a>
    //                             </li>
    //                             <li>
    //                                 <button type="button" class="dropdown-item text-danger deleteRequest" data-delete-id='.$id.'><i class="bx bx-trash"></i> '.__('app.delete').'</button>
    //                             </li>
    //                         </ul>
    //                     </div>';
    //                         return $actionBtn;
    //                 })
    //                 ->rawColumns(['action'])
    //                 ->make(true);
    // }



    public function datatableList(Request $request, $partyType)
    {
        $category = $request->input('customer_category');

        // Start base query
        $data = Party::query()->where('party_type', $partyType);

        // Filter by user role
        $user = Auth::user();
        if ($user->role_id == 2) {
            // Salesman: show only customers created by them
            $data->where('created_by', $user->id);
        }

        return DataTables::of($data)
            ->filter(function ($query) use ($request, $category) {
                if ($request->has('search')) {
                    $searchTerm = $request->search['value'];
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('first_name', 'like', "%{$searchTerm}%")
                        ->orWhere('last_name', 'like', "%{$searchTerm}%")
                        ->orWhere('whatsapp', 'like', "%{$searchTerm}%")
                        ->orWhere('phone', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
                    });
                }

                if (!is_null($category)) {
                    $query->where('category', $category);
                }
            })
            ->addIndexColumn()
            ->addColumn('created_at', function ($row) {
                return $row->created_at->format(app('company')['date_format']);
            })
            ->addColumn('name', function ($row) {
                return $row->first_name . " " . $row->last_name;
            })
            ->addColumn('username', function ($row) {
                return $row->user->username ?? '';
            })
            ->addColumn('credit_limit', function ($row) {
                return $row->credit_limit;
            })
            ->addColumn('action', function ($row) use ($partyType) {
                $id = $row->id;

                $editUrl = route('party.edit', ['id' => $id, 'partyType' => $partyType]);
                $deleteUrl = route('party.delete', ['id' => $id, 'partyType' => $partyType]);
                $transactionUrl = route('party.transaction.list', ['id' => $id, 'partyType' => $partyType]);
                $paymentUrl = route('party.payment.create', ['id' => $id, 'partyType' => $partyType]);

                $actionBtn = '<div class="dropdown ms-auto">
                    <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded font-22 text-option"></i></a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="' . $editUrl . '"><i class="bx bx-edit"></i> ' . __('app.edit') . '</a></li>
                        <li><a class="dropdown-item" href="' . $paymentUrl . '"><i class="bx bx-money"></i> ' . __('payment.payment') . '</a></li>
                        <li><a class="dropdown-item party-payment-history" data-party-id="' . $id . '" role="button"><i class="bx bx-table"></i> ' . __('payment.history') . '</a></li>
                        <li><a class="dropdown-item" href="' . $transactionUrl . '"><i class="bx bx-transfer-alt"></i> ' . __('app.transactions') . '</a></li>
                        <li><button type="button" class="dropdown-item text-danger deleteRequest" data-delete-id="' . $id . '"><i class="bx bx-trash"></i> ' . __('app.delete') . '</button></li>
                    </ul>
                </div>';
                return $actionBtn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }


    public function delete(Request $request) : JsonResponse{

        $selectedRecordIds = $request->input('record_ids');

        // Perform validation for each selected record ID
        foreach ($selectedRecordIds as $recordId) {
            $record = Party::find($recordId);
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
        try{


            // Attempt deletion (as in previous responses)
            Party::whereIn('id', $selectedRecordIds)->chunk(100, function ($parties) {
                foreach ($parties as $party) {
                    //Load Party Transactions like Opening Balance and other payments
                    $partyTransactions = $party->transaction;

                    foreach ($partyTransactions as $partyTransaction) {
                        //Delete Payment Account Transactions
                        $partyTransaction->accountTransaction()->delete();

                        //Delete Party Transaction
                        $partyTransaction->delete();
                    }
                }
            });

            //Delete party
            Party::whereIn('id', $selectedRecordIds)->delete();

        }catch (QueryException $e){
            return response()->json(['message' => __('app.cannot_delete_records')], 409);
        }

        return response()->json([
            'status'    => true,
            'message' => __('app.record_deleted_successfully'),
        ]);
    }
    /**
     * Ajax Response
     * Search Bar for select2 list
     * */
    // function getAjaxSearchBarList(){
    //     $search = request('search');
    //     $partyType = request('party_type');

    //     $parties = Party::where(function($query) use ($search) {
    //                     $query->where('first_name', 'LIKE', "%{$search}%")
    //                           ->orWhere('last_name', 'LIKE', "%{$search}%")
    //                           ->orWhere('mobile', 'LIKE', "%{$search}%")
    //                           ->orWhere('phone', 'LIKE', "%{$search}%")
    //                           ->orWhere('email', 'LIKE', "%{$search}%");
    //                 })
    //                 ->select('id', 'first_name', 'last_name', 'mobile', 'is_wholesale_customer', 'to_pay', 'to_receive')
    //                 ->where('party_type', $partyType)
    //                 ->limit(8)
    //                 ->get();

    //     $response = [
    //         'results' => $parties->map(function ($party) {
    //             $partyBalance = $this->partyService->getPartyBalance($party->id);

    //             return [
    //                 'id' => $party->id,
    //                 'text' => $party->getFullName(),
    //                 'mobile' => $party->mobile,
    //                 'is_wholesale_customer' => $party->is_wholesale_customer,
    //                 'to_pay' => $partyBalance['status']=='you_pay' ? $partyBalance['balance'] : 0,
    //                 'to_receive' => $partyBalance['status']=='you_collect' ? $partyBalance['balance'] : 0,
    //             ];
    //         })->toArray(),
    //     ];

    //     return json_encode($response);

    // }



    // function getAjaxSearchBarList()
    // {
    //     $search = request('search');
    //     $partyType = request('party_type');

    //     $user = Auth::user();

    //     $parties = Party::where(function($query) use ($search) {
    //                         $query->where('first_name', 'LIKE', "%{$search}%")
    //                             ->orWhere('last_name', 'LIKE', "%{$search}%")
    //                             ->orWhere('mobile', 'LIKE', "%{$search}%")
    //                             ->orWhere('phone', 'LIKE', "%{$search}%")
    //                             ->orWhere('email', 'LIKE', "%{$search}%");
    //                     })
    //                     ->where('party_type', $partyType)
    //                     ->when($user->role_id == 2, function ($query) use ($user) {
    //                         // Only apply this condition for salesmen
    //                         $query->where('created_by', $user->id);
    //                     })
    //                     ->select('id', 'first_name', 'last_name', 'mobile', 'is_wholesale_customer', 'to_pay', 'to_receive')
    //                     ->limit(8)
    //                     ->get();

    //     $response = [
    //         'results' => $parties->map(function ($party) {
    //             $partyBalance = $this->partyService->getPartyBalance($party->id);

    //             return [
    //                 'id' => $party->id,
    //                 'text' => $party->getFullName(),
    //                 'mobile' => $party->mobile,
    //                 'is_wholesale_customer' => $party->is_wholesale_customer,
    //                 'to_pay' => $partyBalance['status'] === 'you_pay' ? $partyBalance['balance'] : 0,
    //                 'to_receive' => $partyBalance['status'] === 'you_collect' ? $partyBalance['balance'] : 0,
    //             ];
    //         })->toArray(),
    //     ];

    //     return response()->json($response);
    // }

    function getAjaxSearchBarList()
    {
        $search = request('search');
        $partyType = request('party_type');

        $user = Auth::user();

        $parties = Party::where(function($query) use ($search) {
                            $query->where('first_name', 'LIKE', "%{$search}%")
                                ->orWhere('last_name', 'LIKE', "%{$search}%")
                                ->orWhere('mobile', 'LIKE', "%{$search}%")
                                ->orWhere('phone', 'LIKE', "%{$search}%")
                                ->orWhere('email', 'LIKE', "%{$search}%");
                        })
                        ->where('party_type', $partyType)
                        ->when($user->role_id == 2, function ($query) use ($user) {
                            $query->where('created_by', $user->id);
                        })
                        ->select('id', 'first_name', 'last_name', 'mobile', 'is_wholesale_customer')
                        ->withSum('saleOrders', 'grand_total')
                        ->withSum('customerPayments', 'amount')
                        ->limit(8)
                        ->get();

        $response = [
            'results' => $parties->map(function ($party) {
                $totalSales = $party->sale_orders_sum_grand_total ?? 0;
                $totalPayments = $party->customer_payments_sum_amount ?? 0;

                $remainingToPay = max(0, $totalSales - $totalPayments); // prevent negative

                return [
                    'id' => $party->id,
                    'text' => $party->getFullName(),
                    'mobile' => $party->mobile,
                    'is_wholesale_customer' => $party->is_wholesale_customer,
                    'to_pay' => $remainingToPay,
                    'to_receive' => 0, // You can customize this if needed
                ];
            })->toArray(),
        ];

        return response()->json($response);
    }




    public function customerHistoryPage()
    {
        // just return the Blade view with the table
        return view('party.history');
    }

    // public function customerHistory(Request $request)
    // {
    //     // $customers = DB::table('parties')->where('party_type','customer')->where('status',1)->get();

    //     // return view('party.history',compact('customers'));

    //     $data = Party::where('party_type','customer')->get();

    //     return DataTables::of($data)
    //         ->addIndexColumn()
    //         ->addColumn('customer_name', function ($row) {
    //             return $row->first_name . ' ' . $row->last_name;
    //         })
    //         ->addColumn('mobile', function ($row) {
    //             return $row->mobile ?? '';
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
    //         // ->addColumn('action', function ($row) {
    //         //     $id = $row->id;
    //         //     $deleteUrl = route('order.payment.delete', ['id' => $id]);

    //         //     $actionBtn = '<div class="dropdown ms-auto">
    //         //         <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown">
    //         //             <i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
    //         //         </a>
    //         //         <ul class="dropdown-menu">
    //         //             <li>
    //         //                 <button type="button" class="dropdown-item text-danger deleteRequest" data-delete-id='.$id.'>
    //         //                     <i class="bx bx-trash"></i> '.__('app.delete').'
    //         //                 </button>
    //         //             </li>
    //         //         </ul>
    //         //     </div>';
    //         //     return $actionBtn;
    //         // })
    //         // ->rawColumns(['action'])
    //         ->make(true);
    // }

    // public function customerHistory(Request $request)
    // {
    //     $customers = Party::where('party_type', 'customer')->with('user')->get();

    //     return DataTables::of($customers)
    //         ->addIndexColumn()
    //         ->addColumn('customer_name', function ($row) {
    //             return $row->first_name . ' ' . $row->last_name;
    //         })
    //         ->addColumn('mobile', function ($row) {
    //             return $row->mobile ?? '';
    //         })
    //         ->addColumn('total_amount', function ($row) {
    //             $totalOrders = SaleOrder::where('party_id', $row->id)->sum('grand_total');
    //             return number_format($totalOrders, 2);
    //         })
    //         ->addColumn('paid_amount', function ($row) {
    //             $totalPaid = CustomerPayment::where('party_id', $row->id)->sum('amount');
    //             return number_format($totalPaid, 2);
    //         })
    //         ->addColumn('remaining_amount', function ($row) {
    //             $totalOrders = SaleOrder::where('party_id', $row->id)->sum('grand_total');
    //             $totalPaid   = CustomerPayment::where('party_id', $row->id)->sum('amount');
    //             $remaining   = $totalOrders - $totalPaid;
    //             return number_format(max($remaining, 0), 2);
    //         })
    //         ->addColumn('created_at', function ($row) {
    //             return $row->created_at->format(app('company')['date_format']);
    //         })
    //         ->addColumn('created_by', function ($row) {
    //             return $row->created_at->format(app('company')['date_format']);
    //         })
    //         ->when(auth()->user()->role_id != 1, function ($query) {
    //                         return $query->where('created_by', auth()->user()->id);
    //                     })
    //         ->make(true);
    // }


    public function customerHistory(Request $request)
    {
        $query = Party::where('party_type', 'customer')->with('user');

        // Apply role-based restriction BEFORE get()
        if (auth()->user()->role_id != 1) {
            $query->where('created_by', auth()->user()->id);
        }

        $customers = $query->get();
        if ($request->filled('reached_credit_limit') && $request->reached_credit_limit == true) {
            $data = $customers->filter(function ($row) {
                $totalOrders = SaleOrder::where('party_id', $row->party_id)->sum('grand_total');
                $totalPaid   = CustomerPayment::where('party_id', $row->party_id)->sum('amount');
                $remaining   = $totalOrders - $totalPaid;

                $creditLimit = Party::where('id', $row->party_id)->value('credit_limit');

                return $remaining >= $creditLimit; // or $remaining > $creditLimit based on your preference
            });
        }

        return DataTables::of($customers)
            ->addIndexColumn()
            ->addColumn('customer_name', function ($row) {
                return $row->first_name . ' ' . $row->last_name;
            })
            ->addColumn('mobile', function ($row) {
                return $row->mobile ?? '';
            })
            ->addColumn('total_amount', function ($row) {
                $totalOrders = SaleOrder::where('party_id', $row->id)->sum('grand_total');
                return number_format($totalOrders, 2);
            })
            ->addColumn('paid_amount', function ($row) {
                $totalPaid = CustomerPayment::where('party_id', $row->id)->sum('amount');
                return number_format($totalPaid, 2);
            })
            ->addColumn('remaining_amount', function ($row) {
                $totalOrders = SaleOrder::where('party_id', $row->id)->sum('grand_total');
                $totalPaid   = CustomerPayment::where('party_id', $row->id)->sum('amount');
                $remaining   = $totalOrders - $totalPaid;
                return number_format(max($remaining, 0), 2);
            })
            ->addColumn('credit_limit', function ($row) {
                if ($row->credit_limit == 0) {
                    return '<span style="background-color: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px;">No credit limit</span>';
                } else {
                    $formatted = number_format($row->credit_limit, 2);
                    return '<span style="background-color: #67b0f0; padding: 4px 8px; border-radius: 4px;">' . $formatted . '</span>';
                }

                
            })
            ->addColumn('created_at', function ($row) {
                return $row->created_at->format(app('company')['date_format']);
            })
            ->addColumn('created_by', function ($row) {
                return $row->user->username ?? ''; // Showing creator's name
            })
            ->addColumn('total_orders', function ($row) {
                $totalOrders = SaleOrder::where('created_by', $row->user->id)->count();
                return $totalOrders;
            })
             ->rawColumns(['credit_limit'])
            ->make(true);
    }



    public function getCustomerHistory(Request $request)
    {
        $customerId = $request->customer_id;

        // Fetch customer orders
        $orders = SaleOrder::with('item')->where('party_id', $customerId)->get();

        // Total order amount
        $totalOrders = $orders->sum('grand_total');

        // Total paid from payments table
        $totalPaid = CustomerPayment::where('party_id', $customerId)->sum('paid_amount');

        // Remaining
        $remaining = $totalOrders - $totalPaid;

        return response()->json([
            'html' => view('order_payments.partials.customer_orders', compact('orders', 'totalOrders', 'totalPaid', 'remaining'))->render(),
            'orders' => $orders,
        ]);
    }
}
