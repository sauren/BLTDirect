<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquirySupplierLine.php');

class PriceEnquirySupplier {
	var $ID;
	var $PriceEnquiryID;
	var $Supplier;
	var $IsComplete;
	var $Position;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $Cost;
	var $CostsFetched;
	var $Line;
	var $LinesFetched;
	var $CostHash;

	function __construct($id = NULL) {
		$this->Supplier = new Supplier();
		$this->IsComplete = 'N';
		$this->Cost = array();
		$this->CostsFetched = false;
		$this->Line = array();
		$this->LinesFetched = false;

		if (!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM price_enquiry_supplier WHERE Price_Enquiry_Supplier_ID=%d", mysql_real_escape_string($this->ID)));
		if ($data->TotalRows > 0) {
			$this->PriceEnquiryID = $data->Row["Price_Enquiry_ID"];
			$this->Supplier->ID = $data->Row["Supplier_ID"];
			$this->IsComplete = $data->Row["Is_Complete"];
			$this->Position = $data->Row["Position"];
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

	function GetByEnquiryAndSupplierID($enquiryId = NULL, $supplierId = NULL) {
		if (!is_null($enquiryId)) {
			$this->PriceEnquiryID = $enquiryId;
		}

		if (!is_null($supplierId)) {
			$this->Supplier->ID = $supplierId;
		}

		$data = new DataQuery(sprintf("SELECT Price_Enquiry_Supplier_ID FROM price_enquiry_supplier WHERE Price_Enquiry_ID=%d AND Supplier_ID=%d", mysql_real_escape_string($this->PriceEnquiryID), mysql_real_escape_string($this->Supplier->ID)));
		if ($data->TotalRows > 0) {
			$return = $this->Get($data->Row["Price_Enquiry_Supplier_ID"]);

			$data->Disconnect();
			return $return;
		}

		$data->Disconnect();
		return false;
	}

	function GetCosts() {
		$this->Cost = array();
		$this->CostsFetched = true;

		$priceEnquiry = new PriceEnquiry($this->PriceEnquiryID);
		$priceEnquiry->GetLines();

		$costed = array();

		for($k=0; $k<count($priceEnquiry->Line); $k++) {
			$this->Cost[$k] = array('Items' => array(), 'Cost' => 0, 'Total' => 0, 'Costed' => false);

			$data = new DataQuery(sprintf("SELECT Supplier_Product_Price_ID, Quantity, Cost, Created_On FROM supplier_product_price WHERE Product_ID=%d AND Supplier_ID=%d ORDER BY Quantity ASC, Supplier_Product_Price_ID DESC", mysql_real_escape_string($priceEnquiry->Line[$k]->Product->ID), mysql_real_escape_string($this->Supplier->ID)));
			if($data->TotalRows > 0) {
				$used = array();

				while($data->Row) {
					if(!isset($used[$data->Row['Quantity']])) {
						if($data->Row['Cost'] > 0) {
							$this->Cost[$k]['Items'][] = $data->Row;
						}

						$used[$data->Row['Quantity']] = true;
					}

					$data->Next();
				}
			}
			$data->Disconnect();

			$quantity = 0;
			$cost = 0;

			foreach($this->Cost[$k]['Items'] as $item) {
				if($item['Quantity'] <= $priceEnquiry->Line[$k]->Quantity) {
					$quantity = $item['Quantity'];
					$cost = $item['Cost'];

					$this->Cost[$k]['Costed'] = true;
				}
			}

			if($quantity > 0) {
				$this->Cost[$k]['Cost'] = $cost;
				$this->Cost[$k]['Total'] = $cost * $priceEnquiry->Line[$k]->Quantity;
			}

			if($this->Cost[$k]['Costed']) {
				$costed[$priceEnquiry->Line[$k]->Product->ID] = true;
			}
		}

		ksort($costed);

		$this->CostHash = sha1(serialize($costed));
	}

	function GetLines() {
		$this->Line = array();
		$this->LinesFetched = true;

		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT Price_Enquiry_Supplier_Line_ID FROM price_enquiry_supplier_line WHERE Price_Enquiry_Supplier_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->Line[] = new PriceEnquirySupplierLine($data->Row['Price_Enquiry_Supplier_Line_ID']);

			$data->Next();
		}
		$data->Disconnect();
	}

	function GetTotalCost() {
		if(!$this->CostsFetched) {
			$this->GetCosts();
		}

		$totalCost = 0;

		for($k=0; $k<count($this->Cost); $k++) {
			$totalCost += $this->Cost[$k]['Total'];
		}

		return $totalCost;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO price_enquiry_supplier (Price_Enquiry_ID, Supplier_ID, Is_Complete, Position, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, %d, '%s', %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->PriceEnquiryID), mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->IsComplete), mysql_real_escape_string($this->Position), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update() {

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE price_enquiry_supplier SET Price_Enquiry_ID=%d, Supplier_ID=%d, Is_Complete='%s', Position=%d, Modified_On=NOW(), Modified_By=%d WHERE Price_Enquiry_Supplier_ID=%d", mysql_real_escape_string($this->PriceEnquiryID), mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->IsComplete), mysql_real_escape_string($this->Position), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = NULL) {
		if (!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM price_enquiry_supplier WHERE Price_Enquiry_Supplier_ID=%d", mysql_real_escape_string($this->ID)));
		PriceEnquirySupplierLine::DeletePriceEnquirySupplier($this->ID);
	}

	static function DeletePriceEnquiry($id){

		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM price_enquiry_supplier WHERE Price_Enquiry_ID=%d", mysql_real_escape_string($id)));
	}
}