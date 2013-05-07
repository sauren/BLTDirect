<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ContactProductTrade.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailQueue.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Person.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Postage.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/QuoteLine.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/QuoteShipping.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Order.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/FindReplace.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Coupon.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CouponContact.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CustomerContact.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DiscountCollection.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ShippingCalculator.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/TaxCalculator.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Geozone.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/GlobalTaxCalculator.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Payment.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProForma.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProFormaLine.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Template.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/TradeBanding.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");

class Quote {
	var $ID;
	var $Line;
	var $CustomID;
	var $Prefix;
	var $Customer;
	var $Coupon;
	var $BillingOrg;
	var $Billing;
	var $ShippingOrg;
	var $Shipping;
	var $InvoiceOrg;
	var $Invoice;
	var $QuotedOn;
	var $EmailedOn;
	var $EmailedTo;
	var $Status;
	var $TotalLines;
	var $SubTotal;
	var $SubTotalRetail;
	var $TotalShipping;
	var $TotalDiscount;
	var $TotalNet;
	var $TotalTax;
	var $Total;
	var $Referrer;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $Weight;
	var $Postage;
	var $IsTaxExempt;
	var $TaxExemptCode;
	var $LinesHtml;
	var $Html;
	var $DiscountCollection;
	var $PostageOptions;
	var $Geozone;
	var $Error;
	var $FoundPostage;
	var $IsCustomShipping;
	var $Transaction;
	var $FollowedUp;
	var $ReviewOn;
	public $ShippingLine;
	public $ShippingMultiplier;

