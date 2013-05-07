<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ContactProductTrade.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Country.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Region.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CartLine.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ShippingCalculator.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/TaxCalculator.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Geozone.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Coupon.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/GlobalTaxCalculator.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DiscountCollection.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CustomerContact.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductPrice.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ShippingClass.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CartShipping.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/TradeBanding.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Log.php");

class Cart {
	public $ID;
	public $CSID;
	public $QuoteID;
	public $Customer;
	public $BillingCountry;
	public $BillingRegion;
	public $ShippingCountry;
	public $ShippingRegion;
	public $Line;
	public $SubTotal;
	public $SubTotalRetail;
	public $ShippingTotal;
	public $TaxTotal;
	public $Total;
	public $Weight;
	public $TotalLines;
	public $ShipTo;
	public $Coupon;
	public $Discount;
	public $PostageOptions;
	public $Postage;   // Postage ID, not an object!
	public $FoundPostage;
	public $Error;
	public $Warning;
	public $Geozone;
	public $Location;
	public $ShippingCalculator;
	public $Errors;
	public $Warnings;
	public $DiscountCollection;
	public $DiscountInformation;
	public $ContactedOn;
	public $IsCustomShipping;
	public $TaxExemptCode;
	public $TaxCalculator;
	public $FreeText;
	public $FreeTextValue;
	public $Prefix;
	public $DiscountBandingID;
	public $DiscountBandingOffered;
	public $ShippingLine;
	public $ShippingMultiplier;
	public $MobileDetected;
	public $isAdopted;

	public function __construct(&$session, $isManual=false) {
		$this->Prefix = 'W';
		$this->Error = false;
		$this->Warning = false;
		$this->isAdopted = false;
		$this->Errors = array();
		$this->Warnings = array();
		$this->IsCustomShipping = 'N';
		$this->DiscountInformation = array();
		$this->Customer = new Customer;
		$this->Coupon = new Coupon;
		$this->Coupon->ID = 0;
		$this->ShippingCalculator = new ShippingCalculator();
		$this->Customer->ID = ($isManual)?0:$session->Customer->ID;
		$this->CSID = $session->ID;
		$this->ShippingCountry = new Country;
		$this->ShippingRegion = new Region;
		$this->BillingCountry = new Country;
		$this->BillingRegion = new Region;
		$this->Postage = $GLOBALS['DEFAULT_POSTAGE'];
		$this->DiscountCollection = new DiscountCollection();
		$this->DiscountBandingID = 0;
		$this->DiscountBandingOffered = 'N';
		$this->Line = array();
		$this->ShippingLine = array();
		$this->ShippingMultiplier = 1;
		$this->MobileDetected = false;
		
		$this->Reset();
		$this->Get();
	}

	function Add() {
		$sql = "INSERT INTO customer_basket (CSID, Prefix, Quote_ID, Customer_ID,
                                                 Coupon_ID, Country_ID,
                                                 Region_ID, Shipping_Option_ID,
                                                 Ship_To, TotalShipping,
                                                 IsCustomShipping,
                                                 TaxExemptCode,
                                                 Created_On,
                                                 Free_Text,
                                                 Free_Text_Value,
                                                 Discount_Banding_ID,
                                                 Discount_Banding_Offered)
                     VALUES ('%s', '%s', %d, %d, %d, %d, %d, %d, '%s',
                             %f, '%s', '%s', Now(), '%s', %f, %d, '%s')";

		$data = new DataQuery(sprintf($sql,
		mysql_real_escape_string($this->CSID),
		mysql_real_escape_string($this->Prefix),
		mysql_real_escape_string($this->QuoteID),
		mysql_real_escape_string($this->Customer->ID),
		mysql_real_escape_string($this->Coupon->ID),
		mysql_real_escape_string($this->ShippingCountry->ID),
		mysql_real_escape_string($this->ShippingRegion->ID),
		mysql_real_escape_string($this->Postage),
		mysql_real_escape_string($this->ShipTo),
		mysql_real_escape_string($this->ShippingTotal),
		mysql_real_escape_string($this->IsCustomShipping),
		mysql_real_escape_string($this->TaxExemptCode),
		mysql_real_escape_string($this->FreeText),
		mysql_real_escape_string($this->FreeTextValue),
		mysql_real_escape_string($this->DiscountBandingID),
		mysql_real_escape_string($this->DiscountBandingOffered)));

