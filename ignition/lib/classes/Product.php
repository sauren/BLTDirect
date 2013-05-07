<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/EmailQueue.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Manufacturer.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductDownload.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductBand.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductImage.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductImageExample.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductLink.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductOptionGroupCollection.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductOption.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductInCategory.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductReview.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductOptionGroup.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductPrice.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductRelated.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductSpec.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductOffers.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductComponent.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ShippingClass.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/TaxCalculator.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/TaxClass.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/SupplierProduct.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/WarehouseStock.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductInCategory.php");

class Product {
	var $ID;
	public $LockedSupplierID;
	public $DropSupplierID;
	public $DropSupplierExpiresOn;
	public $DropSupplierQuantity;
	public $DropSupplierReserved;
	public $SpecialOrderSupplierID;
	public $SpecialOrderLeadDays;
	var $InternalBarcode;
	var $BarcodesApplicable;
	var $Quality;
	var $QualityText;
	var $Type;
	var $IntegrationID;
	var $SKU;
	var $Name;
	var $HTMLTitle;
	var $Manufacturer;
	var $Model;
	var $Variant;
	var $Weight;
	var $Width;
	var $Height;
	var $Depth;
	var $Volume;
	var $UnitsPerPallet;
	var $DiscontinuedOn;
	var $DiscontinuedBy;
	var $DiscontinuedBecause;
	var $DiscontinuedShowPrice;
	var $DiscontinuedDealt;
	var $Guarantee;
	var $Status;
	var $DefaultImage;
	var $OrderRule;
	var $PositionQuantities;
	var $PositionQuantitiesRecent;
	var $PositionQuantities3Month;
	var $PositionQuantities12Month;
	var $PositionOrders;
	var $PositionOrdersRecent;
	var $PositionOrders3Month;
	var $PositionOrders12Month;
	var $TotalQuantities;
	var $TotalQuantities3Month;
	var $TotalQuantities12Month;
	var $TotalOrders;
	var $TotalOrders3Month;
	var $TotalOrders12Month;
	var $AverageDespatch;
	var $SimilarText;
	var $SupersededBy;

	// Pricing
	var $PriceRRP;
	var $PriceOurs;
	var $PriceCurrent;
	var $PriceCurrentIncTax;
	var $PriceOffer;
	var $PriceSaving;
	var $PriceSavingPercent;
	var $TaxIncluded;
	var $PriceStatus;
	var $DiscountLimit;

	var $Description;
	var $Blurb;
	var $Codes;
	var $GoogleBaseSuffix;
	var $MetaTitle;
	var $MetaKeywords;
	var $MetaDescription;

	// Tracking
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	// Check Sales of Product
	var $IsActive;
	var $IsBestSeller;
	var $Discontinued;
	var $OrderMin;
	var $OrderMax;
	var $SalesStart;
	var $SalesEnd;

	// Used to alert possible import errors
	var $DataImported;

	// Used for Stock Control
	var $Stocked;
	var $StockedTemporarily;
	var $StockImported;
	var $StockMonitor;
	var $StockSuspend;
	var $StockAlert;
	var $StockReorderQuantity;
	var $Alerted;

	// Used for Tax and Shipping
	var $TaxClass;
	var $ShippingClass;
	var $DespatchDaysMin;
	var $DespatchDaysMax;

	var $Options;
	public $Download;
	public $DownloadsFetched;
	public $Image;
	public $ImagesFetched;
	public $Example;
	public $ExamplesFetched;
	public $LinkObject;
	public $LinkObjectsFetched;
	public $Spec;
	public $SpecsFetched;
	public $Related;
	public $RelatedFetched;
	public $RelatedType;
	public $Component;
	public $ComponentsFetched;
	public $Review;
	public $ReviewAverage;
	public $ReviewsFetched;
	public $ReviewObject;
	public $ReviewObjectsFetched;
	public $AlternativeCode;
	public $AlternativeCodesFetched;
	public $Barcode;
	public $BarcodesFetched;
	public $QualityLink;
	public $QualityLinksFetched;
	public $QualityLinkType;
	var $SpecCache;
	var $SpecCachePrimary;
	var $CacheBestCost;
	var $CacheBestSupplierID;
	var $CacheRecentCost;
	var $Band;
	var $BypassTax;
	var $ServiceDuration;
	var $IsDemo;
	var $DemoNotes;
	var $IsDemoProcessed;
	var $IsNonReturnable;
	var $IsDangerous;
	var $IsProfitControl;
	var $IsAutomaticReview;
	var $AssociativeProductTitle;
	var $IsComplementary;
	var $IsWarehouseShipped;

