<?php

namespace App\Http\Controllers\Reports;

use App\Traits\FormatNumber; 
use App\Traits\FormatsDateInputs;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

use App\Models\Items\Item;
use App\Models\Items\ItemTransaction;

use App\Models\Sale\SaleReturn;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Expenses\Expense;
use App\Enums\ItemTransactionUniqueCode;

use App\Services\Reports\ProfitAndLoss\SaleProfitService;
use App\Services\Reports\ProfitAndLoss\SaleReturnProfitService;


class ProfitReportController extends Controller
{
    use FormatsDateInputs;

    use FormatNumber;

    private $saleProfitService;

    private $saleReturnProfitService;

    public function __construct(SaleProfitService $saleProfitService, SaleReturnProfitService $saleReturnProfitService)
    {
        $this->saleProfitService = $saleProfitService;
        $this->saleReturnProfitService = $saleReturnProfitService;
    }


    public function getProfitRecords(Request $request) : JsonResponse{
        try{
            // Validation rules
            $rules = [
                'from_date'         => ['required', 'date_format:'.implode(',', $this->getDateFormats())],
                'to_date'           => ['required', 'date_format:'.implode(',', $this->getDateFormats())],
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            $fromDate           = $request->input('from_date');
            $fromDate           = $this->toSystemDateFormat($fromDate);
            $toDate             = $request->input('to_date');
            $toDate             = $this->toSystemDateFormat($toDate);

           $saleTotalWithoutTaxAmount = (float) $this->saleProfitService->saleTotalAmount($fromDate, $toDate);

            $expenseTotalWithoutTaxAmount = (float) $this->expenseTotalAmount($fromDate, $toDate);

            $saleProfitTotalAmount = (float) $this->saleProfitService->saleProfitTotalAmount($fromDate, $toDate);

            $netProfit = $saleProfitTotalAmount - $expenseTotalWithoutTaxAmount;

            $recordsArray = [
                                    // new simplified keys used by the Profit Report table
                                    'sale_total'       => $this->formatWithPrecision($saleTotalWithoutTaxAmount),
                                    'expense_total'    => $this->formatWithPrecision($expenseTotalWithoutTaxAmount),
                                    'profit_total'     => $this->formatWithPrecision($saleProfitTotalAmount),
                                    'net_profit'       => $this->formatWithPrecision($netProfit),

                                    // keep legacy keys for compatibility with other consumers
                                    'sale_without_tax'              => $this->formatWithPrecision($saleTotalWithoutTaxAmount),
                                    'sale_return_without_tax'       => $this->formatWithPrecision($this->saleReturnProfitService->saleReturnTotalAmount($fromDate, $toDate)),
                                    'purchase_without_tax'          => $this->formatWithPrecision($this->purchaseTotalAmount($fromDate, $toDate)),
                                    'purchase_return_without_tax'   => $this->formatWithPrecision($this->purchaseReturnTotalAmount($fromDate, $toDate)),
                                    'gross_profit'                  => $this->formatWithPrecision(0),
                                    'indirect_expense_without_tax'  => $this->formatWithPrecision($expenseTotalWithoutTaxAmount),
                                    'sale_profit'                   => $this->formatWithPrecision($saleProfitTotalAmount),
                                ];
            
            return response()->json([
                        'status'    => true,
                        'message'   => "Records are retrieved!!",
                        'data'      => $recordsArray,
                    ]);
        } catch (\Exception $e) {
                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                ], 409);

        }
    }

    public function purchaseTotalAmount($fromDate, $toDate){
        return Purchase::select('id', 'purchase_date')
                        ->whereBetween('purchase_date', [$fromDate, $toDate])
                        ->sum('grand_total');
    }

    public function purchaseReturnTotalAmount($fromDate, $toDate){
        return PurchaseReturn::select('id', 'return_date')
                        ->whereBetween('return_date', [$fromDate, $toDate])
                        ->sum('grand_total');
    }

    public function expenseTotalAmount($fromDate, $toDate)
{
    return (float) Expense::whereBetween('expense_date', [$fromDate, $toDate])
        ->sum('grand_total');
}

}
