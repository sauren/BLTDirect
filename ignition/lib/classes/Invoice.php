<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Person.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Payment.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/PaymentMethod.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/InvoiceLine.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/FindReplace.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/htmlMimeMail5.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Order.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardPDF.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Template.php");

class Invoice {
	var $ID;
	var $IntegrationID;
	var $IntegrationReference;
	var $IsCorrection;
	var $TaxRate;
	var $Order;
	var $Customer;
	var $SubTotal;
	var $Shipping;
	var $Discount;
	var $Tax;
	var $Net;
	var $Total;
	var $DueOn;
	var $PaymentMethod;
	var $IsPaid;
	var $Payment;
	var $IsDespatched;
	var $Despatch;
	var $Person;
	var $Organisation;
	var $NominalCode;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $Line;
	var $LinesHtml;
	var $CustomHtml;
	var $ShowCustom;

	function Invoice($id = null, $connection = null) {
		$this->IsCorrection = 'N';
		$this->ShowCustom = true;
		$this->Person = new Person();
		$this->NominalCode = $GLOBALS['SAGE_DEFAULT_NOMINAL_CODE'];
		$this->Order = new Order();
		$this->Customer = new Customer();
		$this->Line = array();
		$this->Tax = 0;
		$this->Net = 0;
		$this->DueOn = '0000-00-00 00:00:00';
		$this->PaymentMethod = new PaymentMethod();
		$this->IsPaid = 'N';
		$this->IsDespatched = 'Y';
		$this->SubTotal = 0;
		$this->Shipping = 0;
		$this->Discount = 0;
		$this->Total = 0;

		if(!is_null($id)) {
			$this->Get($id, $connection);
		}
	}

