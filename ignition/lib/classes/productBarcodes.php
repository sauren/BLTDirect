<?php
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

class ProductBarcodes {
	var $ID;
	var $ProductID;
	var $Barcode;
	var $Brand;
	var $Quantity;
	var $ManufacturerID;

	function ProductPrice($id=null){
		$this->Quantity = 1;
		if(!is_null($id)) {
			$this->Get($id);
		}
	}

	function Get($id=null){
		if(!is_null($id)){
			$this->ID = $id;	
		} 
		$sql = "select * from product_barcode where ProductID = {$this->ID}";
		$data = new DataQuery($sql);
		if($data->TotalRows > 0){
			$this->ProductID = $data->Row['ProductID'];
			$this->Barcode = $data->Row['Barcode'];
			$this->Brand = $data->Row['Brand'];
			$this->ManufacturerID = $data->Row['ManufacturerID'];
			$this->Quantity = $data->Row['Quantity'];
			return true;
		} else return false;
	}

	function Add($connection = null){
		$sql = sprintf("insert into product_barcode (ProductID, Barcode, Brand, ManufacturerID, Quantity) values (%d, '%s', '%s', %d, %d)",
		mysql_real_escape_string($this->ProductID),
		mysql_real_escape_string($this->Barcode),
		mysql_real_escape_string($this->Brand),
		mysql_real_escape_string($this->ManufacturerID),
		mysql_real_escape_string($this->Quantity));

		$data = new DataQuery($sql, $connection);
		$this->ID = $data->InsertID;
		return true;
	}

	function Update($id=null, $connection = null){
		if(!is_null($id)) $this->ID = $id;
		$sql = sprintf("update product_barcode set 
Barcode = {$this->Barcode},
brand = '{$this->Brand}',
ManufacturerID = {$this->ManufacturerID},
Quantity = {$this->Quantity}
where ProductBarcodeID = {$this->ID}");
		$data = new DataQuery($sql, $connection);
		$data->Disconnect();
	}

	static function DeleteProduct($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("delete from product_barcode where ProductBarcodeID=%d", mysql_real_escape_string($id)));
	}

	static function DeleteProductID($pid) {
		if(!is_numeric($pid)){
			return false;
		}
		new DataQuery(sprintf("delete from product_barcode where ProductID=%d", mysql_real_escape_string($pid)));
	}
}
?>