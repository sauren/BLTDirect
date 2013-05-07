<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class Package {
	public $ID;
	public $Name;
	public $Width;
	public $Height;
	public $Depth;
	public $Weight;
	public $ReductionPercent;
	public $CreatedOn;
	public $CreatedBy;
	public $ModifiedOn;
	public $ModifiedBy;

	public function __construct($id = null) {
		if(!is_null($id)) {
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

		$data = new DataQuery(sprintf("SELECT * FROM package WHERE Package_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Name = $data->Row['Name'];
			$this->Width = $data->Row['Width'];
			$this->Height = $data->Row['Height'];
			$this->Depth = $data->Row['Depth'];
			$this->Weight = $data->Row['Weight'];
			$this->ReductionPercent = $data->Row['Reduction_Percent'];
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

	public function Add() {
		$data = new DataQuery(sprintf("INSERT INTO package (Name, Width, Height, Depth, Weight, Reduction_Percent, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', %f, %f, %f, %f, %f, NOW(), %d, NOW(), %d)", mysql_escape_string($this->Name), mysql_real_escape_string($this->Width), mysql_real_escape_string($this->Height), mysql_real_escape_string($this->Depth), mysql_real_escape_string($this->Weight), mysql_real_escape_string($this->ReductionPercent), mysql_real_escape_string($GLOBALS['Session_USER_ID']), mysql_real_escape_string($GLOBALS['Session_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	public function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE package SET Name='%s', Width=%f, Height=%f, Depth=%f, Weight=%f, Reduction_Percent=%f, Modified_On=NOW(), Modified_By=%d WHERE Package_ID=%d", mysql_escape_string($this->Name), mysql_real_escape_string($this->Width), mysql_real_escape_string($this->Height), mysql_real_escape_string($this->Depth), mysql_real_escape_string($this->Weight), mysql_real_escape_string($this->ReductionPercent), mysql_real_escape_string($GLOBALS['Session_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM package WHERE Package_ID=%d", mysql_real_escape_string($this->ID)));
	}
}
?>