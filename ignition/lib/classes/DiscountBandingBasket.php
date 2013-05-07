<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountBanding.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountBandingOrder.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountBandingBasketLine.php');

class DiscountBandingBasket {
	var $ID;
	var $DSID;
	var $Customer;
	var $Banding;
	var $SubTotal;
	var $TotalShipping;
	var $TotalTax;
	var $Total;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $Lines;

	function DiscountBandingBasket(&$session){
		$this->DSID = $session->ID;
		$this->Customer = new Customer();
		$this->Customer->ID = $session->Customer->ID;
		$this->Banding = new DiscountBanding();
		$this->Lines = array();

		$this->Get();
	}

	function DeleteOld(){
		$data = new DataQuery(sprintf("SELECT Discount_Banding_Basket_ID FROM discount_banding_basket WHERE Created_On<ADDDATE(NOW(), INTERVAL -2 DAY)"));
		while($data->Row) {
			$data2 = new DataQuery(sprintf("DELETE FROM discount_banding_basket_line WHERE Discount_Banding_Basket_ID=%d", mysql_real_escape_string($data->Row['Discount_Banding_Basket_ID'])));
			$data2->Disconnect();

			$data->Next();
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("DELETE FROM discount_banding_basket WHERE Created_On<ADDDATE(NOW(), INTERVAL -2 DAY)"));
		$data->Disconnect();
	}

