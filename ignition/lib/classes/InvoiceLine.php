<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");

class InvoiceLine{
	var $ID;
	var $InvoiceID;
	var $Description;
	var $Quantity;
	var $Product;
	var $Price;
	var $Total;
	var $Discount;
	var $DiscountInformation;
	var $Tax;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function InvoiceLine($id=NULL){
		$this->Product = new Product;
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Add(){
		$data = new DataQuery(sprintf("insert into invoice_line (
										Invoice_ID,
										Description,
										Quantity,
										Product_ID,
										Price,
										Line_Total,
										Line_Discount,
										Discount_Information,
										Line_Tax,
										Created_On,
										Created_By,
										Modified_On,
										Modified_By
										) values (%d, '%s', %d, %d, %f, %f, %f, '%s', %f, Now(), %d, Now(), %d)",
										mysql_real_escape_string($this->InvoiceID),
										mysql_real_escape_string(stripslashes($this->Description)),
										mysql_real_escape_string($this->Quantity),
										mysql_real_escape_string($this->Product->ID),
										mysql_real_escape_string($this->Price),
										mysql_real_escape_string($this->Total),
										mysql_real_escape_string($this->Discount),
										mysql_real_escape_string(stripslashes($this->DiscountInformation)),
										mysql_real_escape_string($this->Tax),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		return true;
	}

	function Update(){

		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("UPDATE invoice_line SET
										Invoice_ID=%d,
										Description='%s',
										Quantity=%d,
										Product_ID=%d,
										Price=%f,
										Line_Total=%f,
										Line_Discount=%f,
										Discount_Information='%s',
										Line_Tax=%f,
										Modified_On=Now(),
										Modified_By =%d
										WHERE Invoice_Line_ID=%d",
										mysql_real_escape_string($this->InvoiceID),
										mysql_real_escape_string(stripslashes($this->Description)),
										mysql_real_escape_string($this->Quantity),
										mysql_real_escape_string($this->Product->ID),
										mysql_real_escape_string($this->Price),
										mysql_real_escape_string($this->Total),
										mysql_real_escape_string($this->Discount),
										mysql_real_escape_string(stripslashes($this->DiscountInformation)),
										mysql_real_escape_string($this->Tax),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($this->ID)));
	}
	function Get($id=NULL){
		if(!is_null($id)) $this->ID = $id;

		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT * FROM invoice_line
										WHERE Invoice_Line_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0){
			$this->InvoiceID = $data->Row['Invoice_ID'];
			$this->Description = $data->Row['Description'];
			$this->Quantity = $data->Row['Quantity'];
			$this->Product->ID = $data->Row['Product_ID'];
			$this->Price = $data->Row['Price'];
			$this->Total = $data->Row['Line_Total'];
			$this->Discount = $data->Row['Line_Discount'];
			$this->DiscountInformation = $data->Row['Discount_Information'];
			$this->Tax = $data->Row['Line_Tax'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$data->Disconnect();
			return true;
		} else {
			$data->Disconnect();
			return false;
		}
	}

	function GetViaProductID($id=NULL){
		if(!is_null($id)) $this->ID = $id;

		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT * FROM invoice_line
										WHERE Product_ID=%d",
										mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0){
			$this->InvoiceID = $data->Row['Invoice_ID'];
			$this->Description = $data->Row['Description'];
			$this->Quantity = $data->Row['Quantity'];
			$this->Product->ID = $data->Row['Product_ID'];
			$this->Price = $data->Row['Price'];
			$this->Total = $data->Row['Line_Total'];
			$this->Discount = $data->Row['Line_Discount'];
			$this->DiscountInformation = $data->Row['Discount_Information'];
			$this->Tax = $data->Row['Line_Tax'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$data->Disconnect();
			return true;
		} else {
			$data->Disconnect();
			return false;
		}
	}

	function GetIDViaOrderAndSKU(){
		$data = new DataQuery(sprintf(	"SELECT Invoice_Line_ID
										FROM invoice_line AS i
										INNER JOIN Product AS p
										ON i.Product_ID = p.Product_ID
										WHERE i.Invoice_ID=%d
										AND p.SKU='%s'",
										mysql_real_escape_string($this->InvoiceID), mysql_real_escape_string($this->Product->SKU)));
		$data->Disconnect();
		if($data->TotalRows > 0)
			$this->ID = $data->Row['Invoice_Line_ID'];
	}

	function Delete($id=NULL){
		if(!is_null($id)) $this->ID = $id;

		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("delete from invoice_line where Invoice_Line_ID=%d", mysql_real_escape_string($this->ID)));
		return true;
	}
	function IsSKUUnique($sku){
		$data = new DataQuery(sprintf("SELECT il.Invoice_Line_ID, p.SKU
										FROM invoice_line AS il
										INNER JOIN Product AS p
										ON il.Product_ID = p.Product_ID
										WHERE il.Invoice_ID = %d
										AND p.SKU = '%s'",
										mysql_real_escape_string($this->InvoiceID),
										mysql_real_escape_string($sku)));
		if($data->TotalRows == 0)
			return true;
		else
			$this->ID = $data->Row['Invoice_Line_ID'];
			return false;
	}
}