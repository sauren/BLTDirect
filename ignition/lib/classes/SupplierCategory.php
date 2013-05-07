<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Address.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrganisationType.php');

class SupplierCategory {
	var $ID;
	var $SupplierID;
	var $CategoryID;

	function SupplierCategory($id = NULL){
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM supplier_categories WHERE Supplier_Category_ID=%d",mysql_real_escape_string($this->ID)));

		if($data->TotalRows > 0) {
			$this->SupplierID = $data->Row['Supplier_ID'];
			$this->CategoryID = $data->Row['Category_ID'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO supplier_categories (Supplier_ID, Category_ID) VALUES (%d, %d)", mysql_real_escape_string($this->SupplierID), mysql_real_escape_string($this->CategoryID)));
		$this->ID = $data->InsertID;
		$data->Disconnect();

		return true;
	}

	function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("DELETE FROM supplier_categories WHERE Supplier_Category_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();

		return true;
	}
}
?>