<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");

class ProFormaLine{
	var $ID;
	var $ProFormaID;
	var $Product;
	var $Quantity;
	var $Price;
	var $PriceRetail;
	var $HandlingCharge;
	var $IncludeDownloads;
	var $Discount;
	var $DiscountInformation;
	var $Tax;
	var $Total;
	var $Status;

	function ProFormaLine($id=NULL){
		$this->Product = new Product;
		$this->IncludeDownloads = 'N';
		
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Add(){
		$sql = sprintf("INSERT INTO proforma_line
							(ProForma_ID, Product_ID, Product_SKU, Product_Title,
							Quantity, Line_Status, Price, Price_Retail, Handling_Charge, IncludeDownloads, Line_Total, Line_Discount,
							Discount_Information, Line_Tax)
							VALUES ( %d, %d, '%s', '%s', %d, '%s', %f, %f, %f, '%s', %f, %f, '%s',
								%f)",
		mysql_real_escape_string($this->ProFormaID),
		mysql_real_escape_string($this->Product->ID),
		mysql_real_escape_string($this->Product->SKU),
		mysql_real_escape_string($this->Product->Name),
		mysql_real_escape_string($this->Quantity),
		mysql_real_escape_string($this->Status),
		mysql_real_escape_string($this->Price),
		mysql_real_escape_string($this->PriceRetail),
		mysql_real_escape_string($this->HandlingCharge),
		mysql_real_escape_string($this->IncludeDownloads),
		mysql_real_escape_string($this->Total),
		mysql_real_escape_string($this->Discount),
		mysql_real_escape_string($this->DiscountInformation),
		mysql_real_escape_string($this->Tax));
		
		$data = new DataQuery($sql);
		$this->ID = $data->InsertID;

		// TODO: move the update proforma total to the despatch object
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$sql = sprintf(	"UPDATE proforma_line SET
                            ProForma_ID=%d, Product_ID=%d, Product_SKU='%s',
							Product_Title='%s', Quantity=%d, Line_Status='%s',
							Price=%f, Price_Retail=%f, Handling_Charge=%f, IncludeDownloads='%s', Line_Total=%f, Line_Discount=%f,
							Discount_Information='%s', Line_Tax=%f
                            WHERE ProForma_Line_ID=%d",
		mysql_real_escape_string($this->ProFormaID),
		mysql_real_escape_string($this->Product->ID),
		mysql_real_escape_string($this->Product->SKU),
		mysql_real_escape_string($this->Product->Name),
		mysql_real_escape_string($this->Quantity),
		mysql_real_escape_string($this->Status),
		mysql_real_escape_string($this->Price),
		mysql_real_escape_string($this->PriceRetail),
		mysql_real_escape_string($this->HandlingCharge),
		mysql_real_escape_string($this->IncludeDownloads),
		mysql_real_escape_string($this->Total),
		mysql_real_escape_string($this->Discount),
		mysql_real_escape_string($this->DiscountInformation),
		mysql_real_escape_string($this->Tax),
		mysql_real_escape_string($this->ID));

		$data = new DataQuery($sql);
		return true;
	}

	function Get($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT * FROM proforma_line
											WHERE ProForma_Line_ID=%d",
		mysql_real_escape_string($this->ID)));

		if($data->TotalRows > 0){
			$this->ProFormaID = $data->Row['ProForma_ID'];
			$this->Product->ID = $data->Row['Product_ID'];
			$this->Product->Name = strip_tags($data->Row['Product_Title']);
			$this->Quantity = $data->Row['Quantity'];
			$this->Status = $data->Row['Line_Status'];
			$this->Price = $data->Row['Price'];
			$this->PriceRetail = $data->Row['Price_Retail'];
			$this->HandlingCharge = $data->Row['Handling_Charge'];
			$this->IncludeDownloads = $data->Row['IncludeDownloads'];
			$this->Total = $data->Row['Line_Total'];
			$this->Discount = $data->Row['Line_Discount'];
			$this->DiscountInformation = $data->Row['Discount_Information'];
			$this->Tax = $data->Row['Line_Tax'];
			$data->Disconnect();
			return true;
		} else {
			$data->Disconnect();
			return false;
		}
	}

	function GetViaProductID($id=NULL){
		if(!is_null($id)) $this->Product->ID = $id;
		$data = new DataQuery(sprintf("SELECT * FROM proforma_line
											WHERE Product_ID=%d
											AND ProForma_ID=%d",
		mysql_real_escape_string($this->Product->ID),
		mysql_real_escape_string($this->ProFormaID)));
		if($data->TotalRows > 0){
			$this->ProFormaID = $data->Row['ProForma_ID'];
			$this->Product->ID = $data->Row['Product_ID'];
			$this->Product->SKU = $data->Row['Product_SKU'];
			$this->Product->Name = strip_tags($data->Row['Product_Title']);
			$this->Quantity = $data->Row['Quantity'];
			$this->Status = $data->Row['Line_Status'];
			$this->Price = $data->Row['Price'];
			$this->PriceRetail = $data->Row['Price_Retail'];
			$this->HandlingCharge = $data->Row['Handling_Charge'];
			$this->IncludeDownloads = $data->Row['IncludeDownloads'];
			$this->Total = $data->Row['Line_Total'];
			$this->Discount = $data->Row['Line_Discount'];
			$this->DiscountInformation = $data->Row['Discount_Information'];
			$this->Tax = $data->Row['Line_Tax'];
			$data->Disconnect();
			return true;
		} else {
			$data->Disconnect();
			return false;
		}
	}

	function Exists(){
		$data = new DataQuery(sprintf("select ProForma_Line_ID, Quantity from proforma_line where ProForma_ID=%d and Product_ID=%d", $this->ProFormaID, $this->Product->ID));
		if($data->TotalRows > 0){
			$this->ID = $data->Row['ProForma_Line_ID'];
			$this->Quantity = $this->Quantity + $data->Row['Quantity'];
			$return = true;
		} else {
			$return = false;
		}
		$data->Disconnect();
		return $return;
	}

	function Delete($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("delete from proforma_line where ProForma_Line_ID=%d", mysql_real_escape_string($this->ID)));
		return true;
	}

	function IsSKUUnique($sku){
		$data = new DataQuery(sprintf(	"SELECT ProForma_Line_ID, Product_SKU
											FROM proforma_line
											WHERE ProForma_ID = %d
											AND Product_SKU = '%s'",
		mysql_real_escape_string($this->ProFormaID),
		mysql_real_escape_string($sku)));
		if($data->TotalRows == 0)
		return true;
		else {
			$this->ID = $data->Row['ProForma_Line_ID'];
			$this->Product->SKU = $data->Row['Product_SKU'];
			return false;
		}
	}
}