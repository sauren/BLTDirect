<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProductPrice.php');

class SupplierProduct {
	var $ID;
	var $Supplier;
	var $Product;
	var $SupplierProductNumber;
	var $Cost;
	var $Reason;
	var $LeadDays;
	var $IsUnavailable;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $PreferredSup;
	var $SKU;
	var $IsSupplied;
	var $IsStockHeld;

	function __construct($id = NULL, $connection = null) {
		$this->Supplier = new Supplier();
		$this->Product = new Product();
		$this->PreferredSup = 'N';
		$this->IsSupplied = 'Y';
		$this->IsStockHeld = 'N';
		$this->IsUnavailable = 'N';

		if(!is_null($id)) {
			$this->Get($id, $connection);
		}
	}

	function Get($id = NULL, $connection = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM supplier_product WHERE Supplier_Product_ID=%d ", mysql_real_escape_string($this->ID)), $connection);
		if ($data->TotalRows > 0) {
			$this->Supplier->ID = $data->Row["Supplier_ID"];
			$this->Product->ID = $data->Row["Product_ID"];
			$this->SupplierProductNumber = $data->Row["Supplier_Product_Number"];
			$this->Cost = $data->Row["Cost"];
			$this->LeadDays = $data->Row["Lead_Days"];
			$this->IsUnavailable = $data->Row["IsUnavailable"];
			$this->CreatedOn = $data->Row["Created_On"];
			$this->CreatedBy = $data->Row["Created_By"];
			$this->ModifiedOn = $data->Row["Modified_On"];
			$this->ModifiedBy = $data->Row["Modified_By"];
			$this->PreferredSup = $data->Row["Preferred_Supplier"];
			$this->SKU = $data->Row['Supplier_SKU'];
			$this->IsSupplied = $data->Row['Is_Supplied'];
			$this->IsStockHeld = $data->Row['Is_Stock_Held'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function GetBySupplierProduct($supplierId = null, $productId = null) {
		if(!is_null($supplierId)) {
			$this->Supplier->ID = $supplierId;
		}

        if(!is_null($productId)) {
			$this->Product->ID = $productId;
		}if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT Supplier_Product_ID FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->Product->ID)));
		if($data->TotalRows > 0) {
			$return = $this->Get($data->Row['Supplier_Product_ID']);

			$data->Disconnect();
			return $return;
		}

		$data->Disconnect();
		return false;
	}

	function Add($connection = null) {
		$data = new DataQuery(sprintf("SELECT Supplier_Product_ID FROM supplier_product WHERE Product_ID=%d And Supplier_ID=%d", mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Supplier->ID)), $connection);
		if($data->TotalRows > 0) {
			$this->ID = $data->Row["Supplier_Product_ID"];
			return $this->Update($connection);
		} else {
			if ($this->PreferredSup == 'Y') {
				$this->ReplacedPreferred($connection);
			}

			$data2 = new DataQuery(sprintf("INSERT INTO supplier_product (Supplier_ID, Product_ID, Supplier_Product_Number, Cost, Lead_Days, IsUnavailable, Created_On, Created_By, Modified_On, Modified_by, Preferred_Supplier, Supplier_SKU, Is_Supplied, Is_Stock_Held) VALUES (%d, %d, %d, %f, %d, '%s', NOW(), %d, NOW(), %d, '%s', '%s', '%s', '%s')", mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->SupplierProductNumber), mysql_real_escape_string($this->Cost), mysql_real_escape_string($this->LeadDays), mysql_real_escape_string($this->IsUnavailable), mysql_real_escape_string($GLOBALS["SESSION_USER_ID"]), mysql_real_escape_string($GLOBALS["SESSION_USER_ID"]), mysql_real_escape_string($this->PreferredSup), mysql_real_escape_string($this->SKU), mysql_real_escape_string($this->IsSupplied), mysql_real_escape_string($this->IsStockHeld)), $connection);

			$this->ID = $data2->InsertID;
		}
		$data->Disconnect();

		$price = new SupplierProductPrice();
		$price->Supplier->ID = $this->Supplier->ID;
		$price->Product->ID = $this->Product->ID;
		$price->Quantity = 1;
		$price->Cost = $this->Cost;
		$price->Reason = $this->Reason;
		$price->Add();
		
		$this->Product->Get();
		$this->Product->Update();
	}

	function Update($connection = null) {
		if($this->PreferredSup == 'Y') {
			$this->ReplacedPreferred($connection);
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Supplier_Product_ID=%d", mysql_real_escape_string($this->ID)), $connection);
		$cost = $data->Row['Cost'];
		$data->Disconnect();

		new DataQuery(sprintf("UPDATE supplier_product SET Supplier_Product_Number=%d, Cost=%f, Lead_Days=%d, IsUnavailable='%s', Modified_On=NOW(), Modified_By=%d, Preferred_Supplier='%s', Supplier_SKU='%s', Is_Supplied='%s', Is_Stock_Held='%s' WHERE Supplier_Product_ID=%d", mysql_real_escape_string($this->SupplierProductNumber), mysql_real_escape_string($this->Cost), mysql_real_escape_string($this->LeadDays), mysql_real_escape_string($this->IsUnavailable), mysql_real_escape_string($GLOBALS["SESSION_USER_ID"]), mysql_real_escape_string($this->PreferredSup), mysql_real_escape_string($this->SKU), mysql_real_escape_string($this->IsSupplied), mysql_real_escape_string($this->IsStockHeld), mysql_real_escape_string($this->ID)), $connection);

		if($cost <> $this->Cost) {
			$price = new SupplierProductPrice();
			$price->Supplier->ID = $this->Supplier->ID;
			$price->Product->ID = $this->Product->ID;
			$price->Quantity = 1;
			$price->Cost = $this->Cost;
			$price->Reason = $this->Reason;
			$price->Add();
		}
		
		$this->Product->Get();
		$this->Product->Update();
	}

	function Delete($id = NULL) {
		if(!is_null($id)) {
			$this->Get($id);
		}
		
		$this->Product->Get();
		$this->Product->Update();
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM supplier_product WHERE Supplier_Product_ID=%d", mysql_real_escape_string($this->ID)));

		$price = new SupplierProductPrice();
		$price->Supplier->ID = $this->Supplier->ID;
		$price->Product->ID = $this->Product->ID;
		$price->Quantity = 1;
		$price->Cost = 0;
		$price->Add();
	}

	function IsUnique() {
		$data = new DataQuery(sprintf("SELECT Supplier_Product_ID FROM supplier_product WHERE Product_ID=%d AND Supplier_ID=%d", mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Supplier->ID)));
		if ($data->TotalRows > 0) {
			$this->ID = $data->Row['Supplier_Product_ID'];

			$data->Disconnect();
			return false;
		}

		$data->Disconnect();
		return true;
	}

	function ReplacedPreferred($connection = null) {
		new DataQuery(sprintf("UPDATE supplier_product SET Preferred_Supplier='N' WHERE Product_ID=%d", mysql_real_escape_string($this->Product->ID)), $connection);
	}
	static function DeleteSupplier($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("delete from supplier_product WHERE Supplier_ID = %d",$id));
	}
	static function DeleteProduct($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("delete from supplier_product where Product_ID=%d",$id));
	}
}