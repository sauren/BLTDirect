<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OverheadType.php');

class Overhead {
	var $ID;
	var $Type;
	var $Name;
	var $Value;
	var $Period;
	var $IsWorkingDaysOnly;
	var $StartDate;
	var $EndDate;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function Overhead($id=NULL) {
		$this->Type = new OverheadType();
		$this->Period = 'D';
		$this->IsWorkingDaysOnly = 'N';
		$this->StartDate = '0000-00-00 00:00:00';
		$this->EndDate = '0000-00-00 00:00:00';

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

		$data = new DataQuery(sprintf("SELECT * FROM overhead WHERE Overhead_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->Row) {
			$this->Type->ID = $data->Row['Overhead_Type_ID'];
			$this->Name = $data->Row['Name'];
			$this->Value = $data->Row['Value'];
			$this->Period = $data->Row['Period'];
			$this->IsWorkingDaysOnly = $data->Row['Is_Working_Days_Only'];
			$this->StartDate = $data->Row['Start_Date'];
			$this->EndDate = $data->Row['End_Date'];
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
		$data = new DataQuery(sprintf("INSERT INTO overhead (Overhead_Type_ID, Name, Value, Period, Is_Working_Days_Only, Start_Date, End_Date, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, '%s', %f, '%s', '%s', '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Type->ID), mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Value), mysql_real_escape_string($this->Period), mysql_real_escape_string($this->IsWorkingDaysOnly), mysql_real_escape_string($this->StartDate), mysql_real_escape_string($this->EndDate), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE overhead SET Overhead_Type_ID=%d, Name='%s', Value=%f, Period='%s', Is_Working_Days_Only='%s', Start_Date='%s', End_Date='%s', Modified_On=NOW(), Modified_By=%d WHERE Overhead_ID=%d", mysql_real_escape_string($this->Type->ID), mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Value), mysql_real_escape_string($this->Period), mysql_real_escape_string($this->IsWorkingDaysOnly), mysql_real_escape_string($this->StartDate), mysql_real_escape_string($this->EndDate), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM overhead WHERE Overhead_ID=%d", mysql_real_escape_string($this->ID)));
	}
}