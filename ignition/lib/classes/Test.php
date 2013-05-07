<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TestSupplier.php');

class Test {
	public $ID;
	public $CreatedOn;
	public $CreatedBy;
	public $ModifiedOn;
	public $ModifiedBy;

	public function __construct($id=NULL) {
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

		$data = new DataQuery(sprintf("SELECT * FROM test WHERE TestID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
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
		$data = new DataQuery(sprintf("INSERT INTO test (CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES (NOW(), %d, NOW(), %d)", mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	public function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE test SET ModifiedOn=NOW(), ModifiedBy=%d WHERE TestID=%d", mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM test WHERE TestID=%d", mysql_real_escape_string($this->ID)));

		$supplier = new TestSupplier();

		$data = new DataQuery(sprintf("SELECT TestSupplierID FROM test_supplier WHERE TestID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$supplier->Delete($data->Row['TestSupplierID']);

			$data->Next();
		}
		$data->Disconnect();
	}
}
?>