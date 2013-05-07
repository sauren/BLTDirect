<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class EmailDateProduct {
	var $ID;
	var $EmailDateID;
	var $ProductID;
	var $Sequence;

	function __construct($id=NULL) {
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

		$data = new DataQuery(sprintf("SELECT * FROM email_date_product WHERE EmailDateProductID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->EmailDateID = $data->Row['EmailDateID'];
			$this->ProductID = $data->Row['ProductID'];
			$this->Sequence = $data->Row['Sequence'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO email_date_product (EmailDateID, ProductID, Sequence) VALUES (%d, %d, %d)", mysql_real_escape_string($this->EmailDateID), mysql_real_escape_string($this->ProductID), mysql_real_escape_string($this->Sequence)));

		$this->ID = $data->InsertID;
		$this->Sequence = $this->ID;
		$this->Update();
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE email_date_product SET EmailDateID=%d, ProductID=%d, Sequence=%d WHERE EmailDateProductID=%d", mysql_real_escape_string($this->EmailDateID), mysql_real_escape_string($this->ProductID), mysql_real_escape_string($this->Sequence), mysql_real_escape_string($this->ID)));
	}
	
	function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}
		
		new DataQuery(sprintf("DELETE FROM email_date_product WHERE EmailDateProductID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeleteEmailDate($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM email_date_product WHERE EmailDateID=%d", mysql_real_escape_string($id)));
	}
}