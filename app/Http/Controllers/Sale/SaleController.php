<?php

namespace App\Http\Controllers\Sale;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use App\Models\Prefix;
use App\Models\Sale\SaleOrder;
use App\Models\Sale\Sale;
use App\Models\Items\Item;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use App\Enums\App;
use App\Enums\General;
use App\Services\PaymentTypeService;
use App\Services\GeneralDataService;
use App\Services\PaymentTransactionService;
use App\Http\Requests\SaleRequest;
use App\Services\AccountTransactionService;
use App\Services\ItemTransactionService;
use App\Models\Items\ItemSerial;
use App\Models\Items\ItemBatchTransaction;
use Carbon\Carbon;
use App\Services\CacheService;
use App\Services\ItemService;
use App\Services\PartyService;
use App\Services\Communication\Email\SaleEmailNotificationService;
use App\Services\Communication\Sms\SaleSmsNotificationService;
use App\Enums\ItemTransactionUniqueCode;
use Illuminate\Support\Facades\Storage;

use Mpdf\Mpdf;

class SaleController extends Controller
{
    use FormatNumber;

    use FormatsDateInputs;

    protected $companyId;

    private $paymentTypeService;

    private $paymentTransactionService;

    private $accountTransactionService;

    private $itemTransactionService;

    private $itemService;

    private $partyService;

    public $previousHistoryOfItems;

    public $saleEmailNotificationService;

    public $saleSmsNotificationService;

    public function __construct(PaymentTypeService $paymentTypeService,
                                PaymentTransactionService $paymentTransactionService,
                                AccountTransactionService $accountTransactionService,
                                ItemTransactionService $itemTransactionService,
                                ItemService $itemService,
                                PartyService $partyService,
                                SaleEmailNotificationService $saleEmailNotificationService,
                                SaleSmsNotificationService $saleSmsNotificationService
                            )
    {
        $this->companyId = App::APP_SETTINGS_RECORD_ID->value;
        $this->paymentTypeService = $paymentTypeService;
        $this->paymentTransactionService = $paymentTransactionService;
        $this->accountTransactionService = $accountTransactionService;
        $this->itemTransactionService = $itemTransactionService;
        $this->itemService = $itemService;
        $this->partyService = $partyService;
        $this->saleEmailNotificationService = $saleEmailNotificationService;
        $this->saleSmsNotificationService = $saleSmsNotificationService;
        $this->previousHistoryOfItems = [];
    }

    /**
     * Create a new order.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View  {
        $prefix = Prefix::findOrNew($this->companyId);
        $lastCountId = $this->getLastCountId();
        $selectedPaymentTypesArray = json_encode($this->paymentTypeService->selectedPaymentTypesArray());
        $categories = DB::table('party_categories')->where('status', 1)->pluck('name', 'id');
        $data = [
            'prefix_code' => $prefix->sale,
            'count_id' => ($lastCountId+1),
        ];

        return view('sale.invoice.create',compact('data', 'selectedPaymentTypesArray','categories'));
    }
    /**
     * Create a POS sale.
     *
     * @return \Illuminate\View\View
     */
    public function posCreate(): View  {
        $prefix = Prefix::findOrNew($this->companyId);
        $lastCountId = $this->getLastCountId();
        $selectedPaymentTypesArray = json_encode($this->paymentTypeService->selectedPaymentTypesArray());
        $data = [
            'prefix_code' => $prefix->sale,
            'count_id' => ($lastCountId+1),
        ];
        return view('sale.invoice.pos.create',compact('data', 'selectedPaymentTypesArray'));
    }

    /**
     * Get last count ID
     * */
    public function getLastCountId(){
        return Sale::select('count_id')->orderBy('id', 'desc')->first()?->count_id ?? 0;
    }

    /**
     * List the orders
     *
     * @return \Illuminate\View\View
     */
    public function list() : View {
        return view('sale.invoice.list');
    }