		$this->ID = $data->InsertID;
	}

	function Adopt($id){
		if(!is_numeric($id)){
			return false;
		}
		$this->getByID($id);
		if(!$this->ID){
			return false;
		}
		$this->Calculate();
		$_SESSION['CART_ADOPT'] = $id;
		$this->isAdopted = true;
		return true;
	}

	function Release($id){
		$_SESSION['CART_ADOPT'] = null;
		$this->isAdopted = false;
	}

	function Exists() {
		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM customer_basket WHERE CSID='%s'", mysql_real_escape_string($this->CSID)));
		$count = ($data->Row && $data->Row['Count']) ? $data->Row['Count'] : 0;
		$data->Disconnect();
		return ($count > 0);
	}
	
	function getByID($id){
		if(!is_numeric($id)) return false;
		$sql = sprintf("SELECT
                      cb.*,
                      c.Country, c.Address_Format_ID, c.ISO_Code_2, c.Allow_Sales,
                      af.Address_Format, af.Address_Summary,
                      r.Region_Name, r.Region_Code
                      FROM customer_basket AS cb
                      LEFT JOIN countries AS c ON cb.Country_ID=c.Country_ID
                      LEFT JOIN address_format AS af ON c.Address_Format_ID=af.Address_Format_ID
                      LEFT JOIN regions AS r ON cb.Region_ID=r.Region_ID WHERE Basket_ID=%d", mysql_real_escape_string($id));
		$this->populateFromSql($sql);
		$this->TaxCalculator = new GlobalTaxCalculator($this->ShippingCountry->ID, $this->ShippingRegion->ID);
	}
	
	function Get(){
		if(array_key_exists('CART_ADOPT', $_SESSION) && !is_null($_SESSION['CART_ADOPT'])){
			$this->getByID($_SESSION['CART_ADOPT']);
			$this->isAdopted = true;
		} else if($this->Exists()) {
			$sql = sprintf("SELECT
                      cb.*,
                      c.Country, c.Address_Format_ID, c.ISO_Code_2, c.Allow_Sales,
                      af.Address_Format, af.Address_Summary,
                      r.Region_Name, r.Region_Code
                      FROM customer_basket AS cb
                      LEFT JOIN countries AS c ON cb.Country_ID=c.Country_ID
                      LEFT JOIN address_format AS af ON c.Address_Format_ID=af.Address_Format_ID

                      LEFT JOIN regions AS r ON cb.Region_ID=r.Region_ID WHERE CSID='%s'", mysql_real_escape_string($this->CSID));
			$this->populateFromSql($sql);
        } else {
			if(empty($this->BillingCountry->ID)){
				$country = new Country($GLOBALS['SYSTEM_COUNTRY']);
				$region = new Region($GLOBALS['SYSTEM_REGION']);
				$this->BillingCountry = $country;
				$this->BillingRegion = $region;
			}

			if(empty($this->ShippingCountry->ID)){
				$country = new Country($GLOBALS['SYSTEM_COUNTRY']);
				$region = new Region($GLOBALS['SYSTEM_REGION']);
				$this->ShippingCountry = $country;
				$this->ShippingRegion = $region;
			}

			//$this->Add();
		}
		$this->TaxCalculator = new GlobalTaxCalculator($this->ShippingCountry->ID, $this->ShippingRegion->ID);
	}
	
	private function populateFromSql($sql){
		$data = new DataQuery($sql);
		$this->ID = $data->Row['Basket_ID'];
		$this->Prefix = $data->Row['Prefix'];
		$this->QuoteID = $data->Row['Quote_ID'];
		$this->Customer->ID = $data->Row['Customer_ID'];
		$this->Coupon->ID = $data->Row['Coupon_ID'];
		$this->ShippingRegion->ID = $data->Row["Region_ID"];
		$this->ShippingRegion->Name = $data->Row["Region_Name"];
		$this->ShippingRegion->CountryID = $data->Row["Country_ID"];
		$this->ShippingRegion->Code = $data->Row["Region_Code"];
		$this->ShippingCountry->ID = $data->Row['Country_ID'];
		$this->ShippingCountry->Name = $data->Row['Country'];
		$this->ShippingCountry->AddressFormat->ID = $data->Row['Address_Format_ID'];
		$this->ShippingCountry->AddressFormat->Long = $data->Row['Address_Format'];
		$this->ShippingCountry->AddressFormat->Short = $data->Row['Address_Summary'];
		$this->ShippingCountry->ISOCode2 = $data->Row['ISO_Code_2'];
		$this->ShippingCountry->AllowSales = $data->Row['Allow_Sales'];
		$this->BillingRegion->ID = $data->Row["Region_ID"];
		$this->BillingRegion->Name = $data->Row["Region_Name"];
		$this->BillingRegion->CountryID = $data->Row["Country_ID"];
		$this->BillingRegion->Code = $data->Row["Region_Code"];
		$this->BillingCountry->ID = $data->Row['Country_ID'];
		$this->BillingCountry->Name = $data->Row['Country'];
		$this->BillingCountry->AddressFormat->ID = $data->Row['Address_Format_ID'];
		$this->BillingCountry->AddressFormat->Long = $data->Row['Address_Format'];
		$this->BillingCountry->AddressFormat->Short = $data->Row['Address_Summary'];
		$this->BillingCountry->ISOCode2 = $data->Row['ISO_Code_2'];
		$this->BillingCountry->AllowSales = $data->Row['Allow_Sales'];

		if(empty($this->BillingCountry->ID)){
			$country = new Country($GLOBALS['SYSTEM_COUNTRY']);
			$region = new Region($GLOBALS['SYSTEM_REGION']);
			$this->BillingCountry = $country;
			$this->BillingRegion = $region;
		}

		if(empty($this->ShippingCountry->ID)){
			$country = new Country($GLOBALS['SYSTEM_COUNTRY']);
			$region = new Region($GLOBALS['SYSTEM_REGION']);
			$this->ShippingCountry = $country;
			$this->ShippingRegion = $region;
		}

		if(!empty($this->Customer->ID)){
			// if customer id is > 0  we use the customers address for billing
			if(empty($this->Customer->Contact->ID)) $this->Customer->Get();
			if(empty($this->Customer->Contact->Person->ID)) $this->Customer->Contact->Get();
			if(empty($this->Customer->Contact->Person->Country->ID)) $this->Customer->Contact->Person->Get();
			$this->Customer->Contact->Person;
			$this->BillingRegion = $this->Customer->Contact->Person->Address->Region;
			$this->BillingCountry = $this->Customer->Contact->Person->Address->Country;

			// if ship to is empty or = 'billing' we use the the customers address for shipping
		} else {
			// if no customer id use the cart country_ID and Region_ID for shipping
			// this has already been done above
		}

		$this->Postage = $data->Row['Shipping_Option_ID'];
		$this->ShipTo = $data->Row['Ship_To'];
		$this->IsCustomShipping = $data->Row['IsCustomShipping'];
		$this->TaxExemptCode = $data->Row['TaxExemptCode'];
		$this->ShippingTotal = $data->Row['TotalShipping'];
		$this->FreeText = $data->Row['Free_Text'];
		$this->FreeTextValue = $data->Row['Free_Text_Value'];
		$this->DiscountBandingID = $data->Row['Discount_Banding_ID'];
		$this->DiscountBandingOffered = $data->Row['Discount_Banding_Offered'];

		$this->ContactedOn = $data->Row['Contacted_On'];

		if($this->Coupon->ID > 0) {
			$this->Coupon->Get();
		}
		$data->Disconnect();
		if(!empty($this->ShipTo)){
			if($this->ShipTo == 'billing'){
				$this->ShippingCountry =  $this->Customer->Contact->Person->Address->Country;
				$this->ShippingRegion = $this->Customer->Contact->Person->Address->Region;
			} else {
				$customerContact = new CustomerContact($this->ShipTo);
				$this->ShippingCountry =  $customerContact->Address->Country;
				$this->ShippingRegion = $customerContact->Address->Region;
			}
		}
		$this->TaxCalculator = new GlobalTaxCalculator($this->ShippingCountry->ID, $this->ShippingRegion->ID);
	}

	function GetLines() {
		$this->Customer->Get();
		$this->Customer->Contact->Get();
		$this->Reset();

		if(!empty($this->Customer->ID)) {
			$this->DiscountCollection->Get($this->Customer);
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT cbl.Basket_Line_ID, cbl.Discount, p.CacheBestCost FROM customer_basket_line AS cbl LEFT JOIN product AS p ON p.Product_ID=cbl.Product_ID WHERE cbl.Basket_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$line = new CartLine();
			$line->Get($data->Row['Basket_Line_ID']);
			$line->Tax = 0;
			$line->PriceRetail = $line->Product->PriceCurrent;
			
			$customDiscount = false;

			if(!empty($line->DiscountInformation)) {
				$discountCustom = explode(':', $line->DiscountInformation);

				if(trim($discountCustom[0]) == 'azxcustom') {
					$customDiscount = true;
				}
			}

			if($this->Customer->Contact->IsTradeAccount == 'Y') {
				$tradeCost = ($line->Product->CacheRecentCost > 0) ? $line->Product->CacheRecentCost : $line->Product->CacheBestCost;
				
				if($line->Product->ID > 0) {
					$line->Price = ContactProductTrade::getPrice($this->Customer->Contact->ID, $line->Product->ID);
					$line->Price = ($line->Price <= 0) ? $tradeCost * ((TradeBanding::GetMarkup($tradeCost, $line->Product->ID) / 100) + 1) : $line->Price;
				}
				
				$line->Discount = 0;
				$line->Total = $line->Price * $line->Quantity;
			} else {
				if($line->FreeOfCharge == 'Y') {
					$line->Total = 0;

					if($line->Product->ID > 0) {
						$line->Price = $line->Product->PriceCurrent;
						$line->Discount = 0;

					} elseif($line->IsAssociative == 'Y') {
						$line->Price = $line->Discount * -1;
					}
				} else {
					if($line->Product->ID > 0) {
						if($line->Product->PriceCurrent == $line->Product->PriceOurs){
							$priceArr = array();

							$data2 = new DataQuery(sprintf("SELECT * FROM product_prices WHERE Price_Starts_On<=NOW() AND Quantity<=%d AND Product_ID=%d ORDER BY Price_Starts_On DESC", mysql_real_escape_string($line->Quantity), mysql_real_escape_string($line->Product->ID)));
							while($data2->Row) {
								if(!isset($priceArr[$data2->Row['Quantity']])) {
									$priceArr[$data2->Row['Quantity']] = $data2->Row['Price_Base_Our'];
								}
								$data2->Next();
							}
							$data2->Disconnect();

							if(count($priceArr) > 0) {
								krsort($priceArr);
								reset($priceArr);

								$line->Price = current($priceArr);
							}
						} else {
							$line->Price = $line->Product->PriceCurrent;
						}
					} elseif($line->IsAssociative == 'Y') {
						$line->Price = $line->Discount * -1;
					}

					$line->Total = $line->Price * $line->Quantity;
				}
			}
			
			if($customDiscount) {
				$line->Discount = round(($discountCustom[1] / 100) * $line->Price, 2) * $line->Quantity;
				$this->Discount += $line->Discount;
			} else {
				if($line->Product->ID > 0) {
					$line->Discount = 0;
					$line->DiscountInformation = '';

					if(!empty($this->Coupon->ID)){
						//$couponLineTotal = $this->Coupon->DiscountProduct($this->Line[$i]->Product, $this->Line[$i]->Quantity);
						$couponLineTotal = round(($line->Price - round(($this->Coupon->Discount / 100) * $line->Price, 2)) * $line->Quantity, 2);

						if($couponLineTotal < $line->Total){
							$line->Price = ($GLOBALS['DISCOUNT_FROM_RRP']) ? $line->Product->PriceRRP : $line->Product->PriceOurs;
							$line->Total = ($line->Quantity * $line->Price);
							$line->Discount = $line->Total - $couponLineTotal;
							$line->DiscountInformation = sprintf('%s (Ref: %s)', $this->Coupon->Name, $this->Coupon->Reference);
						}
					}

					if((count($this->DiscountCollection->Line) > 0) || ($this->DiscountBandingID > 0)) {
						list($tempLineTotal, $discountName) = $this->DiscountCollection->DiscountProduct($line->Product, $line->Quantity, $this->DiscountBandingID);

						if((($line->Total - $tempLineTotal) > $line->Discount) && ($tempLineTotal > 0)) {
							$line->Price = ($GLOBALS['DISCOUNT_FROM_RRP'])? $line->Product->PriceRRP:$line->Product->PriceOurs;
							$line->Total = ($line->Quantity * $line->Price);
							$line->Discount = $line->Total - $tempLineTotal;
							$line->DiscountInformation = $discountName;
						}
					}

					$this->Discount += $line->Discount;
				}
			}

			// Discount Limit Exceeding Check
			if($line->Product->DiscountLimit != '' && ($line->Product->DiscountLimit >= 0 && $line->Product->DiscountLimit <= 100)){
				$maxDiscount = round(($line->Product->DiscountLimit / 100) * $line->Price, 2) * $line->Quantity;
				if($line->Discount > $maxDiscount){
					$this->Discount -= ($line->Discount - $maxDiscount);
					$line->Discount = $maxDiscount;
					if(strpos($line->DiscountInformation, 'azxcustom') === false && strpos($line->DiscountInformation, 'Maximum discount for this product') == false){
						$line->DiscountInformation .= sprintf(' - Maximum discount for this product is %d%%', $line->Product->DiscountLimit);
					}
				}
			}
			
			if(!empty($this->TaxExemptCode)) {
				$line->Tax = 0;
			} else {
				if($line->Product->ID > 0) {
					$line->Tax = $this->TaxCalculator->GetTax(($line->Total-$line->Discount), $line->Product->TaxClass->ID);
				} else {
					$line->Tax = $this->TaxCalculator->GetTax($line->Total);
				}
			}

			$line->Total = round($line->Total, 2);
			$line->Tax = round($line->Tax, 2);
			$line->Discount = round($line->Discount, 2);
			$line->Update();

			$this->SubTotal += $line->Total;
			$this->SubTotalRetail += $line->Price * $line->Quantity;
			$this->Weight += ($line->Quantity * $line->Product->Weight);
			$this->TaxTotal += $line->Tax;
			$this->Line[] = $line;

			$data->Next();
		}
		$data->Disconnect();

		$this->TotalLines = count($this->Line);
	}

	function GetShippingLines() {
		$this->ShippingLine = array();
		$this->ShippingMultiplier = 0;

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT CustomerBasketShippingID FROM customer_basket_shipping WHERE CustomerBasketID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->ShippingLine[] = new CartShipping($data->Row['CustomerBasketShippingID']);

			$data->Next();
		}
		$data->Disconnect();

		for($i=0; $i<count($this->ShippingLine); $i++) {
			$this->ShippingMultiplier += $this->ShippingLine[$i]->Quantity;
		}
	}

	function GetDiscount() {
	}

	function AddLine($productId, $quantity=1) {
		$this->Customer->Get();
		$this->Customer->Contact->Get();

		if($this->Customer->Contact->IsTradeAccount == 'Y') {
			if(!$this->Exists()) {
            	$this->Add();	
			}
				
			$line = new CartLine();
			$line->Product->Get($productId);
			$line->Quantity = $quantity;
			
			$tradeCost = ($line->Product->CacheRecentCost > 0) ? $line->Product->CacheRecentCost : $line->Product->CacheBestCost;
			
			$line->Price = ContactProductTrade::getPrice($this->Customer->Contact->ID, $line->Product->ID);
			$line->Price = ($line->Price <= 0) ? $tradeCost * ((TradeBanding::GetMarkup($tradeCost, $line->Product->ID) / 100) + 1) : $line->Price;
				
			$line->PriceRetail = $line->Product->PriceCurrent;
			$line->CartID = $this->ID;

	        if(($quantity >= $line->Product->OrderMin) && (($line->Product->OrderMax == 0) || (($line->Product->OrderMax > 0) && ($quantity <= $line->Product->OrderMax)))) {
				$line->Add();

				$this->Line[] = $line;

				return $line->ID;
			}
		} else {
			$priceArr = array();

			$data2 = new DataQuery(sprintf("SELECT * FROM product_prices WHERE Price_Starts_On<='%s' AND Quantity<=%d AND Product_ID=%d ORDER BY Price_Starts_On DESC", date('Y-m-d H:i:s'), mysql_real_escape_string($quantity), mysql_real_escape_string($productId)));
			while($data2->Row) {
				if(!isset($priceArr[$data2->Row['Quantity']])) {
					$priceArr[$data2->Row['Quantity']] = $data2->Row['Price_Base_Our'];
				}
				$data2->Next();
			}
			$data2->Disconnect();

			if(count($priceArr) > 0) {
				krsort($priceArr);
				reset($priceArr);

				if(!$this->Exists()) {
            		$this->Add();	
				}
					
				$line = new CartLine();
				$line->Product->Get($productId);
				$line->Quantity = $quantity;

				$line->Price = current($priceArr);
				$line->PriceRetail = current($priceArr);
				$line->CartID = $this->ID;

	            if(($quantity >= $line->Product->OrderMin) && (($line->Product->OrderMax == 0) || (($line->Product->OrderMax > 0) && ($quantity <= $line->Product->OrderMax)))) {
					$line->Add();

					$this->Line[] = $line;

					return $line->ID;
				}
			}
		}

		return false;
	}
	
	function ChangeLine($lineId, $productId) {
		$this->Customer->Get();
		$this->Customer->Contact->Get();
		
		$line = new CartLine($lineId);
		$line->OriginalProduct->ID = $line->Product->ID;
		$line->Product->Get($productId);
		$line->Price = 0;
		$line->PriceRetail = 0;
		$line->Discount = 0;
		$line->DiscountInformation = '';
		$line->HandlingCharge = 0;
		$line->IncludeDownloads = 'N';
		$line->FreeOfCharge = 'N';
		$line->AssociativeProductTitle = '';
			
		if($this->Customer->Contact->IsTradeAccount == 'Y') {
			if(!$this->Exists()) {
            	$this->Add();	
			}

			$tradeCost = ($line->Product->CacheRecentCost > 0) ? $line->Product->CacheRecentCost : $line->Product->CacheBestCost;
			
			$line->Price = ContactProductTrade::getPrice($this->Customer->Contact->ID, $line->Product->ID);
			$line->Price = ($line->Price <= 0) ? $tradeCost * ((TradeBanding::GetMarkup($tradeCost, $line->Product->ID) / 100) + 1) : $line->Price;
				
			$line->PriceRetail = $line->Product->PriceCurrent;
		} else {
			$priceArr = array();

			$data2 = new DataQuery(sprintf("SELECT * FROM product_prices WHERE Price_Starts_On<='%s' AND Quantity<=%d AND Product_ID=%d ORDER BY Price_Starts_On DESC", date('Y-m-d H:i:s'), $quantity, $productId));
			while($data2->Row) {
				if(!isset($priceArr[$data2->Row['Quantity']])) {
					$priceArr[$data2->Row['Quantity']] = $data2->Row['Price_Base_Our'];
				}
				$data2->Next();
			}
			$data2->Disconnect();

			if(count($priceArr) > 0) {
				krsort($priceArr);
				reset($priceArr);

				if(!$this->Exists()) {
            		$this->Add();	
				}
					
				$line->Price = current($priceArr);
				$line->PriceRetail = current($priceArr);
			}
		}
		
		$line->Update();
	}

	function Update(){
		if(!$this->Exists()) {
			$this->Add();	
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("UPDATE customer_basket SET CSID='%s', Prefix='%s', Quote_ID=%d, Customer_ID=%d, Coupon_ID=%d, Country_ID=%d, Region_ID=%d, Shipping_Option_ID=%d, Ship_To='%s', TotalShipping=%f, IsCustomShipping='%s', TaxExemptCode='%s', Free_Text='%s', Free_Text_Value=%f, Discount_Banding_ID=%d, Discount_Banding_Offered='%s' WHERE Basket_ID=%d",
		mysql_real_escape_string($this->CSID),
		mysql_real_escape_string($this->Prefix),
		mysql_real_escape_string($this->QuoteID),
		mysql_real_escape_string($this->Customer->ID),
		mysql_real_escape_string($this->Coupon->ID),
		mysql_real_escape_string($this->ShippingCountry->ID),
		mysql_real_escape_string($this->ShippingRegion->ID),
		mysql_real_escape_string($this->Postage),
		mysql_real_escape_string($this->ShipTo),
		mysql_real_escape_string($this->ShippingTotal),
		mysql_real_escape_string($this->IsCustomShipping),
		mysql_real_escape_string($this->TaxExemptCode),
		mysql_real_escape_string($this->FreeText),
		mysql_real_escape_string($this->FreeTextValue),
		mysql_real_escape_string($this->DiscountBandingID),
		mysql_real_escape_string($this->DiscountBandingOffered),
		mysql_real_escape_string($this->ID)));
	}

	function GenerateFromQuote(&$quote){
		$quote->GetLines();
		$quote->Customer->Get();
		$quote->Customer->Contact->Get();
		$quote->Customer->Contact->Person->Get();

		$this->QuoteID = $quote->ID;
		$this->Prefix = $quote->Prefix;

		$sql = "SELECT cc.Customer_Contact_ID FROM customer_contact AS cc
				   INNER JOIN address as a ON cc.Address_ID = a.Address_ID
				   INNER JOIN customer as cu ON cc.Customer_ID = cu.Customer_ID
				   WHERE a.Address_Line_1 LIKE '{$quote->Shipping->Address->Line1}'
				   AND Zip LIKE '{$quote->Shipping->Address->Zip}'
				   AND cc.Customer_ID = {$quote->Customer->ID}";
		$data = new DataQuery($sql);
		$data->Disconnect();
		$this->ShipTo = $data->Row['Customer_Contact_ID'];
		$data->Disconnect();

		$this->Coupon->ID = $quote->Coupon->ID;
		$this->Total = $quote->Total;
		$this->TaxExemptCode = $quote->TaxExemptCode;
		$this->SubTotal = $quote->SubTotal;
		$this->SubTotalRetail = $quote->SubTotalRetail;
		$this->ShippingTotal = $quote->ShippingTotal;
		$this->Discount = $quote->TotalDiscount;
		$this->TaxTotal = $quote->TotalTax;
		$this->TotalLines = $quote->TotalLines;
		$this->IsCustomShipping = $quote->IsCustomShipping;
		$this->Weight = $quote->Weight;
		$this->Customer = $quote->Customer;
		$this->Customer->Contact = $quote->Customer->Contact;
		$this->Customer->Contact->Person = $quote->Customer->Contact->Person;
		$this->BillingCountry->ID = $this->Customer->Contact->Person->Address->Country->ID;
		$this->BillingRegion->ID = $this->Customer->Contact->Person->Address->Region->ID;
		$this->ShippingCountry->ID = $quote->Shipping->Address->Country->ID;
		$this->ShippingRegion->ID = $quote->Shipping->Address->Region->ID;
		$this->PostageOptions = $quote->PostageOptions;
		$this->FoundPostage = $quote->FoundPostage;
		$this->Postage = $quote->Postage->ID;
		$this->Update();

		for($i=0; $i < count($quote->Line); $i++){
			$line = new CartLine();
			$line->CartID = $this->ID;
			$line->Product->ID = $quote->Line[$i]->Product->ID;
			$line->Product->Get();
			$line->Product->Name = $quote->Line[$i]->Product->Name;
			$line->Quantity = $quote->Line[$i]->Quantity;
			$line->Price = $quote->Line[$i]->Price;
			$line->PriceRetail = $quote->Line[$i]->PriceRetail;
			$line->HandlingCharge = $cart->Line[$i]->HandlingCharge;
			$line->IncludeDownloads = $cart->Line[$i]->IncludeDownloads;
			$line->Total = $quote->Line[$i]->Total;
			$line->Discount = $quote->Line[$i]->Discount;
			$line->DiscountInformation = $quote->Line[$i]->DiscountInformation;
			$line->Tax = round($quote->Line[$i]->Tax, 2);
			$line->Add();
		}
	}

	function Delete() {

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("delete from customer_basket_shipping where CustomerBasketID=%d", mysql_real_escape_string($this->ID)));
		new DataQuery(sprintf("delete from customer_basket_line where Basket_ID=%d", mysql_real_escape_string($this->ID)));
		new DataQuery(sprintf("delete from customer_basket where Basket_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function Reset() {
		$this->Line = array();
		$this->SubTotal = 0;
		$this->SubTotalRetail = 0;
		$this->ShippingLine = array();
		$this->ShippingMultiplier = 1;
		$this->ShippingTotal = ($this->IsCustomShipping == 'N') ? 0 : $this->ShippingTotal;
		$this->TaxTotal = 0;
		$this->Total = 0;
		$this->Weight = 0;
		$this->Discount = 0;
	}

	function Calculate() {
		$this->Reset();

		if(empty($this->Line)) {
			$this->GetLines();
		}
		
		if(!empty($this->Line)) {
			if(!empty($this->Coupon->ID)){
				if(empty($this->Coupon->Name)) {
					$this->Coupon->Get();
				}
			}
		
			$this->CalculateWeight();

			if(empty($this->TaxExemptCode)) {
				$data = new DataQuery("SELECT Tax_Class_ID FROM tax_class WHERE Is_Default='Y'");
				if($data->TotalRows > 0) {
					$temptax = $this->TaxCalculator->GetTax($this->FreeTextValue, $data->Row['Tax_Class_ID']);
					
					$this->TaxTotal += $temptax;
				}
				$data->Disconnect();
			}
			$this->CalculateShipping();
			$this->GetLocation();
			$this->TaxTotal = round($this->TaxTotal, 2);
			$this->SubTotal += $this->FreeTextValue;
			$this->SubTotalRetail += $this->FreeTextValue;
			$this->Total = $this->TaxTotal + $this->ShippingTotal + $this->SubTotal - $this->Discount;

			if(!empty($this->Coupon->ID) && !$this->Coupon->Check($this->Coupon->Reference, $this->SubTotal, $this->Customer->ID)){
				$this->Warnings[] = 'The coupon '. $this->Coupon->Reference . ' was removed from your shopping cart for the following reasons...';

				foreach($this->Coupon->Errors as $key=>$value){
					$this->Warnings[] = $value;
				}

				$this->Coupon->ID = 0;
				$this->Update();
				$this->Reset();
				$this->Calculate();
			}
		}
	}

	function CalculateCustomTax($value = 0.00) {
		$temptax = 0.00;

		if(empty($this->TaxExemptCode)){
			$data = new DataQuery("SELECT Tax_Class_ID FROM tax_class WHERE Is_Default='Y'");
			if($data->TotalRows > 0) {
				$temptax = $this->TaxCalculator->GetTax($value, $data->Row['Tax_Class_ID']);
			}
			$data->Disconnect();
		}

		return $temptax;
	}

	function CalculateShipping() {
		if($this->ShippingCountry->AllowSales == 'N') {
			$this->Error = 'Sales Disallowed';
			$this->Errors[] = sprintf('We are unable to ship to your selected country <strong>%s</strong>.', $this->ShippingCountry->Name);

			return;
		}

		if($this->IsCustomShipping == 'N') {
			$this->ShippingCalculator = new ShippingCalculator($this->ShippingCountry->ID, $this->ShippingRegion->ID, $this->SubTotal, $this->Weight, $this->Postage);

			$associatedCounts = 0;
			$unassociatedQty = 0;

			for($i=0; $i < count($this->Line); $i++){
				if($this->Line[$i]->Product->ID > 0) {
					$this->ShippingCalculator->Add($this->Line[$i]->Quantity, $this->Line[$i]->Product->ShippingClass->ID);
					$associatedCounts++;
				} else {
					$unassociatedQty += $this->Line[$i]->Quantity;
				}
			}

			if($associatedCounts == 0) {
				$data = new DataQuery(sprintf("select Shipping_Class_ID from shipping_class where Is_Default='Y'"));
				if($data->TotalRows > 0) {
					$this->ShippingCalculator->Add($unassociatedQty, $data->Row['Shipping_Class_ID']);
				}
				$data->Disconnect();
			}

			$this->ShippingCalculator->GetLimitations();

			new DataQuery(sprintf("DELETE FROM customer_basket_shipping WHERE CustomerBasketID=%d", $this->ID));

			$data = new DataQuery(sprintf("SELECT sl.Weight FROM shipping_limit AS sl INNER JOIN geozone AS g ON g.Geozone_ID=sl.Geozone_ID INNER JOIN geozone_assoc AS ga ON ga.Geozone_ID=g.Geozone_ID AND ((ga.Country_ID=%d AND ga.Region_ID=%d) OR (ga.Country_ID=%d AND ga.Region_ID=0) OR (ga.Country_ID=0)) WHERE sl.Weight<%f AND sl.Postage_ID=%d ORDER BY sl.Weight ASC LIMIT 0, 1", mysql_real_escape_string($this->ShippingCountry->ID), mysql_real_escape_string($this->ShippingRegion->ID), mysql_real_escape_string($this->ShippingCountry->ID), mysql_real_escape_string($this->Weight), mysql_real_escape_string($this->Postage)));
			if($data->TotalRows > 0) {
				$quantity = floor($this->Weight / $data->Row['Weight']);

				if($quantity >= 1) {
					$shippingCalculator = new ShippingCalculator($this->ShippingCountry->ID, $this->ShippingRegion->ID, $this->SubTotal, $data->Row['Weight'], $this->Postage);

					$associatedCounts = 0;
					$unassociatedQty = 0;

					for($i=0; $i < count($this->Line); $i++){
						if($this->Line[$i]->Product->ID > 0) {
							$shippingCalculator->Add($this->Line[$i]->Quantity, $this->Line[$i]->Product->ShippingClass->ID);
							$associatedCounts++;
						} else {
							$unassociatedQty += $this->Line[$i]->Quantity;
						}
					}

					if($associatedCounts == 0) {
						$data = new DataQuery(sprintf("select Shipping_Class_ID from shipping_class where Is_Default='Y'"));
						if($data->TotalRows > 0) {
							$shippingCalculator->Add($unassociatedQty, $data->Row['Shipping_Class_ID']);
						}
						$data->Disconnect();
					}

					$this->AddShipping($data->Row['Weight'], $quantity, $shippingCalculator->GetTotal());
				}

				$weight = $this->Weight - ($data->Row['Weight'] * $quantity);

				if($weight > 0) {
					$shippingCalculator = new ShippingCalculator($this->ShippingCountry->ID, $this->ShippingRegion->ID, $this->SubTotal, $weight, $this->Postage);

					$associatedCounts = 0;
					$unassociatedQty = 0;

					for($i=0; $i < count($this->Line); $i++){
						if($this->Line[$i]->Product->ID > 0) {
							$shippingCalculator->Add($this->Line[$i]->Quantity, $this->Line[$i]->Product->ShippingClass->ID);
							$associatedCounts++;
						} else {
							$unassociatedQty += $this->Line[$i]->Quantity;
						}
					}

					if($associatedCounts == 0) {
						$data = new DataQuery(sprintf("select Shipping_Class_ID from shipping_class where Is_Default='Y'"));
						if($data->TotalRows > 0) {
							$shippingCalculator->Add($unassociatedQty, $data->Row['Shipping_Class_ID']);
						}
						$data->Disconnect();
					}

					$this->AddShipping($weight, 1, $shippingCalculator->GetTotal());
				}
			} else {
				$this->AddShipping($this->Weight, 1, $this->ShippingCalculator->GetTotal());
			}
			$data->Disconnect();

			$this->GetShippingLines();

			$this->ShippingTotal = 0;

			for($i=0; $i<count($this->ShippingLine); $i++) {
				$this->ShippingTotal += $this->ShippingLine[$i]->Charge * $this->ShippingLine[$i]->Quantity;
			}

			$this->PostageOptions = $this->ShippingCalculator->GetOptions(true);
			$this->Geozone = $this->ShippingCalculator->Geozone;

			$this->Error = $this->ShippingCalculator->Error;

			foreach($this->ShippingCalculator->Errors as $key=>$value){
				$this->Errors[] = $value;
			}

			$this->Warning = $this->ShippingCalculator->Warning;
			$this->Warnings = array_merge($this->Warnings, $this->ShippingCalculator->Warnings);
			$this->FoundPostage = $this->ShippingCalculator->FoundPostage;
		} else {
			$now = getDatetime();
			$dateSplit = explode(' ', $now);
			$today = $dateSplit[0];
			$this->PostageOptions = '<select style="width:100%;" name="deliveryOption" onChange="changeDelivery(this.value);"><option value="">Select Postage</option>';
			$data = new DataQuery("select * from postage order by Postage_Title");
			while($data->Row){
				//$postageDate = $today . ' ' . $data->Row['Cutt_Off_Time'] . ':00';
				/*$secDiff = dateDiff($postageDate, $now, 's');
				if($data->Row['Cutt_Off_Time'] == '00:00' || $secDiff <= 0){*/
				$checked = ($data->Row['Postage_ID'] == $this->Postage)?'selected="selected"':'';
				$this->PostageOptions .= sprintf('<option value="%d" %s >%s</option>', $data->Row['Postage_ID'], $checked, $data->Row['Postage_Title']);
				//}
				$data->Next();
			}
			$data->Disconnect();

			$this->PostageOptions .= '</select>';
		}

		if(empty($this->TaxExemptCode)) {
			$currentTaxTotal = $this->TaxTotal;

			foreach($this->ShippingCalculator->AvailablePostage as $key=>$postage){
				$shipTax = new TaxCalculator($postage->Total, $this->ShippingCountry->ID, $this->ShippingRegion->ID, $GLOBALS['DEFAULT_TAX_ON_SHIPPING']);
				$this->ShippingCalculator->AvailablePostage[$key]->OrderTax = $currentTaxTotal + $shipTax->Tax;
			}

			$shipTax = new TaxCalculator($this->ShippingTotal, $this->ShippingCountry->ID, $this->ShippingRegion->ID, $GLOBALS['DEFAULT_TAX_ON_SHIPPING']);
			$this->TaxTotal += $shipTax->Tax;
		}
	}

	private function CalculateWeight() {
		$this->Weight = 0;

		$totalArea = 0;

		for($i=0; $i<count($this->Line); $i++) {
			$this->Weight += $this->Line[$i]->Product->Weight * $this->Line[$i]->Quantity;

			$totalArea += ($this->Line[$i]->Product->Width * $this->Line[$i]->Product->Height * $this->Line[$i]->Product->Depth) * $this->Line[$i]->Quantity;
		}

		$package = array();

		$data = new DataQuery(sprintf("SELECT Weight, (Width*Height*Depth/100) * (100 - Reduction_Percent) AS Available_Area FROM package ORDER BY Available_Area ASC"));
		while($data->Row) {
			$package[] = $data->Row;

			$data->Next();
		}
		$data->Disconnect();

		$this->Weight += $this->GetPackageWeight($package, $totalArea);
	}

	private function GetPackageWeight($package = array(), $area = 0) {
		$weight = 0;

		for($i=0; $i<count($package); $i++) {
			if($area <= $package[$i]['Available_Area']) {
				$weight += $package[$i]['Weight'];
				break;
			} elseif($i == (count($package) - 1)) {
				$units = floor($area / $package[$i]['Available_Area']);
				$weight += $package[$i]['Weight'] * $units;

				$remaining = $area - ($package[$i]['Available_Area'] * $units);

				$weight += $this->GetPackageWeight($package, $remaining);
			}
		}

		return $weight;
	}

	function PostageMessages(){
		$messages = '';

		$postage = new Postage($this->Postage);

		$now = getDatetime();
		$dateSplit = explode(' ', $now);
		$today = $dateSplit[0];
		$postageDate = $today . ' ' .$postage->CuttOffTime . ':00';
		$secDiff = dateDiff($postageDate, $now, 's');

		if(($postage->CuttOffTime != '00:00') && ($secDiff > 0)){
			$messages .= '<div class="attention">';
			$messages .= '<div class="attention-icon attention-icon-warning"></div>';
			$messages .= '<div class="attention-info attention-info-warning">';
			$messages .= '<span class="attention-info-title">Delivery Alert</span><br />';
			$messages .= $postage->Message;
			$messages .= '</div>';
			$messages .= '</div>';
		}

		return $messages;
	}

	function GetLocation() {
		$this->Location = $this->ShippingCountry->Name;

		if($this->ShippingRegion->ID > 0) {
			$this->Location .= ', ' . $this->ShippingRegion->Name;
		}
	}

	function HasDangerousItems() {
		$isDangerous = false;

		for($i=0; $i < count($this->Line); $i++) {
			if($this->Line[$i]->Product->ID > 0) {
				if($this->Line[$i]->Product->IsDangerous == 'Y') {
					$isDangerous = true;
					break;
				}
			}
		}

		return $isDangerous;
	}

	private function AddShipping($weight, $quantity, $charge) {
		$shipping = new CartShipping();
		$shipping->CartID = $this->ID;
		$shipping->Weight = $weight;
		$shipping->Quantity = $quantity;
		$shipping->Charge = $charge;
		$shipping->Add();
	}
	
	function removeLines(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("delete from customer_basket_line where Basket_ID=%d", mysql_real_escape_string($this->ID)));
		return true;
	}

	function ContactMade() {
		if(!is_numeric($this->ID)){
			return false;
		}

		$this->ContactedOn = date("Y-m-d H:i:s");

		new DataQuery(sprintf("update customer_basket set Contacted_On = '%s' where Basket_ID = %d", mysql_real_escape_string($this->ContactedOn), mysql_real_escape_string($this->ID)));
		return true;
	}
}