	function Get($id = null, $connection = null) {
		if (!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("select * from invoice where Invoice_ID=%d", mysql_real_escape_string($this->ID)), $connection);
		if($data->TotalRows > 0) {
			$this->IntegrationID = $data->Row['Integration_ID'];
			$this->IntegrationReference = $data->Row['Integration_Reference'];
			$this->IsCorrection = $data->Row['Is_Correction'];
			$this->TaxRate = $data->Row['Tax_Rate'];
			$this->Order->ID = $data->Row['Order_ID'];
			$this->Customer->ID = $data->Row['Customer_ID'];
			$this->SubTotal = $data->Row['Invoice_Net'];
			$this->Shipping = $data->Row['Invoice_Shipping'];
			$this->Discount = $data->Row['Invoice_Discount'];
			$this->Tax = $data->Row['Invoice_Tax'];
			$this->Total = $data->Row['Invoice_Total'];
			$this->PaymentMethod->ID = $data->Row['Payment_Method_ID'];
			$this->DueOn = $data->Row['Invoice_Due_On'];
			$this->IsPaid = $data->Row['Is_Paid'];
			$this->Payment = $data->Row['Payment_ID'];
			$this->IsDespatched = $data->Row['Is_Despatched'];
			$this->Despatch = $data->Row['Despatch_ID'];
			$this->Person->Title = $data->Row['Invoice_Title'];
			$this->Person->Name = $data->Row['Invoice_First_Name'];
			$this->Person->Initial = $data->Row['Invoice_Initial'];
			$this->Person->LastName = $data->Row['Invoice_Last_Name'];
			$this->Organisation = $data->Row['Invoice_Organisation'];
			$this->Person->Address->Line1 = $data->Row['Invoice_Address_1'];
			$this->Person->Address->Line2 = $data->Row['Invoice_Address_2'];
			$this->Person->Address->Line3 = $data->Row['Invoice_Address_3'];
			$this->Person->Address->City = $data->Row['Invoice_City'];
			$this->Person->Address->Region->Name = $data->Row['Invoice_Region'];
			$this->Person->Address->Country->Name = $data->Row['Invoice_Country'];
			$this->Person->Address->Zip = $data->Row['Invoice_Zip'];
			$this->NominalCode = $data->Row['Nominal_Code'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$data->Disconnect();

			// Some Backward Compatible Corrections
			if (!isDatetime($this->DueOn)) {
				$this->DueOn = $this->CreatedOn;
			}

			$this->Net = $this->SubTotal + $this->Shipping - $this->Discount;

            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function GetViaOrderID($id = NULL) {
		if (!is_null($id)) {
			$this->Order->ID = $id;
		}

		$data = new DataQuery(sprintf("SELECT Invoice_ID FROM invoice WHERE Order_ID=%d", mysql_real_escape_string($this->Order->ID)));
		if($data->TotalRows > 0) {
			$return = $this->Get($data->Row['Invoice_ID']);

        	$data->Disconnect();
			return $return;
		}

		$data->Disconnect();
		return false;
	}

	function GetCustom() {
		$this->CustomHtml = '';

		if ($this->ShowCustom) {
			if (!empty($this->Order->FreeText) || ($this->Order->FreeTextValue > 0)) {
				$this->CustomHtml .= '<tr>';
				$this->CustomHtml .= '<td>Other:</td>';
				$this->CustomHtml .= '<td colspan="4">' . $this->Order->FreeText . '&nbsp;</td>';
				$this->CustomHtml .= '<td align="right">&pound;' . number_format($this->Order->FreeTextValue, 2, '.', ',') . '</td>';
				$this->CustomHtml .= '</tr>';
			}
		}
	}

	function GetLines($connection = null) {
		$this->Line = array();
		$this->LinesHtml = "";

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT i.*, p.SKU FROM invoice_line AS i LEFT JOIN product AS p ON i.Product_ID = p.Product_ID WHERE Invoice_ID=%d", mysql_real_escape_string($this->ID)), $connection);
		while ($data->Row) {
			$line = new InvoiceLine();
			$line->ID = $data->Row['Invoice_Line_ID'];
			$line->InvoiceID = $data->Row['Invoice_ID'];
			$line->Description = $data->Row['Description'];
			$line->Quantity = $data->Row['Quantity'];
			$line->Product->ID = $data->Row['Product_ID'];
			$line->Product->SKU = $data->Row['SKU'];
			$line->Price = $data->Row['Price'];
			$line->Total = $data->Row['Line_Total'];
			$line->Discount = $data->Row['Line_Discount'];
			$line->DiscountInformation = $data->Row['Discount_Information'];
			$line->Tax = $data->Row['Line_Tax'];
			$line->CreatedOn = $data->Row['Created_On'];
			$line->CreatedBy = $data->Row['Created_By'];
			$line->ModifiedOn = $data->Row['Modified_On'];
			$line->ModifiedBy = $data->Row['Modified_By'];

			$this->LinesHtml .= sprintf("<tr><td>%sx</td><td>%s</td><td>%s</td><td align=\"right\">&pound;%s</td><td align=\"right\">&pound;%s</td><td align=\"right\">&pound;%s</td></tr>", $line->Quantity, $line->Description, $line->Product->PublicID(), number_format($line->Price, 2, '.', ','), number_format($line->Price - ($line->Discount / $line->Quantity), 2, '.', ','), number_format($line->Total - $line->Discount, 2, '.', ','));

			$this->Line[] = $line;

			$data->Next();
		}
		$data->Disconnect();
	}

	function GetLinesViaOrderID() {
		$this->Line = array();
		$this->LinesHtml = "";


		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM invoice_line AS il INNER JOIN invoice AS i ON il.Invoice_ID = i.Invoice_ID WHERE i.Order_ID=%d", mysql_real_escape_string($this->Order->ID)));

		while ($data->Row) {
			$line = new InvoiceLine();
			$line->ID = $data->Row['Invoice_Line_ID'];
			$line->InvoiceID = $data->Row['Invoice_ID'];
			$line->Description = $data->Row['Description'];
			$line->Quantity = $data->Row['Quantity'];
			$line->Product->ID = $data->Row['Product_ID'];
			$line->Price = $data->Row['Price'];
			$line->Total = $data->Row['Line_Total'];
			$line->Discount = $data->Row['Line_Discount'];
			$line->DiscountInformation = $data->Row['Discount_Information'];
			$line->Tax = $data->Row['Line_Tax'];
			$line->CreatedOn = $data->Row['Created_On'];
			$line->CreatedBy = $data->Row['Created_By'];
			$line->ModifiedOn = $data->Row['Modified_On'];
			$line->ModifiedBy = $data->Row['Modified_By'];

			$this->LinesHtml .= "<tr>";
			$this->LinesHtml .= sprintf("<td>%sx</td>
								<td>%s</td>
								<td>%s</td>
								<td align=\"right\">&pound;%s</td>
								<td align=\"right\">&pound;%s</td>", $line->Quantity, $line->Description, $line->Product->ID, number_format($line->Price, 2, '.', ','), number_format($line->Total, 2, '.', ','));
			$this->LinesHtml .= "</tr>";
			$this->Line[] = $line;

			$data->Next();
		}
		$data->Disconnect();
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO invoice
											(Integration_ID, Integration_Reference, Is_Correction, Tax_Rate, Order_ID, Customer_ID, Invoice_Net,
											Invoice_Shipping, Invoice_Discount,
											Invoice_Tax, Invoice_Total,
											Invoice_Due_On, Payment_Method_ID, Is_Paid, Payment_ID,
											Is_Despatched, Despatch_ID,
											Invoice_Title, Invoice_First_Name,
											Invoice_Initial, Invoice_Last_Name,
											Invoice_Organisation,
											Invoice_Address_1, Invoice_Address_2,
											Invoice_Address_3, Invoice_City,
											Invoice_Region, Invoice_Country,
											Invoice_Zip, Nominal_Code, Created_On, Created_By,
											Modified_On, Modified_By)
											VALUES ('%s', '%s', '%s', %f, %d, %d, %f, %f, %f, %f, %f,
											'%s', %d, '%s', %d, '%s', %d, '%s',
											'%s', '%s', '%s', '%s', '%s', '%s',
											'%s', '%s', '%s', '%s', '%s', %d, Now(),
											%d, Now(), %d)", mysql_real_escape_string($this->IntegrationID), mysql_real_escape_string($this->IntegrationReference), mysql_real_escape_string($this->IsCorrection), mysql_real_escape_string($this->TaxRate), mysql_real_escape_string($this->Order->ID), mysql_real_escape_string($this->Customer->ID), mysql_real_escape_string($this->SubTotal), mysql_real_escape_string($this->Shipping), mysql_real_escape_string($this->Discount), mysql_real_escape_string($this->Tax), mysql_real_escape_string($this->Total), mysql_real_escape_string($this->DueOn), mysql_real_escape_string($this->PaymentMethod->ID), mysql_real_escape_string($this->IsPaid), mysql_real_escape_string($this->Payment), mysql_real_escape_string($this->IsDespatched), mysql_real_escape_string($this->Despatch), mysql_real_escape_string(stripslashes($this->Person->Title)), mysql_real_escape_string(stripslashes($this->Person->Name)), mysql_real_escape_string($this->Person->Initial), mysql_real_escape_string(stripslashes($this->Person->LastName)), mysql_real_escape_string(stripslashes($this->Organisation)), mysql_real_escape_string(stripslashes($this->Person->Address->Line1)), mysql_real_escape_string(stripslashes($this->Person->Address->Line2)), mysql_real_escape_string(stripslashes($this->Person->Address->Line3)), mysql_real_escape_string(stripslashes($this->Person->Address->City)), mysql_real_escape_string(stripslashes($this->Person->Address->Region->Name)), mysql_real_escape_string(stripslashes($this->Person->Address->Country->Name)), mysql_real_escape_string(stripslashes($this->Person->Address->Zip)), mysql_real_escape_string($this->NominalCode), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;

		return true;
	}

	function Update() {

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE invoice SET Integration_ID='%s', Integration_Reference='%s', Is_Correction='%s', Tax_Rate=%f, Order_ID=%d, Customer_ID=%d, Invoice_Net='%f', Invoice_Shipping='%f', Invoice_Discount=%f, Invoice_Tax='%f', Invoice_Total='%f', Invoice_Due_On='%s', Payment_Method_ID=%d, Is_Paid='%s', Payment_ID=%d, Is_Despatched='%s', Despatch_ID=%d, Invoice_Title='%s', Invoice_First_Name='%s', Invoice_Initial='%s', Invoice_Last_Name='%s', Invoice_Organisation='%s', Invoice_Address_1='%s', Invoice_Address_2='%s', Invoice_Address_3='%s', Invoice_City='%s', Invoice_Region='%s', Invoice_Country='%s', Invoice_Zip='%s', Nominal_Code=%d, Modified_On=Now(), Modified_By=%d where Invoice_ID=%d", mysql_real_escape_string($this->IntegrationID), mysql_real_escape_string($this->IntegrationReference), mysql_real_escape_string($this->IsCorrection), mysql_real_escape_string($this->TaxRate), mysql_real_escape_string($this->Order->ID), mysql_real_escape_string($this->Customer->ID), mysql_real_escape_string($this->SubTotal), mysql_real_escape_string($this->Shipping), mysql_real_escape_string($this->Discount), mysql_real_escape_string($this->Tax), mysql_real_escape_string($this->Total), mysql_real_escape_string($this->DueOn), mysql_real_escape_string($this->PaymentMethod->ID), mysql_real_escape_string($this->IsPaid), mysql_real_escape_string($this->Payment), mysql_real_escape_string($this->IsDespatched), mysql_real_escape_string($this->Despatch), mysql_real_escape_string(stripslashes($this->Person->Title)), mysql_real_escape_string(stripslashes($this->Person->Name)), mysql_real_escape_string($this->Person->Initial), mysql_real_escape_string(stripslashes($this->Person->LastName)), mysql_real_escape_string(stripslashes($this->Organisation)), mysql_real_escape_string(stripslashes($this->Person->Address->Line1)), mysql_real_escape_string(stripslashes($this->Person->Address->Line2)), mysql_real_escape_string(stripslashes($this->Person->Address->Line3)), mysql_real_escape_string(stripslashes($this->Person->Address->City)), mysql_real_escape_string(stripslashes($this->Person->Address->Region->Name)),mysql_real_escape_string(stripslashes($this->Person->Address->Country->Name)), mysql_real_escape_string(stripslashes($this->Person->Address->Zip)), mysql_real_escape_string($this->NominalCode), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = NULL) {
		if (!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		for ($i = 0; $i < count($this->Line); $i++) {
			$this->Line[$i]->Delete();
		}

		new DataQuery(sprintf("delete from invoice where Invoice_ID=%d", mysql_real_escape_string($this->ID)));

		return true;
	}

	function Cancel() {}

	function GetDocument($documentIdentifier = array(), $connection = null) {
		if(!is_null($connection) && ($connection->DbName == 'lightbulbsuk')) {
			$sql = "SELECT o.*,
										rb.Region_Name AS Billing_Region_Name,
										cb.Country AS Billing_Country,
										afb.Address_Format AS Billing_Address_Format,
										afb.Address_Summary AS Billing_Address_Summary,
										rs.Region_Name AS Shipping_Region_Name,
										cs.Country AS Shipping_Country,
										afs.Address_Format AS Shipping_Address_Format,
										afs.Address_Summary AS Shipping_Address_Summary
										FROM orders AS o
										LEFT JOIN regions AS rb ON o.Billing_Region_ID=rb.Region_ID
										LEFT JOIN countries AS cb ON o.Billing_Country_ID=cb.Country_ID
										LEFT JOIN regions AS rs ON o.Shipping_Region_ID=rs.Region_ID
										LEFT JOIN countries AS cs ON o.Shipping_Country_ID=cs.Country_ID
										LEFT JOIN address_format AS afb ON cb.Address_Format_ID=afb.Address_Format_ID
										LEFT JOIN address_format AS afs ON cs.Address_Format_ID=afs.Address_Format_ID
					WHERE Order_ID={$this->Order->ID}";

			$data = new DataQuery($sql, $connection);
			if($data->TotalRows > 0) {
				$this->Order->ID = $data->Row['Order_ID'];
				$this->Order->Backordered = $data->Row['Backordered'];
				$this->Order->QuoteID = $data->Row['Quote_ID'];
				$this->Order->ReturnID = $data->Row['Return_ID'];
				$this->Order->IsFreeTextDespatched = $data->Row['IsFreeTextDespatched'];
				$this->Order->Prefix = $data->Row['Order_Prefix'];
				$this->Order->Sample = $data->Row['Is_Sample'];
				$this->Order->IsDeclined = $data->Row['Is_Declined'];
				$this->Order->IsWarehouseDeclined = $data->Row['Is_Warehouse_Declined'];
				$this->Order->IsWarehouseUndeclined = $data->Row['Is_Warehouse_Undeclined'];
				$this->Order->IsWarehouseBackordered = $data->Row['Is_Warehouse_Backordered'];
				$this->Order->Customer->ID = $data->Row['Customer_ID'];
				$this->Order->Coupon->ID = $data->Row['Coupon_ID'];
				$this->Order->OriginalCoupon->ID = $data->Row['Original_Coupon_ID'];
				$this->Order->CustomID = $data->Row['Custom_Order_No'];
				$this->Order->OrderedOn = $data->Row['Ordered_On'];
				$this->Order->EmailedOn = $data->Row['Emailed_On'];
				$this->Order->EmailedTo = $data->Row['Emailed_To'];
				$this->Order->ReceivedOn = $data->Row['Received_On'];
				$this->Order->ReceivedBy = $data->Row['Received_By'];
				$this->Order->InvoicedOn = $data->Row['Invoiced_On'];
				$this->Order->PaidOn = $data->Row['Paid_On'];
				$this->Order->DespatchedOn = $data->Row['Despatched_On'];
				$this->Order->Status = $data->Row['Status'];
				$this->Order->TotalLines = $data->Row['Total_Lines'];
				$this->Order->SubTotal = $data->Row['SubTotal'];
				$this->Order->TotalShipping = $data->Row['TotalShipping'];
				$this->Order->IsCustomShipping = $data->Row['IsCustomShipping'];
				$this->Order->IsTaxExempt = $data->Row['IsTaxExempt'];
				$this->Order->TaxExemptCode = $data->Row['TaxExemptCode'];
				$this->Order->TotalDiscount = $data->Row['TotalDiscount'];
				$this->Order->TotalTax = $data->Row['TotalTax'];
				$this->Order->Total = $data->Row['Total'];
				$this->Order->IsActive = $data->Row['Is_Active'];
				$this->Order->Billing->Title = $data->Row['Billing_Title'];
				$this->Order->Billing->Name = $data->Row['Billing_First_Name'];
				$this->Order->Billing->Initial = $data->Row['Billing_Initial'];
				$this->Order->Billing->LastName = $data->Row['Billing_Last_Name'];
				$this->Order->BillingOrg = $data->Row['Billing_Organisation_Name'];
				$this->Order->Billing->Address->Line1 = $data->Row['Billing_Address_1'];
				$this->Order->Billing->Address->Line2 = $data->Row['Billing_Address_2'];
				$this->Order->Billing->Address->Line3 = $data->Row['Billing_Address_3'];
				$this->Order->Billing->Address->City = $data->Row['Billing_City'];
				$this->Order->Billing->Address->Country->ID = $data->Row['Billing_Country_ID'];
				$this->Order->Billing->Address->Country->Name = $data->Row['Billing_Country'];
				$this->Order->Billing->Address->Country->AddressFormat->Long = $data->Row['Billing_Address_Format'];
				$this->Order->Billing->Address->Country->AddressFormat->Short = $data->Row['Billing_Address_Summary'];
				$this->Order->Billing->Address->Region->ID = $data->Row['Billing_Region_ID'];
				$this->Order->Billing->Address->Region->Name = $data->Row['Billing_Region_Name'];
				$this->Order->Billing->Address->Zip = $data->Row['Billing_Zip'];
				$this->Order->Shipping->Title = $data->Row['Shipping_Title'];
				$this->Order->Shipping->Name = $data->Row['Shipping_First_Name'];
				$this->Order->Shipping->Initial = $data->Row['Shipping_Initial'];
				$this->Order->Shipping->LastName = $data->Row['Shipping_Last_Name'];
				$this->Order->ShippingOrg = $data->Row['Shipping_Organisation_Name'];
				$this->Order->Shipping->Address->Line1 = $data->Row['Shipping_Address_1'];
				$this->Order->Shipping->Address->Line2 = $data->Row['Shipping_Address_2'];
				$this->Order->Shipping->Address->Line3 = $data->Row['Shipping_Address_3'];
				$this->Order->Shipping->Address->City = $data->Row['Shipping_City'];
				$this->Order->Shipping->Address->Country->ID = $data->Row['Shipping_Country_ID'];
				$this->Order->Shipping->Address->Country->Name = $data->Row['Shipping_Country'];
				$this->Order->Shipping->Address->Country->AddressFormat->Long = $data->Row['Shipping_Address_Format'];
				$this->Order->Shipping->Address->Country->AddressFormat->Short = $data->Row['Shipping_Address_Summary'];
				$this->Order->Shipping->Address->Region->ID = $data->Row['Shipping_Region_ID'];
				$this->Order->Shipping->Address->Region->Name = $data->Row['Shipping_Region_Name'];
				$this->Order->Shipping->Address->Zip = $data->Row['Shipping_Zip'];
				$this->Order->Referrer = $data->Row['Referrer'];
				$this->Order->IsOnAccount = $data->Row['Is_On_Account'];
				$this->Order->Card->Type->ID = $data->Row['Payment_Method'];
				$this->Order->Card->Type->Name = $data->Row['Card_Type'];
				$this->Order->Card->Number = $data->Row['Card_Number'];
				$this->Order->Card->Title = $data->Row['Card_Title'];
				$this->Order->Card->Initial = $data->Row['Card_Initial'];
				$this->Order->Card->Surname = $data->Row['Card_Surname'];
				$this->Order->Card->CVN = $data->Row['Card_CVN'];
				$this->Order->Card->Starts = $data->Row['Card_Starts'];
				$this->Order->Card->Expires = $data->Row['Card_Expires'];
				$this->Order->Card->Issue = $data->Row['Card_Issue'];
				$this->Order->CreatedOn = $data->Row['Created_On'];
				$this->Order->CreatedBy = $data->Row['Created_By'];
				$this->Order->ModifiedOn = $data->Row['Modified_On'];
				$this->Order->ModifiedBy = $data->Row['Modified_By'];
				$this->Order->Weight = $data->Row['Weight'];
				$this->Order->Postage->Get($data->Row['Postage_ID']);
				$this->Order->FreeText = $data->Row['Free_Text'];
				$this->Order->FreeTextValue = $data->Row['Free_Text_Value'];
				$this->Order->DiscountReward = $data->Row['Discount_Reward'];
				$this->Order->DiscountBandingID = $data->Row['Discount_Banding_ID'];
				$this->Order->DeliveryInstructions = $data->Row['Delivery_Instructions'];
				$this->Order->TotalNet = $this->Order->SubTotal + $this->Order->TotalShipping - $this->Order->TotalDiscount;
			}
			$data->Disconnect();

			// GET LINES
			$this->Line = array();
			$this->LinesHtml = "";

			if(!is_numeric($this->ID)){
				return false;
			}

			$data = new DataQuery(sprintf("SELECT i.*, p.SKU FROM invoice_line AS i LEFT JOIN product AS p ON i.Product_ID = p.Product_ID WHERE Invoice_ID=%d", mysql_real_escape_string($this->ID)), $connection);
			while ($data->Row) {
				$line = new InvoiceLine();
				$line->ID = $data->Row['Invoice_Line_ID'];
				$line->InvoiceID = $data->Row['Invoice_ID'];
				$line->Description = $data->Row['Description'];
				$line->Quantity = $data->Row['Quantity'];
				$line->Product->ID = $data->Row['Product_ID'];
				$line->Product->SKU = $data->Row['SKU'];
				$line->Price = $data->Row['Price'];
				$line->Total = $data->Row['Line_Total'];
				$line->Discount = $data->Row['Line_Discount'];
				$line->DiscountInformation = $data->Row['Discount_Information'];
				$line->Tax = $data->Row['Line_Tax'];
				$line->CreatedOn = $data->Row['Created_On'];
				$line->CreatedBy = $data->Row['Created_By'];
				$line->ModifiedOn = $data->Row['Modified_On'];
				$line->ModifiedBy = $data->Row['Modified_By'];

				$this->LinesHtml .= sprintf("<tr><td>%sx</td><td>%s</td><td>%s</td><td align=\"right\">&pound;%s</td><td align=\"right\">&pound;%s</td></tr>", $line->Quantity, $line->Description, $line->Product->PublicID(), number_format($line->Price, 2, '.', ','), number_format($line->Total, 2, '.', ','));

				$this->Line[] = $line;

				$data->Next();
			}
			$data->Disconnect();
	
			$this->GetCustom();
			
			$dueMessage = cDatetime($this->DueOn, 'longdate');
			$card = $this->Order->Card->PrivateNumber();
			if((strtoupper($this->Order->IsOnAccount) == 'N' && !empty($card))){
				$dueMessage = "Paid with thanks.";
			}

			if($this->ShowCustom) {
				$taxCalculator = new GlobalTaxCalculator($this->Order->Shipping->Address->Country->ID, $this->Order->Shipping->Address->Region->ID);
				$taxToAdd = 0;

				if(strtoupper($this->Order->IsTaxExempt) == 'N' || empty($this->Order->TaxExemptCode)){
					$data = new DataQuery("SELECT Tax_Class_ID FROM tax_class WHERE Is_Default='Y'", $connection);
					if($data->TotalRows > 0) {
						$temptax = $taxCalculator->GetTax($this->Order->FreeTextValue, $data->Row['Tax_Class_ID']);
						$taxToAdd += round($temptax, 2);
					}
					$data->Disconnect();
				}
			}
			
			// GET PAYMENT METHOD
			if(strtoupper($this->IsOnAccount) == 'Y'){
				$paymentMethod = 'Credit Account';
			} else {
				$paymentMethod = $this->Order->Card->Type->Name;
			}

			$findReplace = new FindReplace();
			$findReplace->Add('/\[ORDER_REF\]/', $this->Order->Prefix . $this->Order->ID);
			$findReplace->Add('/\[CUSTOM_REF\]/', $this->Order->CustomID);
			$findReplace->Add('/\[ORDER_DATE\]/', cDatetime($this->Order->OrderedOn, 'longdate'));
			$findReplace->Add('/\[INVOICE_DUE\]/', $dueMessage);
			$findReplace->Add('/\[INVOICE_REF\]/', $this->ID);
			$findReplace->Add('/\[INVOICE_DATE\]/', cDatetime($this->CreatedOn, 'longdate'));
			$findReplace->Add('/\[BILLTO\]/', $this->Order->GetBillingAddress());
			$findReplace->Add('/\[SHIPTO\]/', $this->Order->GetShippingAddress());

			if(stristr($this->Order->Card->Surname, 'GoogleCheckout')) {
				$findReplace->Add('/\[PAYMENT_METHOD\]/', 'Google Checkout');
			} else {
				$findReplace->Add('/\[PAYMENT_METHOD\]/', $paymentMethod);
			}

			$findReplace->Add('/\[CARD_NUMBER\]/', $this->Order->Card->PrivateNumber());
			$findReplace->Add('/\[CARD_EXPIRES\]/', $this->Order->Card->Expires);

			if($this->ShowCustom) {
				$findReplace->Add('/\[NET\]/', "&pound;" . number_format(($this->Net + $this->Order->FreeTextValue), 2, '.',','));
				$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format(($this->SubTotal + $this->Order->FreeTextValue), 2, '.',','));
				$findReplace->Add('/\[TAX\]/', "&pound;" . number_format(($this->Tax+$taxToAdd), 2, '.',','));
				$findReplace->Add('/\[SHIPPING\]/', "&pound;" . number_format($this->Shipping, 2, '.',','));
				$findReplace->Add('/\[DISCOUNT\]/', "-&pound;" . number_format($this->Discount, 2, '.',','));
				$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format(($this->Total + $this->Order->FreeTextValue + $taxToAdd), 2, '.',','));
			} else {
				$findReplace->Add('/\[NET\]/', "&pound;" . number_format($this->Net, 2, '.',','));
				$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format($this->SubTotal, 2, '.',','));
				$findReplace->Add('/\[TAX\]/', "&pound;" . number_format($this->Tax, 2, '.',','));
				$findReplace->Add('/\[SHIPPING\]/', "&pound;" . number_format($this->Shipping, 2, '.',','));
				$findReplace->Add('/\[DISCOUNT\]/', "-&pound;" . number_format($this->Discount, 2, '.',','));
				$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format($this->Total, 2, '.',','));
			}

			// GET POSTAGE
			$data =  new DataQuery(sprintf("SELECT * FROM postage WHERE Postage_ID=%d", mysql_real_escape_string($this->Order->Postage->ID)));
			$this->Order->Postage->Name = $data->Row['Postage_Title'];
			$this->Order->Postage->Description = $data->Row['Postage_Description'];
			$this->Order->Postage->Days = $data->Row['Postage_Days'];
			$this->Order->Postage->CuttOffTime = $data->Row['Cutt_Off_Time'];
			$this->Order->Postage->Message = $data->Row['Cut_Off_Message'];
			$this->Order->Postage->CreatedOn = $data->Row['Created_On'];
			$this->Order->Postage->CreatedBy = $data->Row['Created_By'];
			$this->Order->Postage->ModifiedOn = $data->Row['Modified_On'];
			$this->Order->Postage->ModifiedBy = $data->Row['Modified_By'];
			$data->Disconnect();
			
			$findReplace->Add('/\[DELIVERY\]/', $this->Order->Postage->Name);
			$findReplace->Add('/\[LINES\]/', $this->LinesHtml);
			$findReplace->Add('/\[CUSTOM\]/', (($this->ShowCustom) ? $this->CustomHtml : ''));

			return $findReplace->Execute(Template::GetContent('print_invoice', 2));
		} else {
			$this->PaymentMethod->Get();
			$this->Order->Get();
			$this->GetLines();
			$this->GetCustom();

			if (empty($this->Order->Customer->Contact->ID))
				$this->Order->Customer->Get();
			if (empty($this->Order->Customer->Contact->Person->ID))
				$this->Order->Customer->Contact->Get();

			$dueMessage = cDatetime($this->DueOn, 'longdate');

			if($this->PaymentMethod->Reference == 'card') {
				$dueMessage = "Paid with thanks.";
			}

			$taxToAdd = 0;
			
			if($this->ShowCustom) {
				$taxToAdd += round($this->Order->CalculateCustomTax($this->Order->FreeTextValue), 2);
			}

			$findReplace = new FindReplace();
			$findReplace->Add('/\[ORDER_REF\]/', $this->Order->Prefix . $this->Order->ID);
			$findReplace->Add('/\[CUSTOM_REF\]/', $this->Order->CustomID);
			$findReplace->Add('/\[ORDER_DATE\]/', cDatetime($this->Order->OrderedOn, 'longdate'));
			$findReplace->Add('/\[INVOICE_DUE\]/', $dueMessage);
			$findReplace->Add('/\[INVOICE_REF\]/', $this->ID);
			$findReplace->Add('/\[INVOICE_DATE\]/', cDatetime($this->CreatedOn, 'longdate'));
			$findReplace->Add('/\[TAX_CODE\]/', $this->Order->TaxExemptCode);
			$findReplace->Add('/\[CUSTOMER_NAME\]/', $this->Order->Customer->Contact->Person->GetFullName());
			$findReplace->Add('/\[BILLTO\]/', $this->GetInvoiceAddress() . '<br /><br />' . $this->Order->Customer->Contact->Person->GetPhone('<br />'));
			$findReplace->Add('/\[SHIPTO\]/', $this->Order->GetShippingAddress() . '<br /><br />' . $this->Order->Customer->Contact->Person->GetPhone('<br />'));
			$findReplace->Add('/\[PAYMENT_METHOD\]/', $this->PaymentMethod->Method);
	        $findReplace->Add('/\[CARD_NUMBER\]/', ($this->PaymentMethod->Reference == 'card') ? $this->Order->Card->PrivateNumber() : '');

			if ($this->ShowCustom) {
				$findReplace->Add('/\[NET\]/', "&pound;" . number_format(($this->Net + $this->Order->FreeTextValue), 2, '.', ','));
				$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format(($this->SubTotal + $this->Order->FreeTextValue), 2, '.', ','));
				$findReplace->Add('/\[DISCOUNTTOTAL\]/', "&pound;" . number_format(($this->SubTotal + $this->Order->FreeTextValue - $this->Discount), 2, '.', ','));
				$findReplace->Add('/\[TAX\]/', "&pound;" . number_format(($this->Tax + $taxToAdd), 2, '.', ','));
				$findReplace->Add('/\[SHIPPING\]/', "&pound;" . number_format($this->Shipping, 2, '.', ','));
				$findReplace->Add('/\[DISCOUNT\]/', "-&pound;" . number_format($this->Discount, 2, '.', ','));
				$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format(($this->Total + $this->Order->FreeTextValue + $taxToAdd), 2, '.', ','));
			} else {
				$findReplace->Add('/\[NET\]/', "&pound;" . number_format($this->Net, 2, '.', ','));
				$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format($this->SubTotal, 2, '.', ','));
				$findReplace->Add('/\[DISCOUNTTOTAL\]/', "&pound;" . number_format($this->SubTotal - $this->Discount, 2, '.', ','));
				$findReplace->Add('/\[TAX\]/', "&pound;" . number_format($this->Tax, 2, '.', ','));
				$findReplace->Add('/\[SHIPPING\]/', "&pound;" . number_format($this->Shipping, 2, '.', ','));
				$findReplace->Add('/\[DISCOUNT\]/', "-&pound;" . number_format($this->Discount, 2, '.', ','));
				$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format($this->Total, 2, '.', ','));
			}

			$this->Order->Postage->Get();
			$findReplace->Add('/\[DELIVERY\]/', $this->Order->Postage->Name);
			$findReplace->Add('/\[LINES\]/', $this->LinesHtml);
			$findReplace->Add('/\[CUSTOM\]/', (($this->ShowCustom) ? $this->CustomHtml : ''));

			$html = '';
			
			if(isset($documentIdentifier['Template'])) {
				$html = $findReplace->Execute(Template::GetContent($documentIdentifier['Template']));
			} else {
				$file = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/print_invoice.tpl");
				
				for($i=0; $i<count($file); $i++) {
					$html .= $findReplace->Execute($file[$i]);
				}
			}

			return $html;
		}
	}
	
	function GetErrorDocument($start, $end) {
		$findReplace = new FindReplace();
		$findReplace->Add('/\[INVOICE_ADDRESS\]/', $this->GetInvoiceAddress() . '<br /><br />' . $this->Order->Customer->Contact->Person->GetPhone('<br />'));
		$findReplace->Add('/\[INVOICE_ERROR_START\]/', date('d/m/Y', strtotime($start)));
		$findReplace->Add('/\[INVOICE_ERROR_END\]/', date('d/m/Y', strtotime($end)));

		$file = file(sprintf('%slib/templates/print/invoice_error.tpl', $GLOBALS["DIR_WS_ADMIN"]));
		$html = '';
		
		for($i=0; $i<count($file); $i++) {
			$html .= $findReplace->Execute($file[$i]);
		}

		return $html;
	}
	
	function GetInvoiceAddress() {
		$address = '';
    	
    	$fullName = $this->Person->GetFullName();
    	
    	if(!empty($fullName)) {
			$address .= $fullName;
			$address .= '<br />';
		}
		
		if(!empty($this->Organisation)) {
			$address .= $this->Organisation;
			$address .= '<br />';
		}
		
		$address .= $this->Person->Address->GetFormatted('<br />');
		
		return $address;
	}

	function EmailCustomer($additionEmail = null) {
		$this->PaymentMethod->Get();
		$this->Order->Get();
		$this->Order->Postage->Get();
		$this->GetLines();
		$this->GetCustom();

		if(empty($this->Order->Customer->Contact->ID)) {
			$this->Order->Customer->Get();
		}

		if(empty($this->Order->Customer->Contact->Person->ID)) {
			$this->Order->Customer->Contact->Get();
		}

		$dueMessage = cDatetime($this->DueOn, 'longdate');

		if($this->PaymentMethod->Reference == 'card') {
			$dueMessage = 'Paid with thanks.';
		}

		$taxToAdd = 0;
		
		if($this->ShowCustom) {
			$taxToAdd += round($this->Order->CalculateCustomTax($this->Order->FreeTextValue), 2);
		}

		$findReplace = new FindReplace();
		$findReplace->Add('/\[ORDER_REF\]/', $this->Order->Prefix . $this->Order->ID);
		$findReplace->Add('/\[CUSTOM_REF\]/', $this->Order->CustomID);
		$findReplace->Add('/\[ORDER_DATE\]/', cDatetime($this->Order->OrderedOn, 'longdate'));
		$findReplace->Add('/\[INVOICE_DUE\]/', $dueMessage);
		$findReplace->Add('/\[INVOICE_REF\]/', $this->ID);
		$findReplace->Add('/\[INVOICE_DATE\]/', cDatetime($this->CreatedOn, 'longdate'));
		$findReplace->Add('/\[TAX_CODE\]/', $this->Order->TaxExemptCode);
		$findReplace->Add('/\[CUSTOMER_NAME\]/', $this->Order->Customer->Contact->Person->GetFullName());
		$findReplace->Add('/\[BILLTO\]/', $this->GetInvoiceAddress());
		$findReplace->Add('/\[SHIPTO\]/', $this->Order->GetShippingAddress());
        $findReplace->Add('/\[PAYMENT_METHOD\]/', $this->PaymentMethod->Method);
        $findReplace->Add('/\[CARD_NUMBER\]/', ($this->PaymentMethod->Reference == 'card') ? $this->Order->Card->PrivateNumber() : '');

		if ($this->ShowCustom) {
			$findReplace->Add('/\[NET\]/', "&pound;" . number_format(($this->Net + $this->Order->FreeTextValue), 2, '.', ','));
			$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format(($this->SubTotal + $this->Order->FreeTextValue), 2, '.', ','));
			$findReplace->Add('/\[TAX\]/', "&pound;" . number_format(($this->Tax + $taxToAdd), 2, '.', ','));
			$findReplace->Add('/\[SHIPPING\]/', "&pound;" . number_format($this->Shipping, 2, '.', ','));
			$findReplace->Add('/\[DISCOUNT\]/', "-&pound;" . number_format($this->Discount, 2, '.', ','));
			$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format(($this->Total + $this->Order->FreeTextValue + $taxToAdd), 2, '.', ','));
		} else {
			$findReplace->Add('/\[NET\]/', "&pound;" . number_format($this->Net, 2, '.', ','));
			$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format($this->SubTotal, 2, '.', ','));
			$findReplace->Add('/\[TAX\]/', "&pound;" . number_format($this->Tax, 2, '.', ','));
			$findReplace->Add('/\[SHIPPING\]/', "&pound;" . number_format($this->Shipping, 2, '.', ','));
			$findReplace->Add('/\[DISCOUNT\]/', "-&pound;" . number_format($this->Discount, 2, '.', ','));
			$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format($this->Total, 2, '.', ','));
		}

		$findReplace->Add('/\[DELIVERY\]/', $this->Order->Postage->Name);
		$findReplace->Add('/\[LINES\]/', $this->LinesHtml);
		$findReplace->Add('/\[CUSTOM\]/', (($this->ShowCustom) ? $this->CustomHtml : ''));

		$html = $findReplace->Execute(Template::GetContent('email_invoice'));
		
		$findReplace = new FindReplace();
		$findReplace->Add('/\[BODY\]/', $html);
		$findReplace->Add('/\[NAME\]/', $this->Order->Customer->Contact->Person->GetFullName());

		$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
		$emailBody = "";
		for ($i = 0; $i < count($stdTmplate); $i++) {
			$emailBody .= $findReplace->Execute($stdTmplate[$i]);
		}

		$mail = new htmlMimeMail5();
		$mail->setFrom($GLOBALS['EMAIL_FROM']);
		$mail->setSubject(sprintf("%s Invoice [#%s]", $GLOBALS['COMPANY'], $this->ID));
		$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
		$mail->setHTML($emailBody);
		
		$fileName = sprintf('BLT Direct - Invoice %d.pdf', $this->ID);
		
		$pdf = new StandardPDF();
		$pdf->WriteHTML($this->GetDocument(array('Template' => 'pdf_invoice')));
		$pdf->Output($GLOBALS['TEMP_INVOICE_DOCUMENT_DIR_FS'] . $fileName, 'F');
		
		$mail->addAttachment(new fileAttachment($GLOBALS['TEMP_INVOICE_DOCUMENT_DIR_FS'] . $fileName));
		if(!empty($additionEmail)){
			$mail->send(array($additionEmail));
		}else{
			$mail->send(array($this->Order->Customer->GetInvoiceEmail()));
		}
	}

	function IsUnique($column, $val) {
		$checkOrder = new DataQuery(sprintf("SELECT Invoice_ID FROM invoice WHERE %s = '%s'", mysql_real_escape_string($column), mysql_real_escape_string($val)));
		$checkOrder->Disconnect();
		if ($checkOrder->TotalRows > 0) {
			$this->ID = $checkOrder->Row['Invoice_ID'];
			return false;
		} else {
			return true;
		}
	}
}