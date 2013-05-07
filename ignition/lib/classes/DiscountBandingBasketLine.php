<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

class DiscountBandingBasketLine {
	var $ID;
	var $DiscountBandingBasketID;
	var $Product;
	var $Quantity;
	var $Discount;
	var $DiscountInformation;
	var $Cost;
	var $Price;

	function DiscountBandingBasketLine($id=NULL){
		$this->Product = new Product();

		if(!is_null($id)) {
			$this->Get($id);
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM discount_banding_basket_line WHERE Discount_Banding_Basket_Line_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows) {
			$this->DiscountBandingBasketID = $data->Row['Discount_Banding_Basket_ID'];
			$this->Product->ID = $data->Row['Product_ID'];
			$this->Quantity = $data->Row['Quantity'];
			$this->Discount = $data->Row['Discount'];
			$this->DiscountInformation = $data->Row['Discount_Information'];
			$this->Cost = $data->Row['Cost'];
			$this->Price = $data->Row['Price'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO discount_banding_basket_line (Discount_Banding_Basket_ID, Product_ID, Quantity, Discount, Discount_Information, Cost, Price) VALUES (%d, %d, %d, %f, '%s', %f, %f)", mysql_real_escape_string($this->DiscountBandingBasketID), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->Discount), mysql_real_escape_string($this->DiscountInformation), mysql_real_escape_string($this->Cost), mysql_real_escape_string($this->Price)));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("UPDATE discount_banding_basket_line SET Quantity=%d, Discount=%f, Discount_Information='%s', Cost=%f, Price=%f WHERE Discount_Banding_Basket_Line_ID=%d", mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->Discount), mysql_real_escape_string($this->DiscountInformation), mysql_real_escape_string($this->Cost), mysql_real_escape_string($this->Price), mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("DELETE FROM discount_banding_basket_line WHERE Discount_Banding_Basket_Line_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}

	static function DeleteDiscountBandingBasket($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM discount_banding_basket_line WHERE Discount_Banding_Basket_ID=%d", mysql_real_escape_string($id)));
	}
}
?>