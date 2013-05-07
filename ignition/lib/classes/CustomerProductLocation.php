<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProductGroup.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerLocation.php');

class CustomerProductLocation {
	var $ID;
	var $Product;
	var $Location;
	var $Group;
	var $Quantity;
	
	public function __construct($id=NULL){
		$this->Product = new CustomerProduct();
		$this->Location = new CustomerLocation();
		$this->Group = new CustomerProductGroup();

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

		$data = new DataQuery(sprintf("SELECT * FROM customer_product_location WHERE CustomerProductLocationID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Product->ID = $data->Row['CustomerProductID'];
			$this->Location->ID = $data->Row['CustomerLocationID'];
			$this->Group->ID = $data->Row['CustomerProductGroupID'];
			$this->Quantity = $data->Row['Quantity'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function Add(){
		$data = new DataQuery(sprintf("INSERT INTO customer_product_location (CustomerProductID, CustomerLocationID, CustomerProductGroupID, Quantity) VALUES (%d, %d, %d, %d)", mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Location->ID), mysql_real_escape_string($this->Group->ID), mysql_real_escape_string($this->Quantity)));

		$this->ID = $data->InsertID;
	}

	public function Delete($id = null) {
		if(!is_null($id)) { $this->ID = $id; }
		if(!is_numeric($this->ID)) return false;
		new DataQuery(sprintf("DELETE FROM customer_product_location WHERE CustomerProductLocationID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeleteCustomerLocation($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM customer_product_location WHERE CustomerLocationID=%s", mysql_real_escape_string($id)));
	}

	static function DeleteCustomerProduct($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM customer_product_location WHERE CustomerProductID=%s", mysql_real_escape_string($id)));
	}
}