<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

class ReturnAuto {
	public $id;
	public $product;
	public $order;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;
	
	public function __construct($id = null) {
		$this->product = new Product();
		$this->order = new Order();

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

		$data = new DataQuery(sprintf("SELECT * FROM return_auto WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
			$this->product->ID = $data->Row['productId'];
			$this->order->ID = $data->Row['orderId'];

            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO return_auto (productId, orderId, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->product->ID), mysql_real_escape_string($this->order->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
	}
	
	public function update() {
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE return_auto SET productId=%d, orderId=%d, modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->product->ID), mysql_real_escape_string($this->order->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM return_auto WHERE id=%d", mysql_real_escape_string($this->id)));
	}

	public function convert() {
		$order = new Order();

		if($order->Get($this->order->ID)) {
			$order->IsNotReceived = 'N';
			$order->IsCustomShipping = 'Y';
			$order->TotalShipping = 0;
			$order->OrderedOn = date('Y-m-d H:i:s');
			$order->CustomID = '';
			$order->Status = 'Unread';
			$order->Prefix = 'N';
			$order->Referrer = '';
			$order->PaymentMethod->GetByReference('foc');
			$order->ParentID = $order->ID;
			$order->Add();

			$line = new OrderLine();
			$line->Order = $order->ID;
			$line->Product->Get($this->product->ID);
			$line->Quantity = 1;
			$line->FreeOfCharge = 'Y';
			$line->Add();

			$order->GetLines();
			$order->Recalculate();

			$this->delete();

			return $order->ID;
		}

		return null;
	}
}