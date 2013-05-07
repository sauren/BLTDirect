<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class EmailProductPoolProduct {
	var $ID;
	var $EmailProductPoolID;
	var $ProductID;

	function EmailProductPoolProduct($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM email_product_pool_product WHERE EmailProductPoolProductID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->EmailProductPoolID = $data->Row['EmailProductPoolID'];
			$this->ProductID = $data->Row['ProductID'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO email_product_pool_product (EmailProductPoolID, ProductID) VALUES (%d, %d)", mysql_real_escape_string($this->EmailProductPoolID), mysql_real_escape_string($this->ProductID)));

		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE email_product_pool_product SET EmailProductPoolID=%d, ProductID=%d WHERE EmailProductPoolProductID=%d", mysql_real_escape_string($this->EmailProductPoolID), mysql_real_escape_string($this->ProductID), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM email_product_pool_product WHERE EmailProductPoolProductID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeleteProductPool($id){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM email_product_pool_product WHERE EmailProductPoolID=%d", mysql_real_escape_string($id)));
	}
}
?>