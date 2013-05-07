<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DiscountSchema.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DiscountBanding.php");

class DiscountCollection{
	var $Line;
	var $Customer;

	function DiscountCollection($customer=NULL){
		$this->Line = array();
		$this->Customer = $customer;
		if(!is_null($this->Customer)) $this->Get();
	}

	function Get($customer=NULL){
		if(!is_null($customer)) $this->Customer = $customer;
		$sql = sprintf("SELECT dc.Discount_Customer_ID, ds.* from discount_customer as dc
						inner join discount_schema as ds
						on ds.Discount_Schema_ID=dc.Discount_Schema_ID
						where Customer_ID=%d", mysql_real_escape_string($this->Customer->ID));
		$data = new DataQuery($sql);

		while($data->Row){
			$line = new DiscountSchema();
			$line->ID = $data->Row['Discount_Schema_ID'];
			$line->Reference = $data->Row['Discount_Ref'];
			$line->Name = $data->Row['Discount_Title'];
			$line->Description = $data->Row['Discount_Description'];
			$line->Discount = $data->Row['Discount_Amount'];
			$line->IsFixed = $data->Row['Is_Fixed_Amount'];
			$line->OrdersOver = $data->Row['Orders_Over'];
			$line->IsAllProducts = $data->Row['Is_All_Products'];
			$line->IsActive = $data->Row['Is_Active'];
			$line->CreatedOn = $data->Row['Created_On'];
			$line->CreatedBy = $data->Row['Created_By'];
			$line->ModifiedOn = $data->Row['Modified_On'];
			$line->ModifiedBy = $data->Row['Modified_By'];
			$line->UseBand = $data->Row['Use_Band'];
			$line->IsOnMarkup = $data->Row['Is_On_Markup'];
			$this->Line[] = $line;
			$data->Next();
		}
		$data->Disconnect();
	}

	function DiscountProduct(&$product, $quantity, $discountBandingId = 0) {
		$lineTotal = $product->PriceCurrent * $quantity;
		$discountName = '';

		for($i=0; $i < count($this->Line); $i++) {
			if($this->Line[$i]->IsActive == 'Y') {
				$tempLineTotal = $this->Line[$i]->DiscountProduct($product, $quantity);

				if($tempLineTotal < $lineTotal) {
					$lineTotal = $tempLineTotal;
					$discountName = $this->Line[$i]->Name;
				}
			}
		}

		if($discountBandingId > 0) {
			$banding = new DiscountBanding();
			
			if($banding->Get($discountBandingId)) {
				$price = ($GLOBALS['DISCOUNT_FROM_RRP'] && !empty($product->PriceRRP)) ? $product->PriceRRP : $product->PriceOurs;
				$discount = round(($banding->Discount / 100) * $price, 2);
				$tempLineTotal = round(($price - $discount) * $quantity, 2);

				if($tempLineTotal > ($product->PriceCurrent * $quantity)){
					$tempLineTotal = $product->PriceCurrent * $quantity;
				}

				if($tempLineTotal < $lineTotal) {
					$lineTotal = $tempLineTotal;
					$discountName = $banding->Name;
				}
			}
		}

		return array(round($lineTotal, 2), $discountName);
	}
}
?>