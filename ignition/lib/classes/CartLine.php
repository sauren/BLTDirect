<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");

class CartLine{
	var $ID;
	var $Product;
	var $OriginalProduct;
	var $Quantity;
	var $CartID;
	var $IsAssociative;
	var $Price;
	var $PriceRetail;
	var $Total;
	var $Discount;
	var $Tax;
	var $DiscountInformation;
	var $HandlingCharge;
	var $IncludeDownloads;
	var $FreeOfCharge;
	var $AssociativeProductTitle;

	function CartLine($id=NULL){
		$this->IsAssociative = 'N';
		$this->Product = new Product();
		$this->OriginalProduct = new Product();
		$this->IncludeDownloads = 'N';
		$this->FreeOfCharge = 'N';

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

		$data = new DataQuery(sprintf("SELECT cbl.*, IF(cbl.Product_ID>0, p.Product_Title, cbl.Product_Title) AS Product_Title FROM customer_basket_line AS cbl LEFT JOIN product AS p ON p.Product_ID=cbl.Product_ID WHERE cbl.Basket_Line_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$htmlTitle = preg_replace('/<\/p>$/i', '', preg_replace('/^<p[^>]*>/i', '', $data->Row['Product_Title']));

			$this->Product->Get($data->Row['Product_ID']);
			$this->Product->Name = strip_tags($data->Row['Product_Title']);
			$this->OriginalProduct->ID = $data->Row['Original_Product_ID'];
			$this->Quantity = $data->Row['Quantity'];
			$this->Price = $data->Row['Price'];
			$this->CartID = $data->Row['Basket_ID'];
			$this->IsAssociative = $data->Row['Is_Associative'];
			$this->Discount = $data->Row['Discount'];
			$this->DiscountInformation = $data->Row['Discount_Information'];
			$this->HandlingCharge = $data->Row['Handling_Charge'];
			$this->IncludeDownloads = $data->Row['IncludeDownloads'];
			$this->FreeOfCharge = $data->Row['Free_Of_Charge'];
			$this->AssociativeProductTitle = $data->Row['Associative_Product_Title'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$qtyCheck = ($this->Product->ID > 0) ? $this->Exists() : false;

		if(!$qtyCheck){
			if($this->IsValidQuantity()){
				$data = new DataQuery(sprintf("insert into customer_basket_line (Is_Associative, Product_ID, Original_Product_ID, Product_Title, Quantity, Price, Basket_ID, Discount, Discount_Information, Handling_Charge, IncludeDownloads, Free_Of_Charge, Associative_Product_Title) values ('%s', %d, %d, '%s', %d, %f, %d, %f, '%s', %f, '%s', '%s', '%s')",
				mysql_real_escape_string($this->IsAssociative),
				mysql_real_escape_string($this->Product->ID),
				mysql_real_escape_string($this->OriginalProduct->ID),
				mysql_real_escape_string($this->Product->Name),
				mysql_real_escape_string($this->Quantity),
				mysql_real_escape_string($this->Price),
				mysql_real_escape_string($this->CartID),
				mysql_real_escape_string($this->Discount),
				mysql_real_escape_string($this->DiscountInformation),
				mysql_real_escape_string($this->HandlingCharge),
				mysql_real_escape_string($this->IncludeDownloads),
				mysql_real_escape_string($this->FreeOfCharge),
				mysql_real_escape_string($this->AssociativeProductTitle)));
				$this->ID = $data->InsertID;
				return true;
			} else {
				return false;
			}
		} else {
			$this->Quantity += $qtyCheck;
			if($this->IsValidQuantity()){
				$this->Update();
				return true;
			} else {
				return false;
			}
		}
	}

	function Remove($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)) return false;
		$data = new DataQuery(sprintf("delete from customer_basket_line where Basket_Line_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function Update(){
       if(($this->Quantity >= $this->Product->OrderMin) && (($this->Product->OrderMax == 0) || (($this->Product->OrderMax > 0) && ($this->Quantity <= $this->Product->OrderMax)))) {
			$sql = sprintf("UPDATE customer_basket_line SET Is_Associative='%s', Product_ID=%d, Original_Product_ID=%d, Product_Title='%s', Quantity=%d, Price=%f, Basket_ID=%d, Discount=%f, Discount_Information='%s', Handling_Charge=%f, IncludeDownloads='%s', Free_Of_Charge='%s', Associative_Product_Title='%s' where Basket_Line_ID=%d",
			mysql_real_escape_string($this->IsAssociative),
			mysql_real_escape_string($this->Product->ID),
			mysql_real_escape_string($this->OriginalProduct->ID),
			mysql_real_escape_string($this->Product->Name),
			mysql_real_escape_string($this->Quantity),
			mysql_real_escape_string($this->Price),
			mysql_real_escape_string($this->CartID),
			mysql_real_escape_string($this->Discount),
			mysql_real_escape_string($this->DiscountInformation),
			mysql_real_escape_string($this->HandlingCharge),
			mysql_real_escape_string($this->IncludeDownloads),
			mysql_real_escape_string($this->FreeOfCharge),
			mysql_real_escape_string($this->AssociativeProductTitle),
			mysql_real_escape_string($this->ID));

			new DataQuery($sql);
		}
	}

	function Exists() {
		$data = new DataQuery(sprintf("SELECT * FROM customer_basket_line WHERE Basket_ID=%d AND Product_ID=%d", mysql_real_escape_string($this->CartID), mysql_real_escape_string($this->Product->ID)));

		if($data->TotalRows > 0){
			$this->ID = $data->Row['Basket_Line_ID'];
			return $data->Row['Quantity'];
		} else {
			return false;
		}
	}

	function IsValidQuantity(){
		if(empty($this->Quantity)){
			return false;
		} elseif($this->Quantity < $this->Product->OrderMin){
			return false;
		} elseif(($this->Product->OrderMax == 0) || ($this->Quantity >= $this->Product->OrderMin && $this->Quantity <= $this->Product->OrderMax)){
			return true;
		}
	}
}