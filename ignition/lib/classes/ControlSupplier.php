<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class ControlSupplier {
	var $ID;
	var $SupplierID;

	function ControlSupplier($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM control_supplier WHERE Control_Supplier_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->SupplierID = $data->Row['Supplier_ID'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO control_supplier (Supplier_ID) VALUES (%d)", mysql_real_escape_string($this->SupplierID)));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("UPDATE control_supplier SET Supplier_ID=%d WHERE Control_Supplier_ID=%d", mysql_real_escape_string($this->SupplierID), mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}
	
	function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("DELETE FROM control_supplier WHERE Control_Supplier_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();	
	}
}
?>