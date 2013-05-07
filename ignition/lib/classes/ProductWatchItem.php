<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

class ProductWatchItem {
	var $ID;
	var $WatchID;
	var $Product;

	function __construct($id = null) {
		$this->Product = new Product();

		if(!is_null($id)) {
			$this->Get($id);
		}
	}

	function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM product_watch_item WHERE ProductWatchItemID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->WatchID = $data->Row['ProductWatchID'];
			$this->Product->ID = $data->Row['ProductID'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO product_watch_item (ProductWatchID, ProductID) VALUES (%d, %d)", mysql_real_escape_string($this->WatchID), mysql_real_escape_string($this->Product->ID)));

		$this->ID = $data->InsertID;
	}

	function Delete($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_watch_item WHERE ProductWatchItemID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeleteProductWatch($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM product_watch_item WHERE ProductWatchID=%d", mysql_real_escape_string($id)));
	}
}