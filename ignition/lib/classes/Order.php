<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ContactProductTrade.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Quote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Person.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Enquiry.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Card.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Postage.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/OrderBidding.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/OrderLine.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/OrderNote.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/OrderContact.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/OrderShipping.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DiscountBanding.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DiscountBandingBasket.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DiscountBandingBasketLine.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DiscountBandingOrder.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DiscountBandingOrderLine.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/FindReplace.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/htmlMimeMail5.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/IFile.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Coupon.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CustomerContact.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DiscountCollection.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ShippingCalculator.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/TaxCalculator.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Geozone.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Payment.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Setting.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Supplier.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Warehouse.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/WarehouseStock.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/SupplierShippingCalculator.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/OrderShipping.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailQueue.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/PaymentMethod.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/SupplierProductPriceCollection.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Template.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/TradeBanding.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'services/google-checkout/classes/GoogleRequest.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/gateways/FastSMS.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'packages/Browser/Browser.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');

class Order {
	public $ID;
	public $ParentID;
	public $Sample;
	public $QuoteID;
	public $ProformaID;
	public $ReturnID;
	public $Line;
	public $LinesFetched;
	public $CustomID;
	public $CourierQuoteFile;
	public $CourierQuoteAmount;
	public $Prefix;
	public $Customer;
	public $Coupon;
	public $OriginalCoupon;
	public $BillingOrg;
	public $Billing;
	public $ShippingOrg;
	public $Shipping;
	public $InvoiceOrg;
	public $Invoice;
	public $NominalCode;
	public $IsActive;
	public $IsSecurityRisk;
	public $IsBidding;
	public $IsNotReceived;
	public $IsCollection;
	public $IsAutoShip;
	public $OrderedOn;
	public $EmailedOn;
	public $EmailedTo;
	public $ReceivedOn;
	public $ReceivedBy;
	public $InvoicedOn;
	public $PaidOn;
	public $DespatchedOn;
	public $Status;
	public $DeviceBrowser;
	public $DeviceVersion;
	public $DevicePlatform;
	public $TotalLines;
	public $SubTotal;
	public $SubTotalRetail;
	public $TotalShipping;
	public $TotalDiscount;
	public $TotalNet;
	public $TotalTax;
	public $Total;
	public $TaxRate;
	public $Referrer;
	public $PaymentMethod;
	public $Card;
	public $PaymentReceivedOn;
	public $OwnedBy;
	public $CancelledOn;
	public $CancelledBy;
	public $CancelledReason;
	public $CancelledComment;
	public $CreatedOn;
	public $CreatedBy;
	public $ModifiedOn;
	public $ModifiedBy;
	public $Weight;
	public $Postage;
	public $IsPlainLabel;
	public $IsNotesUnread;
	public $IsAwaitingCustomer;
	public $LinesHtml;
	public $SampleLinesHtml;
	public $Html;
	public $HasInvoices;
	public $DiscountCollection;
	public $PostageOptions;
	public $Geozone;
	public $Error;
	public $FoundPostage;
	public $IsCustomShipping;
	public $IsTaxExemptValid;
	public $TaxExemptCode;
	public $Transaction;
	public $FreeText;
	public $FreeTextValue;
	public $IsFreeTextDespatched;
	public $Backordered;
	public $DiscountReward;
	public $IsPaymentUnverified;
	public $IsDeclined;
	public $IsFailed;
	public $IsRestocked;
	public $IsWarehouseDeclined;
	public $IsWarehouseDeclinedRead;
	public $IsWarehouseUndeclined;
	public $IsWarehouseBackordered;
	public $IsAbsentStockProfile;
	public $DiscountBandingID;
	public $DeliveryInstructions;
	public $AffiliateID;
	public $DeadlineOn;
	public $ConfirmedOn;
	public $ConfirmedBy;
	public $ConfirmedNotes;
	public $SupplierShipping;
	public $SupplierShipped;
	public $ShippingLine;
	public $ShippingMultiplier;
    public $Bid;
	public $BidsFetched;
	public $Suggestion;
	public $BasketID;
	public $AdditionalEmail;
	public $AdditionalEmailedOn;
	public $IsDismissed;




	public function __construct($id = NULL) {
		$this->Sample = 'N';
		$this->IsPaymentUnverified = 'N';
		$this->IsDeclined = 'N';
		$this->IsFailed = 'N';
		$this->IsRestocked = 'N';
		$this->IsWarehouseDeclined = 'N';
		$this->IsWarehouseDeclinedRead = 'N';
		$this->IsWarehouseUndeclined = 'N';
		$this->IsWarehouseBackordered = 'N';
		$this->IsAbsentStockProfile = 'N';
		$this->Backordered = 'N';
		$this->IsFreeTextDespatched = 'N';
		$this->Prefix = 'W';
		$this->IsActive = 'Y';
		$this->IsSecurityRisk = 'N';
		$this->IsBidding = 'N';
		$this->IsNotReceived = 'N';
		$this->IsCollection = 'N';
		$this->IsAutoShip = 'N';
		$this->IsCustomShipping = 'N';
		$this->IsTaxExemptValid = 'N';
		$this->Line = array();
		$this->LinesFetched = false;
		$this->Customer = new Customer();
		$this->Coupon = new Coupon();
		$this->OriginalCoupon = new Coupon();
		$this->Coupon->ID = 0;
		$this->OriginalCoupon->ID = 0;
		$this->TotalDiscount = 0;
		$this->TotalNet;
		$this->Billing = new Person();
		$this->Shipping = new Person();
		$this->Invoice = new Person();
		$this->NominalCode = $GLOBALS['SAGE_DEFAULT_NOMINAL_CODE'];
		$this->Card = new Card();
		$this->Postage = new Postage();
		$this->IsPlainLabel = 'N';
		$this->IsNotesUnread = 'N';
		$this->IsAwaitingCustomer = 'N';
		$this->LinesHtml = '';
		$this->SampleLinesHtml = '';
		$this->HasInvoices = false;
		$this->EmailedOn = '0000-00-00 00:00:00';
		$this->ReceivedOn = '0000-00-00 00:00:00';
		$this->InvoicedOn = '0000-00-00 00:00:00';
		$this->PaidOn = '0000-00-00 00:00:00';
		$this->DespatchedOn = '0000-00-00 00:00:00';
		$this->OrderedOn = '0000-00-00 00:00:00';
		$this->DeadlineOn = '0000-00-00 00:00:00';
		$this->ConfirmedOn = '0000-00-00 00:00:00';
		$this->CancelledOn = '0000-00-00 00:00:00';
		$this->DiscountCollection = new DiscountCollection();
		$this->Transaction = array();
		$this->DiscountBandingID = 0;
		$this->SupplierShipping = array();
		$this->ShippingLine = array();
		$this->ShippingMultiplier = 1;
        $this->Bid = array();
		$this->BidsFetched = false;
		$this->Suggestion = array();
		$this->PaymentReceivedOn = '0000-00-00 00:00:00';
		$this->BasketID = 0;
		$this->AdditionalEmail = '';
		$this->AdditionalEmailedOn = '0000-00-00 00:00:00';
		$this->IsDismissed = 'N';


		$this->CourierQuoteFile = new IFile();
		$this->CourierQuoteFile->OnConflict = 'makeunique';
		$this->CourierQuoteFile->Extensions = '';
		$this->CourierQuoteFile->SetDirectory($GLOBALS['ORDER_QUOTE_DOCUMENT_DIR_FS']);

		$this->PaymentMethod = new PaymentMethod();
		$this->PaymentMethod->GetByReference('card');

		if (!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}
	
	public static function getSql($cols=null){
		$cols = (!empty($cols))? ', ' . $cols : '';
		$sql = "SELECT o.*,
										rb.Region_Name AS Billing_Region_Name,
										cb.Country AS Billing_Country,
										afb.Address_Format AS Billing_Address_Format,
										afb.Address_Summary AS Billing_Address_Summary,
										rs.Region_Name AS Shipping_Region_Name,
										cs.Country AS Shipping_Country,
										afs.Address_Format AS Shipping_Address_Format,
										afs.Address_Summary AS Shipping_Address_Summary,
										ri.Region_Name AS Invoice_Region_Name,
										ci.Country AS Invoice_Country,
										afi.Address_Format AS Invoice_Address_Format,
										afi.Address_Summary AS Invoice_Address_Summary
										{$cols}
										FROM orders AS o
										LEFT JOIN regions AS rb ON o.Billing_Region_ID=rb.Region_ID
										LEFT JOIN countries AS cb ON o.Billing_Country_ID=cb.Country_ID
										LEFT JOIN regions AS rs ON o.Shipping_Region_ID=rs.Region_ID
										LEFT JOIN countries AS cs ON o.Shipping_Country_ID=cs.Country_ID
										LEFT JOIN regions AS ri ON o.Invoice_Region_ID=ri.Region_ID
										LEFT JOIN countries AS ci ON o.Invoice_Country_ID=ci.Country_ID
										LEFT JOIN address_format AS afb ON cb.Address_Format_ID=afb.Address_Format_ID
										LEFT JOIN address_format AS afs ON cs.Address_Format_ID=afs.Address_Format_ID
										LEFT JOIN address_format AS afi ON ci.Address_Format_ID=afi.Address_Format_ID";
		return $sql;
	}
	// populates the object based upon the getSql() set of data returned from the database
	public function populate($data){
		$this->ID = $data['Order_ID'];
		$this->Prefix = $data['Order_Prefix'];
		$this->Sample = $data['Is_Sample'];
		$this->Customer->ID = $data['Customer_ID'];
		$this->Coupon->ID = $data['Coupon_ID'];
		$this->OriginalCoupon->ID = $data['Original_Coupon_ID'];
		$this->CustomID = $data['Custom_Order_No'];
		$this->CourierQuoteFile->SetName($data['Courier_Quote_File']);
		$this->CourierQuoteAmount = $data['Courier_Quote_Amount'];
		$this->Billing->Title = $data['Billing_Title'];
		$this->Billing->Name = $data['Billing_First_Name'];
		$this->Billing->Initial = $data['Billing_Initial'];
		$this->Billing->LastName = $data['Billing_Last_Name'];
		$this->BillingOrg = $data['Billing_Organisation_Name'];
		$this->Billing->Address->Line1 = $data['Billing_Address_1'];
		$this->Billing->Address->Line2 = $data['Billing_Address_2'];
		$this->Billing->Address->Line3 = $data['Billing_Address_3'];
		$this->Billing->Address->City = $data['Billing_City'];
		$this->Billing->Address->Country->ID = $data['Billing_Country_ID'];
		$this->Billing->Address->Country->Name = $data['Billing_Country'];
		$this->Billing->Address->Country->AddressFormat->Long = $data['Billing_Address_Format'];
		$this->Billing->Address->Country->AddressFormat->Short = $data['Billing_Address_Summary'];
		$this->Billing->Address->Region->ID = $data['Billing_Region_ID'];
		$this->Billing->Address->Region->Name = $data['Billing_Region_Name'];
		$this->Billing->Address->Zip = $data['Billing_Zip'];
		$this->Shipping->Title = $data['Shipping_Title'];
		$this->Shipping->Name = $data['Shipping_First_Name'];
		$this->Shipping->Initial = $data['Shipping_Initial'];
		$this->Shipping->LastName = $data['Shipping_Last_Name'];
		$this->ShippingOrg = $data['Shipping_Organisation_Name'];
		$this->Shipping->Address->Line1 = $data['Shipping_Address_1'];
		$this->Shipping->Address->Line2 = $data['Shipping_Address_2'];
		$this->Shipping->Address->Line3 = $data['Shipping_Address_3'];
		$this->Shipping->Address->City = $data['Shipping_City'];
		$this->Shipping->Address->Country->ID = $data['Shipping_Country_ID'];
		$this->Shipping->Address->Country->Name = $data['Shipping_Country'];
		$this->Shipping->Address->Country->AddressFormat->Long = $data['Shipping_Address_Format'];
		$this->Shipping->Address->Country->AddressFormat->Short = $data['Shipping_Address_Summary'];
		$this->Shipping->Address->Region->ID = $data['Shipping_Region_ID'];
		$this->Shipping->Address->Region->Name = $data['Shipping_Region_Name'];
		$this->Shipping->Address->Zip = $data['Shipping_Zip'];
		$this->Invoice->Name = $data['Invoice_First_Name'];
		$this->Invoice->Initial = $data['Invoice_Initial'];
		$this->Invoice->LastName = $data['Invoice_Last_Name'];
		$this->InvoiceOrg = $data['Invoice_Organisation_Name'];
		$this->Invoice->Address->Line1 = $data['Invoice_Address_1'];
		$this->Invoice->Address->Line2 = $data['Invoice_Address_2'];
		$this->Invoice->Address->Line3 = $data['Invoice_Address_3'];
		$this->Invoice->Address->City = $data['Invoice_City'];
		$this->Invoice->Address->Country->ID = $data['Invoice_Country_ID'];
		$this->Invoice->Address->Country->Name = $data['Invoice_Country'];
		$this->Invoice->Address->Country->AddressFormat->Long = $data['Invoice_Address_Format'];
		$this->Invoice->Address->Country->AddressFormat->Short = $data['Invoice_Address_Summary'];
		$this->Invoice->Address->Region->ID = $data['Invoice_Region_ID'];
		$this->Invoice->Address->Region->Name = $data['Invoice_Region_Name'];
		$this->Invoice->Address->Zip = $data['Invoice_Zip'];
		$this->PaymentMethod->ID = $data['Payment_Method_ID'];
		$this->Card->Type->ID = $data['Card_Payment_Method'];
		$this->Card->Type->Name = $data['Card_Type'];
		$this->Card->Number = $data['Card_Number'];
		$this->Card->Title = $data['Card_Title'];
		$this->Card->Initial = $data['Card_Initial'];
		$this->Card->Surname = $data['Card_Surname'];
		$this->Card->Expires = $data['Card_Expires'];
		$this->Postage->Get($data['Postage_ID']);
		$this->BasketID = $data['Basket_ID'];
		$this->AdditionalEmail = $data['Additional_Email'];
		$this->AdditionalEmailedOn = $data['Additional_Emailed_On'];

		$this->TotalNet = $this->SubTotal + $this->TotalShipping - $this->TotalDiscount;
		$this->Total = $data['Total'];
		$this->CreatedOn = $data['Created_On'];
		return $this;
	}

	public function Get($id = NULL, $isCustomID = false) {
		if (!is_null($id) && !$isCustomID) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID) && !$isCustomID) return false;
		$sql = self::getSql() . " WHERE";

