<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DiscountProduct.php");

class DiscountSchema{
	var $ID;
	var $Reference;
	var $Name;
	var $Description;
	var $Discount;
	var $IsFixed;
	var $OrdersOver;
	var $IsAllProducts;
	var $IsActive;
	var $Errors;
	var $UseBand;
	var $IsOnMarkup;
	var $TotalDiscount;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function DiscountSchema($id=NULL){
		$this->Errors = array();
		$this->TotalDiscount = 0;
		$this->UseBand = 0;
		$this->IsOnMarkup = 'N';

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("select * from discount_schema where Discount_Schema_ID=%d", mysql_real_escape_string($this->ID)));

		$this->Reference = $data->Row['Discount_Ref'];
		$this->Name = $data->Row['Discount_Title'];
		$this->Description = $data->Row['Discount_Description'];
		$this->Discount = $data->Row['Discount_Amount'];
		$this->IsFixed = $data->Row['Is_Fixed_Amount'];
		$this->OrdersOver = $data->Row['Orders_Over'];
		$this->IsAllProducts = $data->Row['Is_All_Products'];
		$this->IsActive = $data->Row['Is_Active'];
		$this->UseBand = $data->Row['Use_Band'];
		$this->IsOnMarkup = $data->Row['Is_On_Markup'];
		$this->CreatedOn = $data->Row['Created_On'];
		$this->CreatedBy = $data->Row['Created_By'];
		$this->ModifiedOn = $data->Row['Modified_On'];
		$this->ModifiedBy = $data->Row['Modified_By'];

