<?php
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/DataQuery.php");
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/Supplier.php");
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/SupplierProductPriceCollection.php");
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/SupplierReturnRequestLine.php");
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/Order.php");
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/Purchase.php");
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/Courier.php");

class SupplierReturnRequest {
	public $ID;
	public $Supplier;
	public $IntegrationID;
	public $AuthorisationNumber;
	public $Total;
	public $IsPrinted;
	public $Order;
	public $Purchase;
	public $Courier;
	public $Status;
	public $CreatedOn;
	public $CreatedBy;
	public $ModifiedOn;
	public $ModifiedBy;
	public $Line;
	public $LinesFetched;

	public function __construct($id = null) {
		$this->Supplier = new Supplier();
		$this->IsPrinted = 'N';
		$this->Order = new Order();
		$this->Purchase = new Purchase();
		$this->Courier = new Courier();
		$this->Line = array();
		$this->LinesFetched = false;

		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	public function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM supplier_return_request WHERE SupplierReturnRequestID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Supplier->ID = $data->Row['SupplierID'];
			$this->IntegrationID = $data->Row['IntegrationID'];
			$this->AuthorisationNumber = $data->Row['AuthorisationNumber'];
			$this->Total = $data->Row['Total'];
			$this->IsPrinted = $data->Row['IsPrinted'];
			$this->Order->ID = $data->Row['OrderID'];
			$this->Purchase->ID = $data->Row['PurchaseID'];
			$this->Courier->ID = $data->Row['CourierID'];
			$this->Status = $data->Row['Status'];
			$this->CreatedOn = $data->Row['CreatedOn'];
			$this->CreatedBy = $data->Row['CreatedBy'];
			$this->ModifiedOn = $data->Row['ModifiedOn'];
			$this->ModifiedBy = $data->Row['ModifiedBy'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function GetLines() {
		$this->Line = array();
		$this->LinesFetched = true;

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT SupplierReturnRequestLineID FROM supplier_return_request_line WHERE SupplierReturnRequestID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->Line[] = new SupplierReturnRequestLine($data->Row['SupplierReturnRequestLineID']);

			$data->Next();
		}
		$data->Disconnect();
	}

	public function Add() {
		$data = new DataQuery(sprintf("INSERT INTO supplier_return_request (SupplierID, IntegrationID, OrderID, PurchaseID, CourierID, Status, AuthorisationNumber, Total, IsPrinted, CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES (%d, '%s', %d, %d, %d, '%s', '%s', %f, '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->IntegrationID), mysql_real_escape_string($this->Order->ID), mysql_real_escape_string($this->Purchase->ID), mysql_real_escape_string($this->Courier->ID), mysql_real_escape_string($this->Status), mysql_real_escape_string($this->AuthorisationNumber), mysql_real_escape_string($this->Total), mysql_real_escape_string($this->IsPrinted), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	public function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE supplier_return_request SET SupplierID=%d, IntegrationID='%s', OrderID=%d, PurchaseID=%d, CourierID=%d, Status='%s', AuthorisationNumber='%s', Total=%f, IsPrinted='%s', ModifiedOn=NOW(), ModifiedBy=%d WHERE SupplierReturnRequestID=%d", mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->IntegrationID), mysql_real_escape_string($this->Order->ID), mysql_real_escape_string($this->Purchase->ID), mysql_real_escape_string($this->Courier->ID), mysql_real_escape_string($this->Status), mysql_real_escape_string($this->AuthorisationNumber), mysql_real_escape_string($this->Total), mysql_real_escape_string($this->IsPrinted), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM supplier_return_request WHERE SupplierReturnRequestID=%d", mysql_real_escape_string($this->ID)));
		SupplierReturnRequestLine::DeleteSupplierReturnRequest($this->ID);
	}

	public function Recalculate() {
		$this->Total = 0;

		if(!$this->LinesFetched) {
			$this->GetLines();
		}

		if($this->Order->ID > 0) {
			if(!$this->Order->LinesFetched) {
				$this->Order->GetLines();

				for($i=0; $i<count($this->Order->Line); $i++) {
					$this->Order->Line[$i]->DespatchedFrom->Get();
				}
			}
		} elseif($this->Purchase->ID > 0) {
			$this->Purchase->GetLines();
		}

		for($k=0; $k<count($this->Line); $k++) {
			$cost = 0;

			if($this->Order->ID > 0) {
				for($i=0; $i<count($this->Order->Line); $i++) {
					if(($this->Order->Line[$i]->DespatchedFrom->Type == 'S') && ($this->Order->Line[$i]->DespatchedFrom->Contact->ID == $this->Supplier->ID)) {
						if($this->Order->Line[$i]->Product->ID == $this->Line[$k]->Product->ID) {
							$this->Line[$k]->Cost = $this->Order->Line[$i]->Cost;
							$this->Line[$k]->Update();

							break;
						}
					}
				}
			} elseif($this->Purchase->ID > 0) {
				for($i=0; $i<count($this->Purchase->Line); $i++) {
					if($this->Purchase->Line[$i]->ID == $this->Line[$k]->PurchaseLine->ID) {
						$this->Line[$k]->Cost = $this->Purchase->Line[$i]->Cost;
						$this->Line[$k]->Update();

						break;
					}
				}
			}
			
			if($this->Line[$k]->Cost == 0) {
				$prices = new SupplierProductPriceCollection();
				$prices->GetPrices($this->Line[$k]->Product->ID, $this->Supplier->ID);

				$this->Line[$k]->Cost = $prices->GetPrice($this->Line[$k]->Quantity);
				$this->Line[$k]->Update();
			}

			if($this->Line[$k]->IsRejected == 'N') {
				$cost = $this->Line[$k]->Cost * $this->Line[$k]->Quantity;

				switch($this->Line[$k]->HandlingMethod) {
					case 'R':
						$cost -= ($cost / 100) * $this->Line[$k]->HandlingCharge;
						break;
					case 'F':
						$cost -= $this->Line[$k]->HandlingCharge;
						break;
				}

				$this->Total += $cost;
			}
		}

		$this->Update();
	}
}