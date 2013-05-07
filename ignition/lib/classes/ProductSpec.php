<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductSpecValue.php");

class ProductSpec {
	var $ID;
	var $Value;
	var $Product;
	var $IsPrimary;

	function ProductSpec($id=NULL){
		$this->Product = new Product();
		$this->Value = new ProductSpecValue();
		$this->IsPrimary = 'N';

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

		$data = new DataQuery(sprintf("SELECT * FROM product_specification WHERE Specification_ID=%d", mysql_real_escape_string($this->ID)), $connection);
		if($data->TotalRows > 0) {
			$this->Value->ID = $data->Row['Value_ID'];
			$this->Product->ID = $data->Row['Product_ID'];
			$this->IsPrimary = $data->Row['Is_Primary'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add($connection = null){
		$check = new DataQuery(sprintf("SELECT * FROM product_specification WHERE Product_ID=%d AND Value_ID=%d", mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Value->ID)), $connection);
		if($check->TotalRows == 0){
			$data = new DataQuery(sprintf("INSERT INTO product_specification (Product_ID, Value_ID, Is_Primary) VALUES (%d, %d, '%s')", mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Value->ID), mysql_real_escape_string($this->IsPrimary)), $connection);

			$this->ID = $data->InsertID;
			
			$this->Product->Get();
			$this->Product->UpdateSpecCache($connection);

			$check->Disconnect();
			return true;
		}

		$check->Disconnect();
		return false;;
	}

	function Delete($id=NULL, $connection = null){
		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}

		if(empty($this->Value->ID)) {
			$this->Get();
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_specification WHERE Specification_ID=%d", mysql_real_escape_string($this->ID)), $connection);

		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM product_specification AS s WHERE s.Value_ID=%d", mysql_real_escape_string($this->Value->ID)), $connection);
		if($data->Row['Count'] == 0) {
			$this->Value->Delete($connection);

			$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM product_specification_value AS sv WHERE sv.Group_ID=%d", mysql_real_escape_string($this->Value->Group->ID)), $connection);
			if($data2->Row['Count'] == 0) {
				$this->Value->Group->Delete($connection);
			}
			$data2->Disconnect();
		}
		$data->Disconnect();
		
		$this->Product->Get();
		$this->Product->UpdateSpecCache($connection);
	}

	static function DeleteProductSpecValue($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM product_specification WHERE Value_ID=%d", mysql_real_escape_string($id)));
	}
}