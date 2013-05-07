<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductWatchItem.php');

class ProductWatch {
	var $ID;
	var $Name;

	function __construct($id = null) {
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

		$data = new DataQuery(sprintf("SELECT * FROM product_watch WHERE ProductWatchID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0){
			$this->Name = $data->Row['Name'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO product_watch (Name) VALUES ('%s')", mysql_real_escape_string($this->Name)));

		$this->ID = $data->InsertID;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE product_watch SET Name='%s' WHERE ProductWatchID=%d", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_watch WHERE ProductWatchID=%d", mysql_real_escape_string($this->ID)));
		ProductWatchItem::DeleteProductWatch($this->ID);
	}
}