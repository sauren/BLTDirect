<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");

class DebitLine {
	var $ID;
	var $DebitID;
	var $Description;
	var $Quantity;
	var $Product;
	var $Cost;
	var $Total;
	var $Reason;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $Custom;
	var $SuppliedBy;

	function DebitLine($id=NULL) {
		$this->Product = new Product();

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

		$data = new DataQuery(sprintf("SELECT * FROM debit_line WHERE Debit_Line_ID=%d", mysql_real_escape_string($this->ID)));

		if($data->TotalRows > 0) {
			$this->DebitID = $data->Row['Debit_ID'];
			$this->Description = $data->Row['Description'];
			$this->Quantity = $data->Row['Quantity'];
			$this->Product->ID = $data->Row['Product_ID'];
			$this->Cost = $data->Row['Cost'];
			$this->Total = $data->Row['Line_Total'];
			$this->Reason = $data->Row['Reason'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$this->SuppliedBy = $data->Row['Supplied_By'];
			$this->Custom = $data->Row['Custom'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO debit_line (Debit_ID, Description, Quantity, Product_ID, Cost, Line_Total, Reason, Created_On, Created_By, Modified_On, Modified_By, Supplied_By, Custom) VALUES (%d, '%s', %d, %d, %f, %f, '%s', Now(), %d, Now(), %d, %d, '%s')", mysql_real_escape_string($this->DebitID), mysql_real_escape_string($this->Description), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Cost), mysql_real_escape_string($this->Total), mysql_real_escape_string($this->Reason), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->SuppliedBy), mysql_real_escape_string($this->Custom)));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}

	function Update() {
		new DataQuery(sprintf("UPDATE debit_line SET Debit_ID=%d, Description='%s', Quantity=%d, Product_ID=%d, Cost=%f, Line_Total=%f, Reason='%s', Modified_On=Now(), Modified_By=%d, Supplied_By=%d, Custom='%s' WHERE Debit_Line_ID=%d", mysql_real_escape_string($this->DebitID), mysql_real_escape_string($this->Description), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Cost), mysql_real_escape_string($this->Total), mysql_real_escape_string($this->Reason), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->SuppliedBy), mysql_real_escape_string($this->Custom), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}


		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM debit_line WHERE Debit_Line_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function Exists() {
		$data = new DataQuery(sprintf("SELECT Debit_Line_ID, Quantity FROM debit_line WHERE Debit_ID=%d AND Product_ID=%d", mysql_real_escape_string($this->DebitID), mysql_real_escape_string($this->Product->ID)));

		if($data->TotalRows > 0){
			$this->ID = $data->Row['Debit_Line_ID'];

			$qty = $data->Row['Quantity'];

			$data->Disconnect();
			return $qty;
		} else {
			return false;
		}
	}

	static function DeleteDebit($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM debit_line WHERE Debit_ID=%d", mysql_real_escape_string($id)));
	}
}
?>