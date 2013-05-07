<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailProductPoolCategory.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailProductPoolProduct.php');

class EmailProductPool {
	var $ID;
	var $Name;

	function EmailProductPool($id=NULL) {
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

		$data = new DataQuery(sprintf("SELECT * FROM email_product_pool WHERE EmailProductPoolID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Name = $data->Row['Name'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO email_product_pool (Name) VALUES ('%s')", mysql_real_escape_string($this->Name)));

		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE email_product_pool SET Name='%s' WHERE EmailProductPoolID=%d", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM email_product_pool WHERE EmailProductPoolID=%d", mysql_real_escape_string($this->ID)));
		EmailProductPoolCategory::DeleteEmailProductPool($this->ID);
		EmailProductPoolProduct::DeleteEmailProductPool($this->ID);
		new DataQuery(sprintf("UPDATE email_date SET EmailProductPoolID=0 WHERE EmailProductPoolID=%d",mysql_real_escape_string( $this->ID)));
	}
}
?>