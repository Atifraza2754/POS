<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use App\Models\Prefix;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Purchase\Purchase;
use App\Models\Items\Item;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use App\Enums\App;
use App\Enums\General;
use App\Services\PaymentTypeService;
use App\Services\GeneralDataService;
use App\Services\PaymentTransactionService;
use App\Http\Requests\PurchaseReturnRequest;
use App\Services\AccountTransactionService;
use App\Services\ItemTransactionService;
use App\Models\Items\ItemSerial;
use App\Models\Items\ItemBatchTransaction;
use Carbon\Carbon;
use App\Services\CacheService;
use App\Enums\ItemTransactionUniqueCode;
use App\Models\Items\ItemTransaction;
use App\Models\PaymentTransaction;
use App\Services\ItemService;

use App\Services\Communication\Email\PurchaseReturnEmailNotificationService;
use App\Services\Communication\Sms\PurchaseReturnSmsNotificationService;

use Mpdf\Mpdf;

class PurchaseReturnController extends Controller
{
    use FormatNumber;

    use FormatsDateInputs;

    protected $companyId;

    private $paymentTypeService;

    private $paymentTransactionService;

    private $accountTransactionService;

    private $itemTransactionService;

    public $previousHistoryOfItems;

    public $purchaseReturnEmailNotificationService;

    public $purchaseReturnSmsNotificationService;

    private $itemService;

    public function __construct(PaymentTypeService $paymentTypeService,
                                PaymentTransactionService $paymentTransactionService,
                                AccountTransactionService $accountTransactionService,
                                ItemTransactionService $itemTransactionService,
                                PurchaseReturnEmailNotificationService $purchaseReturnEmailNotificationService,
                                PurchaseReturnSmsNotificationService $purchaseReturnSmsNotificationService,
                                ItemService $itemService
                            )
    {
        $this->companyId = App::APP_SETTINGS_RECORD_ID->value;
        $this->paymentTypeService = $paymentTypeService;
        $this->paymentTransactionService = $paymentTransactionService;
        $this->accountTransactionService = $accountTransactionService;
        $this->itemTransactionService = $itemTransactionService;
        $this->purchaseReturnEmailNotificationService = $purchaseReturnEmailNotificationService;
        $this->purchaseReturnSmsNotificationService = $purchaseReturnSmsNotificationService;
        $this->itemService = $itemService;
        $this->previousHistoryOfItems = [];
    }

    /**
     * Create a new PurchaseReturn.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View  {
        $prefix = Prefix::findOrNew($this->companyId);
        $categories = DB::table('party_categories')->where('status', 1)->pluck('name', 'id');
        $lastCountId = $this->getLastCountId();
        $selectedPaymentTypesArray = json_encode($this->paymentTypeService->selectedPaymentTypesArray());
        $data = [
            'prefix_code' => $prefix->purchase_return,
            'count_id' => ($lastCountId+1),
        ];
        return view('purchase.return.create',compact('data', 'selectedPaymentTypesArray','categories'));
    }

    /**
     * Get last count ID
     * */
    public function getLastCountId(){
        return PurchaseReturn::select('count_id')->orderBy('id', 'desc')->first()?->count_id ?? 0;
    }

    /**
     * List the PurchaseReturns
     *
     * @return \Illuminate\View\View
     */
    public function list() : View {
        return view('purchase.return.list');
    }


    /**
     * convert Purchase.
     *
     * @param int $id The ID of the expense to edit.
     * @return \Illuminate\View\View
     */
    public function convertToPurchaseReturn($id) : View | RedirectResponse {
        //Validate Existance of Converted Purchase Orders
        // $convertedBill = PurchaseReturn::where('purchase_id', $id)->first();
        // if($convertedBill){
        //     session(['record' => [
        //                             'type' => 'success',
        //                             'status' => __('purchase.already_converted'), //Save or update
        //                         ]]);
        //     //Already Converted, Redirect it.
        //     return redirect()->route('purchase.return.details', ['id' => $convertedBill->id]);
        // }
        
        $return = Purchase::with(['party',
                                        'itemTransaction' => [
                                            'item',
                                            'tax',
                                            'batch.itemBatchMaster',
                                            'itemSerialTransaction.itemSerialMaster'
                                        ]])->findOrFail($id);
        //Convert Code adjustment - start
        $return->operation = 'convert';
        $return->formatted_return_date = $this->toSystemDateFormat($return->purchase_date);
        $return->reference_no = $return->purchase_code;
        $return->paid_amount = 0;
        //Convert Code adjustment - end

        $prefix = Prefix::findOrNew($this->companyId);
        $lastCountId = $this->getLastCountId();
        $return->prefix_code = $prefix->purchase_return;
        $return->count_id = ($lastCountId+1);

        $return->formatted_return_date = $this->toUserDateFormat(date('Y-m-d'));

        // Item Details
        // Prepare item transactions with associated units
        $allUnits = CacheService::get('unit');

        $itemTransactions = $return->itemTransaction->map(function ($transaction) use ($allUnits ) {
            $itemData = $transaction->toArray();

            // Use the getOnlySelectedUnits helper function
            $selectedUnits = getOnlySelectedUnits(
                $allUnits,
                $transaction->item->base_unit_id,
                $transaction->item->secondary_unit_id
            );

            // Add unitList to the item data
            $itemData['unitList'] = $selectedUnits->toArray();

            // Get item serial transactions with associated item serial master data
            $itemSerialTransactions = $transaction->itemSerialTransaction->map(function ($serialTransaction) {
                return $serialTransaction->itemSerialMaster->toArray();
            })->toArray();

            // Add itemSerialTransactions to the item data
            $itemData['itemSerialTransactions'] = $itemSerialTransactions;

            return $itemData;
        })->toArray();

        $itemTransactionsJson = json_encode($itemTransactions);

        //Payment Details
        $selectedPaymentTypesArray = json_encode($this->paymentTransactionService->getPaymentRecordsArray($return));

        $taxList = CacheService::get('tax')->toJson();

        $paymentHistory = [];

         $categories = DB::table('party_categories')->where('status', 1)->pluck('name', 'id');

        return view('purchase.return.edit', compact('categories','taxList', 'return', 'itemTransactionsJson','selectedPaymentTypesArray', 'paymentHistory'));
    }

     /**
     * Edit a PurchaseReturn.
     *
     * @param int $id The ID of the expense to edit.
     * @return \Illuminate\View\View
     */
    public function edit($id) : View {
        $return = PurchaseReturn::with(['party',
                                        'itemTransaction' => [
                                            'item',
                                            'tax',
                                            'batch.itemBatchMaster',
                                            'itemSerialTransaction.itemSerialMaster'
                                        ]])->findOrFail($id);
        $return->operation = 'update';

        // Item Details
        // Prepare item transactions with associated units
        $allUnits = CacheService::get('unit');

        $itemTransactions = $return->itemTransaction->map(function ($transaction) use ($allUnits ) {
            $itemData = $transaction->toArray();

            // Use the getOnlySelectedUnits helper function
            $selectedUnits = getOnlySelectedUnits(
                $allUnits,
                $transaction->item->base_unit_id,
                $transaction->item->secondary_unit_id
            );

            // Add unitList to the item data
            $itemData['unitList'] = $selectedUnits->toArray();

            // Get item serial transactions with associated item serial master data
            $itemSerialTransactions = $transaction->itemSerialTransaction->map(function ($serialTransaction) {
                return $serialTransaction->itemSerialMaster->toArray();
            })->toArray();

            // Add itemSerialTransactions to the item data
            $itemData['itemSerialTransactions'] = $itemSerialTransactions;

            return $itemData;
        })->toArray();

        $itemTransactionsJson = json_encode($itemTransactions);

        //Payment Details
        //$selectedPaymentTypesArray = json_encode($this->paymentTransactionService->getPaymentRecordsArray($return));
        $selectedPaymentTypesArray = json_encode($this->paymentTypeService->selectedPaymentTypesArray());

        $paymentHistory = $this->paymentTransactionService->getPaymentRecordsArray($return);

        $taxList = CacheService::get('tax')->toJson();
         $categories = DB::table('party_categories')->where('status', 1)->pluck('name', 'id');


        return view('purchase.return.edit', compact('categories','taxList', 'return', 'itemTransactionsJson','selectedPaymentTypesArray', 'paymentHistory'));
    }

