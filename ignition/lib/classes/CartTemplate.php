<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class CartTemplate {
	var $ID;
	var $Title;
	var $Template;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function CartTemplate($id=NULL){
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

		$data = new DataQuery(sprintf("SELECT * FROM customer_basket_template 
			WHERE Customer_Basket_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->Row) {
			$this->Title = stripslashes($data->Row['Title']);
			$this->Template = stripslashes($data->Row['Template']);
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO customer_basket_template 
		(Title, Template, Created_On, Created_By, Modified_On, Modified_By) 
		VALUES ('%s', '%s', NOW(), %d, NOW(), %d)", 
		mysql_real_escape_string(stripslashes($this->Title)),
		mysql_real_escape_string(stripslashes($this->Template)),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("UPDATE customer_basket_template 
		SET Title='%s', Template='%s', Modified_On=NOW(), Modified_By=%d WHERE Customer_Basket_ID=%d", 
			mysql_real_escape_string(stripslashes($this->Title)),
			mysql_real_escape_string(stripslashes($this->Template)), 
			mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), 
			mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("DELETE FROM customer_basket_template WHERE Customer_Basket_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}
}
?>