    /**
     * Edit a Sale Order.
     *
     * @param int $id The ID of the expense to edit.
     * @return \Illuminate\View\View
     */
    public function convertToSale($id) : View | RedirectResponse {

        //Validate Existance of Converted Sale Orders
        $convertedBill = Sale::where('sale_order_id', $id)->first();
        if($convertedBill){
            session(['record' => [
                                    'type' => 'success',
                                    'status' => __('sale.already_converted'), //Save or update
                                ]]);
            //Already Converted, Redirect it.
            return redirect()->route('sale.invoice.details', ['id' => $convertedBill->id]);
        }

        $sale = SaleOrder::with(['party',
                                        'itemTransaction' => [
                                            'item',
                                            'tax',
                                            'batch.itemBatchMaster',
                                            'itemSerialTransaction.itemSerialMaster'
                                        ]])->findOrFail($id);

        //Convert Code adjustment - start
        $sale->operation = 'convert';
        $sale->formatted_sale_date = $this->toSystemDateFormat($sale->order_date);
        $sale->reference_no = '';
        //Convert Code adjustment - end

        $prefix = Prefix::findOrNew($this->companyId);
        $lastCountId = $this->getLastCountId();
        $sale->prefix_code = $prefix->sale;
        $sale->count_id = ($lastCountId+1);

        $sale->formatted_sale_date = $this->toUserDateFormat(date('Y-m-d'));

        // Item Details
        // Prepare item transactions with associated units
        $allUnits = CacheService::get('unit');

        $itemTransactions = $sale->itemTransaction->map(function ($transaction) use ($allUnits ) {
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
        $selectedPaymentTypesArray = json_encode($this->paymentTransactionService->getPaymentRecordsArray($sale));

        $taxList = CacheService::get('tax')->toJson();

        $paymentHistory = [];

        return view('sale.invoice.edit', compact('taxList', 'sale', 'itemTransactionsJson','selectedPaymentTypesArray', 'paymentHistory'));
    }

     /**
     * Edit a Sale Order.
     *
     * @param int $id The ID of the expense to edit.
     * @return \Illuminate\View\View
     */
    public function edit($id) : View {
        $sale = Sale::with(['party',
                                        'itemTransaction' => [
                                            'item',
                                            'tax',
                                            'batch.itemBatchMaster',
                                            'itemSerialTransaction.itemSerialMaster'
                                        ]])->findOrFail($id);

        $sale->operation = 'update';

        // Item Details
        // Prepare item transactions with associated units
        $allUnits = CacheService::get('unit');

        $itemTransactions = $sale->itemTransaction->map(function ($transaction) use ($allUnits ) {
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

        //Default Payment Details
        $selectedPaymentTypesArray = json_encode($this->paymentTypeService->selectedPaymentTypesArray());

        $paymentHistory = $this->paymentTransactionService->getPaymentRecordsArray($sale);

        $taxList = CacheService::get('tax')->toJson();
        $categories = DB::table('party_categories')->where('status', 1)->pluck('name', 'id');
        

        return view('sale.invoice.edit', compact('taxList', 'sale', 'itemTransactionsJson','selectedPaymentTypesArray', 'paymentHistory','categories'));
    }

    /**
     * View Sale Order details
     *
     * @param int $id, the ID of the order
     * @return \Illuminate\View\View
     */
    public function details($id) : View {
        $sale = Sale::with(['party',
                                        'itemTransaction' => [
                                            'item',
                                            'tax',
                                            'batch.itemBatchMaster',
                                            'itemSerialTransaction.itemSerialMaster'
                                        ]])->findOrFail($id);

        //Payment Details
        $selectedPaymentTypesArray = json_encode($this->paymentTransactionService->getPaymentRecordsArray($sale));

        //Batch Tracking Row count for invoice columns setting
        $batchTrackingRowCount = (new GeneralDataService())->getBatchTranckingRowCount();

        $categories = DB::table('party_categories')->where('status', 1)->pluck('name', 'id');
        

        return view('sale.invoice.details', compact('sale','selectedPaymentTypesArray', 'batchTrackingRowCount','categories'));
    }

    /**
     * Print Sale
     *
     * @param int $id, the ID of the sale
     * @return \Illuminate\View\View
     */
    public function posPrint($id, $isPdf = false) : View {

        $sale = Sale::with(['party','user',
                                        'itemTransaction' => [
                                            'item',
                                            'tax',
                                            'batch.itemBatchMaster',
                                            'itemSerialTransaction.itemSerialMaster'
                                        ]])->findOrFail($id);
        //Payment Details
        $selectedPaymentTypesArray = json_encode($this->paymentTransactionService->getPaymentRecordsArray($sale));

        //Batch Tracking Row count for invoice columns setting
        $batchTrackingRowCount = (new GeneralDataService())->getBatchTranckingRowCount();

        $invoiceData = [
            'name' => __('sale.invoice'),
        ];

        return view('print.sale.pos.print', compact('isPdf', 'invoiceData', 'sale','selectedPaymentTypesArray','batchTrackingRowCount'));

    }

    /**
     * Print Sale
     *
     * @param int $id, the ID of the sale
     * @return \Illuminate\View\View
     */
    public function print($id, $isPdf = false) : View {

        $sale = Sale::with(['party',
                                        'itemTransaction' => [
                                            'item',
                                            'tax',
                                            'batch.itemBatchMaster',
                                            'itemSerialTransaction.itemSerialMaster'
                                        ]])->findOrFail($id);

        //Payment Details
        $selectedPaymentTypesArray = json_encode($this->paymentTransactionService->getPaymentRecordsArray($sale));

        //Batch Tracking Row count for invoice columns setting
        $batchTrackingRowCount = (new GeneralDataService())->getBatchTranckingRowCount();

        $invoiceData = [
            'name' => __('sale.invoice'),
        ];

        return view('print.sale.print', compact('isPdf', 'invoiceData', 'sale','selectedPaymentTypesArray','batchTrackingRowCount'));

    }


    /**
     * Generate PDF using View: print() method
     * */
    public function generatePdf($id, $destination= 'D'){
        $random = uniqid();

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
         * Return String
         * 'S'
         * File Save
         * 'F'
         * */
        $fileName = 'Sale-Bill-'.$id.'-'.$random.'.pdf';

        $mpdf->Output($fileName, $destination);

    }

    /**
     * Store Records
     * */
    public function store(SaleRequest $request) : JsonResponse  {
        try {

            DB::beginTransaction();
            // Get the validated data from the expenseRequest
            $validatedData = $request->validated();

            if($request->operation == 'save' || $request->operation == 'convert'){
                // Create a new sale record using Eloquent and save it
                $newSale = Sale::create($validatedData);

                $request->request->add(['sale_id' => $newSale->id]);
            }
            else{
                $fillableColumns = [
                    'party_id'              => $validatedData['party_id'],
                    'sale_date'             => $validatedData['sale_date'],
                    'reference_no'          => $validatedData['reference_no'],
                    'prefix_code'           => $validatedData['prefix_code'],
                    'count_id'              => $validatedData['count_id'],
                    'sale_code'             => $validatedData['sale_code'],
                    'note'                  => $validatedData['note'],
                    'round_off'             => $validatedData['round_off'],
                    'grand_total'           => $validatedData['grand_total'],
                    'state_id'              => $validatedData['state_id'],
                ];

                $newSale = Sale::findOrFail($validatedData['sale_id']);
                $newSale->update($fillableColumns);

                /**
                * Before deleting ItemTransaction data take the
                * old data of the item_serial_master_id
                * to update the item_serial_quantity
                * */
               $this->previousHistoryOfItems = $this->itemTransactionService->getHistoryOfItems($newSale);

                $newSale->itemTransaction()->delete();
                //$newSale->accountTransaction()->delete();

                //Sale Account Update
                foreach($newSale->accountTransaction as $saleAccount){
                    //get account if of model with tax accounts
                    $saleAccountId = $saleAccount->account_id;

                    //Delete sale and tax account
                    $saleAccount->delete();

                    //Update  account
                    $this->accountTransactionService->calculateAccounts($saleAccountId);
                }//sale account


                // Check if paymentTransactions exist
                // $paymentTransactions = $newSale->paymentTransaction;
                // if ($paymentTransactions->isNotEmpty()) {
                //     foreach ($paymentTransactions as $paymentTransaction) {
                //         $accountTransactions = $paymentTransaction->accountTransaction;
                //         if ($accountTransactions->isNotEmpty()) {
                //             foreach ($accountTransactions as $accountTransaction) {
                //                 //Sale Account Update
                //                 $accountId = $accountTransaction->account_id;
                //                 // Do something with the individual accountTransaction
                //                 $accountTransaction->delete(); // Or any other operation

                //                 $this->accountTransactionService->calculateAccounts($accountId);
                //             }
                //         }
                //     }
                // }

                // $newSale->paymentTransaction()->delete();
            }

            $request->request->add(['modelName' => $newSale]);

            /**
             * Save Table Items in Sale Items Table
             * */
            $SaleItemsArray = $this->saveSaleItems($request);
            if(!$SaleItemsArray['status']){
                throw new \Exception($SaleItemsArray['message']);
            }

            /**
             * Save Expense Payment Records
             * */
            $salePaymentsArray = $this->saveSalePayments($request);
            if(!$salePaymentsArray['status']){
                throw new \Exception($salePaymentsArray['message']);
            }

            /**
            * Payment Should not be less than 0
            * */
            $paidAmount = $newSale->refresh('paymentTransaction')->paymentTransaction->sum('amount');
            if($paidAmount < 0){
                throw new \Exception(__('payment.paid_amount_should_not_be_less_than_zero'));
            }



            /**
             * Paid amount should not be greater than grand total
             * */
            if($paidAmount > $newSale->grand_total){
                throw new \Exception(__('payment.payment_should_not_be_greater_than_grand_total')."<br>Paid Amount : ". $this->formatWithPrecision($paidAmount)."<br>Grand Total : ". $this->formatWithPrecision($newSale->grand_total). "<br>Difference : ".$this->formatWithPrecision($paidAmount-$newSale->grand_total));
            }

            /**
             * Update Sale Model
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
            // $accountTransactionStatus = $this->accountTransactionService->saleAccountTransaction($request->modelName);
            // if(!$accountTransactionStatus){
            //     throw new \Exception(__('payment.failed_to_update_account'));
            // }

            /**
             * Credit Limit Check
             * */
            if($this->partyService->limitThePartyCreditLimit($validatedData['party_id'])){
                //
            }

            /**
             * UPDATE HISTORY DATA
             * LIKE: ITEM SERIAL NUMBER QUNATITY, BATCH NUMBER QUANTITY, GENERAL DATA QUANTITY
             * */
            $this->itemTransactionService->updatePreviousHistoryOfItems($request->modelName, $this->previousHistoryOfItems);

            DB::commit();

            // Regenerate the CSRF token
            //Session::regenerateToken();

            return response()->json([
                'status'    => true,
                'message' => __('app.record_saved_successfully'),
                'id' => $request->sale_id,

            ]);

        } catch (\Exception $e) {
                DB::rollback();

                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                ], 409);

        }

    }


    public function saveSalePayments($request)
    {
        $paymentCount = $request->row_count_payments;
        $grandTotal = $request->grand_total;

        //This is only for POS Page Payments
        if($request->is_pos_form){
            $paymentTotal = 0;
            /**
             * Used if Payment is greater then the payment.
             * Data index start from 0
             * payment_amount[0] & payment_amount[1] because POS page has only 2 payments static code
             * */
            //#0
            $payment_0           = $request->payment_amount[0];
            //#1
            $payment_1           = $request->payment_amount[1];

            //Only if single Payment has the value
            if($payment_1 == 0){// #1
                if($payment_0 >0 && $payment_0 > $grandTotal){
                    $request->merge([
                                    'payment_amount' => array_replace($request->input('payment_amount', []), [0 => $grandTotal]) // Replace 0th index value
                                ]);
                }
            }
        }

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
                    'transaction_date'          => $request->sale_date,
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
    public function saveSaleItems($request)
    {
        $itemsCount = $request->row_count;

        $isWholesaleCustomer = $request->only('is_wholesale_customer')['is_wholesale_customer'];


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

            //Validate is negative stock entry allowed or not for General Item
            $regularItemTransaction = $this->itemTransactionService->validateRegularItemQuantity($itemDetails, $request->warehouse_id[$i], $itemQuantity, ItemTransactionUniqueCode::SALE->value);

            if(!$regularItemTransaction){
                throw new \Exception(__('item.failed_to_save_regular_item_record'));
            }

            /**
             *
             * Item Transaction Entry
             * */
            $transaction = $this->itemTransactionService->recordItemTransactionEntry($request->modelName, [
                'warehouse_id'              => $request->warehouse_id[$i],
                'transaction_date'          => $request->sale_date,
                'item_id'                   => $request->item_id[$i],
                'description'               => $request->description[$i],

                'tracking_type'             => $itemDetails->tracking_type,

                'quantity'                  => $itemQuantity,
                'unit_id'                   => $request->unit_id[$i],
                'unit_price'                => $request->sale_price[$i],
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
             * Update Item Master
             * Sale Price
             * */
            if($isWholesaleCustomer){

            }
            $updateItemMaster = Item::find($request->item_id[$i]);
            if(!empty($request->sale_price[$i]) && $request->sale_price[$i]>0){

                if($updateItemMaster->base_unit_id != $request->unit_id[$i]){
                    $salePrice = $request->sale_price[$i] * $updateItemMaster->conversion_rate;
                }
                else{
                    $salePrice = $request->sale_price[$i];
                }

                if($isWholesaleCustomer){
                    $updateItemMaster->wholesale_price = $salePrice;
                    $updateItemMaster->is_wholesale_price_with_tax = ($request->tax_type[$i] == 'inclusive') ? 1 : 0;
                }else{
                    $updateItemMaster->sale_price = $salePrice;
                    $updateItemMaster->is_sale_price_with_tax = ($request->tax_type[$i] == 'inclusive') ? 1 : 0;
                }

            }
            $updateItemMaster->save();

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

                        $serialTransaction = $this->itemTransactionService->recordItemSerials($transaction->id, $serialArray, $request->item_id[$i], $request->warehouse_id[$i], ItemTransactionUniqueCode::SALE->value);

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

                    $batchTransaction = $this->itemTransactionService->recordItemBatches($transaction->id, $batchArray, $request->item_id[$i], $request->warehouse_id[$i], ItemTransactionUniqueCode::SALE->value);

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

        $data = Sale::with('user', 'party')
                        ->when($request->party_id, function ($query) use ($request) {
                            return $query->where('party_id', $request->party_id);
                        })
                        ->when($request->user_id, function ($query) use ($request) {
                            return $query->where('created_by', $request->user_id);
                        })
                        ->when($request->from_date, function ($query) use ($request) {
                            return $query->where('sale_date', '>=', $this->toSystemDateFormat($request->from_date));
                        })
                        ->when($request->to_date, function ($query) use ($request) {
                            return $query->where('sale_date', '<=', $this->toSystemDateFormat($request->to_date));
                        })
                        ->when(!auth()->user()->hasPermissionTo('sale.invoice.can.view.other.users.sale.invoices'), function ($query) use ($request) {
                            return $query->where('created_by', auth()->user()->id);
                        });

        return DataTables::of($data)
                    ->filter(function ($query) use ($request) {
                        if ($request->has('search') && $request->search['value']) {
                            $searchTerm = $request->search['value'];
                            $query->where(function ($q) use ($searchTerm) {
                                $q->where('sale_code', 'like', "%{$searchTerm}%")
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
                    ->addColumn('sale_date', function ($row) {
                        return $row->formatted_sale_date;
                    })

                    ->addColumn('sale_code', function ($row) {
                        return $row->sale_code;
                    })
                    ->addColumn('party_name', function ($row) {
                        return $row->party->first_name." ".$row->party->last_name;
                    })
                    ->addColumn('grand_total', function ($row) {
                        return $this->formatWithPrecision($row->grand_total);
                    })
                    ->addColumn('paid_amount', function ($row) {
                        return $this->formatWithPrecision($row->paid_amount);
                    })
                    ->addColumn('balance', function ($row) {
                        return $this->formatWithPrecision($row->grand_total - $row->paid_amount);
                    })
                    ->addColumn('action', function($row){
                            $id = $row->id;

                            $editUrl = route('sale.invoice.edit', ['id' => $id]);
                            $deleteUrl = route('sale.invoice.delete', ['id' => $id]);
                            $detailsUrl = route('sale.invoice.details', ['id' => $id]);
                            $printUrl = route('sale.invoice.print', ['id' => $id]);
                            $pdfUrl = route('sale.invoice.pdf', ['id' => $id]);

                            //Verify is it converted or not
                            /*if($row->saleReturn){
                                $convertToSale = route('sale.return.details', ['id' => $row->saleReturn->id]);
                                $convertToSaleText = __('app.view_bill');
                                $convertToSaleIcon = 'check-double';
                            }else{*/
                                $convertToSale = route('sale.return.convert', ['id' => $id]);
                                $convertToSaleText = __('sale.convert_to_return');
                                $convertToSaleIcon = 'transfer-alt';
                            //}

                            $actionBtn = '<div class="dropdown ms-auto">
                            <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="' . $editUrl . '"><i class="bi bi-trash"></i><i class="bx bx-edit"></i> '.__('app.edit').'</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="' . $convertToSale . '"><i class="bx bx-'.$convertToSaleIcon.'"></i> '.$convertToSaleText.'</a>
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
                                </li>
                                <li>
                                    <a class="dropdown-item make-payment" data-invoice-id="' . $id . '" role="button"></i><i class="bx bx-money"></i> '.__('payment.receive_payment').'</a>
                                </li>
                                <li>
                                    <a class="dropdown-item payment-history" data-invoice-id="' . $id . '" role="button"></i><i class="bx bx-table"></i> '.__('payment.history').'</a>
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
     * Delete Sale Records
     * @return JsonResponse
     * */
    public function delete(Request $request) : JsonResponse{

        DB::beginTransaction();

        $selectedRecordIds = $request->input('record_ids');

        // Perform validation for each selected record ID
        foreach ($selectedRecordIds as $recordId) {
            $record = Sale::find($recordId);
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
            Sale::whereIn('id', $selectedRecordIds)->chunk(100, function ($sales) {
                foreach ($sales as $sale) {
                    /**
                    * Before deleting ItemTransaction data take the
                    * old data of the item_serial_master_id
                    * to update the item_serial_quantity
                    * */
                   $this->previousHistoryOfItems = $this->itemTransactionService->getHistoryOfItems($sale);

                    //Sale Account Update
                    // foreach($sale->accountTransaction as $saleAccount){
                    //     //get account if of model with tax accounts
                    //     $saleAccountId = $saleAccount->account_id;

                    //     //Delete sale and tax account
                    //     $saleAccount->delete();

                    //     //Update  account
                    //     $this->accountTransactionService->calculateAccounts($saleAccountId);
                    // }//sale account

                    // Check if paymentTransactions exist
                    $paymentTransactions = $sale->paymentTransaction;
                    if ($paymentTransactions->isNotEmpty()) {
                        foreach ($paymentTransactions as $paymentTransaction) {
                            // $accountTransactions = $paymentTransaction->accountTransaction;
                            // if ($accountTransactions->isNotEmpty()) {
                            //     foreach ($accountTransactions as $accountTransaction) {
                            //         //Sale Account Update
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
                    foreach($sale->itemTransaction as $itemTransaction){
                        //get item id
                        $itemId = $itemTransaction->item_id;

                        //delete item Transactions
                        $itemTransaction->delete();

                        $itemIdArray[] = $itemId;
                    }//sale account



                    /**
                     * UPDATE HISTORY DATA
                     * LIKE: ITEM SERIAL NUMBER QUNATITY, BATCH NUMBER QUANTITY, GENERAL DATA QUANTITY
                     * */
                    $this->itemTransactionService->updatePreviousHistoryOfItems($sale, $this->previousHistoryOfItems);

                    //Delete Sale
                    $sale->delete();

                    //Update stock update in master
                    if(count($itemIdArray) > 0){
                        foreach($itemIdArray as $itemId){
                            $this->itemService->updateItemStock($itemId);
                        }
                    }

                }//sales

            });//chunk

            //Delete Sale
            $deletedCount = Sale::whereIn('id', $selectedRecordIds)->delete();

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
        $model = Sale::with('party')->find($id);

        $emailData = $this->saleEmailNotificationService->saleCreatedEmailNotification($id);

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
        $model = Sale::with('party')->find($id);

        $emailData = $this->saleSmsNotificationService->saleCreatedSmsNotification($id);

        $mobile = ($emailData['status']) ? $emailData['data']['mobile'] : '';
        $content = ($emailData['status']) ? $emailData['data']['content'] : '';

        $data = [
            'mobile'  => $mobile,
            'content'  => $content,
        ];
        return $data;
    }
}
