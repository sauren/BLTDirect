<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

class Bonus {
	public $ID;
	public $User;
	public $StartOn;
	public $EndOn;
	public $BonusAmount;
	public $CreatedOn;
	public $CreatedBy;
	public $ModifiedOn;
	public $ModifiedBy;

	public function __construct($id=NULL) {
		$this->User = new User();

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	public function Get($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM bonus WHERE BonusID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->User->ID = $data->Row['UserID'];
			$this->StartOn = $data->Row['StartOn'];
			$this->EndOn = $data->Row['EndOn'];
			$this->BonusAmount = $data->Row['BonusAmount'];
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

	public function Add() {
		$data = new DataQuery(sprintf("INSERT INTO bonus (UserID, StartOn, EndOn, BonusAmount, CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES (%d, '%s', '%s', %f, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->User->ID), mysql_real_escape_string($this->StartOn), mysql_real_escape_string($this->EndOn), mysql_real_escape_string($this->BonusAmount), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	public function Update() {

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE bonus SET UserID=%d, StartOn='%s', EndOn='%s', BonusAmount=%f, ModifiedOn=NOW(), ModifiedBy=%d WHERE BonusID=%d", mysql_real_escape_string($this->User->ID), mysql_real_escape_string($this->StartOn), mysql_real_escape_string($this->EndOn), mysql_real_escape_string($this->BonusAmount), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM bonus WHERE BonusID=%d",  mysql_real_escape_string($this->ID)));
	}
}
?>