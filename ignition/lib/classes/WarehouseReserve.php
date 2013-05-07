<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');

class WarehouseReserve {
	public static function deductReserves($warehouseId, $productId, $quantity) {
		if($quantity > 0) {		
			$data = new DataQuery(sprintf('SELECT * FROM warehouse_reserve WHERE warehouseId=%d AND productId=%d ORDER BY id ASC', mysql_real_escape_string($warehouseId), mysql_real_escape_string($productId)));
			while($data->Row) {
				if($data->Row['quantity'] > $quantity) {
					$reserve = new WarehouseReserve($data->Row['id']);
					$reserve->quantity -= $quantity;
					$reserve->update();
					
					$quantity = 0;
				} else {
					$reserve = new WarehouseReserve();
					$reserve->delete($data->Row['id']);
					
					$quantity -= $data->Row['quantity'];
				}
				
				if($quantity <= 0) {
					break;
				}
				
				$data->Next();
			}
			$data->Disconnect();
		}	
	}

	public $id;
	public $warehouse;
	public $product;
	public $quantity;
	
	public function __construct($id = null) {
		$this->warehouse = new Warehouse();
		$this->product = new Product();

		if(!is_null($id)) {
			$this->get($id);
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}


		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM warehouse_reserve WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
			$this->warehouse->ID = $data->Row['warehouseId'];
			$this->product->ID = $data->Row['productId'];

            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
	
	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO warehouse_reserve (warehouseId, productId, quantity) VALUES (%d, %d, %d)", mysql_real_escape_string($this->warehouse->ID), mysql_real_escape_string($this->product->ID), mysql_real_escape_string($this->quantity)));

		$this->id = $data->InsertID;
	}
	
	public function update() {


		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE warehouse_reserve SET warehouseId=%d, productId=%d, quantity=%d WHERE id=%d", mysql_real_escape_string($this->warehouse->ID), mysql_real_escape_string($this->product->ID), mysql_real_escape_string($this->quantity), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}


		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM warehouse_reserve WHERE id=%d", mysql_real_escape_string($this->id)));
	}
}