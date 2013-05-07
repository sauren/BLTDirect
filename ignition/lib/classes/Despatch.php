<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Contact.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Coupon.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Courier.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DespatchLine.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/EmailQueue.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Order.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Person.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Postage.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Purchase.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Setting.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/gateways/FastSMS.php');

class Despatch {
	var $ID;
	var $Purchase;
	var $Courier;
	var $Order;
	var $Consignment;
	var $Weight;
	var $Boxes;
	var $Postage;
	var $PostageCost;
	var $DespatchedOn;
	var $Person;
	var $Organisation;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $Line;
	var $DespatchedFrom;
	var $CustomHtml;
	var $ShowCustom;
	var $DeliveryInstructions;
	var $IsIgnition;

	function __construct($id=NULL) {
		$this->Purchase = new Purchase();
		$this->Postage = new Postage();
		$this->IsIgnition = false;
		$this->ShowCustom = true;
		$this->Courier = new Courier();
		$this->Person = new Person();
		$this->Line = array();
		$this->Order = new Order();
		$this->DespatchedFrom = new Warehouse();

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

		$data = new DataQuery(sprintf("select * from despatch where Despatch_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Purchase->ID = $data->Row['Purchase_ID'];
			$this->Courier->ID = $data->Row['Courier_ID'];
			$this->Order->ID = $data->Row['Order_ID'];
			$this->Consignment = $data->Row['Consignment'];
			$this->Weight = $data->Row['Weight'];
			$this->Boxes = $data->Row['Boxes'];
			$this->Postage->ID = $data->Row['Postage_ID'];
			$this->PostageCost = $data->Row['Postage_Cost'];
			$this->DespatchedOn = $data->Row['Despatched_On'];
			$this->DespatchedFrom->Get($data->Row['Despatch_From_ID']);
			$this->Person->Title = $data->Row['Despatch_Title'];
			$this->Person->Name = $data->Row['Despatch_First_Name'];
			$this->Person->Initial = $data->Row['Despatch_Initial'];
			$this->Person->LastName = $data->Row['Despatch_Last_Name'];
			$this->Organisation = $data->Row['Despatch_Organisation_Name'];
			$this->Person->Address->Line1 = $data->Row['Despatch_Address_1'];
			$this->Person->Address->Line2 = $data->Row['Despatch_Address_2'];
			$this->Person->Address->Line3 = $data->Row['Despatch_Address_3'];
			$this->Person->Address->City = $data->Row['Despatch_City'];
			$this->Person->Address->Region->Name = $data->Row['Despatch_Region'];
			$this->Person->Address->Country->Name = $data->Row['Despatch_Country'];
			$this->Person->Address->Zip = $data->Row['Despatch_Zip'];
			$this->DeliveryInstructions = $data->Row['Delivery_Instructions'];
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

	function GetCustom() {
		$this->CustomHtml = '';

		if($this->ShowCustom) {
			if(!empty($this->Order->FreeText)) {
				$this->CustomHtml .= '<tr>';
				$this->CustomHtml .= '<td>&nbsp;</td>';
				$this->CustomHtml .= '<td colspan="3">'.$this->Order->FreeText.'&nbsp;</td>';
				$this->CustomHtml .= '</tr>';
			}
		}
	}

	function GetLines(){
		$this->Line = array();
		$this->LinesHtml = "";

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("select Despatch_Line_ID from despatch_line where Despatch_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row){
			$line = new DespatchLine($data->Row['Despatch_Line_ID']);
			$line->Product->Get();

			$warehouseFind = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Product_ID=%d AND Warehouse_ID=%d AND Shelf_Location<>'' LIMIT 0, 1", mysql_real_escape_string($line->Product->ID), mysql_real_escape_string($this->DespatchedFrom->ID)));

			$this->LinesHtml .= sprintf("<tr><td>%sx</td><td>%s%s</td><td>%s</td><td>%s</td><td>%s</td></tr>", $line->Quantity, $line->Product->Name, ($line->Product->ID > 0) ? sprintf('<br />Part Number: %s', $line->Product->SKU) : '', $line->Product->PublicID(), $warehouseFind->Row['Shelf_Location'], ($line->IsComplementary == 'Y') ? '<strong style="color: #ff3333;">Complementary</strong>' : '');

			$dataComponents = new DataQuery(sprintf("SELECT * FROM product_components WHERE Component_Of_Product_ID=%d", mysql_real_escape_string($line->Product->ID)));

			while($dataComponents->Row) {
				$component = new Product($dataComponents->Row['Product_ID']);

				$warehouseFindMain = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Product_ID=%d AND Warehouse_ID=%d AND Shelf_Location<>'' LIMIT 0, 1", mysql_real_escape_string($component->ID), mysql_real_escape_string($this->DespatchedFrom->ID)));

				$this->LinesHtml .= "<tr style=\"background-color: #eee;\">";
				$this->LinesHtml .= "<td>Component: ".($dataComponents->Row['Component_Quantity']*$line->Quantity)."x</td>";
				$this->LinesHtml .= "<td>".$component->Name."<br />Part Number: ".$component->SKU."</td>";
				$this->LinesHtml .= "<td>".$component->ID."</td>";
				$this->LinesHtml .= "<td>".$warehouseFindMain->Row['Shelf_Location']."</td>";
				$this->LinesHtml .= "</tr>";

				$warehouseFindMain->Disconnect();

				$dataComponents->Next();
			}
			$dataComponents->Disconnect();

			$warehouseFind->Disconnect();

			$this->Line[] = $line;

			$data->Next();
		}
		$data->Disconnect();
	}
	
	function GetShippingAddress() {
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

	function Add(){
		$sql = sprintf("insert into despatch (Purchase_ID,
						Order_ID, Courier_ID, Consignment, Weight, Boxes, Postage_ID, Postage_Cost, Despatched_On,Despatch_From_ID, Despatch_Title, Despatch_First_Name, Despatch_Initial, Despatch_Last_Name, Despatch_Organisation_Name, Despatch_Address_1, Despatch_Address_2, Despatch_Address_3, Despatch_City, Despatch_Region, Despatch_Country, Despatch_Zip, Delivery_Instructions, Created_On, Created_By, Modified_On, Modified_By
						) values (%d, %d, %d, '%s', '%f', %d, %d, %f, '%s',%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', Now(), %s, Now(), %d)",
		mysql_real_escape_string($this->Purchase->ID),
		mysql_real_escape_string($this->Order->ID),
		mysql_real_escape_string($this->Courier->ID),
		mysql_real_escape_string($this->Consignment),
		mysql_real_escape_string($this->Weight),
		mysql_real_escape_string($this->Boxes),
		mysql_real_escape_string($this->Postage->ID),
		mysql_real_escape_string($this->PostageCost),
		mysql_real_escape_string($this->DespatchedOn),
		mysql_real_escape_string($this->DespatchedFrom->ID),
		mysql_real_escape_string(stripslashes($this->Person->Title)),
		mysql_real_escape_string(stripslashes($this->Person->Name)),
		mysql_real_escape_string($this->Person->Initial),
		mysql_real_escape_string(stripslashes($this->Person->LastName)),
		mysql_real_escape_string(stripslashes($this->Organisation)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Line1)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Line2)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Line3)),
		mysql_real_escape_string(stripslashes($this->Person->Address->City)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Region->Name)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Country->Name)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Zip)),
		mysql_real_escape_string(stripslashes($this->DeliveryInstructions)),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));

		$data = new DataQuery($sql);
		$this->ID = $data->InsertID;
		
		$this->SmsConsignment();
		
		return true;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT Consignment FROM despatch WHERE Despatch_ID=%d", mysql_real_escape_string($this->ID)));
		$consignment = $data->Row['Consignment'];
		$data->Disconnect();
		
		$data = new DataQuery(sprintf("update despatch set Purchase_ID=%d, Order_ID=%d,
						Courier_ID=%d, Consignment='%s', Weight='%f', Boxes=%d, Postage_ID=%d, Postage_Cost=%f, Despatched_On='%s',Despatch_From_ID=%d, Despatch_Title='%s', Despatch_First_Name='%s', Despatch_Initial='%s', Despatch_Last_Name='%s', Despatch_Organisation_Name='%s', Despatch_Address_1='%s', Despatch_Address_2='%s', Despatch_Address_3='%s', Despatch_City='%s', Despatch_Region='%s', Despatch_Country='%s', Despatch_Zip='%s', Delivery_Instructions='%s', Modified_On=Now(), Modified_By=%d
						where Despatch_ID=%d",
		mysql_real_escape_string($this->Purchase->ID),
		mysql_real_escape_string($this->Order->ID),
		mysql_real_escape_string($this->Courier->ID),
		mysql_real_escape_string($this->Consignment),
		mysql_real_escape_string($this->Weight),
		mysql_real_escape_string($this->Boxes),
        mysql_real_escape_string($this->Postage->ID),
		mysql_real_escape_string($this->PostageCost),
		mysql_real_escape_string($this->DespatchedOn),
		mysql_real_escape_string($this->DespatchedFrom->ID),
		mysql_real_escape_string(stripslashes($this->Person->Title)),
		mysql_real_escape_string(stripslashes($this->Person->Name)),
		mysql_real_escape_string($this->Person->Initial),
		mysql_real_escape_string(stripslashes($this->Person->LastName)),
		mysql_real_escape_string(stripslashes($this->Organisation)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Line1)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Line2)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Line3)),
		mysql_real_escape_string(stripslashes($this->Person->Address->City)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Region->Name)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Country->Name)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Zip)),
		mysql_real_escape_string(stripslashes($this->DeliveryInstructions)),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->ID)));

		if($consignment != $this->Consignment) {
			$this->SmsConsignment();
		}
		
		return true;
	}

	function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM despatch WHERE Despatch_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function EmailCustomer(){
		$this->Order->Get();
		$this->Order->PaymentMethod->Get();
		$this->Order->Postage->Get();
		$this->Order->GetLines();
		$this->Courier->Get();

		$coupon = new Coupon();

		if(!$coupon->GetIntroductoryCoupon($this->Order->Customer->ID)) {
			$coupon->GenerateIntroductoryCoupon($this->Order->Customer->ID);
		}

		$this->GetLines();
		$this->GetCustom();

		if(empty($this->Order->Customer->Contact->ID))$this->Order->Customer->Get();
		if(empty($this->Order->Customer->Contact->Person->ID)) $this->Order->Customer->Contact->Get();

		$quantityTotal = 0;
		$quantityShipping = 0;

		for($i=0; $i<count($this->Order->Line); $i++) {
			if($this->Order->Line[$i]->Status != 'Cancelled') {
				$quantityTotal += $this->Order->Line[$i]->Quantity;
			}
		}

		for($i=0; $i<count($this->Line); $i++) {
			$quantityShipping += $this->Line[$i]->Quantity;
		}

		$isPartialDespatch = ($quantityShipping < $quantityTotal);

		$autoLogin = serialize(array($this->Order->Customer->Contact->ID, $this->Order->Customer->Contact->CreatedOn));

		$cypher = new Cipher($autoLogin);
		$cypher->Encrypt();

		$findReplace = new FindReplace();
		$findReplace->Add('/\[AUTO_LOGIN\]/', urlencode($cypher->Value));
		$findReplace->Add('/\[ORDER_REF\]/', $this->Order->Prefix . $this->Order->ID);
		$findReplace->Add('/\[CUSTOM_REF\]/', $this->Order->CustomID);
		$findReplace->Add('/\[ORDER_DATE\]/', cDatetime($this->Order->OrderedOn, 'longdate'));
		$findReplace->Add('/\[DESPATCH_REF\]/', $this->ID);
		$findReplace->Add('/\[DESPATCH_DATE\]/', cDatetime($this->DespatchedOn, 'longdate'));
		$findReplace->Add('/\[CUSTOMER_NAME\]/', $this->Order->Customer->Contact->Person->GetFullName());
		$findReplace->Add('/\[CUSTOMER_ID\]/', $this->Customer->Contact->ID);
		$findReplace->Add('/\[SHIPTO\]/', $this->GetShippingAddress());
		$findReplace->Add('/\[PAYMENT_METHOD\]/', $this->Order->GetPaymentMethod());
        $findReplace->Add('/\[CARD_NUMBER\]/', ($this->Order->PaymentMethod->Reference == 'card') ? $this->Order->Card->PrivateNumber() : '');
		$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format($this->Order->SubTotal, 2, '.',','));
		$findReplace->Add('/\[SHIPPING\]/', "&pound;" . number_format($this->Order->TotalShipping, 2, '.',','));
		$findReplace->Add('/\[TAX\]/', "&pound;" . number_format($this->Order->TotalTax, 2, '.',','));
		$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format($this->Order->Total, 2, '.',','));
		$findReplace->Add('/\[WEIGHT\]/', $this->Weight);
		$findReplace->Add('/\[DELIVERY\]/', $this->Order->Postage->Name);
		$findReplace->Add('/\[LINES\]/', $this->LinesHtml);
		$findReplace->Add('/\[CUSTOM\]/', (($this->ShowCustom) ? $this->CustomHtml : ''));
		$findReplace->Add('/\[BOXES\]/', $this->Boxes);
		$findReplace->Add('/\[PHONE\]/', $this->Order->Customer->Contact->Person->Phone1);
		$findReplace->Add('/\[POSTAGE\]/', $this->Order->Postage->Name);
		$findReplace->Add('/\[POSTAGE_DATE\]/', date('jS F Y', strtotime($this->DespatchedOn) + ($this->Order->Postage->Days * 86400)));
		$findReplace->Add('/\[POSTAGE_QUERY_DATE\]/', date('jS F Y', strtotime($this->DespatchedOn) + ($this->Order->Postage->Days * 2 * 86400)));
		$findReplace->Add('/\[COURIER\]/', $this->Courier->Name);
		$findReplace->Add('/\[COURIER_LINK\]/', (($this->Courier->IsTrackingActive == 'Y') && !empty($this->Consignment)) ? $this->Courier->URL : '');
		$findReplace->Add('/\[CONSIGNMENT\]/', ($this->Courier->IsTrackingActive == 'Y') ? $this->Consignment : '');
		$findReplace->Add('/\[NOTICES\]/', $isPartialDespatch ? $this->GetPartialDespatchNotice() : '');
		$findReplace->Add('/\[COUPON\]/', $coupon->Reference);
		$findReplace->Add('/\[INSTRUCTIONS\]/', $this->DeliveryInstructions);

		if($this->Order->Sample == 'Y') {
			$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_despatchSample.tpl");
		} else {
			$complementaryCount = 0;

			for($i=0; $i<count($this->Line); $i++) {
				if($this->Line[$i]->IsComplementary == 'Y') {
					$complementaryCount++;
				}
			}

			if((($this->Order->Prefix == 'R') || $this->Order->Prefix == 'B') && ($complementaryCount > 0)) {
				$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_despatchReturn.tpl");
			} else {
				$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_despatch.tpl");
			}
		}

		$orderHtml = "";
		for($i=0; $i < count($orderEmail); $i++){
			$orderHtml .= $findReplace->Execute($orderEmail[$i]);
		}

		$findReplace = new FindReplace();
		$findReplace->Add('/\[BODY\]/', $orderHtml);
		$findReplace->Add('/\[NAME\]/', $this->Order->Customer->Contact->Person->GetFullName());

		$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
		$emailBody = "";
		for($i=0; $i < count($stdTmplate); $i++){
			$emailBody .= $findReplace->Execute($stdTmplate[$i]);
		}

		$queue = new EmailQueue();
		$queue->GetModuleID('despatches');
		$queue->Subject = sprintf("%s - Order %sDespatched [#%s%s]", $GLOBALS['COMPANY'], $isPartialDespatch ? 'Partially ' : '', $this->Order->Prefix, $this->Order->ID);
		$queue->Body = $emailBody;
		$queue->ToAddress = $this->Order->Customer->GetEmail();
		$queue->Priority = 'H';
		$queue->Type = 'H';

		if($this->Order->Postage->Days > 1) {
			$queue->SetSendAfter(86400);
		}
		
		$queue->Add();
	}

	function GetDocument($branded = true){
		$this->Order->Get();
		$this->Order->PaymentMethod->Get();
		$this->Order->GetLines();
		$this->Courier->Get();


		$coupon = new Coupon();

		if(!$coupon->GetIntroductoryCoupon($this->Order->Customer->ID)) {
			$coupon->GenerateIntroductoryCoupon($this->Order->Customer->ID);
		}

		$this->GetLines();
		$this->GetCustom();

		if(empty($this->Order->Customer->Contact->ID))$this->Order->Customer->Get();
		if(empty($this->Order->Customer->Contact->Person->ID)) $this->Order->Customer->Contact->Get();

		$quantityTotal = 0;
		$quantityShipping = 0;

		for($i=0; $i<count($this->Order->Line); $i++) {
			if($this->Order->Line[$i]->Status != 'Cancelled') {
				$quantityTotal += $this->Order->Line[$i]->Quantity;
			}
		}

		for($i=0; $i<count($this->Line); $i++) {
			$quantityShipping += $this->Line[$i]->Quantity;
		}

		$isPartialDespatch = ($quantityShipping < $quantityTotal);
		
		$cost = 0;

		for($i=0; $i<count($this->Order->Line); $i++) {
			$cost += $this->Order->Line[$i]->Cost * $this->Order->Line[$i]->Quantity;
		}

		$siggy = '';

		if(($this->DespatchedFrom->Type == 'B') && ($cost >= 100.00)) {
			$siggy .= '<tr><td nowrap style="color: #f00;">Signature:</td><td></td></tr>';
		}

		$findReplace = new FindReplace();
		$findReplace->Add('/\[ORDER_REF\]/', $this->Order->Prefix . $this->Order->ID);
		$findReplace->Add('/\[CUSTOM_REF\]/', $this->Order->CustomID);
		$findReplace->Add('/\[ORDER_DATE\]/', cDatetime($this->Order->OrderedOn, 'longdate'));
		$findReplace->Add('/\[DESPATCH_REF\]/', $this->ID);
		$findReplace->Add('/\[DESPATCH_DATE\]/', cDatetime($this->DespatchedOn, 'longdate'));
		$findReplace->Add('/\[ISSAMPLE\]/', ($this->Order->Sample == 'Y') ? 'Sample ' : '');
		$findReplace->Add('/\[CUSTOMER_NAME\]/', $this->Order->Customer->Contact->Person->GetFullName());
		$findReplace->Add('/\[CUSTOMER_ID\]/', $this->Customer->Contact->ID);
		$findReplace->Add('/\[SHIPTO\]/', $this->GetShippingAddress().'<br /><br />'.$this->Order->Customer->Contact->Person->GetPhone('<br />'));
        $findReplace->Add('/\[CARD_NUMBER\]/', ($this->Order->PaymentMethod->Reference == 'card') ? $this->Order->Card->PrivateNumber() : '');
		$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format($this->Order->SubTotal, 2, '.',','));
		$findReplace->Add('/\[SHIPPING\]/', "&pound;" . number_format($this->Order->TotalShipping, 2, '.',','));
		$findReplace->Add('/\[TAX\]/', "&pound;" . number_format($this->Order->TotalTax, 2, '.',','));
		$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format($this->Order->Total, 2, '.',','));
		$findReplace->Add('/\[WEIGHT\]/', $this->Weight);
		$findReplace->Add('/\[DELIVERY\]/', $this->Order->Postage->Name);
		$findReplace->Add('/\[LINES\]/', $this->LinesHtml);
		$findReplace->Add('/\[CUSTOM\]/', (($this->ShowCustom) ? $this->CustomHtml : ''));
		$findReplace->Add('/\[BOXES\]/', $this->Boxes);
		$findReplace->Add('/\[PHONE\]/', $this->Order->Customer->Contact->Person->Phone1);
		$findReplace->Add('/\[POSTAGE\]/', $this->Order->Postage->Name);
		$findReplace->Add('/\[POSTAGE_DATE\]/', date('jS F Y', strtotime($this->DespatchedOn) + ($this->Order->Postage->Days * 86400)));
		$findReplace->Add('/\[POSTAGE_QUERY_DATE\]/', date('jS F Y', strtotime($this->DespatchedOn) + ($this->Order->Postage->Days * 2 * 86400)));
		$findReplace->Add('/\[COURIER\]/', $this->Courier->Name);
		$findReplace->Add('/\[COURIER_LINK\]/', ($this->Courier->IsTrackingActive == 'Y') ? $this->Courier->URL : '');
		$findReplace->Add('/\[CONSIGNMENT\]/', ($this->Courier->IsTrackingActive == 'Y') ? $this->Consignment : '');
		$findReplace->Add('/\[NOTICES\]/', $isPartialDespatch ? $this->GetPartialDespatchNotice() : '');
		$findReplace->Add('/\[SIGNATURE\]/', $siggy);
		$findReplace->Add('/\[COUPON\]/', $coupon->Reference);
		$findReplace->Add('/\[INSTRUCTIONS\]/', $this->DeliveryInstructions);
		$findReplace->Add('/\[VAT_FREE\]/', (!empty($this->Order->TaxExemptCode) || ($this->Order->TaxRate == 0)) ? '<p align="center" style="font-size: 18px; font-weight: bold; color: #f00;">VAT FREE ORDER</p>' : '');

		$complementaryCount = 0;

		for($i=0; $i<count($this->Line); $i++) {
			if($this->Line[$i]->IsComplementary == 'Y') {
				$complementaryCount++;
			}
		}

		if(!$branded) {
			$file = file($GLOBALS["DIR_WS_ADMIN"] . 'lib/templates/print/despatch_order_unbranded.tpl');
		} elseif($this->IsIgnition) {
			if(Setting::GetValue('print_letterhead_despatch') == 'true') {
				$file = file($GLOBALS["DIR_WS_ADMIN"] . 'lib/templates/print/despatch_order_letterhead.tpl');
			} else {
				$file = file($GLOBALS["DIR_WS_ADMIN"] . 'lib/templates/print/despatch_order_greyscale.tpl');
			}
		} else {
			$file = file($GLOBALS["DIR_WS_ADMIN"] . 'lib/templates/print/despatch_order.tpl');
		}

		$html = '';
		for($i=0; $i < count($file); $i++){
			$html .= $findReplace->Execute($file[$i]);
		}

		return $html;
	}

	function GetPartialDespatchNotice(){
		$file = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/partial_despatch_note_info.tpl");
		$html = '';

		for($i=0; $i < count($file); $i++){
			$html .= $file[$i];
		}

		return $html;
	}

	function EmailConsignment(){
		if((strlen(trim($this->Consignment)) > 0) || ($this->Courier->ID > 0)) {
			$this->Courier->Get();

			if(empty($this->Order->Customer->ID)) {
				$this->Order->Get();
			}

			if(empty($this->Order->Customer->Contact->ID)) {
				$this->Order->Customer->Get();
			}

			if(empty($this->Order->Customer->Contact->Person->ID)) {
				$this->Order->Customer->Contact->Get();
			}

			$findReplace = new FindReplace();
			$findReplace->Add('/\[DESPATCH_ID\]/', $this->ID);
			$findReplace->Add('/\[ORDER_REF\]/', $this->Order->Prefix . $this->Order->ID);
			$findReplace->Add('/\[CUSTOM_REF\]/', $this->Order->CustomID);
			$findReplace->Add('/\[COURIER\]/', $this->Courier->Name);
			$findReplace->Add('/\[COURIER_LINK\]/', ($this->Courier->IsTrackingActive == 'Y') ? $this->Courier->URL : '');
			$findReplace->Add('/\[CONSIGNMENT\]/', ($this->Courier->IsTrackingActive == 'Y') ? $this->Consignment : '');

			$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_consignment.tpl");
			$orderHtml = '';

			for($i=0; $i < count($orderEmail); $i++){
				$orderHtml .= $findReplace->Execute($orderEmail[$i]);
			}

			$findReplace = new FindReplace;
			$findReplace->Add('/\[BODY\]/', $orderHtml);
			$findReplace->Add('/\[NAME\]/', $this->Order->Customer->Contact->Person->GetFullName());

			$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
			$emailBody = '';

			for($i=0; $i < count($stdTmplate); $i++){
				$emailBody .= $findReplace->Execute($stdTmplate[$i]);
			}

			$queue = new EmailQueue();
			$queue->GetModuleID('despatches');
			$queue->Subject = sprintf("%s - Consignment Update [#%s%s]", $GLOBALS['COMPANY'], $this->Order->Prefix, $this->Order->ID);
			$queue->Body = $emailBody;
			$queue->ToAddress = $this->Order->Customer->GetEmail();
			$queue->Priority = 'H';
			$queue->Type = 'H';
		
			if($this->Order->Postage->Days > 1) {
				$queue->SetSendAfter(86400);
			}

			$queue->Add();
		}
	}
	
	function SmsConsignment() {
		if(!empty($this->Consignment)) {
			$this->Courier->Get();

			$this->Order->Get();
			$this->Order->Customer->Get();
			$this->Order->Customer->Contact->Get();
			$this->Order->Customer->Contact->SendSms(sprintf('Thank you for your BLT Direct order %s%s. Your tracking code for %s is %s.', $this->Order->Prefix, $this->Order->ID, $this->Courier->Name, $this->Consignment));
		}
	}
}