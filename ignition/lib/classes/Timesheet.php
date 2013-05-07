<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PublicHoliday.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserHoliday.php');

class Timesheet {
	var $ID;
	var $User;
	var $Type;
	var $Description;
	var $Date;
	var $Hours;
	var $UserHoliday;
	var $PublicHoliday;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function Timesheet($id=NULL){
		$this->User = new User();
		$this->Type = 'Standard';
		$this->UserHoliday = new UserHoliday();
		$this->PublicHoliday = new PublicHoliday();

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

		$data = new DataQuery(sprintf("SELECT * FROM timesheet WHERE Timesheet_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->Row) {
			$this->User->ID = $data->Row['User_ID'];
			$this->Type = $data->Row['Type'];
			$this->Description = $data->Row['Description'];
			$this->Date = $data->Row['Date'];
			$this->Hours = $data->Row['Hours'];
			$this->UserHoliday->ID = $data->Row['User_Holiday_ID'];
			$this->PublicHoliday->ID = $data->Row['Public_Holiday_ID'];
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
		$data = new DataQuery(sprintf("INSERT INTO timesheet (User_ID, Type, Description, Date, Hours, User_Holiday_ID, Public_Holiday_ID, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, '%s', '%s', '%s', %f, %d, %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->User->ID), mysql_real_escape_string($this->Type), mysql_real_escape_string($this->Description), mysql_real_escape_string($this->Date), mysql_real_escape_string($this->Hours), mysql_real_escape_string($this->UserHoliday->ID), mysql_real_escape_string($this->PublicHoliday->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE timesheet SET User_ID=%d, Type='%s', Description='%s', Date='%s', Hours=%f, User_Holiday_ID=%d, Public_Holiday_ID=%d, Modified_On=NOW(), Modified_By=%d WHERE Timesheet_ID=%d", mysql_real_escape_string($this->User->ID), mysql_real_escape_string($this->Type), mysql_real_escape_string($this->Description), mysql_real_escape_string($this->Date), mysql_real_escape_string($this->Hours), mysql_real_escape_string($this->UserHoliday->ID), mysql_real_escape_string($this->PublicHoliday->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM timesheet WHERE Timesheet_ID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeletePublicHoliday($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM timesheet WHERE Public_Holiday_ID=%d", mysql_real_escape_string($id)));
	}
}