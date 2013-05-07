<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiryLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquirySupplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquirySupplierLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiryQuantity.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");

class PriceEnquiry {
	var $ID;
	var $Status;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $Line;
	var $LinesFetched;
	var $Supplier;
	var $SuppliersFetched;
	var $Quantity;
	var $QuantitiesFetched;

	function __construct($id = NULL) {
		$this->Line = array();
		$this->LinesFetched = false;
		$this->Supplier = array();
		$this->SuppliersFetched = false;
		$this->Quantity = array();
		$this->QuantitiesFetched = false;

		if (!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = NULL) {
		if (!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM price_enquiry WHERE Price_Enquiry_ID=%d ", mysql_real_escape_string($this->ID)));
		if ($data->TotalRows > 0) {
			$this->Status = $data->Row["Status"];
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

	function GetLines() {
		$this->Line = array();
		$this->LinesFetched = true;
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT Price_Enquiry_Line_ID FROM price_enquiry_line WHERE Price_Enquiry_ID=%d ORDER BY Quantity DESC", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$line = new PriceEnquiryLine($data->Row["Price_Enquiry_Line_ID"]);
			$line->Product->Get();

			$this->Line[] = $line;

			$data->Next();
		}
		$data->Disconnect();
	}

	function GetSuppliers() {
		$this->Supplier = array();
		$this->SuppliersFetched = true;
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT Price_Enquiry_Supplier_ID FROM price_enquiry_supplier WHERE Price_Enquiry_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->Supplier[] = new PriceEnquirySupplier($data->Row["Price_Enquiry_Supplier_ID"]);

			$data->Next();
		}
		$data->Disconnect();
	}

	function GetQuantities() {
		$this->Quantity = array();
		$this->QuantitiesFetched = true;
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT Price_Enquiry_Quantity_ID FROM price_enquiry_quantity WHERE Price_Enquiry_ID=%d ORDER BY Quantity ASC", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->Quantity[] = new PriceEnquiryQuantity($data->Row["Price_Enquiry_Quantity_ID"]);

			$data->Next();
		}
		$data->Disconnect();
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO price_enquiry (Status, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Status), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function AddLine($productId, $quantity = 1, $orders = 0) {
		$line = new PriceEnquiryLine();
		$line->PriceEnquiryID = $this->ID;
		$line->Product->ID = $productId;
		$line->Quantity = $quantity;
		$line->Orders = $orders;
		$line->Add();

		$this->LinesFetched = false;
	}

	function AddSupplier($supplierId) {
		$supplier = new PriceEnquirySupplier();
		$supplier->PriceEnquiryID = $this->ID;
		$supplier->Supplier->Get($supplierId);
		$supplier->Add();

		$this->SuppliersFetched = false;
	}

	function AddQuantity($qty) {
		$quantity = new PriceEnquiryQuantity();
		$quantity->PriceEnquiryID = $this->ID;
		$quantity->Quantity = $qty;
		$quantity->Add();

		$this->QuantitiesFetched = false;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE price_enquiry SET Status='%s', Modified_On=NOW(), Modified_By=%d WHERE Price_Enquiry_ID=%d", mysql_real_escape_string($this->Status), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = NULL) {
		if (!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM price_enquiry WHERE Price_Enquiry_ID=%d", mysql_real_escape_string($this->ID)));
		PriceEnquiryLine::DeletePriceEnquiry($this->ID);
		PriceEnquiryQuantity::DeletePriceEnquiry($this->ID);

		$data = new DataQuery(sprintf("SELECT Price_Enquiry_Supplier_ID FROM price_enquiry_supplier WHERE Price_Enquiry_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			PriceEnquirySupplierLine::DeletePriceEnquiry($data->Row['Price_Enquiry_Supplier_ID']);
			$data->Next();
		}
		$data->Disconnect();
		PriceEnquirySupplier::DeletePriceEnquiry($this->ID);
	}

	function Recalculate() {
		if(!$this->SuppliersFetched) {
			$this->GetSuppliers();
		}

		$showPositioning = true;

		for($i=0; $i<count($this->Supplier); $i++) {
			if($this->Supplier[$i]->IsComplete == 'N') {
				$showPositioning = false;
			}
        }

        if(!$showPositioning) {
        	for($i=0; $i<count($this->Supplier); $i++) {
        		$this->Supplier[$i]->Position = 0;
        		$this->Supplier[$i]->Update();
        	}
        } else {
        	$supplierCosts = array();

        	for($i=0; $i<count($this->Supplier); $i++) {
        		$cost = $this->Supplier[$i]->GetTotalCost();

        		$key = number_format($cost, 4, '.', '');
				$key *= 10000;
				$key .= sprintf('%05d', $this->Supplier[$i]->Supplier->ID);

				$supplierCosts[$key] = array();
				$supplierCosts[$key]['Supplier_ID'] = $this->Supplier[$i]->Supplier->ID;
        	}

        	ksort($supplierCosts);

			$position = 0;

			foreach($supplierCosts as $supplier) {
				$position++;

				for($i=0; $i<count($this->Supplier); $i++) {
					if($supplier['Supplier_ID'] == $this->Supplier[$i]->Supplier->ID) {
						$this->Supplier[$i]->Position = $position;
        				$this->Supplier[$i]->Update();

        				break;
					}
				}
			}
        }
	}

	function SetSuppliersIncomplete() {
		if(!$this->SuppliersFetched) {
			$this->GetSuppliers();
		}

		for($i=0; $i<count($this->Supplier); $i++) {
			if($this->Supplier[$i]->IsComplete == 'Y') {
				$this->Supplier[$i]->IsComplete = 'N';
				$this->Supplier[$i]->Update();
			}
       	}
	}
}