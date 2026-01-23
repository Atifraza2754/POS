<?php

namespace App\Http\Controllers\Expenses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

use App\Models\Expenses\Expense;
use App\Models\Expenses\ExpenseItemMaster;
use App\Models\Expenses\ExpenseItem;
use App\Http\Requests\ExpenseRequest;
use App\Models\Prefix;
use App\Models\CustomerPayment;
use App\Models\PaymentTypes;
use App\Models\Expenses\ExpenseCategory;
use App\Enums\App;
use App\Models\Party\Party;
use App\Models\Party\PartyPayment;
use App\Models\PaymentTransaction;
use App\Models\Purchase\Purchase;
use App\Models\Sale\SaleOrder;
use App\Services\PaymentTransactionService;
use App\Traits\FormatNumber;
use App\Services\AccountTransactionService;
use App\Services\PaymentTypeService;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    use FormatNumber;

    protected $companyId;

    private $accountTransactionService;

    private $paymentTypeService;

    private $paymentTransactionService;

    private $paidPaymentTotal;

    public function __construct(AccountTransactionService $accountTransactionService,
                                    PaymentTypeService $paymentTypeService,
                                    PaymentTransactionService $paymentTransactionService)
    {
        $this->companyId = App::APP_SETTINGS_RECORD_ID->value;
        $this->accountTransactionService = $accountTransactionService;
        $this->paymentTypeService = $paymentTypeService;
        $this->paymentTransactionService = $paymentTransactionService;
    }

    /**
     * Create a new expense.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View  {
        $prefix = Prefix::findOrNew($this->companyId);
        $lastCountId = $this->getLastCountId();
        $selectedPaymentTypesArray = json_encode($this->paymentTypeService->selectedPaymentTypesArray());
        $data = [
            'prefix_code' => $prefix->expense,
            'count_id' => ($lastCountId+1),
        ];
        return view('expenses.expense.create',compact('data', 'selectedPaymentTypesArray'));

    }

    /**
     * Get last count ID
     * */
    public function getLastCountId(){
        return Expense::select('count_id')->orderBy('id', 'desc')->first()?->count_id ?? 0;
    }

    /**
     * List the Accounts
     *
     * @return \Illuminate\View\View
     */
    public function list() : View {
        return view('expenses.expense.list');
    }

    /**
     * Print expenses
     *
     * @param int $id, the ID of the order
     * @return \Illuminate\View\View
     */
    public function print($id) : View {

        $expense = Expense::with('category')->find($id);

        //Item Details
        $expenseItems = ExpenseItem::with('itemDetails')->where('expense_id',$id)->get();

        //Payment Details
        $selectedPaymentTypesArray = json_encode($this->paymentTransactionService->getPaymentRecordsArray($expense));

        return view('expenses.expense.print', compact('expense', 'expenseItems','selectedPaymentTypesArray'));
    }

     /**
     * Edit a expenses.
     *
     * @param int $id The ID of the expense to edit.
     * @return \Illuminate\View\View
     */
    public function edit($id) : View {
        $expense = Expense::find($id);

        //Item Details
        $expenseItems = ExpenseItem::with('itemDetails')->where('expense_id',$id)->get()->toArray();
        $expenseItemsJson = json_encode($expenseItems);

        //Payment Details
        $selectedPaymentTypesArray = json_encode($this->paymentTransactionService->getPaymentRecordsArray($expense));

        return view('expenses.expense.edit', compact('expense', 'expenseItemsJson','selectedPaymentTypesArray'));
    }

    /**
     * Return JsonResponse
     * */
    public function store(ExpenseRequest $request) : JsonResponse  {
        try {
            DB::beginTransaction();
            // Get the validated data from the expenseRequest
            $validatedData = $request->validated();

            if($request->operation == 'save'){
                // Create a new expense record using Eloquent and save it
                $newExpense = Expense::create($validatedData);

                $request->request->add(['expense_id' => $newExpense->id]);

            }else{
                ExpenseItem::where('expense_id', $validatedData['expense_id'])->delete();

                $fillableColumns = [
                    'expense_category_id'   => $validatedData['expense_category_id'],
                    'expense_date'          => $validatedData['expense_date'],
                    'prefix_code'           => $validatedData['prefix_code'],
                    'count_id'              => $validatedData['count_id'],
                    'expense_code'          => $validatedData['expense_code'],
                    'note'                  => $validatedData['note'],
                    'round_off'             => $validatedData['round_off'],
                    'grand_total'           => $validatedData['grand_total'],
                ];
                // First, find the expense
                $newExpense = Expense::findOrFail($validatedData['expense_id']);

                $newExpense->accountTransaction()->delete();
                //Load Expense Payment Transactions
                $paymentTransactions = $newExpense->paymentTransaction;

                foreach ($paymentTransactions as $paymentTransaction) {
                    //Delete Account Transaction
                    $paymentTransaction->accountTransaction()->delete();

                    //Delete Expense Payment Transaction
                    $paymentTransaction->delete();
                }

                // Update the Expense records
                $newExpense->update($fillableColumns);
            }

            $request->request->add(['modelName' => $newExpense]);

            /**
             * Save Table Items in Expense Items Table
             * */
            $expenseItemsArray = $this->saveExpenseItems($request);
            if(!$expenseItemsArray['status']){
                return response()->json([
                    'status'    => false,
                    'message' => $expenseItemsArray['message'],
                ],409);
            }

            /**
             * Save Expense Payment Records
             * */
            $expensePaymentsArray = $this->saveExpensePayments($request);
            if(!$expensePaymentsArray['status']){
                return response()->json([
                    'status'    => false,
                    'message' => $expensePaymentsArray['message'],
                ],409);
            }

            /**
            * Payment Should be equal to Grand Total
            * */
            $this->paidPaymentTotal = ($request->modelName->fresh())->paymentTransaction->sum('amount');
            if($request->grand_total != $this->paidPaymentTotal){
                return response()->json([
                    'status'    => false,
                    'message' => __('payment.paid_payment_not_equal_to_grand_total'),
                ],409);
            }

            /**
             * Update Expenses Model
             * Total Paid Amunt
             * */
            if(!$this->paymentTransactionService->updateTotalPaidAmountInModel($request->modelName)){
                throw new \Exception(__('payment.failed_to_update_paid_amount'));
            }


            /**
             * Update Account Transaction entry
             * Call Services
             * @return boolean
             * */
            $accountTransactionStatus = $this->accountTransactionService->expenseAccountTransaction($request->modelName);
            if(!$accountTransactionStatus){
                return response()->json([
                    'status'    => false,
                    'message' => __('payment.failed_to_update_account'),
                ],409);
            }

            DB::commit();

            // Regenerate the CSRF token
            //Session::regenerateToken();

            return response()->json([
                'status'    => false,
                'message' => __('app.record_saved_successfully'),
                'id' => $request->expense_id,

            ]);

        } catch (\Exception $e) {
                DB::rollback();

                return response()->json([
                    'status' => true,
                    'message' => __('app.something_went_wrong').__('app.check_custom_log_file').$e->getMessage(),
                ], 409);

        }

    }



    public function saveExpensePayments($request)
    {
        $paymentCount = $request->row_count_payments;

        for ($i=0; $i <= $paymentCount; $i++) {

            /**
             * If array record not exist then continue forloop
             * */
            if(!isset($request->payment_amount[$i])){
                continue;
            }

            /**
             * Data index start from 0
             * */
            $amount           = $request->payment_amount[$i];

            if($amount > 0){
                if(!isset($request->payment_type_id[$i])){
                        return [
                            'status' => false,
                            'message' => __('payment.missed_to_select_payment_type')."#".$i,
                        ];
                }

                $paymentsArray = [
                    'transaction_date'          => $request->expense_date,
                    'amount'                    => $amount,
                    'payment_type_id'           => $request->payment_type_id[$i],
                    'note'                      => $request->payment_note[$i],
                ];

                if(!$transaction = $this->paymentTransactionService->recordPayment($request->modelName, $paymentsArray)){
                    throw new \Exception(__('payment.failed_to_record_payment_transactions'));
                }

            }//amount>0
        }//for end

        return ['status' => true];
    }

    public function saveExpenseItems($request)
    {
        $itemsCount = $request->row_count;

        for ($i=0; $i < $itemsCount; $i++) {
            /**
             * If array record not exist then continue forloop
             * */
            if(!isset($request->name[$i])){
                continue;
            }

            /**
             * Data index start from 0
             * */
            $itemName           = $request->name[$i];
            $itemQuantity       = $request->quantity[$i];

            if(empty($itemQuantity) || $itemQuantity === 0 || $itemQuantity < 0){
                    return [
                        'status' => false,
                        'message' => ($itemQuantity<0) ? __('item.item_qty_negative', ['item_name' => $itemName]) : __('item.please_enter_item_quantity', ['item_name' => $itemName]),
                    ];
            }

            $itemsArray = [
                'expense_id'                => $request->expense_id,
                'expense_item_master_id'    => $this->getExpenseItemId($request, index:$i ),
                'description'               => $request->description[$i],
                'unit_price'                => $request->unit_price[$i],
                'quantity'                  => $itemQuantity,
                'total'                     => $request->total[$i],
            ];

            if(!ExpenseItem::create($itemsArray)){
                return ['status' => false];
            }


        }//for end

        return ['status' => true];
    }

    /**
     * If record not exist then create
     * */
    protected function getExpenseItemId($request, $index)
    {
        $itemName           = $request->name[$index];
        $itemUnitPrice      = $request->unit_price[$index];

        $existingItem = ExpenseItemMaster::where('name', $itemName)->first();

        if ($existingItem) {
            return $existingItem->id;
        }

        $newItem = ExpenseItemMaster::create(['name' => $itemName, 'unit_price' => $itemUnitPrice ]);

        return $newItem->id;
    }

    public function datatableList(Request $request){

        $data = Expense::with('user', 'paymentTransaction.paymentType')
                        ->when($request->expense_category_id, function ($query) use ($request) {
                            return $query->where('expense_category_id', $request->expense_category_id);
                        })
                        ->when(!auth()->user()->hasPermissionTo('expense.can.view.other.users.expenses'), function ($query) use ($request) {
                            return $query->where('created_by', auth()->user()->id);
                        });

        return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('created_at', function ($row) {
                        return $row->created_at->format(app('company')['date_format']);
                    })
                    ->addColumn('username', function ($row) {
                        return $row->user->username??'';
                    })
                    ->addColumn('expense_date', function ($row) {
                        return $row->formatted_expense_date;
                    })
                    ->addColumn('paid_amount', function ($row) {
                        return $this->formatWithPrecision($row->paid_amount);
                    })
                    ->addColumn('expense_number', function ($row) {
                        return $row->expense_code;
                    })
                    ->addColumn('payment_type', function ($row) {
                        return $row->paymentTransaction->pluck('paymentType.name')->implode(', ');
                    })
                    ->addColumn('expense_category', function ($row) {
                        return $row->category->name;
                    })
                    ->addColumn('action', function($row){
                            $id = $row->id;

                            $editUrl = route('expense.edit', ['id' => $id]);
                            $deleteUrl = route('expense.delete', ['id' => $id]);
                            $printUrl = route('expense.print', ['id' => $id]);

                            $actionBtn = '<div class="dropdown ms-auto">
                            <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="' . $editUrl . '"><i class="bi bi-trash"></i><i class="bx bx-edit"></i> '.__('app.edit').'</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="' . $printUrl . '"></i><i class="bx bx-receipt"></i> '.__('app.print').'</a>
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

    public function delete(Request $request) : JsonResponse{

        DB::beginTransaction();

        $selectedRecordIds = $request->input('record_ids');

        // Perform validation for each selected record ID
        foreach ($selectedRecordIds as $recordId) {
            $record = Expense::find($recordId);
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


        try {
            // Attempt deletion (as in previous responses)
            Expense::whereIn('id', $selectedRecordIds)->chunk(100, function ($expenses) {
                foreach ($expenses as $expense) {
                    $expense->accountTransaction()->delete();
                    //Load Expense Payment Transactions
                    $paymentTransactions = $expense->paymentTransaction;
                    foreach ($paymentTransactions as $paymentTransaction) {
                        //Delete Payment Account Transactions
                        $paymentTransaction->accountTransaction()->delete();

                        //Delete Expense Payment Transactions
                        $paymentTransaction->delete();
                    }
                }
            });

            //Delete Expenses
            $deletedCount = Expense::whereIn('id', $selectedRecordIds)->delete();

            DB::commit();

            return response()->json([
                'status'    => true,
                'message' => __('app.record_deleted_successfully'),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();

            if ($e->getCode() == 23000) {
                return response()->json([
                    'status'    => false,
                    'message' => __('app.cannot_delete_records'),
                ],409);
            }
        }
    }


    /**
     * Ajax Response
     * Search Bar list
     * */
    function getAjaxSearchBarList(){
        $search = request('search');

        $expenseItemsMaster = ExpenseItemMaster::where('name', 'LIKE', "%{$search}%")
                                      ->select('id', 'name', 'unit_price') // Select only the required columns
                                      ->limit(10)
                                      ->get();
        $response = [
            'results' => $expenseItemsMaster->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->name,
                    'unit_price' => $item->unit_price,
                ];
            })->toArray(),
        ];

        return json_encode($response);
    }

    public function total()
    {
        // $dailyExpense = Expense::where('create_at')
        return view('expenses.total.index');
    }

    public function totalExpenseDatatable(Request $request)
    {
         $data = Expense::with('user', 'paymentTransaction.paymentType')
                        ->when($request->expense_category_id, function ($query) use ($request) {
                            return $query->where('expense_category_id', $request->expense_category_id);
                        })
                        ->when(!auth()->user()->hasPermissionTo('expense.can.view.other.users.expenses'), function ($query) use ($request) {
                            return $query->where('created_by', auth()->user()->id);
                        });
                         if ($request->filled('from_date')) {
            $data->whereDate('created_at', '>=', $this->toSystemDateFormat($request->from_date));
        }

        // 📅 Filter by to_date
        if ($request->filled('to_date')) {
            $data->whereDate('created_at', '<=', $this->toSystemDateFormat($request->to_date));
        }

        return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('created_at', function ($row) {
                        return $row->created_at->format(app('company')['date_format']);
                    })
                    ->addColumn('username', function ($row) {
                        return $row->user->username??'';
                    })
                    ->addColumn('expense_date', function ($row) {
                        return $row->formatted_expense_date;
                    })
                    ->addColumn('paid_amount', function ($row) {
                        return $this->formatWithPrecision($row->paid_amount);
                    })
                    ->addColumn('expense_number', function ($row) {
                        return $row->expense_code;
                    })
                    ->addColumn('payment_type', function ($row) {
                        return $row->paymentTransaction->pluck('paymentType.name')->implode(', ');
                    })
                    ->addColumn('expense_category', function ($row) {
                        return $row->category->name;
                    })
                    ->addColumn('action', function($row){
                            $id = $row->id;

                            $editUrl = route('expense.edit', ['id' => $id]);
                            $deleteUrl = route('expense.delete', ['id' => $id]);
                            $printUrl = route('expense.print', ['id' => $id]);

                            $actionBtn = '<div class="dropdown ms-auto">
                            <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="' . $editUrl . '"><i class="bi bi-trash"></i><i class="bx bx-edit"></i> '.__('app.edit').'</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="' . $printUrl . '"></i><i class="bx bx-receipt"></i> '.__('app.print').'</a>
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


    // function supplierExpenseDatabtable()
    // {
    //     try{
    //         $transactions = PartyPayment::with('party', 'paymentType')->get();

    //         // Check if the transactions collection is empty
    //         if ($transactions->isEmpty()) {
    //             throw new \Exception('No Payment (Manual) History found!!');
    //         }

    //         $firstTransaction = $transactions->first();

    //         $balance = $this->partyService->getPartyBalance($firstTransaction->party->id);

    //         $data = [
    //             'party_id' => $firstTransaction->party->id,
    //             'party_name' => $firstTransaction->party->getFullName(),
    //             'balance' => $balance['balance'],
    //             'balance_type' => $balance['status'],

    //             'partyPayments' => $transactions->map(function ($transaction) {
    //                 return [
    //                     'payment_id' => $transaction->id,
    //                     'payment_direction' => $transaction->payment_direction=='pay' ? 'You Paid' : 'You Received',
    //                     'color' => $transaction->payment_direction=='pay' ? 'danger' : 'success',
    //                     'transaction_date' => $this->toUserDateFormat($transaction->transaction_date),
    //                     'reference_no' => $transaction->reference_no ?? '',
    //                     'payment_type' => $transaction->paymentType->name,
    //                     'amount' => $this->formatWithPrecision($transaction->amount),
    //                 ];
    //             })->toArray(),
    //         ];

    //         return $data;
    //     } catch (\Exception $e) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => $e->getMessage(),
    //             ], 409);

    //     }
    // }


    // public function supplierExpenseDatabtable(Request $request)
    // {
    //     // dd()
        
    //     // $data = PartyPayment::with('party', 'paymentType')
    //     $data = PaymentTransaction::with('party')
    //                 ->when($request->party_id, function ($query) use ($request) {
    //                     return $query->where('supplier_id', $request->party_id);
    //                 });

            
    //     if ($request->filled('from_date') && $request->filled('to_date')) {
    //         $from = \Carbon\Carbon::createFromFormat('d/m/Y', $request->from_date)->format('Y-m-d');
    //         $to   = \Carbon\Carbon::createFromFormat('d/m/Y', $request->to_date)->format('Y-m-d');
    //         $data->whereDate('purchase_date', '>=', $from);
    //         $data->whereDate('purchase_date', '<=', $to);
    //     }
   

    //     return DataTables::of($data)
    //         ->addIndexColumn()
    //         ->addColumn('party_name', function ($row) {
    //             return $row->party->first_name;
    //         })
    //         ->addColumn('transaction_date', function ($row) {
    //             // return $this->toUserDateFormat($row->transaction_date);
    //           return   $row->purchase_date;
    //         })
    //         ->addColumn('payment_direction', function ($row) {
    //             return $row->payment_direction = 'You Paid';
    //         })
    //         ->addColumn('color', function ($row) {
    //             return $row->payment_direction = 'success';
    //         })
    //         ->addColumn('reference_no', function ($row) {
    //             return $row->reference_no ?? 'N/A';
    //         })
    //         ->addColumn('payment_type', function ($row) {
    //             return $row->paymentType->name ?? '';
    //         })
    //         ->addColumn('amount', function ($row) {
    //             return $this->formatWithPrecision($row->amount);
    //         })
    //         ->addColumn('action', function ($row) {
    //             // Optional: Define actions if needed (Edit, Delete, etc.)
    //             return ''; // Or build HTML like in your previous method
    //         })
    //         ->rawColumns(['action']) // If you're adding HTML in action
    //         ->make(true);
    // }

    // public function supplierExpenseDatabtable(Request $request)
    // {
    //     dd($request->all());

    //     dd(PaymentTransaction::with('party'));
        
    //     $data = PaymentTransaction::with('party')
    //                 ->when($request->party_id, function ($query) use ($request) {
    //                     return $query->where('supplier_id', $request->party_id);
    //                 });
    //                 dd($data);

    //     if ($request->filled('from_date') && $request->filled('to_date')) {
    //         $from = \Carbon\Carbon::createFromFormat('d/m/Y', $request->from_date)->format('Y-m-d');
    //         $to   = \Carbon\Carbon::createFromFormat('d/m/Y', $request->to_date)->format('Y-m-d');

    //         $data->whereDate('transaction_date', '>=', $from);
    //         $data->whereDate('transaction_date', '<=', $to);
    //     }

    //     return DataTables::of($data)
    //         ->addIndexColumn()
    //         ->addColumn('party_name', function ($row) {
    //             return $row->party->first_name;
    //         })
    //         ->addColumn('transaction_date', function ($row) {
    //             return $row->transaction_date;
    //         })
    //         ->addColumn('payment_direction', function () {
    //             return 'You Paid';
    //         })
    //         ->addColumn('color', function () {
    //             return 'success';
    //         })
    //         ->addColumn('reference_no', function ($row) {
    //             return $row->reference_no ?? 'N/A';
    //         })
    //         ->addColumn('payment_type', function ($row) {
    //             return $row->paymentType->name ?? '';
    //         })
    //         ->addColumn('amount', function ($row) {
    //             return $this->formatWithPrecision($row->amount);
    //         })
    //         ->addColumn('action', function () {
    //             return '';
    //         })
    //         ->rawColumns(['action'])
    //         ->make(true);
    // }


    public function supplierExpenseDatabtable(Request $request)
    {
        
        $data = PaymentTransaction::with('party')->where('transaction_type','Purchase')->get(); // No filter

        //  if ($request->filled('from_date') && $request->filled('to_date')) {
        //     $from = \Carbon\Carbon::createFromFormat('d/m/Y', $request->from_date)->format('Y-m-d');
        //     $to   = \Carbon\Carbon::createFromFormat('d/m/Y', $request->to_date)->format('Y-m-d');
        //     $data->whereDate('transaction_date', '>=', $from);
        //     $data->whereDate('transaction_date', '<=', $to);
        // }

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from = Carbon::createFromFormat('d/m/Y', $request->from_date)->format('Y-m-d');
            $to   = Carbon::createFromFormat('d/m/Y', $request->to_date)->format('Y-m-d');

            $data->whereBetween('transaction_date', [$from, $to]);
        }


 


        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('party_name', function ($row) {
                return $row->party->first_name ?? 'N/A';
            })
            ->addColumn('transaction_date', function ($row) {
                return $row->transaction_date;
            })
            ->addColumn('payment_direction', function () {
                return 'You Paid';
            })
            ->addColumn('color', function () {
                return 'success';
            })
            ->addColumn('reference_no', function ($row) {
                return $row->reference_no ?? 'N/A';
            })
            ->addColumn('payment_type', function ($row) {
                return $row->paymentType->name ?? '';
            })
            ->addColumn('amount', function ($row) {
                return $this->formatWithPrecision($row->amount);
            })
            ->addColumn('action', function () {
                return '';
            })
            ->rawColumns(['action'])
            ->make(true);
    }




    public function CashInPage()
    {
        // $dailyExpense = Expense::where('create_at')
        return view('expenses.expense.cash-in');
    }



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


    // public function CashInDatatable(Request $request)
    // {
    //     $user = auth()->user();

    //     // Base query
    //     $query = CustomerPayment::with('party');

    //     if ($user->role_id == 1 && $request->filled('user_id')) {
    //         $salesmanId = $request->input('user_id');

    //         // ✅ Filtering directly on CustomerPayment.created_by
    //         $query->where('created_by', $salesmanId);
    //     }

    //     if ($user->role_id != 1) {
    //         $query->where('created_by', auth()->user()->id);
    //     }

    //     // 📅 Filter by from_date
    //     if ($request->filled('from_date')) {
    //         $query->whereDate('payment_date', '>=', $this->toSystemDateFormat($request->from_date));
    //     }

    //     // 📅 Filter by to_date
    //     if ($request->filled('to_date')) {
    //         $query->whereDate('payment_date', '<=', $this->toSystemDateFormat($request->to_date));
    //     }

    //     $data = $query->get();

    //     // Optional filter: show only customers who reached credit limit
    //     if ($request->filled('reached_credit_limit') && $request->reached_credit_limit == true) {
    //         $data = $data->filter(function ($row) {
    //             $totalOrders = SaleOrder::where('party_id', $row->party_id)->sum('grand_total');
    //             $totalPaid   = CustomerPayment::where('party_id', $row->party_id)->sum('amount');
    //             $remaining   = $totalOrders - $totalPaid;

    //             $creditLimit = Party::where('id', $row->party_id)->value('credit_limit');

    //             return $remaining >= $creditLimit; // or $remaining > $creditLimit based on your preference
    //         });
    //     }


    //     return DataTables::of($data)
    //         ->addIndexColumn()
    //         // ->addColumn('customer_name', function ($row) {
    //         //     return $row->party->first_name . ' ' . $row->party->last_name;
    //         // })
    //         ->addColumn('created_by', function ($row) {
    //             return $row->createdBy->username ?? '—';
    //         })
            
    //         ->addColumn('paid_amount', function ($row) {
                
    //             $totalPaidToday = DB::table('customer_payments')
    //                 ->whereDate('created_at', Carbon::today())
    //                 ->sum('amount');

    //             return $totalPaidToday;

    //         })
            
    //         ->addColumn('payment_date', function ($row) {
    //             return $row->payment_date;
    //         })
    //         ->addColumn('action', function ($row) {
    //             $id = $row->id;
    //             $deleteUrl = route('order.payment.delete', ['id' => $id]);

    //             return '<div class="dropdown ms-auto">
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
    //         })
    //         ->rawColumns(['action'])
    //         ->make(true);
    // }



// public function CashInDatatable(Request $request)
// {
//     $user = auth()->user();
//     $today = Carbon::today();

//     // Base query: group payments by created_by and sum today's amounts
//     $query = DB::table('customer_payments')
//         ->select('created_by', DB::raw('SUM(amount) as total_paid_today','pyment_date'))
//         ->whereDate('created_at', $today)
//         ->groupBy('created_by');

//     // 🔒 If admin and user_id is specified, filter by that user
//     if ($user->role_id == 1 && $request->filled('user_id')) {
//         $query->where('created_by', $request->input('user_id'));
//     }

//     // 🔒 If not admin, show only their own data
//     if ($user->role_id != 1) {
//         $query->where('created_by', $user->id);
//     }

//     // Fetch data
//     $data = $query->get();

//     return DataTables::of($data)
//         ->addIndexColumn()
//         ->addColumn('username', function ($row) {
//             // Get the username manually from users table
//             return DB::table('users')->where('id', $row->created_by)->value('username') ?? '—';
//         })
//         ->addColumn('paid_amount', function ($row) {
//             return number_format($row->total_paid_today, 2);
//         })
//         ->addColumn('payment_date', function ($row) {
//                  return $row->payment_date->format(app('company')['date_format']);
//              })
//         ->make(true);
// }


// use Illuminate\Support\Carbon;
// use Illuminate\Support\Facades\DB;

// public function CashInDatatable(Request $request)
// {
//     $user = auth()->user();
//     $today = Carbon::today();

//     // Group payments by user and get total + latest payment date
//     $query = DB::table('customer_payments')
//         ->select(
//             'created_by',
//             DB::raw('SUM(amount) as total_paid_today'),
//             DB::raw('MAX(payment_date) as last_payment_date')
//         )
//         ->whereDate('created_at', $today)
//         ->groupBy('created_by');

//     // Admin filter by user
//     if ($user->role_id == 1 && $request->filled('user_id')) {
//         $query->where('created_by', $request->input('user_id'));
//     }

//     // Regular user only sees their own
//     if ($user->role_id != 1) {
//         $query->where('created_by', $user->id);
//     }

//     $data = $query->get();

//     return DataTables::of($data)
//         ->addIndexColumn()
//         ->addColumn('username', function ($row) {
//             return DB::table('users')->where('id', $row->created_by)->value('username') ?? '—';
//         })
//         ->addColumn('paid_amount', function ($row) {
//             return number_format($row->total_paid_today, 2);
//         })
//         ->addColumn('payment_date', function ($row) {
//             return $row->last_payment_date ? date('Y-m-d', strtotime($row->last_payment_date)) : '—';
//         })
//         ->make(true);
// }



// use Illuminate\Support\Carbon;
// use Illuminate\Support\Facades\DB;

// public function CashInDatatable(Request $request)
// {
//     $user = auth()->user();

//     // Base query
//     $query = CustomerPayment::with('party');

//     if ($user->role_id == 1 && $request->filled('user_id')) {
//         $salesmanId = $request->input('user_id');

//         // ✅ Filtering directly on CustomerPayment.created_by
//         $query->where('created_by', $salesmanId);
//     }

//     if ($user->role_id != 1) {
//         $query->where('created_by', auth()->user()->id);
//     }

//     // 📅 Filter by from_date
//     if ($request->filled('from_date')) {
//         $query->whereDate('payment_date', '>=', $this->toSystemDateFormat($request->from_date));
//     }

//     // 📅 Filter by to_date
//     if ($request->filled('to_date')) {
//         $query->whereDate('payment_date', '<=', $this->toSystemDateFormat($request->to_date));
//     }

//     // ✅ Filter only today's records
//     $query->whereDate('payment_date', Carbon::today());

//     $data = $query->get();

//     // Optional filter: show only customers who reached credit limit
//     if ($request->filled('reached_credit_limit') && $request->reached_credit_limit == true) {
//         $data = $data->filter(function ($row) {
//             $totalOrders = SaleOrder::where('party_id', $row->party_id)->sum('grand_total');
//             $totalPaid   = CustomerPayment::where('party_id', $row->party_id)->sum('amount');
//             $remaining   = $totalOrders - $totalPaid;

//             $creditLimit = Party::where('id', $row->party_id)->value('credit_limit');

//             return $remaining >= $creditLimit; // or $remaining > $creditLimit based on your preference
//         });
//     }

//     return DataTables::of($data)
//         ->addIndexColumn()
//         // ->addColumn('customer_name', function ($row) {
//         //     return $row->party->first_name . ' ' . $row->party->last_name;
//         // })
//         ->addColumn('created_by', function ($row) {
//             return $row->createdBy->username ?? '—';
//         })
//         ->addColumn('paid_amount', function ($row) {
//             $totalPaidToday = DB::table('customer_payments')
//                 ->whereDate('created_at', Carbon::today())
//                 ->sum('amount');

//             return $totalPaidToday;
//         })
//         ->addColumn('payment_date', function ($row) {
//             return $row->payment_date;
//         })
//         ->addColumn('action', function ($row) {
//             $id = $row->id;
//             $deleteUrl = route('order.payment.delete', ['id' => $id]);

//             return '<div class="dropdown ms-auto">
//                 <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown">
//                     <i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
//                 </a>
//                 <ul class="dropdown-menu">
//                     <li>
//                         <button type="button" class="dropdown-item text-danger deleteRequest" data-delete-id=' . $id . '>
//                             <i class="bx bx-trash"></i> ' . __('app.delete') . '
//                         </button>
//                     </li>
//                 </ul>
//             </div>';
//         })
//         ->rawColumns(['action'])
//         ->make(true);
// }


    // public function CashInDatatable(Request $request)
    // {
    //     $user = auth()->user();
    //     $today = Carbon::today();

    //     // Base query using query builder instead of Eloquent
    //     $query = DB::table('customer_payments')
    //         ->select(
    //             'created_by',
    //             DB::raw('SUM(amount) as total_paid'),
    //             DB::raw('MAX(payment_date) as latest_payment_date'),
    //         )
    //         // ->whereDate('payment_date', $today)
    //         ->groupBy('created_by');

    //     // Admin: filter by selected user
    //     if ($user->role_id == 1 && $request->filled('user_id')) {
    //         $query->where('created_by', $request->input('user_id'));
    //     }

    //     // Non-admin: restrict to own records
    //     if ($user->role_id != 1) {
    //         $query->where('created_by', $user->id);
    //     }

    //     $data = $query->get();

    //     return DataTables::of($data)
    //         ->addIndexColumn()
    //          ->addColumn('id', function ($row) {
    //     return $row->created_by;
    //     })
    //         ->addColumn('created_by', function ($row) {
    //             return DB::table('users')->where('id', $row->created_by)->value('username') ?? '—';
    //         })
    //         ->addColumn('paid_amount', function ($row) {
    //             return number_format($row->total_paid, 2);
    //         })
    //         ->addColumn('payment_date', function ($row) {
    //             return $row->latest_payment_date ?? '—';
    //         })
    //         ->addColumn('action', function ($row) {
    //             return '<div class="dropdown ms-auto">
    //                 <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown">
    //                     <i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
    //                 </a>
    //                 <ul class="dropdown-menu">
    //                     <li>
    //                         <button type="button" class="dropdown-item text-danger deleteRequest" data-delete-id="' . $row->created_by . '">
    //                             <i class="bx bx-trash"></i> ' . __('app.delete') . '
    //                         </button>
    //                     </li>
    //                 </ul>
    //             </div>';
    //         })
    //         ->rawColumns(['action'])
    //         ->make(true);
    // }


    // public function CashInDatatable(Request $request)
    // {
    //     $user = auth()->user();

    //     // Base query using query builder
    //     $query = DB::table('customer_payments')
    //         ->select(
    //             'created_by',
    //             DB::raw('DATE(payment_date) as payment_date'),
    //             DB::raw('SUM(amount) as total_paid')
    //         )
    //     ->groupBy('created_by', DB::raw('DATE(payment_date)'));

        

    //     // 🔹 If current user is admin, optionally filter by selected user
    //     if ($user->role_id == 1 && $request->filled('user_id')) {
    //         $query->where('created_by', $request->input('user_id'));
    //     }

    //     // 🔹 If current user is not admin, show only their own records
    //     if ($user->role_id != 1) {
    //         $query->where('created_by', $user->id);
    //     }

    //     $data = $query->get();

    //     return DataTables::of($data)
    //         ->addIndexColumn()
    //         ->addColumn('id', function ($row) {
    //             return $row->created_by; // Add unique ID column for DataTables
    //         })
    //         ->addColumn('created_by', function ($row) {
    //             return DB::table('users')->where('id', $row->created_by)->value('username') ?? '—';
    //         })
    //         ->addColumn('paid_amount', function ($row) {
    //             return number_format($row->total_paid, 2);
    //         })
    //         ->addColumn('payment_date', function ($row) {
    //             return $row->payment_date ?? '—';
    //         })
    //         ->addColumn('action', function ($row) {
    //             return '<div class="dropdown ms-auto">
    //                 <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown">
    //                     <i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
    //                 </a>
    //                 <ul class="dropdown-menu">
    //                     <li>
    //                         <button type="button" class="dropdown-item text-danger deleteRequest" data-delete-id="' . $row->created_by . '">
    //                             <i class="bx bx-trash"></i> ' . __('app.delete') . '
    //                         </button>
    //                     </li>
    //                 </ul>
    //             </div>';
    //         })
    //         ->rawColumns(['action'])
    //         ->make(true);
    // }


    function convertDate($date)
    {
        if (!$date) return null;

        // Convert 30/11/2025 → 2025-11-30
        $parts = explode('/', $date);

        if (count($parts) === 3) {
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }

        return null;
    }



    public function CashInDatatable(Request $request)
    {
        $user = auth()->user();

        // Base query
        $query = DB::table('customer_payments')
            ->select(
                'created_by',
                DB::raw('DATE(payment_date) as payment_date'),
                DB::raw('SUM(amount) as total_paid')
            )
            ->groupBy('created_by', DB::raw('DATE(payment_date)'));

        /**
         * 🔹 ADMIN FILTER (unchanged)
         */
        if ($user->role_id == 1) {
            // Admin → apply only if user is selected
            if ($request->filled('user_id')) {
                $query->where('created_by', $request->user_id);
            }
        } else {
            // Not admin → only own data
            $query->where('created_by', $user->id);
        }

        /**
         * 🔹 DATE RANGE FILTER (new)
         */
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from = $this->convertDate($request->from_date);
            $to   = $this->convertDate($request->to_date);

            if ($from && $to) {
                $query->whereBetween(DB::raw('DATE(payment_date)'), [$from, $to]);
            } elseif ($from) {
                $query->whereDate('payment_date', '>=', $from);
            } elseif ($to) {
                $query->whereDate('payment_date', '<=', $to);
            }

        }

        $data = $query->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('id', fn($row) => $row->created_by)
            ->addColumn('created_by', fn($row) =>
                DB::table('users')->where('id', $row->created_by)->value('username') ?? '—'
            )
            ->addColumn('paid_amount', fn($row) => number_format($row->total_paid, 2))
            ->addColumn('payment_date', fn($row) => $row->payment_date ?? '—')
            ->addColumn('action', function ($row) {
                return '<div class="dropdown ms-auto">
                    <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown">
                        <i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <button type="button" class="dropdown-item text-danger deleteRequest" 
                                data-delete-id="' . $row->created_by . '">
                                <i class="bx bx-trash"></i> ' . __('app.delete') . '
                            </button>
                        </li>
                    </ul>
                </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }







}