	function Product($id=NULL){
		$this->DropSupplierExpiresOn = '0000-00-00 00:00:00';
		$this->DropSupplierReserved = 'N';
		$this->Type = 'S';
		$this->Stocked = 'N';
		$this->StockedTemporarily = 'N';
		$this->StockImported = 'N';
		$this->StockMonitor = 'N';
		$this->TaxIncluded = 'N';
		$this->DataImported = 'N';
		$this->IsActive = 'Y';
		$this->IsBestSeller = 'N';
		$this->Discontinued = 'N';
		$this->Width = 0;
		$this->Height = 0;
		$this->Depth = 0;
		$this->Volume = 0;
		$this->Weight = 0;
		$this->Manufacturer = new Manufacturer;
		$this->DefaultImage = new ProductImage;
		$this->ShippingClass = new ShippingClass;
		$this->TaxClass = new TaxClass;
		$this->Band = new ProductBand;
		$this->DiscontinuedOn = '0000-00-00 00:00:00';
		$this->DiscontinuedShowPrice = 'N';
		$this->DiscontinuedDealt = 'N';
		$this->SalesEnd = '0000-00-00 00:00:00';
		$this->SalesStart = '0000-00-00 00:00:00';
		$this->Download = array();
		$this->DownloadsFetched = false;
		$this->Image = array();
		$this->ImagesFetched = false;
		$this->Example = array();
		$this->ExamplesFetched = false;
		$this->LinkObject = array();
		$this->LinkObjectsFetched = false;
		$this->Spec = array();
		$this->SpecsFetched = false;
		$this->Related = array();
		$this->RelatedFetched = false;
		$this->RelatedType = array();
		$this->Component = array();
		$this->ComponentsFetched = false;
		$this->Review = array();
		$this->ReviewsFetched = false;
		$this->AlternativeCode = array();
		$this->AlternativeCodesFetched = false;
		$this->Barcode = array();
		$this->BarcodesFetched = false;
		$this->QualityLink = array();
		$this->QualityLinksFetched = false;
		$this->BypassTax = false;
		$this->OrderRule = 'M';
		$this->IsDemo = 'N';
		$this->IsDemoProcessed = 'N';
		$this->IsNonReturnable = 'N';
		$this->IsDangerous = 'N';
		$this->IsProfitControl = 'N';
		$this->IsAutomaticReview = 'N';
		$this->IsComplementary = 'N';
		$this->IsWarehouseShipped = 'N';
		$this->GoogleBaseSuffix = 'Light Bulb';

		if(isset($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL, $connection = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		if($this->ID <= 0) {
			return false;
		}
		
		/*
		$cache = Zend_Cache::factory('Output', $GLOBALS['CACHE_BACKEND'], array('lifetime' => 86400, 'automatic_serialization' => true));
		
		$cacheId = 'product__' . $this->ID;
		$cacheData = array();
		
		$cache2 = Zend_Cache::factory('Output', $GLOBALS['CACHE_BACKEND'], array('lifetime' => 3600, 'automatic_serialization' => true));
		
		$cacheId2 = 'product_prices__product_id__' . $this->ID;
		$cacheData2 = array();

		if(($cacheData = $cache->load($cacheId)) === false) {
			$cacheData = array();
		*/	
			$sql = sprintf("select
				p.*,
				m.Manufacturer_Name,
				m.Manufacturer_Image,
				m.Manufacturer_URL,
				t.Tax_Class_Title,
				t.Tax_Class_Description,
				t.Is_Default,
				s.Shipping_Class_Title,
				s.Shipping_Class_Description,
				pi.Product_Image_ID,
				pi.Is_Primary,
				pi.Image_Thumb,
				pi.Image_Thumb_Width,
				pi.Image_Thumb_Height,
				pi.Image_Src,
				pi.Image_Src_Width,
				pi.Image_Src_Height,
				pi.Image_Title,
				pi.Image_Description,
				pp.Product_Price_ID,
				pp.Price_Base_Our,
				pp.Price_Base_RRP,
				pp.Price_Starts_On,
				po.Product_Offer_ID,
				po.Price_Offer,
				po.Offer_Start_On,
				po.Offer_End_On,
				pb.Product_Band_ID,
				pb.Band_Title,
				pb.Band_Ref,
				pb.Band_Description
				FROM
				product AS p
				left join manufacturer as m on p.Manufacturer_ID=m.Manufacturer_ID
				left join tax_class as t on p.Tax_Class_ID=t.Tax_Class_ID
				left join shipping_class as s on p.Shipping_Class_ID=s.Shipping_Class_ID
				left join product_images as pi on pi.Product_ID=p.Product_ID and pi.Is_Active='Y'
				left join product_prices as pp on pp.Product_ID=p.Product_ID and pp.Price_Starts_On<=NOW() AND pp.Quantity=1
				left join product_offers as po on po.Product_ID=p.Product_ID and ((po.Offer_Start_On<=NOW() AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On='000-00-00 00:00:00') OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On<=NOW() AND po.Offer_End_On='0000-00-00 00:00:00'))
				left join product_band as pb on pb.Product_Band_ID=p.Product_Band_ID
				where
				p.Product_ID=%d
				order by
				pi.Is_Primary asc,
				pp.Price_Starts_On desc,
				po.Offer_Start_On desc
				LIMIT 0, 1", mysql_real_escape_string($this->ID));
	
			$data = new DataQuery($sql, $connection);
			$data->Disconnect();

			if($data->TotalRows == 0) {
				return false;
			}
			$cacheData = $data->Row;
		/*	
			$cache->save($cacheData, $cacheId);
		}
		
		if(($cacheData2 = $cache2->load($cacheId2)) === false) {
			$cacheData2 = array();
		
			$data = new DataQuery(sprintf("SELECT Quantity FROM product_prices WHERE Price_Starts_On<=NOW() AND Product_ID=%d ORDER BY Price_Starts_On DESC", $this->ID));
			while($data->Row) {
				$cacheData2[$data->Row['Quantity']] = true;

				$data->Next();
			}
			$data->Disconnect();

			$cache2->save($cacheData2, $cacheId2);
		}

		*/

		$this->LockedSupplierID = isset($cacheData["LockedSupplierID"]) ? $cacheData["LockedSupplierID"] : 0;
		$this->DropSupplierID = isset($cacheData["DropSupplierID"]) ? $cacheData["DropSupplierID"] : 0;
		$this->DropSupplierExpiresOn = isset($cacheData["DropSupplierExpiresOn"]) ? $cacheData["DropSupplierExpiresOn"] : '0000-00-00 00:00:00';
		$this->DropSupplierQuantity = isset($cacheData["DropSupplierQuantity"]) ? $cacheData["DropSupplierQuantity"] : 0;
		$this->DropSupplierReserved  = isset($cacheData["DropSupplierReserved"]) ? $cacheData["DropSupplierReserved"] : 'N';
		$this->SpecialOrderSupplierID = isset($cacheData["SpecialOrderSupplierID"]) ? $cacheData["SpecialOrderSupplierID"] : 0;
		$this->SpecialOrderLeadDays = isset($cacheData["SpecialOrderLeadDays"]) ? $cacheData["SpecialOrderLeadDays"] : 0;		
		$this->InternalBarcode = $cacheData["Barcode"];
		$this->BarcodesApplicable = $cacheData["BarcodesApplicable"];
		$this->Quality = isset($cacheData["Quality"]) ? $cacheData["Quality"] : '';
		$this->QualityText = isset($cacheData["Quality_Text"]) ? $cacheData["Quality_Text"] : '';
		$this->IntegrationID = $cacheData["Integration_ID"];
		$this->SKU = $cacheData["SKU"];
		$this->Manufacturer->ID = $cacheData["Manufacturer_ID"];
		$this->Manufacturer->Name = $cacheData["Manufacturer_Name"];
		$this->Manufacturer->Image = $cacheData["Manufacturer_Image"];
		$this->Manufacturer->URL = $cacheData["Manufacturer_URL"];
		$this->Type = $cacheData['Product_Type'];
		$this->IsActive = $cacheData["Is_Active"];
		$this->IsBestSeller = $cacheData["Is_Best_Seller"];
		$this->Model = $cacheData["Model"];
		$this->Variant = $cacheData["Variant"];
		$this->Weight = $cacheData["Weight"];
		$this->Width = $cacheData["Shelf_Width"];
		$this->Height = $cacheData["Shelf_Height"];
		$this->Depth = $cacheData["Shelf_Depth"];
		$this->Volume = $cacheData["Volume"];
		$this->UnitsPerPallet = $cacheData["Units_Per_Pallet"];
		$this->OrderMin = $cacheData["Order_Min"];
		$this->OrderMax = $cacheData["Order_Max"];
		$this->Discontinued = $cacheData["Discontinued"];
		$this->DiscontinuedOn = $cacheData["Discontinued_On"];
		$this->DiscontinuedBy = $cacheData["Discontinued_By"];
		$this->DiscontinuedBecause = $cacheData["Discontinued_Because"];
		$this->DiscontinuedShowPrice = isset($cacheData["Discontinued_Show_Price"]) ? $cacheData["Discontinued_Show_Price"] : 'N';
		$this->DiscontinuedDealt = isset($cacheData["Discontinued_Dealt"]) ? $cacheData["Discontinued_Dealt"] : 'N';
		$this->GoogleBaseSuffix = $cacheData["Google_Base_Suffix"];
		$this->PositionQuantities = isset($cacheData["Position_Quantities"]) ? $cacheData["Position_Quantities"] : 0;
		$this->PositionQuantitiesRecent = isset($cacheData["Position_Quantities_Recent"]) ? $cacheData["Position_Quantities_Recent"] : 0;
		$this->PositionQuantities3Month = isset($cacheData["Position_Quantities_3_Month"]) ? $cacheData["Position_Quantities_3_Month"] : 0;
		$this->PositionQuantities12Month = isset($cacheData["Position_Quantities_12_Month"]) ? $cacheData["Position_Quantities_12_Month"] : 0;
		$this->PositionOrders = isset($cacheData["Position_Orders"]) ? $cacheData["Position_Orders"] : 0;
		$this->PositionOrdersRecent = isset($cacheData["Position_Orders_Recent"]) ? $cacheData["Position_Orders_Recent"] : 0;
		$this->PositionOrders3Month = isset($cacheData["Position_Orders_3_Month"]) ? $cacheData["Position_Orders_3_Month"] : 0;
		$this->PositionOrders12Month = isset($cacheData["Position_Orders_12_Month"]) ? $cacheData["Position_Orders_12_Month"] : 0;
		$this->TotalQuantities = isset($cacheData["Total_Quantities"]) ? $cacheData["Total_Quantities"] : 0;
		$this->TotalQuantities3Month = isset($cacheData["Total_Quantities_3_Month"]) ? $cacheData["Total_Quantities_3_Month"] : 0;
		$this->TotalQuantities12Month = isset($cacheData["Total_Quantities_12_Month"]) ? $cacheData["Total_Quantities_12_Month"] : 0;
		$this->TotalOrders =  isset($cacheData["Total_Orders"]) ? $cacheData["Total_Orders"] : 0;
		$this->TotalOrders3Month =  isset($cacheData["Total_Orders_3_Month"]) ? $cacheData["Total_Orders_3_Month"] : 0;
		$this->TotalOrders12Month =  isset($cacheData["Total_Orders_12_Month"]) ? $cacheData["Total_Orders_12_Month"] : 0;
		$this->AverageDespatch = isset($cacheData["Average_Despatch"]) ? $cacheData["Average_Despatch"] : 0;
		$this->SimilarText = isset($cacheData["Similar_Text"]) ? $cacheData["Similar_Text"] : '';
		$this->SupersededBy = $cacheData["Superseded_By"];
		$this->CreatedOn = $cacheData["Created_On"];
		$this->CreatedBy = $cacheData["Created_By"];
		$this->ModifiedOn = $cacheData["Modified_On"];
		$this->ModifiedBy = $cacheData["Modified_By"];
		$this->SalesStart = $cacheData["Sales_Start"];
		$this->SalesEnd = $cacheData["Sales_End"];
		$this->DataImported = $cacheData["Is_Data_Imported"];
		$this->Stocked = isset($cacheData["Is_Stocked"]) ? $cacheData["Is_Stocked"] : 'N';
		$this->StockedTemporarily = isset($cacheData["Is_Stocked_Temporarily"]) ? $cacheData["Is_Stocked_Temporarily"] : 'N';
		$this->StockImported = $cacheData["Is_Imported"];
		$this->StockMonitor = $cacheData["Monitor_Stock"];
		$this->StockSuspend = $cacheData["Stock_Level_Suspend"];
		$this->StockAlert = $cacheData["Stock_Level_Alert"];
		$this->StockReorderQuantity = $cacheData["Stock_Reorder_Quantity"];
		$this->Alerted = $cacheData["Sent_Alert"];
		$this->TaxClass->ID = $cacheData["Tax_Class_ID"];
		$this->TaxClass->Name = $cacheData["Tax_Class_Title"];
		$this->TaxClass->Description = $cacheData["Tax_Class_Description"];
		$this->TaxClass->IsDefault = $cacheData["Is_Default"];
		$this->ShippingClass->ID = $cacheData["Shipping_Class_ID"];
		$this->ShippingClass->Name = $cacheData["Shipping_Class_Title"];
		$this->ShippingClass->Description = $cacheData["Shipping_Class_Description"];
		$this->DespatchDaysMin = $cacheData["Estimated_Despatch_Days_Min"];
		$this->DespatchDaysMax = $cacheData["Estimated_Despatch_Days_Max"];
		$this->Guarantee = $cacheData["Guarantee_Days"];
		$this->OrderRule = $cacheData["Order_Rule"];
		// Get product descriptions
		$this->Name = strip_tags($cacheData["Product_Title"]);
		$this->HTMLTitle = preg_replace('/<\/p>$/i', '', preg_replace('/^<p[^>]*>/i', '', $cacheData["Product_Title"]));

		$this->Description = $cacheData["Product_Description"];
		$this->Blurb = $cacheData["Product_Blurb"];
		$this->Codes = $cacheData["Product_Codes"];
		$this->MetaTitle = $cacheData["Meta_Title"];
		$this->MetaDescription = $cacheData["Meta_Description"];
		$this->MetaKeywords = $cacheData["Meta_Keywords"];

		// Set Default Image
		$this->DefaultImage->Thumb->SetName($cacheData["Image_Thumb"]);
		$this->DefaultImage->Thumb->Width = $cacheData["Image_Thumb_Width"];
		$this->DefaultImage->Thumb->Height = $cacheData["Image_Thumb_Height"];
		$this->DefaultImage->Large->SetName($cacheData["Image_Src"]);
		$this->DefaultImage->Large->Width = $cacheData["Image_Src_Width"];
		$this->DefaultImage->Large->Height = $cacheData["Image_Src_Height"];
		$this->DefaultImage->Name = $cacheData["Image_Title"];
		$this->DefaultImage->Description = $cacheData["Image_Description"];

		// Set Price
		$this->PriceRRP = $cacheData["Price_Base_RRP"];
		$this->PriceOurs = $cacheData["Price_Base_Our"];
		$this->PriceOffer = $cacheData["Price_Offer"];

		// Set Cache
		$this->SpecCache = $cacheData["Cache_Specs"];
		$this->SpecCachePrimary = isset($cacheData["Cache_Specs_Primary"]) ? $cacheData["Cache_Specs_Primary"] : '';
		$this->CacheBestCost = isset($cacheData["CacheBestCost"]) ? $cacheData["CacheBestCost"] : 0;
		$this->CacheBestSupplierID = isset($cacheData["CacheBestSupplierID"]) ? $cacheData["CacheBestSupplierID"] : 0;
		$this->CacheRecentCost = isset($cacheData["CacheRecentCost"]) ? $cacheData["CacheRecentCost"] : 0;
		
		// Set Band
		$this->Band->ID = $cacheData["Product_Band_ID"];
		$this->Band->Name = $cacheData["Band_Title"];
		$this->Band->Reference = $cacheData["Band_Ref"];
		$this->Band->Description = $cacheData["Band_Description"];

		$this->ServiceDuration = $cacheData['Service_Duration'];

		$this->IsDemo = $cacheData['Is_Demo_Product'];
		$this->DemoNotes = $cacheData['Demo_Notes'];
		$this->IsDemoProcessed = $cacheData['Is_Demo_Processed'];
		$this->IsNonReturnable = $cacheData['Is_Non_Returnable'];
		$this->IsDangerous = $cacheData['Is_Dangerous'];
		$this->IsProfitControl = $cacheData['Is_Profit_Control'];
		$this->IsAutomaticReview = $cacheData['Is_Automatic_Review'];
		$this->AssociativeProductTitle = $cacheData['Associative_Product_Title'];
		$this->IsComplementary = $cacheData['Is_Complementary'];
		$this->IsWarehouseShipped = $cacheData['Is_Warehouse_Shipped'];

		$this->DiscountLimit = $cacheData['DiscountLimit'];

		$this->GetPrice($connection);
		//$this->GetDefaultImage();

		if(count($cacheData2) > 0) {
			ksort($cacheData2);
			reset($cacheData2);

			if($this->OrderMin < key($cacheData2)) {
				$this->OrderMin = key($cacheData2);
			}
		}
			
		return true;
	}

	function GetStatus(){
		/*
		Check in the following order:
		* Dormant - checks Is_Active to see if the item has been temporarily disabled.
		* Discontinued - whether this product has been discontinued
		* Expired - whether the product's sales has expired
		* Pending - checks Sales_Start if the product is not yet on sale
		* Suspended - checks if stocked that stock is ok
		* Active - whether the product is active or not
		*/

		if($this->IsActive == 'N'){
			$this->Status = 'Dormant';
			return 'Dormant';
		}

		if($this->Discontinued == 'Y'){
			$this->Status = 'Discontinued';
			return 'Discontinued';
		}

		if(isDatetime($this->SalesStart)
		&& (dateDiff($this->SalesStart, getDatetime(), 's') < 0)){
			$this->Status = 'Pending';
			return 'Pending';
		}

		if(isDatetime($this->SalesEnd)
		&& (DateDiff(getDatetime(), $this->SalesEnd, 's') < 0)){
			$this->Status = 'Expired';
			return 'Expired';
		}

		$this->Status = 'Active';
		return 'Active';
	}

	function GetDespatchInfo(){
		$this->GetStatus();
		if(strtolower($this->Status) == 'active'){
			if($this->DespatchDaysMin > 0
			&& $this->DespatchDaysMax > 0
			&& $this->DespatchDaysMax > $this->DespatchDaysMin){
				return sprintf("Usually Ships between %s and %s days", $this->DespatchDaysMin, $this->DespatchDaysMax);
			} elseif($this->DespatchDaysMin > 0
			&& (empty($this->DespatchDaysMax)
			|| $this->DespatchDaysMax == $this->DespatchDaysMin))
			{
				return sprintf("Usually Ships within %s days",
				$this->DespatchDaysMin);
			}
		} elseif( strtolower($this->Status) == 'suspended'){
			return "This item is temporarily suspended";
		} elseif( strtolower($this->Status) == 'expired'){
			return "We no longer sell this item";
		} elseif( strtolower($this->Status) == 'discontinued'){
			return "This item has been discontinued";
		}
		return '';
	}

	function GetBuyIt($cat=0, $small=false, $url=NULL){
		if(strtolower($this->GetStatus()) == 'active'){
			$tempTxt = 	sprintf("<form name=\"buyIt\" action=\"%s\" method=\"post\">", (!is_null($url)) ? $url : 'customise.php');
			$tempTxt .= "<input type=\"hidden\" name=\"action\" value=\"customise\" />";
			$tempTxt .= sprintf("<input type=\"hidden\" name=\"direct\" value=\"%s\" />", urlencode($_SERVER['REQUEST_URI']));
			$tempTxt .= sprintf("<input type=\"hidden\" name=\"product\" value=\"%d\" />", $this->ID);
			$tempTxt .= sprintf("<input type=\"hidden\" name=\"category\" value=\"%d\" />", $cat);
			$tempTxt .= sprintf("<input type=\"text\" name=\"quantity\" value=\"%s\" size=\"%d\" maxlength=\"4\" />", ($this->OrderMin > 0) ? $this->OrderMin : 1, ($small) ? 1 : 3);
			$tempTxt .= " <input type=\"submit\" name=\"buy\" value=\"buy\" class=\"submit\" /></form>";

			return $tempTxt;
		}

		return '&nbsp;';
	}

	function GetPrice($connection = null){
		if(!empty($this->PriceOffer) && $this->PriceOffer > 0){
			$this->PriceCurrent = $this->PriceOffer;
			$this->PriceStatus = 'Offer';
		} else {
			$this->PriceStatus = 'Standard';
			$this->PriceCurrent = $this->PriceOurs;
		}

		$this->PriceCurrent = (float) $this->PriceCurrent;
		
		if($this->PriceRRP > 0) {
			$this->PriceSaving = $this->PriceRRP - $this->PriceCurrent;
			$this->PriceSavingPercent = round(($this->PriceSaving / $this->PriceRRP) * 100);
		} else {
			$this->PriceSaving = 0;
			$this->PriceSavingPercent = 0;
		}

		if(!$this->BypassTax) {
			global $globalTaxCalculator;

			if(!is_object($globalTaxCalculator)){
				$globalTaxCalculator = new TaxCalculator($this->PriceCurrent, $GLOBALS['SYSTEM_COUNTRY'], $GLOBALS['SYSTEM_REGION'], $this->TaxClass->ID, $connection);
			}

			$this->PriceCurrentIncTax = $this->PriceCurrent + $globalTaxCalculator->GetTax($this->PriceCurrent, $this->TaxClass->ID);
			$this->PriceCurrentIncTax = round($this->PriceCurrentIncTax, 2);
		}
	}

	function GetInternalBarcode() {
		return !empty($this->InternalBarcode) ? $this->InternalBarcode : $this->ID;
	}

	public function GetBestCost($quantity = 1) {
		$cost = 0;
		
		$best = $this->GetBest($quantity);
		
		if(isset($best['Cost'])) {
			$cost = $best['Cost'];
		}
		
		return $cost;
	}
	
	public function GetBestSupplierID($quantity = 1) {
		$supplierId = 0;
		
		$best = $this->GetBest($quantity);
		
		if(isset($best['Supplier_ID'])) {
			$supplierId = $best['Supplier_ID'];
		}
		
		return $supplierId;
	}
	
	public function GetBest($quantity = 1) {
		$items = array();

		$data = new DataQuery(sprintf("SELECT spp.Supplier_ID, spp.Quantity, spp.Cost, sp.Supplier_SKU FROM supplier_product_price AS spp INNER JOIN supplier_product AS sp ON sp.Supplier_ID=spp.Supplier_ID AND sp.Product_ID=spp.Product_ID WHERE spp.Product_ID=%d AND spp.Quantity<=%d ORDER BY spp.Quantity ASC, spp.Created_On ASC", $this->ID, $quantity));
		while($data->Row) {
			if($data->Row['Cost'] > 0) {
				$items[$data->Row['Supplier_ID']][$data->Row['Quantity']] = $data->Row;
			} else {
				unset($items[$data->Row['Supplier_ID']][$data->Row['Quantity']]);
			}		

			$data->Next();
		}
		$data->Disconnect();

		$results = array();

		foreach($items as $supplier) {
			foreach($supplier as $item) {
				if(!isset($results['Cost']) || ($item['Cost'] < $results['Cost'])) {
					$results['Supplier_ID'] = $item['Supplier_ID'];
					$results['Supplier_SKU'] = $item['Supplier_SKU'];
					$results['Cost'] = $item['Cost'];
					$results['Quantity'] = $item['Quantity'];
				}
			}
		}
		
		return $results;
	}
	
    function GenerateBarcode() {
		$barcode = $this->ID;
		$barcodeSize = 13;
		$multiplyOdd = 1;
		$multiplyEven = 3;

		if(($len = strlen($barcode)) < ($barcodeSize - 1)) {
			for($i=0; $i<($barcodeSize - 1)-$len; $i++) {
				$barcode = '0'.$barcode;
			}
		}

		$sum = 0;

		for($i=0; $i<($barcodeSize - 1); $i++) {
			$position = $i + 1;
			$integer = substr($barcode, $i, 1);

			if((ceil($position / 2) * 2) == $position) {
				$sum += $integer * $multiplyEven;
			} else {
				$sum += $integer * $multiplyOdd;
			}
		}

		$checkDigit = 10 - ($sum % 10);
		$checkDigit = ($checkDigit > 9) ? 0 : $checkDigit;

		$barcode .= $checkDigit;

		$this->InternalBarcode = $barcode;
	}

	function Update() {
		$this->CacheCodes();

		$sql = sprintf("UPDATE product SET
							LockedSupplierID=%d,
							DropSupplierID=%d,
							DropSupplierExpiresOn='%s',
							DropSupplierQuantity=%d,
							DropSupplierReserved='%s',
							SpecialOrderSupplierID=%d,
							SpecialOrderLeadDays=%d,
							Barcode='%s',
							BarcodesApplicable='%s',
							Quality='%s',
							Quality_Text='%s',
                            Product_Type='%s',
							SKU='%s',
                            Integration_ID=%d,
							Is_Active='%s',
							Is_Best_Seller='%s',
							Is_Demo_Product='%s',
							Demo_Notes='%s',
							Is_Demo_Processed='%s',
							Is_Non_Returnable='%s',
							Is_Dangerous='%s',
							Is_Profit_Control='%s',
							Is_Automatic_Review='%s',
							Product_Title='%s',
							Product_Description='%s',
							Product_Blurb='%s',
							Product_Codes='%s',
							Meta_Title='%s',
							Meta_Description='%s',
							Meta_Keywords='%s',
							Manufacturer_ID=%d,
							Model='%s',
							Variant='%s',
							Weight='%s',
							Shelf_Width=%f,
							Shelf_Height=%f,
							Shelf_Depth=%f,
							Volume=%f,
							Units_Per_Pallet=%d,
							Discontinued='%s',
							Discontinued_On='%s',
							Discontinued_By=%d,
							Discontinued_Because='%s',
							Discontinued_Show_Price='%s',
							Discontinued_Dealt='%s',
							Google_Base_Suffix='%s',
							Position_Quantities=%d,
							Position_Quantities_Recent=%d,
							Position_Quantities_3_Month=%d,
							Position_Quantities_12_Month=%d,
							Position_Orders=%d,
							Position_Orders_Recent=%d,
							Position_Orders_3_Month=%d,
							Position_Orders_12_Month=%d,
							Total_Quantities=%d,
							Total_Quantities_3_Month=%d,
							Total_Quantities_12_Month=%d,
							Total_Orders=%d,
							Total_Orders_3_Month=%d,
							Total_Orders_12_Month=%d,
							Average_Despatch=%d,
							Similar_Text='%s',
							Superseded_By=%d,
							Modified_On=Now(),
							Modified_By=%d,
							Sales_Start='%s',
							Sales_End='%s',
							Is_Data_Imported='N',
							Is_Imported='%s',
							Is_Stocked='%s',
							Is_Stocked_Temporarily='%s',
							Monitor_Stock='%s',
							Stock_Level_Suspend=%d,
							Stock_Level_Alert=%d,
							Stock_Reorder_Quantity=%d,
							Tax_Class_ID=%d,
							Guarantee_Days=%d,
							Estimated_Despatch_Days_Min=%d,
							Estimated_Despatch_Days_Max=%d,
							Order_Min=%d,
							Order_Max=%d,
							Shipping_Class_ID=%d,
							Cache_Specs='%s',
							Cache_Specs_Primary='%s',
							CacheBestCost=%f,
							CacheBestSupplierID=%d,
							CacheRecentCost=%f,
							Product_Band_ID=%d,
							Order_Rule='%s',
                            Service_Duration='%s',
                            Associative_Product_Title='%s',
                            Is_Complementary='%s',
                            Is_Warehouse_Shipped='%s',
                            DiscountLimit='%s'
							WHERE Product_ID=%d",
		mysql_real_escape_string($this->LockedSupplierID),
		mysql_real_escape_string($this->DropSupplierID),
		mysql_real_escape_string($this->DropSupplierExpiresOn),
		mysql_real_escape_string($this->DropSupplierQuantity),
		mysql_real_escape_string($this->DropSupplierReserved),
		mysql_real_escape_string($this->SpecialOrderSupplierID),
		mysql_real_escape_string($this->SpecialOrderLeadDays),
		mysql_real_escape_string($this->InternalBarcode),
		mysql_real_escape_string($this->BarcodesApplicable),
		mysql_real_escape_string($this->Quality),
		mysql_real_escape_string($this->QualityText),
		mysql_real_escape_string($this->Type),
		mysql_real_escape_string(stripslashes($this->SKU)),
		mysql_real_escape_string($this->IntegrationID),
		mysql_real_escape_string($this->IsActive),
		mysql_real_escape_string($this->IsBestSeller),
		mysql_real_escape_string($this->IsDemo),
		mysql_real_escape_string($this->DemoNotes),
		mysql_real_escape_string($this->IsDemoProcessed),
		mysql_real_escape_string($this->IsNonReturnable),
		mysql_real_escape_string($this->IsDangerous),
		mysql_real_escape_string($this->IsProfitControl),
		mysql_real_escape_string($this->IsAutomaticReview),
		mysql_real_escape_string(stripslashes($this->Name)),
		mysql_real_escape_string(stripslashes($this->Description)),
		mysql_real_escape_string(stripslashes($this->Blurb)),
		mysql_real_escape_string(stripslashes($this->Codes)),
		mysql_real_escape_string(stripslashes($this->MetaTitle)),
		mysql_real_escape_string(stripslashes($this->MetaDescription)),
		mysql_real_escape_string(stripslashes($this->MetaKeywords)),
		mysql_real_escape_string($this->Manufacturer->ID),
		mysql_real_escape_string(stripslashes($this->Model)),
		mysql_real_escape_string(stripslashes($this->Variant)),
		mysql_real_escape_string($this->Weight),
		mysql_real_escape_string($this->Width),
		mysql_real_escape_string($this->Height),
		mysql_real_escape_string($this->Depth),
		mysql_real_escape_string($this->Volume),
		mysql_real_escape_string($this->UnitsPerPallet),
		mysql_real_escape_string($this->Discontinued),
		mysql_real_escape_string($this->DiscontinuedOn),
		mysql_real_escape_string($this->DiscontinuedBy),
		mysql_real_escape_string(stripslashes($this->DiscontinuedBecause)),
		mysql_real_escape_string($this->DiscontinuedShowPrice),
		mysql_real_escape_string($this->DiscontinuedDealt),
		mysql_real_escape_string($this->GoogleBaseSuffix),
		mysql_real_escape_string($this->PositionQuantities),
		mysql_real_escape_string($this->PositionQuantitiesRecent),
		mysql_real_escape_string($this->PositionQuantities3Month),
		mysql_real_escape_string($this->PositionQuantities12Month),
		mysql_real_escape_string($this->PositionOrders),
		mysql_real_escape_string($this->PositionOrdersRecent),
		mysql_real_escape_string($this->PositionOrders3Month),
		mysql_real_escape_string($this->PositionOrders12Month),
		mysql_real_escape_string($this->TotalQuantities),
		mysql_real_escape_string($this->TotalQuantities3Month),
		mysql_real_escape_string($this->TotalQuantities12Month),
		mysql_real_escape_string($this->TotalOrders),
		mysql_real_escape_string($this->TotalOrders3Month),
		mysql_real_escape_string($this->TotalOrders12Month),
		mysql_real_escape_string($this->AverageDespatch),
		mysql_real_escape_string($this->SimilarText),
		mysql_real_escape_string($this->SupersededBy),
		$GLOBALS['SESSION_USER_ID'],
		mysql_real_escape_string($this->SalesStart),
		mysql_real_escape_string($this->SalesEnd),
		mysql_real_escape_string($this->StockImported),
		mysql_real_escape_string($this->Stocked),
		mysql_real_escape_string($this->StockedTemporarily),
		mysql_real_escape_string($this->StockMonitor),
		mysql_real_escape_string($this->StockSuspend),
		mysql_real_escape_string($this->StockAlert),
		mysql_real_escape_string($this->StockReorderQuantity),
		mysql_real_escape_string($this->TaxClass->ID),
		mysql_real_escape_string($this->Guarantee),
		mysql_real_escape_string($this->DespatchDaysMin),
		mysql_real_escape_string($this->DespatchDaysMax),
		mysql_real_escape_string($this->OrderMin),
		mysql_real_escape_string($this->OrderMax),
		mysql_real_escape_string($this->ShippingClass->ID),
		mysql_real_escape_string($this->SpecCache),
		mysql_real_escape_string($this->SpecCachePrimary),
		mysql_real_escape_string($this->CacheBestCost),
		mysql_real_escape_string($this->CacheBestSupplierID),
		mysql_real_escape_string($this->CacheRecentCost),
		mysql_real_escape_string($this->Band->ID),
		mysql_real_escape_string($this->OrderRule),
		mysql_real_escape_string($this->ServiceDuration),
		mysql_real_escape_string(stripslashes($this->AssociativeProductTitle)),
		mysql_real_escape_string($this->IsComplementary),
		mysql_real_escape_string($this->IsWarehouseShipped),
		mysql_real_escape_string($this->DiscountLimit),
		mysql_real_escape_string($this->ID));

		new DataQuery($sql);
		
		$this->PurgeCache();

		return true;
	}

	function Add($sync = false, $connection = null) {
		$this->CacheCodes();

		$data = new DataQuery($this->GenerateAddSQL(), $connection);

		$this->ID = $data->InsertID;
		
        $this->GenerateBarcode();
		$this->Update();

		if($this->IsDemo == 'Y') {
			$this->CreatedBy = $GLOBALS['SESSION_USER_ID'];
			$this->NotifyCreation();
		}

		CacheFile::delete('index_recent');
		
		return true;
	}

	function NotifyCreation() {
		$user = new User($this->CreatedBy);

        $findReplace = new FindReplace();
		$findReplace->Add('/\[PRODUCT_ID\]/', $this->ID);
		$findReplace->Add('/\[PRODUCT_NAME\]/', $this->Name);
		$findReplace->Add('/\[PRODUCT_DEMO\]/', $this->IsDemo);
		$findReplace->Add('/\[PRODUCT_DEMO_NOTES\]/', $this->DemoNotes);
		$findReplace->Add('/\[PRODUCT_CREATED_ON\]/', cDatetime($this->CreatedOn, 'longdate'));
		$findReplace->Add('/\[PRODUCT_CREATED_BY\]/', $user->Person->GetFullName());

		$standardEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/product_new.tpl");
		$standardHtml = '';

		for($i=0; $i<count($standardEmail); $i++) {
			$standardHtml .= $findReplace->Execute($standardEmail[$i]);
		}

		$findReplace = new FindReplace();
		$findReplace->Add('/\[BODY\]/', $standardHtml);
		$findReplace->Add('/\[NAME\]/', 'Product Manager');

		$templateEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
		$templateHtml = '';

		for($i=0; $i<count($templateEmail); $i++) {
			$templateHtml .= $findReplace->Execute($templateEmail[$i]);
		}

        $queue = new EmailQueue();
		$queue->GetModuleID('products');
		$queue->Subject = sprintf("%s - Product Creation [%s]", $GLOBALS['COMPANY'], $this->ID);
		$queue->Body = $templateHtml;
		$queue->ToAddress = 'gary@bltdirect.com';
		$queue->Priority = 'H';
		$queue->Type = 'H';
		$queue->Add();
	}

	function GenerateAddSQL($autoIncrement = true) {
		if($autoIncrement) {
			$sql = sprintf("INSERT INTO product (LockedSupplierID,
													DropSupplierID,
													DropSupplierExpiresOn,
													DropSupplierQuantity,
													DropSupplierReserved,
													SpecialOrderSupplierID,
													SpecialOrderLeadDays,
													Barcode,
													BarcodesApplicable,
													Quality,
													Quality_Text,
													Product_Type,
	                                                SKU,
	                                                Integration_ID,
													Is_Active,
													Is_Best_Seller,
													Is_Demo_Product,
													Demo_Notes,
													Is_Demo_Processed,
													Is_Non_Returnable,
													Is_Dangerous,
													Is_Profit_Control,
													Is_Automatic_Review,
													Product_Title,
													Product_Description,
													Product_Blurb,
													Product_Codes,
													Meta_Title,
													Meta_Description,
													Meta_Keywords,
													Manufacturer_ID,
													Model,
													Variant,
													Weight,
													Shelf_Width,
													Shelf_Height,
													Shelf_Depth,
													Volume,
													Units_Per_Pallet,
													Discontinued,
													Discontinued_On,
													Discontinued_By,
													Discontinued_Because,
													Discontinued_Show_Price,
													Discontinued_Dealt,
													Google_Base_Suffix,
													Position_Quantities,
													Position_Quantities_Recent,
													Position_Quantities_3_Month,
													Position_Quantities_12_Month,
													Position_Orders,
													Position_Orders_Recent,
													Position_Orders_3_Month,
													Position_Orders_12_Month,
													Total_Quantities,
													Total_Quantities_3_Month,
													Total_Quantities_12_Month,
													Total_Orders,
													Total_Orders_3_Month,
													Total_Orders_12_Month,
													Average_Despatch,
													Similar_Text,
													Superseded_By,
													Created_On,
													Created_By,
													Modified_On,
													Modified_By,
													Sales_Start,
													Sales_End,
													Is_Data_Imported,
													Is_Imported,
													Is_Stocked,
													Is_Stocked_Temporarily,
													Monitor_Stock,
													Stock_Level_Suspend,
													Tax_Class_ID,
													Guarantee_Days,
													Estimated_Despatch_Days_Min,
													Estimated_Despatch_Days_Max,
													Order_Min,
													Order_Max,
													Shipping_Class_ID,
													Stock_Level_Alert,
													Stock_Reorder_Quantity,
	                                                Order_Rule,
	                                                Service_Duration,
	                                                Associative_Product_Title,
	                                                Is_Complementary,
	                                                Is_Warehouse_Shipped,
	                                                DiscountLimit)
												VALUES (%d, %d, '%s', %d, '%s', %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
													'%s', '%s', '%s', '%s', '%s', %d, '%s',
													 %f, %f, %f, %f, %f, %d, '%s', '%s',
	                                                '%s',  %d, '%s', '%s', '%s', '%s', %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, '%s', %d, Now(), %d, Now(), %d,
	                                                '%s', '%s','%s','%s','%s','%s', '%s', %d,%d,
													%d,%d,%d,%d,%d,%d,%d, %d, '%s', '%s', '%s', '%s', '%s',
													'%s')",
			mysql_real_escape_string($this->LockedSupplierID),
			mysql_real_escape_string($this->DropSupplierID),
			mysql_real_escape_string($this->DropSupplierExpiresOn),
			mysql_real_escape_string($this->DropSupplierQuantity),
			mysql_real_escape_string($this->DropSupplierReserved),
			mysql_real_escape_string($this->SpecialOrderSupplierID),
			mysql_real_escape_string($this->SpecialOrderLeadDays),
			mysql_real_escape_string($this->InternalBarcode),
			mysql_real_escape_string($this->BarcodesApplicable),
			mysql_real_escape_string($this->Quality),
			mysql_real_escape_string($this->QualityText),
			mysql_real_escape_string($this->Type),
			mysql_real_escape_string(stripslashes($this->SKU)),
			mysql_real_escape_string($this->IntegrationID),
			mysql_real_escape_string($this->IsActive),
			mysql_real_escape_string($this->IsBestSeller),
			mysql_real_escape_string($this->IsDemo),
			mysql_real_escape_string($this->DemoNotes),
			mysql_real_escape_string($this->IsDemoProcessed),
			mysql_real_escape_string($this->IsNonReturnable),
			mysql_real_escape_string($this->IsDangerous),
			mysql_real_escape_string($this->IsProfitControl),
			mysql_real_escape_string($this->IsAutomaticReview),
			mysql_real_escape_string(stripslashes($this->Name)),
			mysql_real_escape_string(stripslashes($this->Description)),
			mysql_real_escape_string(stripslashes($this->Blurb)),
			mysql_real_escape_string(stripslashes($this->Codes)),
			mysql_real_escape_string(stripslashes($this->MetaTitle)),
			mysql_real_escape_string(stripslashes($this->MetaDescription)),
			mysql_real_escape_string(stripslashes($this->MetaKeywords)),
			mysql_real_escape_string($this->Manufacturer->ID),
			mysql_real_escape_string(stripslashes($this->Model)),
			mysql_real_escape_string(stripslashes($this->Variant)),
			mysql_real_escape_string($this->Weight),
			mysql_real_escape_string($this->Width),
			mysql_real_escape_string($this->Height),
			mysql_real_escape_string($this->Depth),
			mysql_real_escape_string($this->Volume),
			mysql_real_escape_string($this->UnitsPerPallet),
			mysql_real_escape_string($this->Discontinued),
			mysql_real_escape_string($this->DiscontinuedOn),
			mysql_real_escape_string($this->DiscontinuedBy),
			mysql_real_escape_string(stripslashes($this->DiscontinuedBecause)),
			mysql_real_escape_string($this->DiscontinuedShowPrice),
			mysql_real_escape_string($this->DiscontinuedDealt),
			mysql_real_escape_string($this->GoogleBaseSuffix),
			mysql_real_escape_string($this->PositionQuantities),
			mysql_real_escape_string($this->PositionQuantitiesRecent),
			mysql_real_escape_string($this->PositionQuantities3Month),
			mysql_real_escape_string($this->PositionQuantities12Month),
			mysql_real_escape_string($this->PositionOrders),
			mysql_real_escape_string($this->PositionOrdersRecent),
			mysql_real_escape_string($this->PositionOrders3Month),
			mysql_real_escape_string($this->PositionOrders12Month),
			mysql_real_escape_string($this->TotalQuantities),
			mysql_real_escape_string($this->TotalQuantities3Month),
			mysql_real_escape_string($this->TotalQuantities12Month),
			mysql_real_escape_string($this->TotalOrders),
			mysql_real_escape_string($this->TotalOrders3Month),
			mysql_real_escape_string($this->TotalOrders12Month),
			mysql_real_escape_string($this->AverageDespatch),
			mysql_real_escape_string($this->SimilarText),
			mysql_real_escape_string($this->SupersededBy),
			$GLOBALS['SESSION_USER_ID'],
			$GLOBALS['SESSION_USER_ID'],
			mysql_real_escape_string($this->SalesStart),
			mysql_real_escape_string($this->SalesEnd),
			mysql_real_escape_string($this->DataImported),
			mysql_real_escape_string($this->StockImported),
			mysql_real_escape_string($this->Stocked),
			mysql_real_escape_string($this->StockedTemporarily),
			mysql_real_escape_string($this->StockMonitor),
			mysql_real_escape_string($this->StockSuspend),
			mysql_real_escape_string($this->TaxClass->ID),
			mysql_real_escape_string($this->Guarantee),
			mysql_real_escape_string($this->DespatchDaysMin),
			mysql_real_escape_string($this->DespatchDaysMax),
			mysql_real_escape_string($this->OrderMin),
			mysql_real_escape_string($this->OrderMax),
			mysql_real_escape_string($this->ShippingClass->ID),
			mysql_real_escape_string($this->StockAlert),
			mysql_real_escape_string($this->StockReorderQuantity),
			mysql_real_escape_string($this->OrderRule),
			mysql_real_escape_string($this->ServiceDuration),
			mysql_real_escape_string(stripslashes($this->AssociativeProductTitle)),
			mysql_real_escape_string($this->IsComplementary),
			mysql_real_escape_string($this->IsWarehouseShipped),
			mysql_real_escape_string($this->DiscountLimit));
		} else {

			if(!is_numeric($this->ID)){
				return false;
			}
			$sql = sprintf("INSERT INTO product (Product_ID, LockedSupplierID,
													DropSupplierID,
													DropSupplierExpiresOn,
													DropSupplierQuantity,
													DropSupplierReserved,
													SpecialOrderSupplierID,
													SpecialOrderLeadDays,
													Barcode,
													BarcodesApplicable,
													Quality,
													Quality_Text,
													Product_Type,
	                                                SKU,
	                                                Integration_ID,
													Is_Active,
													Is_Best_Seller,
													Is_Demo_Product,
													Demo_Notes,
													Is_Demo_Processed,
													Is_Non_Returnable,
													Is_Dangerous,
													Is_Profit_Control,
													Is_Automatic_Review,
													Product_Title,
													Product_Description,
													Product_Blurb,
													Product_Codes,
													Meta_Title,
													Meta_Description,
													Meta_Keywords,
													Manufacturer_ID,
													Model,
													Variant,
													Weight,
													Shelf_Width,
													Shelf_Height,
													Shelf_Depth,
													Volume,
													Units_Per_Pallet,
													Discontinued,
													Discontinued_On,
													Discontinued_By,
													Discontinued_Because,
													Discontinued_Show_Price,
													Discontinued_Dealt,
													Google_Base_Suffix,
													Position_Quantities,
													Position_Quantities_Recent,
													Position_Quantities_3_Month,
													Position_Quantities_12_Month,
													Position_Orders,
													Position_Orders_Recent,
													Position_Orders_3_Month,
													Position_Orders_12_Month,
													Total_Quantities,
													Total_Quantities_3_Month,
													Total_Quantities_12_Month,
													Total_Orders,
													Total_Orders_3_Month,
													Total_Orders_12_Month,
													Average_Despatch,
													Similar_Text,
													Superseded_By,
													Created_On,
													Created_By,
													Modified_On,
													Modified_By,
													Sales_Start,
													Sales_End,
													Is_Data_Imported,
													Is_Imported,
													Is_Stocked,
													Is_Stocked_Temporarily,
													Monitor_Stock,
													Stock_Level_Suspend,
													Tax_Class_ID,
													Guarantee_Days,
													Estimated_Despatch_Days_Min,
													Estimated_Despatch_Days_Max,
													Order_Min,
													Order_Max,
													Shipping_Class_ID,
													Stock_Level_Alert,
													Stock_Reorder_Quantity,
	                                                Order_Rule,
	                                                Service_Duration,
	                                                Associative_Product_Title,
	                                                Is_Complementary,
	                                                Is_Warehouse_Shipped,
	                                                DiscountLimit)
												VALUES (%d, %d, %d, '%s', %d, '%s', %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
													'%s', '%s', '%s', '%s', %d, '%s', '%s',
													 %f, %f, %f, %f, %f, %d, '%s', '%s',
	                                                '%s',  %d, '%s', '%s', '%s', %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, '%s', %d, Now(), %d, Now(), %d,
	                                                '%s', '%s','%s','%s','%s','%s', '%s', %d,%d,
													%d,%d,%d,%d,%d,%d,%d, %d, '%s', '%s', '%s', '%s', '%s',
													'%s')",
			mysql_real_escape_string($this->ID),
			mysql_real_escape_string($this->LockedSupplierID),
			mysql_real_escape_string($this->DropSupplierID),
			mysql_real_escape_string($this->DropSupplierExpiresOn),
			mysql_real_escape_string($this->DropSupplierQuantity),
			mysql_real_escape_string($this->DropSupplierReserved),
			mysql_real_escape_string($this->SpecialOrderSupplierID),
			mysql_real_escape_string($this->SpecialOrderLeadDays),
			mysql_real_escape_string($this->InternalBarcode),
			mysql_real_escape_string($this->BarcodesApplicable),
			mysql_real_escape_string($this->Quality),
			mysql_real_escape_string($this->QualityText),
			mysql_real_escape_string($this->Type),
			mysql_real_escape_string(stripslashes($this->SKU)),
			mysql_real_escape_string($this->IntegrationID),
			mysql_real_escape_string($this->IsActive),
			mysql_real_escape_string($this->IsBestSeller),
			mysql_real_escape_string($this->IsDemo),
			mysql_real_escape_string($this->DemoNotes),
			mysql_real_escape_string($this->IsDemoProcessed),
			mysql_real_escape_string($this->IsNonReturnable),
			mysql_real_escape_string($this->IsDangerous),
			mysql_real_escape_string($this->IsProfitControl),
			mysql_real_escape_string($this->IsAutomaticReview),
			mysql_real_escape_string(stripslashes($this->Name)),
			mysql_real_escape_string(stripslashes($this->Description)),
			mysql_real_escape_string(stripslashes($this->Blurb)),
			mysql_real_escape_string(stripslashes($this->Codes)),
			mysql_real_escape_string(stripslashes($this->MetaTitle)),
			mysql_real_escape_string(stripslashes($this->MetaDescription)),
			mysql_real_escape_string(stripslashes($this->MetaKeywords)),
			mysql_real_escape_string($this->Manufacturer->ID),
			mysql_real_escape_string(stripslashes($this->Model)),
			mysql_real_escape_string(stripslashes($this->Variant)),
			mysql_real_escape_string($this->Weight),
			mysql_real_escape_string($this->Width),
			mysql_real_escape_string($this->Height),
			mysql_real_escape_string($this->Depth),
			mysql_real_escape_string($this->Volume),
			mysql_real_escape_string($this->UnitsPerPallet),
			mysql_real_escape_string($this->Discontinued),
			mysql_real_escape_string($this->DiscontinuedOn),
			mysql_real_escape_string($this->DiscontinuedBy,
			mysql_real_escape_string(stripslashes($this->DiscontinuedBecause)),
			mysql_real_escape_string($this->DiscontinuedShowPrice),
			mysql_real_escape_string($this->DiscontinuedDealt),
			mysql_real_escape_string($this->GoogleBaseSuffix),
			mysql_real_escape_string($this->PositionQuantities),
			mysql_real_escape_string($this->PositionQuantitiesRecent),
			mysql_real_escape_string($this->PositionQuantities3Month),
			mysql_real_escape_string($this->PositionQuantities12Month),
			mysql_real_escape_string($this->PositionOrders),
			mysql_real_escape_string($this->PositionOrdersRecent),
			mysql_real_escape_string($this->PositionOrders3Month),
			mysql_real_escape_string($this->PositionOrders12Month),
			mysql_real_escape_string($this->TotalQuantities),
			mysql_real_escape_string($this->TotalQuantities3Month),
			mysql_real_escape_string($this->TotalQuantities12Month),
			mysql_real_escape_string($this->TotalOrders),
			mysql_real_escape_string($this->TotalOrders3Month),
			mysql_real_escape_string($this->TotalOrders12Month),
			mysql_real_escape_string($this->AverageDespatch),
			mysql_real_escape_string($this->SimilarText),
			mysql_real_escape_string($this->SupersededBy),
			$GLOBALS['SESSION_USER_ID'],
			$GLOBALS['SESSION_USER_ID'],
			mysql_real_escape_string($this->SalesStart),
			mysql_real_escape_string($this->SalesEnd),
			mysql_real_escape_string($this->DataImported),
			mysql_real_escape_string($this->StockImported),
			mysql_real_escape_string($this->Stocked),
			mysql_real_escape_string($this->StockedTemporarily),
			mysql_real_escape_string($this->StockMonitor),
			mysql_real_escape_string($this->StockSuspend),
			mysql_real_escape_string($this->TaxClass->ID),
			mysql_real_escape_string($this->Guarantee),
			mysql_real_escape_string($this->DespatchDaysMin),
			mysql_real_escape_string($this->DespatchDaysMax),
			mysql_real_escape_string($this->OrderMin),
			mysql_real_escape_string($this->OrderMax),
			mysql_real_escape_string($this->ShippingClass->ID),
			mysql_real_escape_string($this->StockAlert),
			mysql_real_escape_string($this->StockReorderQuantity),
			mysql_real_escape_string($this->OrderRule),
			mysql_real_escape_string($this->ServiceDuration),
			mysql_real_escape_string(stripslashes($this->AssociativeProductTitle)),
			mysql_real_escape_string($this->IsComplementary),
			mysql_real_escape_string($this->IsWarehouseShipped),
			mysql_real_escape_string($this->DiscountLimit)));
		}

		return $sql;
	}

