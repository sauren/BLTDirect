<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductLog.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProductPriceCollection.php');

class SupplierProductPrice {
	var $ID;
	var $Supplier;
	var $Product;
	var $Quantity;
	var $Cost;
	var $Reason;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	
	function __construct($id = null) {
		$this->Supplier = new Supplier();
		$this->Product = new Product();
		
		if(!is_null($id)) {
			$this->Get($id);
		}
	}
	
	function Get($id = NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		
		$data = new DataQuery(sprintf("SELECT * FROM supplier_product_price WHERE Supplier_Product_Price_ID=%d", mysql_real_escape_string($this->ID)));
		if ($data->TotalRows > 0) {
			$this->Supplier->ID = $data->Row["Supplier_ID"];
			$this->Product->ID = $data->Row["Product_ID"];
			$this->Quantity = $data->Row["Quantity"];
			$this->Cost = $data->Row["Cost"];
			$this->Reason = $data->Row["Reason"];
			$this->CreatedOn = $data->Row["Created_On"];
			$this->CreatedBy = $data->Row["Created_By"];
			$this->ModifiedOn = $data->Row["Modified_On"];
			$this->ModifiedBy = $data->Row["Modified_By"];
			
			$data->Disconnect();
			return true;
		}
		
		$data->Disconnect();
		return false;
	}
	
	function Add() {
		$this->Supplier->Get();
		$this->Supplier->Contact->Get();

		$price = 0;

		if($GLOBALS["SESSION_USER_ID"] > 0) {
			if($this->Cost > 0) {
				$collection = new SupplierProductPriceCollection();
				$collection->GetPrices($this->Product->ID, $this->Supplier->ID);

				$price = $collection->GetPrice($this->Quantity);
			}
		}

		$data = new DataQuery(sprintf("INSERT INTO supplier_product_price (Supplier_ID, Product_ID, Quantity, Cost, Reason, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, %d, %d, %f, '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->Cost), mysql_real_escape_string($this->Reason), mysql_real_escape_string($GLOBALS["SESSION_USER_ID"]), mysql_real_escape_string($GLOBALS["SESSION_USER_ID"])));
			
		$this->ID = $data->InsertID;

		if($GLOBALS["SESSION_USER_ID"] > 0) {
			if($this->Cost > 0) {
				if($price > 0) {
					$log = new ProductLog();
					$log->productId = $this->Product->ID;
					$log->log = sprintf('New cost of &pound;%+.2f (&pound;%+.2f) for \'%s\' of %d quantities%s.', number_format($this->Cost, 2, '.', ''), number_format($this->Cost - $price, 2, '.', ''), $this->Supplier->Contact->Person->GetFullName(), $this->Quantity, !empty($this->Reason) ? sprintf(' because \'%s\'', $this->Reason) : '');
					$log->add();
				} else {
					$log = new ProductLog();
					$log->productId = $this->Product->ID;
					$log->log = sprintf('New cost of &pound;%+.2f for \'%s\' of %d quantities%s.', number_format($this->Cost, 2, '.', ''), $this->Supplier->Contact->Person->GetFullName(), $this->Quantity, !empty($this->Reason) ? sprintf(' because \'%s\'', $this->Reason) : '');
					$log->add();
				}
			} else {
				$log = new ProductLog();
				$log->productId = $this->Product->ID;
				$log->log = sprintf('Nullified cost for \'%s\' of %d quantities%s.', $this->Supplier->Contact->Person->GetFullName(), $this->Quantity, !empty($this->Reason) ? sprintf(' because \'%s\'.', $this->Reason) : '');
				$log->add();
			}
		}
		
		$this->CheckProductLock();
	}
	
	function Delete($id = NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		
		new DataQuery(sprintf("DELETE FROM supplier_product_price WHERE Supplier_Product_Price_ID=%d", mysql_real_escape_string($this->ID)));
	}
	
	function CheckProductLock() {
		$this->Product->Get();

		$bestSupplierId = $this->Product->GetBestSupplierID();
		
		if($this->Product->LockedSupplierID > 0) {
			if($bestSupplierId != $this->Product->LockedSupplierID) {
				if($GLOBALS["SESSION_USER_ID"] > 0) {
					$oldSupplier = new Supplier();
					$oldSupplier->Get($this->Product->LockedSupplierID);
					$oldSupplier->Contact->Get();

					$newSupplier = new Supplier();
					$newSupplier->Get($bestSupplierId);
					$newSupplier->Contact->Get();

					$log = new ProductLog();
					$log->productId = $this->Product->ID;
					$log->log = sprintf('Unlocking supplier from \'%s\' due to better price supplied by \'%s\'.', $oldSupplier->Contact->Person->GetFullName(), $newSupplier->Contact->Person->GetFullName());
					$log->add();
				}

				$this->Product->LockedSupplierID = 0;
				$this->Product->Update();
			}
		}
	}
}