<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class ProductSpecialOffer {
	public $ID;
	public $ProductID;
	public $BaseOfferPercent;
	public $BaseOfferTolerance;
	public $InactivePeriod;

	public function __construct($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	public function Get($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM product_special_offer WHERE Product_Offer_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->ProductID = $data->Row['Product_ID'];
			$this->BaseOfferPercent = $data->Row['Base_Offer_Percent'];
			$this->BaseOfferTolerance = $data->Row['Base_Offer_Tolerance'];
			$this->InactivePeriod = $data->Row['Inactive_Period'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_special_offer WHERE Product_Offer_ID=%d", mysql_real_escape_string($this->ID)));
	}

	public function Add(){
		$data = new DataQuery(sprintf("INSERT INTO product_special_offer (Product_ID, Base_Offer_Percent, Base_Offer_Tolerance, Inactive_Period) VALUES (%d, %d, %d, %d)", mysql_real_escape_string($this->ProductID), mysql_real_escape_string($this->BaseOfferPercent), mysql_real_escape_string($this->BaseOfferTolerance), mysql_real_escape_string($this->InactivePeriod)));
		
		$this->ID = $data->InsertID;
	}

	public function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE product_special_offer SET Product_ID=%d, Base_Offer_Percent=%d, Base_Offer_Tolerance=%d, Inactive_Period=%d WHERE Product_Offer_ID=%d", mysql_real_escape_string($this->ProductID), mysql_real_escape_string($this->BaseOfferPercent), mysql_real_escape_string($this->BaseOfferTolerance), mysql_real_escape_string($this->InactivePeriod), mysql_real_escape_string($this->ID)));
	}
}