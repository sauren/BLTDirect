<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class GoogleConversion {
	var $ID;
	var $Conversions;
	var $Month;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function __construct($id=NULL){
		$this->Month = '0000-00-00 00:00:00';
		
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

		$data = new DataQuery(sprintf("SELECT * FROM google_conversion WHERE GoogleConversionID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Conversions = $data->Row['Conversions'];
			$this->Month = $data->Row['Month'];
			$this->CreatedOn = $data->Row['CreatedOn'];
			$this->CreatedBy = $data->Row['CreatedBy'];
			$this->ModifiedOn = $data->Row['ModifiedOn'];
			$this->ModifiedBy = $data->Row['ModifiedBy'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO google_conversion (Conversions, Month, CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES (%d, '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Conversions), mysql_real_escape_string($this->Month), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update(){

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE google_conversion SET Conversions=%d, Month='%s', ModifiedOn=NOW(), ModifiedBy=%d WHERE GoogleConversionID=%d", mysql_real_escape_string($this->Conversions), mysql_real_escape_string($this->Month), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}


		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM google_conversion WHERE GoogleConversionID=%d", mysql_real_escape_string($this->ID)));
	}
}