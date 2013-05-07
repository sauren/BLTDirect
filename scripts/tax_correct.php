<?php
ini_set('max_execution_time', '90000');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CreditNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CreditNoteLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Payment.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PaymentGateway.php');

$data = new DataQuery("SELECT i.Invoice_ID, i.Order_ID, i.Payment_ID, i.Created_On,
ROUND((i.Invoice_Net+i.Invoice_Shipping-i.Invoice_Discount)*0.2, 2) AS `20-0`,
ROUND((i.Invoice_Net+i.Invoice_Shipping-i.Invoice_Discount)*0.175, 2) AS `17-5`,
i.Invoice_Tax,
ROUND((i.Invoice_Tax-ROUND((i.Invoice_Net+i.Invoice_Shipping-i.Invoice_Discount)*0.175, 2)), 2) AS TaxDiff
FROM invoice AS i
INNER JOIN orders AS o ON o.Order_ID=i.Order_ID AND o.Created_On<'2011-01-04 00:00:00'
WHERE i.Created_On>='2011-01-04 00:00:00' AND i.Invoice_Shipping>0
AND i.Invoice_ID>168051 AND i.Invoice_ID<=168519 -- CHANGE HERE FOR MORE
HAVING ABS(`20-0`-i.Invoice_Tax)<=0.01
ORDER BY i.Invoice_ID ASC");

while($data->Row) {
	$order = new Order($data->Row['Order_ID']);
	$order->PaymentMethod->Get();
	$order->Customer->Get();
	$order->Customer->Contact->Get();
	
	$creditNote = new CreditNote();
	$creditNote->Order->ID = $order->ID;
	$creditNote->NominalCode = $order->NominalCode;
	
	if($data->Row['Payment_ID'] == 0) {
		$creditNote->CreditType = 'Account Credited';
		$creditNote->CreditStatus = 'Authorised';
	} else {
		$creditNote->CreditType = 'Card Refund';
		$creditNote->CreditStatus = 'Pending';
	}

	$creditNote->TotalNet = $data->Row['TaxDiff'];
	$creditNote->Total = $data->Row['TaxDiff'];
	$creditNote->CreditedOn = $data->Row['Created_On'];

	$creditLine = new CreditNoteLine();
	$creditLine->Quantity = 1;
	$creditLine->Description = 'VAT Correction on Invoice';
	$creditLine->Price = $data->Row['TaxDiff'];;
	$creditLine->TotalNet = $data->Row['TaxDiff'];
	$creditLine->Total = $data->Row['TaxDiff'];
	
	$creditNote->Line[] = $creditLine;
	
	$valid = true;
	
	if($data->Row['Payment_ID'] > 0) {
		$gateway = new PaymentGateway();

		if($gateway->GetDefault()) {
			require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/gateways/' . $gateway->ClassFile);
			
			$payment = new Payment($data->Row['Payment_ID']);
			
			$paymentProcessor = new PaymentProcessor($gateway->VendorName, $gateway->IsTestMode);
			$paymentProcessor->Amount = number_format($creditNote->Total, 2, '.', '');
			$paymentProcessor->Description = $GLOBALS['COMPANY'] . ' Card Refund';

			$billing = &$order->Customer->Contact->Person->Address;
			$addressData = array();
			if(!empty($billing->Line1)) $addressData[] = $billing->Line1;
			if(!empty($billing->Line2)) $addressData[] = $billing->Line2;
			if(!empty($billing->Line3)) $addressData[] = $billing->Line3;
			if(!empty($billing->City)) $addressData[] = $billing->City;
			if(!empty($billing->Region->Name)) $addressData[] = $billing->Region->Name;
			if(!empty($billing->Country->Name)) $addressData[] = $billing->Country->Name;
			$addressString = implode(', ', $addressData);

			$paymentProcessor->BillingAddress = $addressString;
			$paymentProcessor->BillingPostcode = $billing->Zip;
			$paymentProcessor->ContactNumber = $order->Customer->Contact->Person->Phone1;
			$paymentProcessor->CustomerEMail = $order->Customer->GetEmail();

			$paymentProcessor->CardHolder = sprintf('%s %s %s', $order->Card->Title, $order->Card->Initial, $order->Card->Surname);
			$paymentProcessor->CardNumber = $order->Card->GetNumber();
			$paymentProcessor->ExpiryDate = $order->Card->Expires;
			$order->Card->Type->Get();
			$paymentProcessor->CardType = $order->Card->Type->Reference;
			$paymentProcessor->ClientNumber = $order->Customer->ID;
			$paymentProcessor->Payment->Gateway->ID = $gateway->ID;
			$paymentProcessor->Payment->Order->ID = $order->ID;

			if(!$paymentProcessor->RefundCard($payment)){
				echo sprintf('Failed - Invoice ID: #%d<br />', $data->Row['Invoice_ID']);
				
				$valid = false;
			} else {
				$creditNote->CreditStatus = 'Authorised';
			}
		}
	}

	if($valid) {
		$creditNote->Add();

		for($i=0; $i <count($creditNote->Line); $i++){
			if($creditNote->Line[$i]->Total > 0){
				$creditNote->Line[$i]->CreditNoteID = $creditNote->ID;
				$creditNote->Line[$i]->Add();
			}
		}

		$creditNote->EmailCustomer();
		
		$note = new OrderNote();
		//$note->Message = sprintf('Due to a system error a partial miscalculation of the VAT was made on your order, dispatched at the new rate 20%% instead of 17.5%% please accept our apologies and your card has been refunded the amount of &pound;%s. Should you have any further queries please do not hesitate to contact our customer services department on 01473 559501.', number_format($data->Row['TaxDiff'], 2, '.', ','));
		$note->Message = sprintf('Due to a system error a miscalculation of the VAT was made on your order, dispatched at the new rate 20%% instead of 17.5%% please accept our apologies and your card has been refunded the amount of &pound;%s. Should you have any further queries please do not hesitate to contact our customer services department on 01473 559501.', number_format($data->Row['TaxDiff'], 2, '.', ','));
		$note->TypeID = 1;
		$note->OrderID = $data->Row['Order_ID'];
		$note->IsPublic = 'Y';
		$note->IsAlert = 'N';
		$note->Add();
		
		$note->SendToCustomer($order->Customer->Contact->Person->GetFullName(), $order->Customer->GetEmail());
		
		echo sprintf('Success - Invoice ID: #%d<br />', $data->Row['Invoice_ID']); 
	}
		
	$data->Next();
}
$data->Disconnect();