    /**
     * View Purchase Order details
     *
     * @param int $id, the ID of the order
     * @return \Illuminate\View\View
     */
    public function details($id) : View {

        $return = PurchaseReturn::with(['party',
                                        'itemTransaction' => [
                                            'item',
                                            'tax',
                                            'batch.itemBatchMaster',
                                            'itemSerialTransaction.itemSerialMaster'
                                        ]])->findOrFail($id);

        //Payment Details
        $selectedPaymentTypesArray = json_encode($this->paymentTransactionService->getPaymentRecordsArray($return));

        //Batch Tracking Row count for invoice columns setting
        $batchTrackingRowCount = (new GeneralDataService())->getBatchTranckingRowCount();

        return view('purchase.return.details', compact('return','selectedPaymentTypesArray', 'batchTrackingRowCount'));
    }

    /**
     * Print Purchase
     *
     * @param int $id, the ID of the purchase
     * @return \Illuminate\View\View
     */
    public function print($id, $isPdf = false) : View {

        $purchase = PurchaseReturn::with(['party',
                                        'itemTransaction' => [
                                            'item',
                                            'tax',
                                            'batch.itemBatchMaster',
                                            'itemSerialTransaction.itemSerialMaster'
                                        ]])->findOrFail($id);

        //Payment Details
        $selectedPaymentTypesArray = json_encode($this->paymentTransactionService->getPaymentRecordsArray($purchase));

        //Batch Tracking Row count for invoice columns setting
        $batchTrackingRowCount = (new GeneralDataService())->getBatchTranckingRowCount();

        $invoiceData = [
            'name' => __('purchase.debit_note'),
        ];

        return view('print.purchase-return', compact('isPdf', 'invoiceData', 'purchase','selectedPaymentTypesArray','batchTrackingRowCount'));

    }


