<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProductLocation.php');

class CustomerProduct {
	var $ID;
	var $Customer;
	var $Product;
	
	public function __construct($id=NULL){
		$this->Customer = new Customer();
		$this->Product = new Product();

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

		$data = new DataQuery(sprintf("SELECT * FROM customer_product WHERE Customer_Product_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Customer->ID = $data->Row['Customer_ID'];
			$this->Product->ID = $data->Row['Product_ID'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function Add(){
		if(!$this->Exists()) {
			$data = new DataQuery(sprintf("INSERT INTO customer_product (Customer_ID, Product_ID) VALUES (%d, %d)", mysql_real_escape_string($this->Customer->ID), mysql_real_escape_string($this->Product->ID)));

			$this->ID = $data->InsertID;
			
			return true;
		}
		
		return false;
	}
	
	public function Delete($id=NULL){
		if(!is_null($id)) { $this->ID = $id; }
		if(!is_numeric($this->ID)) return false;
		new DataQuery(sprintf("DELETE FROM customer_product WHERE Customer_Product_ID=%d", mysql_real_escape_string($this->ID)));
		new DataQuery(sprintf("DELETE FROM customer_product_location WHERE CustomerProductID=%d", mysql_real_escape_string($this->ID)));
	}

	public function Exists() {
		$this->Customer->Get();
		$this->Customer->Contact->Get();
		
		$contacts = array();

		if($this->Customer->Contact->HasParent) {
			$data = new DataQuery(sprintf("SELECT Contact_ID FROM contact WHERE Parent_Contact_ID=%d", mysql_real_escape_string($this->Customer->Contact->Parent->ID)));
			while($data->Row) {
				$contacts[] = $data->Row['Contact_ID'];
				
				$data->Next();	
			}
			$data->Disconnect();
		} else {
			$contacts[] = $this->Customer->Contact->ID;
		}
		
		if(empty($contacts)) {
			return false;
		}

		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM customer_product AS cp INNER JOIN customer AS cu ON cu.Customer_ID=cp.Customer_ID WHERE cu.Contact_ID IN (%s) AND cp.Product_ID=%d", mysql_real_escape_string(implode(', ', $contacts)), mysql_real_escape_string($this->Product->ID)));
		$exists = ($data->Row['Count'] > 0) ? true : false;
		$data->Disconnect();

		return $exists;
	}

	static function DeleteContact($id){
		new DataQuery(sprintf("DELETE FROM customer_product WHERE Customer_ID=%d", mysql_real_escape_string($id)));
	}
}