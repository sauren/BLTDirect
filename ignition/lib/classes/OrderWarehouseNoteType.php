<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class OrderWarehouseNoteType {
	var $ID;
	var $Name;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function OrderWarehouseNoteType($id=NULL){
		if(!is_null($id)){
			$this->ID=$id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) {
			$this->ID=$id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM order_warehouse_note_type WHERE Order_Warehouse_Note_Type_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Name = $data->Row['Name'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO order_warehouse_note_type (Name, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', NOW(), %d, NOW(), %d)",	mysql_real_escape_string($this->Name), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE order_warehouse_note_type SET Name='%s', Modified_On=Now(), Modified_By=%d WHERE Order_Warehouse_Note_Type_ID=%d", mysql_real_escape_string($this->Name), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID=$id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM order_warehouse_note_type WHERE Order_Warehouse_Note_Type_ID=%d", mysql_real_escape_string($this->ID)));
	}
}