    /**
     * Generate PDF using View: print() method
     * */
    public function generatePdf($id){
        $html = $this->print($id, isPdf:true);

        $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 2,
                'margin_right' => 2,
                'margin_top' => 2,
                'margin_bottom' => 2,
                'default_font' => 'dejavusans',
                //'direction' => 'rtl',
            ]);

        $mpdf->showImageErrors = true;
        $mpdf->WriteHTML($html);
        /**
         * Display in browser
         * 'I'
         * Downloadn PDF
         * 'D'
         * */
        $mpdf->Output('Purchase-Bill-'.$id.'.pdf', 'D');
    }

    /**
     * Store Records
     * */
    // public function store(PurchaseReturnRequest $request) : JsonResponse  {
    //     try {

    //         DB::beginTransaction();
    //         // Get the validated data from the expenseRequest
    //         $validatedData = $request->validated();

    //         if($request->operation == 'save' || $request->operation == 'convert'){
    //             // Create a new expense record using Eloquent and save it
    //             $newRuturn = PurchaseReturn::create($validatedData);

    //             $request->request->add(['return_id' => $newRuturn->id]);

    //         }
    //         else{
    //             $fillableColumns = [
    //                 'party_id'              => $validatedData['party_id'],
    //                 'return_date'           => $validatedData['return_date'],
    //                 'reference_no'          => $validatedData['reference_no'],
    //                 'prefix_code'           => $validatedData['prefix_code'],
    //                 'count_id'              => $validatedData['count_id'],
    //                 'return_code'           => $validatedData['return_code'],
    //                 'note'                  => $validatedData['note'],
    //                 'round_off'             => $validatedData['round_off'],
    //                 'grand_total'           => $validatedData['grand_total'],
    //                 'state_id'              => $validatedData['state_id'],
    //             ];

    //             $newRuturn = PurchaseReturn::findOrFail($validatedData['return_id']);
    //             $newRuturn->update($fillableColumns);

    //             /**
    //             * Before deleting ItemTransaction data take the
    //             * old data of the item_serial_master_id
    //             * to update the item_serial_quantity
    //             * */
    //             $this->previousHistoryOfItems = $this->itemTransactionService->getHistoryOfItems($newRuturn);


    //             $newRuturn->itemTransaction()->delete();
    //             //$newRuturn->accountTransaction()->delete();

    //             //Purchase Account Update
    //             foreach($newRuturn->accountTransaction as $purchaseAccount){
    //                 //get account if of model with tax accounts
    //                 $purchaseAccountId = $purchaseAccount->account_id;

    //                 //Delete purchase and tax account
    //                 $purchaseAccount->delete();

    //                 //Update  account
    //                 $this->accountTransactionService->calculateAccounts($purchaseAccountId);
    //             }//purchase account


    //             // Check if paymentTransactions exist
    //             // $paymentTransactions = $newRuturn->paymentTransaction;
    //             // if ($paymentTransactions->isNotEmpty()) {
    //             //     foreach ($paymentTransactions as $paymentTransaction) {
    //             //         $accountTransactions = $paymentTransaction->accountTransaction;
    //             //         if ($accountTransactions->isNotEmpty()) {
    //             //             foreach ($accountTransactions as $accountTransaction) {
    //             //                 //Purchase Account Update
    //             //                 $accountId = $accountTransaction->account_id;
    //             //                 // Do something with the individual accountTransaction
    //             //                 $accountTransaction->delete(); // Or any other operation

    //             //                 $this->accountTransactionService->calculateAccounts($accountId);
    //             //             }
    //             //         }
    //             //     }
    //             // }

    //             // $newRuturn->paymentTransaction()->delete();
    //         }

    //         $request->request->add(['modelName' => $newRuturn]);

    //         /**
    //          * Save Table Items in Purchase Items Table
    //          * */
    //         $PurchaseItemsArray = $this->savePurchaseReturnItems($request);
    //         if(!$PurchaseItemsArray['status']){
    //             throw new \Exception($PurchaseItemsArray['message']);
    //         }
    //         /**
    //          * Save Expense Payment Records
    //          * */
    //         $purchasePaymentsArray = $this->savePurchaseReturnPayments($request);
    //         if(!$purchasePaymentsArray['status']){
    //             throw new \Exception($purchasePaymentsArray['message']);
    //         }

    //         /**
    //         * Payment Should not be less than 0
    //         * */
    //         $paidAmount = $newRuturn->refresh('paymentTransaction')->paymentTransaction->sum('amount');
    //         if($paidAmount < 0){
    //             throw new \Exception(__('payment.paid_amount_should_not_be_less_than_zero'));
    //         }

    //         /**
    //          * Paid amount should not be greater than grand total
    //          * */
    //         if($paidAmount > $newRuturn->grand_total){
    //             throw new \Exception(__('payment.payment_should_not_be_greater_than_grand_total')."<br>Paid Amount : ". $this->formatWithPrecision($paidAmount)."<br>Grand Total : ". $this->formatWithPrecision($newRuturn->grand_total). "<br>Difference : ".$this->formatWithPrecision($paidAmount-$newRuturn->grand_total));
    //         }

    //         /**
    //          * Update Purchase Model
    //          * Total Paid Amunt
    //          * */
    //         if(!$this->paymentTransactionService->updateTotalPaidAmountInModel($request->modelName)){
    //             throw new \Exception(__('payment.failed_to_update_paid_amount'));
    //         }

    //         /**
    //          * Update Account Transaction entry
    //          * Call Services
    //          * @return boolean
    //          * */
    //         /*$accountTransactionStatus = $this->accountTransactionService->purchaseAccountTransaction($request->modelName);
    //         if(!$accountTransactionStatus){
    //             throw new \Exception(__('payment.failed_to_update_account'));
    //         }*/

    //         /**
    //          * UPDATE HISTORY DATA
    //          * LIKE: ITEM SERIAL NUMBER QUNATITY, BATCH NUMBER QUANTITY, GENERAL DATA QUANTITY
    //          * */
    //         $this->itemTransactionService->updatePreviousHistoryOfItems($request->modelName, $this->previousHistoryOfItems);

    //         DB::commit();

    //         // Regenerate the CSRF token
    //         //Session::regenerateToken();

    //         return response()->json([
    //             'status'    => true,
    //             'message' => __('app.record_saved_successfully'),
    //             'id' => $request->return_id,

    //         ]);

    //     } catch (\Exception $e) {
    //             DB::rollback();

    //             return response()->json([
    //                 'status' => false,
    //                 'message' => $e->getMessage(),
    //             ], 409);

    //     }

    // }

    // public function store(PurchaseReturnRequest $request): JsonResponse
    // {
    //     DB::beginTransaction();

    //     try {

    //         $validated = $request->validated();

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 1. CREATE OR UPDATE PURCHASE RETURN
    //         |--------------------------------------------------------------------------
    //         */
    //         if ($request->operation === 'save') {
    //             $purchaseReturn = PurchaseReturn::create($validated);
    //         } else {
    //             $purchaseReturn = PurchaseReturn::findOrFail($validated['return_id']);
    //             $purchaseReturn->update($validated);
    //         }

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 2. REVERSE STOCK (NEGATIVE ITEM TRANSACTIONS)
    //         |--------------------------------------------------------------------------
    //         */
    //         foreach ($request->item_id as $i => $itemId) {

    //             if (!isset($request->quantity[$i]) || $request->quantity[$i] <= 0) {
    //                 continue;
    //             }

    //             $item = Item::findOrFail($itemId);

    //             // Record negative item transaction
    //             $transaction = $this->itemTransactionService->recordItemTransactionEntry(
    //                 $purchaseReturn,
    //                 [
    //                     'warehouse_id'     => $request->warehouse_id[$i],
    //                     'transaction_date' => $purchaseReturn->return_date,
    //                     'item_id'          => $itemId,
    //                     'tracking_type'    => $item->tracking_type,
    //                     'quantity'         => -abs($request->quantity[$i]), // 🔴 NEGATIVE
    //                     'unit_id'          => $request->unit_id[$i],
    //                     'unit_price'       => $request->purchase_price[$i],
    //                     'discount'         => $request->discount[$i] ?? 0,
    //                     'discount_type'    => $request->discount_type[$i] ?? null,
    //                     'discount_amount'  => $request->discount_amount[$i] ?? 0,
    //                     'tax_id'           => $request->tax_id[$i] ?? null,
    //                     'tax_type'         => $request->tax_type[$i] ?? null,
    //                     'tax_amount'       => $request->tax_amount[$i] ?? 0,
    //                     'total'            => -abs($request->total[$i]), // 🔴 NEGATIVE
    //                 ]
    //             );

    //             if (!$transaction) {
    //                 throw new \Exception('Failed to reverse item stock');
    //             }

    //             // Update actual stock
    //             $item->current_stock -= $request->quantity[$i];
    //             $item->save();
    //         }

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 3. REVERSE PAYMENTS (NEGATIVE PAYMENT ENTRY)
    //         |--------------------------------------------------------------------------
    //         */
    //         $paidAmount = $purchaseReturn->paymentTransaction()->sum('amount');

    //         if ($paidAmount > 0) {
    //             $purchaseReturn->paymentTransaction()->create([
    //                 'amount'           => -$paidAmount, // 🔴 NEGATIVE
    //                 'note'             => 'Purchase Return',
    //                 'transaction_type' => 'Purchase Return',
    //                 'payment_type_id'  => 1,
    //                 'supplier_id'      => $purchaseReturn->party_id,
    //                 'transaction_date' => now(),
    //                 'created_by'       => auth()->id(),
    //                 'updated_by'       => auth()->id(),
    //             ]);
    //         }

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 4. UPDATE TOTAL PAID AMOUNT
    //         |--------------------------------------------------------------------------
    //         */
    //         $this->paymentTransactionService
    //             ->updateTotalPaidAmountInModel($purchaseReturn);

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 5. MARK ORIGINAL PURCHASE AS RETURNED
    //         |--------------------------------------------------------------------------
    //         */
    //         // if ($purchaseReturn->purchase_id) {
    //         //     Purchase::where('id', $purchaseReturn->purchase_id)->update([
    //         //         'is_return'    => true,
    //         //         'return_of_id' => $purchaseReturn->id,
    //         //     ]);
    //         // }

    //         DB::commit();

    //         return response()->json([
    //             'status'  => true,
    //             'message' => __('app.record_saved_successfully'),
    //             'id'      => $purchaseReturn->id,
    //         ]);

    //     } catch (\Exception $e) {

    //         DB::rollBack();

    //         return response()->json([
    //             'status'  => false,
    //             'message' => $e->getMessage(),
    //         ], 409);
    //     }
    // }



    // public function store(PurchaseReturnRequest $request): JsonResponse
    // {
    //     DB::beginTransaction();

    //     try {

    //         $validated = $request->validated();

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 1. CREATE PURCHASE RETURN (ALWAYS NEW)
    //         |--------------------------------------------------------------------------
    //         */
    //         $purchaseReturn = PurchaseReturn::create($validated);

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 2. REVERSE STOCK (NEGATIVE ITEM TRANSACTIONS)
    //         |--------------------------------------------------------------------------
    //         */
    //         foreach ($request->item_id as $i => $itemId) {

    //             if (!isset($request->quantity[$i]) || $request->quantity[$i] <= 0) {
    //                 continue;
    //             }

    //             $item = Item::findOrFail($itemId);

    //             // Negative item transaction (RETURN)
    //             $transaction = $this->itemTransactionService->recordItemTransactionEntry(
    //                 $purchaseReturn,
    //                 [
    //                     'warehouse_id'     => $request->warehouse_id[$i],
    //                     'transaction_date' => $purchaseReturn->return_date,
    //                     'item_id'          => $itemId,
    //                     'tracking_type'    => $item->tracking_type,
    //                     'quantity'         => -abs($request->quantity[$i]), // 🔴 NEGATIVE
    //                     'unit_id'          => $request->unit_id[$i],
    //                     'unit_price'       => $request->purchase_price[$i],
    //                     'discount'         => $request->discount[$i] ?? 0,
    //                     'discount_type'    => $request->discount_type[$i] ?? null,
    //                     'discount_amount'  => $request->discount_amount[$i] ?? 0,
    //                     'tax_id'           => $request->tax_id[$i] ?? null,
    //                     'tax_type'         => $request->tax_type[$i] ?? null,
    //                     'tax_amount'       => $request->tax_amount[$i] ?? 0,
    //                     'total'            => -abs($request->total[$i]), // 🔴 NEGATIVE
    //                     'mrp'              => $request->mrp[$i] ?? 0,
                        
    //                 ]
    //             );

    //             if (!$transaction) {
    //                 throw new \Exception('Failed to reverse item stock');
    //             }

    //             // Update current stock
    //             $item->current_stock -= $request->quantity[$i];
    //             $item->save();
    //         }

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 3. REVERSE PAYMENTS (NEGATIVE PAYMENT ENTRY)
    //         |--------------------------------------------------------------------------
    //         */
    //         $paidAmount = $purchaseReturn->paymentTransaction()->sum('amount');

    //         if ($paidAmount > 0) {
    //             $purchaseReturn->paymentTransaction()->create([
    //                 'amount'           => -$paidAmount,
    //                 'note'             => 'Purchase Return',
    //                 'transaction_type' => 'Purchase Return',
    //                 'payment_type_id'  => 1,
    //                 'supplier_id'      => $purchaseReturn->party_id,
    //                 'transaction_date' => now(),
    //                 'created_by'       => auth()->id(),
    //                 'updated_by'       => auth()->id(),
    //             ]);
    //         }

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 4. UPDATE TOTAL PAID AMOUNT IN MODEL
    //         |--------------------------------------------------------------------------
    //         */
    //         $this->paymentTransactionService
    //             ->updateTotalPaidAmountInModel($purchaseReturn);

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 5. MARK ORIGINAL PURCHASE AS RETURNED (OPTIONAL)
    //         |--------------------------------------------------------------------------
    //         */
    //         if (!empty($purchaseReturn->purchase_id)) {
    //             Purchase::where('id', $purchaseReturn->purchase_id)->update([
    //                 'is_return'    => true,
    //                 'return_of_id' => $purchaseReturn->id,
    //             ]);
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'status'  => true,
    //             'message' => __('app.record_saved_successfully'),
    //             'id'      => $purchaseReturn->id,
    //         ]);

    //     } catch (\Exception $e) {

    //         DB::rollBack();

    //         return response()->json([
    //             'status'  => false,
    //             'message' => $e->getMessage(),
    //         ], 409);
    //     }
    // }



    // public function store(PurchaseReturnRequest $request): JsonResponse
    // {
    //     dd($request->all());
    //     DB::beginTransaction();

    //     try {

    //         $validated = $request->validated();

    //         /*
    //         |------------------------------------------------------------------
    //         | 1. CREATE PURCHASE RETURN
    //         |------------------------------------------------------------------
    //         */
    //         $purchaseReturn = PurchaseReturn::create($validated);

    //         /*
    //         |------------------------------------------------------------------
    //         | 2. REVERSE STOCK (NEGATIVE ITEM TRANSACTIONS)
    //         |------------------------------------------------------------------
    //         */
    //         foreach ($request->item_id as $i => $itemId) {

    //             $qty = abs($request->quantity[$i] ?? 0);

    //             if ($qty <= 0) {
    //                 continue;
    //             }

    //             $item = Item::lockForUpdate()->findOrFail($itemId);

    //             // Record negative stock transaction
    //             $transaction = $this->itemTransactionService
    //                 ->recordItemTransactionEntry(
    //                     $purchaseReturn,
    //                     [
    //                         'warehouse_id'     => $request->warehouse_id[$i],
    //                         'transaction_date' => $purchaseReturn->return_date,
    //                         'item_id'          => $itemId,
    //                         'tracking_type'    => $item->tracking_type,
    //                         'quantity'         => -$qty, // 🔴 negative
    //                         'unit_id'          => $request->unit_id[$i],
    //                         'unit_price'       => $request->purchase_price[$i],
    //                         'discount'         => $request->discount[$i] ?? 0,
    //                         'discount_type'    => $request->discount_type[$i] ?? null,
    //                         'discount_amount'  => $request->discount_amount[$i] ?? 0,
    //                         'tax_id'           => $request->tax_id[$i] ?? null,
    //                         'tax_type'         => $request->tax_type[$i] ?? null,
    //                         'tax_amount'       => $request->tax_amount[$i] ?? 0,
    //                         'total'            => -abs($request->total[$i]), // 🔴 negative
    //                         'mrp'              => $request->mrp[$i] ?? 0,
    //                     ]
    //                 );

    //             if (!$transaction) {
    //                 throw new \Exception('Failed to create item transaction');
    //             }

    //             // Reduce physical stock
    //             $item->current_stock -= $qty;
    //             $item->save();

    //             /*
    //             |--------------------------------------------------------------
    //             | OPTIONAL (RECOMMENDED): SAVE RETURN ITEMS
    //             |--------------------------------------------------------------
    //             */
    //             // $purchaseReturn->items()->create([
    //             //     'item_id'   => $itemId,
    //             //     'quantity'  => $qty,
    //             //     'unit_id'   => $request->unit_id[$i],
    //             //     'price'     => $request->purchase_price[$i],
    //             //     'total'     => $request->total[$i],
    //             // ]);
    //         }

    //         /*
    //         |------------------------------------------------------------------
    //         | 3. REVERSE ORIGINAL PURCHASE PAYMENTS
    //         |------------------------------------------------------------------
    //         */
    //         if (!empty($validated->purchase_id)) {

    //             $paidAmount = PaymentTransaction::where('model_type', Purchase::class)
    //                 ->where('model_id', $validated->purchase_id)
    //                 ->sum('amount');

    //             if ($paidAmount > 0) {
    //                 $purchaseReturn->paymentTransaction()->create([
    //                     'amount'           => -$paidAmount,
    //                     'note'             => 'Purchase Return Adjustment',
    //                     'transaction_type' => 'Purchase Return',
    //                     'payment_type_id'  => 1,
    //                     'supplier_id'      => $purchaseReturn->party_id,
    //                     'transaction_date' => now(),
    //                     'created_by'       => auth()->id(),
    //                     'updated_by'       => auth()->id(),
    //                 ]);
    //             }
    //         }

    //         /*
    //         |------------------------------------------------------------------
    //         | 4. UPDATE RETURN PAID AMOUNT
    //         |------------------------------------------------------------------
    //         */
    //         $this->paymentTransactionService
    //             ->updateTotalPaidAmountInModel($purchaseReturn);

    //         /*
    //         |------------------------------------------------------------------
    //         | 5. MARK ORIGINAL PURCHASE AS RETURNED (FLAG ONLY)
    //         |------------------------------------------------------------------
    //         */
    //         if (!empty($validated->purchase_id)) {
    //             Purchase::where('id', $validated->purchase_id)->update([
    //                 'is_return'    => true,
    //                 'return_of_id' => $purchaseReturn->id,
    //             ]);
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'status'  => true,
    //             'message' => __('app.record_saved_successfully'),
    //             'id'      => $purchaseReturn->id,
    //         ]);

    //     } catch (\Exception $e) {

    //         DB::rollBack();

    //         return response()->json([
    //             'status'  => false,
    //             'message' => $e->getMessage(),
    //         ], 409);
    //     }
    // }


    // public function store(PurchaseReturnRequest $request): JsonResponse
    // {
        
    //     DB::beginTransaction();

    //     try {

    //         // ✅ FIX 1: validated() returns ARRAY
    //         $validated = $request->validated();

    //         /*
    //         |------------------------------------------------------------------
    //         | 1. CREATE PURCHASE RETURN
    //         |------------------------------------------------------------------
    //         */
    //         $purchaseReturn = PurchaseReturn::create($validated);

    //         /*
    //         |------------------------------------------------------------------
    //         | 2. REVERSE STOCK (NEGATIVE ITEM TRANSACTIONS)
    //         |------------------------------------------------------------------
    //         */
    //         foreach ($request->item_id as $i => $itemId) {

    //             $qty = abs($request->quantity[$i] ?? 0);

    //             if ($qty <= 0) {
    //                 continue;
    //             }

    //             $item = Item::lockForUpdate()->findOrFail($itemId);

    //             // Record negative stock transaction
    //             $transaction = $this->itemTransactionService
    //                 ->recordItemTransactionEntry(
    //                     $purchaseReturn,
    //                     [
    //                         'warehouse_id'     => $request->warehouse_id[$i],
    //                         'transaction_date' => $purchaseReturn->return_date,
    //                         'item_id'          => $itemId,
    //                         'tracking_type'    => $item->tracking_type,
    //                         'quantity'         => -$qty, // negative
    //                         'unit_id'          => $request->unit_id[$i],
    //                         'unit_price'       => $request->purchase_price[$i],
    //                         'discount'         => $request->discount[$i] ?? 0,
    //                         'discount_type'    => $request->discount_type[$i] ?? null,
    //                         'discount_amount'  => $request->discount_amount[$i] ?? 0,
    //                         'tax_id'           => $request->tax_id[$i] ?? null,
    //                         'tax_type'         => $request->tax_type[$i] ?? null,
    //                         'tax_amount'       => $request->tax_amount[$i] ?? 0,
    //                         'total'            => -abs($request->total[$i]),
    //                         'mrp'              => $request->mrp[$i] ?? 0,
    //                     ]
    //                 );

    //             if (!$transaction) {
    //                 throw new \Exception('Failed to create item transaction');
    //             }

    //             // Reduce physical stock
    //             $item->current_stock -= $qty;
    //             $item->save();
    //         }

    //         /*
    //         |------------------------------------------------------------------
    //         | 3. REVERSE ORIGINAL PURCHASE PAYMENTS
    //         |------------------------------------------------------------------
    //         */
    //         if (!empty($validated['purchase_id'])) { // ✅ FIX 2

    //             $paidAmount = PaymentTransaction::where('purchase_id', $validated['purchase_id'])
    //             ->sum('amount');


    //             if ($paidAmount > 0) {
    //                 $purchaseReturn->paymentTransaction()->create([
    //                     'amount'           => -$paidAmount,
    //                     'note'             => 'Purchase Return Adjustment',
    //                     'transaction_type' => 'Purchase Return',
    //                     'payment_type_id'  => 1,
    //                     'supplier_id'      => $purchaseReturn->party_id,
    //                     'transaction_date' => now(),
    //                     'created_by'       => auth()->id(),
    //                     'updated_by'       => auth()->id(),
    //                 ]);
    //             }
    //         }

    //         /*
    //         |------------------------------------------------------------------
    //         | 4. UPDATE RETURN PAID AMOUNT
    //         |------------------------------------------------------------------
    //         */
    //         $this->paymentTransactionService
    //             ->updateTotalPaidAmountInModel($purchaseReturn);

    //         /*
    //         |------------------------------------------------------------------
    //         | 5. MARK ORIGINAL PURCHASE AS RETURNED (FLAG ONLY)
    //         |------------------------------------------------------------------
    //         */
    //         if (!empty($validated['purchase_id'])) { // ✅ FIX 3

    //             $purchase = Purchase::lockForUpdate()
    //                 ->find($validated['purchase_id']);

    //             if ($purchase) {
    //                 $purchase->update([
    //                     'is_return'    => true,
    //                     'return_of_id' => $purchaseReturn->id,
    //                 ]);
    //             }
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'status'  => true,
    //             'message' => __('app.record_saved_successfully'),
    //             'id'      => $purchaseReturn->id,
    //         ]);

    //     } catch (\Exception $e) {

    //         DB::rollBack();

    //         return response()->json([
    //             'status'  => false,
    //             'message' => $e->getMessage(),
    //         ], 409);
    //     }
    // }




    // public function store(PurchaseReturnRequest $request): JsonResponse
    // {
    //     DB::beginTransaction();

    //     try {
    //         // 1. VALIDATION
    //         $validated = $request->validated();

    //         /*
    //         |--------------------------------------------------
    //         | 2. CREATE PURCHASE RETURN
    //         |--------------------------------------------------
    //         */
    //         $purchaseReturn = PurchaseReturn::create($validated);

    //         /*
    //         |--------------------------------------------------
    //         | 3. REVERSE STOCK (NEGATIVE ITEM TRANSACTIONS)
    //         |--------------------------------------------------
    //         */
    //         foreach ($request->item_id as $i => $itemId) {

    //             $qty = abs($request->quantity[$i] ?? 0);
    //             if ($qty <= 0) {
    //                 continue;
    //             }

    //             $item = Item::lockForUpdate()->findOrFail($itemId);

    //             $transaction = $this->itemTransactionService
    //                 ->recordItemTransactionEntry(
    //                     $purchaseReturn,
    //                     [
    //                         'warehouse_id'     => $request->warehouse_id[$i],
    //                         'transaction_date' => $purchaseReturn->return_date,
    //                         'item_id'          => $itemId,
    //                         'tracking_type'    => $item->tracking_type,
    //                         'quantity'         => -$qty,
    //                         'unit_id'          => $request->unit_id[$i],
    //                         'unit_price'       => $request->purchase_price[$i],
    //                         'discount'         => $request->discount[$i] ?? 0,
    //                         'discount_type'    => $request->discount_type[$i] ?? null,
    //                         'discount_amount'  => $request->discount_amount[$i] ?? 0,
    //                         'tax_id'           => $request->tax_id[$i] ?? null,
    //                         'tax_type'         => $request->tax_type[$i] ?? null,
    //                         'tax_amount'       => $request->tax_amount[$i] ?? 0,
    //                         'total'            => -abs($request->total[$i]),
    //                         'mrp'              => $request->mrp[$i] ?? 0,
    //                     ]
    //                 );

    //             if (!$transaction) {
    //                 throw new \Exception('Failed to create item transaction');
    //             }

    //             // Reduce physical stock
    //             $item->decrement('current_stock', $qty);
    //         }

    //         /*
    //         |--------------------------------------------------
    //         | 4. REVERSE ORIGINAL PURCHASE PAYMENTS
    //         |--------------------------------------------------
    //         */
    //         if (!empty($validated['purchase_id'])) {

    //             $purchase = Purchase::lockForUpdate()
    //                 ->findOrFail($validated['purchase_id']);

    //             // Total amount paid on original purchase
    //             $paidAmount = $purchase->paymentTransaction()->sum('amount');

    //             if ($paidAmount > 0) {
    //                 $purchaseReturn->paymentTransaction()->create([
    //                     'amount'           => -$paidAmount,
    //                     'note'             => 'Purchase Return Adjustment',
    //                     'payment_type_id'  => 1,
    //                     'supplier_id'      => $purchaseReturn->party_id,
    //                     'transaction_date' => now(),
    //                 ]);
    //             }
    //         }

    //         /*
    //         |--------------------------------------------------
    //         | 5. UPDATE RETURN PAID AMOUNT
    //         |--------------------------------------------------
    //         */
    //         $this->paymentTransactionService
    //             ->updateTotalPaidAmountInModel($purchaseReturn);

    //         /*
    //         |--------------------------------------------------
    //         | 6. MARK ORIGINAL PURCHASE AS RETURNED
    //         |--------------------------------------------------
    //         */
    //         if (!empty($validated['purchase_id'])) {

    //             $purchase->update([
    //                 'is_return'    => true,
    //                 'return_of_id' => $purchaseReturn->id,
    //                 'grand_total' => $validated['grand_total'],
    //                 'paid_amount' => $validated[0]['payment_amount'],
    //             ]);
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'status'  => true,
    //             'message' => __('app.record_saved_successfully'),
    //             'id'      => $purchaseReturn->id,
    //         ]);

    //     } catch (\Throwable $e) {

    //         DB::rollBack();

    //         return response()->json([
    //             'status'  => false,
    //             'message' => $e->getMessage(),
    //         ], 409);
    //     }
    // }




    public function store(PurchaseReturnRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            // 1. VALIDATION
            $validated = $request->validated();

            /*
            |--------------------------------------------------
            | 2. CREATE PURCHASE RETURN
            |--------------------------------------------------
            */
            $purchaseReturn = PurchaseReturn::create($validated);

            /*
            |--------------------------------------------------
            | 3. REVERSE STOCK (NEGATIVE ITEM TRANSACTIONS)
            |--------------------------------------------------
            */
            foreach ($request->item_id as $i => $itemId) {

                $qty = abs($request->quantity[$i] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $item = Item::lockForUpdate()->findOrFail($itemId);

                $transaction = $this->itemTransactionService
                    ->recordItemTransactionEntry(
                        $purchaseReturn,
                        [
                            'warehouse_id'     => $request->warehouse_id[$i],
                            'transaction_date' => $purchaseReturn->return_date,
                            'item_id'          => $itemId,
                            'tracking_type'    => $item->tracking_type,
                            'quantity'         => -$qty,
                            'unit_id'          => $request->unit_id[$i],
                            'unit_price'       => $request->purchase_price[$i],
                            'discount'         => $request->discount[$i] ?? 0,
                            'discount_type'    => $request->discount_type[$i] ?? null,
                            'discount_amount'  => $request->discount_amount[$i] ?? 0,
                            'tax_id'           => $request->tax_id[$i] ?? null,
                            'tax_type'         => $request->tax_type[$i] ?? null,
                            'tax_amount'       => $request->tax_amount[$i] ?? 0,
                            'total'            => -abs($request->total[$i]),
                            'mrp'              => $request->mrp[$i] ?? 0,
                        ]
                    );

                if (!$transaction) {
                    throw new \Exception('Failed to create item transaction');
                }

                // Reduce physical stock
                $item->decrement('current_stock', $qty);
            }

            /*
            |--------------------------------------------------
            | 4. REVERSE ORIGINAL PURCHASE PAYMENTS
            |--------------------------------------------------
            */
            if (!empty($validated['purchase_id'])) {

                $purchase = Purchase::lockForUpdate()
                    ->findOrFail($validated['purchase_id']);

                // Total amount paid on original purchase
                $paidAmount = $purchase->paymentTransaction()->sum('amount');

                if ($paidAmount > 0) {
                    $purchaseReturn->paymentTransaction()->create([
                        'amount'           => -$paidAmount,
                        'note'             => 'Purchase Return Adjustment',
                        'payment_type_id'  => 1,
                        'supplier_id'      => $purchaseReturn->party_id,
                        'transaction_date' => now(),
                    ]);
                }
            }

            /*
            |--------------------------------------------------
            | 5. UPDATE RETURN PAID AMOUNT
            |--------------------------------------------------
            */
            // $this->paymentTransactionService
            //     ->updateTotalPaidAmountInModel($purchaseReturn);

            /*
            |--------------------------------------------------
            | 6. MARK ORIGINAL PURCHASE AS RETURNED
            |--------------------------------------------------
            */
            /*
            |--------------------------------------------------
            | 6. MARK ORIGINAL PURCHASE AS RETURNED (CORRECT)
            |--------------------------------------------------
            */
            // if (!empty($validated['purchase_id'])) {

            //     $purchase = Purchase::lockForUpdate()
            //         ->findOrFail($validated['purchase_id']);

            //     $returnedAmount = (float) $validated['grand_total'];

            //     // Reduce paid amount safely
            //     $purchase->paid_amount = max(
            //         0,
            //         ($purchase->paid_amount ?? 0) - $returnedAmount
            //     );

            //     $purchase->is_return    = true;
            //     $purchase->return_of_id = $purchaseReturn->id;

            //     $purchase->save();
            // }


            if (!empty($validated['purchase_id'])) {

                $purchase = Purchase::lockForUpdate()
                    ->findOrFail($validated['purchase_id']);

                $returnedAmount   = (float) $validated['grand_total'];
                $returnedDiscount = (float) ($validated['total_discount'] ?? 0);
                $returnedRoundOff = (float) ($validated['round_off'] ?? 0);

                /*
                |---------------------------------------
                | Reduce purchase amounts
                |---------------------------------------
                */
                $purchase->grand_total = $returnedAmount;

                $purchase->total_discount = $returnedDiscount;

                $purchase->round_off = $returnedRoundOff;

                /*
                |---------------------------------------
                | Reduce paid amount safely
                |---------------------------------------
                */
                $purchase->paid_amount = $returnedAmount;

                /*
                |---------------------------------------
                | Mark return info
                |---------------------------------------
                */
                $purchase->is_return    = true;
                $purchase->return_of_id = $purchaseReturn->id;

                $purchase->save();
            }



            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => __('app.record_saved_successfully'),
                'id'      => $purchaseReturn->id,
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }





 









    public function savePurchaseReturnPayments($request)
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
                    'transaction_date'          => $request->return_date,
                    'amount'                    => $amount,
                    'payment_type_id'           => $request->payment_type_id[$i],
                    'note'                      => $request->payment_note[$i],
                    'payment_from_unique_code'  => General::INVOICE->value,
                ];

                if(!$transaction = $this->paymentTransactionService->recordPayment($request->modelName, $paymentsArray)){
                    throw new \Exception(__('payment.failed_to_record_payment_transactions'));
                }

            }//amount>0
        }//for end

        return ['status' => true];
    }
    public function savePurchaseReturnItems($request)
    {
        $itemsCount = $request->row_count;

        for ($i=0; $i < $itemsCount; $i++) {
            /**
             * If array record not exist then continue forloop
             * */
            if(!isset($request->item_id[$i])){
                continue;
            }

            /**
             * Data index start from 0
             * */
            $itemDetails = Item::find($request->item_id[$i]);
            $itemName           = $itemDetails->name;

            //validate input Quantity
            $itemQuantity       = $request->quantity[$i];
            if(empty($itemQuantity) || $itemQuantity === 0 || $itemQuantity < 0){
                    return [
                        'status' => false,
                        'message' => ($itemQuantity<0) ? __('item.item_qty_negative', ['item_name' => $itemName]) : __('item.please_enter_item_quantity', ['item_name' => $itemName]),
                    ];
            }

            /**
             *
             * Item Transaction Entry
             * */
            $transaction = $this->itemTransactionService->recordItemTransactionEntry($request->modelName, [
                'warehouse_id'              => $request->warehouse_id[$i],
                'transaction_date'          => $request->return_date,
                'item_id'                   => $request->item_id[$i],
                'description'               => $request->description[$i],

                'tracking_type'             => $itemDetails->tracking_type,

                'quantity'                  => $itemQuantity,
                'unit_id'                   => $request->unit_id[$i],
                'unit_price'                => $request->purchase_price[$i],
                'mrp'                       => $request->mrp[$i]??0,

                'discount'                  => $request->discount[$i],
                'discount_type'             => $request->discount_type[$i],
                'discount_amount'           => $request->discount_amount[$i],

                'tax_id'                    => $request->tax_id[$i],
                'tax_type'                  => $request->tax_type[$i],
                'tax_amount'                => $request->tax_amount[$i],

                'total'                     => $request->total[$i],

            ]);

            //return $transaction;
            if(!$transaction){
                throw new \Exception("Failed to record Item Transaction Entry!");
            }


            /**
             * Tracking Type:
             * regular
             * batch
             * serial
             * */
            if($itemDetails->tracking_type == 'serial'){
                //Serial validate and insert records
                if($itemQuantity > 0){
                    $jsonSerials = $request->serial_numbers[$i];
                    $jsonSerialsDecode = json_decode($jsonSerials);

                    /**
                     * Serial number count & Enter Quntity must be equal
                     * */
                    $countRecords = (!empty($jsonSerialsDecode)) ? count($jsonSerialsDecode) : 0;
                    if($countRecords != $itemQuantity){
                        throw new \Exception(__('item.opening_quantity_not_matched_with_serial_records'));
                    }

                    foreach($jsonSerialsDecode as $serialNumber){
                        $serialArray = [
                            'serial_code'       =>  $serialNumber,
                        ];

                        $serialTransaction = $this->itemTransactionService->recordItemSerials($transaction->id, $serialArray, $request->item_id[$i], $request->warehouse_id[$i], ItemTransactionUniqueCode::PURCHASE_RETURN->value);

                        if(!$serialTransaction){
                            throw new \Exception(__('item.failed_to_save_serials'));
                        }
                    }
                }
            }
            else if($itemDetails->tracking_type == 'batch'){
                //Serial validate and insert records
                if($itemQuantity > 0){
                    /**
                     * Record Batch Entry for each batch
                     * */
                    $batchArray = [
                            'batch_no'              =>  $request->batch_no[$i],
                            'mfg_date'              =>  $request->mfg_date[$i]? $this->toSystemDateFormat($request->mfg_date[$i]) : null,
                            'exp_date'              =>  $request->exp_date[$i]? $this->toSystemDateFormat($request->exp_date[$i]) : null,
                            'model_no'              =>  $request->model_no[$i],
                            'mrp'                   =>  $request->mrp[$i]??0,
                            'color'                 =>  $request->color[$i],
                            'size'                  =>  $request->size[$i],
                            'quantity'              =>  $itemQuantity,
                        ];

                    $batchTransaction = $this->itemTransactionService->recordItemBatches($transaction->id, $batchArray, $request->item_id[$i], $request->warehouse_id[$i], ItemTransactionUniqueCode::PURCHASE_RETURN->value);

                    if(!$batchTransaction){
                        throw new \Exception(__('item.failed_to_save_batch_records'));
                    }


                }
            }
            else{
                //Regular item transaction entry already done before if() condition
            }


        }//for end

        return ['status' => true];
    }





    /**
     * Datatabale
     * */
    public function datatableList(Request $request){

        $data = PurchaseReturn::with('user', 'party')
                        ->when($request->party_id, function ($query) use ($request) {
                            return $query->where('party_id', $request->party_id);
                        })
                        ->when($request->user_id, function ($query) use ($request) {
                            return $query->where('created_by', $request->user_id);
                        })
                        ->when($request->from_date, function ($query) use ($request) {
                            return $query->where('return_date', '>=', $this->toSystemDateFormat($request->from_date));
                        })
                        ->when($request->to_date, function ($query) use ($request) {
                            return $query->where('return_date', '<=', $this->toSystemDateFormat($request->to_date));
                        })
                        ->when(!auth()->user()->hasPermissionTo('purchase.return.can.view.other.users.purchase.returns'), function ($query) use ($request) {
                            return $query->where('created_by', auth()->user()->id);
                        });

        return DataTables::of($data)
                    ->filter(function ($query) use ($request) {
                        if ($request->has('search') && $request->search['value']) {
                            $searchTerm = $request->search['value'];
                            $query->where(function ($q) use ($searchTerm) {
                                $q->where('return_code', 'like', "%{$searchTerm}%")
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
                    ->addColumn('return_date', function ($row) {
                        return $row->formatted_return_date;
                    })

                    ->addColumn('return_code', function ($row) {
                        return $row->return_code;
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

                            $editUrl = route('purchase.return.edit', ['id' => $id]);
                            $deleteUrl = route('purchase.return.delete', ['id' => $id]);
                            $detailsUrl = route('purchase.return.details', ['id' => $id]);
                            $printUrl = route('purchase.return.print', ['id' => $id]);
                            $pdfUrl = route('purchase.return.pdf', ['id' => $id]);

                            $actionBtn = '<div class="dropdown ms-auto">
                            <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="' . $editUrl . '"><i class="bi bi-trash"></i><i class="bx bx-edit"></i> '.__('app.edit').'</a>
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
                                    <a class="dropdown-item make-payment" data-invoice-id="' . $id . '" role="button"></i><i class="bx bx-money"></i> '.__('payment.receive_payment').'</a>
                                </li>
                                <li>
                                    <a class="dropdown-item payment-history" data-invoice-id="' . $id . '" role="button"></i><i class="bx bx-table"></i> '.__('payment.history').'</a>
                                </li>
                                <li>
                                    <a class="dropdown-item notify-through-email" data-model="purchase/return" data-id="' . $id . '" role="button"></i><i class="bx bx-envelope"></i> '.__('app.send_email').'</a>
                                </li>
                                <li>
                                    <a class="dropdown-item notify-through-sms" data-model="purchase/return" data-id="' . $id . '" role="button"></i><i class="bx bx-envelope"></i> '.__('app.send_sms').'</a>
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

    /**
     * Delete Purchase Records
     * @return JsonResponse
     * */
    public function delete(Request $request) : JsonResponse{

        DB::beginTransaction();

        $selectedRecordIds = $request->input('record_ids');

        // Perform validation for each selected record ID
        foreach ($selectedRecordIds as $recordId) {
            $record = PurchaseReturn::find($recordId);
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
            PurchaseReturn::whereIn('id', $selectedRecordIds)->chunk(100, function ($purchases) {
                foreach ($purchases as $purchase) {

                    /**
                    * Before deleting ItemTransaction data take the
                    * old data of the item_serial_master_id
                    * to update the item_serial_quantity
                    * */
                   $this->previousHistoryOfItems = $this->itemTransactionService->getHistoryOfItems($purchase);

                    //Purchase Account Update
                    // foreach($purchase->accountTransaction as $purchaseAccount){
                    //     //get account if of model with tax accounts
                    //     $purchaseAccountId = $purchaseAccount->account_id;

                    //     //Delete purchase and tax account
                    //     $purchaseAccount->delete();

                    //     //Update  account
                    //     $this->accountTransactionService->calculateAccounts($purchaseAccountId);
                    // }//purchase account

                    // Check if paymentTransactions exist
                    $paymentTransactions = $purchase->paymentTransaction;
                    if ($paymentTransactions->isNotEmpty()) {
                        foreach ($paymentTransactions as $paymentTransaction) {
                            // $accountTransactions = $paymentTransaction->accountTransaction;
                            // if ($accountTransactions->isNotEmpty()) {
                            //     foreach ($accountTransactions as $accountTransaction) {
                            //         //Purchase Account Update
                            //         $accountId = $accountTransaction->account_id;
                            //         // Do something with the individual accountTransaction
                            //         $accountTransaction->delete(); // Or any other operation

                            //         $this->accountTransactionService->calculateAccounts($accountId);
                            //     }
                            // }

                            //delete Payment now
                            $paymentTransaction->delete();
                        }
                    }//isNotEmpty

                    $itemIdArray = [];
                    //Purchasr Item delete and update the stock
                    foreach($purchase->itemTransaction as $itemTransaction){
                        //get item id
                        $itemId = $itemTransaction->item_id;

                        //delete item Transactions
                        $itemTransaction->delete();

                        $itemIdArray[] = $itemId;
                    }//purchase account

                    /**
                     * UPDATE HISTORY DATA
                     * LIKE: ITEM SERIAL NUMBER QUNATITY, BATCH NUMBER QUANTITY, GENERAL DATA QUANTITY
                     * */
                    $this->itemTransactionService->updatePreviousHistoryOfItems($purchase, $this->previousHistoryOfItems);

                    //Delete Purchase
                    $purchase->delete();

                    //Update stock update in master
                    if(count($itemIdArray) > 0){
                        foreach($itemIdArray as $id){
                            $this->itemService->updateItemStock($id);
                        }
                    }

                }//purchases
            });

            //Delete Purchase
            //$deletedCount = PurchaseReturn::whereIn('id', $selectedRecordIds)->delete();

            DB::commit();

            return response()->json([
                'status'    => true,
                'message' => __('app.record_deleted_successfully'),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();
            return response()->json([
                'status'    => false,
                'message' => __('app.cannot_delete_records'),
            ],409);
        }
    }

    /**
     * Prepare Email Content to view
     * */
    public function getEmailContent($id)
    {
        $model = PurchaseReturn::with('party')->find($id);

        $emailData = $this->purchaseReturnEmailNotificationService->purchaseReturnCreatedEmailNotification($id);

        $subject = ($emailData['status']) ? $emailData['data']['subject'] : '';
        $content = ($emailData['status']) ? $emailData['data']['content'] : '';

        $data = [
            'email'  => $model->party->email,
            'subject'  => $subject,
            'content'  => $content,
        ];
        return $data;
    }

    /**
     * Prepare SMS Content to view
     * */
    public function getSMSContent($id)
    {
        $model = PurchaseReturn::with('party')->find($id);

        $emailData = $this->purchaseReturnSmsNotificationService->purchaseReturnCreatedSmsNotification($id);

        $mobile = ($emailData['status']) ? $emailData['data']['mobile'] : '';
        $content = ($emailData['status']) ? $emailData['data']['content'] : '';

        $data = [
            'mobile'  => $mobile,
            'content'  => $content,
        ];
        return $data;
    }


    public function directReturn(Request $request)
    {
        DB::beginTransaction();

        try {

            $purchase = Purchase::with(['itemTransaction', 'paymentTransaction'])
                ->where('id', $request->purchase_id)
                ->where('is_return', false)
                ->firstOrFail();
            /*
            |------------------------------------------
            | 1. REVERSE STOCK
            |------------------------------------------
            */
            foreach ($purchase->itemTransaction as $row) {
                $item = Item::find($row->item_id);
                if ($item) {
                    $item->current_stock -= $row->quantity;
                    $item->save();
                }
            }

            /*
            |------------------------------------------
            | 2. REVERSE PAYMENTS
            |------------------------------------------
            */
            if ($purchase->paid_amount > 0) {
                $purchase->paymentTransaction()->create([
                    'amount'           => -$purchase->paid_amount,
                    'note'             => 'Purchase returned',
                    'transaction_type' => 'Purchase returned',
                    'payment_type_id'  => 1,
                    'supplier_id'  => $purchase->party_id,
                    'transaction_date' => now(),
                    'created_by'       => auth()->id(),
                    'updated_by'       => auth()->id(),
                ]);
            }

            /*
            |------------------------------------------
            | 3. MARK PURCHASE AS RETURNED
            |------------------------------------------
            */
            $purchase->update([
                'is_return'    => true,
                'return_of_id' => $purchase->id,
            ]);

            DB::commit();

            return response()->json(['status' => true]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 409);
        }
    }


    // public function purchaseReturn()
    // {
    //     return view('purchase.return-list');
    // }

    // public function purchaseReturnDatatableList(Request $request){
    //     $data = Purchase::with('user', 'party')
    //                     ->when($request->party_id, function ($query) use ($request) {
    //                         return $query->where('party_id', $request->party_id);
    //                     })
    //                     ->when($request->user_id, function ($query) use ($request) {
    //                         return $query->where('created_by', $request->user_id);
    //                     })
    //                     ->when($request->from_date, function ($query) use ($request) {
    //                         return $query->where('purchase_date', '>=', $this->toSystemDateFormat($request->from_date));
    //                     })
    //                     ->when($request->to_date, function ($query) use ($request) {
    //                         return $query->where('purchase_date', '<=', $this->toSystemDateFormat($request->to_date));
    //                     })
    //                     ->when(!auth()->user()->hasPermissionTo('purchase.bill.can.view.other.users.purchase.bills'), function ($query) use ($request) {
    //                         return $query->where('created_by', auth()->user()->id);
    //                     })
    //                     ->where('is_return', true);
    //                     dd($data);

    //     return DataTables::of($data)
    //                 ->filter(function ($query) use ($request) {
    //                     if ($request->has('search') && $request->search['value']) {
    //                         $searchTerm = $request->search['value'];
    //                         $query->where(function ($q) use ($searchTerm) {
    //                             $q->where('purchase_code', 'like', "%{$searchTerm}%")
    //                               ->orWhere('grand_total', 'like', "%{$searchTerm}%")
    //                               ->orWhereHas('party', function ($partyQuery) use ($searchTerm) {
    //                                   $partyQuery->where('first_name', 'like', "%{$searchTerm}%")
    //                                         ->orWhere('last_name', 'like', "%{$searchTerm}%");
    //                               })
    //                               ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
    //                                   $userQuery->where('username', 'like', "%{$searchTerm}%");
    //                               });
    //                         });
    //                     }
    //                 })
    //                 ->addIndexColumn()
    //                 ->addColumn('return_date', function ($row) {
    //                     return $row->formatted_return_date;
    //                 })

    //                 ->addColumn('return_code', function ($row) {
    //                     return $row->return_code;
    //                 })
    //                 ->addColumn('created_at', function ($row) {
    //                     return $row->created_at->format(app('company')['date_format']);
    //                 })
    //                 ->addColumn('username', function ($row) {
    //                     return $row->user->username??'';
    //                 })
    //                 ->addColumn('purchase_date', function ($row) {
    //                     return $row->formatted_purchase_date;
    //                 })

    //                 ->addColumn('purchase_code', function ($row) {
    //                     return $row->purchase_code;
    //                 })
    //                 ->addColumn('party_name', function ($row) {
    //                     return $row->party->first_name." ".$row->party->last_name;
    //                 })
    //                 ->addColumn('grand_total', function ($row) {
    //                     return $this->formatWithPrecision($row->grand_total);
    //                 })
    //                 ->addColumn('balance', function ($row) {
    //                     return $this->formatWithPrecision($row->grand_total - $row->paid_amount);
    //                 })
    //                 ->addColumn('select', function ($row) {
    //                     if ($row->is_return) {
    //                         return '<span class="text-muted">Returned</span>';
    //                     }

    //                      return '
    //                             <div class="form-check form-switch">
    //                                 <input class="form-check-input purchase-switch"
    //                                     type="checkbox"
    //                                     role="switch"
    //                                     data-purchase-id="'.$row->id.'"
    //                                     id="purchaseSwitch'.$row->id.'">
    //                             </div>';
    //                 })

    //                 ->addColumn('action', function($row){
    //                         $id = $row->id;

    //                         $editUrl = route('purchase.bill.edit', ['id' => $id]);
    //                         $deleteUrl = route('purchase.bill.delete', ['id' => $id]);
    //                         $detailsUrl = route('purchase.bill.details', ['id' => $id]);
    //                         $printUrl = route('purchase.bill.print', ['id' => $id]);
    //                         $pdfUrl = route('purchase.bill.pdf', ['id' => $id]);

    //                         //Verify is it converted or not
    //                         /*if($row->purchaseReturn){
    //                             $convertToPurchase = route('purchase.return.details', ['id' => $row->purchaseReturn->id]);
    //                             $convertToPurchaseText = __('app.view_bill');
    //                             $convertToPurchaseIcon = 'check-double';
    //                         }else{*/
    //                             $convertToPurchase = route('purchase.return.convert', ['id' => $id]);
    //                             $convertToPurchaseText = __('purchase.convert_to_return');
    //                             $convertToPurchaseIcon = 'transfer-alt';
    //                         //}

    //                         $actionBtn = '<div class="dropdown ms-auto">
    //                         <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
    //                         </a>
    //                         <ul class="dropdown-menu">
    //                             <li>
    //                                 <a class="dropdown-item" href="' . $editUrl . '"><i class="bi bi-trash"></i><i class="bx bx-edit"></i> '.__('app.edit').'</a>
    //                             </li>
    //                             <li>
    //                                 <a class="dropdown-item" href="' . $convertToPurchase . '"><i class="bx bx-'.$convertToPurchaseIcon.'"></i> '.$convertToPurchaseText.'</a>
    //                             </li>
    //                             <li>
    //                                 <a class="dropdown-item" href="' . $detailsUrl . '"></i><i class="bx bx-show-alt"></i> '.__('app.details').'</a>
    //                             </li>
    //                             <li>
    //                                 <a target="_blank" class="dropdown-item" href="' . $printUrl . '"></i><i class="bx bx-printer "></i> '.__('app.print').'</a>
    //                             </li>
    //                             <li>
    //                                 <a target="_blank" class="dropdown-item" href="' . $pdfUrl . '"></i><i class="bx bxs-file-pdf"></i> '.__('app.pdf').'</a>
    //                             </li>
    //                             <li>
    //                                 <a class="dropdown-item make-payment" data-invoice-id="' . $id . '" role="button"></i><i class="bx bx-money"></i> '.__('payment.make_payment').'</a>
    //                             </li>
    //                             <li>
    //                                 <a class="dropdown-item payment-history" data-invoice-id="' . $id . '" role="button"></i><i class="bx bx-table"></i> '.__('payment.history').'</a>
    //                             </li>
                                
    //                                 <button type="button" class="dropdown-item text-danger deleteRequest" data-delete-id='.$id.'><i class="bx bx-trash"></i> '.__('app.delete').'</button>
    //                             </li>
    //                         </ul>
    //                     </div>';
    //                         return $actionBtn;
    //                 })
    //                 ->rawColumns(['action', 'select'])
    //                 ->make(true);
    // }


}
