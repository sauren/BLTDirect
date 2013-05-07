<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class GoogleCheckout {
	var $ID;
	var $Data;
	var $IsProcessed;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function GoogleCheckout($id=NULL){
		$this->IsProcessed = 'N';
		
		if(!is_null($id)){
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

		$data = new DataQuery(sprintf("SELECT * FROM google_checkout WHERE Google_Checkout_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Data = $data->Row['Data'];
			$this->IsProcessed = $data->Row['Is_Processed'];
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
		$data = new DataQuery(sprintf("INSERT INTO google_checkout (Data, Is_Processed, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Data), mysql_real_escape_string($this->IsProcessed), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update(){

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE google_checkout SET Data='%s', Is_Processed='%s', Modified_On=NOW(), Modified_By=%d WHERE Google_Checkout_ID=%d", mysql_real_escape_string($this->Data), mysql_real_escape_string($this->IsProcessed), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}


		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM google_checkout WHERE Google_Checkout_ID=%d", mysql_real_escape_string($this->ID)));
	}
}