<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

class ContactProductTrade {
	
	public static function getPrice($contactId, $productId) {
		$data = new DataQuery(sprintf("SELECT price FROM contact_product_trade WHERE contactId=%d AND productId=%d", mysql_real_escape_string($contactId), mysql_real_escape_string($productId)));
		if($data->TotalRows > 0) {
			$price = $data->Row['price'];
			
			$data->Disconnect();
			return $price;
		}
		
		$data->Disconnect();
		return 0;
	}
	
	public $id;
	public $contact;
	public $product;
	public $price;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;
	
	public function __construct($id = null) {
		$this->contact = new Contact();
		$this->product = new Product();

		if(!is_null($id)) {
			$this->get($id);
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}


		if(!is_numeric($this->id)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM contact_product_trade WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
			$this->contact->ID = $data->Row['contactId'];
			$this->product->ID = $data->Row['productId'];

            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
	
	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO contact_product_trade (contactId, productId, price, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, %d, %f, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->contact->ID), mysql_real_escape_string($this->product->ID), mysql_real_escape_string($this->price), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
	}
	
	public function update() {


		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE contact_product_trade SET contactId=%d, productId=%d, price=%f, modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->contact->ID), mysql_real_escape_string($this->product->ID), mysql_real_escape_string($this->price), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}


		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM contact_product_trade WHERE id=%d", mysql_real_escape_string($this->id)));
	}
}