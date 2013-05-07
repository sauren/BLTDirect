<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class EmailProductPoolCategory {
	var $ID;
	var $EmailProductPoolID;
	var $CategoryID;

	function EmailProductPoolCategory($id=NULL) {
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

		$data = new DataQuery(sprintf("SELECT * FROM email_product_pool_category WHERE EmailProductPoolCategoryID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->EmailProductPoolID = $data->Row['EmailProductPoolID'];
			$this->CategoryID = $data->Row['CategoryID'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO email_product_pool_category (EmailProductPoolID, CategoryID) VALUES (%d, %d)", mysql_real_escape_string($this->EmailProductPoolID), mysql_real_escape_string($this->CategoryID)));

		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE email_product_pool_category SET EmailProductPoolID=%d, CategoryID=%d WHERE EmailProductPoolCategoryID=%d", mysql_real_escape_string($this->EmailProductPoolID), mysql_real_escape_string($this->CategoryID, $this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM email_product_pool_category WHERE EmailProductPoolCategoryID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeleteEmailProductPool($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM email_product_pool_category WHERE EmailProductPoolID=%d", mysql_real_escape_string($id)));
	}
}
?>