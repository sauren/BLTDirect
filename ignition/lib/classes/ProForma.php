<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ContactProductTrade.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailQueue.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Person.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Postage.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProFormaLine.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProFormaShipping.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Order.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/OrderShipping.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/FindReplace.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/htmlMimeMail5.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Coupon.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CustomerContact.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DiscountCollection.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ShippingCalculator.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/TaxCalculator.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Geozone.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/GlobalTaxCalculator.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Payment.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Quote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/TradeBanding.php");

class ProForma {
	var $ID;
	var $Quote;
	var $Line;
	var $CustomID;
	var $Prefix;
	var $Customer;
	var $Coupon;
	var $BillingOrg;
	var $Billing;
	var $ShippingOrg;
	var $Shipping;
	var $FormedOn;
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
	public $ShippingLine;
	public $ShippingMultiplier;

	function __construct($id=NULL){
		$this->Quote = new Quote();
		$this->Prefix = "W";
		$this->IsCustomShipping = 'N';
		$this->Line = array();
		$this->Customer = new Customer;
		$this->Coupon = new Coupon;
		$this->Coupon->ID = 0;
		$this->TotalDiscount = 0;
		$this->TotalNet;
		$this->Billing = new Person;
		$this->Shipping = new Person;
		$this->Postage = new Postage;
		$this->LinesHtml = "";
		$this->EmailedOn = '0000-00-00 00:00:00';
		$this->FormedOn = '0000-00-00 00:00:00';
		$this->DiscountCollection = new DiscountCollection;
		$this->Transaction = array();
 		$this->ShippingLine = array();

		if(!is_null($id)){
			$this->ID=$id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) $this->ID = $id;
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
										afs.Address_Summary AS Shipping_Address_Summary
										from proforma AS q
										LEFT JOIN regions AS rb
										ON q.Billing_Region_ID=rb.Region_ID
										LEFT JOIN countries AS cb
										ON q.Billing_Country_ID=cb.Country_ID
										LEFT JOIN regions AS rs
										ON q.Shipping_Region_ID=rs.Region_ID
										LEFT JOIN countries AS cs
										ON q.Shipping_Country_ID=cs.Country_ID
										LEFT JOIN address_format AS afb
										ON cb.Address_Format_ID=afb.Address_Format_ID
										LEFT JOIN address_format AS afs
										ON cs.Address_Format_ID=afs.Address_Format_ID
										WHERE ProForma_ID=%d", mysql_real_escape_string($this->ID)));
										
		$this->Quote->ID = $data->Row['Quote_ID'];
		$this->Prefix = $data->Row['ProForma_Prefix'];
		$this->Customer->ID = $data->Row['Customer_ID'];
		$this->Coupon->ID = $data->Row['Coupon_ID'];
		$this->CustomID = $data->Row['Custom_Order_No'];
		$this->FormedOn = $data->Row['Formed_On'];
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
		$this->Referrer = $data->Row['Referrer'];
		$this->TaxExemptCode = $data->Row['TaxExemptCode'];
		$this->CreatedOn = $data->Row['Created_On'];
		$this->CreatedBy = $data->Row['Created_By'];
		$this->ModifiedOn = $data->Row['Modified_On'];
		$this->ModifiedBy = $data->Row['Modified_By'];
		$this->Weight = $data->Row['Weight'];
		$this->Postage->Get($data->Row['Postage_ID']);
		$data->Disconnect();

		$this->TotalNet = $this->SubTotal + $this->TotalShipping - $this->TotalDiscount;
	}

