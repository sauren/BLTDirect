<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class SupplierProductPriceCollection {
	public $Item;

	public function __construct() {
		$this->Reset();
	}

	public function GetPrices($productId, $supplierId) {
		$data = new DataQuery(sprintf("SELECT Quantity, Cost FROM supplier_product_price WHERE Product_ID=%d AND Supplier_ID=%d ORDER BY Quantity ASC, Created_On ASC", mysql_real_escape_string($productId), mysql_real_escape_string($supplierId)));
		while($data->Row) {
			if($data->Row['Cost'] > 0) {
				$this->Item[$data->Row['Quantity']] = $data->Row['Cost'];
			} else {
				unset($this->Item[$data->Row['Quantity']]);
			}

			$data->Next();
		}
		$data->Disconnect();
	}

	public function GetPrice($quantity) {
		$cost = 0;

		foreach($this->Item as $itemQuantity=>$itemCost) {
			if($quantity >= $itemQuantity) {
				$cost = $itemCost;
			}
		}

		return $cost;
	}

	public function GetQuantity($quantity) {
		$breakQuantity = 0;

		foreach($this->Item as $itemQuantity=>$itemCost) {
			if($quantity >= $itemQuantity) {
				$breakQuantity = $itemQuantity;
			}
		}

		return $breakQuantity;
	}

	public function Reset() {
		$this->Item = array();
	}
}