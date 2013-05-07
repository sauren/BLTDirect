<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Warehouse.php");

class OrderLine {
	var $ID;
	var $Order;
	var $IsAssociative;
	var $Product;
	var $OriginalProduct;
	var $Quantity;
	var $QuantityNotReceived;
	var $Price;
	var $PriceRetail;
	var $Cost;
	var $CostBest;
	var $Discount;
	var $DiscountInformation;
	var $Tax;
	var $Total;
	var $Status;
	var $DespatchID;
	var $InvoiceID;
	var $DespatchedFrom;
	var $IsWarehouseFixed;
	var $FreeOfCharge;
	var $AssociativeProductTitle;
	var $BackorderExpectedOn;
	var $HandlingCharge;
	var $IncludeDownloads;
	var $IsComplementary;

	public function __construct($id=NULL) {
		$this->FreeOfCharge = 'N';
		$this->IsAssociative = 'N';
		$this->Product = new Product();
		$this->OriginalProduct = new Product();
		$this->DespatchedFrom = new Warehouse();
		$this->IsWarehouseFixed = 'N';
		$this->BackorderExpectedOn = '0000-00-00 00:00:00';
		$this->IncludeDownloads = 'N';
		$this->IsComplementary = 'N';

		if(!is_null($id)){
			$this->Get($id);
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT ol.*, IF(ol.Product_ID>0, p.Product_Title, ol.Product_Title) AS Product_Title, p.Weight, p.Shipping_Class_ID, p.Is_Dangerous FROM order_line AS ol LEFT JOIN product AS p ON p.Product_ID=ol.Product_ID WHERE ol.Order_Line_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0){
			$this->Order = $data->Row['Order_ID'];
			$this->IsAssociative = $data->Row['Is_Associative'];
			$this->Product->ID = $data->Row['Product_ID'];
			$this->Product->Name = strip_tags($data->Row['Product_Title']);
			$this->Product->Weight = $data->Row['Weight'];
			$this->Product->ShippingClass->ID = $data->Row['Shipping_Class_ID'];
			$this->Product->IsDangerous = $data->Row['Is_Dangerous'];
			$this->OriginalProduct->ID = $data->Row['Original_Product_ID'];
			$this->Quantity = $data->Row['Quantity'];
			$this->QuantityNotReceived = $data->Row['Quantity_Not_Received'];
			$this->Status = $data->Row['Line_Status'];
			$this->Price = $data->Row['Price'];
			$this->PriceRetail = $data->Row['Price_Retail'];
			$this->Cost = $data->Row['Cost'];
			$this->CostBest = $data->Row['Cost_Best'];
			$this->Total = $data->Row['Line_Total'];
			$this->Discount = $data->Row['Line_Discount'];
			$this->DiscountInformation = $data->Row['Discount_Information'];
			$this->Tax = $data->Row['Line_Tax'];
			$this->DespatchID = $data->Row['Despatch_ID'];
			$this->DespatchedFrom->Get($data->Row['Despatch_From_ID']);
			$this->IsWarehouseFixed = $data->Row['Is_Warehouse_Fixed'];
			$this->InvoiceID = $data->Row['Invoice_ID'];
			$this->FreeOfCharge = $data->Row['Free_Of_Charge'];
			$this->AssociativeProductTitle = $data->Row['Associative_Product_Title'];
			$this->BackorderExpectedOn = $data->Row['Backorder_Expected_On'];
			$this->HandlingCharge = $data->Row['Handling_Charge'];
			$this->IncludeDownloads = $data->Row['IncludeDownloads'];
			$this->IsComplementary = $data->Row['Is_Complementary'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function GetViaProductID($id=NULL){
		if(!is_null($id)) {
			$this->Product->ID = $id;
		}

		$data = new DataQuery(sprintf("SELECT Order_Line_ID FROM order_line WHERE Product_ID=%d AND Order_ID=%d", mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Order)));
		if($data->TotalRows > 0){
			$return = $this->Get($data->Row['Order_Line_ID']);

			$data->Disconnect();
			return $return;
		}

		$data->Disconnect();
		return false;
	}

	function GetIDViaOrderAndSKU(){
		$data = new DataQuery(sprintf("SELECT Order_Line_ID FROM order_line WHERE Order_ID=%d AND Product_SKU='%s'", mysql_real_escape_string($this->Order), mysql_real_escape_string($this->Product->SKU)));
		if($data->TotalRows > 0) {
			$this->ID = $data->Row['Order_Line_ID'];
		}
		$data->Disconnect();
	}

	function Add(){
		$sql = sprintf("INSERT INTO order_line
							(Order_ID, Is_Associative, Product_ID, Original_Product_ID, Product_SKU, Product_Title,
							Quantity, Quantity_Not_Received, Line_Status, Price, Price_Retail, Cost, Cost_Best, Line_Total, Line_Discount,
							Discount_Information, Line_Tax, Despatch_ID,
							Despatch_From_ID, Is_Warehouse_Fixed, Invoice_ID, Free_Of_Charge, Associative_Product_Title,
							Backorder_Expected_On, Handling_Charge, IncludeDownloads, Is_Complementary)
							VALUES (%d, '%s', %d, %d, '%s', '%s', %d, %d, '%s', %f, %f, %f, %f, %f, %f, '%s', %f, %d, %d, '%s', %d, '%s', '%s', '%s', %f, '%s', '%s')",
		mysql_real_escape_string($this->Order),
		mysql_real_escape_string($this->IsAssociative),
		mysql_real_escape_string($this->Product->ID),
		mysql_real_escape_string($this->OriginalProduct->ID),
		mysql_real_escape_string(stripslashes($this->Product->SKU)),
		mysql_real_escape_string(stripslashes($this->Product->Name)),
		mysql_real_escape_string($this->Quantity),
		mysql_real_escape_string($this->QuantityNotReceived),
		mysql_real_escape_string(stripslashes($this->Status)),
		mysql_real_escape_string($this->Price),
		mysql_real_escape_string($this->PriceRetail),
		mysql_real_escape_string($this->Cost),
		mysql_real_escape_string($this->CostBest),
		mysql_real_escape_string($this->Total),
		mysql_real_escape_string($this->Discount),
		mysql_real_escape_string(stripslashes($this->DiscountInformation)),
		mysql_real_escape_string($this->Tax),
		mysql_real_escape_string($this->DespatchID),
		mysql_real_escape_string($this->DespatchedFrom->ID),
		mysql_real_escape_string($this->IsWarehouseFixed),
		mysql_real_escape_string($this->InvoiceID),
		mysql_real_escape_string($this->FreeOfCharge),
		mysql_real_escape_string(stripslashes($this->AssociativeProductTitle)),
		mysql_real_escape_string($this->BackorderExpectedOn),
		mysql_real_escape_string($this->HandlingCharge),
		mysql_real_escape_string($this->IncludeDownloads),
		mysql_real_escape_string($this->IsComplementary));

		$data = new DataQuery($sql);
		$this->ID = $data->InsertID;
	}

	function Update(){
		if($this->DespatchID != 0 && $this->DespatchedFrom->ID != 0){
			$sql = sprintf("UPDATE order_line SET
							Order_ID=%d, Is_Associative='%s', Product_ID=%d, Original_Product_ID=%d, Product_SKU='%s',
							Product_Title='%s',
							Quantity=%d, Quantity_Not_Received=%d, Line_Status='%s', Price=%f, Price_Retail=%f, Cost=%f, Cost_Best=%f, Line_Total=%f,
							Line_Discount=%f, Discount_Information='%s', Line_Tax=%f,
							Despatch_ID=%d, Is_Warehouse_Fixed='%s', Invoice_ID=%d, Free_Of_Charge='%s', Associative_Product_Title='%s',
							Backorder_Expected_On='%s', Handling_Charge=%f, IncludeDownloads='%s', Is_Complementary='%s'
							WHERE Order_Line_ID=%d",
			mysql_real_escape_string($this->Order),
			mysql_real_escape_string($this->IsAssociative),
			mysql_real_escape_string($this->Product->ID),
			mysql_real_escape_string($this->OriginalProduct->ID),
			mysql_real_escape_string(stripslashes($this->Product->SKU)),
			mysql_real_escape_string(stripslashes($this->Product->Name)),
			mysql_real_escape_string($this->Quantity),
			mysql_real_escape_string($this->QuantityNotReceived),
			mysql_real_escape_string(stripslashes($this->Status)),
			mysql_real_escape_string($this->Price),
			mysql_real_escape_string($this->PriceRetail),
			mysql_real_escape_string($this->Cost),
			mysql_real_escape_string($this->CostBest),
			mysql_real_escape_string($this->Total),
			mysql_real_escape_string($this->Discount),
			mysql_real_escape_string(stripslashes($this->DiscountInformation)),
			mysql_real_escape_string($this->Tax),
			mysql_real_escape_string($this->DespatchID),
			mysql_real_escape_string($this->IsWarehouseFixed),
			mysql_real_escape_string($this->InvoiceID),
			mysql_real_escape_string($this->FreeOfCharge),
			mysql_real_escape_string(stripslashes($this->AssociativeProductTitle)),
			mysql_real_escape_string($this->BackorderExpectedOn),
			mysql_real_escape_string($this->HandlingCharge),
			mysql_real_escape_string($this->IncludeDownloads),
			mysql_real_escape_string($this->IsComplementary),
			mysql_real_escape_string($this->ID));
		} else {
			if($this->DespatchedFrom->ID == 0){
				$branchFinder = new DataQuery("SELECT * FROM warehouse w INNER JOIN branch b ON w.Type_Reference_ID = b.Branch_ID WHERE b.Is_HQ = 'Y' AND Type='B'");
				$this->DespatchedFrom->ID = $branchFinder->Row['Warehouse_ID'];
				$branchFinder->Disconnect();
			}
			$sql = sprintf("UPDATE order_line SET
							Order_ID=%d, Is_Associative='%s', Product_ID=%d, Original_Product_ID=%d, Product_SKU='%s',
							Product_Title='%s',
							Quantity=%d, Quantity_not_Received=%d, Line_Status='%s', Price=%f, Price_Retail=%f, Cost=%f, Cost_Best=%f, Line_Total=%f,
							Line_Discount=%f, Discount_Information='%s', Line_Tax=%f,
							Despatch_ID=%d, Despatch_From_ID=%d, Is_Warehouse_Fixed='%s', Invoice_ID=%d, Free_Of_Charge='%s', Associative_Product_Title='%s',
							Backorder_Expected_On='%s', Handling_Charge=%f, IncludeDownloads='%s', Is_Complementary='%s'
							WHERE Order_Line_ID=%d",
			mysql_real_escape_string($this->Order),
			mysql_real_escape_string($this->IsAssociative),
			mysql_real_escape_string($this->Product->ID),
			mysql_real_escape_string($this->OriginalProduct->ID),
			mysql_real_escape_string(stripslashes($this->Product->SKU)),
			mysql_real_escape_string(stripslashes($this->Product->Name)),
			mysql_real_escape_string($this->Quantity),
			mysql_real_escape_string($this->QuantityNotReceived),
			mysql_real_escape_string(stripslashes($this->Status)),
			mysql_real_escape_string($this->Price),
			mysql_real_escape_string($this->PriceRetail),
			mysql_real_escape_string($this->Cost),
			mysql_real_escape_string($this->CostBest),
			mysql_real_escape_string($this->Total),
			mysql_real_escape_string($this->Discount),
			mysql_real_escape_string(stripslashes($this->DiscountInformation)),
			mysql_real_escape_string($this->Tax),
			mysql_real_escape_string($this->DespatchID),
			mysql_real_escape_string($this->DespatchedFrom->ID),
			mysql_real_escape_string($this->IsWarehouseFixed),
			mysql_real_escape_string($this->InvoiceID),
			mysql_real_escape_string($this->FreeOfCharge),
			mysql_real_escape_string(stripslashes($this->AssociativeProductTitle)),
			mysql_real_escape_string($this->BackorderExpectedOn),
			mysql_real_escape_string($this->HandlingCharge),
			mysql_real_escape_string($this->IncludeDownloads),
			mysql_real_escape_string($this->IsComplementary),
			mysql_real_escape_string($this->ID));
		}

		new DataQuery($sql);

		return true;
	}

	function Exists(){
		$data = new DataQuery(sprintf("SELECT Order_Line_ID, Quantity FROM order_line WHERE Order_ID=%d AND Product_ID=%d", mysql_real_escape_string($this->Order), mysql_real_escape_string($this->Product->ID)));
		if($data->TotalRows > 0){
			$this->ID = $data->Row['Order_Line_ID'];
			$this->Quantity = $this->Quantity + $data->Row['Quantity'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE from order_line WHERE Order_Line_ID=%d", mysql_real_escape_string($this->ID)));
	}
	
	static function DeleteOrder($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("delete from order_line where Order_ID=%d",mysql_real_escape_string($id)));
	}

	function IsSKUUnique($sku){
		$data = new DataQuery(sprintf("SELECT Order_Line_ID, Product_SKU FROM order_line WHERE Order_ID=%d AND Product_SKU='%s'", mysql_real_escape_string($this->Order), mysql_real_escape_string($sku)));
		if($data->TotalRows > 0) {
			$this->ID = $data->Row['Order_Line_ID'];
			$this->Product->SKU = $data->Row['Product_SKU'];

			$data->Disconnect();
			return false;
		}

		$data->Disconnect();
		return true;
	}
}