		if ($isCustomID && !is_null($id)) {
			$sql .= " Custom_Order_No='{$id}'";
		} else {
			$sql .= " Order_ID={$this->ID}";
		}
		$data = new DataQuery($sql);
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$key = str_replace('_', '', $key);
				if(!is_object($this->$key)) {
					$this->$key = $value;
				}
			}
			$this->populate($data->Row);
			$data->Disconnect();
			return true;
		}
		$data->Disconnect();
		return false;
	}

	function GetVia($col, $val) {
		$val = mysql_real_escape_string($val);
		$data = new DataQuery("SELECT Order_ID FROM orders WHERE $col = $val");
		if ($data->GetTotalRows() == 1) {
			$this->ID = $data->Row['Order_ID'];
			$this->Get();
			return true;
		}
		return false;
	}

	function GetAdditioanlEmail($orderId){
		$order = new Order();
  		$order->Get($orderId);
  		$additionEmail = "";

  		if(!empty($order->AdditionalEmail)){
  			$additionEmail = $order->AdditionalEmail;
  		}

  		return $additionEmail;
	}

	function GetAcountManager($customerId){
	$data = new DataQuery(sprintf("SELECT c.Contact_ID, cus.Customer_ID, u.User_ID, u.User_Name from
		contact as c
		left join users as u on c.Account_Manager_ID = u.User_ID
		left join customer as cus on c.Contact_ID = cus.Contact_ID
		where cus.Customer_ID =%d",  mysql_real_escape_string($customerId)));
		
		if($data->TotalRows > 0) {
			return $data->Row['User_ID'];
		}
	return 0;
	}

	function Add() {
		if(in_array($this->Prefix, array('W', 'M', 'L', 'U'))) {
			$browser = new Browser();
			
			$this->DeviceBrowser = $browser->getBrowser();
			$this->DeviceVersion = $browser->getVersion();
			$this->DevicePlatform = $browser->getPlatform();
		}

		if($this->IsSecurityRisk == 'N') {
			if($this->Billing->Address->Zip != $this->Shipping->Address->Zip) {
				$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM orders WHERE Customer_ID=%d", mysql_real_escape_string($this->Customer->ID)));
				if($data->Row['Count'] == 0) {
					$this->IsSecurityRisk == 'Y';
				}
				$data->Disconnect();
			}
		}

        if($this->IsSecurityRisk == 'N') {
			if($this->Billing->Address->Zip != $this->Shipping->Address->Zip) {
				$this->Postage->Get();

				if($this->Postage->Days <= 1) {
					$this->IsSecurityRisk == 'Y';
				}
			}
		}
		
		$this->CalculateNominalCode();
		$this->CalculateTaxRate();

		$fields = array(
			array('Parent_ID', '%d', mysql_real_escape_string($this->ParentID)),
			array('ProformaID', '%d', mysql_real_escape_string($this->ProformaID)),
			array('Is_Sample', '"%s"', mysql_real_escape_string($this->Sample)),
			array('Is_Payment_Unverified', '"%s"', mysql_real_escape_string($this->IsPaymentUnverified)),
			array('Is_Declined', '"%s"', mysql_real_escape_string($this->IsDeclined)),
			array('Is_Failed', '"%s"', mysql_real_escape_string($this->IsFailed)),
			array('Is_Restocked', '"%s"', mysql_real_escape_string($this->IsRestocked)),
			array('Is_Warehouse_Declined', '"%s"', mysql_real_escape_string($this->IsWarehouseDeclined)),
			array('Is_Warehouse_Declined_Read', '"%s"', mysql_real_escape_string($this->IsWarehouseDeclinedRead)),
			array('Is_Warehouse_Undeclined', '"%s"', mysql_real_escape_string($this->IsWarehouseUndeclined)),
			array('Is_Warehouse_Backordered', '"%s"', mysql_real_escape_string($this->IsWarehouseBackordered)),
			array('Is_Absent_Stock_Profile', '"%s"', mysql_real_escape_string($this->IsAbsentStockProfile)),
			array('Quote_ID', '%d', mysql_real_escape_string($this->QuoteID)),
			array('Return_ID', '%d', mysql_real_escape_string($this->ReturnID)),
			array('IsFreeTextDespatched', '"%s"', mysql_real_escape_string($this->IsFreeTextDespatched)),
			array('Order_Prefix', '"%s"', mysql_real_escape_string($this->Prefix)),
			array('Customer_ID', '%d', mysql_real_escape_string($this->Customer->ID)),
			array('Coupon_ID', '%d', mysql_real_escape_string($this->Coupon->ID)),
			array('Original_Coupon_ID', '%d', mysql_real_escape_string($this->OriginalCoupon->ID)),
			array('Custom_Order_No', '"%s"', mysql_real_escape_string(stripslashes($this->CustomID))),
			array('Custom_Order_No_Search', '"%s"', mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->CustomID))),
			array('Courier_Quote_File', '"%s"', mysql_real_escape_string($this->CourierQuoteFile->FileName)),
			array('Courier_Quote_Amount', '%f', mysql_real_escape_string($this->CourierQuoteAmount)),
			array('Ordered_On', '"%s"', mysql_real_escape_string($this->OrderedOn)),
			array('Emailed_On', '"%s"', mysql_real_escape_string($this->EmailedOn)),
			array('Emailed_To', '"%s"', mysql_real_escape_string($this->EmailedTo)),
			array('Received_On', '"%s"', mysql_real_escape_string($this->ReceivedOn)),
			array('Received_By', '%d', mysql_real_escape_string($this->ReceivedBy)),
			array('Invoiced_On', '"%s"', mysql_real_escape_string($this->InvoicedOn)),
			array('Paid_On', '"%s"', mysql_real_escape_string($this->PaidOn)),
			array('Despatched_On', '"%s"', mysql_real_escape_string($this->DespatchedOn)),
			array('Status', '"%s"', mysql_real_escape_string($this->Status)),
			array('DeviceBrowser', '"%s"', mysql_real_escape_string($this->DeviceBrowser)),
			array('DeviceVersion', '"%s"', mysql_real_escape_string($this->DeviceVersion)),
			array('DevicePlatform', '"%s"', mysql_real_escape_string($this->DevicePlatform)),
			array('Total_Lines', '"%s"', mysql_real_escape_string($this->TotalLines)),
			array('SubTotal', '"%s"', mysql_real_escape_string($this->SubTotal)),
			array('Sub_Total_Retail', '%f', mysql_real_escape_string($this->SubTotalRetail)),
			array('TotalShipping', '%f', mysql_real_escape_string($this->TotalShipping)),
			array('IsCustomShipping', '"%s"', mysql_real_escape_string($this->IsCustomShipping)),
			array('TotalDiscount', '%f', mysql_real_escape_string($this->TotalDiscount)),
			array('TotalTax', '%f', mysql_real_escape_string($this->TotalTax)),
			array('Total', '"%s"', mysql_real_escape_string($this->Total)),
			array('Tax_Rate', '%f', mysql_real_escape_string($this->TaxRate)),
			array('Is_Active', '"%s"', mysql_real_escape_string($this->IsActive)),
			array('Is_Security_Risk', '"%s"', mysql_real_escape_string($this->IsSecurityRisk)),
			array('Is_Bidding', '"%s"', mysql_real_escape_string($this->IsBidding)),
			array('Is_Not_Received', '"%s"', mysql_real_escape_string($this->IsNotReceived)),
			array('Is_Collection', '"%s"', mysql_real_escape_string($this->IsCollection)),
			array('Is_Auto_Ship', '"%s"', mysql_real_escape_string($this->IsAutoShip)),
			array('Billing_Title', '"%s"', mysql_real_escape_string(stripslashes($this->Billing->Title))),
			array('Billing_First_Name', '"%s"', mysql_real_escape_string(stripslashes($this->Billing->Name))),
			array('Billing_First_Name_Search', '"%s"', mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Billing->Name))),
			array('Billing_Initial', '"%s"', mysql_real_escape_string($this->Billing->Initial)),
			array('Billing_Last_Name', '"%s"', mysql_real_escape_string(stripslashes($this->Billing->LastName))),
			array('Billing_Last_Name_Search', '"%s"', mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Billing->LastName))),
			array('Billing_Organisation_Name', '"%s"', mysql_real_escape_string(stripslashes($this->BillingOrg))),
			array('Billing_Organisation_Name_Search', '"%s"', mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->BillingOrg))),
			array('Billing_Address_1', '"%s"', mysql_real_escape_string(stripslashes($this->Billing->Address->Line1))),
			array('Billing_Address_2', '"%s"', mysql_real_escape_string(stripslashes($this->Billing->Address->Line2))),
			array('Billing_Address_3', '"%s"', mysql_real_escape_string(stripslashes($this->Billing->Address->Line3))),
			array('Billing_City', '"%s"', mysql_real_escape_string(stripslashes($this->Billing->Address->City))),
			array('Billing_Country_ID', '%d', mysql_real_escape_string($this->Billing->Address->Country->ID)),
			array('Billing_Region_ID', '%d', mysql_real_escape_string($this->Billing->Address->Region->ID)),
			array('Billing_Zip', '"%s"', mysql_real_escape_string(stripslashes($this->Billing->Address->Zip))),
			array('Billing_Zip_Search', '"%s"', mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Billing->Address->Zip))),
			array('Shipping_Title', '"%s"', mysql_real_escape_string(stripslashes($this->Shipping->Title))),
			array('Shipping_First_Name', '"%s"', mysql_real_escape_string(stripslashes($this->Shipping->Name))),
			array('Shipping_First_Name_Search', '"%s"', mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Shipping->Name))),
			array('Shipping_Initial', '"%s"', mysql_real_escape_string($this->Shipping->Initial)),
			array('Shipping_Last_Name', '"%s"', mysql_real_escape_string(stripslashes($this->Shipping->LastName))),
			array('Shipping_Last_Name_Search', '"%s"', mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Shipping->LastName))),
			array('Shipping_Organisation_Name', '"%s"', mysql_real_escape_string(stripslashes($this->ShippingOrg))),
			array('Shipping_Organisation_Name_Search', '"%s"', mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->ShippingOrg))),
			array('Shipping_Address_1', '"%s"', mysql_real_escape_string(stripslashes($this->Shipping->Address->Line1))),
			array('Shipping_Address_2', '"%s"', mysql_real_escape_string(stripslashes($this->Shipping->Address->Line2))),
			array('Shipping_Address_3', '"%s"', mysql_real_escape_string(stripslashes($this->Shipping->Address->Line3))),
			array('Shipping_City', '"%s"', mysql_real_escape_string(stripslashes($this->Shipping->Address->City))),
			array('Shipping_Country_ID', '%d', mysql_real_escape_string($this->Shipping->Address->Country->ID)),
			array('Shipping_Region_ID', '%d', mysql_real_escape_string($this->Shipping->Address->Region->ID)),
			array('Shipping_Zip', '"%s"', mysql_real_escape_string(stripslashes($this->Shipping->Address->Zip))),
			array('Shipping_Zip_Search', '"%s"', mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Shipping->Address->Zip))),
			array('Invoice_Title', '"%s"', mysql_real_escape_string(stripslashes($this->Invoice->Title))),
			array('Invoice_First_Name', '"%s"', mysql_real_escape_string(stripslashes($this->Invoice->Name))),
			array('Invoice_First_Name_Search', '"%s"', mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Invoice->Name))),
			array('Invoice_Initial', '"%s"', mysql_real_escape_string($this->Invoice->Initial)),
			array('Invoice_Last_Name', '"%s"', mysql_real_escape_string(stripslashes($this->Invoice->LastName))),
			array('Invoice_Last_Name_Search', '"%s"', mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Invoice->LastName))),
			array('Invoice_Organisation_Name', '"%s"', mysql_real_escape_string(stripslashes($this->InvoiceOrg))),
			array('Invoice_Organisation_Name_Search', '"%s"', mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->InvoiceOrg))),
			array('Invoice_Address_1', '"%s"', mysql_real_escape_string(stripslashes($this->Invoice->Address->Line1))),
			array('Invoice_Address_2', '"%s"', mysql_real_escape_string(stripslashes($this->Invoice->Address->Line2))),
			array('Invoice_Address_3', '"%s"', mysql_real_escape_string(stripslashes($this->Invoice->Address->Line3))),
			array('Invoice_City', '"%s"', mysql_real_escape_string(stripslashes($this->Invoice->Address->City))),
			array('Invoice_Country_ID', '%d', mysql_real_escape_string($this->Invoice->Address->Country->ID)),
			array('Invoice_Region_ID', '%d', mysql_real_escape_string($this->Invoice->Address->Region->ID)),
			array('Invoice_Zip', '"%s"', mysql_real_escape_string(stripslashes($this->Invoice->Address->Zip))),
			array('Invoice_Zip_Search', '"%s"', mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Invoice->Address->Zip))),
			array('Nominal_Code', '%d', mysql_real_escape_string($this->NominalCode)),
			array('Referrer', '"%s"', mysql_real_escape_string($this->Referrer)),
			array('Payment_Method_ID', '%d', mysql_real_escape_string($this->PaymentMethod->ID)),
			array('Card_Payment_Method', '%d', mysql_real_escape_string($this->Card->Type->ID)),
			array('Card_Type', '"%s"', mysql_real_escape_string($this->Card->Type->Name)),
			array('Card_Number', '"%s"', mysql_real_escape_string($this->Card->Number)),
			array('Card_Title', '"%s"', mysql_real_escape_string(stripslashes($this->Card->Title))),
			array('Card_Initial', '"%s"', mysql_real_escape_string($this->Card->Initial)),
			array('Card_Surname', '"%s"', mysql_real_escape_string(stripslashes($this->Card->Surname))),
			array('Card_Expires', '"%s"', mysql_real_escape_string($this->Card->Expires)),
			array('Weight', '%f', mysql_real_escape_string($this->Weight)),
			array('Postage_ID', '%d', mysql_real_escape_string($this->Postage->ID)),
			array('Is_Plain_Label', '"%s"', mysql_real_escape_string($this->IsPlainLabel)),
			array('Is_Notes_Unread', '"%s"', mysql_real_escape_string($this->IsNotesUnread)),
			array('Is_Awaiting_Customer', '"%s"', mysql_real_escape_string($this->IsAwaitingCustomer)),
			array('PaymentReceivedOn', '"%s"', mysql_real_escape_string($this->PaymentReceivedOn)),
			array('Owned_By', '%d', mysql_real_escape_string($this->OwnedBy)),
			array('CancelledOn', '"%s"', mysql_real_escape_string($this->CancelledOn)),
			array('CancelledBy', '%d', mysql_real_escape_string($this->CancelledBy)),
			array('CancelledReason', '"%s"', mysql_real_escape_string($this->CancelledReason)),
			array('CancelledComment', '"%s"', mysql_real_escape_string($this->CancelledComment)),
			array('Created_On', 'NOW()', null),
			array('Created_By', '%d', mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])),
			array('Modified_On', 'NOW()', null),
			array('Modified_By', '%d', mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])),
			array('Free_Text', '"%s"', mysql_real_escape_string($this->FreeText)),
			array('Free_Text_Value', '%f', mysql_real_escape_string($this->FreeTextValue)),
			array('IsTaxExemptValid', '"%s"', mysql_real_escape_string($this->IsTaxExemptValid)),
			array('TaxExemptCode', '"%s"', mysql_real_escape_string($this->TaxExemptCode)),
			array('Backordered', '"%s"', mysql_real_escape_string($this->Backordered)),
			array('Discount_Reward', '%f', mysql_real_escape_string($this->DiscountReward)),
			array('Discount_Banding_ID', '%d', mysql_real_escape_string($this->DiscountBandingID)),
			array('Delivery_Instructions', '"%s"', mysql_real_escape_string(stripslashes($this->DeliveryInstructions))),
			array('Affiliate_ID', '%d', mysql_real_escape_string($this->AffiliateID)),
			array('Deadline_On', '"%s"', mysql_real_escape_string($this->DeadlineOn)),
			array('Confirmed_On', '"%s"', mysql_real_escape_string($this->ConfirmedOn)),
			array('Confirmed_By', '%d', mysql_real_escape_string($this->ConfirmedBy)),
			array('Confirmed_Notes', '"%s"', mysql_real_escape_string($this->ConfirmedNotes)),
			array('Basket_ID', '%d', mysql_real_escape_string($this->BasketID)),
			array('Additional_Email', '"%s"', mysql_real_escape_string($this->AdditionalEmail)),
			array('Additional_Emailed_On', '"%s"', mysql_real_escape_string(stripslashes($this->AdditionalEmailedOn))),
			array('Is_Dismissed', '"%s"', mysql_real_escape_string($this->IsDismissed))
		);

		$columns = array();
		$values = array();

		foreach ($fields as $field) {
			$columns[] = '`' . $field[0] . '`';
			$values[] = sprintf($field[1], $field[2]);
		}

		$sql = sprintf("INSERT INTO orders (%s) VALUES (%s)", join(",", $columns), join(",", $values));

		$data = new DataQuery($sql);

		$this->ID = $data->InsertID;

		Enquiry::EnquiryOrdered($this->Customer->ID);

		$this->SetInvoiceAddress();
	}
	
	function AddQuote($fileField = null) {
		if(!is_null($fileField) && isset($_FILES[$fileField]) && !empty($_FILES[$fileField]['name'])) {
			if(!$this->CourierQuoteFile->Upload($fileField)){
				return false;
			}
		}

		return true;
	}

	public function AddSuggestion($line, $suggestion) {
		$this->Suggestion[] = array('Line' => $line, 'Suggestion' => $suggestion);
	}
	
	function UpdateQuote($fileField = null) {
		$oldFile = new IFile($this->CourierQuoteFile->FileName, $this->CourierQuoteFile->Directory);
		
		if(!is_null($fileField) && isset($_FILES[$fileField]) && !empty($_FILES[$fileField]['name'])) {
			if(!$this->CourierQuoteFile->Upload($fileField)){
				return false;
			} else {
				$oldFile->Delete();
			}
		}
		
		return true;
	}

	function Delete($id = NULL, $reason = '', $comment = '') {
		if(!is_null($id)) { $this->ID = $id; }
		if(!is_numeric($this->ID)) { return false; }

		$this->Get();

		new DataQuery("delete from orders where Order_ID=" . $this->ID);
		OrderLine::DeleteOrder($this->ID);
		OrderNote::DeleteOrder($this->ID);
		OrderBidding::DeleteOrder($this->ID);
		OrderContact::DeleteOrder($this->ID); 
		OrderShipping::DeleteOrder($this->ID);

		if($this->PaymentMethod->Reference == 'google') {
			$googleRequest = new GoogleRequest();

			if (stristr($this->Status, 'Unread') || stristr($this->Status, 'Pending')) {
				if ($googleRequest->cancelOrder($this->CustomID, $reason, $comment)) {
					$googleRequest->archiveOrder($this->CustomID);
				}
			} elseif (stristr($this->Status, 'Confirmed') || stristr($this->Status, 'Packing')) {
				if ($googleRequest->refundOrder($this->CustomID, $this->Total)) {
					if ($googleRequest->cancelOrder($this->CustomID, $reason, $comment)) {
						$googleRequest->archiveOrder($this->CustomID);
					}
				}
			}
		}
		DiscountBandingOrder::DeleteOrder($this->ID);
		
		if(!empty($this->CourierQuoteFile->FileName) && $this->CourierQuoteFile->Exists()) {
			$this->CourierQuoteFile->Delete();
		}
	}

	function CancelItems($items, $reason = '', $comment = '') {
		if($this->PaymentMethod->Reference == 'google') {
			$googleRequest = new GoogleRequest();
			return $googleRequest->cancelItems($this->CustomID, $items, $reason, $comment);
		}

		return true;
	}

	function CancelLines($lines, $reason = '', $comment = '') {
		$refundAmount = 0;
		$cancelItems = array();
		$return = true;
		$googleRequest = new GoogleRequest();

		for ($i = 0; $i < count($lines); $i++) {
			if (empty($lines[$i]->DespatchID) && empty($lines[$i]->InvoiceID) && ($lines[$i]->Status != 'Cancelled')) {
				$refundAmount += $lines[$i]->Total - $lines[$i]->Discount + $lines[$i]->Tax;
				$cancelItems[] = $lines[$i]->Product->ID;
			}
		}

		if($this->PaymentMethod->Reference == 'google') {
			$return = false;

			if ($googleRequest->refundOrder($this->CustomID, $refundAmount)) {
				$return = $this->CancelItems($cancelItems, $reason, $comment);
			}
		}

		if ($return) {
			for ($i = 0; $i < count($lines); $i++) {
				if (empty($lines[$i]->DespatchID) && empty($lines[$i]->InvoiceID) && ($lines[$i]->Status != 'Cancelled')) {
					$lines[$i]->Status = 'Cancelled';
					$lines[$i]->Update();
				}
			}

			$this->GetLines();
			$validLines = 0;

			for ($i = 0; $i < count($this->Line); $i++) {
				if ($this->Line[$i]->Status != 'Cancelled') {
					$validLines++;
				}
			}

			if ($validLines == 0) {
				if (stristr($this->Status, 'Partially Despatched')) {
					$this->Status = 'Despatched';
				} else {
					$this->Status = 'Cancelled';
				}

				$this->Update();
			}
		}

		return $return;
	}

	function Cancel($reason = '', $comment = '') {
		$this->GetLines();

		$paymentId = 0;
		$payment = new Payment();

		if($this->PaymentMethod->Reference == 'google') {
			$googleRequest = new GoogleRequest();

			if (stristr($this->Status, 'Unread') || stristr($this->Status, 'Pending')) {
				if ($googleRequest->cancelOrder($this->CustomID, $reason, $comment)) {
					$googleRequest->archiveOrder($this->CustomID);

					for ($i = 0; $i < count($this->Line); $i++) {
						$this->Line[$i]->Status = 'Cancelled';
						$this->Line[$i]->Update();
					}

					$this->Status = 'Cancelled';
					$this->CancelledOn = now();
					$this->CancelledBy = $GLOBALS['SESSION_USER_ID'];
					$this->CancelledReason = $reason;
					$this->CancelledComment = $comment;
					$this->Update();
				}
			} elseif (stristr($this->Status, 'Confirmed') || stristr($this->Status, 'Packing')) {
				if ($googleRequest->refundOrder($this->CustomID, $this->Total)) {
					if ($googleRequest->cancelOrder($this->CustomID, $reason, $comment)) {
						$googleRequest->archiveOrder($this->CustomID);

						for ($i = 0; $i < count($this->Line); $i++) {
							$this->Line[$i]->Status = 'Cancelled';
							$this->Line[$i]->Update();
						}

						$this->Status = 'Cancelled';
						$this->CancelledOn = now();
						$this->CancelledBy = $GLOBALS['SESSION_USER_ID'];
						$this->CancelledReason = $reason;
						$this->CancelledComment = $comment;
						$this->Update();
					}
				}
			} elseif (stristr($this->Status, 'Partially Despatched')) {
				$lines = array();

				for ($i = 0; $i < count($this->Line); $i++) {
					if (empty($this->Line[$i]->DespatchID) && empty($this->Line[$i]->InvoiceID) && ($this->Line[$i]->Status != 'Cancelled')) {
						$lines[] = $this->Line[$i];
					}
				}

				if ($this->CancelLines($lines, $reason, $comment)) {
					$googleRequest->archiveOrder($this->CustomID);
				}
			}
		} else {
			if(!is_numeric($this->ID)){
				return false;
			}
			$data = new DataQuery(sprintf("SELECT Payment_ID, Transaction_Type FROM payment WHERE ((Transaction_Type LIKE '3DAUTH' AND Status LIKE 'AUTHENTICATED') OR (Transaction_Type LIKE 'AUTHENTICATE' AND Status LIKE 'REGISTERED')) AND Order_ID=%d", mysql_real_escape_string($this->ID)));
			if ($data->TotalRows > 0) {

				if ($data->Row['Transaction_Type'] == '3DAUTH') {
					$data2 = new DataQuery(sprintf("SELECT Payment_ID FROM payment WHERE Transaction_Type LIKE 'AUTHENTICATE' AND Status LIKE '3DAUTH' AND Order_ID=%d", mysql_real_escape_string($this->ID)));
					if ($data2->TotalRows > 0) {
						$paymentId = $data2->Row['Payment_ID'];
					}
					$data2->Disconnect();

				} else {
					$paymentId = $data->Row['Payment_ID'];
				}

				if ($paymentId > 0) {
					$payment->Get($paymentId);

					$gateway = new PaymentGateway();
					$hasGateway = $gateway->GetDefault();

					if ($hasGateway) {
						require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/gateways/' . $gateway->ClassFile);

						$paymentProcessor = new PaymentProcessor($gateway->VendorName, $gateway->IsTestMode);
						$paymentProcessor->Description = $GLOBALS['COMPANY'] . ' Authentication Cancellation';
						$paymentProcessor->Payment->Gateway->ID = $gateway->ID;
						$paymentProcessor->Payment->Order->ID = $this->ID;
						$paymentProcessor->Cancel($payment);
					}
				}
			}
			$data->Disconnect();

			if (stristr($this->Status, 'Partially Despatched')) {
				for ($i = 0; $i < count($this->Line); $i++) {
					if (empty($this->Line[$i]->DespatchID) && empty($this->Line[$i]->InvoiceID)) {
						$this->Line[$i]->Status = 'Cancelled';
						$this->Line[$i]->Update();
					}
				}

				$this->Status = 'Despatched';
			} else {
				for ($i = 0; $i < count($this->Line); $i++) {
					$this->Line[$i]->Status = 'Cancelled';
					$this->Line[$i]->Update();
				}

				$this->Status = 'Cancelled';
				$this->CancelledOn = now();
				$this->CancelledBy = $GLOBALS['SESSION_USER_ID'];
				$this->CancelledReason = $reason;
				$this->CancelledComment = $comment;
				$this->Update();
			}

			$this->Update();
		}
	}

	function NotifyCancellation($lines = array()) {
		$this->Customer->Get();
		$this->Customer->Contact->Get();

		$lineHtml = '';

		if(count($lines) > 0) {
			for($i=0; $i<count($lines); $i++) {
				$lineHtml .= sprintf('<tr><td>%sx</td><td>%s</td><td>%s</td></tr>', $lines[$i]->Quantity, (($lines[$i]->IsAssociative == 'N') || ($lines[$i]->Product->ID > 0)) ? $lines[$i]->Product->Name : $lines[$i]->AssociativeProductTitle, ($lines[$i]->Product->ID > 0) ? $lines[$i]->Product->ID : '-');
			}
		} else {
			$this->GetLines();

			for($i=0; $i<count($this->Line); $i++) {
				if(strtolower($this->Line[$i]->Status) != 'cancelled') {
					$lineHtml .= sprintf('<tr><td>%sx</td><td>%s</td><td>%s</td></tr>', $this->Line[$i]->Quantity, (($lines[$i]->IsAssociative == 'N') || ($this->Line[$i]->Product->ID > 0)) ? $this->Line[$i]->Product->Name : $this->Line[$i]->AssociativeProductTitle, ($this->Line[$i]->Product->ID > 0) ? $this->Line[$i]->Product->ID : '-');
				}
			}
		}

		$findReplace = new FindReplace();
		$findReplace->Add('/\[ORDER_REF\]/', $this->Prefix . $this->ID);
		$findReplace->Add('/\[ORDER_DATE\]/', cDatetime($this->OrderedOn, 'longdate'));
		$findReplace->Add('/\[CUSTOMER_NAME\]/', $this->Customer->Contact->Person->GetFullName());
		$findReplace->Add('/\[CUSTOMER_ID\]/', $this->Customer->Contact->ID);
		$findReplace->Add('/\[BILLTO\]/', $this->GetBillingAddress());
		$findReplace->Add('/\[SHIPTO\]/', $this->GetShippingAddress());
		$findReplace->Add('/\[ORDER_LINES\]/', $lineHtml);

		$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/order_cancellation.tpl");
		$orderHtml = '';

		for($i=0; $i<count($orderEmail); $i++) {
			$orderHtml .= $findReplace->Execute($orderEmail[$i]);
		}

		$findReplace = new FindReplace();
		$findReplace->Add('/\[BODY\]/', $orderHtml);
		$findReplace->Add('/\[NAME\]/', 'Sales Order Manager');

		$templateHtml = $findReplace->Execute(Template::GetContent('email_template_standard'));

		$mail = new htmlMimeMail5();
		$mail->setFrom($GLOBALS['EMAIL_FROM']);
		$mail->setSubject(sprintf("%s Order Cancellation [%s%s]", $GLOBALS['COMPANY'], $this->Prefix, $this->ID));
		$mail->setText('This is an HTML email. If you only see this text your email client only supports plain text emails.');
		$mail->setHTML($templateHtml);
		$mail->send(array('gary@bltdirect.com'));
	}

    function NotifyUnbackorder($lines = array()) {
		$this->Customer->Get();
		$this->Customer->Contact->Get();

		$suppliers = array();

		foreach($lines as $supplierId=>$lines) {
			if(!isset($suppliers[$supplierId])) {
				$suppliers[$supplierId] = '';
			}

			for($i=0; $i<count($lines); $i++) {
				$suppliers[$supplierId] .= sprintf('<tr><td>%sx</td><td>%s</td><td>%s</td></tr>', $lines[$i]->Quantity, (($lines[$i]->IsAssociative == 'N') || ($lines[$i]->Product->ID > 0)) ? $lines[$i]->Product->Name : $lines[$i]->AssociativeProductTitle, ($lines[$i]->Product->ID > 0) ? $lines[$i]->Product->ID : '-');
			}
		}

		foreach($suppliers as $supplierId=>$lineHtml) {
			$supplier = new Supplier($supplierId);

			$findReplace = new FindReplace();
			$findReplace->Add('/\[ORDER_ID\]/', $this->ID);
			$findReplace->Add('/\[ORDER_REFERENCE\]/', $this->Prefix . $this->ID);
			$findReplace->Add('/\[ORDER_DATE\]/', cDatetime($this->OrderedOn, 'longdate'));
	        $findReplace->Add('/\[ORDER_BILLING_ADDRESS\]/', $this->GetBillingAddress());
			$findReplace->Add('/\[ORDER_SHIPPING_ADDRESS\]/', $this->GetShippingAddress());
			$findReplace->Add('/\[ORDER_LINES\]/', $lineHtml);
			$findReplace->Add('/\[CUSTOMER_ID\]/', $this->Customer->Contact->ID);
			$findReplace->Add('/\[CUSTOMER_NAME\]/', $this->Customer->Contact->Person->GetFullName());

			$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/order_unbackorder.tpl");
			$orderHtml = '';

			for($i=0; $i<count($orderEmail); $i++) {
				$orderHtml .= $findReplace->Execute($orderEmail[$i]);
			}

			$findReplace = new FindReplace();
			$findReplace->Add('/\[BODY\]/', $orderHtml);
			$findReplace->Add('/\[NAME\]/', 'Sales Order Manager');

			$templateHtml = $findReplace->Execute(Template::GetContent('email_template_standard'));

			$queue = new EmailQueue();
			$queue->GetModuleID('orders');
			$queue->Subject = sprintf("%s Order Unbackorder [%s%s]", $GLOBALS['COMPANY'], $this->Prefix, $this->ID);
			$queue->Body = $templateHtml;
			$queue->ToAddress = $supplier->GetEmail();
			$queue->Priority = 'H';
			$queue->Add();
		}
	}

	function EmailNotReceivedConfirmation() {
		$this->Customer->Get();
		$this->Customer->Contact->Get();

		$this->GetLines();

		$lineHtml = '';

		for($i=0; $i<count($this->Line); $i++) {
			if(strtolower($this->Line[$i]->Status) != 'cancelled') {
				if($this->Line[$i]->QuantityNotReceived > 0) {
					$lineHtml .= sprintf('<tr><td>%sx</td><td>%s</td><td>%s</td></tr>', $this->Line[$i]->QuantityNotReceived, (($lines[$i]->IsAssociative == 'N') || ($this->Line[$i]->Product->ID > 0)) ? $this->Line[$i]->Product->Name : $this->Line[$i]->AssociativeProductTitle, ($this->Line[$i]->Product->ID > 0) ? $this->Line[$i]->Product->ID : '-');
				}
			}
		}

		$findReplace = new FindReplace();
		$findReplace->Add('/\[ORDER_REF\]/', $this->Prefix . $this->ID);
		$findReplace->Add('/\[ORDER_DATE\]/', cDatetime($this->OrderedOn, 'longdate'));
		$findReplace->Add('/\[CUSTOMER_NAME\]/', $this->Customer->Contact->Person->GetFullName());
		$findReplace->Add('/\[CUSTOMER_ID\]/', $this->Customer->Contact->ID);
		$findReplace->Add('/\[BILLTO\]/', $this->GetBillingAddress());
		$findReplace->Add('/\[SHIPTO\]/', $this->GetShippingAddress());
		$findReplace->Add('/\[ORDER_LINES\]/', $lineHtml);

		$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/order_notreceived.tpl");
		$orderHtml = '';

		for($i=0; $i<count($orderEmail); $i++) {
			$orderHtml .= $findReplace->Execute($orderEmail[$i]);
		}

		$findReplace = new FindReplace();
		$findReplace->Add('/\[BODY\]/', $orderHtml);
		$findReplace->Add('/\[NAME\]/', 'Sales Order Manager');

		$templateHtml = $findReplace->Execute(Template::GetContent('email_template_standard'));

        $queue = new EmailQueue();
		$queue->GetModuleID('orders');
		$queue->Subject = sprintf("%s Order Not Received [%s%s]", $GLOBALS['COMPANY'], $this->Prefix, $this->ID);
		$queue->Body = $templateHtml;
		$queue->FromAddress = 'not-received@bltdirect.com';
		$queue->ToAddress = $this->Customer->GetEmail();
		$queue->Priority = 'H';
		$queue->Add();
	}

	function Update() {
		if((strtolower($this->Status) == 'despatched') || (strtolower($this->Status) == 'cancelled')) {
			$this->IsAbsentStockProfile = 'N';
		}

		if(!is_numeric($this->ID)){
			return false;
		}
		
		$this->CalculateNominalCode();
		
		$sql = sprintf("UPDATE orders SET Parent_ID=%d, ProformaID=%d, Is_Sample='%s', Is_Payment_Unverified='%s', Is_Declined='%s', Is_Failed='%s', Is_Restocked='%s', Is_Warehouse_Declined='%s', Is_Warehouse_Declined_Read='%s', Is_Warehouse_Undeclined='%s', Is_Warehouse_Backordered='%s', Is_Absent_Stock_Profile='%s',
							Quote_ID=%d, Return_ID=%d, IsFreeTextDespatched='%s',
							Order_Prefix='%s', Customer_ID=%d, Coupon_ID=%d, Original_Coupon_ID=%d,
							Custom_Order_No='%s', Custom_Order_No_Search='%s', Courier_Quote_File='%s', Courier_Quote_Amount=%f, Ordered_On='%s', Emailed_On='%s',
							Emailed_To='%s', Received_On='%s', Received_By=%d,
							Invoiced_On='%s', Paid_On='%s', Despatched_On='%s',
							Status='%s', DeviceBrowser='%s', DeviceVersion='%s', DevicePlatform='%s', Total_Lines=%d, SubTotal=%f, Sub_Total_Retail=%f,
							TotalShipping=%f, IsCustomShipping='%s', IsTaxExemptValid='%s', TaxExemptCode='%s', TotalDiscount=%f,
							TotalTax=%f, Total=%f, Tax_Rate=%f, Is_Active='%s', Is_Security_Risk='%s', Is_Bidding='%s', Is_Not_Received='%s', Is_Collection='%s', Is_Auto_Ship='%s', 
							Billing_Title='%s',
							Billing_First_Name='%s', Billing_First_Name_Search='%s', Billing_Initial='%s',
							Billing_Last_Name='%s', Billing_Last_Name_Search='%s', Billing_Organisation_Name='%s', Billing_Organisation_Name_Search='%s',
							Billing_Address_1='%s', Billing_Address_2='%s',
							Billing_Address_3='%s', Billing_City='%s',
							Billing_Country_ID=%d, Billing_Region_ID=%d,
							Billing_Zip='%s', Billing_Zip_Search='%s',
							Shipping_Title='%s',
							Shipping_First_Name='%s', Shipping_First_Name_Search='%s', Shipping_Initial='%s',
							Shipping_Last_Name='%s', Shipping_Last_Name_Search='%s', Shipping_Organisation_Name='%s', Shipping_Organisation_Name_Search='%s',
							Shipping_Address_1='%s', Shipping_Address_2='%s',
							Shipping_Address_3='%s', Shipping_City='%s',
							Shipping_Country_ID=%d, Shipping_Region_ID=%d,
							Shipping_Zip='%s', Shipping_Zip_Search='%s',
							Invoice_Title='%s',
							Invoice_First_Name='%s', Invoice_First_Name_Search='%s', Invoice_Initial='%s',
							Invoice_Last_Name='%s', Invoice_Last_Name_Search='%s', Invoice_Organisation_Name='%s', Invoice_Organisation_Name_Search='%s',
							Invoice_Address_1='%s', Invoice_Address_2='%s',
							Invoice_Address_3='%s', Invoice_City='%s',
							Invoice_Country_ID=%d, Invoice_Region_ID=%d,
							Invoice_Zip='%s', Invoice_Zip_Search='%s',
							Nominal_Code=%d, Referrer='%s',
							Payment_Method_ID=%d, Card_Payment_Method=%d, Card_Type='%s', Card_Number='%s',
							Card_Title='%s', Card_Initial='%s', Card_Surname='%s',Card_Expires='%s',
							Weight=%f, Postage_ID=%d, Is_Plain_Label='%s', Is_Notes_Unread='%s', Is_Awaiting_Customer='%s',
							PaymentReceivedOn='%s', Owned_By=%d, CancelledOn='%s', CancelledBy=%d, CancelledReason='%s', CancelledComment='%s', Modified_On=NOW(), Modified_By=%d, Free_Text='%s', Free_Text_Value=%f,
							Backordered='%s', Discount_Reward=%f, Discount_Banding_ID=%d, Delivery_Instructions='%s', Affiliate_ID=%d, Deadline_On='%s', Confirmed_On='%s', Confirmed_By=%d, Confirmed_Notes='%s', Basket_ID=%d, Additional_Email='%s', Additional_Emailed_On='%s', Is_Dismissed='%s'



							WHERE Order_ID=%d", mysql_real_escape_string($this->ParentID),
								mysql_real_escape_string($this->ProformaID),
								mysql_real_escape_string($this->Sample),
								mysql_real_escape_string($this->IsPaymentUnverified),
								mysql_real_escape_string($this->IsDeclined), 
								mysql_real_escape_string($this->IsFailed), 
								mysql_real_escape_string($this->IsRestocked),
								mysql_real_escape_string($this->IsWarehouseDeclined),
								mysql_real_escape_string($this->IsWarehouseDeclinedRead),
								mysql_real_escape_string($this->IsWarehouseUndeclined),
								mysql_real_escape_string($this->IsWarehouseBackordered), 
								mysql_real_escape_string($this->IsAbsentStockProfile),
								mysql_real_escape_string($this->QuoteID),
								mysql_real_escape_string($this->ReturnID),
								mysql_real_escape_string($this->IsFreeTextDespatched),
								mysql_real_escape_string($this->Prefix), 
								mysql_real_escape_string($this->Customer->ID),
								mysql_real_escape_string($this->Coupon->ID), 
								mysql_real_escape_string($this->OriginalCoupon->ID), 
								mysql_real_escape_string(stripslashes($this->CustomID)),
								mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->CustomID)),
								mysql_real_escape_string($this->CourierQuoteFile->FileName),
								mysql_real_escape_string($this->CourierQuoteAmount), 
								mysql_real_escape_string($this->OrderedOn), 
								mysql_real_escape_string($this->EmailedOn),
								mysql_real_escape_string(stripslashes($this->EmailedTo)),
								mysql_real_escape_string($this->ReceivedOn),
								mysql_real_escape_string($this->ReceivedBy),
								mysql_real_escape_string($this->InvoicedOn),
								mysql_real_escape_string($this->PaidOn),
								mysql_real_escape_string($this->DespatchedOn),
								mysql_real_escape_string($this->Status),
								mysql_real_escape_string($this->DeviceBrowser), 
								mysql_real_escape_string($this->DeviceVersion), 
								mysql_real_escape_string($this->DevicePlatform), 
								mysql_real_escape_string($this->TotalLines),
								mysql_real_escape_string($this->SubTotal), 
								mysql_real_escape_string($this->SubTotalRetail), 
								mysql_real_escape_string($this->TotalShipping),
								mysql_real_escape_string($this->IsCustomShipping),
								mysql_real_escape_string($this->IsTaxExemptValid), 
								mysql_real_escape_string($this->TaxExemptCode), 
								mysql_real_escape_string($this->TotalDiscount),
								mysql_real_escape_string($this->TotalTax),
								mysql_real_escape_string($this->Total),
								mysql_real_escape_string($this->TaxRate),
								mysql_real_escape_string($this->IsActive),
								mysql_real_escape_string($this->IsSecurityRisk), 
								mysql_real_escape_string($this->IsBidding), 
								mysql_real_escape_string($this->IsNotReceived),
								mysql_real_escape_string($this->IsCollection), 
								mysql_real_escape_string($this->IsAutoShip),
								mysql_real_escape_string($this->Billing->Title), 
								mysql_real_escape_string(stripslashes($this->Billing->Name)),
								mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Billing->Name)),
								mysql_real_escape_string($this->Billing->Initial), 
								mysql_real_escape_string(stripslashes($this->Billing->LastName)),
								mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Billing->LastName)), 
								mysql_real_escape_string(stripslashes($this->BillingOrg)), 
								mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->BillingOrg)),
								mysql_real_escape_string(stripslashes($this->Billing->Address->Line1)),
								mysql_real_escape_string(stripslashes($this->Billing->Address->Line2)),
								mysql_real_escape_string(stripslashes($this->Billing->Address->Line3)),
								mysql_real_escape_string(stripslashes($this->Billing->Address->City)),
								mysql_real_escape_string($this->Billing->Address->Country->ID),
								mysql_real_escape_string($this->Billing->Address->Region->ID),
								mysql_real_escape_string(stripslashes($this->Billing->Address->Zip)),
								mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Billing->Address->Zip)), 
								mysql_real_escape_string(stripslashes($this->Shipping->Title)), 
								mysql_real_escape_string(stripslashes($this->Shipping->Name)),
								mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Shipping->Name)),
								mysql_real_escape_string($this->Shipping->Initial), 
								mysql_real_escape_string(stripslashes($this->Shipping->LastName)), 
								mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Shipping->LastName)),
								mysql_real_escape_string(stripslashes($this->ShippingOrg)),
								mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->ShippingOrg)),
								mysql_real_escape_string(stripslashes($this->Shipping->Address->Line1)), 
								mysql_real_escape_string(stripslashes($this->Shipping->Address->Line2)), 
								mysql_real_escape_string(stripslashes($this->Shipping->Address->Line3)),
								mysql_real_escape_string(stripslashes($this->Shipping->Address->City)),
								mysql_real_escape_string($this->Shipping->Address->Country->ID), 
								mysql_real_escape_string($this->Shipping->Address->Region->ID),
								mysql_real_escape_string(stripslashes($this->Shipping->Address->Zip)),
								mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Shipping->Address->Zip)),
								mysql_real_escape_string(stripslashes($this->Invoice->Title)), 
								mysql_real_escape_string(stripslashes($this->Invoice->Name)), 
								mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Invoice->Name)), 
								mysql_real_escape_string($this->Invoice->Initial), 
								mysql_real_escape_string(stripslashes($this->Invoice->LastName)),
								mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Invoice->LastName)),
								mysql_real_escape_string(stripslashes($this->InvoiceOrg)), 
								mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->InvoiceOrg)),
								mysql_real_escape_string(stripslashes($this->Invoice->Address->Line1)), 
								mysql_real_escape_string(stripslashes($this->Invoice->Address->Line2)), 
								mysql_real_escape_string(stripslashes($this->Invoice->Address->Line3)), 
								mysql_real_escape_string(stripslashes($this->Invoice->Address->City)), 
								mysql_real_escape_string($this->Invoice->Address->Country->ID), 
								mysql_real_escape_string($this->Invoice->Address->Region->ID),
								mysql_real_escape_string(stripslashes($this->Invoice->Address->Zip)), 
								mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Invoice->Address->Zip)),
								mysql_real_escape_string($this->NominalCode), 
								mysql_real_escape_string($this->Referrer), 
								mysql_real_escape_string($this->PaymentMethod->ID),
								mysql_real_escape_string($this->Card->Type->ID), 
								mysql_real_escape_string($this->Card->Type->Name), 
								mysql_real_escape_string($this->Card->Number), 
								mysql_real_escape_string(stripslashes($this->Card->Title)),
								mysql_real_escape_string($this->Card->Initial),
								mysql_real_escape_string(stripslashes($this->Card->Surname)), 
								mysql_real_escape_string($this->Card->Expires),
								mysql_real_escape_string($this->Weight), 
								mysql_real_escape_string($this->Postage->ID), 
								mysql_real_escape_string($this->IsPlainLabel), 
								mysql_real_escape_string($this->IsNotesUnread),
								mysql_real_escape_string($this->IsAwaitingCustomer), 
								mysql_real_escape_string($this->PaymentReceivedOn), 
								mysql_real_escape_string($this->OwnedBy), 
								mysql_real_escape_string($this->CancelledOn),
								mysql_real_escape_string($this->CancelledBy),
								mysql_real_escape_string($this->CancelledReason),
								mysql_real_escape_string($this->CancelledComment),
								mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
								mysql_real_escape_string($this->FreeText), 
								mysql_real_escape_string($this->FreeTextValue), 
								mysql_real_escape_string($this->Backordered),
								mysql_real_escape_string($this->DiscountReward), 
								mysql_real_escape_string($this->DiscountBandingID), 
								mysql_real_escape_string(stripslashes($this->DeliveryInstructions)), 
								mysql_real_escape_string($this->AffiliateID),
								mysql_real_escape_string($this->DeadlineOn),
								mysql_real_escape_string($this->ConfirmedOn),
								mysql_real_escape_string($this->ConfirmedBy),
								mysql_real_escape_string($this->ConfirmedNotes),
								mysql_real_escape_string($this->BasketID),
								mysql_real_escape_string($this->AdditionalEmail),
								mysql_real_escape_string(stripslashes($this->AdditionalEmailedOn)),
								mysql_real_escape_string($this->IsDismissed),
								mysql_real_escape_string($this->ID));
		new DataQuery($sql);
	}

	function GetLines() {
		$this->Line = array();
		$this->LinesFetched = true;
		$this->LinesHtml = '';

		$data = new DataQuery(sprintf("SELECT Order_Line_ID FROM order_line WHERE Order_ID=%d", mysql_real_escape_string($this->ID)));
		while ($data->Row) {
			$i = count($this->Line);

			$this->Line[$i] = new OrderLine($data->Row['Order_Line_ID']);

			if (!empty($this->Line[$i]->InvoiceID)) {
				$this->HasInvoices = true;
			}

			$this->LinesHtml .= sprintf("<tr><td>%sx</td><td>%s</td><td>%s</td><td align=\"right\">&pound;%s</td><td align=\"right\">&pound;%s</td><td align=\"right\">&pound;%s</td></tr>", $this->Line[$i]->Quantity, (($lines[$i]->IsAssociative == 'N') || ($this->Line[$i]->Product->ID > 0)) ? $this->Line[$i]->Product->Name : $this->Line[$i]->AssociativeProductTitle, ($this->Line[$i]->Product->ID > 0) ? $this->Line[$i]->Product->ID : '-', number_format($this->Line[$i]->Price, 2, '.', ','), number_format($this->Line[$i]->Price - ($this->Line[$i]->Discount / $this->Line[$i]->Quantity), 2, '.', ','), number_format($this->Line[$i]->Total - $this->Line[$i]->Discount, 2, '.', ','));

			$data->Next();
		}
		$data->Disconnect();
	}

    function GetBids() {
		$this->Bid = array();
		$this->BidsFetched = true;
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT OrderBiddingID FROM order_bidding WHERE OrderID=%d AND IsAccepted='N'", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->Bid[] = new OrderBidding($data->Row['OrderBiddingID']);

			$data->Next();
		}
		$data->Disconnect();
	}

	function GetSampleLines() {
		$this->SampleLinesHtml = '';

		$data = new DataQuery(sprintf("SELECT Order_Line_ID FROM order_line AS ol LEFT JOIN product AS p ON ol.Product_ID=p.Product_ID WHERE ol.Order_ID=%d", mysql_real_escape_string($this->ID)));
		while ($data->Row) {
			$line = new OrderLine($data->Row['Order_Line_ID']);

			$this->SampleLinesHtml .= sprintf('<tr><td>%sx</td><td>%s</td><td>%s</td></tr>', $line->Quantity, $line->Product->Name, $line->Product->ID);

			$data->Next();
		}
		$data->Disconnect();
	}

	function GetShippingLines() {
		$this->ShippingLine = array();
		$this->ShippingMultiplier = 0;
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT OrderShippingID FROM order_shipping WHERE OrderID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->ShippingLine[] = new OrderShipping($data->Row['OrderShippingID']);

			$data->Next();
		}
		$data->Disconnect();

		for($i=0; $i<count($this->ShippingLine); $i++) {
			$this->ShippingMultiplier += $this->ShippingLine[$i]->Quantity;
		}
	}

	function SendEmail($mailTo = '') {
		$this->PaymentMethod->Get();

		if (strlen($mailTo) == 0) {
			if(empty($this->Customer->Contact->ID)) $this->Customer->Get();
			$mailTo = $this->Customer->GetEmail();
		}

		if (count($this->Line) <= 0) {
			$this->GetLines();
		}
		
		$this->TotalNet = $this->SubTotal + $this->TotalShipping - $this->TotalDiscount;

		if (empty($this->Customer->Contact->ID))
			$this->Customer->Get();
		if (empty($this->Customer->Contact->Person->ID))
			$this->Customer->Contact->Get();
		$this->Postage->Get();

		$findReplace = new FindReplace();
		$findReplace->Add('/\[ORDER_REF\]/', $this->Prefix . $this->ID);
		$findReplace->Add('/\[CUSTOM_REF\]/', $this->CustomID);
		$findReplace->Add('/\[ORDER_DATE\]/', cDatetime($this->OrderedOn, 'longdate'));
		$findReplace->Add('/\[CUSTOMER_NAME\]/', $this->Customer->Contact->Person->GetFullName());
		$findReplace->Add('/\[CUSTOMER_ID\]/', $this->Customer->Contact->ID);
		$findReplace->Add('/\[BILLTO\]/', $this->GetBillingAddress());
		$findReplace->Add('/\[SHIPTO\]/', $this->GetShippingAddress());
		$findReplace->Add('/\[PAYMENT_METHOD\]/', $this->GetPaymentMethod());
		$findReplace->Add('/\[CARD_NUMBER\]/', ($this->PaymentMethod->Reference == 'card') ? $this->Card->PrivateNumber() : '');
		$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format($this->SubTotal, 2, '.', ','));
		$findReplace->Add('/\[SHIPPING\]/', "&pound;" . number_format($this->TotalShipping, 2, '.', ','));
		$findReplace->Add('/\[DISCOUNT\]/', "-&pound;" . number_format($this->TotalDiscount, 2, '.', ','));
		$findReplace->Add('/\[TAX\]/', "&pound;" . number_format($this->TotalTax, 2, '.', ','));
		$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format($this->Total, 2, '.', ','));
		$findReplace->Add('/\[ORDER_WEIGHT\]/', $this->Weight);
		$findReplace->Add('/\[DELIVERY\]/', $this->Postage->Name);
		$findReplace->Add('/\[ORDER_LINES\]/', $this->LinesHtml);
		$findReplace->Add('/\[NET\]/', "&pound;" . number_format($this->TotalNet, 2, '.', ','));

		if($this->Customer->Contact->IsTradeAccount == 'Y') {
			$findReplace->Add('/\[ORDER_TRADE_SAVING\]/', '&pound;' . number_format($this->SubTotalRetail - $this->SubTotal, 2, '.', ','));
			$findReplace->Add('/\[ORDER_TRADE_SAVING_PERCENTAGE\]/', ($this->SubTotalRetail > 0) ? (number_format(100 - (($this->SubTotal / $this->SubTotalRetail) * 100), 2, '.', ',') . '%') : '');
		}
		
		$handling = '';
		$handlingApplied = false;
		
		foreach($this->Line as $line) {
			if($line->HandlingCharge > 0) {
				$handlingApplied = true;
				break;
			}
		}
		
		if($handlingApplied) {
			$handling .= '<p>The following products within this order are subject to a restocking charge if they are returned to us:</p>';
			$handling .= '<ul>';
			
			foreach($this->Line as $line) {
				if($line->HandlingCharge > 0) {
					$handling .= sprintf('<li>%s (#%d) @ %s%%</li>', $line->Product->Name, $line->Product->ID, number_format($line->HandlingCharge, 2, '.', ''));
				}
			}
			
			$handling .= '</ul>';
		}
		
		$attachments = array();
		
		for($i=0; $i < count($this->Line); $i++) {
			if($this->Line[$i]->IncludeDownloads == 'Y') {
				$this->Line[$i]->Product->GetDownloads();
				
				foreach($this->Line[$i]->Product->Download as $download) {
					$attachments[] = array($GLOBALS['PRODUCT_DOWNLOAD_DIR_FS'] . $download->file->FileName, $GLOBALS['PRODUCT_DOWNLOAD_DIR_WS'] . $download->file->FileName);
				}
			}
		}
		
		$findReplace->Add('/\[ATTACHMENTS\]/', !empty($attachments) ? '<p>Please find attached data sheets for this quotation.</p>' : '');
		$findReplace->Add('/\[HANDLING\]/', $handling);
		
		$orderHtml = $findReplace->Execute(Template::GetContent('email_order' . (($this->Customer->Contact->IsTradeAccount == 'Y') ? '_trade' : '')));

		$findReplace = new FindReplace();
		$findReplace->Add('/\[BODY\]/', $orderHtml);
		$findReplace->Add('/\[NAME\]/', $this->Customer->Contact->Person->GetFullName());

		$emailBody = $findReplace->Execute(Template::GetContent('email_template_standard'));

		$queue = new EmailQueue();
		$queue->GetModuleID('orders');
		$queue->Subject = sprintf("%s Order Confirmation [#%s%s]", $GLOBALS['COMPANY'], $this->Prefix, $this->ID);
		$queue->Body = $emailBody;
		$queue->ToAddress = $mailTo;
		$queue->Priority = 'H';
		$queue->Add();
		
		foreach($attachments as $attachment) {
			if(count($attachment) == 2) {
				$queue->AddAttachment($attachment[0], $attachment[1]);
			}
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("update orders set Emailed_On=Now(), Emailed_To='%s' where Order_ID=%d", mysql_real_escape_string($this->Customer->GetEmail()), $this->ID));
	}

	function SendSampleEmail($mailTo = '') {
		$this->GetSampleLines();

		if (empty($this->Customer->Contact->ID))
			$this->Customer->Get();
		if (empty($this->Customer->Contact->Person->ID))
			$this->Customer->Contact->Get();

		$findReplace = new FindReplace();
		$findReplace->Add('/\[SAMPLE_REF\]/', $this->Prefix . $this->ID);
		$findReplace->Add('/\[SAMPLE_DATE\]/', cDatetime($this->OrderedOn, 'longdate'));
		$findReplace->Add('/\[CUSTOMER_NAME\]/', $this->Customer->Contact->Person->GetFullName());
		$findReplace->Add('/\[CUSTOMER_ID\]/', $this->Customer->Contact->ID);
		$findReplace->Add('/\[SHIPTO\]/', $this->GetShippingAddress());
		$findReplace->Add('/\[SAMPLE_WEIGHT\]/', $this->Weight);
		$findReplace->Add('/\[SAMPLE_LINES\]/', $this->SampleLinesHtml);

		$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_sample.tpl");
		$orderHtml = "";
		for ($i = 0; $i < count($orderEmail); $i++) {
			$orderHtml .= $findReplace->Execute($orderEmail[$i]);
		}

		$findReplace = new FindReplace();
		$findReplace->Add('/\[BODY\]/', $orderHtml);
		$findReplace->Add('/\[NAME\]/', $this->Customer->Contact->Person->GetFullName());

		$emailBody = $findReplace->Execute(Template::GetContent('email_template_standard'));

		$mail = new htmlMimeMail5();
		$mail->setFrom($GLOBALS['EMAIL_FROM']);
		$mail->setSubject(sprintf("%s Sample Confirmation [%s%s]", $GLOBALS['COMPANY'], $this->Prefix, $this->ID));
		$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
		$mail->setHTML($emailBody);

		if(!empty($mailTo)){
			$mail->send(array($mailTo));
		}else{
			$mail->send(array($this->Customer->GetEmail()));
		}
		
		new DataQuery(sprintf("update orders set Emailed_On=Now(), Emailed_To='%s' where Order_ID=%d", mysql_real_escape_string($this->Customer->GetEmail()), mysql_real_escape_string($this->ID)));
	}

	function GetLinesHtml() {
		if (count($this->Line) == 0) {
			$this->GetLines();
		}
		return $this->LinesHtml;
	}

	function GetBillingAddress() {
		$address = '';
    	
    	$fullName = $this->Billing->GetFullName();
    	
    	if(!empty($fullName)) {
			$address .= $fullName;
			$address .= '<br />';
		}
		
		if(!empty($this->BillingOrg)) {
			$address .= $this->BillingOrg;
			$address .= '<br />';
		}
		
		$address .= $this->Billing->Address->GetFormatted('<br />');

		return $address;
	}

	function GetShippingAddress() {
		$address = '';
    	
    	$fullName = $this->Shipping->GetFullName();
    	
    	if(!empty($fullName)) {
			$address .= $fullName;
			$address .= '<br />';
		}
		
		if(!empty($this->ShippingOrg)) {
			$address .= $this->ShippingOrg;
			$address .= '<br />';
		}
		
		$address .= $this->Shipping->Address->GetFormatted('<br />');
		
		return $address;
	}
	
	function GetInvoiceAddress() {
    	$address = '';
    	
    	$fullName = $this->Invoice->GetFullName();
    	
    	if(!empty($fullName)) {
			$address .= $fullName;
			$address .= '<br />';
		}
		
		if(!empty($this->InvoiceOrg)) {
			$address .= $this->InvoiceOrg;
			$address .= '<br />';
		}
		
		$address .= $this->Invoice->Address->GetFormatted('<br />');
		
		return $address;
	}

	function Received() {
		$this->ReceivedOn = getDatetime();
		$this->ReceivedBy = $GLOBALS['SESSION_USER_ID'];
		

		if(strtolower($this->Status) == 'unread') {
			$this->Status = 'Pending';
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$receive = new DataQuery(sprintf("update orders set Received_On=Now(), Status='%s', Received_By=%d where Order_ID=%d", mysql_real_escape_string($this->Status), mysql_real_escape_string($this->ReceivedBy), mysql_real_escape_string($this->ID)));
		$receive->Disconnect();

		if($this->PaymentMethod->Reference == 'google') {
			$googleRequest = new GoogleRequest();
			$googleRequest->processOrder($this->CustomID);
		}
	}

	function SetShippingAddress() {
		if(empty($this->Shipping->Address->Zip) && empty($this->Shipping->Address->City)) {
			$this->Shipping->Title = $this->Billing->Title;
			$this->Shipping->Name = $this->Billing->Name;
			$this->Shipping->Initial = $this->Billing->Initial;
			$this->Shipping->LastName = $this->Billing->LastName;
			$this->ShippingOrg = $this->BillingOrg;
			$this->Shipping->Address->Line1 = $this->Billing->Address->Line1;
			$this->Shipping->Address->Line2 = $this->Billing->Address->Line2;
			$this->Shipping->Address->Line3 = $this->Billing->Address->Line3;
			$this->Shipping->Address->City = $this->Billing->Address->City;
			$this->Shipping->Address->Country->ID = $this->Billing->Address->Country->ID;
			$this->Shipping->Address->Country->Name = $this->Billing->Address->Country->Name;
			$this->Shipping->Address->Country->AddressFormat->Long = $this->Billing->Address->Country->AddressFormat->Long;
			$this->Shipping->Address->Country->AddressFormat->Short = $this->Billing->Address->Country->AddressFormat->Short;
			$this->Shipping->Address->Region->ID = $this->Billing->Address->Region->ID;
			$this->Shipping->Address->Region->Name = $this->Billing->Address->Region->Name;
			$this->Shipping->Address->Zip = $this->Billing->Address->Zip;
			
			$this->Update();
		}
	}
	
	function SetInvoiceAddress() {
		if(empty($this->Invoice->Address->Zip) && empty($this->Invoice->Address->City)) {
			if($this->Customer->Contact->ID == 0) {
				$this->Customer->Get();
				$this->Customer->Contact->Get();
			}

			if($this->Customer->Contact->Parent->ID > 0) {
				if($this->Customer->Contact->Parent->Organisation->UseInvoiceAddress == 'Y') {
					$this->Invoice->Title = '';
					$this->Invoice->Name = '';
					$this->Invoice->Initial = '';
					$this->Invoice->LastName = '';
					$this->InvoiceOrg = $this->Customer->Contact->Parent->Organisation->InvoiceName;
					$this->Invoice->Address->Line1 = $this->Customer->Contact->Parent->Organisation->InvoiceAddress->Line1;
					$this->Invoice->Address->Line2 = $this->Customer->Contact->Parent->Organisation->InvoiceAddress->Line2;
					$this->Invoice->Address->Line3 = $this->Customer->Contact->Parent->Organisation->InvoiceAddress->Line3;
					$this->Invoice->Address->City = $this->Customer->Contact->Parent->Organisation->InvoiceAddress->City;
					$this->Invoice->Address->Country->ID = $this->Customer->Contact->Parent->Organisation->InvoiceAddress->Country->ID;
					$this->Invoice->Address->Country->Name = $this->Customer->Contact->Parent->Organisation->InvoiceAddress->Country->Name;
					$this->Invoice->Address->Country->AddressFormat->Long = $this->Customer->Contact->Parent->Organisation->InvoiceAddress->Country->AddressFormat->Long;
					$this->Invoice->Address->Country->AddressFormat->Short = $this->Customer->Contact->Parent->Organisation->InvoiceAddress->Country->AddressFormat->Short;
					$this->Invoice->Address->Region->ID = $this->Customer->Contact->Parent->Organisation->InvoiceAddress->Region->ID;
					$this->Invoice->Address->Region->Name = $this->Customer->Contact->Parent->Organisation->InvoiceAddress->Region->Name;
					$this->Invoice->Address->Zip = $this->Customer->Contact->Parent->Organisation->InvoiceAddress->Zip;
				} else {
					$this->Invoice->Title = '';
					$this->Invoice->Name = '';
					$this->Invoice->Initial = '';
					$this->Invoice->LastName = '';
					$this->InvoiceOrg = $this->Customer->Contact->Parent->Organisation->Name;
					$this->Invoice->Address->Line1 = $this->Customer->Contact->Parent->Organisation->Address->Line1;
					$this->Invoice->Address->Line2 = $this->Customer->Contact->Parent->Organisation->Address->Line2;
					$this->Invoice->Address->Line3 = $this->Customer->Contact->Parent->Organisation->Address->Line3;
					$this->Invoice->Address->City = $this->Customer->Contact->Parent->Organisation->Address->City;
					$this->Invoice->Address->Country->ID = $this->Customer->Contact->Parent->Organisation->Address->Country->ID;
					$this->Invoice->Address->Country->Name = $this->Customer->Contact->Parent->Organisation->Address->Country->Name;
					$this->Invoice->Address->Country->AddressFormat->Long = $this->Customer->Contact->Parent->Organisation->Address->Country->AddressFormat->Long;
					$this->Invoice->Address->Country->AddressFormat->Short = $this->Customer->Contact->Parent->Organisation->Address->Country->AddressFormat->Short;
					$this->Invoice->Address->Region->ID = $this->Customer->Contact->Parent->Organisation->Address->Region->ID;
					$this->Invoice->Address->Region->Name = $this->Customer->Contact->Parent->Organisation->Address->Region->Name;
					$this->Invoice->Address->Zip = $this->Customer->Contact->Parent->Organisation->Address->Zip;
				}
			} else {
				$this->Invoice->Title = $this->Billing->Title;
				$this->Invoice->Name = $this->Billing->Name;
				$this->Invoice->Initial = $this->Billing->Initial;
				$this->Invoice->LastName = $this->Billing->LastName;
				$this->InvoiceOrg = $this->BillingOrg;
				$this->Invoice->Address->Line1 = $this->Billing->Address->Line1;
				$this->Invoice->Address->Line2 = $this->Billing->Address->Line2;
				$this->Invoice->Address->Line3 = $this->Billing->Address->Line3;
				$this->Invoice->Address->City = $this->Billing->Address->City;
				$this->Invoice->Address->Country->ID = $this->Billing->Address->Country->ID;
				$this->Invoice->Address->Country->Name = $this->Billing->Address->Country->Name;
				$this->Invoice->Address->Country->AddressFormat->Long = $this->Billing->Address->Country->AddressFormat->Long;
				$this->Invoice->Address->Country->AddressFormat->Short = $this->Billing->Address->Country->AddressFormat->Short;
				$this->Invoice->Address->Region->ID = $this->Billing->Address->Region->ID;
				$this->Invoice->Address->Region->Name = $this->Billing->Address->Region->Name;
				$this->Invoice->Address->Zip = $this->Billing->Address->Zip;
			}
				
			$this->Update();
		}
	}

	function UpdateInvoiceAddress() {
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT Invoice_ID FROM invoice WHERE Order_ID=%d", mysql_real_escape_string($this->ID)));	
		while($data->Row) {
			$invoice = new Invoice($data->Row['Invoice_ID']);
			$invoice->Organisation = $this->InvoiceOrg;
			$invoice->Person->Title = $this->Invoice->Title;
			$invoice->Person->Name = $this->Invoice->Name;
			$invoice->Person->Initial = $this->Invoice->Initial;
			$invoice->Person->LastName = $this->Invoice->LastName;
			$invoice->Person->Address->Line1 = $this->Invoice->Address->Line1;
			$invoice->Person->Address->Line2 = $this->Invoice->Address->Line2;
			$invoice->Person->Address->Line3 = $this->Invoice->Address->Line3;
			$invoice->Person->Address->City = $this->Invoice->Address->City;
			$invoice->Person->Address->Region->ID = $this->Invoice->Address->Region->ID;
			$invoice->Person->Address->Region->Get();
			$invoice->Person->Address->Country->ID = $this->Invoice->Address->Country->ID;
			$invoice->Person->Address->Country->Get();
			$invoice->Person->Address->Zip = $this->Invoice->Address->Zip;
			$invoice->Update();
		
			$data->Next();	
		}
		$data->Disconnect();
	}
	
	function Pack() {
		if($this->HasInvoiceAddress()) {
			$this->IsBidding = 'N';
			$this->Status = 'Packing';
			$this->Update();
		}
	}

	function Despatch($emailCustomer = true) {
		$this->Status = 'Despatched';
		$this->DespatchedOn = date('Y-m-d H:i:s');
		$this->ModifiedBy = $GLOBALS['SESSION_USER_ID'];
		$this->ModifiedOn = date('Y-m-d H:i:s');

		if($this->PaymentMethod->Reference == 'google') {
			$googleRequest = new GoogleRequest();
			$googleRequest->archiveOrder($this->CustomID);
		}

		if($emailCustomer) {
			// TODO: use despatch class email function instead for centralisation.
			$this->EmailDespatch();
		}
	}

	function EmailDespatch() {
		$this->PaymentMethod->Get();
		$this->GetLines();

		if (empty($this->Customer->Contact->ID))
			$this->Customer->Get();
		if (empty($this->Customer->Contact->Person->ID))
			$this->Customer->Contact->Get();

		$autoLogin = serialize(array($this->Customer->Contact->ID, $this->Customer->Contact->CreatedOn));

		$cypher = new Cipher($autoLogin);
		$cypher->Encrypt();

		$findReplace = new FindReplace();
		$findReplace->Add('/\[AUTO_LOGIN\]/', urlencode($cypher->Value));
		$findReplace->Add('/\[ORDER_REF\]/', $this->Prefix . $this->ID);
		$findReplace->Add('/\[CUSTOM_REF\]/', $this->CustomID);
		$findReplace->Add('/\[ORDER_DATE\]/', cDatetime($this->OrderedOn, 'longdate'));
		$findReplace->Add('/\[CUSTOMER_NAME\]/', $this->Customer->Contact->Person->GetFullName());
		$findReplace->Add('/\[CUSTOMER_ID\]/', $this->Customer->Contact->ID);
		$findReplace->Add('/\[BILLTO\]/', $this->GetBillingAddress());
		$findReplace->Add('/\[SHIPTO\]/', $this->GetShippingAddress());
		$findReplace->Add('/\[PAYMENT_METHOD\]/', $this->GetPaymentMethod());
		$findReplace->Add('/\[CARD_NUMBER\]/', ($this->PaymentMethod->Reference == 'card') ? $this->Card->PrivateNumber() : '');
		$findReplace->Add('/\[NET\]/', "&pound;" . number_format($this->TotalNet, 2, '.', ','));
		$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format($this->SubTotal, 2, '.', ','));
		$findReplace->Add('/\[SHIPPING\]/', "&pound;" . number_format($this->TotalShipping, 2, '.', ','));
		$findReplace->Add('/\[DISCOUNT\]/', "&pound;" . number_format($this->TotalDiscount, 2, '.', ','));
		$findReplace->Add('/\[TAX\]/', "&pound;" . number_format($this->TotalTax, 2, '.', ','));
		$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format($this->Total, 2, '.', ','));
		$findReplace->Add('/\[ORDER_WEIGHT\]/', $this->Weight);
		$findReplace->Add('/\[DELIVERY\]/', $this->Postage->Name);
		$findReplace->Add('/\[ORDER_LINES\]/', $this->LinesHtml);

		if ($this->Sample == 'Y') {
			$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_despatchSample.tpl");
		} else {
			$complementaryCount = 0;

			for ($i = 0; $i < count($this->Line); $i++) {
				if ($this->Line[$i]->IsComplementary == 'Y') {
					$complementaryCount++;
				}
			}

			if((($this->Prefix == 'R') || ($this->Prefix == 'B')) && ($complementaryCount > 0)) {
				$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_despatchReturn.tpl");
			} else {
				$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_despatch.tpl");
			}
		}

		$orderHtml = "";
		
		for ($i = 0; $i < count($orderEmail); $i++) {
			$orderHtml .= $findReplace->Execute($orderEmail[$i]);
		}

		$findReplace = new FindReplace();
		$findReplace->Add('/\[BODY\]/', $orderHtml);
		$findReplace->Add('/\[NAME\]/', $this->Customer->Contact->Person->GetFullName());

		$emailBody = $findReplace->Execute(Template::GetContent('email_template_standard'));

		$mail = new htmlMimeMail5();
		$mail->setFrom($GLOBALS['EMAIL_FROM']);
		$mail->setSubject(sprintf("%s Order Despatched [%s%s]", $GLOBALS['COMPANY'], $this->Prefix, $this->ID));
		$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
		$mail->setHTML($emailBody);
		$mail->send(array($this->Customer->GetEmail()));
	}

	function GenerateFromReturn(&$return) {
		$return->Order->Get();
		$return->Reason->Get();

		if (empty($return->Customer->Contact->ID)) {
			$return->Customer->Get();
		}

		if (empty($return->Customer->Contact->Person->ID)) {
			$return->Customer->Contact->Get();
		}

		switch($return->Reason->ResultantPrefix) {
			case 'R':
				$this->PaymentMethod->GetByReference('card');
				break;
			case 'B':
				$this->PaymentMethod->GetByReference('foc');
				break;
		}

		$this->Prefix = $return->Reason->ResultantPrefix;
		$this->ReturnID = $return->ID;
		$this->OrderedOn = getDatetime();
		$this->Status = 'Unread';
		$this->IsCustomShipping = 'Y';

		$this->Customer = $return->Customer;
		$this->Billing = $return->Customer->Contact->Person;
		
		if ($return->Customer->Contact->HasParent) {
			$this->BillingOrg = $return->Customer->Contact->Parent->Organisation->Name;
		}

		$this->Shipping = $return->Order->Shipping;
		$this->ShippingOrg = $return->Order->ShippingOrg;
		$this->ParentID = $return->Order->ID;
		$this->Add();

		for ($i = 0; $i < count($return->DespatchLine); $i++) {
			$return->DespatchLine[$i]->Product->Get();

			$line = new OrderLine();
			$line->Order = $this->ID;
			$line->Product->ID = $return->DespatchLine[$i]->Product->ID;
			$line->Product->SKU = $return->DespatchLine[$i]->Product->SKU;
			$line->Product->Name = $return->DespatchLine[$i]->Product->Name;
			$line->Quantity = $return->DespatchLine[$i]->Quantity;
			$line->FreeOfCharge = 'Y';
			$line->Cost = 0;

			if($return->DespatchLine[$i]->Product->Type == 'S') {
				$data = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Cost>0 ORDER BY Preferred_Supplier ASC LIMIT 0, 1", mysql_real_escape_string($return->DespatchLine[$i]->Product->ID)));
				if($data->TotalRows > 0) {
					$line->Cost = $data->Row['Cost'];
				}
				$data->Disconnect();
			} elseif($return->DespatchLine[$i]->Product->Type == 'G') {
				$data = new DataQuery(sprintf("SELECT Product_ID, Component_Quantity FROM product_components WHERE Component_Of_Product_ID=%d", mysql_real_escape_string($return->DespatchLine[$i]->Product->ID)));
				while($data->Row) {
                    $data2 = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Cost>0 ORDER BY Preferred_Supplier ASC LIMIT 0, 1", mysql_real_escape_string($data->Row['Product_ID'])));
					if($data2->TotalRows > 0) {
						$line->Cost += $data2->Row['Cost'] * $data->Row['Component_Quantity'];
					}
					$data2->Disconnect();

					$data->Next();
				}
				$data->Disconnect();
			}

			$line->HandlingCharge = Setting::GetValue('return_handling_charge');
			$line->Add();
		}

		$this->Recalculate();
	}

	function GenerateFromCart(&$cart, $status='Unread') {
		global $session;
		$cart->Customer->Get();
		$cart->Customer->Contact->Get();
		
		if(($cart->Prefix == 'W') && $cart->MobileDetected) {
			$cart->Prefix = 'M';
		}

		$this->QuoteID = $cart->QuoteID;
		$this->Prefix = $cart->Prefix;
		$this->BasketID = $cart->ID;
		$this->IsCollection = (isset($_SESSION['CartCollection']) && ($_SESSION['CartCollection'] == 'Y')) ? 'Y' : 'N';

		$originalCoupon = $cart->Coupon->ID;

		$this->Coupon->ID = $cart->Coupon->ID;
		$this->Total = $cart->Total;
		$this->SubTotal = $cart->SubTotal + $this->FreeTextValue;
		$this->TotalShipping = $cart->ShippingTotal;
		$this->TotalDiscount = $cart->Discount;
		$this->TotalTax = $cart->TaxTotal;
		$this->TotalLines = $cart->TotalLines;
		$this->IsCustomShipping = $cart->IsCustomShipping;
		$this->TaxExemptCode = $cart->TaxExemptCode;
		$this->FreeText = $cart->FreeText;
		$this->FreeTextValue = $cart->FreeTextValue;
		$this->DiscountBandingID = $cart->DiscountBandingID;

		$this->Billing = $cart->Customer->Contact->Person;

        if($cart->Customer->Contact->HasParent) {
			$this->BillingOrg = $cart->Customer->Contact->Parent->Organisation->Name;
		}
		
		$this->Invoice = $this->Billing;
		$this->InvoiceOrg = $this->BillingOrg;
		$this->OrderedOn = getDatetime();
		$this->Status = $status;
		$this->Customer = $cart->Customer;
		$this->Weight = $cart->Weight;
		$this->Postage->ID = $cart->Postage;

		if(strtolower($cart->ShipTo) == 'billing') {
			$this->Shipping = $cart->Customer->Contact->Person;

			if ($cart->Customer->Contact->HasParent) {
				$this->ShippingOrg = $cart->Customer->Contact->Parent->Organisation->Name;
			}
		} else {
			$cc = new CustomerContact($cart->ShipTo);
			
			$this->Shipping = $cc;
			$this->ShippingOrg = $cc->OrgName;
		}
		
		$this->SetDefaultOwner();

		if(!empty($cart->QuoteID)) {
			$quote = new Quote($cart->QuoteID);
			$quote->Status = 'Ordered';
			$quote->Update();

            $this->Prefix = $quote->Prefix;
            $this->Billing->Title = $quote->Billing->Title;
			$this->Billing->Name = $quote->Billing->Name;
			$this->Billing->Initial = $quote->Billing->Initial;
			$this->Billing->LastName = $quote->Billing->LastName;
			$this->BillingOrg = $quote->BillingOrg;
			$this->Billing->Address->Line1 = $quote->Billing->Address->Line1;
			$this->Billing->Address->Line2 = $quote->Billing->Address->Line2;
			$this->Billing->Address->Line3 = $quote->Billing->Address->Line3;
			$this->Billing->Address->City = $quote->Billing->Address->City;
			$this->Billing->Address->Country->ID = $quote->Billing->Address->Country->ID;
			$this->Billing->Address->Country->Name = $quote->Billing->Address->Country->Name;
			$this->Billing->Address->Country->AddressFormat->Long = $quote->Billing->Address->Country->AddressFormat->Long;
			$this->Billing->Address->Country->AddressFormat->Short = $quote->Billing->Address->Country->AddressFormat->Short;
			$this->Billing->Address->Region->ID = $quote->Billing->Address->Region->ID;
			$this->Billing->Address->Region->Name = $quote->Billing->Address->Region->Name;
			$this->Billing->Address->Zip = $quote->Billing->Address->Zip;
			$this->Shipping->Title = $quote->Shipping->Title;
			$this->Shipping->Name = $quote->Shipping->Name;
			$this->Shipping->Initial = $quote->Shipping->Initial;
			$this->Shipping->LastName = $quote->Shipping->LastName;
			$this->ShippingOrg = $quote->ShippingOrg;
			$this->Shipping->Address->Line1 = $quote->Shipping->Address->Line1;
			$this->Shipping->Address->Line2 = $quote->Shipping->Address->Line2;
			$this->Shipping->Address->Line3 = $quote->Shipping->Address->Line3;
			$this->Shipping->Address->City = $quote->Shipping->Address->City;
			$this->Shipping->Address->Country->ID = $quote->Shipping->Address->Country->ID;
			$this->Shipping->Address->Country->Name = $quote->Shipping->Address->Country->Name;
			$this->Shipping->Address->Country->AddressFormat->Long = $quote->Shipping->Address->Country->AddressFormat->Long;
			$this->Shipping->Address->Country->AddressFormat->Short = $quote->Shipping->Address->Country->AddressFormat->Short;
			$this->Shipping->Address->Region->ID = $quote->Shipping->Address->Region->ID;
			$this->Shipping->Address->Region->Name = $quote->Shipping->Address->Region->Name;
			$this->Shipping->Address->Zip = $quote->Shipping->Address->Zip;
			$this->Invoice->Title = $quote->Invoice->Title;
			$this->Invoice->Name = $quote->Invoice->Name;
			$this->Invoice->Initial = $quote->Invoice->Initial;
			$this->Invoice->LastName = $quote->Invoice->LastName;
			$this->InvoiceOrg = $quote->InvoiceOrg;
			$this->Invoice->Address->Line1 = $quote->Invoice->Address->Line1;
			$this->Invoice->Address->Line2 = $quote->Invoice->Address->Line2;
			$this->Invoice->Address->Line3 = $quote->Invoice->Address->Line3;
			$this->Invoice->Address->City = $quote->Invoice->Address->City;
			$this->Invoice->Address->Country->ID = $quote->Invoice->Address->Country->ID;
			$this->Invoice->Address->Country->Name = $quote->Invoice->Address->Country->Name;
			$this->Invoice->Address->Country->AddressFormat->Long = $quote->Invoice->Address->Country->AddressFormat->Long;
			$this->Invoice->Address->Country->AddressFormat->Short = $quote->Invoice->Address->Country->AddressFormat->Short;
			$this->Invoice->Address->Region->ID = $quote->Invoice->Address->Region->ID;
			$this->Invoice->Address->Region->Name = $quote->Invoice->Address->Region->Name;
			$this->Invoice->Address->Zip = $quote->Invoice->Address->Zip;

			if($quote->CreatedBy > 0) {
				if($this->OwnedBy == 0) {
					$this->OwnedBy = $quote->CreatedBy;
				}

                $this->Prefix = 'T';
			}

			$cart->QuoteID = 0;
		}

		if($this->OwnedBy == 0) {
			$this->OwnedBy = $this->GetAcountManager($this->Customer->ID);
		}
		
		if($this->OwnedBy == 0) {
			$this->OwnedBy = $GLOBALS['SESSION_USER_ID'];
		}

		$this->Add();

		$cart->GetShippingLines();

		for($i=0; $i<count($cart->ShippingLine); $i++) {
			$shipping = new OrderShipping();
			$shipping->OrderID = $this->ID;
			$shipping->Weight = $cart->ShippingLine[$i]->Weight;
			$shipping->Quantity = $cart->ShippingLine[$i]->Quantity;
			$shipping->Charge = $cart->ShippingLine[$i]->Charge;
			$shipping->Add();
		}

		$bandingBasket = new DiscountBandingBasket($session);

		if(($bandingBasket->Banding->Threshold > 0) && ($cart->SubTotal >= $bandingBasket->Banding->Threshold)) {
			$bandingBasket->Convert($this->ID);
		} else {
			$bandingBasket->Delete();
		}

		for($i=0; $i<count($cart->Line); $i++) {
			$cart->Line[$i]->Product->Get();

			$line = new OrderLine();
			$line->Order = $this->ID;
			$line->IsAssociative = $cart->Line[$i]->IsAssociative;
			$line->Product->ID = $cart->Line[$i]->Product->ID;
			$line->Product->Name = $cart->Line[$i]->Product->Name;
			$line->AssociativeProductTitle = $cart->Line[$i]->AssociativeProductTitle;
			$line->OriginalProduct->ID = $cart->Line[$i]->OriginalProduct->ID;
			$line->Quantity = $cart->Line[$i]->Quantity;
			$line->Price = $cart->Line[$i]->Price;
			$line->PriceRetail = $cart->Line[$i]->PriceRetail;
			$line->HandlingCharge = $cart->Line[$i]->HandlingCharge;
			$line->IncludeDownloads = $cart->Line[$i]->IncludeDownloads;
			$line->Total = $cart->Line[$i]->Total;
			$line->Discount = $cart->Line[$i]->Discount;
			$line->DiscountInformation = $cart->Line[$i]->DiscountInformation;
			$line->FreeOfCharge = $cart->Line[$i]->FreeOfCharge;
			$line->Cost = 0;

			if($cart->Line[$i]->Product->ID > 0) {
				$line->Tax = round($cart->Line[$i]->Tax, 2);

                if($cart->Line[$i]->Product->Type == 'S') {
					$data = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Cost>0 ORDER BY Preferred_Supplier ASC LIMIT 0, 1", mysql_real_escape_string($cart->Line[$i]->Product->ID)));
					if($data->TotalRows > 0) {
						$line->Cost = $data->Row['Cost'];
					}
					$data->Disconnect();
				} elseif($cart->Line[$i]->Product->Type == 'G') {
					$data = new DataQuery(sprintf("SELECT Product_ID, Component_Quantity FROM product_components WHERE Component_Of_Product_ID=%d", mysql_real_escape_string($cart->Line[$i]->Product->ID)));
					while($data->Row) {
	                    $data2 = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Cost>0 ORDER BY Preferred_Supplier ASC LIMIT 0, 1", mysql_real_escape_string($data->Row['Product_ID'])));
						if($data2->TotalRows > 0) {
							$line->Cost += $data2->Row['Cost'] * $data->Row['Component_Quantity'];
						}
						$data2->Disconnect();

						$data->Next();
					}
					$data->Disconnect();
				}
			}

			$line->Add();
		}

		
		switch($this->PaymentMethod->Reference) {


			case 'credit':
			case 'foc':
			case 'pdq':
			case 'cheque':
			case 'transfer':
				$this->confirmNewOrder();
				break;
		}
		if(strtoupper($this->Status) == 'UNREAD'){
			$cart->DiscountBandingID = 0;
			$cart->DiscountBandingOffered = 'N';
			$cart->Coupon->ID = 0;
		}
		$cart->Update();

		$cart->Customer->Contact->IsCustomer = 'Y';
		$cart->Customer->Contact->Update();

		// This is where we really f%*$ around with things.
		//
		// Calculate invisible and unique coupon to assist order with discounting
		// absolute values based upon a relative percentage value discount system
		// when and when not already presented with a percentage discount value.

		$unassociatedProducts = 0;

		for ($i = 0; $i < count($cart->Line); $i++) {
			if ($cart->Line[$i]->Product->ID == 0) {
				$unassociatedProducts++;
			}
		}

		if ($unassociatedProducts > 0) {
			$session->Customer->AvailableDiscountReward = 0;
		}

		if ($session->Customer->AvailableDiscountReward > 0) {
			$discount = $session->Customer->AvailableDiscountReward;
			if (($cart->SubTotal - $cart->Discount) < $discount) {
				$discount = ($cart->SubTotal - $cart->Discount);
			}

			$subTotal = ($cart->SubTotal - $cart->Discount) - $session->Customer->AvailableDiscountReward;
			if ($subTotal < 0) {
				$subTotal = 0;
			}

			$this->OriginalCoupon->ID = $originalCoupon;
			$this->DiscountReward = $discount;

			// 100-((100/InitialSubTotal)DiscountRewardSubTotal) = ?%
			$discount = 100 - ((100 / $this->SubTotal) * $subTotal);

			$coupon = new Coupon();
			$coupon->Reference = $coupon->GenerateReference();
			$coupon->Name = 'Assistive Discount Reward Coupon';
			$coupon->Description = 'This coupon exists solely to assist calculating discount values for Order Ref ' . $this->ID . ' which was placed using the discount reward structure.';
			$coupon->Discount = $discount;
			$coupon->IsFixed = 'N';
			$coupon->OrdersOver = 0.00;
			$coupon->UsageLimit = 1;
			$coupon->IsAllProducts = 'Y';
			$coupon->IsActive = 'Y';
			$coupon->StaffOnly = 'N';
			$coupon->IsInvisible = 'Y';
			$coupon->UseBand = 0;
			$coupon->IsAllCustomers = 'Y';
			$coupon->ExpiresOn = '0000-00-00 00:00:00';
			$coupon->IntroducedBy = 0;
			$coupon->Add();

			$this->Coupon->ID = $coupon->ID;
			$this->Recalculate();
		}
	}

	function customerHasSimilar(){
		$sql = sprintf("select count(*) as num from orders where Customer_ID=%d and Total=%f and Status not like 'Incomplete' and Status not like 'Unauthenticated'", 
				mysql_real_escape_string($this->Customer->ID),
				mysql_real_escape_string($this->Total));
		$data = new DataQuery($sql);
		if($data->Row['num']){
			return $data->Row['num'];
		}
		return false;
	}
	
	function confirmNewOrder(){
		global $session;
		if(strtoupper($this->Status) != 'UNREAD'){
			$this->Status = 'Unread';
			$this->Update();
			$this->SendEmail();
		}
		
		$cart = new Cart($session);
		$cart->getByID($this->BasketID);
		$cart->removeLines();
		$cart->DiscountBandingID = 0;
		$cart->DiscountBandingOffered = 'N';
		$cart->Coupon->ID = 0;
		$cart->Update();
		$cart->Release($cart->ID);

		// how about
		// 1. Delete the cart
		// 2. Add the cart
		$cart->Delete();
		$cart->ID = null;
		$cart->Add();

		switch($this->PaymentMethod->Reference) {
			case 'card':
			case 'credit':
			case 'foc':
			case 'pdq':
			case 'cheque':
			case 'transfer':
				if($this->CheckAutomaticPack()) {
					$this->SetAutomaticPack();
				} else {
					$this->AutoShip();
				}
				break;
		}
		return true;
	}
	
	function unauthenticated(){
		$this->Status = 'Unauthenticated';
		$this->Update();
	}

	function GetPaymentMethod() {
        $this->PaymentMethod->Get();
		return $this->PaymentMethod->Method;
	}

	function Recalculate() {
		if((strtolower($this->Status) != 'despatched') && (strtolower($this->Status) != 'partially despatched')) {
			$this->Customer->Get();
			$this->Customer->Contact->Get();
		
			$updateCoupon = false;

			if(count($this->Line) == 0) {
				$this->GetLines();
			}

			$this->TotalLines = count($this->Line);
			$this->SubTotal = $this->FreeTextValue;
			$this->SubTotalRetail = $this->FreeTextValue;

			if($this->IsCustomShipping == 'N') {
				$this->TotalShipping = 0;
			}

			$this->TotalDiscount = 0;
			$this->TotalTax = 0;
			$this->Total = 0;
			$this->Weight = 0;
			
			$this->CalculateNominalCode();

			if(($this->Coupon->IsInvisible == 'Y')) {
				if(!is_numeric($this->ID)){
					return false;
				}
				$data = new DataQuery(sprintf("SELECT Coupon_ID FROM orders WHERE Order_ID=%d", mysql_real_escape_string($this->ID)));
				$couponID = $data->Row['Coupon_ID'];
				$data->Disconnect();

				if($this->Coupon->ID != $couponID) {
					$this->OriginalCoupon->ID = $this->Coupon->ID;

					$updateCoupon = true;
				}
			}

			if(!empty($this->Coupon->ID))
				$this->Coupon->Get();
			if(!empty($this->OriginalCoupon->ID))
				$this->OriginalCoupon->Get();

			$this->DiscountCollection->Get($this->Customer);
			
			$this->TotalTax += $this->CalculateCustomTax($this->FreeTextValue);
			$this->RecalculateCost();

			for($i=0; $i<count($this->Line); $i++) {
				if($this->Line[$i]->Product->ID > 0) {
					$this->Line[$i]->Product->Get();
					$this->Line[$i]->PriceRetail = $this->Line[$i]->Product->PriceCurrent;
				}
				
				if(($this->Line[$i]->Status != 'Cancelled') && ($this->Line[$i]->Status != 'Invoiced') && ($this->Line[$i]->Status != 'Despatched')) {
					$this->Line[$i]->Tax = 0;

					if($this->Line[$i]->Product->ID > 0) {
						$customDiscount = false;

						if (!empty($this->Line[$i]->DiscountInformation)) {
							$discountCustom = explode(':', $this->Line[$i]->DiscountInformation);

							if (trim($discountCustom[0]) == 'azxcustom') {
								$customDiscount = true;
							}
						}
					}

					if($this->Line[$i]->FreeOfCharge == 'Y') {
						if ($this->Line[$i]->Product->ID > 0) {
							if($this->Customer->Contact->IsTradeAccount == 'Y') {
								$tradeCost = ($this->Line[$i]->Product->CacheRecentCost > 0) ? $this->Line[$i]->Product->CacheRecentCost : $this->Line[$i]->Product->CacheBestCost;
								
								$this->Line[$i]->Price = ContactProductTrade::getPrice($this->Customer->Contact->ID, $this->Line[$i]->Product->ID);
								$this->Line[$i]->Price = ($this->Line[$i]->Price <= 0) ? $tradeCost * ((TradeBanding::GetMarkup($tradeCost, $this->Line[$i]->Product->ID) / 100) + 1) : $this->Line[$i]->Price;
							} else {
								$this->Line[$i]->Price = $this->Line[$i]->Product->PriceCurrent;
							}
						} elseif($lines[$i]->IsAssociative == 'Y') {
							$this->Line[$i]->Price = $this->Line[$i]->Discount * -1;
						}

						$this->Line[$i]->Total = 0;
					} else {
						if ($this->Line[$i]->Product->ID > 0) {
							if($this->Customer->Contact->IsTradeAccount == 'Y') {
								$tradeCost = ($this->Line[$i]->Product->CacheRecentCost > 0) ? $this->Line[$i]->Product->CacheRecentCost : $this->Line[$i]->Product->CacheBestCost;

								$this->Line[$i]->Price = ContactProductTrade::getPrice($this->Customer->Contact->ID, $this->Line[$i]->Product->ID);
								$this->Line[$i]->Price = ($this->Line[$i]->Price <= 0) ? $tradeCost * ((TradeBanding::GetMarkup($tradeCost, $this->Line[$i]->Product->ID) / 100) + 1) : $this->Line[$i]->Price;
							} else {
								if ($this->Line[$i]->Product->PriceCurrent == $this->Line[$i]->Product->PriceOurs) {
									$priceArr = array();

									$data2 = new DataQuery(sprintf("SELECT * FROM product_prices WHERE Price_Starts_On<=NOW() AND Quantity<=%d AND Product_ID=%d ORDER BY Price_Starts_On DESC", mysql_real_escape_string($this->Line[$i]->Quantity), mysql_real_escape_string($this->Line[$i]->Product->ID)));
									while ($data2->Row) {
										if (!isset($priceArr[$data2->Row['Quantity']])) {
											$priceArr[$data2->Row['Quantity']] = $data2->Row['Price_Base_Our'];
										}
										$data2->Next();
									}
									$data2->Disconnect();

									if (count($priceArr) > 0) {
										krsort($priceArr);
										reset($priceArr);

										$this->Line[$i]->Price = current($priceArr);
										$this->Line[$i]->PriceRetail = current($priceArr);
									}
								} else {
									$this->Line[$i]->Price = $this->Line[$i]->Product->PriceCurrent;
								}
							}

						} elseif($lines[$i]->IsAssociative == 'Y') {
							$this->Line[$i]->Price = $this->Line[$i]->Discount * -1;
						}

						$this->Line[$i]->Total = $this->Line[$i]->Price * $this->Line[$i]->Quantity;
					}

					if($customDiscount) {
						$this->Line[$i]->Discount = ($this->Line[$i]->FreeOfCharge == 'Y') ? 0 : round($this->Line[$i]->Price * ($discountCustom[1] / 100), 2) * $this->Line[$i]->Quantity;
					} else {
						if ($this->Line[$i]->Product->ID > 0) {
							$this->Line[$i]->Discount = 0;
							$this->Line[$i]->DiscountInformation = '';

							if (!empty($this->Coupon->ID)) {
								//$couponLineTotal = $this->Coupon->DiscountProduct($this->Line[$i]->Product, $this->Line[$i]->Quantity);
								$couponLineTotal = round(($this->Line[$i]->Price - round(($this->Coupon->Discount / 100) * $this->Line[$i]->Price, 2)) * $this->Line[$i]->Quantity, 2);

								if ($couponLineTotal < $this->Line[$i]->Total) {
									$this->Line[$i]->Discount = ($this->Line[$i]->FreeOfCharge == 'Y') ? 0 : $this->Line[$i]->Total - $couponLineTotal;
									$this->Line[$i]->DiscountInformation = sprintf('%s (Ref: %s)', $this->Coupon->Name, $this->Coupon->Reference);
								}
							}

							if ((count($this->DiscountCollection->Line) > 0) || ($this->DiscountBandingID > 0)) {
								list($tempLineTotal, $discountName) = $this->DiscountCollection->DiscountProduct($this->Line[$i]->Product, $this->Line[$i]->Quantity, $this->DiscountBandingID);

								if ((($this->Line[$i]->Total - $tempLineTotal) > $this->Line[$i]->Discount) && ($tempLineTotal > 0)) {
									$this->Line[$i]->DiscountInformation = $discountName;
									$this->Line[$i]->Discount = ($this->Line[$i]->FreeOfCharge == 'Y') ? 0 : $this->Line[$i]->Total - $tempLineTotal;
								}
							}

							if (!empty($this->Line[$i]->Product->PriceOffer) && ($this->Line[$i]->Product->PriceOffer < ($this->Line[$i]->Price - $this->Line[$i]->Discount))) {
								$this->Line[$i]->DiscountInformation = 'Special offer';
								$this->Line[$i]->Discount = ($this->Line[$i]->FreeOfCharge == 'Y') ? 0 : $this->Line[$i]->Total - ($this->Line[$i]->Product->PriceOffer * $this->Line[$i]->Quantity);
							}
						}
					}

					// Discount Limit Exceeding Check
					if($this->Line[$i]->Product->DiscountLimit != '' && ($this->Line[$i]->Product->DiscountLimit >= 0 && $this->Line[$i]->Product->DiscountLimit <= 100)){
						$maxDiscount = round(($this->Line[$i]->Product->DiscountLimit / 100) * $this->Line[$i]->Price, 2) * $this->Line[$i]->Quantity;
						if($this->Line[$i]->Discount > $maxDiscount){
							$this->Line[$i]->Discount = $maxDiscount;
							if(strpos($this->Line[$i]->DiscountInformation, 'azxcustom') === false && strpos($this->Line[$i]->DiscountInformation, 'Maximum discount for this product') == false){
								$this->Line[$i]->DiscountInformation .= sprintf(' - Maximum discount for this product is %d%%', $this->Line[$i]->Product->DiscountLimit);
							}
						}
					}

					if ($this->Line[$i]->Product->ID > 0) {
						$this->Line[$i]->Tax = $this->CalculateCustomTax($this->Line[$i]->Total - $this->Line[$i]->Discount);
					} else {
						$this->Line[$i]->Tax = $this->CalculateCustomTax($this->Line[$i]->Total);
					}

					$this->Line[$i]->Total = round($this->Line[$i]->Total, 2);
					$this->Line[$i]->Tax = round($this->Line[$i]->Tax, 2);
					$this->Line[$i]->Discount = round($this->Line[$i]->Discount, 2);

					$this->Line[$i]->Update();
				}

				$this->SubTotal += $this->Line[$i]->Total;
				$this->SubTotalRetail += $this->Line[$i]->PriceRetail * $this->Line[$i]->Quantity;

				if ($this->Line[$i]->Product->ID > 0) {
					$this->TotalDiscount += $this->Line[$i]->Discount;
				}

				$this->TotalTax += $this->Line[$i]->Tax;
				$this->Weight += ($this->Line[$i]->Quantity * $this->Line[$i]->Product->Weight);
			}

			$this->CalculateWeight();
			$this->CalculateShipping();

			$this->Total = $this->TotalTax + $this->TotalShipping + $this->SubTotal - $this->TotalDiscount;

			if ($updateCoupon) {
				// update discount reward available
				$discount = ($this->Customer->AvailableDiscountReward + $this->DiscountReward);
				if (($this->SubTotal - $this->TotalDiscount) < $discount) {
					$discount = ($this->SubTotal - $this->TotalDiscount);
				}

				$subTotal = ($this->SubTotal - $this->TotalDiscount) - ($this->Customer->AvailableDiscountReward + $this->DiscountReward);
				if ($subTotal < 0) {
					$subTotal = 0;
				}

				$this->DiscountReward = $discount;

				// 100-((100/InitialSubTotal)DiscountRewardSubTotal) = ?%
				$discount = 100 - ((100 / $this->SubTotal) * $subTotal);

				$this->Coupon->Get($couponID);
				$this->Coupon->Discount = $discount;
				$this->Coupon->Update();
			}

			$this->Update();

			$banding = new DiscountBanding();

			if ($banding->Get($this->DiscountBandingID)) {
				if ($this->SubTotal < $banding->Trigger) {
					$this->DiscountBandingID = 0;
					$this->Recalculate();
				}
			}

			return true;
		} else {
			$this->Update();
			
			return false;
		}
	}

	function RecalculateCost() {
		if(count($this->Line) == 0) {
			$this->GetLines();
		}

		for($i=0; $i<count($this->Line); $i++) {
			if($this->Line[$i]->Product->ID > 0) {
				$this->Line[$i]->Product->Get();

				if(($this->Line[$i]->Status != 'Cancelled') && ($this->Line[$i]->Status != 'Despatched')) {
					if($this->Line[$i]->Product->ID > 0) {
						$usePreferred = true;

						if($this->Line[$i]->DespatchedFrom->ID > 0) {
							if($this->Line[$i]->DespatchedFrom->Get()) {
								if($this->Line[$i]->DespatchedFrom->Type == 'S') {
									$usePreferred = false;

									if($this->Line[$i]->Product->Type == 'S') {
	                                    $prices = new SupplierProductPriceCollection();
										$prices->GetPrices($this->Line[$i]->Product->ID, $this->Line[$i]->DespatchedFrom->Contact->ID);

										$this->Line[$i]->Cost = $prices->GetPrice($this->Line[$i]->Quantity);;
									} elseif($this->Line[$i]->Product->Type == 'G') {
										$this->Line[$i]->Cost = 0;

	                                    $data = new DataQuery(sprintf("SELECT Product_ID, Component_Quantity FROM product_components WHERE Component_Of_Product_ID=%d", mysql_real_escape_string($this->Line[$i]->Product->ID)));
										while($data->Row) {
	                                        $prices = new SupplierProductPriceCollection();
											$prices->GetPrices($data->Row['Product_ID'], $this->Line[$i]->DespatchedFrom->Contact->ID);
											
	                                        $this->Line[$i]->Cost += $prices->GetPrice($this->Line[$i]->Quantity * $data->Row['Component_Quantity']) * $data->Row['Component_Quantity'];

											$data->Next();
										}
										$data->Disconnect();									
									}
								}
							}
						}

						if($usePreferred) {
	                        if($this->Line[$i]->Product->Type == 'S') {
	                            $data = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Cost>0 ORDER BY Preferred_Supplier ASC LIMIT 0, 1", mysql_real_escape_string($this->Line[$i]->Product->ID)));
								if ($data->TotalRows > 0) {
									$this->Line[$i]->Cost = $data->Row['Cost'];
								}
								$data->Disconnect();
							} elseif($this->Line[$i]->Product->Type == 'G') {
								$this->Line[$i]->Cost = 0;

								$data = new DataQuery(sprintf("SELECT Product_ID, Component_Quantity FROM product_components WHERE Component_Of_Product_ID=%d", mysql_real_escape_string($this->Line[$i]->Product->ID)));
								while($data->Row) {
				                    $data2 = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Cost>0 ORDER BY Preferred_Supplier ASC LIMIT 0, 1", mysql_real_escape_string($data->Row['Product_ID'])));
									if($data2->TotalRows > 0) {
	                                    $this->Line[$i]->Cost += $data2->Row['Cost'] * $data->Row['Component_Quantity'];
									}
									$data2->Disconnect();

									$data->Next();
								}
								$data->Disconnect();
							}
						}

						$this->Line[$i]->CostBest = $this->Line[$i]->Product->CacheBestCost;
						$this->Line[$i]->Update();
					}
				}
			}
		}
	}

	function CalculateShipping() {
		if ($this->IsCustomShipping == 'N') {
			$calc = new ShippingCalculator($this->Shipping->Address->Country->ID, $this->Shipping->Address->Region->ID, $this->SubTotal, $this->Weight, $this->Postage->ID);

			for($i = 0; $i < count($this->Line); $i++) {
				$calc->Add($this->Line[$i]->Quantity, $this->Line[$i]->Product->ShippingClass->ID);
			}

			$calc->GetLimitations();
			OrderShipping::DeleteOrder($this->ID);

			$data = new DataQuery(sprintf("SELECT sl.Weight FROM shipping_limit AS sl INNER JOIN geozone AS g ON g.Geozone_ID=sl.Geozone_ID INNER JOIN geozone_assoc AS ga ON ga.Geozone_ID=g.Geozone_ID AND ((ga.Country_ID=%d AND ga.Region_ID=%d) OR (ga.Country_ID=%d AND ga.Region_ID=0) OR (ga.Country_ID=0)) WHERE sl.Weight<%f AND sl.Postage_ID=%d ORDER BY sl.Weight ASC LIMIT 0, 1", mysql_real_escape_string($this->Shipping->Address->Country->ID), mysql_real_escape_string($this->Shipping->Address->Region->ID), mysql_real_escape_string($this->Shipping->Address->Country->ID), mysql_real_escape_string($this->Weight), mysql_real_escape_string($this->Postage->ID)));
			if($data->TotalRows > 0) {
				$quantity = floor($this->Weight / $data->Row['Weight']);

				if($quantity >= 1) {
					$shippingCalculator = new ShippingCalculator($this->Shipping->Address->Country->ID, $this->Shipping->Address->Region->ID, $this->SubTotal, $data->Row['Weight'], $this->Postage->ID);

					for($i = 0; $i < count($this->Line); $i++) {
						$shippingCalculator->Add($this->Line[$i]->Quantity, $this->Line[$i]->Product->ShippingClass->ID);
					}

					$this->AddShipping($data->Row['Weight'], $quantity, $shippingCalculator->GetTotal());
				}

				$weight = $this->Weight - ($data->Row['Weight'] * $quantity);

				if($weight > 0) {
					$shippingCalculator = new ShippingCalculator($this->Shipping->Address->Country->ID, $this->Shipping->Address->Region->ID, $this->SubTotal, $weight, $this->Postage->ID);

					for($i = 0; $i < count($this->Line); $i++) {
						$shippingCalculator->Add($this->Line[$i]->Quantity, $this->Line[$i]->Product->ShippingClass->ID);
					}

					$this->AddShipping($weight, 1, $shippingCalculator->GetTotal());
				}
			} else {
				$this->AddShipping($this->Weight, 1, $calc->GetTotal());
			}
			$data->Disconnect();

			$this->GetShippingLines();

			$this->TotalShipping = 0;

			for($i=0; $i<count($this->ShippingLine); $i++) {
				$this->TotalShipping += $this->ShippingLine[$i]->Charge * $this->ShippingLine[$i]->Quantity;
			}

			$this->PostageOptions = $calc->GetOptions();
			$this->Geozone = $calc->Geozone;

			$this->Error = $calc->Error;
			$this->FoundPostage = $calc->FoundPostage;
		} else {
			$this->PostageOptions = '<select style="width:100%;" name="deliveryOption" onChange="changeDelivery(this.value);"><option value="">Select Postage</option>';
			$data = new DataQuery("select * from postage order by Postage_Title");
			while ($data->Row) {
				$checked = ($data->Row['Postage_ID'] == $this->Postage->ID) ? 'selected="selected"' : '';
				$this->PostageOptions .= sprintf('<option value="%d" %s >%s</option>', $data->Row['Postage_ID'], $checked, $data->Row['Postage_Title']);
				$data->Next();
			}
			$data->Disconnect();

			$this->PostageOptions .= '</select>';
		}

		$this->TotalTax += $this->CalculateCustomTax($this->TotalShipping);
	}

	private function CalculateWeight() {
		$this->Weight = 0;

		$totalArea = 0;

		for($i=0; $i < count($this->Line); $i++) {
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

	function GetSupplierShipping() {
		$this->SupplierShipping = array();

		$supplierLines = array();

		for($i=0; $i<count($this->Line); $i++) {
			if($this->Line[$i]->Product->ID > 0) {
				$this->Line[$i]->Product->Get();

				if($this->Line[$i]->DespatchedFrom->Type == 'S') {
					if(!isset($supplierLines[$this->Line[$i]->DespatchedFrom->Contact->ID])) {
						$supplierLines[$this->Line[$i]->DespatchedFrom->Contact->ID] = array();
					}

					$supplierLines[$this->Line[$i]->DespatchedFrom->Contact->ID][] = $this->Line[$i];
				}
			}
		}

		foreach($supplierLines as $supplierId=>$lines) {
			$cost = 0;
			$weight = 0;

			for($i=0; $i<count($lines); $i++) {
				$cost += $lines[$i]->Cost * $lines[$i]->Quantity;
				$weight += $lines[$i]->Product->Weight * $lines[$i]->Quantity;
			}

			$calc = new SupplierShippingCalculator($this->Shipping->Address->Country->ID, $this->Shipping->Address->Region->ID, $cost, $weight, $this->Postage->ID, $supplierId);

			for($i=0; $i<count($lines); $i++) {
				$calc->Add($lines[$i]->Quantity, $lines[$i]->Product->ShippingClass->ID);
			}

			$this->SupplierShipping[] = array('Supplier' => new Supplier($supplierId), 'Calculator' => $calc);
		}
	}

    function GetSupplierShipped() {
		$this->SupplierShipped = array();

		$supplierLines = array();

		for($i=0; $i<count($this->Line); $i++) {
			if($this->Line[$i]->DespatchID > 0) {
				if($this->Line[$i]->DespatchedFrom->Type == 'S') {
					if($this->Line[$i]->Product->ID > 0) {
						$this->Line[$i]->Product->Get();

						if(!isset($supplierLines[$this->Line[$i]->DespatchedFrom->Contact->ID])) {
							$supplierLines[$this->Line[$i]->DespatchedFrom->Contact->ID] = array();
						}

	                    if(!isset($supplierLines[$this->Line[$i]->DespatchedFrom->Contact->ID][$this->Line[$i]->DespatchID])) {
							$supplierLines[$this->Line[$i]->DespatchedFrom->Contact->ID][$this->Line[$i]->DespatchID] = array();
						}

						$supplierLines[$this->Line[$i]->DespatchedFrom->Contact->ID][$this->Line[$i]->DespatchID][] = $this->Line[$i];
					}
				}
			}
		}

		foreach($supplierLines as $supplierId=>$despatches) {
			foreach($despatches as $despatchId=>$lines) {
				$cost = 0;
				$weight = 0;

				for($i=0; $i<count($lines); $i++) {
					$cost += $lines[$i]->Cost * $lines[$i]->Quantity;
					$weight += $lines[$i]->Product->Weight * $lines[$i]->Quantity;
				}

				$calc = new SupplierShippingCalculator($this->Shipping->Address->Country->ID, $this->Shipping->Address->Region->ID, $cost, $weight, $this->Postage->ID, $supplierId);

				for($i=0; $i<count($lines); $i++) {
					$calc->Add($lines[$i]->Quantity, $lines[$i]->Product->ShippingClass->ID);
				}

				$this->SupplierShipped[] = $calc;
			}
		}
	}

	function HasAlerts($warehouseId = 0) {
		$alerts = 0;
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM order_note WHERE Order_ID=%d AND Is_Alert='Y'", mysql_real_escape_string($this->ID)));
		$alerts += $data->Row['Counter'];
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM order_warehouse_note WHERE Order_ID=%d AND Is_Alert='Y' AND (0=%d OR Warehouse_ID=%d)", mysql_real_escape_string($this->ID), mysql_real_escape_string($warehouseId), mysql_real_escape_string($warehouseId)));
		$alerts += $data->Row['Counter'];
		$data->Disconnect();

		return ($alerts > 0) ? true : false;
	}

	function AddLine($quantity, $product, $isComplementary = 'N') {
		$this->Customer->Get();
		$this->Customer->Contact->Get();
		
		$line = new OrderLine();
		$line->Product->Get($product);
		$line->Quantity = $quantity;
		$line->Order = $this->ID;
		$line->IsComplementary = $isComplementary;

		if ($line->IsComplementary == 'Y') {
			$line->FreeOfCharge = 'Y';
		}

		if($this->Customer->Contact->IsTradeAccount == 'Y') {
			$tradeCost = ($line->Product->CacheRecentCost > 0) ? $line->Product->CacheRecentCost : $line->Product->CacheBestCost;
			
			$line->Price = ContactProductTrade::getPrice($this->Customer->Contact->ID, $line->Product->ID);
			$line->Price = ($line->Price <= 0) ? $tradeCost * ((TradeBanding::GetMarkup($tradeCost, $line->Product->ID) / 100) + 1) : $line->Price;
		} else {
			$line->Price = $line->Product->PriceCurrent;
		}
		
		$line->PriceRetail = $line->Product->PriceCurrent;
		$line->Total = $line->Price * $line->Quantity;
		$line->Cost = 0;

        if($line->Product->Type == 'S') {
			$data = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Cost>0 ORDER BY Preferred_Supplier ASC LIMIT 0, 1", mysql_real_escape_string($line->Product->ID)));
			if($data->TotalRows > 0) {
				$line->Cost = $data->Row['Cost'];
			}
			$data->Disconnect();
		} elseif($line->Product->Type == 'G') {
			$data = new DataQuery(sprintf("SELECT Product_ID, Component_Quantity FROM product_components WHERE Component_Of_Product_ID=%d", mysql_real_escape_string($line->Product->ID)));
			while($data->Row) {
	            $data2 = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Cost>0 ORDER BY Preferred_Supplier ASC LIMIT 0, 1", mysql_real_escape_string($data->Row['Product_ID'])));
				if($data2->TotalRows > 0) {
					$line->Cost += $data2->Row['Cost'] * $data->Row['Component_Quantity'];
				}
				$data2->Disconnect();

				$data->Next();
			}
			$data->Disconnect();
		}

		$line->Discount = 0;
		$line->DiscountInformation = '';
		$line->Tax = $this->CalculateCustomTax($line->Total);

		$updated = false;

		if (!$updated) {
			$line->Add();
		}

		return $line;
	}

	function GetTransactions() {
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT * FROM payment WHERE Order_ID=%d ORDER BY Payment_ID desc", mysql_real_escape_string($this->ID)));
		while ($data->Row) {
			$payment = new Payment();
			$payment->ID = $data->Row['Payment_ID'];
			$payment->Reference = $data->Row['Reference'];
			$payment->Type = $data->Row['Transaction_Type'];
			$payment->Gateway->ID = $data->Row['Gateway_ID'];
			$payment->Status = $data->Row['Status'];
			$payment->StatusDetail = $data->Row['Status_Detail'];
			$payment->SecurityKey = $data->Row['Security_Key'];
			$payment->AuthorisationNumber = $data->Row['Authorisation_Number'];
			$payment->AVSCV2 = $data->Row['AVSCV2'];
			$payment->AddressResult = $data->Row['Address_Result'];
			$payment->PostcodeResult = $data->Row['Postcode_Result'];
			$payment->CV2Result = $data->Row['CV2_Result'];
			$payment->Amount = $data->Row['Amount'];
			$payment->Order->ID = $data->Row['Order_ID'];
			$payment->Invoice->ID = $data->Row['Invoice_ID'];
			$payment->PaidOn = $data->Row['Paid_On'];
			$payment->CreatedOn = $data->Row['Created_On'];
			$payment->CreatedBy = $data->Row['Created_By'];
			$payment->ModifiedOn = $data->Row['Modified_On'];
			$payment->ModifiedBy = $data->Row['Modified_By'];

			$this->Transaction[] = $payment;
			$data->Next();
		}
		$data->Disconnect();
	}

	function IsUnique($column, $val) {
		$checkOrder = new DataQuery(sprintf("SELECT Order_ID FROM orders
												WHERE %s = '%s'", mysql_real_escape_string($column), mysql_real_escape_string($val)));
		$checkOrder->Disconnect();
		if ($checkOrder->TotalRows > 0) {
			$this->ID = $checkOrder->Row['Order_ID'];
			return false;
		} else {
			return true;
		}
	}



	function CheckAutomaticPack() {
		$this->Postage->Get();
		
		if($this->Postage->Days <= 1) {
			return false;
		}

		if(!$this->LinesFetched) {
			$this->GetLines();
		}

		$autopack = 0;
		$orderTotal = $this->Total;

		$datapack = new DataQuery(sprintf("SELECT s.value from settings as s
		where s.Setting_ID =%d", AUTO_PACK_LIMIT));
			$autopack = $datapack->Row['value'];
		$datapack->Disconnect();

		if($orderTotal > $autopack){
			return false;
		}
		
		for($i=0; $i<count($this->Line); $i++) {
			if($this->Line[$i]->Product->ID > 0) {
				$this->Line[$i]->Product->Get();
			
				if($this->Line[$i]->Product->Stocked == 'N') {
					return false;
				}

				$data = new DataQuery(sprintf("SELECT SUM(ws.Quantity_In_Stock) AS Quantity FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B' WHERE ws.Product_ID=%d", mysql_real_escape_string($this->Line[$i]->Product->ID)));
				if($data->Row['Quantity'] < $this->Line[$i]->Quantity) {
					$data->Disconnect();
					return false;
				}
				$data->Disconnect();
			} else {
				return false;
			}
		}

		return true;
	}

	function SetAutomaticPack() {
		if(!$this->LinesFetched) {
			$this->GetLines();
		}

		for($i=0; $i<count($this->Line); $i++) {
			$data = new DataQuery(sprintf("SELECT ws.Warehouse_ID FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B' WHERE ws.Product_ID=%d ORDER BY ws.Is_Archived ASC, ws.Stock_ID ASC LIMIT 0, 1", mysql_real_escape_string($this->Line[$i]->Product->ID)));
			if($data->TotalRows > 0) {
				$this->Line[$i]->DespatchedFrom->ID = $data->Row['Warehouse_ID'];
				$this->Line[$i]->Update();
			} else {
				$data2 = new DataQuery(sprintf("SELECT Warehouse_ID FROM warehouse WHERE Type='B' ORDER BY Warehouse_ID ASC LIMIT 0, 1"));
				
				$this->Line[$i]->DespatchedFrom->ID = $data2->Row['Warehouse_ID'];
				$this->Line[$i]->Update();
				
				$data2->Disconnect();
			}
			$data->Disconnect();
		}

		$this->ReceivedBy = $GLOBALS['SESSION_USER_ID'];
		$this->ReceivedOn = getDatetime();
		$this->Update();

		$this->Pack();
	}

	function CheckWarehouseShipped() {
		if(!$this->LinesFetched) {
			$this->GetLines();
		}

		$autoShip = true;

		for($i = 0; $i < count($this->Line); $i++) {
			$data = new DataQuery(sprintf("SELECT * FROM product AS p INNER JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B' INNER JOIN branch AS b ON b.Branch_ID=w.Type_Reference_ID AND b.Is_HQ='Y' WHERE p.Is_Warehouse_Shipped='Y' AND p.Product_ID=%d", mysql_real_escape_string($this->Line[$i]->Product->ID)));
			if($data->TotalRows == 0) {
				$autoShip = false;
			}
			$data->Disconnect();
		}

		return $autoShip;
	}

	function SetWarehouseShipped() {
		if(!$this->LinesFetched) {
			$this->GetLines();
		}

		$data = new DataQuery(sprintf("SELECT w.Warehouse_ID FROM warehouse AS w INNER JOIN branch AS b ON b.Branch_ID=w.Type_Reference_ID AND b.Is_HQ='Y' WHERE w.Type='B'"));
		$warehouseId = ($data->TotalRows > 0) ? $data->Row['Warehouse_ID'] : 0;
		$data->Disconnect();

		if($warehouseId > 0) {
			for($i = 0; $i < count($this->Line); $i++) {
				$this->Line[$i]->DespatchedFrom->ID = $warehouseId;
				$this->Line[$i]->Update();
			}

			$this->ReceivedBy = $GLOBALS['SESSION_USER_ID'];
			$this->ReceivedOn = getDatetime();
			$this->Update();

			$this->Pack();
		}
	}

	function SendDeclined() {
        $this->Customer->Get();
		$this->Customer->Contact->Get();

		$findReplace = new FindReplace();

		$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_declined.tpl");
		$orderHtml = "";

		for ($i = 0; $i < count($orderEmail); $i++) {
			$orderHtml .= $findReplace->Execute($orderEmail[$i]);
		}

		$findReplace = new FindReplace();
		$findReplace->Add('/\[BODY\]/', $orderHtml);
		$findReplace->Add('/\[NAME\]/', $GLOBALS['COMPANY']);

		$emailBody = $findReplace->Execute(Template::GetContent('email_template_standard'));

		$mail = new htmlMimeMail5();
		$mail->setFrom($GLOBALS['EMAIL_FROM']);
		$mail->setSubject(sprintf("%s Payment Declined [%s%s]", $GLOBALS['COMPANY'], $this->Prefix, $this->ID));
		$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
		$mail->setHTML($emailBody);
		$mail->send(array($this->EmailedTo));

		$this->Customer->Contact->SendSms(sprintf('Your recent BLT Direct order %s%d contains incorrect payment details. Please login to your account centre to modify your card details or contact customer services on 01473 559501.', $this->Prefix, $this->ID));
	}

	public function CalculatePackages() {
		$totalArea = 0;

		for($i=0; $i<count($this->Line); $i++) {
			if($this->Line[$i]->Product->ID > 0) {
				$this->Line[$i]->Product->Get();

				$totalArea += ($this->Line[$i]->Product->Width * $this->Line[$i]->Product->Height * $this->Line[$i]->Product->Depth) * $this->Line[$i]->Quantity;
			}
		}

		$package = array();

		$data = new DataQuery(sprintf("SELECT Package_ID, (Width*Height*Depth/100) * (100 - Reduction_Percent) AS Available_Area FROM package ORDER BY Available_Area ASC"));
		while($data->Row) {
			$package[] = $data->Row;

			$data->Next();
		}
		$data->Disconnect();

		return $this->GetPackages($package, $totalArea);
	}

	private function GetPackages($package = array(), $area = 0, $sizes = array()) {
		for($i=0; $i<count($package); $i++) {
			if($area <= $package[$i]['Available_Area']) {
				if(!isset($sizes[$package[$i]['Package_ID']])) {
					$sizes[$package[$i]['Package_ID']] = 0;
				}

				$sizes[$package[$i]['Package_ID']]++;
				break;

			} elseif($i == (count($package) - 1)) {
				$units = floor($area / $package[$i]['Available_Area']);
				$remaining = $area - ($package[$i]['Available_Area'] * $units);

				if(!isset($sizes[$package[$i]['Package_ID']])) {
					$sizes[$package[$i]['Package_ID']] = 0;
				}

				$sizes[$package[$i]['Package_ID']] += $units;

				$sizes = $this->GetPackages($package, $remaining, $sizes);
			}
		}

		return $sizes;
	}

	private function AddShipping($weight, $quantity, $charge) {
		$shipping = new OrderShipping();
		$shipping->OrderID = $this->ID;
		$shipping->Weight = $weight;
		$shipping->Quantity = $quantity;
		$shipping->Charge = $charge;
		$shipping->Add();
	}

	public function SetUnverifiedPayment($notifyAdministrator = false) {
		$this->IsPaymentUnverified = 'Y';
		$this->Update();

		if($notifyAdministrator) {
			$mail = new htmlMimeMail5();
			$mail->setFrom($GLOBALS['EMAIL_FROM']);
			$mail->setSubject(sprintf("%s Unverified Order Payment [%s%s]", $GLOBALS['COMPANY'], $this->Prefix, $this->ID));
			$mail->setText('This is an HTML email. If you only see this text your email client only supports plain text emails.');
			$mail->setHTML(sprintf('<p>Order %s%d has been processed with unverified card payment details.</p>', $this->Prefix, $this->ID));
			$mail->send(array('customerservices@bltdirect.com'));
		}
	}
	
	public function SetDefaultOwner() {
		if(empty($this->Customer->Contact->ID)) {
			$this->Customer->Get();
			$this->Customer->Contact->Get();
		}
			
		if($this->Customer->Contact->AccountManagerID > 0) {
			$this->OwnedBy = $this->Customer->Contact->AccountManagerID;
		}
	}
	
	public function Backorder() {
		if($this->Backordered == 'N') {
			if(empty($this->Customer->Contact->ID)) {
				$this->Customer->Get();
				$this->Customer->Contact->Get();
			}
			
			$this->Customer->Contact->SendSms(sprintf('Parts of your recent BLT Direct order %s%d are on backordered. Please login to your account centre to view your order or contact customer services on 01473 559501.', $this->Prefix, $this->ID));
		}
		
		$this->Backordered = 'Y';
		$this->Update();
	}
	
	public function HasInvoiceAddress() {
		$hasInvoice = false;

		if(($this->Invoice->Address->Country->ID > 0) && (($this->Invoice->Address->Country->ID <> $GLOBALS['SYSTEM_COUNTRY']) || (($this->Invoice->Address->Country->ID == $GLOBALS['SYSTEM_COUNTRY']) && ($this->Invoice->Address->Region->ID > 0))) && !empty($this->Invoice->Address->City)) {
			$hasInvoice = true;
		}

		return $hasInvoice;
	}
	
	public function CalculateNominalCode() {
		if(empty($this->PaymentMethod->Reference)) {
			$this->PaymentMethod->Get();
		}
		
		if(empty($this->Shipping->Address->Country->NominalCode)) {
			$this->Shipping->Address->Country->Get();
		}
			
		if($this->PaymentMethod->Reference == 'credit') {
			$this->NominalCode = empty($this->TaxExemptCode) ? $this->Shipping->Address->Country->NominalCodeAccount : $this->Shipping->Address->Country->NominalCodeAccountTaxFree;
		} else {
			$this->NominalCode = empty($this->TaxExemptCode) ? $this->Shipping->Address->Country->NominalCode : $this->Shipping->Address->Country->NominalCodeTaxFree;
		}
	}
	
	public function CalculateTaxRate() {
		$data = new DataQuery("SELECT Tax_Class_ID FROM tax_class WHERE Is_Default='Y'");
		if($data->TotalRows > 0) {
			$calculator = new TaxCalculator(0, $this->Shipping->Address->Country->ID, $this->Shipping->Address->Region->ID, $data->Row['Tax_Class_ID']);
			
			$this->TaxRate = $calculator->TaxRate;
		}
		$data->Disconnect();
	}
	
	public function GetTaxRate() {
		return empty($this->TaxExemptCode) ? $this->TaxRate : 0;
	}
	
	public function CalculateCustomTax($value = 0.00) {
		return empty($this->TaxExemptCode) ? $value * ($this->TaxRate / 100) : 0;
	}

	public function Unpack() {
		if(strtolower($this->Status) == 'packing') {
			$this->IsAutoShip = 'N';
			$this->Status = 'Pending';
			$this->Update();
		}
	}

	public function GetDocument($documentIdentifier = array()) {
		$this->PaymentMethod->Get();
		$this->Postage->Get();

		if(count($this->Line) <= 0) {
			$this->GetLines();
		}		

		if(empty($this->Customer->Contact->ID)) {
			$this->Customer->Get();
		}

		if(empty($this->Customer->Contact->Person->ID)) {
			$this->Customer->Contact->Get();
		}

		$this->TotalNet = $this->SubTotal + $this->TotalShipping - $this->TotalDiscount;

		$findReplace = new FindReplace();
		$findReplace->Add('/\[ORDER_REF\]/', $this->Prefix . $this->ID);
		$findReplace->Add('/\[CUSTOM_REF\]/', $this->CustomID);
		$findReplace->Add('/\[ORDER_DATE\]/', cDatetime($this->OrderedOn, 'longdate'));
		$findReplace->Add('/\[CUSTOMER_NAME\]/', $this->Customer->Contact->Person->GetFullName());
		$findReplace->Add('/\[CUSTOMER_ID\]/', $this->Customer->Contact->ID);
		$findReplace->Add('/\[BILLTO\]/', $this->GetBillingAddress());
		$findReplace->Add('/\[SHIPTO\]/', $this->GetShippingAddress());
		$findReplace->Add('/\[PAYMENT_METHOD\]/', $this->GetPaymentMethod());
		$findReplace->Add('/\[CARD_NUMBER\]/', ($this->PaymentMethod->Reference == 'card') ? $this->Card->PrivateNumber() : '');
		$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format($this->SubTotal, 2, '.', ','));
		$findReplace->Add('/\[SHIPPING\]/', "&pound;" . number_format($this->TotalShipping, 2, '.', ','));
		$findReplace->Add('/\[DISCOUNT\]/', "-&pound;" . number_format($this->TotalDiscount, 2, '.', ','));
		$findReplace->Add('/\[TAX\]/', "&pound;" . number_format($this->TotalTax, 2, '.', ','));
		$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format($this->Total, 2, '.', ','));
		$findReplace->Add('/\[ORDER_WEIGHT\]/', $this->Weight);
		$findReplace->Add('/\[DELIVERY\]/', $this->Postage->Name);
		$findReplace->Add('/\[ORDER_LINES\]/', $this->LinesHtml);
		$findReplace->Add('/\[NET\]/', "&pound;" . number_format($this->TotalNet, 2, '.', ','));

		if($this->Customer->Contact->IsTradeAccount == 'Y') {
			$findReplace->Add('/\[ORDER_TRADE_SAVING\]/', '&pound;' . number_format($this->SubTotalRetail - $this->SubTotal, 2, '.', ','));
			$findReplace->Add('/\[ORDER_TRADE_SAVING_PERCENTAGE\]/', ($this->SubTotalRetail > 0) ? (number_format(100 - (($this->SubTotal / $this->SubTotalRetail) * 100), 2, '.', ',') . '%') : '');
		}
		
		$handling = '';
		$handlingApplied = false;
		
		foreach($this->Line as $line) {
			if($line->HandlingCharge > 0) {
				$handlingApplied = true;
				break;
			}
		}
		
		if($handlingApplied) {
			$handling .= '<p>The following products within this order are subject to a restocking charge if they are returned to us:</p>';
			$handling .= '<ul>';
			
			foreach($this->Line as $line) {
				if($line->HandlingCharge > 0) {
					$handling .= sprintf('<li>%s (#%d) @ %s%%</li>', $line->Product->Name, $line->Product->ID, number_format($line->HandlingCharge, 2, '.', ''));
				}
			}
			
			$handling .= '</ul>';
		}
		
		$attachments = array();
		
		for($i=0; $i < count($this->Line); $i++) {
			if($this->Line[$i]->IncludeDownloads == 'Y') {
				$this->Line[$i]->Product->GetDownloads();
				
				foreach($this->Line[$i]->Product->Download as $download) {
					$attachments[] = array($GLOBALS['PRODUCT_DOWNLOAD_DIR_FS'] . $download->file->FileName, $GLOBALS['PRODUCT_DOWNLOAD_DIR_WS'] . $download->file->FileName);
				}
			}
		}
		
		$findReplace->Add('/\[ATTACHMENTS\]/', !empty($attachments) ? '<p>Please find attached data sheets for this quotation.</p>' : '');
		$findReplace->Add('/\[HANDLING\]/', $handling);

		return $findReplace->Execute(Template::GetContent($documentIdentifier['Template']));
	}

	public function GetPrintDocument($documentIdentifier = array()) {
		if(!isset($documentIdentifier['Template'])) {
			$documentIdentifier['Template'] = 'print_order_collection';
		}

		return $this->GetDocument($documentIdentifier);
	}

	public function AutoShip() {
		if((strtolower($this->Status) == 'unread') || (strtolower($this->Status) == 'pending')) {
			if($this->IsCollection == 'Y') {
				return false;
			}
			
			$this->Postage->Get();
		
			if($this->Postage->Days <= 1) {
				return false;
			}

			if(!$this->LinesFetched) {
				$this->GetLines();
			}

			foreach($this->Line as $line) {
				if($line->Product->IsDangerous == 'Y') {
					return false;
				}
			}

			$lines = array();

			foreach($this->Line as $line) {
				if(empty($line->DespatchID)) {
					$line->Product->Get();

					if($line->Product->ID == 0) {
						return false;
					} elseif($line->Product->Stocked == 'Y') {
						return false;
					} else {
						$lines[] = $line;
					}
				}
			}

			$suppliers = array();
			$supplierId = 0;

			foreach($lines as $line) {
				$productSupplierId = $line->Product->GetBestSupplierID($line->Quantity);

				if($productSupplierId == 0) {
					return false;
				}

				$suppliers[$productSupplierId] = true;
				$supplierId = $productSupplierId;
			}

			if(count($suppliers) > 1) {
				return false;
			}

			$supplier = new Supplier();
			$supplier->Get($supplierId);

			if($supplier->IsAutoShip == 'N') {
				return false;
			}

			$data = new DataQuery(sprintf("SELECT Warehouse_ID FROM warehouse WHERE Type='S' AND Type_Reference_ID=%d", mysql_real_escape_string($supplierId)));
			$warehouseId = ($data->TotalRows > 0) ? $data->Row['Warehouse_ID'] : 0;
			$data->Disconnect();

			if($warehouseId == 0) {
				return false;
			}

			foreach($lines as $line) {
				$line->DespatchedFrom->ID = $warehouseId;
				$line->Update();
			}

			$this->IsAutoShip = 'Y';
			$this->Status = 'Packing';
			$this->Recalculate();

			return true;
		}

		return false;
	}
	static function OrderRestock($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE orders SET Is_Restocked='Y' WHERE Order_ID=%d", mysql_real_escape_string($id)));
	}


	public function DismissOrder($id) {
		if(!is_numeric($id)){
			return false;
		}

		new DataQuery(sprintf("UPDATE orders SET Is_Dismissed='Y' WHERE Order_ID=%d", mysql_real_escape_string($id)));
	}

	public function UndismissOrder($id) {
		if(!is_numeric($id)){
			return false;
		}

		new DataQuery(sprintf("UPDATE orders SET Is_Dismissed='N' WHERE Order_ID=%d", mysql_real_escape_string($id)));
	}

}
