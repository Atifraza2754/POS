<?php

namespace App\Http\Controllers\Reports;

use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

use App\Models\Items\Item;
use App\Models\Sale\Sale;
use Illuminate\Support\Facades\DB;
use App\Models\Purchase\Purchase;
use App\Models\Expenses\Expense;
use App\Models\PaymentTransaction;
use App\Models\CashAdjustment;
use App\Models\CustomerPayment;
use App\Models\SupplierPayment;
use App\Models\Sale\SaleOrder;

use App\Services\Reports\ProfitAndLoss\SaleProfitService;
use App\Services\Reports\ProfitAndLoss\SaleReturnProfitService;
use App\Services\PaymentTypeService;
use App\Enums\PaymentTypesUniqueCode;

class GrowthReportController extends Controller
{
    use FormatsDateInputs;
    use FormatNumber;

    private $saleProfitService;
    private $saleReturnProfitService;
    public function __construct(SaleProfitService $saleProfitService, SaleReturnProfitService $saleReturnProfitService, PaymentTypeService $paymentTypeService)
    {
        $this->saleProfitService = $saleProfitService;
        $this->saleReturnProfitService = $saleReturnProfitService;
        $this->paymentTypeService = $paymentTypeService;
    }

    public function getGrowthRecords(Request $request) : JsonResponse{
        try{
            $rules = [
                'from_date'         => ['required', 'date_format:'.implode(',', $this->getDateFormats())],
                'to_date'           => ['required', 'date_format:'.implode(',', $this->getDateFormats())],
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            $fromDate = $this->toSystemDateFormat($request->input('from_date'));
            $toDate = $this->toSystemDateFormat($request->input('to_date'));

            // Total Stock Value as of to_date: reconstruct stock qty up to to_date using item_transactions
            $positiveCodes = [
                \App\Enums\ItemTransactionUniqueCode::ITEM_OPENING->value,
                \App\Enums\ItemTransactionUniqueCode::PURCHASE->value,
                \App\Enums\ItemTransactionUniqueCode::SALE_RETURN->value,
                \App\Enums\ItemTransactionUniqueCode::STOCK_RECEIVE->value,
            ];

            $caseExpr = 'SUM(CASE WHEN unique_code IN ('.implode(',', array_map(function($c){ return "'".$c."'"; }, $positiveCodes)).") THEN quantity ELSE -quantity END) as qty";

            $sub = DB::table('item_transactions')
                ->selectRaw('item_id, '.$caseExpr)
                ->whereDate('transaction_date', '<=', $toDate)
                ->groupBy('item_id');

            $totalStockValue = (float) DB::table(DB::raw('('.$sub->toSql().') as t'))
                ->mergeBindings($sub)
                ->join('items', 'items.id', '=', 't.item_id')
                ->selectRaw('COALESCE(SUM(GREATEST(t.qty,0) * items.purchase_price),0) as total')
                ->value('total');

            // Total Due On Customer within date range: (sales + sale orders) - customer payments
            $totalSalesPOS = (float) Sale::whereBetween('sale_date', [$fromDate, $toDate])->sum('grand_total');
            $totalSalesOrders = (float) SaleOrder::whereBetween('order_date', [$fromDate, $toDate])->sum('grand_total');
            $totalCustomerPayments = (float) CustomerPayment::whereBetween('payment_date', [$fromDate, $toDate])->sum('amount');
            $totalDueOnCustomer = $totalSalesPOS + $totalSalesOrders - $totalCustomerPayments;

            // Total Purchase Supplier Balance within date range
            // Total Purchase Supplier Balance within date range: purchases - supplier payments
            $totalPurchases = (float) Purchase::whereBetween('purchase_date', [$fromDate, $toDate])->sum('grand_total');
            $totalSupplierPayments = (float) SupplierPayment::whereBetween('payment_date', [$fromDate, $toDate])->sum('amount');
            $totalPurchaseSupplierBalance = $totalPurchases - $totalSupplierPayments;

            // Cash In Hand within date range - compute net cash similar to CashController but filtered by date
            $cashId = $this->paymentTypeService->returnPaymentTypeId(PaymentTypesUniqueCode::CASH->value);

            $cashTypes = [
                'Sale', 'Sale Return', 'Sale Order', 'Purchase', 'Purchase Return', 'Purchase Order', 'Expense'
            ];

            // helper to sum by transaction type and whether it's considered in or out
            $sumByType = function($type) use ($cashId, $fromDate, $toDate) {
                return (float) PaymentTransaction::where('transaction_type', $type)
                        ->where(function($q) use ($cashId){
                            $q->where('payment_type_id', $cashId)
                              ->orWhere('transfer_to_payment_type_id', $cashId);
                        })
                        ->whereBetween('transaction_date', [$fromDate, $toDate])
                        ->sum('amount');
            };

            $cashTransactionOfSale = $sumByType('Sale');
            $cashTransactionOfSaleReturn = $sumByType('Sale Return');
            $cashTransactionOfSaleOrder = $sumByType('Sale Order');
            $cashTransactionOfPurchase = $sumByType('Purchase');
            $cashTransactionOfPurchaseReturn = $sumByType('Purchase Return');
            $cashTransactionOfPurchaseOrder = $sumByType('Purchase Order');
            $cashTransactionOfExpense = $sumByType('Expense');

            // Cash Adjustment net within date range
            $addCashIds = CashAdjustment::select('id')->where('adjustment_type', 'Cash Increase')->whereBetween('adjustment_date', [$fromDate, $toDate])->pluck('id');
            $reduceCashIds = CashAdjustment::select('id')->where('adjustment_type', 'Cash Reduce')->whereBetween('adjustment_date', [$fromDate, $toDate])->pluck('id');

            $netCashAdjustment = (float) PaymentTransaction::where('transaction_type', 'Cash Adjustment')
                                ->whereBetween('transaction_date', [$fromDate, $toDate])
                                ->whereIn('transaction_id', $addCashIds)
                                ->sum('amount')
                                - (float) PaymentTransaction::where('transaction_type', 'Cash Adjustment')
                                ->whereBetween('transaction_date', [$fromDate, $toDate])
                                ->whereIn('transaction_id', $reduceCashIds)
                                ->sum('amount');

            $cashInHand = ($cashTransactionOfSale + $cashTransactionOfPurchaseReturn + $cashTransactionOfSaleOrder + $netCashAdjustment) - ($cashTransactionOfSaleReturn + $cashTransactionOfPurchase + $cashTransactionOfPurchaseOrder + $cashTransactionOfExpense);

            // Total Profit (reuse sale profit service)
            $saleProfitTotalAmount = (float) $this->saleProfitService->saleProfitTotalAmount($fromDate, $toDate);
            $expenseTotalWithoutTaxAmount = (float) Expense::whereBetween('expense_date', [$fromDate, $toDate])->sum('grand_total');
            $netProfit = $saleProfitTotalAmount - $expenseTotalWithoutTaxAmount;

            $recordsArray = [
                'stock_value' => round((float) $totalStockValue, 2),
                'due_on_customer' => round((float) $totalDueOnCustomer, 2),
                'purchase_supplier_balance' => round((float) $totalPurchaseSupplierBalance, 2),
                'cash_in_hand' => round((float) $cashInHand, 2),
                'total_profit' => round((float) $netProfit, 2),
            ];

            return response()->json([
                'status' => true,
                'message' => 'Records are retrieved!!',
                'data' => $recordsArray,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }

}
