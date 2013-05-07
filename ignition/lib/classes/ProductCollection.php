<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductCollectionAssoc.php');

class ProductCollection {
	public $ID;
	public $Name;
	public $CreatedOn;
	public $CreatedBy;
	public $ModifiedOn;
	public $ModifiedBy;

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
		
		$data = new DataQuery(sprintf("SELECT * FROM product_collection WHERE ProductCollectionID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Name = $data->Row['Name'];
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

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO product_collection (Name, CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES ('%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Name), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		
		$this->ID = $data->InsertID;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		 new DataQuery(sprintf("UPDATE product_collection SET Name='%s', ModifiedOn=NOW(), ModifiedBy=%d WHERE ProductCollectionID=%d", mysql_real_escape_string($this->Name), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}
	
	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;	
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_collection WHERE ProductCollectionID=%d", mysql_real_escape_string($this->ID)));
		ProductCollectionAssoc::DeleteProductCollection($this->ID);
	}
}