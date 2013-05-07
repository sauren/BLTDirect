<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');

class Backorder {
	public $ID;
	public $Product;
	public $Supplier;
	public $Quantity;
	public $ExpectedOn;
	public $OrderLine;
	public $CreatedOn;
	public $CreatedBy;
	public $ModifiedOn;
	public $ModifiedBy;

	public function __construct($id=NULL) {
		$this->Product = new Product();
		$this->Supplier = new Supplier();
		$this->OrderLine = new OrderLine();

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	public function Get($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM backorder WHERE BackorderID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Product->ID = $data->Row['ProductID'];
			$this->Supplier->ID = $data->Row['SupplierID'];
			$this->Quantity = $data->Row['Quantity'];
			$this->ExpectedOn = $data->Row['ExpectedOn'];
			$this->OrderLine->ID = $data->Row['OrderLineID'];
			$this->CreatedOn = $data->Row['CreatedOn'];
			$this->CreatedBy = $data->Row['CreatedBy'];
			$this->ModifiedOn = $data->Row['ModifiedOn'];
			$this->ModifiedBy = $data->Row['ModifiedBy'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
	
	public function GetByOrderLineID($orderLineId=NULL) {
		if(!is_null($orderLineId)) {
			$this->OrderLine->ID = $orderLineId;
		}

		$data = new DataQuery(sprintf("SELECT BackorderID FROM backorder WHERE OrderLineID=%d", mysql_real_escape_string($this->OrderLine->ID)));
		if($data->TotalRows > 0) {
			$return = $this->Get($data->Row['BackorderID']);
			
			$data->Disconnect();
			return $return;
		}

		$data->Disconnect();
		return false;
	}

	public function Add() {
		$data = new DataQuery(sprintf("INSERT INTO backorder (ProductID, SupplierID, Quantity, ExpectedOn, OrderLineID, CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES (%d, %d, %d, '%s', %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->ExpectedOn), mysql_real_escape_string($this->OrderLine->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	public function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE backorder SET ProductID=%d, SupplierID=%d, Quantity=%d, ExpectedOn='%s', OrderLineID=%d, ModifiedOn=NOW(), ModifiedBy=%d WHERE BackorderID=%d", mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->ExpectedOn), mysql_real_escape_string($this->OrderLine->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM backorder WHERE BackorderID=%d",  mysql_real_escape_string($this->ID)));
	}
}
?>