	function Add(){
		$sql = sprintf("INSERT INTO proforma (ProForma_Prefix, Quote_ID, Customer_ID,
				Coupon_ID, Custom_Order_No, Formed_On, Emailed_On, Emailed_To,
				Status, Total_Lines, Sub_Total, Sub_Total_Retail, Total_Shipping,
				Is_Custom_Shipping, Total_Discount, Total_Tax, Total, Billing_Title,
				Billing_First_Name, Billing_Initial, Billing_Last_Name,
				Billing_Organisation_Name, Billing_Address_1, Billing_Address_2,
				Billing_Address_3, Billing_City, Billing_Country_ID,
				Billing_Region_ID, Billing_Zip, Shipping_Title,
				Shipping_First_Name, Shipping_Initial, Shipping_Last_Name,
				Shipping_Organisation_Name, Shipping_Address_1,
				Shipping_Address_2, Shipping_Address_3, Shipping_City,
				Shipping_Country_ID, Shipping_Region_ID, Shipping_Zip, Referrer,
				Weight, Postage_ID, TaxExemptCode, Created_On,
				Created_By, Modified_On, Modified_By)
				VALUES (
					'%s', %d, %d, %d, '%s', '%s', '%s', '%s',
					'%s', %d, %f, %f, %f, '%s', %f, %f, %f,
					'%s', '%s', '%s', '%s', '%s', '%s',
					'%s', '%s', '%s', %d, %d, '%s',
					'%s', '%s', '%s', '%s', '%s',
					'%s', '%s', '%s', '%s', %d, %d,
					'%s', '%s', '%s', %d, '%s', Now(), %d, Now(), %d)",
		mysql_real_escape_string($this->Prefix), 
		mysql_real_escape_string($this->Quote->ID), 
		mysql_real_escape_string($this->Customer->ID), 
		mysql_real_escape_string($this->Coupon->ID),
		mysql_real_escape_string($this->CustomID), 
		mysql_real_escape_string($this->FormedOn), 
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
		mysql_real_escape_string($this->Billing->Title),
		mysql_real_escape_string($this->Billing->Name),
		mysql_real_escape_string($this->Billing->Initial),
		mysql_real_escape_string($this->Billing->LastName),
		mysql_real_escape_string($this->BillingOrg),
		mysql_real_escape_string($this->Billing->Address->Line1),
		mysql_real_escape_string($this->Billing->Address->Line2),
		mysql_real_escape_string($this->Billing->Address->Line3),
		mysql_real_escape_string($this->Billing->Address->City),
		mysql_real_escape_string($this->Billing->Address->Country->ID),
		mysql_real_escape_string($this->Billing->Address->Region->ID),
		mysql_real_escape_string($this->Billing->Address->Zip),
		mysql_real_escape_string($this->Shipping->Title),
		mysql_real_escape_string($this->Shipping->Name),
		mysql_real_escape_string($this->Shipping->Initial),
		mysql_real_escape_string($this->Shipping->LastName),
		mysql_real_escape_string($this->ShippingOrg),
		mysql_real_escape_string($this->Shipping->Address->Line1),
		mysql_real_escape_string($this->Shipping->Address->Line2),
		mysql_real_escape_string($this->Shipping->Address->Line3),
		mysql_real_escape_string($this->Shipping->Address->City),
		mysql_real_escape_string($this->Shipping->Address->Country->ID),
		mysql_real_escape_string($this->Shipping->Address->Region->ID),
		mysql_real_escape_string($this->Shipping->Address->Zip),
		mysql_real_escape_string($this->Referrer),
		mysql_real_escape_string($this->Weight), 
		mysql_real_escape_string($this->Postage->ID),
		mysql_real_escape_string($this->TaxExemptCode),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), 
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));

		$data = new DataQuery($sql);
		$this->ID = $data->InsertID;
	}

	function Delete($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		$this->Get();
		$this->DeleteLines();
		if(!is_numeric($this->ID)){
			return false;
		}
		$sql = "delete from proforma where ProForma_ID=" . mysql_real_escape_string($this->ID);
		$data = new DataQuery($sql);
	}

	function DeleteLines($id=NULL) {
		if(!is_null($id)) $this->ID = $id;
		$this->GetLines();
		for($i=0; $i < count($this->Line); $i++){
			$this->Line[$i]->Delete();
		}
	}

	function Cancel(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery("UPDATE proforma
                                   SET Status='cancelled'
                                   WHERE ProForma_ID={$this->ID}");
	}

	function Convert(){
        $this->Customer->Get();
		$this->Customer->Contact->Get();

		$order = new Order();
		$order->ProformaID = $this->ID;
		$order->Prefix = $this->Prefix;
		$order->Customer->ID = $this->Customer->ID;
		$order->Coupon->ID = $this->Coupon->ID;
		$order->CustomID = $this->CustomID;
		$order->OrderedOn = $this->FormedOn;
		$order->EmailedOn = $this->EmailedOn;
		$order->EmailedTo = $this->EmailedTo;
		$order->Status = 'Unread';
		$order->TotalLines = $this->TotalLines;
		$order->SubTotal = $this->SubTotal;
		$order->SubTotalRetail = $this->SubTotalRetail;
		$order->TotalShipping = $this->TotalShipping;
		$order->IsCustomShipping = $this->IsCustomShipping;
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

		foreach($this->Line as $l){
			$ol = new OrderLine();
			$ol->Order = $order->ID;
			$ol->Product = $l->Product;
			$ol->Quantity = $l->Quantity;
			$ol->Price = $l->Price;
			$ol->PriceRetail = $l->PriceRetail;
			$ol->HandlingCharge = $l->HandlingCharge;
			$ol->IncludeDownloads = $l->IncludeDownloads;
			$ol->Discount = $l->Discount;
			$ol->DiscountInformation = $l->DiscountInformation;
			$ol->Tax = $l->Tax;
			$ol->Total = $l->Total;
			$ol->Status = $l->Status;
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

		$this->Status = 'Converted';
		$this->Update();

		return $order->ID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$sql = sprintf("UPDATE proforma SET
							ProForma_Prefix='%s', Quote_ID=%d, Customer_ID=%d, Coupon_ID=%d,
							Custom_Order_No='%s', Formed_On='%s',
							Emailed_On='%s', Emailed_To='%s',
							Status='%s', Total_Lines=%d, Sub_Total=%f, Sub_Total_Retail=%f,
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
							Shipping_Zip='%s', Referrer='%s',
							Weight=%f, Postage_ID=%d, TaxExemptCode='%s',
							Modified_On=Now(), Modified_By=%d
							WHERE ProForma_ID=%d",
		mysql_real_escape_string($this->Prefix), 
		mysql_real_escape_string($this->Quote->ID), 
		mysql_real_escape_string($this->Customer->ID),
		mysql_real_escape_string($this->Coupon->ID), 
		mysql_real_escape_string($this->CustomID), 
		mysql_real_escape_string($this->FormedOn),
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
		mysql_real_escape_string($this->Billing->Title),
		mysql_real_escape_string($this->Billing->Name),
		mysql_real_escape_string($this->Billing->Initial),
		mysql_real_escape_string($this->Billing->LastName),
		mysql_real_escape_string($this->BillingOrg),
		mysql_real_escape_string($this->Billing->Address->Line1),
		mysql_real_escape_string($this->Billing->Address->Line2),
		mysql_real_escape_string($this->Billing->Address->Line3),
		mysql_real_escape_string($this->Billing->Address->City),
		mysql_real_escape_string($this->Billing->Address->Country->ID),
		mysql_real_escape_string($this->Billing->Address->Region->ID),
		mysql_real_escape_string($this->Billing->Address->Zip),
		mysql_real_escape_string($this->Shipping->Title),
		mysql_real_escape_string($this->Shipping->Name),
		mysql_real_escape_string($this->Shipping->Initial),
		mysql_real_escape_string($this->Shipping->LastName),
		mysql_real_escape_string($this->ShippingOrg),
		mysql_real_escape_string($this->Shipping->Address->Line1),
		mysql_real_escape_string($this->Shipping->Address->Line2),
		mysql_real_escape_string($this->Shipping->Address->Line3),
		mysql_real_escape_string($this->Shipping->Address->City),
		mysql_real_escape_string($this->Shipping->Address->Country->ID),
		mysql_real_escape_string($this->Shipping->Address->Region->ID),
		mysql_real_escape_string($this->Shipping->Address->Zip),
		mysql_real_escape_string($this->Referrer),
		mysql_real_escape_string($this->Weight), 
		mysql_real_escape_string($this->Postage->ID),
		mysql_real_escape_string($this->TaxExemptCode),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), 
		mysql_real_escape_string($this->ID));

		$data = new DataQuery($sql);
	}

	function GetLines(){
		$this->Line = array();
		$this->LinesHtml = "";
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT * FROM proforma_line
											WHERE ProForma_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row){
			$i = count($this->Line);
			$this->Line[$i] = new ProFormaLine();
			$this->Line[$i]->ID = $data->Row['ProForma_Line_ID'];
			$this->Line[$i]->ProFormaID = $data->Row['ProForma_ID'];
			$this->Line[$i]->Product->ID = $data->Row['Product_ID'];
			$this->Line[$i]->Product->Name = strip_tags($data->Row['Product_Title']);
			$this->Line[$i]->Quantity = $data->Row['Quantity'];
			$this->Line[$i]->Status = $data->Row['Line_Status'];
			$this->Line[$i]->Price = $data->Row['Price'];
			$this->Line[$i]->PriceRetail = $data->Row['Price_Retail'];
			$this->Line[$i]->HandlingCharge = $data->Row['Handling_Charge'];
			$this->Line[$i]->IncludeDownloads = $data->Row['IncludeDownloads'];
			$this->Line[$i]->Total = $data->Row['Line_Total'];
			$this->Line[$i]->Discount = $data->Row['Line_Discount'];
			$this->Line[$i]->DiscountInformation = $data->Row['Discount_Information'];
			$this->Line[$i]->Tax = $data->Row['Line_Tax'];

			$this->LinesHtml .= "<tr>";
			$this->LinesHtml .= sprintf("<td>%sx</td>
								<td>%s</td>
								<td>%s</td>
								<td align=\"right\">&pound;%s</td>
								<td align=\"right\">&pound;%s</td>
								<td align=\"right\">&pound;%s</td>",
			$this->Line[$i]->Quantity,
			$this->Line[$i]->Product->Name,
			$this->Line[$i]->Product->ID,
			number_format($this->Line[$i]->Price, 2, '.', ','),
			number_format($this->Line[$i]->Price - ($this->Line[$i]->Discount / $this->Line[$i]->Quantity), 2, '.', ','),
			number_format($this->Line[$i]->Total - $this->Line[$i]->Discount, 2, '.', ','));

			$this->LinesHtml .= "</tr>";
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

		$data = new DataQuery(sprintf("SELECT ProFormaShippingID FROM proforma_shipping WHERE ProFormaID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->ShippingLine[] = new ProFormaShipping($data->Row['ProFormaShippingID']);

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

		$this->TotalNet = $this->SubTotal + $this->TotalShipping - $this->TotalDiscount;

		if(empty($this->Customer->Contact->ID))$this->Customer->Get();
			$this->Customer->Get();
		if(empty($this->Customer->Contact->Person->ID))
		$this->Customer->Contact->Get();
		$this->Postage->Get();

		$findReplace = new FindReplace();
		$findReplace->Add('/\[PROFORMA_REF\]/', $this->Prefix . $this->ID);
		$findReplace->Add('/\[CUSTOM_REF\]/', $this->CustomID);
		$findReplace->Add('/\[PROFORMA_DATE\]/', cDatetime($this->FormedOn, 'longdate'));
		$findReplace->Add('/\[CUSTOMER_NAME\]/', $this->Customer->Contact->Person->GetFullName());
		$findReplace->Add('/\[CUSTOMER_ID\]/', $this->Customer->Contact->ID);
		$findReplace->Add('/\[BILLTO\]/', $this->GetBillingAddress());
		$findReplace->Add('/\[SHIPTO\]/', $this->GetShippingAddress());
		$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format($this->SubTotal - $this->TotalDiscount, 2, '.',','));
		$findReplace->Add('/\[SHIPPING\]/', "&pound;" . number_format($this->TotalShipping, 2, '.',','));
		$findReplace->Add('/\[DISCOUNT\]/', "-&pound;" . number_format($this->TotalDiscount, 2, '.',','));
		$findReplace->Add('/\[TAX\]/', "&pound;" . number_format($this->TotalTax, 2, '.',','));
		$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format($this->Total, 2, '.',','));
		$findReplace->Add('/\[PROFORMA_WEIGHT\]/', $this->Weight);
		$findReplace->Add('/\[DELIVERY\]/', $this->Postage->Name);
		$findReplace->Add('/\[PROFORMA_LINES\]/', $this->LinesHtml);
		$findReplace->Add('/\[NET\]/', "&pound;" . number_format($this->TotalNet, 2, '.',','));

		$handling = '';
		$handlingApplied = false;
		
		foreach($this->Line as $line) {
			if($line->HandlingCharge > 0) {
				$handlingApplied = true;
				break;
			}
		}
		
		if($handlingApplied) {
			$handling .= '<p>The following products within this pro forma are subject to a restocking charge if they are returned to us:</p>';
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

		$proFormaEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_proforma.tpl");
		$proFormaHtml = "";
		for($i=0; $i < count($proFormaEmail); $i++){
			$proFormaHtml .= $findReplace->Execute($proFormaEmail[$i]);
		}

		$findReplace = new FindReplace;
		$findReplace->Add('/\[BODY\]/', $proFormaHtml);
		$findReplace->Add('/\[NAME\]/', $this->Customer->Contact->Person->GetFullName());

		$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
		$emailBody = "";
		for($i=0; $i < count($stdTmplate); $i++){
			$emailBody .= $findReplace->Execute($stdTmplate[$i]);
		}

		$queue = new EmailQueue();
		$queue->GetModuleID('proformas');
		$queue->Subject = sprintf("%s ProForma Confirmation [#%s%s]", $GLOBALS['COMPANY'], $this->Prefix, $this->ID);
		$queue->Body = $emailBody;
		$queue->ToAddress = $this->Customer->GetEmail();
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
		$proFormaUpdate = new DataQuery(sprintf("UPDATE proforma
                                                  SET Emailed_On=Now(),
                                                  Emailed_To='%s'
                                                  WHERE ProForma_ID=%d",
		mysql_real_escape_string($this->Customer->GetEmail()),
		mysql_real_escape_string($this->ID)));
	}

	function GetLinesHtml(){
		if(count($this->Line) == 0){
			$this->GetLines();
		}
		return $this->LinesHtml;
	}

	function GetBillingAddress(){
		$address = $this->Billing->GetFullName();
		$address .= "<br />";
		if(!empty($this->BillingOrg)){
			$address .= $this->BillingOrg . "<br />";
		}
		$address .= $this->Billing->Address->GetFormatted('<br />');
		return $address;
	}

	function GetShippingAddress(){
		$address = $this->Shipping->GetFullName();
		$address .= "<br />";
		if(!empty($this->ShippingOrg)){
			$address .= $this->ShippingOrg . "<br />";
		}
		$address .= $this->Shipping->Address->GetFormatted('<br />');
		return $address;
	}

	function GenerateFromCart(&$cart){
		$cart->Customer->Get();
		$cart->Customer->Contact->Get();

		$cart->DiscountCollection->Get();

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
		
		if($cart->Customer->Contact->HasParent){
			$this->BillingOrg = $cart->Customer->Contact->Parent->Organisation->Name;
		}

		$this->FormedOn = getDatetime();
		$this->Status = "Pending";
		$this->Customer = $cart->Customer;
		$this->Weight = $cart->Weight;
		$this->Postage->ID = $cart->Postage;
		$this->TaxExemptCode = $cart->TaxExemptCode;

		if(strtolower($cart->ShipTo) == 'billing'){
			$this->Shipping = $cart->Customer->Contact->Person;
			if($cart->Customer->Contact->HasParent){
				$this->ShippingOrg = $cart->Customer->Contact->Parent->Organisation->Name;
			}
		} else {
			$cc = new CustomerContact($cart->ShipTo);
			
			$this->Shipping = $cc;
			$this->ShippingOrg = $cc->OrgName;
		}

		if(empty($cart->ProFormaID)) {
			$this->Add();
		} else {
			$this->Update();
		}

		$cart->GetShippingLines();

		for($i=0; $i<count($cart->ShippingLine); $i++) {
			$shipping = new ProformaShipping();
			$shipping->ProFormaID = $this->ID;
			$shipping->Weight = $cart->ShippingLine[$i]->Weight;
			$shipping->Quantity = $cart->ShippingLine[$i]->Quantity;
			$shipping->Charge = $cart->ShippingLine[$i]->Charge;
			$shipping->Add();
		}

		$this->DeleteLines();

		for($i=0; $i < count($cart->Line); $i++){
			$line = new ProFormaLine;
			$line->ProFormaID = $this->ID;
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
		$cart->Customer->Contact->IsProformaAccount = 'Y';
		$cart->Customer->Contact->Update();

        $cart->Customer->IsCreditActive = 'N';
		$cart->Customer->Update();
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

			$customDiscount = false;

			if(!empty($this->Line[$i]->DiscountInformation)) {
				$discountCustom = explode(':', $this->Line[$i]->DiscountInformation);

				if(trim($discountCustom[0]) == 'azxcustom') {
					$customDiscount = true;
				}
			}

			if($this->Line[$i]->FreeOfCharge == 'Y') {
				$this->Line[$i]->Total = 0;
			} else {
				$this->Line[$i]->Total = $this->Line[$i]->Quantity * $this->Line[$i]->Price;
			}

			if($customDiscount) {
				$this->Line[$i]->Discount = round($this->Line[$i]->Price * ($discountCustom[1] / 100), 2) * $this->Line[$i]->Quantity;
			} else {
				$this->Line[$i]->Discount = 0;
				$this->Line[$i]->DiscountInformation = '';

				if(!empty($this->Coupon->ID)){
					$couponLineTotal = $this->Coupon->DiscountProduct($this->Line[$i]->Product, $this->Line[$i]->Quantity);

					if($couponLineTotal < $this->Line[$i]->Total){
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

	function CalculateShipping(){
		if($this->IsCustomShipping == 'N'){
			$calc = new ShippingCalculator($this->Shipping->Address->Country->ID, $this->Shipping->Address->Region->ID, $this->SubTotal, $this->Weight, $this->Postage->ID);

			for($i=0; $i < count($this->Line); $i++){
				$calc->Add($this->Line[$i]->Quantity, $this->Line[$i]->Product->ShippingClass->ID);
			}

			$calc->GetLimitations();

			new DataQuery(sprintf("DELETE FROM proforma_shipping WHERE ProFormaID=%d", mysql_real_escape_string($this->ID)));

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
			while($data->Row){
				$checked = ($data->Row['Postage_ID'] == $this->Postage->ID)?'selected="selected"':'';
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
		
		$line = new ProFormaLine();
		$line->Product->Get($product);
		$line->Quantity = $quantity;
		$line->ProFormaID = $this->ID;
		
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

	function IsUnique($column, $val){
		$checkProForma = new DataQuery(sprintf("SELECT ProForma_ID FROM proforma
												WHERE %s = '%s'",
		mysql_real_escape_string($column), mysql_real_escape_string($val)));
		$checkProForma->Disconnect();
		if($checkProForma->TotalRows > 0){
			$this->ID = $checkProForma->Row['ProForma_ID'];
			return false;
		} else {
			return true;
		}
	}

	private function AddShipping($weight, $quantity, $charge) {
		$shipping = new ProFormaShipping();
		$shipping->ProFormaID = $this->ID;
		$shipping->Weight = $weight;
		$shipping->Quantity = $quantity;
		$shipping->Charge = $charge;
		$shipping->Add();
	}
}