		$data->Disconnect();
	}

	function Add(){
		$sql = sprintf("insert into discount_schema (Discount_Ref, Discount_Title, Discount_Description, Discount_Amount, Is_Fixed_Amount, Orders_Over, Is_All_Products, Use_Band, Is_Active, Is_On_Markup, Created_On, Created_By, Modified_On, Modified_By) values ('%s', '%s', '%s', %f, '%s', %f, '%s', '%s', '%s', '%s', Now(), %d, Now(), %d)",
		mysql_real_escape_string($this->Reference),
		mysql_real_escape_string($this->Name),
		mysql_real_escape_string($this->Description),
		mysql_real_escape_string($this->Discount),
		mysql_real_escape_string($this->IsFixed),
		mysql_real_escape_string($this->OrdersOver),
		mysql_real_escape_string($this->IsAllProducts),
		mysql_real_escape_string($this->UseBand),
		mysql_real_escape_string($this->IsActive),
		mysql_real_escape_string($this->IsOnMarkup),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));

		$data = new DataQuery($sql);
		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$sql = sprintf("update discount_schema set Discount_Ref='%s', Discount_Title='%s', Discount_Description='%s', Discount_Amount=%f, Is_Fixed_Amount='%s', Orders_Over=%f, Is_All_Products='%s', Use_Band='%s', Is_Active='%s', Is_On_Markup='%s', Modified_On=Now(), Modified_By=%d where Discount_Schema_ID=%d",
		mysql_real_escape_string($this->Reference),
		mysql_real_escape_string($this->Name),
		mysql_real_escape_string($this->Description),
		mysql_real_escape_string($this->Discount),
		mysql_real_escape_string($this->IsFixed),
		mysql_real_escape_string($this->OrdersOver),
		mysql_real_escape_string($this->IsAllProducts),
		mysql_real_escape_string($this->UseBand),
		mysql_real_escape_string($this->IsActive),
		mysql_real_escape_string($this->IsOnMarkup),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->ID));

		$data = new DataQuery($sql);
	}

	function Delete($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("delete from discount_schema where Discount_Schema_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function GetByReference($reference=NULL){
		if(!is_null($reference)) $this->Reference = $reference;

		$sql = sprintf("select * from discount_schema where Discount_Ref LIKE '%s' and Is_Active='Y'", mysql_real_escape_string($this->Reference));
		$data = new DataQuery($sql);

		if($data->TotalRows > 0){
			$this->ID = $data->Row['Discount_Schema_ID'];
			$this->Name = $data->Row['Discount_Title'];
			$this->Description = $data->Row['Discount_Description'];
			$this->Discount = $data->Row['Discount_Amount'];
			$this->IsFixed = $data->Row['Is_Fixed_Amount'];
			$this->OrdersOver = $data->Row['Orders_Over'];
			$this->IsAllProducts = $data->Row['Is_All_Products'];
			$this->UseBand = $data->Row['Use_Band'];
			$this->IsActive = $data->Row['Is_Active'];
			$this->IsOnMarkup = $data->Row['Is_On_Markup'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];

			$data->Disconnect();
			return true;
		} else {
			$this->Errors[] = sprintf('Unable to find Discount Schema, the Discount Reference &quot;%s&quot; may be invalid.', strtoupper($this->Reference));

			$data->Disconnect();
			return false;
		}
	}

	function CheckTotal($amount){
		if($amount < $this->OrdersOver){
			$this->Errors[] = sprintf('Sorry, this Discount Schema &quot;%s&quot; only entitles you to a discount of %s on orders over &pound;%s.',
			strtoupper($this->Reference),
			$this->GetDiscountString(),
			number_format($this->OrdersOver, 2, ".", ","));
			return false;
		} else {
			return true;
		}
	}

	function GetDiscountString(){
		if(strtoupper($this->IsFixed) == 'Y'){
			$tempStr = sprintf('&pound;%s', number_format($this->Discount, 2, ".", ","));
		} else {
			$tempStr = $this->Discount . '%';
		}
		return $tempStr;
	}

	function DiscountProduct(&$product, $quantity){
		$lineTotal = 0;
		if($this->IsProductDiscounted($product->ID)){
			$price = ($GLOBALS['DISCOUNT_FROM_RRP'] && !empty($product->PriceRRP)) ? $product->PriceRRP : $product->PriceOurs;
			$discount = 0;
			
			if($this->IsOnMarkup == 'N') {
				$discount = round(($this->Discount / 100) * $price, 2);
			} else {
				$data = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Preferred_Supplier='Y' LIMIT 0, 1", mysql_real_escape_string($product->ID)));
				if($data->TotalRows > 0) {
					$discount = round(($this->Discount / 100) * ($product->PriceOurs - $data->Row['Cost']), 2);
				}
				$data->Disconnect();
			}

			$lineTotal = round(($price - $discount) * $quantity, 2);

			if($lineTotal > ($product->PriceCurrent * $quantity)){
				$lineTotal = $product->PriceCurrent * $quantity;
			}
		} else {
			$lineTotal = $product->PriceCurrent * $quantity;
		}

		return round($lineTotal, 2);
	}

	function GetTotalDiscount(){
		return $this->TotalDiscount;
	}

	function Reset(){
		$this->TotalDiscount = 0;
	}

	function IsProductDiscounted($product){
		if(strtoupper($this->IsAllProducts) == 'Y'){
			return true;
		} elseif(strtoupper($this->IsAllProducts) == 'B') {
			if(empty($this->UseBand)) $this->Get();
			$sql = sprintf("select Product_Band_ID from product where Product_ID=%d", mysql_real_escape_string($product));
			$data = new DataQuery($sql);


			if(!empty($data->TotalRows)){
				if($this->UseBand == $data->Row['Product_Band_ID']){
					$myVal = true;
				} else {
					$myVal = false;
				}
				$data->Disconnect();
				return $myVal;
			} else {
				$data->Disconnect();
				return false;
			}
		} else {
			if(!is_numeric($this->ID)){
				return false;
			}
			$sql = sprintf("select Product_ID from discount_product where Discount_Schema_ID=%d and Product_ID=%d", mysql_real_escape_string($this->ID), mysql_real_escape_string($product));
			$data = new DataQuery($sql);
			$data->Disconnect();
			if(!empty($data->TotalRows)){
				return true;
			} else {
				return false;
			}
		}
	}

	function Exists($reference=NULL){
		if(!is_null($reference)) $this->Reference = $reference;

		$sql = sprintf("select Discount_Schema_ID from discount_schema where Discount_Ref like '%s'", mysql_real_escape_string($this->Reference));
		$data = new DataQuery($sql);
		$data->Disconnect();
		if($data->TotalRows > 0){
			return true;
		} else {
			return false;
		}
	}

	function AddCategory($cat){
		$sql = sprintf("select * from product_in_categories where Category_ID=%d", mysql_real_escape_string($cat));
		$data = new DataQuery($sql);
		while($data->Row){
			$cp = new DiscountProduct;
			$cp->ProductID = $data->Row['Product_ID'];
			$cp->DiscountID = $this->ID;
			$cp->Add();
			$data->Next();
		}
		$data->Disconnect();
	}

	function DeleteCategory($cat){

		$sql = sprintf("select * from product_in_categories where Category_ID=%d", mysql_real_escape_string($cat));
		$data = new DataQuery($sql);
		while($data->Row){
			DiscoutProduct::DeleteBySchema($this->ID, $data->Row['Product_ID']);
			$data->Next();
		}
		$data->Disconnect();
	}
}