	function AddToCategory($catId = null, $connection = null){
		if(!is_null($catId)){
			$insertId = 0;

			$data = new DataQuery(sprintf("SELECT * FROM product_in_categories WHERE Category_ID=%d AND Product_ID=%d", $catId, $this->ID), $connection);
			if($data->TotalRows == 0){
				$category = new ProductInCategory();
				$category->categoryId = $catId;
				$category->productId = $this->ID;
				$category->add();

				return $insertId;
			}
			$data->Disconnect();
		}

		return false;
	}

	function DeleteFromCategory($catId = 0, $prodId = 0) {
		if($catId > 0) {
			if($prodId == 0) {
				$data = new DataQuery(sprintf("SELECT Product_ID FROM product_in_categories WHERE Category_ID=%d", $catId));
				while($data->Row) {
					ProductInCategory::DeleteProduct($catId, $data->Row['Product_ID']);
					$data->Next();
				}
				$data->Disconnect();
			} else {
				ProductInCategory::DeleteProduct2($catId, $prodId);
			}
		}
	}

	function DeleteFromCategoryById($id) {

		if(!is_numeric($id)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT Product_ID, Category_ID FROM product_in_categories WHERE Products_In_Categories_ID=%d", mysql_real_escape_string($id)));
		if($data->TotalRows > 0) {
			$this->DeleteFromCategory($data->Row['Category_ID'], $data->Row['Product_ID']);
		}
		$data->Disconnect();
	}

	function AddSpec($value, $isPrimary = 'N', $connection = null){
		$spec = new ProductSpec();
		$spec->Product->ID = $this->ID;
		$spec->Value->ID = $value;
		$spec->IsPrimary = $isPrimary;
		$spec->Add($connection);
		
		$this->UpdateSpecCache();
	}

	function AddPrice($ourPrice=NULL, $rrpPrice=NULL, $taxIncluded=NULL, $starts=NULL){
		if(!is_null($ourPrice)) $this->PriceOurs = $ourPrice;
		if(!is_null($rrpPrice)) $this->PriceRRP = $rrpPrice;
		if(!is_null($taxIncluded)) $this->TaxIncluded = $taxIncluded;
		if(is_null($starts)) $starts = getDatetime();
		
		$price = new ProductPrice();
		$price->ProductID = $this->ID;
		$price->PriceOurs = $this->PriceOurs;
		$price->PriceRRP = $this->PriceRRP;
	    $price->Quantity = 1;
		$price->IsTaxIncluded = $this->TaxIncluded;
		$price->PriceStartsOn = $starts;
		$price->Add();

		return $price->ID;
	}

	function ToString(){
		ob_start();
		print_r($this);
		$content = ob_get_contents();
		ob_end_clean();
		return sprintf("<pre>%s</pre>", $content);
	}

	function GetDefaultImage(){
		$data = new DataQuery(sprintf("SELECT * FROM product_images
										WHERE (Product_ID=%d AND Is_Primary='Y'
										AND Is_Active='Y') OR
										(Product_ID=%d AND Is_Active='Y')
										ORDER BY Is_Primary, Modified_On ASC limit 1",
		$this->ID, $this->ID));
		if($data->TotalRows !=0){
			$this->DefaultImage->Thumb->SetName($data->Row['Image_Thumb']);
			$this->DefaultImage->Thumb->Width = $data->Row['Image_Thumb_Width'];
			$this->DefaultImage->Thumb->Height = $data->Row['Image_Thumb_Height'];
			$this->DefaultImage->Large->SetName($data->Row['Image_Src']);
			$this->DefaultImage->Large->Width = $data->Row['Image_Src_Width'];
			$this->DefaultImage->Large->Height = $data->Row['Image_Src_Height'];
			$this->DefaultImage->Name = $data->Row['Image_Title'];
			$this->DefaultImage->Description = $data->Row['Image_Description'];
			$this->DefaultImage->CreatedOn = $data->Row['Created_On'];
			$this->DefaultImage->CreatedBy = $data->Row['Created_By'];
			$this->DefaultImage->ModifiedOn = $data->Row['Modified_On'];
			$this->DefaultImage->ModifiedBy = $data->Row['Modified_By'];
		}
		$data->Disconnect();
	}

	function GetOptions() {
		$this->Options = new ProductOptionGroupCollection($this->ID);
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}


		if(!is_numeric($this->ID)){
			return false;
		}


		ProductComponent::DeleteProduct($this->ID);

		$getImages = new DataQuery(sprintf("SELECT Product_Image_ID FROM product_images WHERE Product_ID=%d", mysql_real_escape_string($this->ID)));
		while($getImages->Row){
			$productImage = new ProductImage;
			$productImage->Delete($getImages->Row['Product_Image_ID']);

			$getImages->Next();
		}
		$getImages->Disconnect();
		ProductInCategory::DeleteProduct3($this->ID);
		ProductOffer::DeleteProduct($this->ID);
		
		// Delete Product Option Groups and their options
		$getOptionGroups = new DataQuery(sprintf("select Product_Option_Group_ID from product_option_groups where Product_ID=%d", mysql_real_escape_string($this->ID)));
		while($getOptionGroups->Row){
			$data = new ProductOptions();
			$data->ID = $getOptionGroups->Row['Product_Option_Group_ID'];
			$data->Delete();
			$getOptionGroups->Next();
		}
		$getOptionGroups->Disconnect();

		ProductOptionGroup::DeleteProduct($this->ID);
		ProductOption::DeleteProduct($this->ID);
		ProductPrice::DeleteProduct($this->ID);
		ProductRelated::DeleteProduct($this->ID);

		$data = new DataQuery(sprintf("SELECT Specification_ID FROM product_specification WHERE Product_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$spec = new ProductSpec();
			$spec->Delete($data->Row['Specification_ID']);

			$data->Next();
		}
		$data->Disconnect();

		new DataQuery(sprintf("delete from product where Product_ID=%d", mysql_real_escape_string($this->ID)));
		SupplierProduct::DeleteProduct($this->ID);
		WarehouseStock::DeleteProduct($this->ID);
	}

	public function GetDownloads() {
		$this->Download = array();
		$this->DownloadsFetched = true;

		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT id FROM product_download WHERE productId=%d ORDER BY name ASC", mysql_real_escape_string($this->ID)));
		while($data->Row){
			$this->Download[] = new ProductDownload($data->Row['id']);

			$data->Next();
		}
		$data->Disconnect();
	}
	
	public function GetImages() {
		$this->Image = array();
		$this->ImagesFetched = true;

		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT Product_Image_ID FROM product_images WHERE Product_ID=%d AND Image_Title NOT LIKE 'specialoffer' ORDER BY Image_Title ASC", mysql_real_escape_string($this->ID)));
		while($data->Row){
			$this->Image[] = new ProductImage($data->Row['Product_Image_ID']);

			$data->Next();
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT pi.Product_Image_ID FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID INNER JOIN product_images AS pi ON pi.Specification_Value_ID=psv.Value_ID WHERE ps.Product_ID=%d ORDER BY pi.Image_Title ASC", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->Image[] = new ProductImage($data->Row['Product_Image_ID']);

			$data->Next();
		}
		$data->Disconnect();

		if(isset($_REQUEST['debug'])) {
			echo '<pre>';
			print_r($this->Image);
			echo '</pre>';
		}
	}
	
	public function GetExamples() {
		$this->Example = array();
		$this->ExamplesFetched = true;
		
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT Product_Image_Example_ID FROM product_image_example WHERE Product_ID=%d ORDER BY Image_Title ASC", mysql_real_escape_string($this->ID)));
		while($data->Row){
			$this->Example[] = new ProductImageExample($data->Row['Product_Image_Example_ID']);

			$data->Next();
		}
		$data->Disconnect();
	}
	
	public function GetLinkObjects() {
		$this->LinkObject = array();
		$this->LinkObjectsFetched = true;

		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT id FROM product_link WHERE productId=%d", mysql_real_escape_string($this->ID)));
		while($data->Row){
			$object = new ProductLink($data->Row['id']);
			$object->asset->getMeta();

			$this->LinkObject[] = $object;

			$data->Next();
		}
		$data->Disconnect();
	}
	
	public function GetSpecs() {
		$this->Spec = array();
		$this->SpecsFetched = true;

		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT ps.Is_Primary, psg.Name, psv.Value, CONCAT_WS(' ', psv.Value, psg.Units) AS UnitValue FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID INNER JOIN product_specification_group AS psg ON psv.Group_ID=psg.Group_ID AND psg.Is_Hidden='N' WHERE ps.Product_ID=%d AND psg.Is_Visible='Y' ORDER BY psg.Name ASC", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->Spec[] = $data->Row;
			
			$data->Next();
		}
		$data->Disconnect();
	}
	
