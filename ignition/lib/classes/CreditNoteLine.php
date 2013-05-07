<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

class CreditNoteLine{
	var $ID;
	var $CreditNoteID;
	var $Quantity;
	var $Description;
	var $Product;
	var $Price;
	var $TotalNet;
	var $TotalTax;
	var $Total;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	
	function CreditNoteLine($id=NULL){
		$this->Product = new Product;
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
		$sql = sprintf("select * from credit_note_line where Credit_Note_Line_ID=%d", mysql_real_escape_string($this->ID));
		$data = new DataQuery($sql);
		$this->CreditNoteID = $data->Row['Credit_Note_ID'];
		$this->Quantity = $data->Row['Quantity'];
		$this->Description = $data->Row['Line_Description'];
		$this->Product->ID = $data->Row['Product_ID'];
		$this->Price = $data->Row['Price'];
		$this->TotalNet = $data->Row['TotalNet'];
		$this->TotalTax = $data->Row['TotalTax'];
		$this->Total = $data->Row['Total'];
		$this->CreatedOn = $data->Row['Created_On'];
		$this->CreatedBy = $data->Row['Created_By'];
		$this->ModifiedOn = $data->Row['Modified_On'];
		$this->ModifiedBy = $data->Row['Modified_By'];
		$data->Disconnect();
	}
	
	function Add(){
		$sql = sprintf("insert into credit_note_line (Credit_Note_ID, Quantity, Line_Description, Product_ID, Price, TotalNet, TotalTax, Total, Created_On, Created_By, Modified_On, Modified_By) values (%d, %d, '%s', %d, %f, %f, %f, %f, Now(), %d, Now(), %d)",
				mysql_real_escape_string($this->CreditNoteID),
				mysql_real_escape_string($this->Quantity),
				mysql_real_escape_string($this->Description),
				mysql_real_escape_string($this->Product->ID),
				mysql_real_escape_string($this->Price),
				mysql_real_escape_string($this->TotalNet),
				mysql_real_escape_string($this->TotalTax),
				mysql_real_escape_string($this->Total),
				mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
				mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));
				
		$data = new DataQuery($sql);
		$this->ID = $data->InsertID;
	}
	
	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$sql = sprintf("update credit_note_line set Credit_Note_ID=%d, Quantity=%d, Line_Description='%s', Product_ID=%d, Price=%f, TotalNet=%f, TotalTax=%f, Total=%f, Modified_On=Now(), Modified_By=%d where Credit_Note_Line_ID=%d",
				mysql_real_escape_string($this->CreditNoteID),
				mysql_real_escape_string($this->Quantity),
				mysql_real_escape_string($this->Description),
				mysql_real_escape_string($this->Product->ID),
				mysql_real_escape_string($this->Price),
				mysql_real_escape_string($this->TotalNet),
				mysql_real_escape_string($this->TotalTax),
				mysql_real_escape_string($this->Total),
				mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
				mysql_real_escape_string($this->ID));
		$data = new DataQuery($sql);
	}
	
	function Delete($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		$sql = sprintf("delete from credit_note_line where Credit_Note_Line_ID=%d", mysql_real_escape_string($this->ID));
		$data = new DataQuery($sql);
	}
}	