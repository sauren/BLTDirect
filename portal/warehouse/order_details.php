<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderContact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Referrer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseStock.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Card.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Quote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardPDF.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierShippingCalculator.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProductPrice.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProductPriceCollection.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProForma.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'services/google-checkout/classes/GoogleRequest.php');

$session->Secure(3);

$order = new Order();

if(!isset($_REQUEST['orderid']) || !$order->Get($_REQUEST['orderid'])) {
	redirectTo('order_search.php');
}

$order->PaymentMethod->Get();
$order->SetShippingAddress();
$order->SetInvoiceAddress();
$order->GetLines();
$order->GetBids();
$order->GetShippingLines();
$order->Customer->Get();
$order->Customer->Contact->Get();
$order->GetTransactions();
$order->IsNotesUnread = 'N';


if($order->IsWarehouseDeclined == 'Y') {
	$order->IsWarehouseDeclinedRead = 'Y';
}

$order->Update();

$autoPack = $order->CheckAutomaticPack();

$suggestionQuantityPoints = array();

$data = new DataQuery(sprintf("SELECT * FROM order_suggestion_quantity ORDER BY quantityBreakPoint ASC"));
while($data->Row) {
	$suggestionQuantityPoints[] = array($data->Row['quantityCosted'], $data->Row['quantityBreakPoint']);
		
	$data->Next();
}
$data->Disconnect();

$owner = new User();

if($order->OwnedBy > 0) {
	$owner->ID = $order->OwnedBy;
	$owner->Get();
}

$creator = new User();

if($order->CreatedBy > 0) {
	$creator->ID = $order->CreatedBy;
	$creator->Get();
}

$cancelledBy = new User();

if($order->CancelledBy > 0) {
	$cancelledBy->ID = $order->CancelledBy;
	$cancelledBy->Get();
}

if($order->ParentID > 0) {
	$parent = new Order($order->ParentID);
	$parent->GetLines();
}

//UserRecent::Record(sprintf('[#%d] Order Details (%s)', $order->ID, $order->Customer->Contact->Person->GetFullName()), sprintf('order_details.php?orderid=%d', $order->ID));

$referrer = new Referrer($order->Referrer);

if($order->PaymentMethod->Reference != 'google') {
	if(isset($_REQUEST['changePostage']) && is_numeric($_REQUEST['changePostage']) && $_REQUEST['changePostage'] > 0) {
		$order->Postage->ID = $_REQUEST['changePostage'];
		$order->Recalculate();
		$order->Update();

		redirect("Location: order_details.php?orderid=" . $_REQUEST['orderid']);
	}

	if(isset($_REQUEST['shipping'])){
		if($_REQUEST['shipping'] == 'custom'){
			$order->IsCustomShipping = 'Y';
			$order->Update();
		} elseif($_REQUEST['shipping'] == 'standard'){
			$order->IsCustomShipping = 'N';
			$order->Recalculate();
		}
	}
}

$warehouseEditable = false;
$isEditable = false;

if((strtolower($order->Status) != 'despatched') && (strtolower($order->Status) != 'cancelled')){
	$warehouseEditable = true;

	if((strtolower($order->Status) != 'packing' && (strtolower($order->Status) != 'partially despatched')) ){
		$isEditable = true;
	}
}

$editableStatus = array('unread', 'pending', 'unauthenticated', 'incomplete', 'compromised');
$isEditable = (in_array(strtolower($order->Status), $editableStatus));

$despatchableStatus = array('unread', 'pending', 'packing', 'partially despatched');
$isDespatchable = (in_array(strtolower($order->Status), $despatchableStatus));

$invalidOrderStatus = array('incomplete', 'compromised', 'unauthenticated');
$isInvalidOrder = (in_array(strtolower($order->Status), $invalidOrderStatus));

