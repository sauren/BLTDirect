<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CallFrom.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CallTo.php');

class Call {
	var $ID;
	var $CallFrom;
	var $CallTo;
	var $Duration;
	var $Cost;
	var $CalledOn;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function Call($id=NULL){
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

		$data = new DataQuery(sprintf("SELECT * FROM `call` WHERE Call_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->Row) {
			$this->CallFrom->ID = $data->Row['Call_From_ID'];
			$this->CallTo->ID = $data->Row['Call_To_ID'];
			$this->Duration = $data->Row['Duration'];
			$this->Cost = $data->Row['Cost'];
			$this->CalledOn = $data->Row['Called_On'];
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
		$data = new DataQuery(sprintf("INSERT INTO `call` (Call_From_ID, Call_To_ID, Duration, Cost, Called_On, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, %d, %d, %f, '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->CallFrom->ID), mysql_real_escape_string($this->CallTo->ID), mysql_real_escape_string($this->Duration), mysql_real_escape_string($this->Cost), mysql_real_escape_string($this->CalledOn), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE `call` SET Call_From_ID=%d, Call_To_ID=%d, Duration=%d, Cost=%f, Called_On='%s', Modified_On=NOW(), Modified_By=%d WHERE Call_ID=%d", mysql_real_escape_string($this->CallFrom->ID), mysql_real_escape_string($this->CallTo->ID), mysql_real_escape_string($this->Duration), mysql_real_escape_string($this->Cost), mysql_real_escape_string($this->CalledOn), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM `call` WHERE Call_ID=%d", mysql_real_escape_string($this->ID)));
	}
}
?>