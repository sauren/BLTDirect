<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Geozone.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Postage.php");

class ShippingCost {
	var $ID;
	var $ClassID;
	var $Geozone;
	var $Postage;
    var $OverOrderAmount;
	var $WeightThreshold;
	var $PerItem;
	var $PerDelivery;
	var $PerAdditionalKilo;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function __construct($id=NULL) {
		$this->Geozone = new Geozone();
		$this->Postage = new Postage();

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM shipping_cost WHERE Shipping_Cost_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->ClassID = $data->Row['Shipping_Class_ID'];
			$this->Geozone->Get($data->Row['Geozone_ID']);
			$this->Postage->Get($data->Row['Postage_ID']);
			$this->OverOrderAmount = $data->Row['Over_Order_Amount'];
			$this->WeightThreshold = $data->Row['Weight_Threshold'];
			$this->PerItem = $data->Row['Per_Item'];
			$this->PerDelivery = $data->Row['Per_Delivery'];
			$this->PerAdditionalKilo = $data->Row['Per_Additional_Kilo'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO shipping_cost (Shipping_Class_ID, Geozone_ID, Postage_ID, Over_Order_Amount, Weight_Threshold, Per_Item, Per_Delivery, Per_Additional_Kilo, Created_On, Created_By, Modified_On, Modified_By ) VALUES (%d, %d, %d, %f, %f, %f, %f, %f, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->ClassID), mysql_real_escape_string($this->Geozone->ID), mysql_real_escape_string($this->Postage->ID), mysql_real_escape_string($this->OverOrderAmount), mysql_real_escape_string($this->WeightThreshold), mysql_real_escape_string($this->PerItem), mysql_real_escape_string($this->PerDelivery), mysql_real_escape_string($this->PerAdditionalKilo), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE shipping_cost SET Shipping_Class_ID=%d, Geozone_ID=%d, Postage_ID=%d, Over_Order_Amount=%f, Weight_Threshold=%f, Per_Item=%f, Per_Delivery=%f, Per_Additional_Kilo=%f, Modified_On=NOW(), Modified_By=%d WHERE Shipping_Cost_ID=%d", mysql_real_escape_string($this->ClassID), mysql_real_escape_string($this->Geozone->ID), mysql_real_escape_string($this->Postage->ID), mysql_real_escape_string($this->OverOrderAmount), mysql_real_escape_string($this->WeightThreshold), mysql_real_escape_string($this->PerItem), mysql_real_escape_string($this->PerDelivery), mysql_real_escape_string($this->PerAdditionalKilo), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM shipping_cost WHERE Shipping_Cost_ID=%d", mysql_real_escape_string($this->ID)));
	}
}