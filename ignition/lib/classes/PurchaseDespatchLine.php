<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/PurchaseLine.php");

class PurchaseDespatchLine {
	var $ID;
	var $PurchaseDespatchID;
	var $PurchaseLine;
	var $Quantity;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function __construct($id = null){
		$this->PurchaseLine = new PurchaseLine();

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

		$data = new DataQuery(sprintf("SELECT * FROM purchase_despatch_line WHERE Purchase_Despatch_Line_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->PurchaseDespatchID =$data->Row['Purchase_Despatch_ID'];
			$this->PurchaseLine->Get($data->Row['Purchase_Line_ID']);
			$this->Quantity = $data->Row['Quantity'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedBy = $data->Row['Modified_On'];
			$this->ModifiedOn = $data->Row['Modified_By'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO purchase_despatch_line (Purchase_Despatch_ID, Purchase_Line_ID, Quantity, Created_On, Created_By, Modified_On, Modified_By) values (%d, %d, %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->PurchaseDespatchID), mysql_real_escape_string($this->PurchaseLine->ID), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE purchase_despatch_line SET Quantity=%d, Modified_On=NOW(), Modified_By=%d WHERE Purchase_Despatch_Line_ID=%d", mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->ModifiedBy), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM purchase_despatch_line WHERE Purchase_Despatch_Line_ID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeletePurchase($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM purchase_despatch_line WHERE Purchase_Despatch_ID=%d", mysql_real_escape_string($id)));
	}

	static function DeletePurchaseDespatch($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM purchase_despatch_line WHERE Purchase_Despatch_ID=%d", mysql_real_escape_string($id)));
	}
}