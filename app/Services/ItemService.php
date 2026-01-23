<?php
namespace App\Services;

use App\Models\Items\Item;
use App\Models\Items\ItemTransaction;
use App\Models\Items\ItemGeneralQuantity;
use App\Enums\ItemTransactionUniqueCode;

class ItemService{

	/**
	 * Update Item Stock in Item Model
	 *
	 * */
	public function updateItemStock($itemId) : bool{
		$itemModel = Item::find($itemId);
		//Get the Sum of Quantity
		$baseUnitSumQuantity = $this->getSumOfItemQuantity($itemId);
		$itemModel->current_stock = $baseUnitSumQuantity;
		$itemModel->save();
		return true;
	}

	public function getSumOfItemQuantity($itemId)
	{
		$itemTransactions = ItemGeneralQuantity::where('item_id', $itemId)->sum('quantity');
		return $itemTransactions;
	}

}
