<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CouponProduct.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Password.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');

class Coupon {
	var $ID;
	var $Reference;
	var $Name;
	var $Description;
	var $Discount;
	var $Commission;
	var $IsFixed;
	var $OrdersOver;
	var $UsageLimit;
	var $IsAllProducts;
	var $IsActive;
	var $IsAllCustomers;
	var $OwnedBy;
	var $ExpiresOn;
	var $Errors;
	var $UseBand;
	var $StaffOnly;
	var $IntroducedBy;
	var $IsInvisible;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $TotalDiscount;

	function __construct($id=NULL) {
		$this->StaffOnly = 'N';
		$this->Errors = array();
		$this->TotalDiscount = 0;
		$this->UseBand = 0;
		$this->IsInvisible = 'N';
		$this->ExpiresOn = '0000-00-00 00:00:00';

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

		$data = new DataQuery(sprintf("select * from coupon where Coupon_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Reference = $data->Row['Coupon_Ref'];
			$this->Name = $data->Row['Coupon_Title'];
			$this->Description = $data->Row['Coupon_Description'];
			$this->Discount = $data->Row['Discount_Amount'];
			$this->Commission = $data->Row['Commission_Amount'];
			$this->IsFixed = $data->Row['Is_Fixed_Amount'];
			$this->OrdersOver = $data->Row['Orders_Over'];
			$this->UsageLimit = $data->Row['Usage_Limit'];
			$this->IsAllProducts = $data->Row['Is_All_Products'];
			$this->IsActive = $data->Row['Is_Active'];
			$this->UseBand = $data->Row['Use_Band'];
			$this->IsAllCustomers = $data->Row['Is_All_Customers'];
			$this->OwnedBy = $data->Row['Owned_By'];
			$this->ExpiresOn = $data->Row['Expires_On'];
			$this->StaffOnly = $data->Row['Staff_Only'];
			$this->IntroducedBy = $data->Row['Introduced_By'];
			$this->IsInvisible = $data->Row['Is_Invisible'];
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
		$data = new DataQuery(sprintf("INSERT INTO coupon (Staff_Only, Coupon_Ref, Coupon_Title, Coupon_Description, Discount_Amount, Commission_Amount, Is_Fixed_Amount, Orders_Over, Usage_Limit, Is_All_Products, Use_Band, Is_Active, Is_All_Customers, Owned_By, Expires_On, Created_On, Created_By, Modified_On, Modified_By, Introduced_By, Is_Invisible) VALUES ('%s', '%s', '%s', '%s', %f, %f,'%s', %f, %d, '%s', '%s', '%s', '%s', %d, '%s', Now(), %d, Now(), %d, %d, '%s')", mysql_real_escape_string($this->StaffOnly), mysql_real_escape_string($this->Reference), mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Description), mysql_real_escape_string($this->Discount), mysql_real_escape_string($this->Commission), mysql_real_escape_string($this->IsFixed), mysql_real_escape_string($this->OrdersOver), mysql_real_escape_string($this->UsageLimit), mysql_real_escape_string($this->IsAllProducts), mysql_real_escape_string($this->UseBand), mysql_real_escape_string($this->IsActive), mysql_real_escape_string($this->IsAllCustomers), mysql_real_escape_string($this->OwnedBy), mysql_real_escape_string($this->ExpiresOn), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->IntroducedBy), mysql_real_escape_string($this->IsInvisible)));

		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE coupon SET Coupon_Ref='%s', Coupon_Title='%s', Coupon_Description='%s', Discount_Amount=%f, Commission_Amount=%f, Is_Fixed_Amount='%s', Orders_Over=%f, Usage_Limit=%d, Is_All_Products='%s', Use_Band='%s', Is_Active='%s', Is_All_Customers='%s', Owned_By=%d, Expires_On='%s', Modified_On=Now(), Modified_By=%d where Coupon_ID=%d", mysql_real_escape_string($this->Reference), mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Description), mysql_real_escape_string($this->Discount), mysql_real_escape_string($this->Commission), mysql_real_escape_string($this->IsFixed), mysql_real_escape_string($this->OrdersOver), mysql_real_escape_string($this->UsageLimit), mysql_real_escape_string($this->IsAllProducts), mysql_real_escape_string($this->UseBand), mysql_real_escape_string($this->IsActive), mysql_real_escape_string($this->IsAllCustomers), mysql_real_escape_string($this->OwnedBy), mysql_real_escape_string($this->ExpiresOn), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("delete from coupon where Coupon_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function Check($reference, $total, $customer, $customerSide = false){
		/*
			* Check Reference Number
			* Check Cart Total Order Amount
			* Check Usage
			* Check Staff Only
		*/
		// Check Reference
		$this->Reference = $reference;
		if(!$this->GetByReference()) return false;

		// Check Staff Only
		if(($customerSide) && ($this->StaffOnly == 'Y')) {
			$this->Errors[] = sprintf('Unable to find Coupon, the Coupon Reference &quot;%s&quot; may be invalid, or may have expired.', mysql_real_escape_string(strtoupper($this->Reference)));
			return false;
		}

		// Check Introduced By
		if($this->IntroducedBy > 0) {
			$data = new DataQuery(sprintf("SELECT COUNT(*) AS count FROM coupon WHERE Coupon_Ref LIKE '%s' AND Introduced_By=%d", mysql_real_escape_string($reference), mysql_real_escape_string($customer)));
			$data->Disconnect();

			if($data->Row['count'] > 0) {
				$this->Errors[] = sprintf('You cannot use your own coupon to discount your order.', mysql_real_escape_string(strtoupper($this->Reference)));
				return false;
			}
		}

		// Check Cart Total
		if(!$this->CheckTotal($total)) return false;

		// Check Usage
		if(!empty($customer)){
			if(!$this->CheckUsage($customer)) return false;
		}
		return true;
	}

	function GetByReference($reference=NULL){
		if(!is_null($reference)) {
			$this->Reference = $reference;
		}

		$data = new DataQuery(sprintf("select Coupon_ID from coupon where Coupon_Ref LIKE '%s' and Is_Active='Y' and (Expires_On > Now() or Expires_On = '0000-00-00 00:00:00') AND Is_Invisible='N'", mysql_real_escape_string($this->Reference)));

		if($data->TotalRows > 0){
			$return = $this->Get($data->Row['Coupon_ID']);

			$data->Disconnect();
			return $return;
		}

		$this->Errors[] = sprintf('Unable to find Coupon, the Coupon Reference &quot;%s&quot; may be invalid, or may have expired.', strtoupper($this->Reference));

		$data->Disconnect();
		return false;
	}

	function CheckTotal($amount){
		if($amount < $this->OrdersOver){
			$this->Errors[] = sprintf('Sorry, coupon &quot;%s&quot; only entitles you to a discount of %s on orders over &pound;%s.',
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

	function CheckUsage($customer){
		// count the number of times the coupon has been used by this customer
		$sql = sprintf("select count(Order_ID) as used from orders where Customer_ID=%d and Coupon_ID=%d AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')",
			$customer,
			$this->ID);
		$data = new DataQuery($sql);
		if($data->Row['used'] >= 0 && $data->Row['used'] < $this->UsageLimit){
			// good we can use the coupon
			$data->Disconnect();
			return true;
		} else {
			// no we can't, it's been used too much
			$this->Errors[] = sprintf("Sorry, this coupon may only be used %d times. You have used this coupon %d time(s).", $this->UsageLimit, $data->Row['used']);
			$data->Disconnect();
			return false;
		}
	}

	function GetExpiryString(){
		if($this->ExpiresOn != '0000-00-00 00:00:00'){
			return sprintf('This coupon expires on %s', cDatetime($this->ExpiresOn, 'longdate'));
		} else {
			return 'This coupon does not have an expiry date.';
		}
	}

	function DiscountProduct(&$product, $quantity){
		$lineTotal = 0;
		if($this->IsProductDiscounted($product->ID)) {
			$price = ($GLOBALS['DISCOUNT_FROM_RRP'] && !empty($product->PriceRRP)) ? $product->PriceRRP : $product->PriceOurs;
			$discount = round(($this->Discount / 100) * $price, 2);
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
			$sql = sprintf("select Product_ID from product where Product_Band_ID=%d and Product_ID=%d", $this->UseBand, $product);
			$data = new DataQuery($sql);
			$data->Disconnect();
			if(!empty($data->TotalRows)){
				return true;
			} else {
				return false;
			}
		} else {
			if(!is_numeric($this->ID)){
				return false;
			}
			$sql = sprintf("select Product_ID from coupon_product where Coupon_ID=%d and Product_ID=%d", $this->ID, $product);
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

		$sql = sprintf("select Coupon_ID from coupon where Coupon_Ref like '%s'", $this->Reference);
		$data = new DataQuery($sql);
		$data->Disconnect();
		if($data->TotalRows > 0){
			return true;
		} else {
			return false;
		}
	}

	function AddCategory($cat, $sub = true) {
		$clientString = '';
	
		if($cat != 0) {
			if($sub) {
				$clientString = sprintf("WHERE (Category_ID=%d %s) ", $cat, $this->GetChildIDS($cat));
			} else {
				$clientString = sprintf("WHERE Category_ID=%d ", $cat);
			}
		} else {
			if(!$sub) {
				$clientString = sprintf("WHERE (Category_ID IS NULL OR Category_ID=%d) ", $cat);
			}
		}
	
		$sql = sprintf("select DISTINCT Product_ID from product_in_categories %s", $clientString);
		$data = new DataQuery($sql);
		while($data->Row){
			$cp = new CouponProduct;
			$cp->ProductID = $data->Row['Product_ID'];
			$cp->CouponID = $this->ID;
			$cp->Add();
			$data->Next();
		}
		$data->Disconnect();
	}
	
	function GetChildIDS($cat) {
		$string = "";
		$children = new DataQuery(sprintf("SELECT * FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($cat)));
		while($children->Row) {
			$string .= "OR Category_ID=".$children->Row['Category_ID']." ";
			$string .= $this->GetChildIDS($children->Row['Category_ID']);
			$children->Next();
		}
		$children->Disconnect();
		return $string;
	}

	function DeleteCategory($cat){
		$sql = sprintf("select * from product_in_categories where Category_ID=%d", mysql_real_escape_string($cat));
		$data = new DataQuery($sql);
		while($data->Row){
			$cp = new CuponProduct();
			$cp->ID = $this->ID;
			$cp->Delete();
			$data->Next();
		}
		$data->Disconnect();
	}

	function GenerateReference() {
		$randomString = new Password(9);
		$randomString->Generate();
		$couponRef = strtoupper($randomString->Value);
		$couponRef = substr($couponRef, 0 , 3) . '-' . substr($couponRef, 3, 3) . '-' . substr($couponRef, 6, 3);

		if($this->Exists($couponRef))	{
			return $this->GenerateReference();
		} else {
			return $couponRef;
		}
	}

	function GetIntroductoryCoupon($introducedBy) {
		$data = new DataQuery(sprintf("SELECT Coupon_ID FROM coupon WHERE Introduced_By=%d", mysql_real_escape_string($introducedBy)));
		if($data->TotalRows > 0) {
			$this->Get($data->Row['Coupon_ID']);

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function GenerateIntroductoryCoupon($introducedBy) {
		$this->Reference = $this->GenerateReference();
		$this->Name = 'Introduce A Friend';
		$this->Description = 'Introduce A Friend';
		$this->Discount = Setting::GetValue('customer_coupon_discount');
		$this->IsFixed = 'N';
		$this->OrdersOver = Setting::GetValue('customer_coupon_orders_over');
		$this->UsageLimit = Setting::GetValue('customer_coupon_usage_limit');
		$this->IsAllProducts = 'Y';
		$this->IsActive = 'Y';
		$this->StaffOnly = 'N';
		$this->UseBand = 0;
		$this->IsAllCustomers = 'Y';
		$this->ExpiresOn = '0000-00-00 00:00:00';
		$this->IntroducedBy = $introducedBy;
		$this->Add();
	}
}
?>