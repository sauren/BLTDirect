<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProductGroupItem.php');

class CustomerProductGroup {
	public $id;
	public $customer;
	public $name;
	
	public function __construct($id = null) {
		$this->customer = new Customer();

		if(!is_null($id)) {
			$this->get($id);
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		$data = new DataQuery(sprintf("SELECT * FROM customer_product_group WHERE id=%d", $this->id));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
			$this->customer->ID = $data->Row['customerId'];

            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
	
	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO customer_product_group (customerId, name) VALUES (%d, '%s')", $this->customer->ID, $this->name));

		$this->id = $data->InsertID;
	}
	
	public function update() {
		new DataQuery(sprintf("UPDATE customer_product_group SET customerId=%d, name='%s' WHERE id=%d", $this->customer->ID, $this->name, $this->id));
	}

	public function delete($id = null) {
		if(!is_null($id)) { $this->id = $id; }
		if(!is_numeric($this->id)) return false;
		new DataQuery(sprintf("DELETE FROM customer_product_group WHERE id=%d", $this->id));
		CustomerProductGroupItem::DeleteCustomerProductGroup($this->id);
	}
}