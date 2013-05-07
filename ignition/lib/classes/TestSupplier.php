<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerContact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TestSupplierProduct.php');

class TestSupplier {
	public $ID;
	public $TestID;
	public $Supplier;
	public $Customer;
	public $CustomerContact;
	public $CreatedOn;
	public $CreatedBy;
	public $ModifiedOn;
	public $ModifiedBy;

	public function __construct($id=NULL) {
		$this->Supplier = new Supplier();
		$this->Customer = new Customer();
		$this->CustomerContact = new CustomerContact();

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

		$data = new DataQuery(sprintf("SELECT * FROM test_supplier WHERE TestSupplierID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->TestID = $data->Row['TestID'];
			$this->Supplier->ID = $data->Row['SupplierID'];
			$this->Customer->ID = $data->Row['CustomerID'];
			$this->CustomerContact->ID = $data->Row['CustomerContactID'];
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
		$data = new DataQuery(sprintf("INSERT INTO test_supplier (TestID, SupplierID, CustomerID, CustomerContactID, CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES (%d, %d, %d, %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->TestID), mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->Customer->ID), mysql_real_escape_string($this->CustomerContact->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	public function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE test_supplier SET TestID=%d, SupplierID=%d, CustomerID=%d, CustomerContactID=%d, ModifiedOn=NOW(), ModifiedBy=%d WHERE TestSupplierID=%d", mysql_real_escape_string($this->TestID), mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->Customer->ID), mysql_real_escape_string($this->CustomerContact->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM test_supplier WHERE TestSupplierID=%d", mysql_real_escape_string($this->ID)));
		TestSupplierProduct::DeleteTestSupplier($this->ID);
	}
}
?>