	function Quote($id = NULL) {
		$this->Prefix = 'W';
		$this->IsCustomShipping = 'N';
		$this->Line = array();
		$this->Customer = new Customer();
		$this->Coupon = new Coupon();
		$this->Coupon->ID = 0;
		$this->TotalDiscount = 0;
		$this->TotalNet;
		$this->Billing = new Person();
		$this->Shipping = new Person();
		$this->Invoice = new Person();
		$this->Postage = new Postage();
		$this->IsTaxExempt = 'N';
		$this->LinesHtml = '';
		$this->EmailedOn = '0000-00-00 00:00:00';
		$this->OrderedOn = '0000-00-00 00:00:00';
		$this->DiscountCollection = new DiscountCollection();
		$this->Transaction = array();
		$this->FollowedUp = 'N';
		$this->ReviewOn = '0000-00-00 00:00:00';
 		$this->ShippingLine = array();

		if (!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = NULL) {
		if (!is_null($id))
			$this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT q.*,
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
										from quote AS q
										LEFT JOIN regions AS rb
										ON q.Billing_Region_ID=rb.Region_ID
										LEFT JOIN countries AS cb
										ON q.Billing_Country_ID=cb.Country_ID
										LEFT JOIN regions AS rs
										ON q.Shipping_Region_ID=rs.Region_ID
										LEFT JOIN countries AS cs
										ON q.Shipping_Country_ID=cs.Country_ID
										LEFT JOIN regions AS ri
										ON q.Invoice_Region_ID=ri.Region_ID
										LEFT JOIN countries AS ci
										ON q.Invoice_Country_ID=ci.Country_ID
										LEFT JOIN address_format AS afb
										ON cb.Address_Format_ID=afb.Address_Format_ID
										LEFT JOIN address_format AS afs
										ON cs.Address_Format_ID=afs.Address_Format_ID
										LEFT JOIN address_format AS afi
										ON ci.Address_Format_ID=afi.Address_Format_ID
										WHERE Quote_ID=%d", mysql_real_escape_string($this->ID)));

		if ($data->TotalRows > 0) {
			$this->Prefix = $data->Row['Quote_Prefix'];
			$this->Customer->ID = $data->Row['Customer_ID'];
			$this->Coupon->ID = $data->Row['Coupon_ID'];
			$this->CustomID = $data->Row['Custom_Order_No'];
			$this->QuotedOn = $data->Row['Quoted_On'];
			$this->EmailedOn = $data->Row['Emailed_On'];
			$this->EmailedTo = $data->Row['Emailed_To'];
			$this->Status = $data->Row['Status'];
			$this->TotalLines = $data->Row['Total_Lines'];
			$this->SubTotal = $data->Row['Sub_Total'];
			$this->SubTotalRetail = $data->Row['Sub_Total_Retail'];
			$this->TotalShipping = $data->Row['Total_Shipping'];
			$this->IsCustomShipping = $data->Row['Is_Custom_Shipping'];
			$this->TotalDiscount = $data->Row['Total_Discount'];
			$this->TotalTax = $data->Row['Total_Tax'];
			$this->Total = $data->Row['Total'];
			$this->Billing->Title = $data->Row['Billing_Title'];
			$this->Billing->Name = $data->Row['Billing_First_Name'];
			$this->Billing->Initial = $data->Row['Billing_Initial'];
			$this->Billing->LastName = $data->Row['Billing_Last_Name'];
			$this->BillingOrg = $data->Row['Billing_Organisation_Name'];
			$this->Billing->Address->Line1 = $data->Row['Billing_Address_1'];
			$this->Billing->Address->Line2 = $data->Row['Billing_Address_2'];
			$this->Billing->Address->Line3 = $data->Row['Billing_Address_3'];
			$this->Billing->Address->City = $data->Row['Billing_City'];
			$this->Billing->Address->Country->ID = $data->Row['Billing_Country_ID'];
			$this->Billing->Address->Country->Name = $data->Row['Billing_Country'];
			$this->Billing->Address->Country->AddressFormat->Long = $data->Row['Billing_Address_Format'];
			$this->Billing->Address->Country->AddressFormat->Short = $data->Row['Billing_Address_Summary'];
			$this->Billing->Address->Region->ID = $data->Row['Billing_Region_ID'];
			$this->Billing->Address->Region->Name = $data->Row['Billing_Region_Name'];
			$this->Billing->Address->Zip = $data->Row['Billing_Zip'];
			$this->Shipping->Title = $data->Row['Shipping_Title'];
			$this->Shipping->Name = $data->Row['Shipping_First_Name'];
			$this->Shipping->Initial = $data->Row['Shipping_Initial'];
			$this->Shipping->LastName = $data->Row['Shipping_Last_Name'];
			$this->ShippingOrg = $data->Row['Shipping_Organisation_Name'];
			$this->Shipping->Address->Line1 = $data->Row['Shipping_Address_1'];
			$this->Shipping->Address->Line2 = $data->Row['Shipping_Address_2'];
			$this->Shipping->Address->Line3 = $data->Row['Shipping_Address_3'];
			$this->Shipping->Address->City = $data->Row['Shipping_City'];
			$this->Shipping->Address->Country->ID = $data->Row['Shipping_Country_ID'];
			$this->Shipping->Address->Country->Name = $data->Row['Shipping_Country'];
			$this->Shipping->Address->Country->AddressFormat->Long = $data->Row['Shipping_Address_Format'];
			$this->Shipping->Address->Country->AddressFormat->Short = $data->Row['Shipping_Address_Summary'];
			$this->Shipping->Address->Region->ID = $data->Row['Shipping_Region_ID'];
			$this->Shipping->Address->Region->Name = $data->Row['Shipping_Region_Name'];
			$this->Shipping->Address->Zip = $data->Row['Shipping_Zip'];
			$this->Invoice->Title = $data->Row['Invoice_Title'];
			$this->Invoice->Name = $data->Row['Invoice_First_Name'];
			$this->Invoice->Initial = $data->Row['Invoice_Initial'];
			$this->Invoice->LastName = $data->Row['Invoice_Last_Name'];
			$this->InvoiceOrg = $data->Row['Invoice_Organisation_Name'];
			$this->Invoice->Address->Line1 = $data->Row['Invoice_Address_1'];
			$this->Invoice->Address->Line2 = $data->Row['Invoice_Address_2'];
			$this->Invoice->Address->Line3 = $data->Row['Invoice_Address_3'];
			$this->Invoice->Address->City = $data->Row['Invoice_City'];
			$this->Invoice->Address->Country->ID = $data->Row['Invoice_Country_ID'];
			$this->Invoice->Address->Country->Name = $data->Row['Invoice_Country'];
			$this->Invoice->Address->Country->AddressFormat->Long = $data->Row['Invoice_Address_Format'];
			$this->Invoice->Address->Country->AddressFormat->Short = $data->Row['Invoice_Address_Summary'];
			$this->Invoice->Address->Region->ID = $data->Row['Invoice_Region_ID'];
			$this->Invoice->Address->Region->Name = $data->Row['Invoice_Region_Name'];
			$this->Invoice->Address->Zip = $data->Row['Invoice_Zip'];
			$this->Referrer = $data->Row['Referrer'];
			$this->IsTaxExempt = $data->Row['IsTaxExempt'];
			$this->TaxExemptCode = $data->Row['TaxExemptCode'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$this->FollowedUp = $data->Row['Followed_Up'];
			$this->ReviewOn = $data->Row['Review_On'];
			$this->Weight = $data->Row['Weight'];
			$this->Postage->Get($data->Row['Postage_ID']);
			$this->TotalNet = $this->SubTotal + $this->TotalShipping - $this->TotalDiscount;

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$sql = sprintf("INSERT INTO quote (Quote_Prefix, Customer_ID,
				Coupon_ID, Custom_Order_No, Quoted_On, Emailed_On, Emailed_To,
				Status, Total_Lines, Sub_Total, Sub_Total_Retail, Total_Shipping,
				Is_Custom_Shipping, Total_Discount, Total_Tax, Total, Billing_Title,
				Billing_First_Name, Billing_Initial, Billing_Last_Name,
				Billing_Organisation_Name, Billing_Address_1, Billing_Address_2,
				Billing_Address_3, Billing_City, Billing_Country_ID,
				Billing_Region_ID, Billing_Zip, Shipping_Title,
				Shipping_First_Name, Shipping_Initial, Shipping_Last_Name,
				Shipping_Organisation_Name, Shipping_Address_1,
				Shipping_Address_2, Shipping_Address_3, Shipping_City,
				Shipping_Country_ID, Shipping_Region_ID, Shipping_Zip, Invoice_Title,
				Invoice_First_Name, Invoice_Initial, Invoice_Last_Name,
				Invoice_Organisation_Name, Invoice_Address_1,
				Invoice_Address_2, Invoice_Address_3, Invoice_City,
				Invoice_Country_ID, Invoice_Region_ID, Invoice_Zip, Referrer,
				Weight, Postage_ID, IsTaxExempt, TaxExemptCode, Created_On,
				Created_By, Modified_On, Modified_By, Followed_Up, Review_On)
				VALUES (
					'%s', %d, %d, '%s', '%s', '%s', '%s',
					'%s', %d, %f, %f, %f, '%s', %f, %f, %f,
					'%s', '%s', '%s', '%s', '%s', '%s',
					'%s', '%s', '%s', %d, %d, '%s',
					'%s', '%s', '%s', '%s', '%s', '%s',
					'%s', '%s', '%s', %d, %d, '%s',
					'%s', '%s', '%s', '%s', '%s',
					'%s', '%s', '%s', '%s', %d, %d,
					'%s', '%s', '%s', %d, '%s', '%s', Now(), %d, Now(), %d, '%s', '%s')", 
					mysql_real_escape_string($this->Prefix), 
					mysql_real_escape_string($this->Customer->ID),
					mysql_real_escape_string($this->Coupon->ID),
					mysql_real_escape_string($this->CustomID), 
					mysql_real_escape_string($this->QuotedOn), 
					mysql_real_escape_string($this->EmailedOn),
					mysql_real_escape_string($this->EmailedTo),
					mysql_real_escape_string($this->Status),
					mysql_real_escape_string($this->TotalLines),
					mysql_real_escape_string($this->SubTotal),
					mysql_real_escape_string($this->SubTotalRetail),
					mysql_real_escape_string($this->TotalShipping),
					mysql_real_escape_string($this->IsCustomShipping),
					mysql_real_escape_string($this->TotalDiscount),
					mysql_real_escape_string($this->TotalTax),
					mysql_real_escape_string($this->Total),
					mysql_real_escape_string(stripslashes($this->Billing->Title)),
					mysql_real_escape_string(stripslashes($this->Billing->Name)), 
					mysql_real_escape_string($this->Billing->Initial),
					mysql_real_escape_string(stripslashes($this->Billing->LastName)),
					mysql_real_escape_string(stripslashes($this->BillingOrg)),
					mysql_real_escape_string(stripslashes($this->Billing->Address->Line1)),
					mysql_real_escape_string(stripslashes($this->Billing->Address->Line2)),
					mysql_real_escape_string(stripslashes($this->Billing->Address->Line3)),
					mysql_real_escape_string(stripslashes($this->Billing->Address->City)),
					mysql_real_escape_string($this->Billing->Address->Country->ID),
					mysql_real_escape_string($this->Billing->Address->Region->ID),
					mysql_real_escape_string(stripslashes($this->Billing->Address->Zip)),
					mysql_real_escape_string(stripslashes($this->Shipping->Title)), 
					mysql_real_escape_string(stripslashes($this->Shipping->Name)), 
					mysql_real_escape_string($this->Shipping->Initial),
					mysql_real_escape_string(stripslashes($this->Shipping->LastName)), 
					mysql_real_escape_string(stripslashes($this->ShippingOrg)), 
					mysql_real_escape_string(stripslashes($this->Shipping->Address->Line1)),
					mysql_real_escape_string(stripslashes($this->Shipping->Address->Line2)),
					mysql_real_escape_string(stripslashes($this->Shipping->Address->Line3)), 
					mysql_real_escape_string(stripslashes($this->Shipping->Address->City)), 
					mysql_real_escape_string($this->Shipping->Address->Country->ID), 
					mysql_real_escape_string($this->Shipping->Address->Region->ID), 
					mysql_real_escape_string(stripslashes($this->Shipping->Address->Zip)), 
					mysql_real_escape_string(stripslashes($this->Invoice->Title)),
					mysql_real_escape_string(stripslashes($this->Invoice->Name)),
					mysql_real_escape_string($this->Invoice->Initial), 
					mysql_real_escape_string(stripslashes($this->Invoice->LastName)),
					mysql_real_escape_string(stripslashes($this->InvoiceOrg)), 
					mysql_real_escape_string(stripslashes($this->Invoice->Address->Line1)),
					mysql_real_escape_string(stripslashes($this->Invoice->Address->Line2)), 
					mysql_real_escape_string(stripslashes($this->Invoice->Address->Line3)),
					mysql_real_escape_string(stripslashes($this->Invoice->Address->City)),
					mysql_real_escape_string($this->Invoice->Address->Country->ID), 
					mysql_real_escape_string($this->Invoice->Address->Region->ID), 
					mysql_real_escape_string(stripslashes($this->Invoice->Address->Zip)), 
					mysql_real_escape_string($this->Referrer), 
					mysql_real_escape_string($this->Weight), 
					mysql_real_escape_string($this->Postage->ID), 
					mysql_real_escape_string($this->IsTaxExempt), 
					mysql_real_escape_string($this->TaxExemptCode), 
					mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
					mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
					mysql_real_escape_string($this->FollowedUp), 
					mysql_real_escape_string($this->ReviewOn));

		$data = new DataQuery($sql);
		
		$this->ID = $data->InsertID;
		
		$this->SetInvoiceAddress();
	}

	function Delete($id = NULL) {
		if (!is_null($id))
			$this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		$this->Get();
		$this->DeleteLines();
		$sql = "delete from quote where Quote_ID=" . $this->ID;
		$data = new DataQuery($sql);

		QuoteShipping::DeleteQuote($this->ID);
	}

	function DeleteLines($id = NULL) {
		if (!is_null($id))
			$this->ID = $id;
		$this->GetLines();
		for ($i = 0; $i < count($this->Line); $i++) {
			$this->Line[$i]->Delete();
		}
	}

	function Cancel() {
		new DataQuery("UPDATE quote SET Status='Cancelled', Modified_By={$GLOBALS['SESSION_USER_ID']}, Modified_On=NOW() WHERE Quote_ID={$this->ID}");
	}

	function Convert() {
        $this->Customer->Get();
		$this->Customer->Contact->Get();

		$order = new Order();
		$order->QuoteID = $this->ID;
		$order->GetVia('Quote_ID', $order->QuoteID);
		$order->Prefix = ($this->CreatedBy > 0) ? 'T' : $this->Prefix;
		$order->Customer->ID = $this->Customer->ID;
		$order->Coupon->ID = $this->Coupon->ID;
		$order->CustomID = $this->CustomID;
		$order->QuotedOn = $this->QuotedOn;
		$order->EmailedOn = $this->EmailedOn;
		$order->EmailedTo = $this->EmailedTo;
		$order->Status = 'Unread';
		$order->TotalLines = $this->TotalLines;
		$order->SubTotal = $this->SubTotal;
		$order->SubTotalRetail = $this->SubTotalRetail;
		$order->TotalShipping = $this->TotalShipping;
		$order->IsCustomShipping = $this->IsCustomShipping;
		$order->IsTaxExempt = $this->IsTaxExempt;
		$order->TaxExemptCode = $this->TaxExemptCode;
		$order->TotalDiscount = $this->TotalDiscount;
		$order->TotalTax = $this->TotalTax;
		$order->Total = $this->Total;
		$order->Billing->Title = $this->Billing->Title;
		$order->Billing->Name = $this->Billing->Name;
		$order->Billing->Initial = $this->Billing->Initial;
		$order->Billing->LastName = $this->Billing->LastName;
		$order->BillingOrg = $this->BillingOrg;
		$order->Billing->Address->Line1 = $this->Billing->Address->Line1;
		$order->Billing->Address->Line2 = $this->Billing->Address->Line2;
		$order->Billing->Address->Line3 = $this->Billing->Address->Line3;
		$order->Billing->Address->City = $this->Billing->Address->City;
		$order->Billing->Address->Country->ID = $this->Billing->Address->Country->ID;
		$order->Billing->Address->Country->Name = $this->Billing->Address->Country->Name;
		$order->Billing->Address->Country->AddressFormat->Long = $this->Billing->Address->Country->AddressFormat->Long;
		$order->Billing->Address->Country->AddressFormat->Short = $this->Billing->Address->Country->AddressFormat->Short;
		$order->Billing->Address->Region->ID = $this->Billing->Address->Region->ID;
		$order->Billing->Address->Region->Name = $this->Billing->Address->Region->Name;
		$order->Billing->Address->Zip = $this->Billing->Address->Zip;
		$order->Shipping->Title = $this->Shipping->Title;
		$order->Shipping->Name = $this->Shipping->Name;
		$order->Shipping->Initial = $this->Shipping->Initial;
		$order->Shipping->LastName = $this->Shipping->LastName;
		$order->ShippingOrg = $this->ShippingOrg;
		$order->Shipping->Address->Line1 = $this->Shipping->Address->Line1;
		$order->Shipping->Address->Line2 = $this->Shipping->Address->Line2;
		$order->Shipping->Address->Line3 = $this->Shipping->Address->Line3;
		$order->Shipping->Address->City = $this->Shipping->Address->City;
		$order->Shipping->Address->Country->ID = $this->Shipping->Address->Country->ID;
		$order->Shipping->Address->Country->Name = $this->Shipping->Address->Country->Name;
		$order->Shipping->Address->Country->AddressFormat->Long = $this->Shipping->Address->Country->AddressFormat->Long;
		$order->Shipping->Address->Country->AddressFormat->Short = $this->Shipping->Address->Country->AddressFormat->Short;
		$order->Shipping->Address->Region->ID = $this->Shipping->Address->Region->ID;
		$order->Shipping->Address->Region->Name = $this->Shipping->Address->Region->Name;
		$order->Shipping->Address->Zip = $this->Shipping->Address->Zip;
		$order->Invoice->Title = $this->Invoice->Title;
		$order->Invoice->Name = $this->Invoice->Name;
		$order->Invoice->Initial = $this->Invoice->Initial;
		$order->Invoice->LastName = $this->Invoice->LastName;
		$order->InvoiceOrg = $this->InvoiceOrg;
		$order->Invoice->Address->Line1 = $this->Invoice->Address->Line1;
		$order->Invoice->Address->Line2 = $this->Invoice->Address->Line2;
		$order->Invoice->Address->Line3 = $this->Invoice->Address->Line3;
		$order->Invoice->Address->City = $this->Invoice->Address->City;
		$order->Invoice->Address->Country->ID = $this->Invoice->Address->Country->ID;
		$order->Invoice->Address->Country->Name = $this->Invoice->Address->Country->Name;
		$order->Invoice->Address->Country->AddressFormat->Long = $this->Invoice->Address->Country->AddressFormat->Long;
		$order->Invoice->Address->Country->AddressFormat->Short = $this->Invoice->Address->Country->AddressFormat->Short;
		$order->Invoice->Address->Region->ID = $this->Invoice->Address->Region->ID;
		$order->Invoice->Address->Region->Name = $this->Invoice->Address->Region->Name;
		$order->Invoice->Address->Zip = $this->Invoice->Address->Zip;
		$order->Referrer = $this->Referrer;
		$order->OrderedOn = date("Y-m-d H:i:s");
		$order->Weight = $this->Weight;
		$order->Postage = $this->Postage;
		$order->SetDefaultOwner();

		if ($order->OwnedBy == 0) {
			$order->OwnedBy = $order->GetAcountManager($order->Customer->ID);
		}
		
		if($order->OwnedBy == 0) {
			$order->OwnedBy = $this->CreatedBy;
		}
		
		if($order->OwnedBy == 0) {
			$this->OwnedBy = $GLOBALS['SESSION_USER_ID'];
		}
		
		$order->Add();
		
		foreach ($this->Line as $l) {
			$ol = new OrderLine();
			$ol->Order = $order->ID;
			$ol->Product = $l->Product;
			$ol->Quantity = $l->Quantity;
			$ol->Price = $l->Price;
			$ol->PriceRetail = $l->PriceRetail;
			$ol->Discount = $l->Discount;
			$ol->DiscountInformation = $l->DiscountInformation;
			$ol->Tax = $l->Tax;
			$ol->Total = $l->Total;
			$ol->Status = $l->Status;
			$ol->HandlingCharge = $l->HandlingCharge;
			$ol->IncludeDownloads = $l->IncludeDownloads;
			$ol->Add();
		}

		$this->GetShippingLines();

		for($i=0; $i<count($this->ShippingLine); $i++) {
			$shipping = new OrderShipping();
			$shipping->OrderID = $order->ID;
			$shipping->Weight = $this->ShippingLine[$i]->Weight;
			$shipping->Quantity = $this->ShippingLine[$i]->Quantity;
			$shipping->Charge = $this->ShippingLine[$i]->Charge;
			$shipping->Add();
		}

		$this->Status = 'Ordered';
		$this->Update();

		return $order->ID;
	}
	
	function ConvertToProforma() {
        $this->Customer->Get();
		$this->Customer->Contact->Get();

		$proforma = new ProForma();
		$proforma->Quote->ID = $this->ID;
		$proforma->Prefix = ($this->CreatedBy > 0) ? 'T' : $this->Prefix;
		$proforma->Customer->ID = $this->Customer->ID;
		$proforma->Coupon->ID = $this->Coupon->ID;
		$proforma->CustomID = $this->CustomID;
		$proforma->FormedOn = date('Y-m-d H:i:s');
		$proforma->Status = 'Pending';
		$proforma->TotalLines = $this->TotalLines;
		$proforma->SubTotal = $this->SubTotal;
		$proforma->SubTotalRetail = $this->SubTotalRetail;
		$proforma->TotalShipping = $this->TotalShipping;
		$proforma->TotalDiscount = $this->TotalDiscount;
		$proforma->TotalNet = $this->TotalNet;
		$proforma->TotalTax = $this->TotalTax;
		$proforma->Total = $this->Total;
		$proforma->Billing->Title = $this->Billing->Title;
		$proforma->Billing->Name = $this->Billing->Name;
		$proforma->Billing->Initial = $this->Billing->Initial;
		$proforma->Billing->LastName = $this->Billing->LastName;
		$proforma->BillingOrg = $this->BillingOrg;
		$proforma->Billing->Address->Line1 = $this->Billing->Address->Line1;
		$proforma->Billing->Address->Line2 = $this->Billing->Address->Line2;
		$proforma->Billing->Address->Line3 = $this->Billing->Address->Line3;
		$proforma->Billing->Address->City = $this->Billing->Address->City;
		$proforma->Billing->Address->Country->ID = $this->Billing->Address->Country->ID;
		$proforma->Billing->Address->Country->Name = $this->Billing->Address->Country->Name;
		$proforma->Billing->Address->Country->AddressFormat->Long = $this->Billing->Address->Country->AddressFormat->Long;
		$proforma->Billing->Address->Country->AddressFormat->Short = $this->Billing->Address->Country->AddressFormat->Short;
		$proforma->Billing->Address->Region->ID = $this->Billing->Address->Region->ID;
		$proforma->Billing->Address->Region->Name = $this->Billing->Address->Region->Name;
		$proforma->Billing->Address->Zip = $this->Billing->Address->Zip;
		$proforma->Shipping->Title = $this->Shipping->Title;
		$proforma->Shipping->Name = $this->Shipping->Name;
		$proforma->Shipping->Initial = $this->Shipping->Initial;
		$proforma->Shipping->LastName = $this->Shipping->LastName;
		$proforma->ShippingOrg = $this->ShippingOrg;
		$proforma->Shipping->Address->Line1 = $this->Shipping->Address->Line1;
		$proforma->Shipping->Address->Line2 = $this->Shipping->Address->Line2;
		$proforma->Shipping->Address->Line3 = $this->Shipping->Address->Line3;
		$proforma->Shipping->Address->City = $this->Shipping->Address->City;
		$proforma->Shipping->Address->Country->ID = $this->Shipping->Address->Country->ID;
		$proforma->Shipping->Address->Country->Name = $this->Shipping->Address->Country->Name;
		$proforma->Shipping->Address->Country->AddressFormat->Long = $this->Shipping->Address->Country->AddressFormat->Long;
		$proforma->Shipping->Address->Country->AddressFormat->Short = $this->Shipping->Address->Country->AddressFormat->Short;
		$proforma->Shipping->Address->Region->ID = $this->Shipping->Address->Region->ID;
		$proforma->Shipping->Address->Region->Name = $this->Shipping->Address->Region->Name;
		$proforma->Shipping->Address->Zip = $this->Shipping->Address->Zip;
		$proforma->Referrer = $this->Referrer;
		$proforma->Weight = $this->Weight;
		$proforma->Postage = $this->Postage;
		$proforma->IsTaxExempt = $this->IsTaxExempt;
		$proforma->TaxExemptCode = $this->TaxExemptCode;
		$proforma->IsCustomShipping = $this->IsCustomShipping;
		$proforma->Add();
		
		foreach ($this->Line as $l) {
			$ol = new ProformaLine();
	
			$ol->ProFormaID = $proforma->ID;
			$ol->Product = $l->Product;
			$ol->Quantity = $l->Quantity;
			$ol->Price = $l->Price;
			$ol->PriceRetail = $l->PriceRetail;
			$ol->Discount = $l->Discount;
			$ol->DiscountInformation = $l->DiscountInformation;
			$ol->Tax = $l->Tax;
			$ol->Total = $l->Total;
			$ol->Status = $l->Status;
			$ol->HandlingCharge = $l->HandlingCharge;
			$ol->IncludeDownloads = $l->IncludeDownloads;
			$ol->Add();
		}

		$this->Status = 'Ordered';
		$this->Update();

		return $proforma->ID;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		$sql = sprintf("UPDATE quote SET
							Quote_Prefix='%s', Customer_ID=%d, Coupon_ID=%d,
							Custom_Order_No='%s', Quoted_On='%s',
							Emailed_On='%s', Emailed_To='%s',
							Status='%s', Total_Lines=%d, Sub_Total='%s', Sub_Total_Retail='%s',
							Total_Shipping=%f, Is_Custom_Shipping='%s',
							Total_Discount=%f,
							Total_Tax=%f, Total=%f, Billing_Title='%s',
							Billing_First_Name='%s', Billing_Initial='%s',
							Billing_Last_Name='%s',
							Billing_Organisation_Name='%s',
							Billing_Address_1='%s', Billing_Address_2='%s',
							Billing_Address_3='%s', Billing_City='%s',
							Billing_Country_ID=%d, Billing_Region_ID=%d,
							Billing_Zip='%s', Shipping_Title='%s',
							Shipping_First_Name='%s', Shipping_Initial='%s',
							Shipping_Last_Name='%s',
							Shipping_Organisation_Name='%s',
							Shipping_Address_1='%s', Shipping_Address_2='%s',
							Shipping_Address_3='%s', Shipping_City='%s',
							Shipping_Country_ID=%d, Shipping_Region_ID=%d,
							Shipping_Zip='%s', Invoice_Title='%s',
							Invoice_First_Name='%s', Invoice_Initial='%s',
							Invoice_Last_Name='%s',
							Invoice_Organisation_Name='%s',
							Invoice_Address_1='%s', Invoice_Address_2='%s',
							Invoice_Address_3='%s', Invoice_City='%s',
							Invoice_Country_ID=%d, Invoice_Region_ID=%d,
							Invoice_Zip='%s', Referrer='%s',
							Weight=%f, Postage_ID=%d, IsTaxExempt='%s', TaxExemptCode='%s',
							Modified_On=Now(), Modified_By=%d, Followed_Up='%s', Review_On='%s'
							WHERE Quote_ID=%d", 
								mysql_real_escape_string($this->Prefix), 
								mysql_real_escape_string($this->Customer->ID), 
								mysql_real_escape_string($this->Coupon->ID), 
								mysql_real_escape_string($this->CustomID), 
								mysql_real_escape_string($this->QuotedOn), 
								mysql_real_escape_string($this->EmailedOn),
								mysql_real_escape_string($this->EmailedTo),
								mysql_real_escape_string($this->Status), 
								mysql_real_escape_string($this->TotalLines), 
								mysql_real_escape_string($this->SubTotal), 
								mysql_real_escape_string($this->SubTotalRetail), 
								mysql_real_escape_string($this->TotalShipping), 
								mysql_real_escape_string($this->IsCustomShipping),
								mysql_real_escape_string($this->TotalDiscount), 
								mysql_real_escape_string($this->TotalTax),
								mysql_real_escape_string($this->Total), 
								mysql_real_escape_string(stripslashes($this->Billing->Title)), 
								mysql_real_escape_string(stripslashes($this->Billing->Name)),
								mysql_real_escape_string($this->Billing->Initial),
								mysql_real_escape_string(stripslashes($this->Billing->LastName)),
								mysql_real_escape_string(stripslashes($this->BillingOrg)),
								mysql_real_escape_string(stripslashes($this->Billing->Address->Line1)),
								mysql_real_escape_string(stripslashes($this->Billing->Address->Line2)),
								mysql_real_escape_string(stripslashes($this->Billing->Address->Line3)),
								mysql_real_escape_string(stripslashes($this->Billing->Address->City)), 
								mysql_real_escape_string($this->Billing->Address->Country->ID), 
								mysql_real_escape_string($this->Billing->Address->Region->ID), 
								mysql_real_escape_string(stripslashes($this->Billing->Address->Zip)), 
								mysql_real_escape_string(stripslashes($this->Shipping->Title)), 
								mysql_real_escape_string(stripslashes($this->Shipping->Name)), 
								mysql_real_escape_string($this->Shipping->Initial), 
								mysql_real_escape_string(stripslashes($this->Shipping->LastName)), 
								mysql_real_escape_string(stripslashes($this->ShippingOrg)), 
								mysql_real_escape_string(stripslashes($this->Shipping->Address->Line1)),
								mysql_real_escape_string(stripslashes($this->Shipping->Address->Line2)),
								mysql_real_escape_string(stripslashes($this->Shipping->Address->Line3)), 
								mysql_real_escape_string(stripslashes($this->Shipping->Address->City)), 
								mysql_real_escape_string($this->Shipping->Address->Country->ID), 
								mysql_real_escape_string($this->Shipping->Address->Region->ID), 
								mysql_real_escape_string(stripslashes($this->Shipping->Address->Zip)), 
								mysql_real_escape_string(stripslashes($this->Invoice->Title)), 
								mysql_real_escape_string(stripslashes($this->Invoice->Name)), 
								mysql_real_escape_string($this->Invoice->Initial), 
								mysql_real_escape_string(stripslashes($this->Invoice->LastName)), 
								mysql_real_escape_string(stripslashes($this->InvoiceOrg)),
								mysql_real_escape_string(stripslashes($this->Invoice->Address->Line1)), 
								mysql_real_escape_string(stripslashes($this->Invoice->Address->Line2)),
								mysql_real_escape_string(stripslashes($this->Invoice->Address->Line3)),
								mysql_real_escape_string(stripslashes($this->Invoice->Address->City)),
								mysql_real_escape_string($this->Invoice->Address->Country->ID), 
								mysql_real_escape_string($this->Invoice->Address->Region->ID), 
								mysql_real_escape_string(stripslashes($this->Invoice->Address->Zip)), 
								mysql_real_escape_string($this->Referrer), 
								mysql_real_escape_string($this->Weight), 
								mysql_real_escape_string($this->Postage->ID), 
								mysql_real_escape_string($this->IsTaxExempt), 
								mysql_real_escape_string($this->TaxExemptCode),
								mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
								mysql_real_escape_string($this->FollowedUp), 
								mysql_real_escape_string($this->ReviewOn), 
								mysql_real_escape_string($this->ID));
							
		new DataQuery($sql);
	}

	function GetLines() {
		$this->Line = array();
		$this->LinesHtml = "";
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT Quote_Line_ID FROM quote_line WHERE Quote_ID=%d", mysql_real_escape_string($this->ID)));
		while ($data->Row) {
			$line = new QuoteLine();
			$line->Get($data->Row['Quote_Line_ID']);
			
			$this->Line[] = $line;
			$this->LinesHtml .= sprintf("<tr><td>%sx</td><td>%s</td><td>%s</td><td align=\"right\">&pound;%s</td><td align=\"right\">&pound;%s</td><td align=\"right\">&pound;%s</td></tr>", $line->Quantity, $line->Product->Name, $line->Product->ID, number_format($line->Price, 2, '.', ','), number_format($line->Price - ($line->Discount / $line->Quantity), 2, '.', ','), number_format($line->Total - $line->Discount, 2, '.', ','));

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
		$data = new DataQuery(sprintf("SELECT QuoteShippingID FROM quote_shipping WHERE QuoteID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->ShippingLine[] = new QuoteShipping($data->Row['QuoteShippingID']);

			$data->Next();
		}
		$data->Disconnect();

		for($i=0; $i<count($this->ShippingLine); $i++) {
			$this->ShippingMultiplier += $this->ShippingLine[$i]->Quantity;
		}
	}

	function SendEmail() {
		if (count($this->Line) <= 0) {
			$this->GetLines();
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$this->TotalNet = $this->SubTotal + $this->TotalShipping - $this->TotalDiscount;

		if (empty($this->Customer->Contact->ID)) {
			$this->Customer->Get();
		}
		if (empty($this->Customer->Contact->Person->ID)) {
			$this->Customer->Contact->Get();
		}

		$this->Postage->Get();

		$findReplace = new FindReplace();
		$findReplace->Add('/\[QUOTE_REF\]/', $this->Prefix . $this->ID);
		$findReplace->Add('/\[CUSTOM_REF\]/', $this->CustomID);
		$findReplace->Add('/\[QUOTE_DATE\]/', cDatetime($this->QuotedOn, 'longdate'));
		$findReplace->Add('/\[CUSTOMER_NAME\]/', $this->Customer->Contact->Person->GetFullName());
		$findReplace->Add('/\[CUSTOMER_ID\]/', $this->Customer->Contact->ID);
		$findReplace->Add('/\[BILLTO\]/', $this->GetBillingAddress());
		$findReplace->Add('/\[SHIPTO\]/', $this->GetShippingAddress());
		$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format($this->SubTotal - $this->TotalDiscount, 2, '.', ','));
		$findReplace->Add('/\[SHIPPING\]/', "&pound;" . number_format($this->TotalShipping, 2, '.', ','));
		$findReplace->Add('/\[DISCOUNT\]/', "-&pound;" . number_format($this->TotalDiscount, 2, '.', ','));
		$findReplace->Add('/\[TAX\]/', "&pound;" . number_format($this->TotalTax, 2, '.', ','));
		$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format($this->Total, 2, '.', ','));
		$findReplace->Add('/\[QUOTE_WEIGHT\]/', $this->Weight);
		$findReplace->Add('/\[DELIVERY\]/', $this->Postage->Name);
		$findReplace->Add('/\[QUOTE_LINES\]/', $this->LinesHtml);
		$findReplace->Add('/\[NET\]/', "&pound;" . number_format($this->TotalNet, 2, '.', ','));
		
		if($this->Customer->Contact->IsTradeAccount == 'Y') {
			$findReplace->Add('/\[QUOTE_TRADE_SAVING\]/', '&pound;' . number_format($this->SubTotalRetail - $this->SubTotal, 2, '.', ','));
			$findReplace->Add('/\[QUOTE_TRADE_SAVING_PERCENTAGE\]/', ($this->SubTotalRetail > 0) ? (number_format(100 - (($this->SubTotal / $this->SubTotalRetail) * 100), 2, '.', ',') . '%') : '');
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
			$handling .= '<p>The following products within this quotation are subject to a restocking charge if they are returned to us:</p>';
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

		$quoteHtml = $findReplace->Execute(Template::GetContent((($this->CreatedBy == 0) ? 'email_quote_standard' : 'email_quote_telesales') . (($this->Customer->Contact->IsTradeAccount == 'Y') ? '_trade' : '')));

		$findReplace = new FindReplace();
		$findReplace->Add('/\[BODY\]/', $quoteHtml);
		$findReplace->Add('/\[NAME\]/', $this->Customer->Contact->Person->GetFullName());
		
		$fromAddress = '';

		if($this->CreatedBy > 0) {
			$user = new User($this->CreatedBy);
			
			$findReplace->Add('/\[SALES\]/', sprintf('%s<br />%s<br />%s', trim(sprintf("%s %s", $user->Person->Name, $user->Person->LastName)), (strlen(trim($user->Person->Phone1)) > 0) ? $user->Person->Phone1 : Setting::GetValue('default_userphone'), !empty($user->SecondaryMailbox) ? $user->SecondaryMailbox : $user->Username));

			$fromAddress = !empty($user->SecondaryMailbox) ? $user->SecondaryMailbox : $user->Username;
		} else {
			$findReplace->Add('/\[SALES\]/', sprintf('%s<br />%s<br />%s', Setting::GetValue('default_username'), Setting::GetValue('default_userphone'), Setting::GetValue('default_useremail')));
		}
		
		$emailBody = $findReplace->Execute(Template::GetContent(($this->CreatedBy == 0) ? 'email_template_standard' : 'email_template_personal'));

		$returnPath = explode('@', $GLOBALS['EMAIL_RETURN']);
		$returnPath = (count($returnPath) == 2) ? sprintf('%s.quote.%d@%s', $returnPath[0], $this->ID, $returnPath[1]) : $GLOBALS['EMAIL_RETURN'];
		
		$queue = new EmailQueue();
		$queue->GetModuleID('quotes');
		$queue->Subject = sprintf("%s Quote Confirmation [#%s%s]", $GLOBALS['COMPANY'], $this->Prefix, $this->ID);
		$queue->Body = $emailBody;
		$queue->ToAddress = $this->Customer->GetEmail();
		$queue->FromAddress = !empty($fromAddress) ? $fromAddress : $queue->FromAddress;
		$queue->ReturnPath = $returnPath;
		$queue->Priority = 'H';
		$queue->Add();
		
		foreach($attachments as $attachment) {
			if(count($attachment) == 2) {
				$queue->AddAttachment($attachment[0], $attachment[1]);
			}
		}
		
		new DataQuery(sprintf("UPDATE quote
                                                  SET Emailed_On=Now(),
                                                  Emailed_To='%s'
                                                  WHERE Quote_ID=%d", mysql_real_escape_string($this->Customer->GetEmail()), mysql_real_escape_string($this->ID)));
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

	function GenerateFromCart(&$cart) {
		$cart->Customer->Get();
		$cart->Customer->Contact->Get();

		$this->Coupon->ID = $cart->Coupon->ID;
		$this->Total = $cart->Total;
		$this->SubTotal = $cart->SubTotal;
		$this->SubTotalRetail = $cart->SubTotalRetail;
		$this->TotalShipping = $cart->ShippingTotal;
		$this->TotalDiscount = $cart->Discount;
		$this->TotalTax = $cart->TaxTotal;
		$this->TotalLines = $cart->TotalLines;
		$this->IsCustomShipping = $cart->IsCustomShipping;

		$this->Billing = $cart->Customer->Contact->Person;
		
		if($cart->Customer->Contact->HasParent) {
			$this->BillingOrg = $cart->Customer->Contact->Parent->Organisation->Name;
		}

		$this->QuotedOn = getDatetime();
		$this->Status = "Pending";
		$this->Customer = $cart->Customer;
		$this->Weight = $cart->Weight;
		$this->Postage->ID = $cart->Postage;
		$this->TaxExemptCode = $cart->TaxExemptCode;

		if (strtolower($cart->ShipTo) == 'billing') {
			$this->Shipping = $cart->Customer->Contact->Person;
			
			if ($cart->Customer->Contact->HasParent) {
				$this->ShippingOrg = $cart->Customer->Contact->Parent->Organisation->Name;
			}
		} else {
			$cc = new CustomerContact($cart->ShipTo);

			$this->Shipping = $cc;
			$this->ShippingOrg = $cc->OrgName;
		}
		
		if (empty($cart->QuoteID)) {
			$this->Add();
		} else {
			$this->Update();
		}

		$cart->GetShippingLines();

		for($i=0; $i<count($cart->ShippingLine); $i++) {
			$shipping = new QuoteShipping();
			$shipping->QuoteID = $this->ID;
			$shipping->Weight = $cart->ShippingLine[$i]->Weight;
			$shipping->Quantity = $cart->ShippingLine[$i]->Quantity;
			$shipping->Charge = $cart->ShippingLine[$i]->Charge;
			$shipping->Add();
		}

		$this->DeleteLines();

		for ($i = 0; $i < count($cart->Line); $i++) {
			$line = new QuoteLine();
			$line->QuoteID = $this->ID;
			$line->Product->ID = $cart->Line[$i]->Product->ID;
			$line->Product->Name = $cart->Line[$i]->Product->Name;
			$line->Quantity = $cart->Line[$i]->Quantity;
			$line->Price = $cart->Line[$i]->Price;
			$line->PriceRetail = $cart->Line[$i]->PriceRetail;
			$line->HandlingCharge = $cart->Line[$i]->HandlingCharge;
			$line->IncludeDownloads = $cart->Line[$i]->IncludeDownloads;
			$line->Total = $cart->Line[$i]->Total;
			$line->Discount = $cart->Line[$i]->Discount;
			$line->DiscountInformation = $cart->Line[$i]->DiscountInformation;
			$line->Tax = round($cart->Line[$i]->Tax, 2);
			$line->Add();

			$cart->Line[$i]->Remove();
		}

		$cart->Coupon->ID = 0;
		$cart->Update();

		$cart->Customer->Contact->IsCustomer = 'Y';
		$cart->Customer->Contact->Update();
	}

	function Recalculate() {
		if (count($this->Line) == 0) {
			$this->GetLines();
		}

		$this->TotalLines = count($this->Line);
		$this->SubTotal = 0;
		$this->SubTotalRetail = 0;

		if ($this->IsCustomShipping == 'N') {
			$this->TotalShipping = 0;
		}

		$this->TotalDiscount = 0;
		$this->TotalTax = 0;
		$this->Total = 0;
		$this->Weight = 0;
		
		if (!empty($this->Coupon->ID)) {
			$this->Coupon->Get();
		}

		$this->DiscountCollection->Get($this->Customer);
		
		$taxCalculator = new GlobalTaxCalculator($this->Shipping->Address->Country->ID, $this->Shipping->Address->Region->ID);

		for($i=0; $i < count($this->Line); $i++){
			$this->Line[$i]->Product->Get();
			$this->Line[$i]->Discount = 0;
			$this->Line[$i]->Tax = 0;
			$this->Line[$i]->Total = $this->Line[$i]->Quantity * $this->Line[$i]->Price;
			
			$customDiscount = false;

			if (!empty($this->Line[$i]->DiscountInformation)) {
				$discountCustom = explode(':', $this->Line[$i]->DiscountInformation);

				if (trim($discountCustom[0]) == 'azxcustom') {
					$customDiscount = true;
				}
			}

			if ($customDiscount) {
				$this->Line[$i]->Discount = round($this->Line[$i]->Price * ($discountCustom[1] / 100), 2) * $this->Line[$i]->Quantity;
			} else {
				$this->Line[$i]->Discount = 0;
				$this->Line[$i]->DiscountInformation = '';

				if (!empty($this->Coupon->ID)) {
					$couponLineTotal = $this->Coupon->DiscountProduct($this->Line[$i]->Product, $this->Line[$i]->Quantity);

					if ($couponLineTotal < $this->Line[$i]->Total) {
						$this->Line[$i]->Discount = $this->Line[$i]->Total - $couponLineTotal;
						$this->Line[$i]->DiscountInformation = sprintf('%s (Ref: %s)', $this->Coupon->Name, $this->Coupon->Reference);
					}
				}

				if ((count($this->DiscountCollection->Line) > 0) || ($this->DiscountBandingID > 0)) {
					list($tempLineTotal, $discountName) = $this->DiscountCollection->DiscountProduct($this->Line[$i]->Product, $this->Line[$i]->Quantity, $this->DiscountBandingID);

					if ((($this->Line[$i]->Total - $tempLineTotal) > $this->Line[$i]->Discount) && ($tempLineTotal > 0)) {
						$this->Line[$i]->DiscountInformation = $discountName;
						$this->Line[$i]->Discount = $this->Line[$i]->Total - $tempLineTotal;
					}
				}

				if (!empty($this->Line[$i]->Product->PriceOffer) && ($this->Line[$i]->Product->PriceOffer < ($this->Line[$i]->Price - $this->Line[$i]->Discount))) {
					$this->Line[$i]->DiscountInformation = 'Special offer';
					$this->Line[$i]->Discount = $this->Line[$i]->Total - ($this->Line[$i]->Product->PriceOffer * $this->Line[$i]->Quantity);
				}
			}

			// Discount Limit Exceeding Check
			if($this->Line[$i]->Product->DiscountLimit != '' && ($this->Line[$i]->Product->DiscountLimit >= 0 && $this->Line[$i]->Product->DiscountLimit <= 100)){
				$maxDiscount = round((($this->Line[$i]->Price / 100) * $this->Line[$i]->Product->DiscountLimit) * $this->Line[$i]->Quantity, 2);
				if($this->Line[$i]->Discount > $maxDiscount){
					$this->Line[$i]->Discount = $maxDiscount;
					if(strpos($this->Line[$i]->DiscountInformation, 'azxcustom') === false && strpos($this->Line[$i]->DiscountInformation, 'Maximum discount for this product') == false){
						$this->Line[$i]->DiscountInformation .= sprintf(' - Maximum discount for this product is %d%%', $this->Line[$i]->Product->DiscountLimit);
					}
				}
			}

			if(!empty($this->TaxExemptCode)) {
				$this->Line[$i]->Tax = 0;
			} else {
				$this->Line[$i]->Tax = $taxCalculator->GetTax(($this->Line[$i]->Total - $this->Line[$i]->Discount), $this->Line[$i]->Product->TaxClass->ID);
			}
		
			$this->Line[$i]->Total = round($this->Line[$i]->Total, 2);
			$this->Line[$i]->Tax = round($this->Line[$i]->Tax, 2);
			$this->Line[$i]->Discount = round($this->Line[$i]->Discount, 2);
			$this->Line[$i]->Update();

			$this->SubTotal += $this->Line[$i]->Total;
			$this->SubTotalRetail += $this->Line[$i]->PriceRetail * $this->Line[$i]->Quantity;
			$this->TotalDiscount += $this->Line[$i]->Discount;
			$this->TotalTax += $this->Line[$i]->Tax;
			$this->Weight += ($this->Line[$i]->Quantity * $this->Line[$i]->Product->Weight);
		}

		$this->CalculateWeight();
		$this->CalculateShipping();

		$this->Total = $this->TotalTax + $this->TotalShipping + $this->SubTotal - $this->TotalDiscount;
		$this->Update();
	}

	function CalculateShipping() {
		if ($this->IsCustomShipping == 'N') {
			$calc = new ShippingCalculator($this->Shipping->Address->Country->ID, $this->Shipping->Address->Region->ID, $this->SubTotal, $this->Weight, $this->Postage->ID);

			for($i = 0; $i < count($this->Line); $i++) {
				$calc->Add($this->Line[$i]->Quantity, $this->Line[$i]->Product->ShippingClass->ID);
			}

			$calc->GetLimitations();
			QuoteShipping::DeleteQuote($this->ID);

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
			$this->FoundPostage = true;
		}

		if(empty($this->TaxExemptCode)) {
			$shipTax = new TaxCalculator($this->TotalShipping, $this->Shipping->Address->Country->ID, $this->Shipping->Address->Region->ID, $GLOBALS['DEFAULT_TAX_ON_SHIPPING']);
			$this->TotalTax += round($shipTax->Tax, 2);
		}
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

	function AddLine($quantity, $product) {
		$this->Customer->Get();
		$this->Customer->Contact->Get();

		$line = new QuoteLine();
		$line->Product->Get($product);
		$line->Quantity = $quantity;
		$line->QuoteID = $this->ID;
		
		if($this->Customer->Contact->IsTradeAccount == 'Y') {
			$tradeCost = ($line->Product->CacheRecentCost > 0) ? $line->Product->CacheRecentCost : $line->Product->CacheBestCost;
			
			$line->Price = ContactProductTrade::getPrice($this->Customer->Contact->ID, $line->Product->ID);
			$line->Price = ($line->Price <= 0) ? $tradeCost * ((TradeBanding::GetMarkup($tradeCost, $line->Product->ID) / 100) + 1) : $line->Price;
		} else {
			$line->Price = $line->Product->PriceCurrent;
		}
		
		$line->PriceRetail = $line->Product->PriceCurrent;
		$line->Total = $line->Price * $line->Quantity;
		$line->Discount = 0;
		$line->DiscountInformation = '';
		
		$taxCalculator = new GlobalTaxCalculator($this->Shipping->Address->Country->ID, $this->Shipping->Address->Region->ID);

		if(!empty($this->TaxExemptCode)) {
			$line->Tax = 0;
		} else {
			$line->Tax = $taxCalculator->GetTax(($line->Total - $line->Discount), $line->Product->TaxClass->ID);
		}
			
		$line->Tax *= $line->Quantity;

		if($line->Exists()){
			$line->Update();
		} else {
			$line->Add();
		}

		$this->Recalculate();
	}

	function IsUnique($column, $val) {
		$checkQuote = new DataQuery(sprintf("SELECT Quote_ID FROM quote
												WHERE %s = '%s'", mysql_real_escape_string($column), mysql_real_escape_string($val)));
		$checkQuote->Disconnect();
		if ($checkQuote->TotalRows > 0) {
			$this->ID = $checkQuote->Row['Quote_ID'];
			return false;
		} else {
			return true;
		}
	}

	function HasAlerts() {
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("select Quote_Note_ID from quote_note where Quote_ID=%d and Is_Alert='Y'", mysql_real_escape_string($this->ID)));
		$data->Disconnect();
		if ($data->TotalRows > 0) {
			return true;
		} else {
			return false;
		}
	}

	function FollowUp() {
		$data = new DataQuery(sprintf("SELECT Coupon_ID, Coupon_Ref, Discount_Amount FROM coupon WHERE Coupon_Title LIKE 'Introduction Coupon'"));
		if ($data->TotalRows > 0) {

			$this->FollowedUp = 'Y';
			$this->Update();

			if (count($this->Line) <= 0)
				$this->GetLines();
			$this->TotalNet = $this->SubTotal + $this->TotalShipping - $this->TotalDiscount;

			if (empty($this->Customer->Contact->ID))
				$this->Customer->Get();
			if (empty($this->Customer->Contact->Person->ID))
				$this->Customer->Contact->Get();
			$this->Postage->Get();

			$findReplace = new FindReplace();
			$findReplace->Add('/\[DISCOUNT_AMOUNT\]/', $data->Row['Discount_Amount']);
			$findReplace->Add('/\[COUPON\]/', $data->Row['Coupon_Ref']);
			$findReplace->Add('/\[QUOTE_REF\]/', $this->Prefix . $this->ID);
			$findReplace->Add('/\[CUSTOM_REF\]/', $this->CustomID);
			$findReplace->Add('/\[QUOTE_DATE\]/', cDatetime($this->QuotedOn, 'longdate'));
			$findReplace->Add('/\[CUSTOMER_NAME\]/', $this->Customer->Contact->Person->GetFullName());
			$findReplace->Add('/\[CUSTOMER_ID\]/', $this->Customer->Contact->ID);
			$findReplace->Add('/\[BILLTO\]/', $this->GetBillingAddress());
			$findReplace->Add('/\[SHIPTO\]/', $this->GetShippingAddress());
			$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format($this->SubTotal, 2, '.', ','));
			$findReplace->Add('/\[SHIPPING\]/', "&pound;" . number_format($this->TotalShipping, 2, '.', ','));
			$findReplace->Add('/\[DISCOUNT\]/', "-&pound;" . number_format($this->TotalDiscount, 2, '.', ','));
			$findReplace->Add('/\[TAX\]/', "&pound;" . number_format($this->TotalTax, 2, '.', ','));
			$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format($this->Total, 2, '.', ','));
			$findReplace->Add('/\[QUOTE_WEIGHT\]/', $this->Weight);
			$findReplace->Add('/\[DELIVERY\]/', $this->Postage->Name);
			$findReplace->Add('/\[QUOTE_LINES\]/', $this->LinesHtml);
			$findReplace->Add('/\[NET\]/', "&pound;" . number_format($this->TotalNet, 2, '.', ','));

			$quoteEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_couponQuote.tpl");
			$quoteHtml = "";
			
			for ($i = 0; $i < count($quoteEmail); $i++) {
				$quoteHtml .= $findReplace->Execute($quoteEmail[$i]);
			}

			$findReplace = new FindReplace();
			$findReplace->Add('/\[BODY\]/', $quoteHtml);
			$findReplace->Add('/\[NAME\]/', $this->Customer->Contact->Person->GetFullName());

			$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
			$emailBody = "";
			for ($i = 0; $i < count($stdTmplate); $i++) {
				$emailBody .= $findReplace->Execute($stdTmplate[$i]);
			}

			$fromAddress = '';

			if($this->CreatedBy > 0) {
				$user = new User($this->CreatedBy);
				
				$fromAddress = !empty($user->SecondaryMailbox) ? $user->SecondaryMailbox : $user->Username;
			}

			$queue = new EmailQueue();
			$queue->GetModuleID('quotes');
			$queue->Subject = sprintf("Discount on your Light Bulb Quote from %s", $GLOBALS['COMPANY']);
			$queue->Body = $emailBody;
			$queue->ToAddress = $this->Customer->GetEmail();
			$queue->FromAddress = !empty($fromAddress) ? $fromAddress : $queue->FromAddress;
			$queue->ReturnPath = $returnPath;
			$queue->Priority = 'H';
			$queue->Add();

			$contact = new CouponContact();
			$contact->Coupon->ID = $data->Row['Coupon_ID'];
			$contact->EmailAddress = $this->Customer->GetEmail();
			$contact->Add();
		}
		$data->Disconnect();
	}

	private function AddShipping($weight, $quantity, $charge) {
		$shipping = new QuoteShipping();
		$shipping->QuoteID = $this->ID;
		$shipping->Weight = $weight;
		$shipping->Quantity = $quantity;
		$shipping->Charge = $charge;
		$shipping->Add();
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
}