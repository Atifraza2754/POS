<?php
namespace App\Services\Reports\ProfitAndLoss;

use App\Models\Items\Item;
use App\Models\Items\ItemTransaction;
use App\Models\Items\ItemGeneralQuantity;
use App\Enums\ItemTransactionUniqueCode;
use App\Services\PaymentTransactionService;
use Illuminate\Support\Facades\DB;
use App\Models\Sale\Sale;

class SaleProfitService{

	private $paymentTransactionService;

	public function __construct(PaymentTransactionService $paymentTransactionService)
    {
        $this->paymentTransactionService = $paymentTransactionService;
    }

   
    public function saleTotalAmount($fromDate, $toDate){
        return Sale::select('id', 'sale_date')
                        ->whereBetween('sale_date', [$fromDate, $toDate])
                        ->sum('grand_total');
    }

    public function saleProfitTotalAmount($fromDate, $toDate) {
        $sales = Sale::whereBetween('sale_date', [$fromDate, $toDate])->get();
        
        $totalProfit = 0;
        
        foreach ($sales as $sale) {
            $saleItems = ItemTransaction::where('transaction_id', $sale->id)
                                        ->where('transaction_type', 'Sale')
                                        ->with('item')
                                        ->get();
            
            foreach ($saleItems as $saleItem) {
                // Calculate sale item profit
                $saleQuantity = $saleItem->quantity;
                $salePricePerUnit = $saleItem->unit_price;
                $purchasePricePerUnit = $saleItem->item->purchase_price;
                
                // Subtract any sale returns
                $saleReturns = ItemTransaction::where('item_id', $saleItem->item_id)
                                              ->where('transaction_type', 'Sale Return')
                                              ->sum('quantity');
                
                $effectiveQuantity = $saleQuantity - $saleReturns;
                
                // Calculate profit per item
                $itemProfit = ($salePricePerUnit - $purchasePricePerUnit) * $effectiveQuantity;
                
                $totalProfit += $itemProfit;
            }
        }
        
        return $totalProfit;
    }

	// public function saleTotalAmount($fromDate, $toDate){

    //     $saleItemsCollection   = collect();
    //     $openingDataCollection = collect();
    //     $newSaleItemsCollection = collect();

    //     // Ensure morph map keys are defined
    //     $this->paymentTransactionService->usedTransactionTypeValue();

    //     /**
    //      * Get Sale Master Records based on date
    //      * */
    //     $sales = Sale::select('id', 'sale_date')
    //                     ->whereBetween('sale_date', [$fromDate, $toDate])
    //                     ->get();
    //     if($sales->isEmpty()){
    //         return 0;
    //     }

    //     $saleItems = ItemTransaction::with(['item' => function ($query) {
    //                                         $query->select('id','sale_price', 'purchase_price');
    //                                     }])
    //                                 ->select('item_id', 
    //                                         DB::raw('SUM(quantity) as total_quantity'), 
    //                                         DB::raw('AVG(unit_price) as avg_price'))
    //                                 ->whereIn('transaction_id', $sales->pluck('id')->toArray()) // Get all sale ids from the sales collection
    //                                 ->where('transaction_type', 'Sale')
    //                                 ->groupBy('item_id') // Group by item_id and transaction_id
    //                                 ->get();
        
    //     /**
    //      * Get Sale Items
    //      * */
    //     if($saleItems->isEmpty()){
    //         return 0;
    //     }
        
    //     // Step 1: Fetch and handle sale returns
    //     /*$saleReturns = ItemTransaction::select('item_id', DB::raw('SUM(quantity) as total_return_quantity'))
    //                                ->where('transaction_type', 'Sale Return')
    //                                ->groupBy('item_id')
    //                                ->get();
        
    //     // Step 2: Loop through each sale item and reduce quantity based on sale returns
    //     $saleItems->each(function ($transaction) use ($saleReturns, $saleItemsCollection) {
    //         $returnQuantity = $saleReturns->firstWhere('item_id', $transaction->item_id)->total_return_quantity ?? 0;

    //         // Adjust the total sale quantity based on sale returns
    //         $adjustedQuantity = $transaction->total_quantity - $returnQuantity;

    //         if ($adjustedQuantity > 0) {
    //             // Add to the collection
    //             $saleItemsCollection->push([
    //                 'item_master_sale_price'               => $transaction->item->sale_price,
    //                 'item_master_purchase_price'           => $transaction->item->purchase_price,
    //                 'sale_item_id'                         => $transaction->item_id,
    //                 'sale_item_total_quantity'             => $adjustedQuantity, // Adjusted for sale returns
    //                 'sale_item_average_price'              => $transaction->avg_price,
    //                 'sale_item_opening_quantity'           => 0,
    //                 'sale_item_opening_unit_purchase_price'=> 0,
    //             ]);
    //         }
    //     });*/
    //     $saleItems->each(function ($transaction) use ($saleItemsCollection) {
    //         // Add to the collection
    //         $saleItemsCollection->push([
    //             'item_master_sale_price'               => $transaction->item->sale_price,
    //             'item_master_purchase_price'           => $transaction->item->purchase_price,
    //             'sale_item_id'                              => $transaction->item_id,
    //             'sale_item_total_quantity'                  => $transaction->total_quantity,
    //             'sale_item_average_price'                   => $transaction->avg_price,
    //             'sale_item_opening_quantity'                => 0,
    //             'sale_item_opening_unit_purchase_price'     => 0,
    //         ]);
    //     });
       
