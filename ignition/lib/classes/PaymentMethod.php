<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class PaymentMethod {
	var $ID;
	var $Reference;
	var $Method;

	function __construct($id=NULL) {
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM payment_method WHERE Payment_Method_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->Row) {
			$this->Reference = $data->Row['Reference'];
			$this->Method = $data->Row['Method'];

			$data->Disconnect();
			return true;
		}
		$data->Disconnect();
		return false;
	}

	function GetByReference($reference=NULL){
		if(!is_null($reference)) {
			$this->Reference = $reference;
		}

		$data = new DataQuery(sprintf("SELECT Payment_Method_ID FROM payment_method WHERE Reference LIKE '%s'", mysql_real_escape_string($this->Reference)));
		if($data->Row) {
			$return = $this->Get($data->Row['Payment_Method_ID']);

			$data->Disconnect();
			return $return;
		}
		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO payment_method (Reference, Method) VALUES ('%s', '%s')", mysql_real_escape_string($this->Reference, $this->Method)));

		$this->ID = $data->InsertID;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE payment_method SET Reference='%s', Method='%s' WHERE Payment_Method_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM payment_method WHERE Payment_Method_ID=%d", mysql_real_escape_string($this->ID)));
	}
}