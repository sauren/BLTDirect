<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Manufacturer.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");

class PurchaseLine{
	var $ID;
	var $Purchase;
	var $AdviceNote;
	var $Quantity;
	var $Product;
	var $Manufacturer;
	var $Cost;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $SuppliedBy;
	var $SKU;
	var $Location;
	var $QuantityDec;

	function PurchaseLine($id=NULL) {
		$this->Product = new Product();
		$this->Manufacturer = new Manufacturer();
		$this->QuantityDec = 0;

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

		$data = new DataQuery(sprintf("select * from purchase_line where Purchase_Line_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Purchase = $data->Row['Purchase_ID'];
			$this->AdviceNote = $data->Row['Advice_Note'];
			$this->Quantity = $data->Row['Quantity'];
			$this->Product->ID = $data->Row['Product_ID'];
			$this->Product->Name = $data->Row['Description'];
			$this->Manufacturer->ID = $data->Row['Manufacturer_ID'];
			$this->Cost = $data->Row['Cost'];
			$this->SKU = $data->Row['Supplier_SKU'];
			$this->Location = $data->Row['Shelf_Location'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedBy = $data->Row['Modified_On'];
			$this->ModifiedOn = $data->Row['Modified_By'];
			$this->SuppliedBy = $data->Row['Supplied_By'];
			$this->QuantityDec = $data->Row['Quantity_Decremental'];

			$data->Disconnect();
			return true;
		}
		
		$data->Disconnect();
		return false;
	}

	function Add(){
		$qtyCheck = $this->Exists();
		if(!$qtyCheck){

			if($this->SuppliedBy > 0) {
				$data2 = new DataQuery(sprintf("SELECT Supplier_SKU FROM supplier_product WHERE Product_ID=%d AND Supplier_ID=%d", mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->SuppliedBy)));
				if($data2->TotalRows > 0) {
					$this->SKU = $data2->Row['Supplier_SKU'];
				}
				$data2->Disconnect();
			}

			$data = new DataQuery(sprintf("insert into purchase_line (Purchase_ID, Advice_Note, Quantity, Description, Product_ID, Manufacturer_ID, Cost, Created_On, Created_By, Modified_On, Modified_By, Supplied_By,Supplier_SKU,Shelf_Location, Quantity_Decremental) values (%d, '%s', %d, '%s', %d, %d, %f, Now(), %d, Now(), %d, %d, '%s', '%s', %d)",
											mysql_real_escape_string($this->Purchase),
											mysql_real_escape_string($this->AdviceNote),
											mysql_real_escape_string($this->Quantity),
											mysql_real_escape_string(stripslashes($this->Product->Name)),
											mysql_real_escape_string($this->Product->ID),
											mysql_real_escape_string($this->Manufacturer->ID),
											mysql_real_escape_string($this->Cost),
											mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
											mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
											mysql_real_escape_string($this->SuppliedBy),
											mysql_real_escape_string(stripslashes($this->SKU)),
											mysql_real_escape_string(stripslashes($this->Location)),
											mysql_real_escape_string($this->QuantityDec)));

			$this->ID = $data->InsertID;
			return true;
		}else{
			$this->Quantity += $qtyCheck;
			$this->Update();
			return true;
		}
	}

	function Exists(){
		$data = new DataQuery(sprintf("select * from purchase_line where Purchase_ID=%d and Product_ID=%d",
										mysql_real_escape_string($this->Purchase),
										mysql_real_escape_string($this->Product->ID)));
		$data->Disconnect();
		if($data->TotalRows > 0){
			$this->ID = $data->Row['Purchase_Line_ID'];
			return $data->Row['Quantity'];
		} else {
			return false;
		}
	}

	function Update(){
		if($this->SuppliedBy > 0) {
			$data2 = new DataQuery(sprintf("SELECT Supplier_SKU FROM supplier_product WHERE Product_ID=%d AND Supplier_ID=%d", mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->SuppliedBy)));
			if($data2->TotalRows > 0) {
				$this->SKU = $data2->Row['Supplier_SKU'];
			}
			$data2->Disconnect();
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("update purchase_line set Purchase_ID=%d, Advice_Note='%s', Quantity=%d, Description='%s', Product_ID=%d, Manufacturer_ID=%d, Cost=%f, Modified_On=Now(), Modified_By=%d, Supplied_By=%d,Supplier_SKU='%s',Shelf_Location='%s', Quantity_Decremental=%d WHERE Purchase_Line_ID=%d",
										mysql_real_escape_string($this->Purchase),
										mysql_real_escape_string($this->AdviceNote),
										mysql_real_escape_string($this->Quantity),
										mysql_real_escape_string(stripslashes($this->Product->Name)),
										mysql_real_escape_string($this->Product->ID),
										mysql_real_escape_string($this->Manufacturer->ID),
										mysql_real_escape_string($this->Cost),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($this->SuppliedBy),
										mysql_real_escape_string(stripslashes($this->SKU)),
										mysql_real_escape_string(stripslashes($this->Location)),
										mysql_real_escape_string($this->QuantityDec),
										mysql_real_escape_string($this->ID)));
		return true;
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("delete from purchase_line where Purchase_Line_ID=%d", $this->ID));
	}

	static function DeletePurchaseId($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("delete from purchase_line where Purchase_ID=%d", $id));
	}
}