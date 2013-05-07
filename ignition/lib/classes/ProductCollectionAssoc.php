<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class ProductCollectionAssoc {
	public $ID;
	public $ProductCollectionID;
	public $ProductID;

	function __construct($id=NULL){
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;	
		}

		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT * FROM product_collection_assoc WHERE ProductCollectionAssocID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->ProductCollectionID = $data->Row['ProductCollectionID'];
			$this->ProductID = $data->Row['ProductID'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO product_collection_assoc (ProductCollectionID, ProductID) VALUES (%d, %d)", mysql_real_escape_string($this->ProductCollectionID), mysql_real_escape_string($this->ProductID)));
		
		$this->ID = $data->InsertID;
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;	
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_collection_assoc WHERE ProductCollectionAssocID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeleteProductCollection($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM product_collection_assoc WHERE ProductCollectionID=%d", mysql_real_escape_string($id)));
	}
}