	function Exists(){
		$data = new DataQuery(sprintf("SELECT * FROM discount_banding_basket WHERE DSID='%s'", mysql_real_escape_string($this->DSID)));
		if($data->TotalRows > 0){

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function GetLines() {
		$this->Lines = array();

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT Discount_Banding_Basket_Line_ID FROM discount_banding_basket_line WHERE Discount_Banding_Basket_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->Line[] = new DiscountBandingBasketLine($data->Row['Discount_Banding_Basket_Line_ID']);

			$data->Next();
		}
		$data->Disconnect();
	}

	function Get(){
		if($this->Exists()) {
			$data = new DataQuery(sprintf("SELECT * FROM discount_banding_basket WHERE DSID='%s'", mysql_real_escape_string($this->DSID)));
			if($data->TotalRows) {
				$this->ID = $data->Row['Discount_Banding_Basket_ID'];
				$this->DSID = $data->Row['DSID'];
				$this->Customer->ID = $data->Row['Customer_ID'];
				$this->Banding->ID = $data->Row['Banding_ID'];
				$this->Banding->Name = $data->Row['Banding_Name'];
				$this->Banding->Discount = $data->Row['Banding_Discount'];
				$this->Banding->TriggerLow = $data->Row['Banding_Trigger_Low'];
				$this->Banding->TriggerHigh = $data->Row['Banding_Trigger_High'];
				$this->Banding->Threshold = $data->Row['Banding_Threshold'];
				$this->SubTotal = $data->Row['SubTotal'];
				$this->TotalShipping = $data->Row['TotalShipping'];
				$this->TotalTax = $data->Row['TotalTax'];
				$this->Total = $data->Row['Total'];
				$this->CreatedOn = $data->Row['Created_On'];
				$this->CreatedBy = $data->Row['Created_By'];
				$this->ModifiedOn = $data->Row['Modified_On'];
				$this->ModifiedBy = $data->Row['Modified_By'];

				$this->GetLines();

				$data->Disconnect();
				return true;
			}

			$data->Disconnect();
			return false;

		} else {
			$this->Add();

			return true;
		}
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO discount_banding_basket (DSID, Customer_ID, Banding_ID, Banding_Name, Banding_Discount, Banding_Trigger_Low, Banding_Trigger_High, Banding_Threshold, SubTotal, TotalShipping, TotalTax, Total, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', %d, %d, '%s', %d, %f, %f, %f, %f, %f, %f, %f, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->DSID), mysql_real_escape_string($this->Customer->ID), mysql_real_escape_string($this->Banding->ID), mysql_real_escape_string($this->Banding->Name), mysql_real_escape_string($this->Banding->Discount), mysql_real_escape_string($this->Banding->TriggerLow), mysql_real_escape_string($this->Banding->TriggerHigh), mysql_real_escape_string($this->Banding->Threshold), mysql_real_escape_string($this->SubTotal), mysql_real_escape_string($this->TotalShipping), mysql_real_escape_string($this->TotalTax), mysql_real_escape_string($this->Total), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}

	function Update(){

		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("UPDATE discount_banding_basket SET DSID='%s', Customer_ID=%d, Banding_ID=%d, Banding_Name='%s', Banding_Discount=%d, Banding_Trigger_Low=%f, Banding_Trigger_High=%f, Banding_Threshold=%f, SubTotal=%f, TotalShipping=%f, TotalTax=%f, Total=%f, Modified_On=NOW(), Modified_By=%d WHERE Discount_Banding_Basket_ID=%d", mysql_real_escape_string($this->DSID), mysql_real_escape_string($this->Customer->ID), mysql_real_escape_string($this->Banding->ID), mysql_real_escape_string($this->Banding->Name), mysql_real_escape_string($this->Banding->Discount), mysql_real_escape_string($this->Banding->TriggerLow), mysql_real_escape_string($this->Banding->TriggerHigh), mysql_real_escape_string($this->Banding->Threshold), mysql_real_escape_string($this->SubTotal), mysql_real_escape_string($this->TotalShipping), mysql_real_escape_string($this->TotalTax), mysql_real_escape_string($this->Total), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("DELETE FROM discount_banding_basket WHERE Discount_Banding_Basket_ID=%d", mysql_real_escape_string($this->ID)));
		DiscountBandingBasketLine::DeleteDiscountBandingBasket($this->ID);
		$this->DeleteOld();
	}

	function Clear(){
		$this->DeleteOld();
	}

	function Convert($orderID) {
		$bandingOrder = new DiscountBandingOrder();
		$bandingOrder->Order->ID = $orderID;
		$bandingOrder->Banding->ID = $this->Banding->ID;
		$bandingOrder->Banding->Name = $this->Banding->Name;
		$bandingOrder->Banding->Discount = $this->Banding->Discount;
		$bandingOrder->Banding->TriggerLow = $this->Banding->TriggerLow;
		$bandingOrder->Banding->TriggerHigh = $this->Banding->TriggerHigh;
		$bandingOrder->Banding->Threshold = $this->Banding->Threshold;
		$bandingOrder->SubTotal = $this->SubTotal;
		$bandingOrder->TotalShipping = $this->TotalShipping;
		$bandingOrder->TotalTax = $this->TotalTax;
		$bandingOrder->Total = $this->Total;
		$bandingOrder->Add();

		for($i=0; $i<count($this->Line); $i++) {
			$bandingOrderLine = new DiscountBandingOrderLine();
			$bandingOrderLine->DiscountBandingOrderID = $bandingOrder->ID;
			$bandingOrderLine->Product->ID = $this->Line[$i]->Product->ID;
			$bandingOrderLine->Quantity = $this->Line[$i]->Quantity;
			$bandingOrderLine->Discount = $this->Line[$i]->Discount;
			$bandingOrderLine->DiscountInformation = $this->Line[$i]->DiscountInformation;
			$bandingOrderLine->Cost = $this->Line[$i]->Cost;
			$bandingOrderLine->Price = $this->Line[$i]->Price;
			$bandingOrderLine->Add();
		}

		$this->Delete();
	}

	function GenerateFromCart(&$cart, $bandingId) {
		$banding = new DiscountBanding($bandingId);

		$this->Banding = $banding;
		$this->SubTotal = $cart->SubTotal;
		$this->TotalShipping = $cart->ShippingTotal;
		$this->TotalTax = $cart->TaxTotal;
		$this->Total = $cart->Total;
		$this->Update();

		for($i=0; $i<count($cart->Line); $i++) {
			$line = new DiscountBandingBasketLine();
			$line->DiscountBandingBasketID = $this->ID;
			$line->Discount = $cart->Line[$i]->Discount;
			$line->DiscountInformation = $cart->Line[$i]->DiscountInformation;
			$line->Product->Get($cart->Line[$i]->Product->ID);
			$line->Quantity = $cart->Line[$i]->Quantity;

            if($line->Product->Type == 'S') {
				$data = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Cost>0 ORDER BY Preferred_Supplier ASC LIMIT 0, 1", mysql_real_escape_string($line->Product->ID)));
				if($data->TotalRows > 0) {
					$line->Cost = $data->Row['Cost'];
				}
				$data->Disconnect();
			} elseif($line->Product->Type == 'G') {
				$data = new DataQuery(sprintf("SELECT Product_ID, Component_Quantity FROM product_components WHERE Component_Of_Product_ID=%d", mysql_real_escape_string($line->Product->ID)));
				while($data->Row) {
	                $data2 = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Cost>0 ORDER BY Preferred_Supplier ASC LIMIT 0, 1", mysql_real_escape_string($data->Row['Product_ID'])));
					if($data2->TotalRows > 0) {
						$line->Cost += $data2->Row['Cost'] * $data->Row['Component_Quantity'];
					}
					$data2->Disconnect();

					$data->Next();
				}
				$data->Disconnect();
			}

			if($line->Product->PriceCurrent == $line->Product->PriceOurs){
				$priceArr = array();

				$data2 = new DataQuery(sprintf("SELECT * FROM product_prices WHERE Price_Starts_On<=NOW() AND Quantity<=%d AND Product_ID=%d ORDER BY Price_Starts_On DESC", mysql_real_escape_string($line->Quantity), mysql_real_escape_string($line->Product->ID)));
				while($data2->Row) {
					if(!isset($priceArr[$data2->Row['Quantity']])) {
						$priceArr[$data2->Row['Quantity']] = $data2->Row['Price_Base_Our'];
					}
					$data2->Next();
				}
				$data2->Disconnect();

				if(count($priceArr) > 0) {
					krsort($priceArr);
					reset($priceArr);

					$line->Price = current($priceArr);
				}
			} else {
				$line->Price = $line->Product->PriceCurrent;
			}

			$line->Add();
		}
	}

	static function DeleteContact($id){

		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM discount_banding_basket WHERE Customer_ID=%d", mysql_real_escape_string($id)));
	}
}
?>