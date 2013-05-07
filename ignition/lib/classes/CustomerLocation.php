<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProductLocation.php');

class CustomerLocation {
	var $ID;
	var $Customer;
	var $Name;

	public function __construct($id=NULL){
		$this->Customer = new Customer();

		if(!is_null($id)) {
			$this->Get($id);
		}
	}

	public function Get($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM customer_location WHERE CustomerLocationID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Customer->ID = $data->Row['Customer_ID'];
			$this->Name = $data->Row['Name'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function Add(){
		$data = new DataQuery(sprintf("INSERT INTO customer_location (CustomerID, Name) VALUES (%d, '%s')", mysql_real_escape_string($this->Customer->ID), mysql_real_escape_string($this->Name)));

		$this->ID = $data->InsertID;
	}

	public function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE customer_location SET Name='%s' WHERE CustomerLocationID=%d", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM customer_location WHERE CustomerLocationID=%s", mysql_real_escape_string($this->ID)));
		CustomerProductLocation::DeleteCustomerLocation($this->ID);
	}
}