    //     /**
    //      * Check is any opening stock of the sale items?
    //      * Each Item master has single Item Opening
    //      * */
    //     $items = Item::with('itemTransaction')
    //                     ->select('id','sale_price','purchase_price', 'tax_id')
    //                     ->whereIn('id', $saleItemsCollection->pluck('sale_item_id'))
    //                     ->get();
    //     if($items->isEmpty()){
    //         return 0;
    //     }
    //     // Loop through each item and build the collection
    //     $items->each(function ($item) use ($openingDataCollection) {
    //         $item->itemTransaction->each(function ($transaction) use ($openingDataCollection) {
    //             // Add to the collection
    //             $openingDataCollection->push([
    //                 'opening_item_id'               => $transaction->item_id,
    //                 'opening_item_opening_quantity' => $transaction->quantity,
    //                 'opening_item_unit_price'       => $transaction->unit_price,
    //             ]);
    //         });
    //     });

    //     //Deduct the item quantity of sale item quantity
    //     foreach ($saleItemsCollection as $item) {
    //         //echo $item['item_id'];
    //         if($item['sale_item_total_quantity'] > 0){

    //             $saleItemId = $item['sale_item_id'];

    //             //Find the opening_item_opening_quantity & shouleb greater then 0
    //             $openingQuantity = $openingDataCollection->firstWhere('opening_item_id', $saleItemId);

    //             //If record exist
    //             if($openingQuantity){
                    
    //                 $openingItemQuantity = $openingQuantity['opening_item_opening_quantity'];
    //                 $openingItemUnitPrice = $openingQuantity['opening_item_unit_price'];

    //                 if($openingItemQuantity > 0){
    //                     //update sale item collection
    //                     $newSaleItemsCollection = $saleItemsCollection->transform(function ($saleItems) use ($saleItemId, $openingItemQuantity, $openingItemUnitPrice) {

    //                         if ($saleItems['sale_item_id'] == $saleItemId) {
    //                             $saleItems['sale_item_opening_quantity'] = $openingItemQuantity;
    //                             $saleItems['sale_item_opening_unit_purchase_price'] = $openingItemUnitPrice;
    //                         }
    //                         return $saleItems;

    //                     });//transform end
    //                 }

    //             }//openingQuantity end
    //         }
    //     }//saleItemsCollection end
    //     if($newSaleItemsCollection->isEmpty()){
    //         $newSaleItemsCollection = $saleItemsCollection;
    //     }
        
    //     //update sale item collection
    //     $newSaleItemsCollection = $newSaleItemsCollection->transform(function ($saleItems) {

    //         /**
    //          * Added new array variable
    //          * $saleItems['sale_qty_minus_opening_qty']
    //          * */
    //         if($saleItems['sale_item_opening_quantity'] > $saleItems['sale_item_total_quantity']){
    //             $saleItems['sale_qty_minus_opening_qty'] = 0;
    //         }else{
    //             $saleItems['sale_qty_minus_opening_qty'] = $saleItems['sale_item_total_quantity'] - $saleItems['sale_item_opening_quantity'];
    //         }
    //         return $saleItems;
    //     });//transform end

    //     //Find the Purchase item 
    //     $newSaleItemsCollection = $this->getItemPurchasePriceFromPurchaseEntry($newSaleItemsCollection);

    //     //convert to array
    //     $totalSaleItemsArray = $newSaleItemsCollection->toArray();

    //     $totalProfit = 0;
        
    //     foreach ($totalSaleItemsArray as $data) {

    //         $totalSaleItemQty = $data['sale_item_total_quantity'];//2

    //         $totalSalePriceAverage = $data['sale_item_average_price'] * $totalSaleItemQty;//600 * 2 = 1200

    //         $availableOpeningQty = $data['sale_item_opening_quantity'];//1

    //         $minusOpeningPurchasePrice = ($data['sale_item_opening_unit_purchase_price']!=0 ? $data['sale_item_opening_unit_purchase_price'] : $data['item_master_purchase_price']) * $availableOpeningQty;

    //         $remainingQuantity = $data['sale_qty_minus_opening_qty'];//after opening deduction = 1

    //         $minusPurchaseEntryPrice = $data['remaining_quantity_total_purchase_price']; // 0

    //         //Caculate through opening
    //         $totalProfit += ($totalSalePriceAverage - $minusOpeningPurchasePrice) - $minusPurchaseEntryPrice;
    //     }

    //     return $totalProfit;

    // }//method end

