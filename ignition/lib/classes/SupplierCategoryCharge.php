<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');

class SupplierCategoryCharge {
	public $ID;
	public $Supplier;
	public $Category;
	public $Charge;

	public function __construct($id = null) {
		$this->Supplier = new Supplier();
		$this->Category = new Category();

		if(!is_null($id)) {
			$this->Get($id);
		}
	}

	public function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		$data = new DataQuery(sprintf("SELECT * FROM supplier_category_charge WHERE SupplierCategoryChargeID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Supplier->ID = $data->Row['SupplierID'];
			$this->Category->ID = $data->Row['CategoryID'];
			$this->Charge = $data->Row['Charge'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function GetByReference($supplierId = null, $categoryId = null) {
		if(!is_null($supplierId)) {
			$this->Supplier->ID = $supplierId;
		}

		if(!is_null($categoryId)) {
			$this->Category->ID = $categoryId;
		}

		$data = new DataQuery(sprintf("SELECT SupplierCategoryChargeID FROM supplier_category_charge WHERE SupplierID=%d AND CategoryID=%d", mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->Category->ID)));
		if($data->TotalRows > 0) {
			$return = $this->Get($data->Row['SupplierCategoryChargeID']);

			$data->Disconnect();
			return $return;
		}

		$data->Disconnect();
		return false;
	}

	public function Add() {
		$data = new DataQuery(sprintf("INSERT INTO supplier_category_charge (SupplierID, CategoryID, Charge) VALUES (%d, %d, %f)", mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->Category->ID), mysql_real_escape_string($this->Charge)));

		$this->ID = $data->InsertID;
	}

	public function Update() {
		new DataQuery(sprintf("UPDATE supplier_category_charge SET Charge=%f WHERE SupplierCategoryChargeID=%d", mysql_real_escape_string($this->Charge), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		new DataQuery(sprintf("DELETE FROM supplier_category_charge WHERE SupplierCategoryChargeID=%d", mysql_real_escape_string($this->ID)));
	}
}