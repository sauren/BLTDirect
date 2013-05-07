<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Manufacturer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

class ProductBarcode {
	public $ID;
	public $Product;
	public $Barcode;
	public $Brand;
	public $Manufacturer;
	public $Quantity;

	public function __concept($id = null) {
		$this->Manufacturer = new Manufacturer();
		
		if(!is_null($id)) {
			$this->Get($id);
		}
	}

	public function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM product_barcode WHERE ProductBarcodeID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Product->ID = $data->Row['ProductID'];
			$this->Barcode = $data->Row['Barcode'];
			$this->Brand = $data->Row['Brand'];
			$this->Manufacturer->ID = $data->Row['ManufacturerID'];
			$this->Quantity = $data->Row['Quantity'];

            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function GetByBarcode($barcode = null) {
		if(!is_null($barcode)) {
			$this->Barcode = $barcode;
		}

		$data = new DataQuery(sprintf("SELECT ProductBarcodeID FROM product_barcode WHERE Barcode LIKE '%s'", mysql_real_escape_string($this->Barcode)));
		if($data->TotalRows > 0) {
			$return = $this->Get($data->Row['ProductBarcodeID']);

            $data->Disconnect();
			return $return;
		}

		$data->Disconnect();
		return false;
	}

	public function Add() {
		if(!$this->GetByBarcode()) {
			$data = new DataQuery(sprintf("INSERT INTO product_barcode (ProductID, Barcode, Brand, ManufacturerID, Quantity) VALUES (%d, '%s', '%s', %d, %d)", mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Barcode), mysql_real_escape_string($this->Brand), mysql_real_escape_string($this->Manufacturer->ID), mysql_real_escape_string($this->Quantity)));

			$this->ID = $data->InsertID;

			return true;
		}

		return false;
	}
	
	public function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_barcode WHERE ProductBarcodeID=%d", mysql_real_escape_string($this->ID)));
	}
}