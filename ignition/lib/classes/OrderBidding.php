<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Supplier.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");

class OrderBidding {
	public $ID;
	public $OrderID;
	public $Supplier;
	public $Product;
	public $CostOriginal;
	public $CostBid;
	public $IsAccepted;
	public $CreatedOn;
	public $CreatedBy;
    public $ModifiedOn;
	public $ModifiedBy;

	public function __construct($id = null) {
		$this->Supplier = new Supplier();
		$this->Product = new Product();
		$this->IsAccepted = 'N';

		if(!is_null($id)) {
			$this->Get($id);
		}
	}

	public function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM order_bidding WHERE OrderBiddingID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->OrderID = $data->Row['OrderID'];
			$this->Supplier->ID = $data->Row['SupplierID'];
			$this->Product->ID = $data->Row['ProductID'];
			$this->CostOriginal = $data->Row['CostOriginal'];
			$this->CostBid = $data->Row['CostBid'];
			$this->IsAccepted = $data->Row['IsAccepted'];
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
		$data = new DataQuery(sprintf("INSERT INTO order_bidding (OrderID, SupplierID, ProductID, CostOriginal, CostBid, IsAccepted, CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES (%d, %d, %d, %f, %f, '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->OrderID), mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->CostOriginal), mysql_real_escape_string($this->CostBid), mysql_real_escape_string($this->IsAccepted), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

    public function Update() {
    	if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE order_bidding SET OrderID=%d, SupplierID=%d, ProductID=%d, CostOriginal=%f, CostBid=%f, IsAccepted='%s', ModifiedOn=NOW(), ModifiedBy=%d WHERE OrderBiddingID=%d", mysql_real_escape_string($this->OrderID), mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->CostOriginal), mysql_real_escape_string($this->CostBid), mysql_real_escape_string($this->IsAccepted), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM order_bidding WHERE OrderBiddingID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeleteOrder($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("delete from order_bidding where OrderID = %d", mysql_real_escape_string($id)));
	}
}