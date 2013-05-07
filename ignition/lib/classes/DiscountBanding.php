<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class DiscountBanding {
	var $ID;
	var $Name;
	var $Discount;
	var $TriggerLow;
	var $TriggerHigh;
	var $Threshold;
	var $Notes;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function DiscountBanding($id=NULL){
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) $this->ID = $id;

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM discount_banding WHERE Discount_Banding_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows) {
			$this->Name = $data->Row['Name'];
			$this->Discount = $data->Row['Discount'];
			$this->TriggerLow = $data->Row['Trigger_Low'];
			$this->TriggerHigh = $data->Row['Trigger_High'];
			$this->Threshold = $data->Row['Threshold'];
			$this->Notes = $data->Row['Notes'];
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
		$sql = sprintf("INSERT INTO discount_banding (Name, Discount, Trigger_Low, Trigger_High, Threshold, Notes, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', %d, %f, %f, %f, '%s', Now(), %d, Now(), %d)",
		mysql_real_escape_string($this->Name),
		mysql_real_escape_string($this->Discount),
		mysql_real_escape_string($this->TriggerLow),
		mysql_real_escape_string($this->TriggerHigh),
		mysql_real_escape_string($this->Threshold),
		mysql_real_escape_string(stripslashes($this->Notes)),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));

		$data = new DataQuery($sql);
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$sql = sprintf("UPDATE discount_banding SET Name='%s', Discount=%d, Trigger_Low=%f, Trigger_High=%f, Threshold=%f, Notes='%s', Modified_On=Now(), Modified_By=%d WHERE Discount_Banding_ID=%d",
		mysql_real_escape_string($this->Name),
		mysql_real_escape_string($this->Discount),
		mysql_real_escape_string($this->TriggerLow),
		mysql_real_escape_string($this->TriggerHigh),
		mysql_real_escape_string($this->Threshold),
		mysql_real_escape_string(stripslashes($this->Notes)),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->ID));

		$data = new DataQuery($sql);
		$data->Disconnect();
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("delete from discount_banding where Discount_Banding_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}
}
?>