    public function getItemPurchasePriceFromPurchaseEntry($newSaleItemsCollection){
        // Ensure morph map keys are defined
        $this->paymentTransactionService->usedTransactionTypeValue();

        $purchasePriceData = []; // To store adjusted sale price information

        $finalSaleItemsCollection = $newSaleItemsCollection->transform(function ($saleItems) {

            $remainingQuantity  = $saleItems['sale_qty_minus_opening_qty'];

            //$eachItemSalePrice =  $saleItems['sale_item_average_price'];

            $saleItems['remaining_quantity'] = 0;

            /*ItemTransaction::where('transaction_type', 'Purchase')
                            ->orderBy('transaction_date')
                            ->where('item_id', $saleItems['sale_item_id'])
                            ->chunk(10, function ($purchaseItems) use (&$remainingQuantity, &$purchasePriceData) {
                                foreach ($purchaseItems as $transaction) {
                                    if ($remainingQuantity <= 0) {
                                        break;
                                    }

                                    // Calculate the remaining quantity for this purchase after considering returns
                                    $purchaseQuantity = $transaction->quantity;
                                    $purchasePrice = $transaction->unit_price;

                                    // Find matching purchase return transactions and subtract the returned quantity
                                    $purchaseReturnQuantity = ItemTransaction::where('transaction_type', 'Purchase Return')
                                        ->where('item_id', $transaction->item_id)
                                        ->sum('quantity'); // Total quantity returned for this purchase

                                    $adjustedPurchaseQuantity = $purchaseQuantity - $purchaseReturnQuantity;

                                    if ($adjustedPurchaseQuantity <= 0) {
                                        continue; // Skip if all quantity was returned
                                    }

                                    // Now process the adjusted purchase quantity in FIFO manner
                                    if ($adjustedPurchaseQuantity > 0) {
                                        if ($adjustedPurchaseQuantity <= $remainingQuantity) {
                                            // The whole adjusted quantity can be used to fulfill the sale
                                            $purchasePriceData[] = [
                                                'transaction_id'    => $transaction->id,
                                                'quantity'          => $adjustedPurchaseQuantity,
                                                'purchase_price'    => $purchasePrice,
                                                'total'             => $adjustedPurchaseQuantity * $purchasePrice,
                                            ];
                                            $remainingQuantity -= $adjustedPurchaseQuantity;
                                        } else {
                                            // Partially use the purchase quantity to fulfill the sale
                                            $purchasePriceData[] = [
                                                'transaction_id'    => $transaction->id,
                                                'quantity'          => $remainingQuantity,
                                                'purchase_price'    => $purchasePrice,
                                                'total'             => $remainingQuantity * $purchasePrice,
                                            ];
                                            $remainingQuantity = 0;
                                        }
                                    }
                                }
                            });*/


            ItemTransaction::where('transaction_type', 'Purchase')
                ->orderBy('transaction_date')
                ->where('item_id', $saleItems['sale_item_id'])
                ->chunk(30, function ($purchaseItems) use (&$remainingQuantity, &$purchasePriceData) {
                    foreach ($purchaseItems as $transaction) {
                        
                        if ($remainingQuantity <= 0) {
                            break;
                        }
                        $purchasePrice = $transaction->unit_price;

                        $purchaseReturn = ItemTransaction::where('transaction_type', 'Purchase Return')->where('item_id', $transaction->item_id)->get();
                        if($purchaseReturn->count()>0){

                        }

                        if ($transaction->quantity > 0) {
                            if ($transaction->quantity <= $remainingQuantity) {
                                
                                $purchasePriceData[] = [
                                    'transaction_id'    => $transaction->id,
                                    'quantity'          => $transaction->quantity,
                                    'purchase_price'    => $purchasePrice,
                                    'total'             => $transaction->quantity * $purchasePrice,
                                    //'remainingQuantity' => $remainingQuantity - $transaction->quantity,
                                ];
                                $remainingQuantity -= $transaction->quantity;
                               
                             
                            } else {
                                
                                $purchasePriceData[] = [
                                    'transaction_id'    => $transaction->id,
                                    'quantity'          => $remainingQuantity,
                                    'purchase_price'    => $purchasePrice,
                                    'total'             => $remainingQuantity * $purchasePrice,
                                    //'remainingQuantity' => $remainingQuantity - $transaction->quantity,
                                ];
                                $transaction->quantity -= $remainingQuantity;
                                
                                $remainingQuantity = 0;
                                
                            }
                        }


                    }// foreach
                });

            $saleItems['remaining_quantity'] = $purchasePriceData??$saleItems['sale_qty_minus_opening_qty'];
            
            // After processing, you can calculate profit and loss based on $purchasePriceData
            $totalCost = is_array($purchasePriceData) ? array_sum(array_column($purchasePriceData, 'total')) : 0; // Total cost of adjustments

            $saleItems['remaining_quantity_total_purchase_price'] = $totalCost;

            return $saleItems;
        });//transform end

        return $finalSaleItemsCollection;
    }

}
