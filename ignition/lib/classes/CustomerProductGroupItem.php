<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProductGroup.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class CustomerProductGroupItem {
	public $id;
	public $group;
	public $product;
	
	public function __construct($id = null) {
		$this->group = new CustomerProductGroup();
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

		$data = new DataQuery(sprintf("SELECT * FROM customer_product_group_item WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
			$this->group->id = $data->Row['groupId'];
			$this->product->ID = $data->Row['productId'];

            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
	
	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO customer_product_group_item (groupId, productId) VALUES (%d, %d)", mysql_real_escape_string($this->group->id), mysql_real_escape_string($this->product->ID)));

		$this->id = $data->InsertID;
	}
	
	public function update() {
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE customer_product_group_item SET groupId=%d, productId=%d WHERE id=%d", mysql_real_escape_string($this->group->id), mysql_real_escape_string($this->product->ID), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) { $this->id = $id; }
		if(!is_numeric($this->id)) return false;
		new DataQuery(sprintf("DELETE FROM customer_product_group_item WHERE id=%d", mysql_real_escape_string($this->id)));
	}

	static function DeleteCustomerProductGroup($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM customer_product_group_item WHERE groupId=%d", mysql_real_escape_string($id)));
	}
}