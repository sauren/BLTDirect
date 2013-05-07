<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class ProductCart {
	var $ID;
	var $ProductID;

	function ProductCart($id=NULL){
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("select * from product_cart where Product_Cart_ID=%d", mysql_real_escape_string($this->ID)));

		if($data->TotalRows > 0) {
			$this->ProductID = $data->Row['Product_ID'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Delete($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("delete from product_cart where Product_Cart_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();

		return true;
	}

	function Add(){
		$data = new DataQuery(sprintf("insert into product_cart (Product_ID) values (%d)", mysql_real_escape_string($this->ProductID)));
		$data->Disconnect();

		return true;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("update product_cart set
										Product_ID=%d
										where Product_Cart_ID=%d", mysql_real_escape_string($this->ProductID), mysql_real_escape_string($this->ID)));
		$this->ID = $data->InsertID;
		return true;
	}
}
?>