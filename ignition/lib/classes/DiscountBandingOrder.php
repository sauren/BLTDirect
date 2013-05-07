<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountBanding.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountBandingOrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');

class DiscountBandingOrder {
	var $ID;
	var $Order;
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

	function DiscountBandingOrder($id=NULL){
		$this->Order = new Order();
		$this->Banding = new DiscountBanding();
		$this->Lines = array();

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function GetLines() {
		$this->Lines = array();
		if(!is_numeric($this->ID)){
			return false;
		}


		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT Discount_Banding_Order_Line_ID FROM discount_banding_order_line WHERE Discount_Banding_Order_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->Line[] = new DiscountBandingOrderLine($data->Row['Discount_Banding_Order_Line_ID']);

			$data->Next();
		}
		$data->Disconnect();
	}

	function Get($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM discount_banding_order WHERE Discount_Banding_Order_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows) {
			$this->ID = $data->Row['Discount_Banding_Order_ID'];
			$this->Order->ID = $data->Row['Order_ID'];
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
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO discount_banding_order (Order_ID, Banding_ID, Banding_Name, Banding_Discount, Banding_Trigger_Low, Banding_Trigger_High, Banding_Threshold, SubTotal, TotalShipping, TotalTax, Total, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, %d, '%s', %d, %f, %f, %f, %f, %f, %f, %f, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Order->ID), mysql_real_escape_string($this->Banding->ID), mysql_real_escape_string($this->Banding->Name), mysql_real_escape_string($this->Banding->Discount), mysql_real_escape_string($this->Banding->TriggerLow), mysql_real_escape_string($this->Banding->TriggerHigh), mysql_real_escape_string($this->Banding->Threshold), mysql_real_escape_string($this->SubTotal), mysql_real_escape_string($this->TotalShipping), mysql_real_escape_string($this->TotalTax), mysql_real_escape_string($this->Total), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}

	function Update(){

		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("UPDATE discount_banding_order SET Order_ID=%d, Banding_ID=%d, Banding_Name='%s', Banding_Discount=%d, Banding_Trigger_Low=%f, Banding_Trigger_High=%f, Banding_Threshold=%f, SubTotal=%f, TotalShipping=%f, TotalTax=%f, Total=%f, Modified_On=NOW(), Modified_By=%d WHERE Discount_Banding_Order_ID=%d", mysql_real_escape_string($this->Order->ID), mysql_real_escape_string($this->Banding->ID), mysql_real_escape_string($this->Banding->Name), mysql_real_escape_string($this->Banding->Discount), mysql_real_escape_string($this->Banding->TriggerLow), mysql_real_escape_string($this->Banding->TriggerHigh), mysql_real_escape_string($this->Banding->Threshold), mysql_real_escape_string($this->SubTotal), mysql_real_escape_string($this->TotalShipping), mysql_real_escape_string($this->TotalTax), mysql_real_escape_string($this->Total), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}


		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("DELETE FROM discount_banding_order WHERE Discount_Banding_Order_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();
		DiscountBandingOrderLine::DeleteDiscountBandingOrder($this->ID);
	}

	static function DeleteOrder($id){

		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM discount_banding_order WHERE Order_ID=%d", mysql_real_escape_string($id)));
	}
}
?>