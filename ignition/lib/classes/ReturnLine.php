<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Product.php');

class ProductReturnLine {
	var $ID;
	var $ReturnID;
	var $Product;
	var $Quantity;
	var $Status;
	var $IsRestocking;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function ProductReturnLine($id=null){
		$this->Product = new Product();
		$this->IsRestocking = 'N';

		if(!is_null($id)){
			$this->Get($id);
		}
	}

	function Get($id=null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM return_line WHERE Return_Line_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->ReturnID = $data->Row['Return_ID'];
			$this->Product->ID = $data->Row['Product_ID'];
			$this->Quantity = $data->Row['Quantity'];
			$this->Status = $data->Row['Status'];
			$this->IsRestocking = $data->Row['Is_Restocking'];
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
		$data = new DataQuery(sprintf("INSERT INTO return_line (Return_ID, Product_ID, Quantity, Status, Is_Restocking, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, %d, %d, 'Unresolved', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->ReturnID), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->IsRestocking), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}

	function Update($id=null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("UPDATE return_line SET Return_ID=%d, Product_ID=%d, Quantity=%d, Status='%s', Is_Restocking='%s', Modified_On=NOW(), Modified_By=%d WHERE Return_Line_ID=%d", mysql_real_escape_string($this->ReturnID), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->Status), mysql_real_escape_string($this->IsRestocking), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}

	function Delete($id=null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("DELETE FROM return_line WHERE Return_Line_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}
}
?>