if(($order->ReceivedOn == '0000-00-00 00:00:00') || (stristr($order->Status, 'unread'))){
	$order->Received();
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('orderid', 'Order ID', 'hidden', $order->ID, 'numeric_unsigned', 1, 11);
          
if($action == 'unpack') {
	$order->Unpack();
	redirect(sprintf("Location: ?orderid=%d", $_REQUEST['orderid']));
	
} elseif($action == 'downloadquote') {
	$fileName = $order->CourierQuoteFile->FileName;
	$filePath = sprintf("%s%s", $GLOBALS['ORDER_QUOTE_DOCUMENT_DIR_FS'], $fileName);
	$fileSize = filesize($filePath);

	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private", false);
	header("Content-Transfer-Encoding: binary");
	header("Content-Type: application/force-download");
	header(sprintf("Content-Length: %s", $fileSize));
	header(sprintf("Content-Disposition: attachment; filename=%s", $fileName));

	readfile($filePath);

	require_once('lib/common/app_footer.php');

} elseif($action == 'download'){
	if(isset($_REQUEST['invoiceid'])) {
		$invoice = new Invoice($_REQUEST['invoiceid']);
		$invoice->GetLines();

		$pdf = new StandardPDF();
		$pdf->WriteHTML($invoice->GetDocument(array('Template' => 'pdf_invoice')));
		$pdf->Output(sprintf('invoice_%d.pdf', $invoice->ID), 'D');
			
		exit;
	}

	redirect(sprintf("Location: ?orderid=%d", $order->ID));
	
} elseif($action == 'changepayment') {
	if(isset($_REQUEST['payment'])) {
		$order->PaymentMethod->ID = $_REQUEST['payment'];
		$order->Update();
	}

	redirect(sprintf("Location: %s?orderid=%d", $_SERVER['PHP_SELF'], $order->ID));

} elseif(($action == 'remove') && isset($_REQUEST['line'])) {
	if($order->PaymentMethod->Reference != 'google') {
		$line = new OrderLine;
		$line->Delete($_REQUEST['line']);

		$order->Recalculate();
	}

	redirect(sprintf("Location: %s?orderid=%d", $_SERVER['PHP_SELF'], $order->ID));

} elseif($action == 'changeowner') {
	if(isset($_REQUEST['ownerid'])) {
		$order->OwnedBy = $_REQUEST['ownerid'];
		$order->Update();
	}

	redirect(sprintf("Location: %s?orderid=%d", $_SERVER['PHP_SELF'], $order->ID));
	
} elseif($action == 'changeprefix') {
	if(User::UserHasAccess($GLOBALS['SESSION_USER_ID'], 1)) {
		if($GLOBALS['SESSION_USER_ID'] == 3) {
			if(isset($_REQUEST['prefix'])) {
				$order->Prefix = $_REQUEST['prefix'];
				$order->Update();
			}
		}
	}

	redirect(sprintf("Location: %s?orderid=%d", $_SERVER['PHP_SELF'], $order->ID));

} elseif($action == 'replace') {
	if($order->PaymentMethod->Reference != 'google') {
		if(isset($_REQUEST['address'])) {
			if($_REQUEST['address'] == 'billing') {
				$order->Billing = $order->Customer->Contact->Person;

				if($order->Customer->Contact->HasParent){
					$order->BillingOrg = $order->Customer->Contact->Parent->Organisation->Name;
				}
			} elseif($_REQUEST['address'] == 'shipping') {
				$order->Shipping = $order->Customer->Contact->Person;

				if($order->Customer->Contact->HasParent){
					$order->ShippingOrg = $order->Customer->Contact->Parent->Organisation->Name;
				}
			} elseif($_REQUEST['address'] == 'invoice') {
				$order->Invoice->Address->Zip = '';
				$order->Invoice->Address->City = '';
				$order->SetInvoiceAddress();
				$order->UpdateInvoiceAddress();	
			}

			$order->Recalculate();
		}
	}

	redirect(sprintf("Location: ?orderid=%d", $order->ID));

} elseif($action == "repeat") {
	if($order->PaymentMethod->Reference == 'google') {
		$order->Card = new Card();
		$order->CustomID = '';
		$order->PaymentMethod->GetByReference('card');
	}
	
	$order->OrderedOn = date('Y-m-d H:i:s');
	$order->EmailedOn = '0000-00-00 00:00:00';
	$order->EmailedTo = '';
	$order->ReceivedOn = '0000-00-00 00:00:00';
	$order->ReceivedBy = 0;
	$order->DespatchedOn = '0000-00-00 00:00:00';
	$order->InvoicedOn = '0000-00-00 00:00:00';
	$order->PaidOn = '0000-00-00 00:00:00';
	$order->Status = 'Pending';
	$order->ReturnID = 0;
	$order->AffiliateID = 0;
	$order->Add();

	for($i=0; $i < count($order->Line); $i++) {
		$order->Line[$i]->Product->Get();

		if($order->Line[$i]->Product->Discontinued == 'N') {
			$order->Line[$i]->Order = $order->ID;
			$order->Line[$i]->Status = '';
			$order->Line[$i]->DespatchID = 0;
			$order->Line[$i]->InvoiceID = 0;
			$order->Line[$i]->Add();
		}
	}

	$order->Recalculate();

	redirect(sprintf("Location: ?orderid=%d", $order->ID));

} elseif($action == "contact") {
	$contact = new OrderContact();
	$contact->OrderID = $order->ID;
	$contact->Add();

	redirect(sprintf("Location: ?orderid=%d", $order->ID));

} elseif($action == 'resend') {
	if($order->Sample == 'N') {
		$order->SendEmail();
	} else {
		$order->SendSampleEmail();
	}

	redirect(sprintf("Location: ?orderid=%d", $order->ID));

} elseif($action == 'resendinvoice') {
	$additionalEmail = $order->GetAdditioanlEmail($order->ID);
	if($order->Sample == 'N') {
		$order->SendEmail($additionalEmail);
	} else {
		$order->SendSampleEmail($additionalEmail);
	}

	redirect(sprintf("Location: ?orderid=%d", $order->ID));

} elseif($action == 'plainlabel') {
	if(isset($_REQUEST['plainlabel'])) {
		$order->IsPlainLabel = (trim(strtoupper($_REQUEST['plainlabel'])) == 'Y') ? 'Y' : 'N';
		$order->Update();
	}

	redirect(sprintf("Location: ?orderid=%d", $order->ID));
	
} elseif($action == 'taxexemptvalid') {
	if(isset($_REQUEST['taxexemptvalid'])) {
		$order->IsTaxExemptValid = (trim(strtoupper($_REQUEST['taxexemptvalid'])) == 'Y') ? 'Y' : 'N';
		$order->Update();
	}

	redirect(sprintf("Location: ?orderid=%d", $order->ID));

} elseif($action == 'securityrisk') {
	if(isset($_REQUEST['securityrisk'])) {
		$order->IsSecurityRisk = (trim(strtoupper($_REQUEST['securityrisk'])) == 'Y') ? 'Y' : 'N';
		$order->Update();
	}

	redirect(sprintf("Location: ?orderid=%d", $order->ID));
	
} elseif($action == 'awaitingcustomer') {
	if(isset($_REQUEST['awaitingcustomer'])) {
		$order->IsAwaitingCustomer = (trim(strtoupper($_REQUEST['awaitingcustomer'])) == 'Y') ? 'Y' : 'N';
		$order->Update();
	}

	redirect(sprintf("Location: ?orderid=%d", $order->ID));
	
} elseif($action == 'bidding') {
	if(isset($_REQUEST['bidding'])) {
		$order->IsBidding = (trim(strtoupper($_REQUEST['bidding'])) == 'Y') ? 'Y' : 'N';
		$order->Update();
	}

	redirect(sprintf("Location: ?orderid=%d", $order->ID));

} elseif($action == 'collection') {
	if(isset($_REQUEST['collection'])) {
		$order->IsCollection = (trim(strtoupper($_REQUEST['collection'])) == 'Y') ? 'Y' : 'N';
		$order->Update();
	}

	redirect(sprintf("Location: ?orderid=%d", $order->ID));

} elseif($action == 'undeclinepayment') {
	$order->IsDeclined = 'N';
	$order->Update();

	redirect(sprintf("Location: ?orderid=%d", $order->ID));

} elseif($action == 'unfailpayment') {
	$order->IsFailed = 'N';
	$order->Update();

	redirect(sprintf("Location: ?orderid=%d", $order->ID));
	
} elseif($action == 'undeclinewarehouse') {
	$order->IsWarehouseDeclined = 'N';
	$order->IsWarehouseUndeclined = 'Y';
	$order->IsWarehouseDeclinedRead = 'N';
	$order->Update();

	redirect(sprintf("Location: ?orderid=%d", $order->ID));

} elseif($action == 'unbackorderwarehouse') {
	$order->IsWarehouseBackordered = 'N';
	$order->Update();

	redirect(sprintf("Location: ?orderid=%d", $order->ID));

} elseif($action == 'purchasing') {
	$order->Status = 'Purchasing';
	$order->Update();

	redirect(sprintf("Location: ?orderid=%d", $order->ID));
	
} elseif($action == 'notreceivedrequest') {
	$quantities = 0;
	
	for($i=0; $i<count($order->Line); $i++) {
		$quantities += $order->Line[$i]->QuantityNotReceived;
	}
	
	if($quantities > 0) {
		$order->IsNotReceived = 'Y';
		$order->Update();

		$order->EmailNotReceivedConfirmation();

		redirect(sprintf("Location: ?orderid=%d", $order->ID));
	} else {
		$form->AddError('You must enter at least one quantity of not received products for this request.');
	}
} elseif($action == 'notreceived') {
	$order->IsNotReceived = 'N';
	$order->Update();

	if($order->PaymentMethod->Reference == 'google') {
		$order->Card = new Card();
	}

	$order->IsCustomShipping = 'Y';
	$order->TotalShipping = 0;
	$order->OrderedOn = date('Y-m-d H:i:s');
	$order->CustomID = '';
	$order->Status = 'Unread';
	$order->Prefix = 'N';
	$order->Referrer = '';
	$order->PaymentMethod->GetByReference('foc');
	$order->ParentID = $order->ID;
	$order->Add();
	
	for($i=0; $i<count($order->Line); $i++) {
		if($order->Line[$i]->QuantityNotReceived > 0) {
			$order->Line[$i]->Order = $order->ID;
			$order->Line[$i]->Quantity = $order->Line[$i]->QuantityNotReceived;
			$order->Line[$i]->DespatchID = 0;
			$order->Line[$i]->InvoiceID = 0;
			$order->Line[$i]->Status = '';
			$order->Line[$i]->DespatchedFrom->ID = 0;
			$order->Line[$i]->FreeOfCharge = 'Y';
			$order->Line[$i]->Add();
		}
	}

	$order->GetLines();
	$order->Recalculate();

	redirect(sprintf("Location: ?orderid=%d", $order->ID));

} elseif($action == 'addcustom') {
	if($isEditable) {
		$line = new OrderLine();
		$line->Quantity = 1;
		$line->Order = $order->ID;
		$line->Total = $line->Price * $line->Quantity;
		$line->Tax = $order->CalculateCustomTax($line->Total);
		$line->Add();
	}	

	redirect(sprintf("Location: ?orderid=%d", $_REQUEST['orderid']));

} elseif($action == 'addcatalogue') {
	if($isEditable) {
		$line = new OrderLine();
		$line->Quantity = 1;
		$line->Order = $order->ID;
		$line->Product->Name = 'BLT Direct Catalogue';
		$line->Total = $line->Price * $line->Quantity;
		$line->Tax = $order->CalculateCustomTax($line->Total);
		$line->Add();
	}	

	redirect(sprintf("Location: ?orderid=%d", $_REQUEST['orderid']));

} elseif($action == 'autopack') {
	if($autoPack) {
		if($isEditable) {
			$order->SetAutomaticPack();
		}
	}
} elseif($action == "dismissed") {
	$id = $order->ID; 
	$order->DismissOrder($id);
	redirect(sprintf("Location: %s?orderid=%d", $_SERVER['PHP_SELF'], $order->ID));
}

$date = '';

if($order->DeadlineOn > '0000-00-00 00:00:00') {
	$date = date('d/m/Y', strtotime($order->DeadlineOn));
}

$prefix = array('W', 'T', 'M', 'F', 'E', 'U', 'L', 'R', 'N', 'D', 'B');

if(User::UserHasAccess($GLOBALS['SESSION_USER_ID'], 1)) {
	if($GLOBALS['SESSION_USER_ID'] == 3) {
		$form->AddField('prefix', 'Prefix', 'select', $order->Prefix, 'alpha', 1, 1, true, 'onchange=changePrefix(this);');

		foreach($prefix as $prefixItem) {
			$form->AddOption('prefix', $prefixItem, $prefixItem);
		}
	}
}

$form->AddField('deadline', 'Deadline Date', 'text', $date, 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
$form->AddField('confirmednotes', 'Notes', 'textarea', '', 'anything', 1, 1024, false, 'rows="5" style="font-family: arial, sans-serif; width: 350px;"');
$form->AddField('plainlabel', 'Is Plain Label', 'checkbox', $order->IsPlainLabel, 'boolean', 1, 11, false, 'onclick="togglePlainLabel(this);"');
$form->AddField('taxexemptvalid', 'Is Tax Exempt Valid', 'checkbox', $order->IsTaxExemptValid, 'boolean', 1, 11, false, 'onclick="toggleTaxExemptValid(this);"');
$form->AddField('securityrisk', 'Is Security Risk', 'checkbox', $order->IsSecurityRisk, 'boolean', 1, 11, false, 'onclick="toggleSecurityRisk(this);"');
$form->AddField('awaitingcustomer', 'Is Awaiting Customer', 'checkbox', $order->IsAwaitingCustomer, 'boolean', 1, 11, false, 'onclick="toggleAwaitingCustomer(this);"');
$form->AddField('email', 'Email Address', 'text', $order->Customer->Contact->Person->Email, 'email', null, null, true);

$form->AddField('additionalEmail', 'AdditionalEmail Address', 'text', $order->AdditionalEmail, 'additionalEmail', null, null, false);

$form->AddField('dismissOrder', 'Order Dismissed', 'checkbox', $order->IsDismissed, 'boolean', 1, 11, false);

//$form->AddField('additionalEmail' 'Additional Email', 'text', $order->additionalEmail, 'additionalEmail', null, null, true);

if((strtolower($order->Status) == 'pending') || (strtolower($order->Status) == 'purchasing')) {
	$form->AddField('bidding', 'Is Bidding', 'checkbox', $order->IsBidding, 'boolean', 1, 1, false, 'onclick="toggleBidding(this);"');

	for($i=0; $i<count($order->Bid); $i++) {
        $form->AddField(sprintf('line_bid_%d', $order->Bid[$i]->ID), 'Bid', 'checkbox', 'N', 'boolean', 1, 1, false);
	}
}

$form->AddField('iscollection', 'Is Collection', 'checkbox', $order->IsCollection, 'boolean', 1, 11, false, 'onclick="toggleCollection(this);"');

$user = new User($session->UserID);

if(User::UserHasAccess($GLOBALS['SESSION_USER_ID'], 1)) {
	$form->AddField('ownedby', 'Owned By', 'select', $order->OwnedBy, 'numeric_unsigned', 1, 11, true, 'onchange=changeOwner(this);');
	$form->AddOption('ownedby', '0', '');

	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Name FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('ownedby', $data->Row['User_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();
}

$form->AddField('courierquoteamount', 'Courier Quote Amount', 'text', $order->CourierQuoteAmount, 'float', 1, 11, false);
$form->AddField('courierquotefile', 'Courier Quote File', 'file', '', 'file', null, null, false);

$disabled = '';

if($order->Coupon->ID > 0) {
	$order->Coupon->Get();
	if($order->Coupon->IsInvisible == 'Y') {
		$disabled = ' disabled="disabled"';
	}
}

$form->AddField('freeText', 'Free Text', 'text', $order->FreeText, 'paragraph', 0, 255, false, 'style="width:100%;"'.$disabled);
$form->AddField('freeTextValue', 'Free Text Value', 'text', (isset($order->FreeTextValue) ? $order->FreeTextValue : '0.00'), 'float', 0, 11, false, 'size="4"'.$disabled);

if($order->PaymentMethod->Reference != 'google') {
	$form->AddField('ref', 'Custom Ref', 'text', $order->CustomID, 'anything', 1, 32, false);
}

if($isEditable) {
	$form->AddField('taxexemptcode', 'Tax Exempt Code', 'text', $order->TaxExemptCode, 'anything', 0, 20, false);

	$couponId = ($order->Coupon->IsInvisible == 'Y') ? $order->OriginalCoupon->ID : $order->Coupon->ID;

	$form->AddField('coupon', 'Coupon', 'select', $couponId, 'numeric_unsigned', 0, 11, false);
	$form->AddOption('coupon', 0, 'None');

	$data = new DataQuery(sprintf("SELECT Coupon_ID, Coupon_Title, Coupon_Ref FROM coupon WHERE Is_Invisible='N' AND (Introduced_By=0 OR Coupon_ID=%d) ORDER BY Coupon_Title ASC", mysql_real_escape_string($couponId)));
	while($data->Row) {
		$form->AddOption('coupon', $data->Row['Coupon_ID'], $data->Row['Coupon_Title'] . ' ('.$data->Row['Coupon_Ref'].')');

		$data->Next();
	}
	$data->Disconnect();

    $form->AddField('payment', 'Payment Method', 'select', $order->PaymentMethod->ID, 'numeric_unsigned', 1, 11, true, 'onchange="changePaymentOptions(this);"');

    if($order->Prefix == 'R') {
        $sql = sprintf("SELECT Payment_Method_ID, Method, Reference FROM payment_method WHERE (Reference LIKE 'card' OR Reference LIKE 'credit' OR Reference LIKE 'foc' OR Reference LIKE 'pdq') ORDER BY Method ASC");
	} elseif($order->ProformaID > 0) {
		$sql = sprintf("SELECT Payment_Method_ID, Method, Reference FROM payment_method WHERE (Reference LIKE 'card' OR Reference LIKE 'credit' OR Reference LIKE 'cash' OR Reference LIKE 'transfer' OR Reference LIKE 'pdq' OR Reference LIKE 'cheque') ORDER BY Method ASC");
	} else {
		if(($order->PaymentMethod->Reference == 'card') || ($order->PaymentMethod->Reference == 'credit')) {
			$sql = sprintf("SELECT Payment_Method_ID, Method, Reference FROM payment_method WHERE (Reference LIKE 'card' OR Reference LIKE 'credit' OR Reference LIKE 'pdq') ORDER BY Method ASC");
		} else {
			$sql = sprintf("SELECT Payment_Method_ID, Method, Reference FROM payment_method WHERE Payment_Method_ID=%d ORDER BY Method ASC", mysql_real_escape_string($order->PaymentMethod->ID));
		}
	}

	$data = new DataQuery($sql);
	while($data->Row) {
		$form->AddOption('payment', $data->Row['Payment_Method_ID'], $data->Row['Method']);

		$data->Next();
	}
	$data->Disconnect();
}

$isStocked = (Setting::GetValue('lock_stocked_enable') == 'true') ? true : false;

if($isStocked) {
	for($i=0; $i<count($order->Line); $i++) {
		if($order->Line[$i]->Product->ID > 0) {
			$order->Line[$i]->Product->Get();
		}

		if(($order->Line[$i]->Product->Stocked == 'N') || ($order->Line[$i]->Product->PositionOrdersRecent > Setting::GetValue('lock_stocked_threshold')) || ($order->Line[$i]->Product->PositionOrdersRecent == 0)) {
			$isStocked = false;
		}	
	}
}

$supplierData = array();

for($i=0; $i<count($order->Line); $i++) {
	if($order->Line[$i]->Product->ID > 0) {
		if($order->Line[$i]->Product->Type == 'S') {
			if(empty($order->Line[$i]->DespatchID)) {
				$key = $order->Line[$i]->Product->ID;

				$data = new DataQuery(sprintf('SELECT * FROM warehouse%s ORDER BY Warehouse_Name ASC', ($branchOnly) ? ' WHERE Type=\'B\'' : ''));
				if($data->TotalRows > 0) {
					$supplierData[$key] = array();

					while($data->Row) {
						if($data->Row['Type'] == 'S') {
							if(!$isStocked || ($order->Line[$i]->DespatchedFrom->ID == $data->Row['Warehouse_ID']) || ($GLOBALS['SESSION_USER_ID'] == 3)) {
								$prices = new SupplierProductPriceCollection();
								$prices->GetPrices($order->Line[$i]->Product->ID, $data->Row['Type_Reference_ID']);

								$cost = $prices->GetPrice($order->Line[$i]->Quantity);
								$quantity = $prices->GetQuantity($order->Line[$i]->Quantity);

								if($quantity > 0) {
									$supplierData[$key][$data->Row['Warehouse_ID']] = array('Quantity' => $quantity, 'Cost' => $cost, 'Warehouse' => $data->Row['Warehouse_Name']);
								}
							}							
						}

						$data->Next();
					}
				}
				$data->Disconnect();
			}
		}
	}
}

$suppliers = array();

$data = new DataQuery(sprintf("SELECT w.Warehouse_ID, s.Supplier_ID, s.Is_Favourite, IF(o.Org_ID IS NULL, CONCAT_WS(' ', p.Name_First, p.Name_Last), CONCAT_WS(' ', o.Org_Name, CONCAT('(', CONCAT_WS(' ', p.Name_First, p.Name_Last), ')'))) AS Name FROM supplier AS s INNER JOIN warehouse AS w ON w.Type_Reference_ID=s.Supplier_ID AND w.Type='S' INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID ORDER BY Name ASC"));
while($data->Row) {
	$suppliers[$data->Row['Supplier_ID']] = $data->Row;

	$data->Next();
}
$data->Disconnect();

for($i=0; $i<count($order->Line); $i++) {
	if($order->Line[$i]->Product->ID > 0) {
		$order->Line[$i]->Product->Get();
		$order->Line[$i]->Product->GetDownloads();
	}
	
	if(empty($order->Line[$i]->DespatchID)) {
		if(($order->Line[$i]->DespatchedFrom->ID == 0) && ($order->Line[$i]->Product->ID > 0)) {
			$warehouseFind = new DataQuery(sprintf("SELECT w.Warehouse_ID FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B' WHERE ws.Product_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID)));
			if($warehouseFind->TotalRows > 0) {
				$order->Line[$i]->DespatchedFrom->ID = $warehouseFind->Row['Warehouse_ID'];
			}
			$warehouseFind->Disconnect();
		}
		
		$form->AddField('despatchfrom_'. $order->Line[$i]->ID, 'Despatch From', 'select', $order->Line[$i]->DespatchedFrom->ID, 'numeric_unsigned', 1, 11, true, 'class="Order_Despatched_From"' . (($order->Line[$i]->IsWarehouseFixed == 'Y') ? ' disabled="disabled"' : ''));
		$form->AddGroup('despatchfrom_'. $order->Line[$i]->ID, 'B', 'Branches');
		$form->AddGroup('despatchfrom_'. $order->Line[$i]->ID, 'S3', 'Suppliers (Stocked)');
		$form->AddGroup('despatchfrom_'. $order->Line[$i]->ID, 'S1', 'Suppliers (Costed)');
		$form->AddGroup('despatchfrom_'. $order->Line[$i]->ID, 'S2', 'Suppliers (No Costs)');

		if($order->OwnedBy == $GLOBALS['SESSION_USER_ID']) {
			if($order->Line[$i]->DespatchID == 0) {
				$form->AddField('warehousefixed_'.$order->Line[$i]->ID, 'Is Warehouse Fixed', 'checkbox', $order->Line[$i]->IsWarehouseFixed, 'boolean', 1, 1, false);
			}
		}

		$discountVal = '';

		if(!empty($order->Line[$i]->DiscountInformation)) {
			$discountCustom = explode(':', $order->Line[$i]->DiscountInformation);

			if(trim($discountCustom[0]) == 'azxcustom') {
				$discountVal = $discountCustom[1];
			}
		}

		$form->AddField('freeofcharge_'.$order->Line[$i]->ID, 'Free of Charge','checkbox',$order->Line[$i]->FreeOfCharge,'boolean',1,1,false, ($order->Line[$i]->IsComplementary == 'Y') ? 'disabled="disabled"' : $disabled);
		$form->AddField('discount_'.$order->Line[$i]->ID, 'Discount for '. (($order->Line[$i]->IsAssociative == 'N') || ($order->Line[$i]->Product->ID > 0)) ? $order->Line[$i]->Product->Name : $order->Line[$i]->AssociativeProductTitle, 'text',$discountVal,'float',0,6,false, 'size="1"'.$disabled);
		$form->AddField('handling_'.$order->Line[$i]->ID, 'Handling Charge for '. (($order->Line[$i]->IsAssociative == 'N') || ($order->Line[$i]->Product->ID > 0)) ? $order->Line[$i]->Product->Name : $order->Line[$i]->AssociativeProductTitle, 'text', $order->Line[$i]->HandlingCharge, 'float', 0, 6, false, 'size="1"');
		$form->AddField('line_warehouseshipped_'.$order->Line[$i]->ID, 'Is Warehouse Shipped', 'checkbox', $order->Line[$i]->Product->IsWarehouseShipped, 'boolean', 1, 1, false);

		if(!empty($order->Line[$i]->Product->Download)) {
			$form->AddField(sprintf('downloads_%d', $order->Line[$i]->ID), 'Spec Sheets', 'checkbox', $order->Line[$i]->IncludeDownloads, 'boolean', 1, 11, false);	
		}
	
		$branchOnly = false;

		if(($order->TotalTax == 0) && ($order->Total > 0)) {
			$branchOnly = true;
		}

		$warehouseFindMain = new DataQuery(sprintf('SELECT w.*, sp.IsUnavailable FROM warehouse AS w LEFT JOIN supplier_product AS sp ON sp.Supplier_ID=w.Type_Reference_ID AND w.Type=\'S\' AND sp.Product_ID=%d WHERE w.Warehouse_ID=%d OR (TRUE%s%s) ORDER BY w.Warehouse_Name ASC', mysql_real_escape_string($order->Line[$i]->Product->ID), mysql_real_escape_string($order->Line[$i]->DespatchedFrom->ID), $branchOnly ? ' AND w.Type=\'B\'' : '', (($order->Line[$i]->Product->DropSupplierID > 0) && ((strtotime($order->Line[$i]->Product->DropSupplierExpiresOn) > time()) || ($order->Line[$i]->Product->DropSupplierExpiresOn == '0000-00-00 00:00:00'))) ? sprintf(' AND (w.Type=\'B\' OR (w.Type=\'S\' AND w.Type_Reference_ID=%d))', mysql_real_escape_string($order->Line[$i]->Product->DropSupplierID)) : ''));
		while($warehouseFindMain->Row) {
			$warehouseText = $warehouseFindMain->Row['Warehouse_Name'];
			$hasCosting = false;
			
			if(($order->Line[$i]->IsAssociative == 'N') || ($order->Line[$i]->Product->ID > 0)) {
				$qtyStocked = 0;
				
				if($order->Line[$i]->Product->ID > 0) {
					$warehouseFind = new DataQuery(sprintf("SELECT SUM(Quantity_In_Stock) AS Quantity FROM warehouse_stock WHERE Product_ID=%d AND Warehouse_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID), mysql_real_escape_string($warehouseFindMain->Row['Warehouse_ID'])));
					if($warehouseFind->TotalRows > 0) {
						$qtyStocked = $warehouseFind->Row['Quantity'];
					}
					$warehouseFind->Disconnect();
				}

				if($warehouseFindMain->Row['Type'] == 'B') {
					$form->AddOption('despatchfrom_'.$order->Line[$i]->ID, $warehouseFindMain->Row['Warehouse_ID'], sprintf('%s [%d]', $warehouseFindMain->Row['Warehouse_Name'], $qtyStocked), ($warehouseFindMain->Row['Type'] == 'B') ? 'B' : ('S' . (($hasCosting) ? '1' : '2')));
				} else {
					if(!$isStocked || ($order->Line[$i]->DespatchedFrom->ID == $warehouseFindMain->Row['Warehouse_ID']) || ($GLOBALS['SESSION_USER_ID'] == 3)) {
						$cost = 0;
						$costNA = ($warehouseFindMain->Row['IsUnavailable'] == 'Y');
						$quantity = 0;
					
						if($order->Line[$i]->Product->Type == 'S') {
							$prices = new SupplierProductPriceCollection();
							$prices->GetPrices($order->Line[$i]->Product->ID, $warehouseFindMain->Row['Type_Reference_ID']);

							$cost = $prices->GetPrice($order->Line[$i]->Quantity);
							$quantity = $prices->GetQuantity($order->Line[$i]->Quantity);

							if($order->Line[$i]->DespatchedFrom->ID == $warehouseFindMain->Row['Warehouse_ID']) {
								if($cost == 0) {
									$order->AddSuggestion($order->Line[$i], sprintf('Obtain cost price for <strong>%s</strong>.', $warehouseFindMain->Row['Warehouse_Name']));
								} else {
									for($j=count($suggestionQuantityPoints)-1; $j>=0; $j--) {
										$check = $suggestionQuantityPoints[$j];

										if($order->Line[$i]->Quantity >= $check[1]) {
											if($quantity < $check[0]) {
												$order->AddSuggestion($order->Line[$i], sprintf('Obtain better cost price of <strong>&pound;%s</strong> for shipping at least <strong>%d</strong> quantities with <strong>%s</strong>.', $cost, $check[1], $warehouseFindMain->Row['Warehouse_Name']));
											}

											break;
										}
									}

									$key = $order->Line[$i]->Product->ID;

									if(isset($supplierData[$key])) {
										$lowerCost = false;

										foreach($supplierData[$key] as $warehouseId=>$supplierItem) {
											if($supplierItem['Cost'] < $cost) {
												$lowerCost = true;
												break;
											}
										}

										if($lowerCost) {
											$order->AddSuggestion($order->Line[$i], sprintf('Lower cost supplier available instead of <strong>%s</strong> for <strong>%s</strong>.', $warehouseFindMain->Row['Warehouse_Name'], $supplierItem['Warehouse']));
										}
									}
								}
							}

						} elseif($order->Line[$i]->Product->Type == 'G') {
							$data = new DataQuery(sprintf("SELECT Product_ID, Component_Quantity FROM product_components WHERE Component_Of_Product_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID)));
							while($data->Row) {
								$prices = new SupplierProductPriceCollection();
								$prices->GetPrices($data->Row['Product_ID'], $warehouseFindMain->Row['Type_Reference_ID']);
								
								$cost += $prices->GetPrice($order->Line[$i]->Quantity * $data->Row['Component_Quantity']) * $data->Row['Component_Quantity'];

								$data->Next();
							}
							$data->Disconnect();
						}

						if($cost > 0) {
							$hasCosting = true;
						}

						$data = new DataQuery(sprintf("SELECT SUM(quantity) AS quantity FROM warehouse_reserve WHERE productId=%d AND warehouseId=%d", mysql_real_escape_string($order->Line[$i]->Product->ID), mysql_real_escape_string($warehouseFindMain->Row['Warehouse_ID'])));
						$reserveQuantity = $data->Row['quantity'];
						$data->Disconnect();

						if($qtyStocked > 0) {
							$form->AddOption('despatchfrom_'.$order->Line[$i]->ID, $warehouseFindMain->Row['Warehouse_ID'], sprintf('%s [%d%s]%s', $warehouseFindMain->Row['Warehouse_Name'], $qtyStocked, ($costNA) ? ': &pound;N/A' : (($cost > 0) ? sprintf(': &pound;%s%s', number_format(round($cost, 2), 2, '.', ''), ($quantity > 0) ? sprintf(' - %dx', $quantity) : '') : ''), ($reserveQuantity > 0) ? sprintf(' - %dx Resv.', $reserveQuantity) : ''), ($warehouseFindMain->Row['Type'] == 'B') ? 'B' : 'S3');
						} else {
							$form->AddOption('despatchfrom_'.$order->Line[$i]->ID, $warehouseFindMain->Row['Warehouse_ID'], sprintf('%s%s', $warehouseFindMain->Row['Warehouse_Name'], ($costNA) ? ' [&pound;N/A]' : (($cost > 0) ? sprintf(' [&pound;%s%s]%s', number_format(round($cost, 2), 2, '.', ''), ($quantity > 0) ? sprintf(' - %dx', $quantity) : '', ($reserveQuantity > 0) ? sprintf(' - %dx Resv.', $reserveQuantity) : '') : '')), ($warehouseFindMain->Row['Type'] == 'B') ? 'B' : ('S' . (($hasCosting) ? '1' : '2')));
						}
					}
				}
			}

			$warehouseFindMain->Next();
		}
		$warehouseFindMain->Disconnect();

		if($order->Line[$i]->Product->ID > 0) {
			if($order->Line[$i]->Quantity >= 10) {
				$form->AddField('new_quantity_'. $order->Line[$i]->ID, sprintf('New Quantity for \'%s\'', $order->Line[$i]->Product->Name), 'text', $order->Line[$i]->Quantity, 'numeric_unsigned', 1, 11, false, 'size="3"');
				$form->AddField('new_cost_'. $order->Line[$i]->ID, sprintf('New Cost for \'%s\'', $order->Line[$i]->Product->Name), 'text', '', 'float', 1, 11, false, 'size="3"');
				$form->AddField('new_supplier_'. $order->Line[$i]->ID, sprintf('New Supplier for \'%s\'', $order->Line[$i]->Product->Name), 'select', '', 'numeric_unsigned', 1, 11, false);
				$form->AddGroup('new_supplier_'. $order->Line[$i]->ID, 'Y', 'Favourite Suppliers');
				$form->AddGroup('new_supplier_'. $order->Line[$i]->ID, 'N', 'Standard Suppliers');
				$form->AddOption('new_supplier_'. $order->Line[$i]->ID, '', '');

				foreach($suppliers as $supplier) {
					$form->AddOption('new_supplier_'. $order->Line[$i]->ID, $supplier['Supplier_ID'], $supplier['Name']);			
				}
			}
		}
	}

	if(($order->Line[$i]->IsAssociative == 'N') || ($order->Line[$i]->Product->ID > 0)) {
		$form->AddField('qty_' . $order->Line[$i]->ID, 'Quantity of ' . $order->Line[$i]->Product->Name, 'text', $order->Line[$i]->Quantity, 'numeric_unsigned', 1, 9, true, 'size="3"');
		$form->AddField('qtynotreceived_' . $order->Line[$i]->ID, 'Not Received of ' . $order->Line[$i]->Product->Name, 'text', $order->Line[$i]->QuantityNotReceived, 'numeric_unsigned', 1, 9, true, 'size="3"');
	} else {
		$form->AddField('qty_' . $order->Line[$i]->ID, 'Quantity of ' . $order->Line[$i]->AssociativeProductTitle, 'text', $order->Line[$i]->Quantity, 'numeric_unsigned', 1, 9, true, 'size="3"');
		$form->AddField('quickfind_' . $order->Line[$i]->ID, 'Quickfind of ' . $order->Line[$i]->AssociativeProductTitle, 'text', '', 'numeric_unsigned', 1, 9, false, 'size="3"');
	}

	if(($order->Line[$i]->IsAssociative == 'N') && ($order->Line[$i]->Product->ID == 0)) {
		$form->AddField('name_' . $order->Line[$i]->ID, 'Name for ' . $order->Line[$i]->Product->Name, 'textarea', $order->Line[$i]->Product->Name, 'paragraph', 1, 100, true, 'style="font-family: arial, sans-serif;"');
		$form->AddField('price_' . $order->Line[$i]->ID, 'Price for ' . $order->Line[$i]->Product->Name, 'text', $order->Line[$i]->Price, 'float', 1, 11, true, 'size="5"');
	}
}

$form->AddField('warehouses', 'Warehouses', 'select', '0', 'numeric_unsigned', 0, 11, false, 'onchange="despatch(this);"');
$form->AddOption('warehouses', '0', '-- Despatch From --');

$warehouses = array();

for($i=0; $i<count($order->Line); $i++) {
	$order->Line[$i]->DespatchedFrom->Contact->Get();
                  	
	if(empty($order->Line[$i]->DespatchID)) {
		$warehouses[$order->Line[$i]->DespatchedFrom->ID] = $order->Line[$i]->DespatchedFrom->Name;
	}
}

foreach($warehouses as $warehouseId=>$warehouseName) {
	$form->AddOption('warehouses', $warehouseId, $warehouseName);
}

if($isEditable) {
	if($order->Prefix == 'N') {
		for($i=0; $i<count($parent->Line); $i++) {
			$form->AddField(sprintf('not_received_selected_%d', $parent->Line[$i]->ID), sprintf('Select \'%s\'', $parent->Line[$i]->Product->Name), 'checkbox', 'N', 'boolean', 1, 1, false);
	        $form->AddField(sprintf('not_received_qty_%d', $parent->Line[$i]->ID), sprintf('Quantity of \'%s\'', $parent->Line[$i]->Product->Name), 'text', $parent->Line[$i]->Quantity, 'numeric_unsigned', 1, 9, true, 'size="3"');
		}
	}
}

if(isset($_REQUEST['confirm'])) {
	if(isset($_REQUEST['confirmed'])) {
		$order->ConfirmedOn = now();
		$order->ConfirmedBy = $GLOBALS['SESSION_USER_ID'];
		$order->ConfirmedNotes = $form->GetValue('confirmednotes');
		$order->Update();
		
		redirect(sprintf("Location: ?orderid=%d", $order->ID));
		
	} elseif(isset($_REQUEST['addselectednotreceived'])) {
        	for($i=0; $i<count($parent->Line); $i++) {
			if($form->GetValue(sprintf('not_received_selected_%d', $parent->Line[$i]->ID)) == 'Y') {
				$line = $order->AddLine($form->GetValue(sprintf('not_received_qty_%d', $parent->Line[$i]->ID)), $parent->Line[$i]->Product->ID);
				$line->FreeOfCharge = 'Y';
				$line->Update();
			}
		}

		$order->Recalculate();

		redirect(sprintf("Location: ?orderid=%d", $order->ID));

	} elseif(isset($_REQUEST['turnaroundpurchase'])) {
		$suppliers = array();

		for($i=0; $i<count($order->Line); $i++) {
			if(empty($order->Line[$i]->DespatchID)) {
				if(($order->Line[$i]->DespatchedFrom->ID > 0) && ($order->Line[$i]->Product->ID > 0)) {
					if($order->Line[$i]->DespatchedFrom->Type == 'B') {
						$results = $order->Line[$i]->Product->GetBest($order->Line[$i]->Quantity);

						if(isset($results['Supplier_ID'])) {
							if(!isset($suppliers[$results['Supplier_ID']])) {
								$suppliers[$results['Supplier_ID']] = array();
							}

							$suppliers[$results['Supplier_ID']][] = array('Line' => $order->Line[$i], 'Results' => $results);
						} else {
							$form->AddError(sprintf('Cannot turnaround purchase \'%s\' due to missing supplier costings.', $order->Line[$i]->Product->Name));
						}
					}
				}
			}
		}

		if($form->Valid) {
			$session->User->Get();
			$session->User->Branch->Get();

			$data = new DataQuery(sprintf("SELECT Warehouse_ID FROM warehouse WHERE Type='B' AND Type_Reference_ID=%d", mysql_real_escape_string($session->User->Branch->ID)));
			$warehouseId = $data->Row['Warehouse_ID'];
			$data->Disconnect();

			foreach($suppliers as $supplierId=>$supplierData) {
				$supplier = new Supplier($supplierId);
				$supplier->Contact->Get();

				$purchase = new Purchase();
				$purchase->SupplierID = $supplier->ID;
				$purchase->Type = 'Turnaround';
				$purchase->PurchasedOn = getDatetime();
				$purchase->Status = 'Unfulfilled';
				$purchase->Supplier = $supplier->Contact->Person;
				$purchase->SupOrg = ($supplier->Contact->HasParent)? $supplier->Contact->Parent->Organisation->Name: '';

				$data = new DataQuery(sprintf("SELECT o.Fax FROM person AS p INNER JOIN contact AS c ON c.Person_ID=p.Person_ID INNER JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID INNER JOIN organisation AS o ON o.Org_ID=c2.Org_ID WHERE p.Person_ID=%d", mysql_real_escape_string($supplier->Contact->Person->ID)));
				$purchase->Supplier->Fax = $data->Row['Fax'];
				$data->Disconnect();

				$purchase->Person = $session->User->Person;
				$purchase->Person->Address = $session->User->Branch->Address;
				$purchase->Organisation = $session->User->Branch->Name;
				$purchase->Branch = $session->User->Branch->ID;
				$purchase->Warehouse->ID = $warehouseId;
				$purchase->Add();

				foreach($supplierData as $lineData) {
					$line = new PurchaseLine();
					$line->Product = $lineData['Line']->Product;
					$line->Quantity = $lineData['Line']->Quantity;
					$line->QuantityDec = $line->Quantity;
					$line->Purchase = $purchase->ID;
					$line->SuppliedBy = $supplierId;
					$line->Cost = $lineData['Results']['Cost'];
					$line->SKU = $lineData['Results']['Supplier_SKU'];

					$data = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>'' LIMIT 0, 1", mysql_real_escape_string($purchase->Warehouse->ID), mysql_real_escape_string($line->Product->ID)));
					if($data->TotalRows > 0) {
						$line->Location = $data->Row['Shelf_Location'];
					}
					$data->Disconnect();

					$line->Add();
				}
			}

			redirect(sprintf("Location: ?orderid=%d&turnaroundsuccess", $order->ID));

		}
	} elseif(isset($_REQUEST['acceptbids'])) {
		for($i=0; $i<count($order->Bid); $i++) {
			if($form->GetValue(sprintf('line_bid_%d', $order->Bid[$i]->ID)) == 'Y') {
				$price = new SupplierProduct();
	            		$price->Supplier->ID = $order->Bid[$i]->Supplier->ID;
				$price->Product->ID = $order->Bid[$i]->Product->ID;
				$price->Cost = $order->Bid[$i]->CostBid;
				$price->Add();

				$order->Bid[$i]->IsAccepted = 'Y';
				$order->Bid[$i]->Update();
			}
		}
		
		$order->Recalculate();

		redirect(sprintf('Location: ?orderid=%d', $order->ID));
		
	} elseif(isset($_REQUEST['updatenotreceived'])) {
		for($i=0; $i < count($order->Line); $i++){
			$order->Line[$i]->QuantityNotReceived = $form->GetValue('qtynotreceived_' . $order->Line[$i]->ID);	
			$order->Line[$i]->Update();
		}
		
		redirect(sprintf("Location: ?orderid=%d", $order->ID));

	} else {
		if($action == 'confirm payment'){
			if($order->PaymentMethod->Reference == 'google') {
				$googleRequest = new GoogleRequest();

				if(!$googleRequest->chargeOrder($order->CustomID, $order->ID, $order->Total)) {
					$form->AddError($googleRequest->ErrorMessage);
				} else {
					redirect(sprintf("Location: %s?orderid=%d", $_SERVER['PHP_SELF'], $order->ID));
				}
			}

		} elseif(($action == 'pack')) {
			$form->Validate();

			if($form->Valid){
				for($i=0; $i < count($order->Line); $i++) {
					if(($order->Line[$i]->IsAssociative == 'Y') && ($order->Line[$i]->Product->ID == 0)) {
						$pid = trim($form->GetValue('quickfind_'.$order->Line[$i]->ID));

						if((strlen($pid) > 0) && is_numeric($pid)) {
							$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM product WHERE Product_ID=%d", mysql_real_escape_string($pid)));
							if($data->Row['Count'] == 0) {
								$form->AddError(sprintf('Quickfind of %d for %s does not exist.', $pid, $order->Line[$i]->AssociativeProductTitle));
							}
							$data->Disconnect();
						}
					}

					if(($form->GetValue('handling_'.$order->Line[$i]->ID) < 0) || ($form->GetValue('handling_'.$order->Line[$i]->ID) > 100)) {
						$form->AddError(sprintf('Handling Charge for %s must be between 0 and 100%%', ($order->Line[$i]->Product->ID > 0) ? $order->Line[$i]->Product->Name : $order->Line[$i]->AssociativeProductTitle), 'handling_'.$order->Line[$i]->ID);
					}
				}
			}

			if($form->Valid) {
				if(User::UserHasAccess($GLOBALS['SESSION_USER_ID'], 1)) {
					if($GLOBALS['SESSION_USER_ID'] == 3) {
						$order->Prefix = $form->GetValue('prefix');
					}
				}
				
				for($i=0; $i < count($order->Line); $i++){
					if(empty($order->Line[$i]->DespatchID)) {
						if(is_numeric($form->GetValue('qty_' . $order->Line[$i]->ID)) && ($order->Line[$i]->Quantity != $form->GetValue('qty_' . $order->Line[$i]->ID)) && $form->GetValue('qty_' . $order->Line[$i]->ID) > 0) {
							$order->Line[$i]->Quantity = $form->GetValue('qty_' . $order->Line[$i]->ID);
						}
						
						if(($order->Line[$i]->IsAssociative == 'Y') && ($order->Line[$i]->Product->ID == 0)) {
							$pid = trim($form->GetValue('quickfind_'.$order->Line[$i]->ID));

							if((strlen($pid) > 0) && is_numeric($pid)) {
								$order->Line[$i]->Product->Get($pid);
								$order->Line[$i]->Product->AssociativeProductTitle = $order->Line[$i]->AssociativeProductTitle;
								$order->Line[$i]->Product->Update();

								$order->Line[$i]->AssociativeProductTitle = '';

								if($order->Line[$i]->Price < $order->Line[$i]->Product->PriceCurrent) {
									$discount = number_format(100 - (($order->Line[$i]->Price / $order->Line[$i]->Product->PriceCurrent) * 100), 2, '.', '');

									$order->Line[$i]->DiscountInformation = sprintf('azxcustom:%s', $discount);
									$order->Line[$i]->Discount = ($order->Line[$i]->Product->PriceCurrent / 100) * $discount;
								}
							}
						}

						if(!empty($order->Line[$i]->Product->Download)) {
							$order->Line[$i]->IncludeDownloads = $form->GetValue(sprintf('downloads_%d', $order->Line[$i]->ID));
						}

						if(($order->Line[$i]->IsAssociative == 'N') && ($order->Line[$i]->Product->ID == 0)) {
							$order->Line[$i]->Product->Name = $form->GetValue('name_' . $order->Line[$i]->ID);
							$order->Line[$i]->Price = $form->GetValue('price_' . $order->Line[$i]->ID);
						}
						
						$order->Line[$i]->HandlingCharge = $form->GetValue('handling_'.$order->Line[$i]->ID);
						$order->Line[$i]->DespatchedFrom->ID = $form->GetValue('despatchfrom_'.$order->Line[$i]->ID);

						if($order->OwnedBy == $GLOBALS['SESSION_USER_ID']) {
							if($order->Line[$i]->DespatchID == 0) {
								$order->Line[$i]->IsWarehouseFixed = $form->GetValue('warehousefixed_'.$order->Line[$i]->ID);
							}
						}

						$order->Line[$i]->Update();
					}
				}

				if(isset($_REQUEST['setShipping'])){
					$order->TotalShipping = $_REQUEST['setShipping'];
					$order->Update();
				}

				if($order->PaymentMethod->Reference != 'google') {
					$order->Recalculate();
				}
			}

			if($form->Valid) {
				$unassociatedProducts = 0;

				for($i=0;$i<count($order->Line);$i++) {
					if(($order->Line[$i]->IsAssociative == 'Y') && ($order->Line[$i]->Product->ID == 0)) {
						$unassociatedProducts++;
					}
				}

				if($unassociatedProducts > 0) {
					$form->AddError("Cannot pack this order until all unassociated products are mapped.");
				}
			}

			if($form->Valid) {
				$order->PaymentMethod->ID = $form->GetValue('payment');
				$order->Update();
				$order->Pack();

				//Check if the quantity has been increased to a high amount
				$paymentLookupSql = sprintf("select * from payment where Order_ID=%d && `Transaction_Type` = 'Authenticate' ORDER BY Created_ON DESC",mysql_real_escape_string($order->ID));
				$paymentLookup = new DataQuery($paymentLookupSql);
				$orderAmount = $paymentAmount = $order->Total;
				if($paymentLookup->TotalRows > 0){
					$paymentAmount = $paymentLookup->Row['Amount'];
				}
				if(($orderAmount - $paymentAmount  > ($paymentAmount * ((strtotime($order->CreatedOn) < strtotime('2010-01-01 00:00:00')) ? 0.150 : ((strtotime($order->CreatedOn) < strtotime('2011-01-04 00:00:00')) ? 0.175 : 0.2))))){
					redirect("Location: order_takePayment.php?orderid=".$order->ID);
				}else{
					redirect(sprintf('Location: ?orderid=%d', $order->ID));
				}
			}
		} elseif($action == 'update') {
			$form->Validate();

			if($form->Valid) {
				for($i=0; $i < count($order->Line); $i++) {
					if($form->GetValue('handling_'.$order->Line[$i]->ID)) {
						if(($form->GetValue('handling_'.$order->Line[$i]->ID) < 0) || ($form->GetValue('handling_'.$order->Line[$i]->ID) > 100)) {
							$form->AddError(sprintf('Handling Charge for %s must be between 0 and 100%%', (($order->Line[$i]->IsAssociative == 'N') || ($order->Line[$i]->Product->ID > 0)) ? $order->Line[$i]->Product->Name : $order->Line[$i]->AssociativeProductTitle), 'handling_'.$order->Line[$i]->ID);
						}
					}
				}
			}

			if($form->Valid) {
				for($i=0; $i < count($order->Line); $i++) {
					if(($order->Line[$i]->IsAssociative == 'Y') && ($order->Line[$i]->Product->ID == 0)) {
						$pid = trim($form->GetValue('quickfind_'.$order->Line[$i]->ID));

						if((strlen($pid) > 0) && is_numeric($pid)) {
							$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM product WHERE Product_ID=%d", mysql_real_escape_string($pid)));
							if($data->Row['Counter'] == 0) {
								$form->AddError(sprintf('Quickfind of %d for %s does not exist.', $pid, $order->Line[$i]->AssociativeProductTitle));
							}
							$data->Disconnect();
						}
					}
				}
			}

			if($form->Valid) {
				if(User::UserHasAccess($GLOBALS['SESSION_USER_ID'], 1)) {
					if($GLOBALS['SESSION_USER_ID'] == 3) {
						$order->Prefix = $form->GetValue('prefix');
					}
				}
				$order->IsDismissed = $form->GetValue('dismissOrder');
				$order->CourierQuoteAmount = $form->GetValue('courierquoteamount');
				$unbackorderLines = array();

				for($i=0; $i < count($order->Line); $i++) {
					if(empty($order->Line[$i]->DespatchID)) {
						if($order->Line[$i]->Product->ID > 0) {
							$order->Line[$i]->Product->Get();
							$order->Line[$i]->Product->IsWarehouseShipped = $form->GetValue('line_warehouseshipped_' . $order->Line[$i]->ID);
							$order->Line[$i]->Product->Update();
						}

						if($order->PaymentMethod->Reference != 'google') {
							if(($order->Status != 'Cancelled') && ($order->Status != 'Despatched') && ($order->Status != 'Partially Despatched') && ($order->Status != 'Packing')) {
								if(($order->Line[$i]->IsAssociative == 'N') || ($order->Line[$i]->Product->ID > 0)) {
									if(is_numeric($form->GetValue('qty_' . $order->Line[$i]->ID)) && ($order->Line[$i]->Quantity != $form->GetValue('qty_' . $order->Line[$i]->ID)) && $form->GetValue('qty_' . $order->Line[$i]->ID) > 0) {
										$order->Line[$i]->Quantity = $form->GetValue('qty_' . $order->Line[$i]->ID);
									}
								} elseif(is_numeric($form->GetValue('qty_' . $order->Line[$i]->ID)) && ($order->Line[$i]->Quantity != $form->GetValue('qty_' . $order->Line[$i]->ID)) && $form->GetValue('qty_' . $order->Line[$i]->ID) > 0) {
									$order->Line[$i]->Quantity = $form->GetValue('qty_' . $order->Line[$i]->ID);
								}

								if($order->Line[$i]->IsComplementary == 'N') {
									$order->Line[$i]->FreeOfCharge = $form->GetValue('freeofcharge_'.$order->Line[$i]->ID);
								} else {
									$order->Line[$i]->FreeOfCharge = 'Y';
								}

								$discountVal = $form->GetValue('discount_'.$order->Line[$i]->ID);

								if(strlen($discountVal) > 0) {
									$order->Line[$i]->DiscountInformation = 'azxcustom:'.$discountVal;
								} else {
									$order->Line[$i]->DiscountInformation = '';
								}
							}
						}
						
						if(($order->Line[$i]->IsAssociative == 'Y') && ($order->Line[$i]->Product->ID == 0)) {
							$pid = trim($form->GetValue('quickfind_'.$order->Line[$i]->ID));

							if((strlen($pid) > 0) && is_numeric($pid)) {
								$order->Line[$i]->Product->Get($pid);
								$order->Line[$i]->Product->AssociativeProductTitle = $order->Line[$i]->AssociativeProductTitle;
								$order->Line[$i]->Product->Update();

								$order->Line[$i]->AssociativeProductTitle = '';

								if($order->Line[$i]->Price < $order->Line[$i]->Product->PriceCurrent) {
									$discount = number_format(100 - (($order->Line[$i]->Price / $order->Line[$i]->Product->PriceCurrent) * 100), 2, '.', '');

									$order->Line[$i]->DiscountInformation = sprintf('azxcustom:%s', $discount);
									$order->Line[$i]->Discount = ($order->Line[$i]->Product->PriceCurrent / 100) * $discount;
								}
							}
						}

						if($order->Line[$i]->DespatchedFrom->ID != $form->GetValue('despatchfrom_'.$order->Line[$i]->ID)) {
							if($order->Line[$i]->BackorderExpectedOn != '0000-00-00 00:00:00') {
								if($order->Line[$i]->DespatchedFrom->Type == 'S') {
									if(!isset($unbackorderLines[$order->Line[$i]->DespatchedFrom->Contact->ID])) {
										$unbackorderLines[$order->Line[$i]->DespatchedFrom->Contact->ID] = array();
									}

									$unbackorderLines[$order->Line[$i]->DespatchedFrom->Contact->ID][] = $order->Line[$i];
								}
							}

							$order->Line[$i]->BackorderExpectedOn = '0000-00-00 00:00:00';
							$order->Line[$i]->Status = '';
						}
						
						if(!empty($order->Line[$i]->Product->Download)) {
							$order->Line[$i]->IncludeDownloads = $form->GetValue(sprintf('downloads_%d', $order->Line[$i]->ID));
						}

						if(($order->Line[$i]->IsAssociative == 'N') && ($order->Line[$i]->Product->ID == 0)) {
							$order->Line[$i]->Product->Name = $form->GetValue('name_' . $order->Line[$i]->ID);
							$order->Line[$i]->Price = $form->GetValue('price_' . $order->Line[$i]->ID);
						}

						$order->Line[$i]->HandlingCharge = $form->GetValue('handling_'.$order->Line[$i]->ID);
						$order->Line[$i]->DespatchedFrom->ID = $form->GetValue('despatchfrom_'.$order->Line[$i]->ID);

						if($order->OwnedBy == $GLOBALS['SESSION_USER_ID']) {
							if($order->Line[$i]->DespatchID == 0) {
								$order->Line[$i]->IsWarehouseFixed = $form->GetValue('warehousefixed_'.$order->Line[$i]->ID);
							}
						}
						
						$order->Line[$i]->Update();
					}

					$stock = new WarehouseStock();

					if($stock->GetViaWarehouseProduct($order->Line[$i]->DespatchedFrom->ID, $order->Line[$i]->Product->ID)) {
						if(($stock->IsBackordered == 'Y') && (strtotime($stock->BackorderExpectedOn) > time())) {
							$order->IsWarehouseBackordered = 'Y';
						}
					}
				}

				if(isset($_REQUEST['setShipping'])){
					$order->TotalShipping = $_REQUEST['setShipping'];
				}

				$order->Customer->Contact->Person->Email = $form->GetValue('email');
				$order->Customer->Contact->Person->Update();

				$order->AdditionalEmail = $form->GetValue('additionalEmail');
				$order->Update();
				
				if($order->PaymentMethod->Reference != 'google') {
					$order->TaxExemptCode = isset($_REQUEST['taxexemptcode']) ? $_REQUEST['taxexemptcode'] : '';

					if($isEditable) {
						$order->Coupon->ID = $form->GetValue('coupon');
					}

					if($order->PaymentMethod->Reference != 'google') {
						$order->CustomID = $form->GetValue('ref');
					}

					$order->FreeText = $form->GetValue('freeText');
					$order->FreeTextValue = $form->GetValue('freeTextValue');
					$order->DeadlineOn = (strlen($form->GetValue('deadline')) > 0) ? date('Y-m-d 00:00:00', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('deadline'), 6, 4), substr($form->GetValue('deadline'), 3, 2), substr($form->GetValue('deadline'), 0, 2)))) : '0000-00-00 00:00:00';
					$order->Recalculate();
				}

				if($isEditable) {
					$order->PaymentMethod->ID = $form->GetValue('payment');
				}

				$order->UpdateQuote('courierquotefile');
				$order->Update();

				if($order->IsWarehouseDeclined == 'N') {
					if($order->IsDeclined == 'N') {
						if($order->IsFailed == 'N') {
							$order->NotifyUnbackorder($unbackorderLines);
						}
					}
				}

				//Check if the quantity has been increased to a high amount
				$paymentLookupSql = sprintf("select * from payment where Order_ID=%d && `Transaction_Type` = 'Authenticate' ORDER BY Created_ON DESC", mysql_real_escape_string($order->ID));
				$paymentLookup = new DataQuery($paymentLookupSql);
				$orderAmount = $paymentAmount = $order->Total;
				if($paymentLookup->TotalRows > 0){
					$paymentAmount = $paymentLookup->Row['Amount'];
				}

				if(($orderAmount - $paymentAmount > ($paymentAmount * ((strtotime($order->CreatedOn) < strtotime('2010-01-01 00:00:00')) ? 0.150 : ((strtotime($order->CreatedOn) < strtotime('2011-01-04 00:00:00')) ? 0.175 : 0.2))))){
					redirect("Location: order_takePayment.php?orderid=".$order->ID);
				}else{
					redirect("Location: order_details.php?orderid=". $order->ID);
				}
			}
		} else {
			for($i=0; $i<count($order->Line); $i++) {
				if($order->Line[$i]->Product->ID > 0) {
					if(empty($order->Line[$i]->DespatchID)) {
						if(isset($_REQUEST['newcost-' . $order->Line[$i]->ID . '_x'])) {
							$form->InputFields['new_quantity_' . $order->Line[$i]->ID]->Required = true;
							$form->InputFields['new_cost_' . $order->Line[$i]->ID]->Required = true;
							$form->InputFields['new_supplier_' . $order->Line[$i]->ID]->Required = true;

							$form->Validate('new_quantity_' . $order->Line[$i]->ID);
							$form->Validate('new_cost_' . $order->Line[$i]->ID);
							$form->Validate('new_supplier_' . $order->Line[$i]->ID);

							if($form->Valid) {
								$supplierPrice = new SupplierProductPrice();
								$supplierPrice->Supplier->ID = $form->GetValue('new_supplier_' . $order->Line[$i]->ID);
								$supplierPrice->Product->ID = $order->Line[$i]->Product->ID;
								$supplierPrice->Quantity = $form->GetValue('new_quantity_' . $order->Line[$i]->ID);
								$supplierPrice->Cost = $form->GetValue('new_cost_' . $order->Line[$i]->ID);
								$supplierPrice->Add();

								$order->Line[$i]->DespatchedFrom->ID = $suppliers[$supplierPrice->Supplier->ID]['Warehouse_ID'];
								$order->Line[$i]->Update();

								$order->Recalculate();
								
								redirect(sprintf('Location: ?orderid=%d', $order->ID));
							}

							break;
						}
					}
				}
			}									
		}
	}
}

$script = sprintf('<script language="javascript" type="text/javascript">
	var togglePlainLabel = function(obj) {
		var isPlainLabel = \'N\';

		if(obj.checked) {
			isPlainLabel = \'Y\';
		} else {
			isPlainLabel = \'N\';
		}

		window.self.location.href = \'%s?orderid=%d&action=plainlabel&plainlabel=\' + isPlainLabel;
	}
	</script>', $_SERVER['PHP_SELF'], $order->ID);
	
$script .= sprintf('<script language="javascript" type="text/javascript">
	var toggleTaxExemptValid = function(obj) {
		var isTaxExemptValid = \'N\';

		if(obj.checked) {
			isTaxExemptValid = \'Y\';
		} else {
			isTaxExemptValid = \'N\';
		}

		window.self.location.href = \'%s?orderid=%d&action=taxexemptvalid&taxexemptvalid=\' + isTaxExemptValid;
	}
	</script>', $_SERVER['PHP_SELF'], $order->ID);

$script .= sprintf('<script language="javascript" type="text/javascript">
	var toggleSecurityRisk = function(obj) {
		var isSecurityRisk = \'N\';

		if(obj.checked) {
			isSecurityRisk = \'Y\';
		} else {
			isSecurityRisk = \'N\';
		}

		window.self.location.href = \'%s?orderid=%d&action=securityrisk&securityrisk=\' + isSecurityRisk;
	}
	</script>', $_SERVER['PHP_SELF'], $order->ID);

$script .= sprintf('<script language="javascript" type="text/javascript">
	var toggleAwaitingCustomer = function(obj) {
		window.self.location.href = \'?orderid=%d&action=awaitingcustomer&awaitingcustomer=\' + (obj.checked ? \'Y\' : \'N\');
	}
	</script>', $order->ID);
	
$script .= sprintf('<script language="javascript" type="text/javascript">
	var toggleBidding = function(obj) {
		if(obj.checked) {
			isBidding = \'Y\';
		} else {
			isBidding = \'N\';
		}

		window.self.location.href = \'?orderid=%d&action=bidding&bidding=\' + isBidding;
	}
	</script>', $order->ID);

$script .= sprintf('<script language="javascript" type="text/javascript">
	var toggleCollection = function(obj) {
		window.self.location.href = \'?orderid=%d&action=collection&collection=\' + ((obj.checked) ? \'Y\' : \'N\');
	}
	</script>', $order->ID);

$script .= sprintf('<script language="javascript" type="text/javascript">
	var changeOwner = function(obj) {
		window.self.location.href = \'?orderid=%d&action=changeowner&ownerid=\' + obj.value;
	}
	</script>', $order->ID);

$script .= sprintf('<script language="javascript" type="text/javascript">
	var changePrefix = function(obj) {
		window.self.location.href = \'?orderid=%d&action=changeprefix&prefix=\' + obj.value;
	}
	</script>', $order->ID);
	
$script .= sprintf('<script language="javascript" type="text/javascript">
	var changePaymentOptions = function(obj) {
		window.location.href = \'order_details.php?orderid=%d&action=changepayment&payment=\' + obj.value;
	}
	</script>', $order->ID);

$script .= sprintf('<script language="javascript" type="text/javascript">
	var toggleLine = function(id) {
		var e = document.getElementById(\'line-\' + id);

		if(e) {
			e.style.display = (e.style.display == \'none\') ? \'table-row\' : \'none\';
		}		
	}
	</script>', $order->ID);

$style = '';

if(($order->Status != 'Despatched') && ($order->Status != 'Cancelled')) {
	if(($order->Postage->Days == 1) && (time() > strtotime(date(sprintf('%s %s:00', substr($order->CreatedOn, 0, 10), $order->Postage->CuttOffTime))))) {
		$style .= '<style>body { background-image: url(images/bg_watermark_deadline_missed.gif); }</style>';
	}
}

if(isset($_REQUEST['returnid']) && !empty($_REQUEST['returnid'])) {
	$rid = $_REQUEST['returnid'];
	$page = new Page("<a href=\"return_details.php?id=$rid\">Return #$rid</a> &gt;" . sprintf('%s%s Order Details for %s', $order->Prefix, $order->ID, $order->Customer->Contact->Person->GetFullName()), '');
} else {
	$page = new Page(sprintf('[#%s%s] Order Details for %s', $order->Prefix, $order->ID, $order->Customer->Contact->Person->GetFullName()), '');
}

$page->AddToHead('<script language="javascript" type="text/javascript" src="js/scw.js"></script>');
$page->AddToHead($style);
$page->AddToHead($script);
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('orderid');

// massive hack to adjust totals with regards to discount reward
$subTotal = ($order->SubTotal-$order->Discount)-$order->DiscountReward;
if($subTotal < 0) {
	$subTotal = 0;
}
?>
<script language="javascript" type="text/javascript">
if(<?php echo ($order->HasAlerts()) ? 'true' : 'false'; ?>){
	popUrl('order_alerts.php?oid=<?php echo $order->ID; ?>', 500, 400);
}

function changeDelivery(num){
	if(num == ''){
		alert('Please Select a Delivery Option');
	} else {
		window.location.href = '?orderid=<?php echo $order->ID; ?>&changePostage=' + num;
	}
}

function despatch(obj) {
	if(obj.value > 0) {
		popUrl('order_despatch.php?orderid=<?php echo $order->ID; ?>&warehouseid=' + obj.value, 800, 600);
	}
}
</script>

<?php 
	if($isInvalidOrder){ 
		$hasSimilar = $order->customerHasSimilar();
		$lookedForSimilar = ($hasSimilar)? sprintf('the customer has %d similar orders', $hasSimilar) : 'it would appear they do not have any similar orders';
?>
<div class="info">
	<h1>This Order is <?php echo $order->Status; ?></h1>
	<p>IMPORTANT: This order reached the payment stage during checkout, but never successfully received payment details.<br />
		Do not perform any other operations on this order till this notice is removed.</p>
	<p>Before you adopt this order:</p>
	<ol>
		<li>Ensure the order is at least 15 minutes old. I checked and it was created  <?php echo fromNow($order->CreatedOn, 'i'); ?></li>
		<li><p>Check the customer has not already successfully ordered the below items under a different Order Reference.<br />
			I had a quick look for you and <?php echo $lookedForSimilar; ?> at &pound;<?php echo $order->Total; ?><br />
			<a href="customer_orders.php?customer=<?php echo $order->Customer->ID; ?>">Check customer orders</a></p> </li>
		<li>If not please contact the customer to obtain <a href="paymentServer.php?action=change&orderid=<?php echo $order->ID; ?>">payment details</a> if they wish to proceed with their order</li>
	</ol>
	<br />
	<p><input type="button" name="card" value="add card details" class="btn" onclick="window.location.href='paymentServer.php?action=change&orderid=<?php echo $order->ID; ?>'" /></p>
</div>
<br />
<?php } ?>

<?php if($order->Status=='Incomplete' || $order->Status == 'Unauthenticated' || $order->Status == 'Compromised') {?>
	

	<div class="info">
		<?php if($order->IsDismissed == 'Y'){?>
			<h1>Order Dismissed</h1>
			<p>This order has been dealt with and dismissed</p>
		<?php } else { ?>
			<h1>Order Dismissal</h1>
			<p>If the customers order has been dealt with via phone, email or other means you may dismiss the order</p>
			<p><input type="button" name="dismissal" value="Dismiss an order" class="btn" onclick="window.self.location.href = 'order_details.php?orderid=<?php echo $order->ID; ?>&action=dismissed';" /></p>
		<?php }?>
	</div>
	<br />
<?php } ?>



<?php
//look up the latest payment
$paymentLookupSql = sprintf("select * from payment where Order_ID=%d && `Transaction_Type` = 'Authenticate' ORDER BY Created_ON DESC", mysql_real_escape_string($order->ID));
$paymentLookup = new DataQuery($paymentLookupSql);
$orderAmount = $paymentAmount = $order->Total;
if($paymentLookup->TotalRows > 0){
	$paymentAmount = $paymentLookup->Row['Amount'];
}
if(($orderAmount - $paymentAmount > ($paymentAmount * ((strtotime($order->CreatedOn) < strtotime('2010-01-01 00:00:00')) ? 0.150 : ((strtotime($order->CreatedOn) < strtotime('2011-01-04 00:00:00')) ? 0.175 : 0.2))))){
?>
<table class="error" cellspacing="0">
  <tr>
    <td valign="top"><img src="images/icon_alert_2.gif" width="16" height="16" align="absmiddle">	<strong>Shipping Information Needed:</strong><br>
	<strong>IMPORTANT</strong> the total cost of this order is 15% more than what was initially authorised on the
	customers card. You <strong>MUST</strong> take the credit card details again to reauthorise
	the amount. Please fill out the form <a href="order_takePayment.php?orderid=<?php echo $order->ID; ?>"><strong><span style="text-decoration: underline;">here</span></strong></a> to do so. If this is not done then there is a strong
	chance that the money will not be able to be taken upon despatch.
    </td>
  </tr>
</table>
<br />
<?php } ?>

<?php
if(isset($_REQUEST['postage']) && $_REQUEST['postage'] == 'error') {
	$order->CalculateShipping();

	if($order->Error) {
?>
<table class="error" cellspacing="0">
  <tr>
    <td valign="top"><img src="images/icon_alert_2.gif" width="16" height="16" align="absmiddle">	<strong>Shipping Information Not Found:</strong><br>
	Sorry could not find any shipping settings for this location. Please change shipping location. <a href="order_changeAddress.php?orderid=<?php echo $order->ID; ?>&type=shipping">Click Here</a>
    </td>
  </tr>
</table>
<br />
<?php
	} else {
?>
<table class="error" cellspacing="0">
  <tr>
    <td valign="top"><img src="images/icon_alert_2.gif" width="16" height="16" align="absmiddle">	<strong>Shipping Information Needed:</strong><br>
	Please select an Appropriate Shipping Option: <?php echo $order->PostageOptions; ?>
    </td>
  </tr>
</table>
<br />
<?php
	}
}

if(($order->Postage->Days == 1) && (strtolower($order->Status) != 'despatched') && (strtolower($order->Status) != 'cancelled')) {
	$bubble = new Bubble('Next Day Delivery', 'This customer has requested next day delivery.');

	echo $bubble->GetHTML();
	echo '<br />';
}

if($order->TaxRate == 0) {
	$bubble = new Bubble('Zero Tax Order', 'This order currently has no tax value and must be shipped directly from a BLT branch to obtain proof of postage.');

	echo $bubble->GetHTML();
	echo '<br />';
}

if($order->IsTaxExemptValid == 'N') {
	if(!empty($order->TaxExemptCode)) {
		$bubble = new Bubble('Unverified Exemption Code ', 'The tax exemption code of this order is not yet verified.');

		echo $bubble->GetHTML();
		echo '<br />';
	}
}

if(!$order->HasInvoiceAddress()) {
	$bubble = new Bubble('Missing Invoice Details', 'This order cannot be packed or despatched due to missing invoice address details.');

	echo $bubble->GetHTML();
	echo '<br />';
}

$isDangerous = false;

for($i=0; $i < count($order->Line); $i++) {
	if($order->Line[$i]->Product->IsDangerous == 'Y') {
		$isDangerous = true;
		break;
	}
}

if($isDangerous && ($order->ConfirmedOn == '0000-00-00 00:00:00')) {
	$html = 'Please ring the customer to confirm their placement of the dangerous items in this order.<br />You cannot pack this order until the customer has confirmed the products required.<br /><br />';
	$html .= sprintf('<strong>%s</strong><br />', $form->GetLabel('confirmednotes'));
	$html .= sprintf('%s<br /><br />', $form->GetHTML('confirmednotes'));
	$html .= '<input name="confirmed" class="btn" type="submit" value="confirmed" />';
	
	$bubble = new Bubble('Dangerous Order', $html);

	echo $bubble->GetHTML();
	echo '<br />';
}

if(isset($_REQUEST['turnaroundsuccess'])) {
	$bubble = new Bubble('Turnaround Purchase Created', 'A number of new turnaround purchase orders for branch products has been created.');

	echo $bubble->GetHTML();
	echo '<br />';
}
?>

<table width="100%"  border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td valign="top">

    <?php if($order->Sample == 'N') { ?>
      <p><a href="order_transactions.php?orderid=<?php echo $order->ID; ?>">View Transactions for this Order</a> <br />
        <br />
      </p>
      <?php
    }
        ?>
      <table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
      <tr>
        <?php
        if($order->Sample == 'N') {
        	?>
	        <td valign="top" class="billing"><p><strong>Organisation/Individual:</strong><br />
	                <?php echo $order->GetBillingAddress();  ?><br /><br />
	                <?php echo $order->Customer->Contact->Person->GetPhone('<br />');  ?></p>
	        </td>
	        <?php
        }
        ?>
        <td valign="top" class="shipping"><p><strong>Shipping Address:</strong><br />
                 <?php echo $order->GetShippingAddress();  ?><br /><br />
				<?php echo $order->Customer->Contact->Person->GetPhone('<br />');  ?></p></td>
		<?php
        if($order->Sample == 'N') {
        	?>
			<td valign="top" class="billing"><p><strong>Invoice Address:</strong><br />
                 <?php echo $order->GetInvoiceAddress();  ?><br /><br />
				<?php echo ($order->Customer->Contact->Parent->ID > 0) ? $order->Customer->Contact->Parent->Organisation->InvoicePhone : $order->Customer->Contact->Person->GetPhone('<br />');  ?></p>
			</td>
			<?php
        }
        ?>
      </tr>
      <?php if($isEditable) { ?>
	  <tr>
	  	
	  	<?php
	  	//if($order->Sample == 'N') {
        	?>
			<td class="billing"><input type="button" name="change" value="change" class="btn" onclick="window.location.href='order_changeAddress.php?orderid=<?php echo $order->ID; ?>&type=billing'" /> <input type="button" name="default" value="use default" class="btn" onclick="confirmRequest('order_details.php?orderid=<?php echo $order->ID; ?>&action=replace&address=billing', 'Are you sure you wish to replace this billing address with the customers default address?');" /></td>
	  		<?php
	  	//}
        ?>
        <?php/* } else { */?>
        <!--<td class="billing"></td>-->
        
        <td class="shipping"><input type="button" name="change" value="change" class="btn" onclick="window.location.href='order_changeAddress.php?orderid=<?php echo $order->ID; ?>&type=shipping'" /> <input type="button" name="default" value="use default" class="btn" onclick="confirmRequest('order_details.php?orderid=<?php echo $order->ID; ?>&action=replace&address=shipping', 'Are you sure you wish to replace this shipping address with the customers default address?');" /></td>
		
		<?php
	  	if($order->Sample == 'N') {
        	?>
			<td class="billing"><input type="button" name="change" value="change" class="btn" onclick="window.location.href='order_changeAddress.php?orderid=<?php echo $order->ID; ?>&type=invoice'" /> <input type="button" name="default" value="use default" class="btn" onclick="confirmRequest('order_details.php?orderid=<?php echo $order->ID; ?>&action=replace&address=invoice', 'Are you sure you wish to replace this invoice address with the customers default address?');" /></td>
	  		<?php
	  	}
        ?>
	  </tr>
	  <?php  } ?>
    </table>

    </td>
    <td align="right" valign="top">

    <table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
      	<?php
		if($order->ParentID > 0) {
			?>

		    <tr>
				<th>Orginal Order Ref:</th>
				<td><a href="order_details.php?orderid=<?php echo $parent->ID; ?>"><?php echo $parent->Prefix . $parent->ID; ?></a></td>
				<td class="icons">&nbsp;</td>
			</tr>

			<?php
		}
		?>
      <tr>
        <th>Order Ref:</th>
        <td><?php echo $order->Prefix.$order->ID; ?></td>
        <td class="icons">&nbsp;</td>
      </tr>
      <tr>
        <th>Prefix:</th>
        <td><?php echo (User::UserHasAccess($GLOBALS['SESSION_USER_ID'], 1) && ($GLOBALS['SESSION_USER_ID'] == 3)) ? $form->GetHTML('prefix') : $order->Prefix; ?></td>
        <td class="icons">&nbsp;</td>
      </tr>

      <?php
      if($order->QuoteID) {
      	$quote = new Quote($order->QuoteID);
      	?>

     	  <tr>
	        <th>Quote Ref:</th>
	        <td><a href="quote_details.php?quoteid=<?php echo $quote->ID; ?>"><?php echo $quote->Prefix.$quote->ID; ?></a></td>
	        <td class="icons">&nbsp;</td>
	      </tr>

      	<?php
      }
      ?>

      <?php
      if($order->ProformaID) {
      	$proforma = new Proforma($order->ProformaID);
      	?>

     	  <tr>
	        <th>Proforma Ref:</th>
	        <td><a href="proforma_details.php?proformaid=<?php echo $proforma->ID; ?>"><?php echo $proforma->Prefix.$proforma->ID; ?></a></td>
	        <td class="icons">&nbsp;</td>
	      </tr>

      	<?php
      }
      ?>

	<tr>
       <th>Customer Ref:</th>
       <td><?php echo ($order->PaymentMethod->Reference != 'google') ? $form->GetHTML('ref') : $order->CustomID; ?></td>
       <td class="icons">&nbsp;</td>
    </tr>
    <tr>
        <th>Customer:</th>
        <td><a href="contact_profile.php?cid=<?php echo $order->Customer->Contact->ID; ?>"><?php echo $order->Customer->Contact->Person->GetFullName(); ?></a></td>
        <td class="icons">&nbsp;</td>
   </tr>
   <tr>
        <th>Order Status:</th>
        <td><?php echo $order->Status; ?></td>
        <td class="icons">&nbsp;</td>
   	</tr>


   	<?php if($order->Status=='Incomplete' || $order->Status == 'Unauthenticated' || $order->Status == 'Compromised') {?>
	    <tr>
	        <th>Order Dismissed:</th>
	        <td><?php echo $form->GetHTML('dismissOrder'); ?></td>
	        <td class="icons">&nbsp;</td>
	    </tr>
    <?php }?>

    <tr>
		<th>Tax Rate</th>
		<td><?php echo number_format($order->TaxRate, 2, '.', ','); ?>%</td>
		<td class="icons">&nbsp;</td>
	</tr>
       <tr>
        <th>Sample:</th>
        <td><?php echo ($order->Sample == 'Y') ? 'Yes' : 'No'; ?></td>
        <td class="icons">&nbsp;</td>
      </tr>
      <tr>
        <th>Backordered:</th>
        <td><?php echo ($order->Backordered == 'Y') ? 'Yes' : 'No'; ?></td>
        <td class="icons">&nbsp;</td>
      </tr>
      <tr>
        <th>Device:</th>
        <td><?php echo !empty($order->DevicePlatform) ? sprintf('%s: %s (%s)', $order->DevicePlatform, $order->DeviceBrowser, $order->DeviceVersion) : ''; ?></td>
        <td class="icons">&nbsp;</td>
      </tr>
      <?php
      if($isEditable) {
      	?>

	       <tr>
	        <th>Auto Pack:</th>
	        <td><?php echo $autoPack ? '<img src="images/icon_tick_3.gif" />' : '<img src="images/icon_cross_3.gif" />'; ?></td>
	        <td class="icons">
	        	<?php
	        	if($autoPack) {
		        	?>
		        	<a href="?action=autopack&orderid=<?php echo $order->ID; ?>"><img src="images/icon_stock.gif" /></a>
		        	<?php
		        }
		        ?>
		    </td>
	      </tr>

	      <?php
	  }
	  ?>

      <tr>
        <th>&nbsp;</th>
        <td>&nbsp;</td>
        <td class="icons">&nbsp;</td>
      </tr>
      <tr>
        <th>Order Date:</th>
        <td><?php echo cDatetime($order->OrderedOn, 'shortdate'); ?></td>
        <td class="icons">&nbsp;</td>
      </tr>
	 <tr>
	    <th>Email Address:</th>
	    <td><?php echo ($isEditable) ? $form->GetHTML('email') : $order->Customer->Contact->Person->Email; ?></td>
	    <td class="icons">&nbsp;</td>
	  </tr>
      <tr>
        <th>Emailed On:</th>
        <td><?php echo (!empty($order->EmailedOn))?cDatetime($order->EmailedOn, 'shortdate'):''; ?></td>
        <td class="icons">&nbsp;</td>
      </tr>
      <tr>
        <th>Emailed To:</th>
        <td><?php echo (empty($order->EmailedTo) ? '' : $order->EmailedTo . '&nbsp;'); ?><a href="order_details.php?orderid=<?php echo $order->ID; ?>&amp;action=resend" title="Click to Resend Order Confirmation to Customer">(resend)</a></td>
        <td class="icons">&nbsp;</td>
      </tr>

       <tr>
        <th>Invoice Email:</th>
        <td><?php echo ($isEditable) ? $form->GetHTML('additionalEmail') : $form->GetHTML('additionalEmail'); ?><a href="order_details.php?orderid=<?php echo $order->ID; ?>&amp;action=resendinvoice" title="Click to Resend Order Confirmation to Customer">(resend)</a></td>
	    <td class="icons">&nbsp;</td>
      </tr>

      

       <!--<tr>
        <th>Invoice Email: To:</th>
        <td><?php// echo (empty($order->AdditionalEmail) ? '' : $order->AdditionalEmail . '&nbsp;'); ?><a href="order_details.php?orderid=<?php //echo $order->ID; ?>&amp;action=resend" title="Click to Resend Order Confirmation to Customer">(resend)</a></td>
        <td class="icons">&nbsp;</td>
      </tr>-->

      <?php
      if($order->Sample == 'N') {
        	?>
      <tr>
        <th>Received On:</th>
        <td><?php echo cDatetime($order->ReceivedOn, 'shortdate'); ?></td>
        <td class="icons">&nbsp;</td>
      </tr>
      <?php
      }
        ?>
	  <tr>
        <th>Deadline On:</th>
        <td><?php echo $form->GetHTML('deadline'); ?></td>
        <td class="icons">&nbsp;</td>
      </tr>
      <tr>
        <th>Is Plain Label:</th>
        <td><?php echo $form->GetHTML('plainlabel'); ?></td>
        <td class="icons">&nbsp;</td>
      </tr>
      <tr>
        <th>Is Tax Exempt Valid:</th>
        <td><?php echo $form->GetHTML('taxexemptvalid'); ?></td>
        <td class="icons">&nbsp;</td>
      </tr>
      <tr>
        <th>Is Security Risk:</th>
        <td><?php echo $form->GetHTML('securityrisk'); ?></td>
        <td class="icons">&nbsp;</td>
      </tr>
      <tr>
        <th>Is Awaiting Customer:</th>
        <td><?php echo $form->GetHTML('awaitingcustomer'); ?></td>
        <td class="icons">&nbsp;</td>
      </tr>

      <?php
      if((strtolower($order->Status) == 'pending') || (strtolower($order->Status) == 'purchasing')) {
	  	?>
 		<tr>
        	<th>Is Bidding:</th>
        	<td><?php echo $form->GetHTML('bidding'); ?></td>
        	<td class="icons">&nbsp;</td>
		</tr>
	  	<?php
	  }

      if((strtolower($order->Status) == 'despatched') && ($order->Prefix != 'N')) {
	  	?>
 		<tr>
        	<th>Is Not Received:</th>
        	<td><?php echo ($order->IsNotReceived == 'Y') ? 'Yes' : 'No'; ?></td>
        	<td class="icons">&nbsp;</td>
		</tr>
	  	<?php
	  }
	  ?>
	  
	  <tr>
    	<th>Is Collection:</th>
    	<td><?php echo $form->GetHTML('iscollection'); ?></td>
    	<td class="icons"><a href="javascript:popUrl('order_collection_print.php?orderid=<?php echo $order->ID; ?>', 800, 600);"><img src="images/icon_print_1.gif" border="0" /></a></td>
	</tr>
	  <tr>
	    <th>Created By:</th>
	    <td><?php echo trim(sprintf('%s %s', $creator->Person->Name, $creator->Person->LastName)); ?></td>
	    <td class="icons">&nbsp;</td>
	  </tr>
	  
	  <?php
      if(User::UserHasAccess($GLOBALS['SESSION_USER_ID'], 1)) {
      	?>
		  <tr>
	        <th>Owned By:</th>
	        <td><?php echo $form->GetHTML('ownedby'); ?></td>
	        <td class="icons">&nbsp;</td>
		  </tr>
		<?php
      } else {
      	?>
      	<tr>
	        <th>Owned By:</th>
	        <td><?php echo trim(sprintf('%s %s', $owner->Person->Name, $owner->Person->LastName)); ?></td>
	        <td class="icons">&nbsp;</td>
		  </tr>
		  <?php
	  }
	  ?>
	  
      <tr>
			<th>&nbsp;</th>
			<td>&nbsp;</td>
			<td class="icons">&nbsp;</td>
		</tr>
		<tr>
			<th>Payment Method</th>
			<td><?php echo ($isEditable) ? $form->GetHTML('payment') : $order->PaymentMethod->Method; ?></td>
			<td class="icons">&nbsp;</td>
		</tr>
		<tr class="paymentCard" <?php echo ($order->PaymentMethod->Reference == 'card') ? '' : 'style="display: none;"'; ?>>
			<th>Card</th>
			<td><?php echo $order->Card->PrivateNumber(); ?> (<a href="paymentServer.php?action=change&orderid=<?php echo $order->ID; ?>">Change</a>)</td>
			<td class="icons">&nbsp;</td>
		</tr>
		<tr class="paymentCredit" <?php echo ($order->PaymentMethod->Reference == 'credit') ? '' : 'style="display: none;"'; ?>>
			<th>Credit Account</th>
			<td><?php echo sprintf('%s%s', ($order->Customer->IsCreditActive == 'Y') ? 'Yes' : 'No', ($order->Customer->IsCreditActive == 'Y') ? sprintf(' - &pound;%s', number_format($order->Customer->CreditLimit, 0, '.', ',')) : ''); ?> (<a href="order_changeCredit.php?orderid=<?php echo $order->ID; ?>">Change</a>)</td>
			<td class="icons">&nbsp;</td>
		</tr>
		
		<?php
		if($order->PaymentReceivedOn != '0000-00-00 00:00:00') {
			?>
			<tr>
				<th>Payment Received On:</th>
				<td><?php echo cDatetime($order->PaymentReceivedOn, 'shortdate'); ?></td>
				<td class="icons">&nbsp;</td>
			</tr>
			<?php
		}

		if($isDangerous && ($order->ConfirmedOn != '0000-00-00 00:00:00')) {
			?>
			
			<tr>
				<th>&nbsp;</th>
				<td>&nbsp;</td>
				<td class="icons">&nbsp;</td>
			</tr>
			<tr>
				<th>Confirmed On</th>
				<td><?php echo cDatetime($order->ConfirmedOn, 'shortdate'); ?></td>
				<td class="icons">&nbsp;</td>
			</tr>
			<tr>
				<th>Confirmed Notes</th>
				<td><?php echo $order->ConfirmedNotes; ?></td>
				<td class="icons">&nbsp;</td>
			</tr>
			
			<?php
		}
		?>
		
		<tr>
			<th>&nbsp;</th>
			<td>&nbsp;</td>
			<td class="icons">&nbsp;</td>
		</tr>
		<tr>
			<th>Courier Quote Amount</th>
			<td>&pound;<?php echo ($isEditable) ? $form->GetHTML('courierquoteamount') : $order->CourierQuoteAmount; ?></td>
			<td class="icons">&nbsp;</td>
		</tr>
		
		<?php
		if(!empty($order->CourierQuoteFile->FileName)) {
			?>
			
			<tr>
				<th>Current Quote File</th>
				<td><a href="?orderid=<?php echo $order->ID; ?>&action=downloadquote"><?php echo $order->CourierQuoteFile->FileName; ?></a></td>
				<td class="icons">&nbsp;</td>
			</tr>
			
			<?php
		}
		
		if($isEditable) {
			?>
			
			<tr>
				<th>New Quote File</th>
				<td><?php echo $form->GetHTML('courierquotefile'); ?></td>
				<td class="icons">&nbsp;</td>
			</tr>
			
			<?php
		}

		if($order->Status == 'Cancelled') {
			?>
			
			<tr>
				<th>&nbsp;</th>
				<td>&nbsp;</td>
				<td class="icons">&nbsp;</td>
			</tr>
			<tr>
				<th>Cancelled On</th>
				<td><?php echo cDatetime($order->CancelledOn, 'shortdate'); ?></td>
				<td class="icons">&nbsp;</td>
			</tr>
			<tr>
				<th>Cancelled By</th>
				<td><?php echo trim(sprintf('%s %s', $cancelledBy->Person->Name, $cancelledBy->Person->LastName)); ?></td>
				<td class="icons">&nbsp;</td>
			</tr>
			<tr>
				<th>Cancelled Reason</th>
				<td><?php echo $order->CancelledReason; ?></td>
				<td class="icons">&nbsp;</td>
			</tr>
			
			<?php
		}
		?>
		
    </table>
    <br />

    </td>
  </tr>
  <tr>
  	<td>
  		<?php
		if((strtolower($order->Status) != 'despatched') && (strtolower($order->Status) != 'cancelled')){
			?>
			<p><strong>IMPORTANT:</strong> If you change the quantities of this order or add more items to it you may be required to re-enter the credit card details for this order. If this is the case you will automatically be redirected to the payment page upon updating the order.</p>
			<?php
			if($isEditable) {
				$confirm = false;

				if($order->PaymentMethod->Reference == 'google') {
					$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM payment WHERE Transaction_Type LIKE 'PAYMENT' AND Security_Key LIKE 'GoogleCheckout' AND Status LIKE 'OK' AND Order_ID=%d", mysql_real_escape_string($order->ID)));
					if($data->Row['Count'] == 0) {
						$confirm = true;
					}
					$data->Disconnect();
				}

				if($confirm) {
					$disabled = false;

					$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM payment WHERE Transaction_Type LIKE 'PAYMENT' AND Security_Key LIKE 'GoogleCheckout' AND Status LIKE 'INITIATED' AND Order_ID=%d", mysql_real_escape_string($order->ID)));
					if($data->Row['Count'] > 0) {
						$disabled = true;
					}
					$data->Disconnect();
					?>
					<input name="action" type="submit" id="confirm" value="<?php print ($disabled) ? 'payment initiated': 'confirm payment'; ?>" class="btn" <?php print ($disabled) ? 'disabled="disabled"': ''; ?> />
					<?php
				} else {
					if($order->HasInvoiceAddress()) {
						if(!$isDangerous || ($isDangerous && ($order->ConfirmedOn != '0000-00-00 00:00:00'))) {
							if($order->Postage->ID > 0) {
		                        $cardNumber = $order->Card->PrivateNumber();

         						if(($order->PaymentMethod->ID > 0) && ((($order->PaymentMethod->Reference != 'credit') && ($order->PaymentMethod->Reference != 'card')) || (($order->PaymentMethod->Reference == 'credit') && ($order->Customer->IsCreditActive == 'Y')) || (($order->PaymentMethod->Reference == 'card')))) {
	         						?>
	        						<input name="action" type="submit" value="pack" class="btn" />
									<?php
         						} else {
         							?>
	        						<input name="action" type="submit" value="pack" class="btn" disabled="disabled" />
									<?php
         						}
							}
						}
					}
				}
			}

			if($order->HasInvoiceAddress()) {
				if($warehouseEditable && !$isEditable) {
					echo $form->GetHTML('warehouses');
				}
			}
		}

		if((strtolower($order->Status) == 'partially despatched') || (strtolower($order->Status) == 'despatched')) {
			?>
			<input name="undespatch" type="button" value="undespatch" class="btn" onclick="popUrl('order_undespatch.php?orderid=<?php echo $order->ID; ?>', 800, 600);" />
			<?php
		}
		
		if(strtolower($order->Status) == 'packing') {
			?>
			<input name="action" type="submit" value="unpack" class="btn" />
			<?php
		}
		
		if((strtolower($order->Status) != 'despatched') && (strtolower($order->Status) != 'cancelled')){
			?>
			<input name="cancel order" type="button" value="cancel order" class="btn" onclick="popUrl('order_cancel.php?orderid=<?php echo $order->ID; ?>', 800, 600);" />
			<?php
		}

		if($order->IsDeclined == 'Y'){
			?>
			<input name="undeclined payment" type="button" value="undecline payment" class="btn" onclick="confirmRequest('./order_details.php?orderid=<?php echo $order->ID; ?>&action=undeclinepayment', 'Are you sure you would like to undecline payment for this order?');" />
			<?php
		}
		
		if($order->IsFailed == 'Y'){
			?>
			<input name="unfail payment" type="button" value="unfail payment" class="btn" onclick="confirmRequest('order_details.php?orderid=<?php echo $order->ID; ?>&action=unfailpayment', 'Are you sure you would like to unfail payment for this order?');" />
			<?php
		}

		if($order->IsWarehouseDeclined == 'Y'){
			?>
			<input name="undeclined warehouse" type="button" value="undecline warehouse" class="btn" onclick="window.location.href = 'order_undecline.php?orderid=<?php echo $order->ID; ?>';" />
			<?php
		}

		if($order->IsWarehouseBackordered == 'Y'){
			?>
			<input name="undeclined warehouse" type="button" value="unbackorder warehouse" class="btn" onclick="confirmRequest('./order_details.php?orderid=<?php echo $order->ID; ?>&action=unbackorderwarehouse', 'Are you sure you would like to unbackorder warehouse for this order?');" />
			<?php
		}
		?>

		<input name="print coupon" type="button" value="print coupon" class="btn" onclick="popUrl('./order_printCoupons.php?orderid=<?php echo $order->ID; ?>', 800, 600);" />

		<?php
		if($order->Prefix != 'N') {
			?>
			<input name="notreceivedrequest" type="button" value="not received request" class="btn" onclick="window.self.location.href = 'order_details.php?orderid=<?php echo $order->ID; ?>&action=notreceivedrequest';" />
			<input name="notreceived" type="button" value="not received" class="btn" onclick="window.self.location.href = 'order_details.php?orderid=<?php echo $order->ID; ?>&action=notreceived';" />
			<?php
		}
		?>

		<input name="repeat" type="button" value="repeat" class="btn" onclick="confirmRequest('order_details.php?orderid=<?php echo $order->ID; ?>&action=repeat', 'Are you sure you would like to repeat this order?');" />

		<?php
		if(($order->IsDeclined == 'Y') || ($order->IsFailed == 'Y')) {
			?>
        	<input name="contact" type="button" value="contact" class="btn" onclick="window.self.location.href = 'order_details.php?orderid=<?php echo $order->ID; ?>&action=contact';" />
        	<?php
		}
		
		if(strtolower($order->Status) == 'pending') {
			?>
		
			<input name="purchasing" type="button" value="purchasing" class="btn" onclick="window.self.location.href = '?orderid=<?php echo $order->ID; ?>&action=purchasing';" />
			
			<?php
		}
		?>

		<input name="turnaroundpurchase" type="submit" value="turnaround purchase" class="btn" />
  	</td>
  	<td align="right">
  		<input type="submit" name="action" value="update" class="btn" />
  	</div>
  </tr>
  <tr>
    <td colspan="2">
    	<br />

	<?php if($order->Sample == 'N') { ?>

	<?php
	if(strtolower($order->Status) == 'pending') {
		if(count($order->Bid) > 0) {
			?>

	        <div style="background-color: #eee; padding: 10px 0 10px 0;">
		 		<p><span class="pageSubTitle">Bids</span><br /><span class="pageDescription">Listing bids for new prices made by suppliers.</span></p>

				<table cellspacing="0" class="orderDetails">
					<tr>
						<th nowrap="nowrap" style="padding-right: 5px; width: 1%;"></th>
						<th nowrap="nowrap" style="padding-right: 5px;">Product</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Supplier</th>
						<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Cost (Original)</th>
						<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Cost (Bid)</th>
					</tr>

					<?php
					for($i=0; $i<count($order->Bid); $i++) {
						$order->Bid[$i]->Supplier->Get();
						$order->Bid[$i]->Supplier->Contact->Get();
						$order->Bid[$i]->Product->Get();
						?>

						<tr>
							<td><?php echo $form->GetHTML(sprintf('line_bid_%d', $order->Bid[$i]->ID)); ?></td>
							<td><?php echo $order->Bid[$i]->Product->Name; ?></td>
							<td><a href="product_profile.php?pid=<?php echo $order->Bid[$i]->Product->ID; ?>"><?php echo $order->Bid[$i]->Product->ID; ?></a></td>
							<td><?php echo $order->Bid[$i]->Supplier->Contact->Parent->Organisation->Name; ?></td>
							<td align="right">&pound;<?php echo $order->Bid[$i]->CostOriginal; ?></td>
							<td align="right">&pound;<?php echo $order->Bid[$i]->CostBid; ?></td>
						</tr>

						<?php
					}
					?>

				</table>
				<br />

                <input type="submit" name="acceptbids" value="accept" class="btn" />

			</div>
			<br />

			<?php
		}
	}

	if($isEditable) {
		if($order->Prefix == 'N') {
			?>

			<div style="background-color: #eee; padding: 10px 0 10px 0;">
				<p><span class="pageSubTitle">Not Received Lines</span><br /><span class="pageDescription">Listing products availlable for resending.</span></p>

		        <table cellspacing="0" class="orderDetails">
					<tr>
						<th nowrap="nowrap" style="padding-right: 5px;" width="1%"></th>
						<th nowrap="nowrap" style="padding-right: 5px;">Qty</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Product</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
					</tr>

					<?php
					for($i=0; $i<count($parent->Line); $i++) {
						?>

						<tr>
							<td><?php echo $form->GetHTML(sprintf('not_received_selected_%d', $parent->Line[$i]->ID)); ?></td>
							<td><?php echo $form->GetHTML(sprintf('not_received_qty_%d', $parent->Line[$i]->ID)); ?></td>
							<td><?php echo $parent->Line[$i]->Product->Name; ?></td>
							<td>
								<?php
								if($parent->Line[$i]->Product->ID > 0) {
									?>

									<a href="product_profile.php?pid=<?php echo $parent->Line[$i]->Product->ID; ?>"><?php echo $parent->Line[$i]->Product->ID; ?></a>

									<?php
								}
								?>
							</td>
						</tr>

						<?php
					}
					?>

				</table>
				<br />

				<input type="submit" name="addselectednotreceived" value="add selected" class="btn" />

			</div>
			<br />

			<?php
		}
	}

	if(!empty($order->Suggestion)) {
		?>

        <div style="background-color: #eee; padding: 10px 0 10px 0;">
	 		<p><span class="pageSubTitle">Suggestions</span><br /><span class="pageDescription">Complete the below suggestions to optimise this order.</span></p>

			<table cellspacing="0" class="orderDetails">
				<tr>
					<th style="padding-right: 5px;">Item</th>
					<th style="padding-right: 5px;">Relation</th>
				</tr>

				<?php
				foreach($order->Suggestion as $item) {
					?>

					<tr>
						<td><?php echo $item['Suggestion']; ?></td>
						<td><?php echo isset($item['Line']) ? sprintf('<a href="product_profile.php?pid=%1$d">%2$s</a> [%1$d]', $item['Line']->Product->ID, $item['Line']->Product->Name) : ''; ?></td>
					</tr>

					<?php
				}
				?>

			</table>
			<br />

		</div>
		<br />

		<?php
	}
	?>

    <div style="background-color: #eee; padding: 10px 0 10px 0;">
		<p><span class="pageSubTitle">Order Lines</span><br /><span class="pageDescription">Listing products for despatching.</span></p>

	    <table cellspacing="0" class="orderDetails">
	      <tr>

	      	<?php
	      	if($isEditable) {
	      		?>
				<th width="1%">&nbsp;</th>
				<th width="1%">&nbsp;</th>
	      		<?php
	      	}
	      	?>
	      	
	        <th nowrap="nowrap" style="padding-right: 5px;">Qty</th>
	        
	        <?php
	        if((strtolower($order->Status) == 'despatched') || (strtolower($order->Status) == 'partially despatched')) {
	        	?>
				<th nowrap="nowrap" style="padding-right: 5px;">Not Received</th>
				<?php
			}
			?>
	        	 
	        <th nowrap="nowrap" style="padding-right: 5px;">Product</th>
	        <th nowrap="nowrap" style="padding-right: 5px; text-align: center;">Spec Sheets</th>

	        <?php
	        if($order->PaymentMethod->Reference != 'google') {
        		?>
        		<th nowrap="nowrap" style="padding-right: 5px;">Discount</th>
        		<th nowrap="nowrap" style="padding-right: 5px;">Handling</th>
        		<?php
	        }
	        ?>

			<th nowrap="nowrap" style="padding-right: 5px;">Despatched</th>
			<th nowrap="nowrap" style="padding-right: 5px; text-align: center;">Fix Warehouse</th>
			<th nowrap="nowrap" style="padding-right: 5px;">Incoming</th>
			<th nowrap="nowrap" style="padding-right: 5px;">Invoice</th>
	        <th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
	        <th nowrap="nowrap" style="padding-right: 5px; text-align: center;">Warehouse Shipped</th>
	        <th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Price</th>

	        <?php
	        if($order->PaymentMethod->Reference != 'google') {
        		?>
        		<th nowrap="nowrap" style="padding-right: 5px; text-align: center;">Free</th>
        		<?php
	        }
	        ?>

	        <th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Line Total</th>
	        <th>Backorder</th>
	        <th>&nbsp;</th>
	      </tr>

	      <?php
	      $order->Coupon->Get();
	      $order->OriginalCoupon->Get();

	      for($i=0; $i < count($order->Line); $i++) {
      		$dataComponents = new DataQuery(sprintf("SELECT * FROM product_components WHERE Component_Of_Product_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID)));
      		if($dataComponents->TotalRows > 0) {
      			$hasComponents = true;
      		} else {
      			$hasComponents = false;
      		}

      		if($order->Coupon->IsInvisible == 'Y') {
      			$discountAmt = 0;

      			if($order->OriginalCoupon->ID > 0) {
      				$discountAmt = $order->OriginalCoupon->Discount;
      			}

      			$itemTotal = (($order->Line[$i]->Price-((($order->Line[$i]->Price/100)*$discountAmt)/$order->Line[$i]->Quantity))*$order->Line[$i]->Quantity);
      		} else {
      			$itemTotal = (($order->Line[$i]->Price-($order->Line[$i]->Discount/$order->Line[$i]->Quantity))*$order->Line[$i]->Quantity);
      		}
      		$subTotal += $itemTotal;

      		$backgroundColor = 'transparent';

      		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM warehouse AS w INNER JOIN supplier_product AS sp ON sp.Supplier_ID=w.Type_Reference_ID WHERE w.Type='S' AND sp.product_ID=%d AND w.Warehouse_ID=%d AND sp.Is_Stock_Held='Y'", mysql_real_escape_string($order->Line[$i]->Product->ID), $form->GetValue('despatchfrom_'.$order->Line[$i]->ID)));
      		if($data->Row['Count'] > 0) {
      			$backgroundColor = '#ffcccc';
      		}
      		$data->Disconnect();

      		if(($order->Line[$i]->IsAssociative == 'Y') && ($order->Line[$i]->Product->ID == 0)) {
      			$backgroundColor = '#ffffcc';
      		}

      		if($order->Line[$i]->Product->Stocked == 'Y') {
      			$branchStock = 0;

	      		$data = new DataQuery(sprintf("SELECT SUM(ws.Quantity_In_Stock) AS Quantity FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B' WHERE ws.Product_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID)));
    	  		$branchStock += $data->Row['Quantity'];
      			$data->Disconnect();

      			if($branchStock == 0) {
      				$backgroundColor = '#bb99ff';
      			}
      		}
			?>

	      <tr <?php echo sprintf('style="background-color: %s;"', $backgroundColor); ?>>
	      	<?php
	      	if($isEditable) {
	      		?>

	      		<td>
	      			<?php
					if($isEditable) {
						if($order->Line[$i]->Product->ID > 0) {
							if($order->Line[$i]->Quantity >= 10) {
								if(empty($order->Line[$i]->DespatchID)) {
									?>
									<a href="javascript:toggleLine(<?php echo $order->Line[$i]->ID; ?>);"><img src="images/button-plus.gif" /></a>
									<?php
								}
							}
						}
					}
					?>
				</td>
	      		<td>
	      			<?php
					if($isEditable) {
						if($order->PaymentMethod->Reference != 'google') {
							?>
							<a href="order_details.php?orderid=<?php echo $order->ID; ?>&action=remove&line=<?php echo $order->Line[$i]->ID; ?>"><img src="images/icon_trash_1.gif" alt="Remove" border="0" /></a>
							<?php
						}
					}
					?>
				</td>

				<?php
	      	}
	      	?>

	        <td nowrap="nowrap">
		        <?php
		        if($order->PaymentMethod->Reference != 'google') {
	        		echo ($isEditable)? $form->GetHTML('qty_'. $order->Line[$i]->ID) : $order->Line[$i]->Quantity;
		        } else {
	        		echo $order->Line[$i]->Quantity;
		        }
		        ?>
			</td>
	        
	        <?php
	        if((strtolower($order->Status) == 'despatched') || (strtolower($order->Status) == 'partially despatched')) {
	        	?>
	        	<td nowrap="nowrap"><?php echo $form->GetHTML('qtynotreceived_'. $order->Line[$i]->ID); ?>x</td>
	        	<?php
			}
			?>
			
			<td>
				<?php
				if(($order->Line[$i]->IsAssociative == 'N') || ($order->Line[$i]->Product->ID > 0)) {
					if(($order->Line[$i]->Product->DropSupplierID > 0) && ((strtotime($order->Line[$i]->Product->DropSupplierExpiresOn) > time()) || ($order->Line[$i]->Product->DropSupplierExpiresOn == '0000-00-00 00:00:00'))) {
						echo '<img src="../images/icons/lock.png" alt="Supplier Locked" /> ';
					}

					if($order->Line[$i]->Product->ID > 0) {
						echo $order->Line[$i]->Product->Name;
					} else {
						echo ($isEditable && empty($order->Line[$i]->DespatchID)) ? $form->GetHTML('name_' . $order->Line[$i]->ID) : $order->Line[$i]->Product->Name;
					}

					if($order->Line[$i]->Product->ID > 0) {
						if(!empty($order->Line[$i]->Discount)){
							$discountVal = explode(':', $order->Line[$i]->DiscountInformation);

							if(trim($discountVal[0]) == 'azxcustom') {
								$showDiscount = 'Custom Discount';
							} else {
								$showDiscount = $order->Line[$i]->DiscountInformation;
							}

							if(!empty($showDiscount)) {
								if($order->Coupon->IsInvisible == 'Y') {
									$discountAmt = 0;
									if($order->OriginalCoupon->ID > 0) {
										$discountAmt = $order->OriginalCoupon->Discount;
									}
									echo sprintf("<br />(-&pound;%s)",number_format((($order->Line[$i]->Price/100)*$discountAmt)*$order->Line[$i]->Quantity, 2, '.',','));
								} else {
									echo sprintf("<br />(-&pound;%s)",number_format($order->Line[$i]->Discount, 2, '.',','));
								}
							} else {
								echo sprintf("<br />(-&pound;%s)",number_format($order->Line[$i]->Discount, 2, '.',','));
							}
						}
					}
				} else {
					echo $order->Line[$i]->AssociativeProductTitle;
				}
				?>
			</td>
			<td align="center"><?php echo ($isEditable) ? $form->GetHTML(sprintf('downloads_%d', $order->Line[$i]->ID)) : $order->Line[$i]->IncludeDownloads; ?></td>

			<?php
			if($order->PaymentMethod->Reference != 'google') {
        		?>

				<td nowrap="nowrap">
					<?php
					if($order->Line[$i]->Product->ID > 0) {
						if($isEditable && empty($order->Line[$i]->DespatchID)){
							print $form->GetHTML('discount_'.$order->Line[$i]->ID); ?>%<?php
						} else {
							$discountVal = explode(':', $order->Line[$i]->DiscountInformation);
							if(trim($discountVal[0]) == 'azxcustom') {
								print $discountVal[1]; ?>%<?php
							}
						}
					}
					?>&nbsp;
				</td>
				<td nowrap="nowrap"><?php echo ($isEditable) ? $form->GetHTML('handling_'.$order->Line[$i]->ID).'%' : ''; ?>&nbsp;</td>

				<?php
			}

			if(strtolower($order->Line[$i]->Status) == 'cancelled'){
				?>
				<td colspan="4" align="center">Cancelled</td>
				<?php
			} else {
				?>
				<td nowrap="nowrap">
					<?php
					if(!empty($order->Line[$i]->DespatchID)) {
						echo '<a href="despatch.php?despatchid=' . $order->Line[$i]->DespatchID . '" target="_blank"><img src="./images/icon_tick_3.gif" border="0" /></a>  '.$order->Line[$i]->DespatchedFrom->Name;
					} else {
						if($warehouseEditable) {
							echo $form->GetHTML('despatchfrom_'.$order->Line[$i]->ID);
						}
					}

					if($order->Line[$i]->Product->SpecialOrderSupplierID > 0) {
						$specialOrderSupplier = new Supplier($order->Line[$i]->Product->SpecialOrderSupplierID);
						$specialOrderSupplier->Contact->Get();

						if($specialOrderSupplier->Contact->Parent->Organisation->ID > 0) {
							$specialOrderSupplierName = $specialOrderSupplier->Contact->Parent->Organisation->Name;
						} else {
							$specialOrderSupplierName = trim(sprintf('%s %s', $specialOrderSupplier->Contact->Person->Name, $specialOrderSupplier->Contact->Person->LastName));
						}

						echo sprintf('<br /><br /><strong>Special Order:</strong> %s<br /><strong>Lead Days:</strong> %d', $specialOrderSupplierName, $order->Line[$i]->Product->SpecialOrderLeadDays);
					}
					?>
				</td>
				<td align="center"><?php echo (($order->OwnedBy == $GLOBALS['SESSION_USER_ID']) && ($order->Line[$i]->DespatchID == 0)) ? $form->GetHTML('warehousefixed_'.$order->Line[$i]->ID) : sprintf('<img src="images/%s" border="0" />', ($order->Line[$i]->IsWarehouseFixed == 'Y') ? 'icon_tick_3.gif' : 'icon_cross_3.gif'); ?></td>
				<td>
					<?php
					if($order->Line[$i]->Product->ID > 0) {
						if(!$hasComponents) {
							$data = new DataQuery(sprintf("SELECT SUM(pl.Quantity_Decremental) AS Quantity_Incoming FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID WHERE p.For_Branch>0 AND pl.Quantity_Decremental>0 AND pl.Product_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID)));
							echo $data->Row['Quantity_Incoming'].'x';
							$data->Disconnect();
						}
					}
					?>&nbsp;
				</td>
				<td align="center">
					<?php
					if (!empty($order->Line[$i]->InvoiceID)){
						echo '<a href="invoice.php?invoiceid=' . $order->Line[$i]->InvoiceID . '">' . $order->Line[$i]->InvoiceID . '</a> ';
						echo sprintf('<a href="?orderid=%d&action=download&invoiceid=%d"><img src="images/icon_view_1.gif" align="absmiddle" /></a> ', $order->ID, $order->Line[$i]->InvoiceID);
					}
					?>&nbsp;
				</td>
				<?php
			}
			?>

			<td align="center" nowrap="nowrap">
				<?php
				if($order->Line[$i]->Product->ID > 0) {
					?>
			        <a href="product_profile.php?pid=<?php print $order->Line[$i]->Product->ID; ?>"><?php echo $order->Line[$i]->Product->ID; ?></a>
			        <?php
				} elseif($order->Line[$i]->IsAssociative == 'Y') {
					$productTitle = $order->Line[$i]->AssociativeProductTitle;
					$pos = strpos($productTitle, '(');
					if($pos !== false) {
						$productTitle = trim(substr($productTitle, 0, $pos));
					}
					$productTitle = urlencode($productTitle);
					?>
			        <a href="javascript:popUrl('product_search_association.php?string=<?php print $productTitle; ?>&field=quickfind_<?php print $order->Line[$i]->ID; ?>', 550, 600);"><img alt="Find Product Association" align="absmiddle" src="images/icon_search_1.gif" border="0" /></a> <?php echo $form->GetHTML('quickfind_'. $order->Line[$i]->ID); ?>
			        <?php
				}
				?>
			</td>
			<td align="center"><?php echo ($isEditable) ? $form->GetHTML('line_warehouseshipped_'.$order->Line[$i]->ID) : '<img src="images/icon_tick_3.gif" border="0" />'; ?></td>
	        <td align="right" nowrap="nowrap">
	        	<?php
				if(($order->Line[$i]->IsAssociative == 'Y') || ($order->Line[$i]->Product->ID > 0)) {
					echo '&pound;' . number_format($order->Line[$i]->Price, 2, '.', ',');
				} else {
					echo '&pound;' . (($isEditable && empty($order->Line[$i]->DespatchID)) ? $form->GetHTML('price_' . $order->Line[$i]->ID) : number_format($order->Line[$i]->Price, 2, '.', ','));
				}
				?>
	        </td>

	        <?php
	        if($order->PaymentMethod->Reference != 'google') {
        		?>

        		<td align="center">
	        		<?php
	        		if($isEditable && (strtolower($order->Line[$i]->Status) != 'cancelled') && empty($order->Line[$i]->DespatchID)){
	        			echo $form->GetHTML('freeofcharge_'.$order->Line[$i]->ID);
	        		} else {
	        			echo ($order->Line[$i]->FreeOfCharge == 'Y') ? '<img src="./images/icon_tick_3.gif" border="0" />' : '&nbsp;';
	        		}
	        		?>
        		</td>

        		<?php
	        }
	        ?>

	        <td align="right">&pound;<?php echo number_format($order->Line[$i]->Total, 2, '.', ','); ?></td>

			<?php
			if(($order->Line[$i]->Product->ID > 0) && (strtolower($order->Line[$i]->Status) != 'cancelled')) {
				if(!stristr($order->Line[$i]->Status, 'Backordered')){
					if(empty($order->Line[$i]->DespatchID)) {
						?>
		        		<td align="right"><input style="padding: 0;" name="Backorder" type="button" id="Backorder"  value="backorder" class="btn" onclick="window.self.location.href='order_backorder.php?orderid=<?php echo $order->ID; ?>&orderlineid=<?php echo $order->Line[$i]->ID; ?>&redirect=<?php echo $_SERVER['PHP_SELF']; ?>';" /></td>
		        		<?php
					} else {
						echo '<td>&nbsp;</td>';
					}
				} else {
					?>
			        <td>Expected:<br /><a href="order_backorder.php?orderid=<?php echo $order->ID; ?>&orderlineid=<?php echo $order->Line[$i]->ID; ?>&redirect=<?php echo $_SERVER['PHP_SELF']; ?>"><?php print ($order->Line[$i]->BackorderExpectedOn > '0000-00-00 00:00:00') ? cDatetime($order->Line[$i]->BackorderExpectedOn, 'shortdate') : 'Unknown'; ?></a></td>
			        <?php
				}
			} else {
				echo '<td>&nbsp;</td>';
			}

			$stock = new WarehouseStock();

			if($stock->GetViaWarehouseProduct($order->Line[$i]->DespatchedFrom->ID, $order->Line[$i]->Product->ID)) {
				echo sprintf('<td><a href="warehouse_stock_edit.php?sid=%d&direct=%s"><img border="0" src="images/icon_stock.gif" width="16" height="16" alt="Edit Stock Details" /></a></td>', $stock->ID, urlencode(sprintf('%s%s', $_SERVER['PHP_SELF'], (strlen($_SERVER['QUERY_STRING']) > 0) ? sprintf('?%s', $_SERVER['QUERY_STRING']) : '')));
			} else {
				echo '<td>&nbsp;</td>';
			}
			?>

	      </tr>
	      <tr id="line-<?php echo $order->Line[$i]->ID; ?>" style="display: none;">
	      	<td></td>
	      	<td colspan="16">

	      		<?php
	      		if($order->Line[$i]->Product->ID > 0) {
	      			if($order->Line[$i]->Quantity >= 10) {
			      		if(empty($order->Line[$i]->DespatchID)) {
			      			?>
			      				      		
				      		<table width="100%">
				      			<tr>
				      				<th>Quantity</th>
				      				<th>Supplier</th>
				      				<th style="text-align: right;">Cost</th>
				      				<th width="1%">&nbsp;</th>
				      			</tr>
				      			<tr>
				      				<td><?php echo $form->GetHTML('new_quantity_'. $order->Line[$i]->ID); ?></td>
				      				<td><?php echo $form->GetHTML('new_supplier_'. $order->Line[$i]->ID); ?></td>
				      				<td align="right">&pound;<?php echo $form->GetHTML('new_cost_'. $order->Line[$i]->ID); ?></td>
				      				<td><input type="image" src="images/button-plus.gif" name="newcost-<?php echo $order->Line[$i]->ID; ?>" /></td>
				      			</tr>
				      		</table>

				      		<?php
					}
				}
			}
			?>

	      	</td>
	      </tr>

	<?php
	while($dataComponents->Row) {
		$component = new Product($dataComponents->Row['Product_ID']);

		$warehouseFind = new DataQuery(sprintf("SELECT Warehouse_Name FROM warehouse WHERE Warehouse_ID=%d", $form->GetValue('despatchfrom_'.$order->Line[$i]->ID)));
		$warehouseFindMain = new DataQuery(sprintf("SELECT SUM(Quantity_In_Stock) AS Quantity FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d", $form->GetValue('despatchfrom_'.$order->Line[$i]->ID), mysql_real_escape_string($component->ID)));

		$qtyStockedComp = ((strlen($warehouseFindMain->Row['Quantity']) > 0) ? $warehouseFindMain->Row['Quantity'] : 0);
				?>

				<tr>
					<td>Component: <?php print ($dataComponents->Row['Component_Quantity']*$order->Line[$i]->Quantity); ?>x</td>
					<td>
						<?php echo $component->Name; ?><br />
						Part Number: <?php print $component->SKU; ?>
					</td>

					<?php
					if((strtolower($order->Status) == 'despatched') || (strtolower($order->Status) == 'partially despatched')) {
						echo '<td>&nbsp;</td>';	
					}
					
					if($order->PaymentMethod->Reference != 'google') {
						?>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<?php
					}
					?>

					<td>
						<?php
						echo $warehouseFind->Row['Warehouse_Name'].' ('.$qtyStockedComp.')';
						?>
					</td>
					<td>&nbsp;</td>
					<td>
						<?php
						$data = new DataQuery(sprintf("SELECT SUM(pl.Quantity_Decremental) AS Quantity_Incoming FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID WHERE p.For_Branch>0 AND pl.Quantity_Decremental>0 AND pl.Product_ID=%d", mysql_real_escape_string($component->ID)));
						echo $data->Row['Quantity_Incoming'].'x';
						$data->Disconnect();
						?>
					</td>
					<td>&nbsp;</td>
					<td align="center"><a href="product_profile.php?pid=<?php print $component->ID; ?>"><?php print $component->ID; ?></a></td>

					<?php
					if($order->PaymentMethod->Reference != 'google') {
						?>
						<td colspan="5">&nbsp;</td>
						<?php
					} else {
						?>
						<td colspan="4">&nbsp;</td>
						<?php
					}
					?>
				</tr>

				<?php
				$warehouseFind->Disconnect();
				$warehouseFindMain->Disconnect();

				$dataComponents->Next();
	}
	$dataComponents->Disconnect();

	      }

	      if($order->PaymentMethod->Reference != 'google') {
      		if($isEditable && (!empty($order->FreeText) || (strlen($order->FreeText) > 0) || ($order->FreeTextValue > 0))) {
				?>
				<tr>
					<td nowrap="nowrap">
						Free text:
					</td>
					<td colspan="<?php echo ($order->PaymentMethod->Reference == 'google') ? (((strtolower($order->Status) == 'despatched') || (strtolower($order->Status) == 'partially despatched')) ? 12 : 12) : (((strtolower($order->Status) == 'despatched') || (strtolower($order->Status) == 'partially despatched')) ? 12 : 12); ?>">
						<?php echo $form->GetHTML('freeText'); ?>
					</td>
					<td align="right" nowrap="nowrap">
						<?php echo '&pound;&nbsp;'.$form->GetHTML('freeTextValue'); ?>
					</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
				<?php
      		} elseif(!empty($order->FreeText) || (strlen($order->FreeText) > 0) || ($order->FreeTextValue > 0)) {
				?>
				<tr>
					<td nowrap="nowrap">
						Free text:
					</td>
					<td colspan="<?php echo ($order->PaymentMethod->Reference == 'google') ? (((strtolower($order->Status) == 'despatched') || (strtolower($order->Status) == 'partially despatched')) ? 12 : 11) : (((strtolower($order->Status) == 'despatched') || (strtolower($order->Status) == 'partially despatched')) ? 12 : 11); ?>">
						<?php echo $order->FreeText.'&nbsp;'; ?>
					</td>
					<td align="right" nowrap="nowrap">
						<?php echo '&pound;'.$order->FreeTextValue; ?>
					</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
				<?php
      		}
	      }
		?>

	      <tr>
	        <td colspan="<?php echo ($order->PaymentMethod->Reference == 'google') ? 11 : 14; ?>" align="left">Cart Weight: ~<?php echo $order->Weight; ?>Kg</td>
	        <td align="right" nowrap="nowrap">Sub Total:</td>
	        <td align="right">&pound;<?php echo number_format($order->SubTotal, 2, '.', ','); ?></td>
	        <td>&nbsp;</td>
	        <td>&nbsp;</td>
	      </tr>
	    </table>

	    <?php } else { ?>

		<table cellspacing="0" class="orderDetails">
	      <tr>
	        <th nowrap="nowrap" style="padding-right: 5px;">Qty</th>
	        <th nowrap="nowrap" style="padding-right: 5px;">Product</th>
			<th nowrap="nowrap" style="padding-right: 5px;">Despatched</th>
	        <th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
	        <th>&nbsp;</th>
	      </tr>
	      <?php
	      $order->Coupon->Get();
	      $order->OriginalCoupon->Get();

	      for($i=0; $i < count($order->Line); $i++){
	?>
	      <tr>
	        <td nowrap="nowrap">
			<?php if ($isEditable){ ?>
			<a href="order_details.php?orderid=<?php echo $order->ID; ?>&action=remove&line=<?php echo $order->Line[$i]->ID; ?>"><img src="images/icon_trash_1.gif" alt="Remove" border="0" /></a>
			<?php } ?>
	        <?php
	        echo ($isEditable)? $form->GetHTML('qty_'. $order->Line[$i]->ID) : $order->Line[$i]->Quantity;
	        ?>x</td>
	        <td>
				<?php echo $order->Line[$i]->Product->Name; ?>
			</td>
			<?php
			if(strtolower($order->Line[$i]->Status) == 'cancelled'){
			?>
			<td align="center">Cancelled</td>
			<?php
			} else {
			?>
			<td nowrap="nowrap"><?php if(!empty($order->Line[$i]->DespatchID)){
				echo '<a href="despatch.php?despatchid=' . $order->Line[$i]->DespatchID . '" target="_blank"><img src="./images/icon_tick_3.gif" border="0" /></a>  '.$order->Line[$i]->DespatchedFrom->Name;
			}else{
				if($warehouseEditable){
					echo  $form->GetHTML('despatchfrom_'.$order->Line[$i]->ID);
				}
			}; ?>&nbsp;

			</td>
			<?php } ?>
	        <td align="center"><a href="product_profile.php?pid=<?php print $order->Line[$i]->Product->ID; ?>"><?php echo $order->Line[$i]->Product->ID; ?></a></td>
	        <?php
	        if(strtolower($order->Line[$i]->Status) != 'cancelled'){
			?>
			        <?php if(strtolower($order->Line[$i]->Status)!='backordered' && empty($order->Line[$i]->DespatchID)){?>
		        			<td align="right"><input style="padding: 0;" name="Backorder Order" type="button" id="Backorder Order"  value="backorder" class="btn" onclick='backorder(<?php echo $order->ID; ?>, <?php echo $order->Line[$i]->ID; ?>);'></td>

			        <?php }elseif(empty($order->Line[$i]->DespatchID)){ ?>
		        			<td>Backordered</td>
			        <?php }else{?>
		        			<td>&nbsp;</td>
			        <?php } ?>
			<?php
	        }
			?>
	      </tr>
	      <?php
	      }
		  ?>

	      <tr>
	        <td colspan="7" align="left">Cart Weight: ~<?php echo $order->Weight; ?>Kg</td>
	      </tr>
	    </table>


	    <?php } ?>

	</div>
    <br />

	    <?php
	    $data = new DataQuery(sprintf("SELECT * FROM invoice WHERE Order_ID=%d", mysql_real_escape_string($order->ID)));
		if($data->TotalRows > 0) {
			?>

	        <div style="background-color: #eee; padding: 10px 0 10px 0;">
		 		<p><span class="pageSubTitle">Invoices</span><br /><span class="pageDescription">Listing invoices for this order.</span></p>

				<table cellspacing="0" class="orderDetails">
					<tr>
						<th nowrap="nowrap" style="padding-right: 5px;">Invoice</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Date</th>
						<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Total</th>
					</tr>

					<?php
					while($data->Row) {
						?>

						<tr>
							<td><a href="invoice.php?invoiceid=<?php echo $data->Row['Invoice_ID']; ?>"><?php echo $data->Row['Invoice_ID']; ?></a></td>
							<td><?php echo $data->Row['Created_On']; ?></td>
							<td align="right">&pound;<?php echo $data->Row['Invoice_Total']; ?></td>
						</tr>

						<?php
						$data->Next();
					}
					?>

				</table>
				<br />

			</div>
			<br />

			<?php
		}
		$data->Disconnect();
		?>

    </td>
  </tr>
  <?php if($order->Sample == 'N') { ?>
  <tr>
  	<td align="left" valign="top">
  		<?php
	    if($order->PaymentMethod->Reference != 'google') {
    		if($isEditable || (strtolower($order->Status) == 'packing')) {
				?>

				<input type="button" name="addproduct" value="add product" class="btn" onclick="window.location.href='order_add.php?orderid=<?php echo $order->ID; ?>';" />

				<input type="button" name="addcustom" value="add custom" class="btn" onclick="window.location.href='?orderid=<?php echo $order->ID; ?>&action=addcustom';" />

				<input type="button" name="addcatalogue" value="add catalogue" class="btn" onclick="window.location.href='?orderid=<?php echo $order->ID; ?>&action=addcatalogue';" />

				<?php
		    }
		}
		?>
  	</td>
  	<td align="right" valign="top">
  		<?php
  		if((strtolower($order->Status) == 'despatched') || (strtolower($order->Status) == 'partially despatched')) {
	    	?>
			<input type="submit" name="updatenotreceived" value="update not received" class="btn" />
			<?php	
		}	    
	    ?>

	    <input type="submit" name="action" value="update" class="btn" />
  	</td>
  </tr>
  <tr>
    <td align="left" valign="top" colspan="2">

	<table border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td width="49.5%" valign="top">

				<br />
				<br />
				<strong>Additional Information:</strong><br />
			      <table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
			        <tr>
			          <th valign="top"> <a href="./order_notes.php?oid=<?php echo $order->ID; ?>">Order Notes:</a></th>
			          <td valign="top">
					  <a href="./order_notes.php?oid=<?php echo $order->ID; ?>">
					  <?php
					  $data = new DataQuery(sprintf("SELECT COUNT(*) AS count FROM order_note WHERE Order_ID=%d", mysql_real_escape_string($order->ID)));
					  echo $data->Row['count'];
					  $data->Disconnect();
					  ?></a>
					  </td>
			        </tr>
			        <tr>
			          <th valign="top"><a href="order_documents.php?oid=<?php echo $order->ID; ?>&restricttype=Purchase+Order">Purchase Orders:</a></th>
			          <td valign="top">
					  <a href="order_documents.php?oid=<?php echo $order->ID; ?>">
					  <?php
					  $data = new DataQuery(sprintf("SELECT COUNT(*) AS count FROM order_document WHERE orderId=%d AND type LIKE 'Purchase Order'", mysql_real_escape_string($order->ID)));
					  echo $data->Row['count'];
					  $data->Disconnect();
					  ?></a>
					  </td>
					 </tr>
					 <tr>
			          <th valign="top"><a href="order_documents.php?oid=<?php echo $order->ID; ?>&restricttype=Export+Proof">Proof of Export:</a></th>
			          <td valign="top">
					  <a href="order_documents.php?oid=<?php echo $order->ID; ?>">
					  <?php
					  $data = new DataQuery(sprintf("SELECT COUNT(*) AS count FROM order_document WHERE orderId=%d AND type LIKE 'Export Proof'", mysql_real_escape_string($order->ID)));
					  echo $data->Row['count'];
					  $data->Disconnect();
					  ?></a>
					  </td>
					 </tr>
					<tr>
			          <th valign="top"> <a href="./credit_notes.php?oid=<?php echo $order->ID; ?>">Credit Notes:</a> </th>
			          <td valign="top">
					  <a href="./credit_notes.php?oid=<?php echo $order->ID; ?>">
					  <?php
					  $data = new DataQuery(sprintf("SELECT COUNT(*) AS count FROM credit_note WHERE Order_ID=%d", mysql_real_escape_string($order->ID)));
					  echo $data->Row['count'];
					  $data->Disconnect();
					  ?></a>
					  </td>
					 </tr>
					<tr>
			          <th>
			            <?php echo "<a href=\"return_history.php?orderid={$order->ID}\">Return History</a>\n";?>
			          </th>
			          <td valign="top">
			          	 <a href="./return_history.php?orderid=<?php echo $order->ID; ?>">
						  <?php
						  $where = '';
						  $lineids = array();
						  foreach($order->Line as $l) {
						  	$lineids[] = $l->ID;
						  }
						  if(count($lineids) > 0) {
						  	if(count($lineids) > 1) {
						  		$where .= implode(' OR Order_Line_ID=', $lineids);
						  	} else {
						  		$where .= $lineids[0];
						  	}

						  	$where = ' AND (Order_Line_ID='.$where.')';
						  }

						  $returns = new DataQuery(sprintf("select count(Return_ID) as returnCount from `return` where Customer_ID=%d%s", mysql_real_escape_string($order->Customer->ID), mysql_real_escape_string($where)));
						  echo $returns->Row['returnCount'];
						  $returns->Disconnect();
						  unset($returns);
						  ?></a>
			          </td>
			        </tr>
					<tr>
			          <th valign="top">Referrer:</th>
			          <td valign="top"><?php echo $referrer->Url; ?></td>
			        </tr>
			        <tr>
			          <th valign="top">Search String:</th>
			          <td valign="top"><?php echo $referrer->SearchString; ?></td>
			        </tr>
			        <tr>
			          <th valign="top">Alerts:</th>
			          <td valign="top"><a href="alert_add.php?owner=Order&referenceid=<?php echo $order->ID; ?>">Add New Alert</a></td>
			        </tr>
			      </table><br />

				<?php
				$favouriteSupplier = array();

				$data = new DataQuery(sprintf("SELECT Supplier_ID FROM supplier WHERE Is_Favourite='Y'"));
				while($data->Row) {
					$favouriteSupplier[] = $data->Row['Supplier_ID'];

					$data->Next();
				}
				$data->Disconnect();

				if(count($favouriteSupplier) > 0) {
					$supplierLine = array();
					$activeLines = 0;

					$sqlWhere = sprintf("WHERE (sp.Supplier_ID=%s) ", mysql_real_escape_string(implode(' OR sp.Supplier_ID=', $favouriteSupplier)));

					for($i=0; $i<count($order->Line); $i++) {
						if($order->Line[$i]->Product->ID > 0) {
							$order->Line[$i]->Product->Get();

							if(($order->Line[$i]->Status != 'Invoiced') && ($order->Line[$i]->Status != 'Despatched') && ($order->Line[$i]->DespatchedFrom->Type == 'S')) {
								$data = new DataQuery(sprintf("SELECT Supplier_ID, Cost FROM supplier_product AS sp %s AND sp.Product_ID=%d AND sp.Cost>0", $sqlWhere, mysql_real_escape_string($order->Line[$i]->Product->ID)));
								while($data->Row) {
									if(!isset($supplierLine[$data->Row['Supplier_ID']])) {
										$supplierLine[$data->Row['Supplier_ID']] = array();
									}

									$supplierLine[$data->Row['Supplier_ID']][] = array(
										'ProductID' => $order->Line[$i]->Product->ID,
										'Cost' => $data->Row['Cost'],
										'Quantity' => $order->Line[$i]->Quantity,
										'ShippingClassID' => $order->Line[$i]->Product->ShippingClass->ID,
										'Weight' => $order->Line[$i]->Product->Weight);

									$data->Next();
								}
								$data->Disconnect();

								$activeLines++;
							}
						}
					}
					?>

					<table border="0" cellpadding="5" cellspacing="0" class="orderTotals" width="100%">
						<tr>
							<th>Supplier</th>
							<th style="text-align: right;">Products</th>
							<th style="text-align: right;">Shipping</th>
							<th style="text-align: right;">Total Cost</th>
						</tr>

						<?php
						foreach($supplierLine as $supplierId => $line) {
							if(count($line) == $activeLines) {
								$supplier = new Supplier($supplierId);
								$supplier->Contact->Get();

								$supplierName = trim(sprintf('%s %s %s', $supplier->Contact->Person->Name, $supplier->Contact->Person->LastName, ($supplier->Contact->Parent->ID > 0) ? sprintf(' (%s)', $supplier->Contact->Parent->Organisation->Name) : ''));

								$productCost = 0;
								$productWeight = 0;
								$shippingCost = 0;

								for($i=0; $i<count($line); $i++) {
									$productCost += $line[$i]['Cost'] * $line[$i]['Quantity'];
									$productWeight += $line[$i]['Weight'] * $line[$i]['Quantity'];
								}

								$calc = new SupplierShippingCalculator($order->Shipping->Address->Country->ID, $order->Shipping->Address->Region->ID, $productCost, $productWeight, $order->Postage->ID, $supplierId);

								for($i=0; $i<count($line); $i++) {
									$calc->Add($line[$i]['Quantity'], $line[$i]['ShippingClassID']);
								}

								$shippingCost = $calc->GetTotal();
								?>

								<tr>
									<td><?php echo $supplierName; ?></td>
									<td align="right">&pound;<?php echo number_format($productCost, 2, '.', ''); ?></td>
									<td align="right">&pound;<?php echo number_format($shippingCost, 2, '.', ''); ?></td>
									<td align="right">&pound;<?php echo number_format($productCost + $shippingCost, 2, '.', ''); ?></td>
								</tr>

								<?php
							}
						}
						?>
					</table>

					<?php
				}
				?>

			</td>
			<td width="1%">&nbsp;</td>
			<td width="49.5%" valign="top">

				<?php
				$data = new DataQuery(sprintf("SELECT Order_ID, Order_Prefix, Total FROM orders WHERE Parent_ID=%d", mysql_real_escape_string($order->ID)));
				if($data->TotalRows > 0) {
					?>

                    <table border="0" cellpadding="5" cellspacing="0" class="orderTotals" width="100%">
						<tr>
							<th colspan="2">Related Orders</th>
						</tr>

						<?php
						while($data->Row) {
							?>

                            <tr>
								<td><a href="order_details.php?orderid=<?php echo $data->Row['Order_ID']; ?>"><?php echo $data->Row['Order_Prefix'].$data->Row['Order_ID']; ?></a></td>
								<td align="right">&pound;<?php echo number_format(round($data->Row['Total'], 2), 2, '.', ','); ?></td>
							</tr>

							<?php
							$data->Next();
						}
						?>

					</table>
					<br />

					<?php
				}
				$data->Disconnect();
				?>

			    <table border="0" cellpadding="5" cellspacing="0" class="orderTotals" width="100%">
			      <tr>
			        <th colspan="2">Tax &amp; Shipping</th>
			      </tr>
			      <tr>
					<td>Nominal Code:</td>
					<td align="right"><?php echo $order->NominalCode; ?></td>
					</tr>
			      <tr>
			        <td>Delivery Option:</td>
			        <td align="right">
			          <?php
			          if($order->PaymentMethod->Reference != 'google') {
			          	if(!$isEditable){
			          		$order->Postage->Get();
			          		echo $order->Postage->Name;
			          	} else {
			          		$order->Recalculate();
			          		echo $order->PostageOptions;
			          	}
			          } else {
			          	$order->Postage->Get();
			          	echo $order->Postage->Name;
			          }
						?>
			        </td>
			      </tr>
			      <tr>
			        <td>
						Shipping
						<?php
						if($order->PaymentMethod->Reference != 'google') {
							if($isEditable){
								if($order->IsCustomShipping == 'N'){
									?>
									<a href="<?php echo $_SERVER['PHP_SELF']; ?>?orderid=<?php echo $order->ID; ?>&shipping=custom">(customise)</a>
									<?php
								} else {
									?>
									<a href="<?php echo $_SERVER['PHP_SELF']; ?>?orderid=<?php echo $order->ID; ?>&shipping=standard">(standardise)</a>
									<?php
								}
							}
						}
						?>
						:
					</td>
			        <td align="right">
					&pound;
					<?php
					if($isEditable){
						if($order->IsCustomShipping == 'N'){
							echo number_format($order->TotalShipping, 2, ".", ",");
						} else {
							?>
							<input type="text" name="setShipping" value="<?php echo number_format($order->TotalShipping, 2, ".", ",");  ?>" size="10" />
							<?php
						}
					} else {
						echo number_format($order->TotalShipping, 2, ".", ",");
					}
					 ?>
					</td>
			      </tr>

					<?php
					if($order->ShippingMultiplier > 1) {
						?>

						<tr>
							<td style="background-color: #ffc;" valign="top">
								Shipping Breakdown<br /><br />

								<?php
								for($i=0; $i<count($order->ShippingLine); $i++) {
									echo sprintf('<span style="font-size: 9px; color: #333;">%d x %skg @ &pound;%s</span><br />', $order->ShippingLine[$i]->Quantity, $order->ShippingLine[$i]->Weight, number_format($order->ShippingLine[$i]->Charge, 2, '.', ','));
								}
								?>
							</td>
							<td style="background-color: #ffc;" valign="top" align="right">
								&nbsp;<br /><br />

								<?php
								for($i=0; $i<count($order->ShippingLine); $i++) {
									echo sprintf('<span style="font-size: 9px; color: #333;">&pound;%s</span><br />', number_format($order->ShippingLine[$i]->Charge * $order->ShippingLine[$i]->Quantity, 2, '.', ','));
								}
								?>
							</td>
						</tr>

						<?php
					}

				  if($order->DiscountReward > 0) {
					?>

					<tr>
						<td>Discount Reward Used</td>
						<td align="right">&pound;<?php echo number_format($order->DiscountReward, 2, ".", ","); ?></td>
					</tr>

					<?php
				  }

				  if(($order->Coupon->IsInvisible == 'N') || !empty($order->TotalDiscount)) {
					?>
						<tr>
							<td>
								Discount:
							<?php
							if(!empty($order->Coupon->ID)){
								if($order->Coupon->IsInvisible == 'Y') {
									if(!empty($order->OriginalCoupon->ID)){
										$order->OriginalCoupon->Get();
										echo sprintf('<br /><span class="smallGreyText">%s (%s)</span>', $order->OriginalCoupon->Name, $order->OriginalCoupon->Reference);
									}
								} else {
									$order->Coupon->Get();
									echo sprintf('<br /><span class="smallGreyText">%s (%s)</span>', $order->Coupon->Name, $order->Coupon->Reference);
								}
							}
							?>

							</td>
							<td align="right">-&pound;<?php echo number_format($order->TotalDiscount, 2, ".", ","); ?></td>
						</tr>
					<?php
				  } elseif(($order->Coupon->IsInvisible == 'Y') && ($order->OriginalCoupon->ID > 0)) {
					?>
						<tr>
							<td>
								Discount:
							<?php
							if(!empty($order->Coupon->ID)){
								if($order->Coupon->IsInvisible == 'Y') {
									$order->OriginalCoupon->Get();
									echo sprintf('<br /><span class="smallGreyText">%s (%s)</span>', $order->OriginalCoupon->Name, $order->OriginalCoupon->Reference);
								} else {
									$order->Coupon->Get();
									echo sprintf('<br /><span class="smallGreyText">%s (%s)</span>', $order->Coupon->Name, $order->Coupon->Reference);
								}
							}
							?>

							</td>
							<td align="right">-&pound;<?php echo number_format((($order->SubTotal/100)*$order->OriginalCoupon->Discount), 2, ".", ","); ?></td>
						</tr>
						<?php
				  }

				  if($order->PaymentMethod->Reference != 'google') {
				  	if($isEditable){
							?>
							<tr>
								<td>Coupon:</td>
								<td align="right"><?php print $form->GetHTML('coupon'); ?></td>
							</tr>
							<?php
				  	}
				  }
					?>
			      <tr>
			        <td>Net:</td>
			        <td align="right">&pound;<?php echo number_format($order->TotalNet, 2, ".", ","); ?></td>
			      </tr>
			      <?php
			      if($order->PaymentMethod->Reference != 'google') {
			      	if($isEditable){
						?>
				        <tr><td>Tax Exemption Code:</td><td align="right"><?php echo $form->GetHTML('taxexemptcode'); ?></td></tr>
				        <?php
			      	} elseif(!empty($order->TaxExemptCode)) {
						?>
						<tr><td>Tax Exemption Code:</td><td align="right"><?php echo $order->TaxExemptCode; ?></td></tr>
						<?php
			      	}
			      }
				  ?>
				  <tr>
			        <td>Tax:</td>
			        <td align="right">&pound;<?php echo number_format($order->TotalTax, 2, ".", ","); ?></td>
			      </tr>
			      <tr>
                    <td><strong>Total:</strong></td>
					<td align="right"><strong>&pound;<?php echo number_format($order->Total, 2, ".", ","); ?></strong></td>
			      </tr>
			    </table><br />

			    <?php
			    $order->GetSupplierShipping();

			    if(count($order->SupplierShipping) > 0) {
			    	?>

				    <table border="0" cellpadding="5" cellspacing="0" class="orderTotals" width="100%">
				      <tr>
				        <th colspan="2">Supplier Shipping Costs</th>
				      </tr>

						<?php
						for($i=0; $i<count($order->SupplierShipping); $i++) {
							$order->SupplierShipping[$i]['Supplier']->Contact->Get();

							$supplierName = trim(sprintf('%s %s %s', $order->SupplierShipping[$i]['Supplier']->Contact->Person->Name, $order->SupplierShipping[$i]['Supplier']->Contact->Person->LastName, ($order->SupplierShipping[$i]['Supplier']->Contact->Parent->ID > 0) ? sprintf(' (%s)', $order->SupplierShipping[$i]['Supplier']->Contact->Parent->Organisation->Name) : ''));
							$display = ($order->SupplierShipping[$i]['Calculator']->HasErrors) ? 'Missing shipping settings' : sprintf('&pound;%s', number_format($order->SupplierShipping[$i]['Calculator']->GetTotal(), 2, '.', ''));
				  			?>

							<tr>
								<td><?php echo $supplierName; ?></td>
				        		<td align="right"><?php echo $display; ?></td>
							</tr>

				      		<?php
						}
						?>

				    </table>

				    <?php
			    }
			    ?>

			</td>
		</tr>
	</table>

    </td>
  </tr>

  	<?php
  } else {
  	?>

   <tr>
    <td align="left" valign="top">
	<input type="submit" name="action" value="update" class="btn" />
	<?php if ($isEditable){ ?>
	<input type="button" name="add product" value="add" class="btn" onclick="window.location.href='order_add.php?orderid=<?php echo $order->ID; ?>';" />
	<?php } ?>
	</td>
    <td align="right"><table border="0" cellpadding="5" cellspacing="0" class="orderTotals">
      <tr>
        <th colspan="2">Tax &amp; Shipping</th>
      </tr>
      <tr>
        <td>Delivery Option:</td>
        <td align="right">
          <?php
          if (!$isEditable){
          	$order->Postage->Get();
          	echo $order->Postage->Name;
          } else {
          	echo $order->PostageOptions;
          }
			?>
        </td>
      </tr>
    </table></td>
  </tr>

  <?php
  }
  ?>

</table>

<?php
echo $form->Close();

$page->Display('footer');

require_once('lib/common/app_footer.php');