	public function GetRelated() {
		$this->Related = array();
		$this->RelatedFetched = true;

		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT pr.Product_ID, p.Product_Title FROM product_related AS pr INNER JOIN product AS p ON p.Product_ID=pr.Product_ID WHERE pr.Related_To_Product_ID=%d AND pr.Is_Active='Y' AND p.Is_Active='Y'%s", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->Related[] = $data->Row;
			
			$data->Next();
		}
		$data->Disconnect();
	}
	
	public function GetRelatedByType($type = '') {
		$this->RelatedType[$type] = array();

		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT pr.Product_ID, p.Product_Title FROM product_related AS pr INNER JOIN product AS p ON p.Product_ID=pr.Product_ID WHERE pr.Related_To_Product_ID=%d AND pr.Is_Active='Y' AND p.Is_Active='Y' AND pr.Type LIKE '%s'", mysql_real_escape_string($this->ID), mysql_real_escape_string($type)));
		while($data->Row) {
			$this->RelatedType[$type][] = $data->Row;
			
			$data->Next();
		}
		$data->Disconnect();
	}
	
	public function GetComponents() {
		$this->Component = array();
		$this->ComponentsFetched = true;

		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT pc.Product_ID, pc.Component_Of_Product_ID, p.Product_Title, pc.Component_Quantity FROM product_components AS pc INNER JOIN product AS p ON p.Product_ID=pc.Product_ID AND p.Is_Active='Y' WHERE pc.Is_Active='Y' AND (pc.Component_Of_Product_ID=%d OR pc.Product_ID=%d)", mysql_real_escape_string($this->ID), mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->Component[] = $data->Row;
			
			$data->Next();
		}
		$data->Disconnect();
	}
	
	public function GetReviews() {
		$this->Review = array();
		$this->ReviewAverage = 0;
		$this->ReviewsFetched = true;

		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT pr.Product_Review_ID, pr.Title, pr.Review, pr.Rating, pr.Created_On, CONCAT_WS(' ', p.Name_First) AS Customer_Name, r.Region_Name, ct.Country AS Country_Name FROM product_review AS pr LEFT JOIN customer AS cu ON cu.Customer_ID=pr.Customer_ID LEFT JOIN contact AS c ON c.Contact_ID=cu.Contact_ID LEFT JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN address AS a ON a.Address_ID=p.Address_ID LEFT JOIN regions AS r ON r.Region_ID=a.Region_ID LEFT JOIN countries AS ct ON ct.Country_ID=a.Country_ID WHERE pr.Product_ID=%d AND Is_Approved='Y' ORDER BY pr.Created_On DESC", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->Review[] = $data->Row;
			$this->ReviewAverage += $data->Row['Rating'];
			
			$data->Next();
		}
		$data->Disconnect();
		
		if(count($this->Review) > 0) {
			$this->ReviewAverage /= count($this->Review);
		}
	}
	
	public function GetReviewObjects() {
		$this->ReviewObject = array();
		$this->ReviewObjectsFetched = true;
		
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT Product_Review_ID, Rating FROM product_review WHERE Product_ID=%d AND Is_Approved='Y'", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->ReviewObject[] = new ProductReview($data->Row['Product_Review_ID']);
			
			$data->Next();
		}
		$data->Disconnect();
	}
	
	public function GetAlternativeCodes() {
		$this->AlternativeCode = array();
		$this->AlternativeCodesFetched = true;

		if(!is_numeric($this->ID)){
			return false;
		}
		
		if($this->Type <> 'G') {
			$data = new DataQuery(sprintf("SELECT DISTINCT Supplier_SKU AS Code FROM supplier_product WHERE Product_ID=%d AND Supplier_SKU<>'' AND Supplier_SKU<>'0' AND Supplier_ID<>4 ORDER BY Supplier_SKU ASC",  mysql_real_escape_string($this->ID)));
			while($data->Row) {
				$this->AlternativeCode[] = $data->Row;
				
				$data->Next();
			}
			$data->Disconnect();
		}
	}
	
	public function GetBarcodes() {
		$this->Barcode = array();
		$this->BarcodesFetched = true;

		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT DISTINCT Barcode FROM product_barcode WHERE ProductID=%d ORDER BY Barcode ASC",  mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->Barcode[] = $data->Row;
			
			$data->Next();
		}
		$data->Disconnect();
	}

	public function GetQualityLinks() {
		$this->QualityLink = array();
		$this->QualityLinksFetched = true;

		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title FROM product_quality AS pq INNER JOIN product AS p ON p.Product_ID=pq.productId WHERE pq.parentId=%d AND p.Is_Active='Y'",  mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->QualityLink[] = $data->Row;
			
			$data->Next();
		}
		$data->Disconnect();
	}
	
	public function GetQualityLinksByType($type = '') {
		$this->QualityLinkType[$type] = array();

		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title FROM product_quality AS pq INNER JOIN product AS p ON p.Product_ID=pq.productId WHERE pq.parentId=%d AND p.Is_Active='Y' AND p.Quality LIKE '%s'",  mysql_real_escape_string($this->ID), mysql_real_escape_string($type)));
		while($data->Row) {
			$this->QualityLinkType[$type][] = $data->Row;
			
			$data->Next();
		}
		$data->Disconnect();
	}

	function UpdateSpecCache($connection = null) {
		$this->SpecCache = '';
		$this->SpecCachePrimary = '';

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT ps.Is_Primary, psv.Value, psg.Name, psg.Units FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON psv.Value_ID=ps.Value_ID INNER JOIN product_specification_group AS psg ON psg.Group_ID=psv.Group_ID WHERE ps.Product_ID=%d ORDER BY psg.Name ASC",  mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$value = $data->Row['Value'];
			
			if(!empty($data->Row['Units'])) {
				$value .= ' ' . $data->Row['Units'];
			}
			
			$this->SpecCache .= sprintf("%s=%s;", $data->Row['Name'], $value);
			
			if($data->Row['Is_Primary'] == 'Y') {
				$this->SpecCachePrimary .= sprintf("%s=%s;", $data->Row['Name'], $value);
			}

			$data->Next();
		}
		$data->Disconnect();

		if(!empty($this->SpecCache)) {
			$this->SpecCache = substr($this->SpecCache, 0, -1);
		}
		
		if(!empty($this->SpecCachePrimary)) {
			$this->SpecCachePrimary = substr($this->SpecCachePrimary, 0, -1);
		}

		$this->Update();		
	}

	function IsSKUUnique($sku){
		$data = new DataQuery(sprintf("SELECT Product_ID, SKU FROM product WHERE SKU = '%s'",  mysql_real_escape_string($sku)));
		if($data->TotalRows == 0){
			$data->Disconnect();
			return true;
		}	else {
			$this->ID = $data->Row['Product_ID'];
			$this->SKU = $data->Row['SKU'];
			$data->Disconnect();
			return false;
		}
	}

	function EscapeSpecial() {
		$this->Description = str_replace("'", "\'", $this->Description);
	}

	function PublicID() {
		return ($this->ID > 0) ? $GLOBALS['PRODUCT_PREFIX'] . $this->ID : '';
	}
	
	function CacheCodes() {
		$code = preg_replace('/[^a-zA-Z0-9\s]/', '', $this->SKU);

		$codes = array();
		$codes[$code] = $code;

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT DISTINCT psv.Value AS Code FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON psv.Value_ID=ps.Value_ID INNER JOIN product_specification_group AS psg ON psg.Group_ID=psv.Group_ID AND psg.Reference LIKE '%% Code' WHERE ps.Product_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row){
			$code = preg_replace('/[^a-zA-Z0-9\s]/', '', $data->Row['Code']);

			$codes[$code] = $code;

			$data->Next();
		}
		$data->Disconnect();
		
		if($this->Type <> 'G') {
			$data = new DataQuery(sprintf("SELECT DISTINCT Supplier_SKU AS Code FROM supplier_product WHERE Product_ID=%d AND Supplier_ID<>4", mysql_real_escape_string($this->ID)));
			while($data->Row){
				$code = preg_replace('/[^a-zA-Z0-9\s]/', '', $data->Row['Code']);

				$codes[$code] = $code;

				$data->Next();
			}
			$data->Disconnect();
		}
		
		$cleaned = array();
		
		foreach($codes as $code) {
			$items = explode(' ', $code);
			
			foreach($items as $item) {
				if(!empty($item)) {
					$cleaned[] = $item;
				}	
			}
		}
		
		$this->Codes = implode(' ', $cleaned);
	}
	
	function PurgeCache() {
		$cache = Zend_Cache::factory('Output', $GLOBALS['CACHE_BACKEND']);
		$cache->remove('product__' . $this->ID);
		$cache->remove('product_prices__product_id__' . $this->ID);
	}

	static function Reset($id){
		new DataQuery (sprintf("update product set Product_Band_ID=0 where Product_Band_ID=%d", mysql_real_escape_string($id)));
	} 

	static function UpdateProductBand($id, $ProdID){
		new DataQuery (sprintf("update product set Product_Band_ID=%d where Product_ID=%d", mysql_real_escape_string($id), mysql_real_escape_string($ProdID)));
	}

	static function ResetCategory($id){
		new DataQuery (sprintf("update product set Product_Band_ID=0 where Product_ID=%d", mysql_real_escape_string($id)));
	}

	static function AddProductAdd($id, $ProductID){
		new DataQuery (sprintf("update product set Product_Band_ID=%d where Product_ID=%d", mysql_real_escape_string($id),mysql_real_escape_string($ProductID)));
	}

	static function ResetProduct($id){
		new DataQuery (sprintf("update product set Product_Band_ID=0 where Product_ID=%d", mysql_real_escape_string($id)));
	}
}