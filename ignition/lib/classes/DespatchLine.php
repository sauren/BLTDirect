<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");

class DespatchLine{
	var $ID;
	var $Despatch;
	var $Quantity;
	var $Product;
	var $IsComplementary;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function DespatchLine($id=NULL){
		$this->Product = new Product();
		$this->IsComplementary = 'N';

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
		$data = new DataQuery(sprintf("select * from despatch_line where Despatch_Line_ID=%d", mysql_real_escape_string($this->ID)));
		$this->Despatch = $data->Row['Despatch_ID'];
		$this->Quantity = $data->Row['Quantity'];
		$this->Product->Name = $data->Row['Description'];
		$this->Product->ID = $data->Row['Product_ID'];
		$this->IsComplementary = $data->Row['Is_Complementary'];
		$this->CreatedOn = $data->Row['Created_On'];
		$this->CreatedBy = $data->Row['Created_By'];
		$this->ModifiedBy = $data->Row['Modified_On'];
		$this->ModifiedOn = $data->Row['Modified_By'];
		$data->Disconnect();
		return true;
	}

	function Add(){
		$data = new DataQuery(sprintf("insert into despatch_line (Despatch_ID, Quantity, Description, Product_ID, Is_Complementary, Created_On, Created_By, Modified_On, Modified_By) values (%d, %d, '%s', %d, '%s', Now(), %d, Now(), %d)",
		mysql_real_escape_string($this->Despatch),
		mysql_real_escape_string($this->Quantity),
		mysql_real_escape_string(stripslashes($this->Product->Name)),
		mysql_real_escape_string($this->Product->ID),
		mysql_real_escape_string($this->IsComplementary),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("update despatch_line set Despatch_ID=%d, Quantity=%d, Description='%s', Product_ID=%d, Is_Complementary='%s', Modified_On=Now(), Modified_By=%d where Despatch_Line_ID=%d",
		mysql_real_escape_string($this->Despatch),
		mysql_real_escape_string($this->Quantity),
		mysql_real_escape_string(stripslashes($this->Product->Name)),
		mysql_real_escape_string($this->Product->ID),
		mysql_real_escape_string($this->IsComplementary),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->ID)));
		return true;
	}

	function Delete($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("delete from despatch_line where Despatch_Line_ID=%d", mysql_real_escape_string($this->ID)));
		return true;
	}
}