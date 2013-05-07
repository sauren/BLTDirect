<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class ProductSpecGroupCategory {
	var $ID;
	var $GroupID;
	var $CategoryID;

	function ProductSpecGroupCategory($id=NULL){
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL, $connection = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM product_specification_group_category WHERE Group_Category_ID=%d", mysql_real_escape_string($this->ID)), $connection);
		if($data->TotalRows > 0) {
			$this->GroupID = $data->Row['Group_ID'];
			$this->CategoryID = $data->Row['Category_ID'];
			
			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add($connection = null){
		$data = new DataQuery(sprintf("INSERT INTO product_specification_group_category (Group_ID, Category_ID) VALUES (%d, %d)", mysql_real_escape_string($this->GroupID), mysql_real_escape_string($this->CategoryID)), $connection);
		
		$this->ID = $data->InsertID;
	}
	
	function Delete($id=NULL, $connection = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		
		new DataQuery(sprintf("DELETE FROM product_specification_group_category WHERE Group_Category_ID=%d", mysql_real_escape_string($this->ID)), $connection);
	}
}