<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Geozone.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Postage.php");

class ShippingLimit {
	public $ID;
	public $Postage;
	public $Geozone;
	public $Weight;
	public $Message;
	public $IsShippingPrevented;
	public $CreatedOn;
	public $CreatedBy;
	public $ModifiedOn;
	public $ModifiedBy;

	public function __construct($id=NULL) {
		$this->Geozone = new Geozone();
		$this->Postage = new Postage();
		$this->IsShippingPrevented = 'N';

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

		$data = new DataQuery(sprintf("SELECT * FROM shipping_limit WHERE Shipping_Limit_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Postage->Get($data->Row['Postage_ID']);
			$this->Geozone->Get($data->Row['Geozone_ID']);
			$this->Weight = $data->Row['Weight'];
			$this->Message = $data->Row['Message'];
			$this->IsShippingPrevented = $data->Row['Is_Shipping_Prevented'];
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

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO shipping_limit (Postage_ID, Geozone_ID, Weight, Message, Is_Shipping_Prevented, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, %d, %f, '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Postage->ID), mysql_real_escape_string($this->Geozone->ID), mysql_real_escape_string($this->Weight), mysql_real_escape_string($this->Message), mysql_real_escape_string($this->IsShippingPrevented), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update() {

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE shipping_limit SET Postage_ID=%d, Geozone_ID=%d, Weight=%f, Message='%s', Is_Shipping_Prevented='%s', Modified_On=NOW(), Modified_By=%d WHERE Shipping_Limit_ID=%d", mysql_real_escape_string($this->Postage->ID), mysql_real_escape_string($this->Geozone->ID), mysql_real_escape_string($this->Weight), mysql_real_escape_string($this->Message), mysql_real_escape_string($this->IsShippingPrevented), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}


		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM shipping_limit WHERE Shipping_Limit_ID=%d", mysql_real_escape_string($this->ID)));
	}
}
?>