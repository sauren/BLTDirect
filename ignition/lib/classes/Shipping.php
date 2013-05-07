<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Geozone.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Postage.php");

class Shipping{
	public $ID;
	public $Name;
	public $ClassID;
	public $Postage;
	public $Geozone;
	public $PerItem;
	public $PerDelivery;
	public $OverOrderAmount;
	public $WeightThreshold;
	public $PerAdditionalKilo;
	public $CreatedOn;
	public $CreatedBy;
	public $ModifiedOn;
	public $ModifiedBy;

	function Shipping($id=NULL){
		$this->Geozone = new Geozone();
		$this->Postage = new Postage();

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


		$data = new DataQuery(sprintf("SELECT * FROM shipping WHERE Shipping_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Postage->Get($data->Row['Postage_ID']);
			$this->ClassID = $data->Row['Shipping_Class_ID'];
			$this->Geozone->Get($data->Row['Geozone_ID']);
			$this->PerItem = $data->Row['Per_Item'];
			$this->PerDelivery = $data->Row['Per_Delivery'];
			$this->OverOrderAmount = $data->Row['Over_Order_Amount'];
			$this->WeightThreshold = $data->Row['Weight_Threshold'];
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
		$data = new DataQuery(sprintf("INSERT INTO shipping (Postage_ID, Shipping_Class_ID, Geozone_ID, Per_Item, Per_Delivery, Weight_Threshold, Per_Additional_Kilo, Over_Order_Amount, Created_On, Created_By, Modified_On, Modified_By ) VALUES (%d, %d, %d, %f, %f, %f, %f, %f, Now(), %d, Now(), %d)", mysql_real_escape_string($this->Postage->ID), mysql_real_escape_string($this->ClassID), mysql_real_escape_string($this->Geozone->ID), mysql_real_escape_string($this->PerItem), mysql_real_escape_string($this->PerDelivery), mysql_real_escape_string($this->WeightThreshold), mysql_real_escape_string($this->PerAdditionalKilo), mysql_real_escape_string($this->OverOrderAmount), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE shipping SET Postage_ID=%d, Shipping_Class_ID=%d, Geozone_ID=%d, Per_Item=%f, Per_Delivery=%f, Weight_Threshold=%f, Per_Additional_Kilo=%f, Over_Order_Amount=%f, Modified_On=Now(), Modified_By=%d WHERE Shipping_ID=%d", mysql_real_escape_string($this->Postage->ID), mysql_real_escape_string($this->ClassID), mysql_real_escape_string($this->Geozone->ID), mysql_real_escape_string($this->PerItem), mysql_real_escape_string($this->PerDelivery), mysql_real_escape_string($this->WeightThreshold), mysql_real_escape_string($this->PerAdditionalKilo), mysql_real_escape_string($this->OverOrderAmount), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("delete from shipping where Shipping_ID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeleteGeozone($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("delete from shipping where Geozone_ID=%d", mysql_real_escape_string($id)));
	}

	static function DeleteClass($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("delete from shipping where Shipping_Class_ID=%d", mysql_real_escape_string($id)));
	}
}
?>