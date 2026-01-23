<?php
namespace App\Services\Reports\ProfitAndLoss;

use App\Models\Items\Item;
use App\Models\Items\ItemTransaction;
use App\Models\Items\ItemGeneralQuantity;
use App\Enums\ItemTransactionUniqueCode;
use Illuminate\Support\Facades\DB;
use App\Models\Sale\SaleReturn;

class SaleReturnProfitService{

	public function saleReturnTotalAmount($fromDate, $toDate){
		return SaleReturn::select('id', 'return_date')
                        ->whereBetween('return_date', [$fromDate, $toDate])
                        ->sum('grand_total');
    }//method end

}
