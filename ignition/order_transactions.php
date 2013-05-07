<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CreditNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CreditNoteLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Invoice.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/InvoiceLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Payment.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PaymentGateway.php');

if($action == 'correct') {
	$session->Secure(3);
	correct();
	exit;
} else {
    $session->Secure(2);
	view();
	exit;
}

function correct() {
	$payment = new Payment($_REQUEST['txid']);
	$payment->Order->Get();

	if($payment->Order->PaymentMethod->Reference == 'card') {
		$invoice = new Invoice();
		$invoice->TaxRate = $order->GetTaxRate();
		$invoice->IsCorrection = 'Y';
	    $invoice->Order->ID = $payment->Order->ID;
		$invoice->Customer->ID = $payment->Order->Customer->ID;
		$invoice->PaymentMethod->ID = $order->PaymentMethod->ID;
		$invoice->IsDespatched = 'Y';
	    $invoice->DueOn = date('Y-m-d H:i:s');
	    $invoice->IsPaid = 'Y';
	    $invoice->Organisation = $payment->Order->InvoiceOrg;
		$invoice->Person->Title = $payment->Order->Invoice->Title;
		$invoice->Person->Name = $payment->Order->Invoice->Name;
		$invoice->Person->Initial = $payment->Order->Invoice->Initial;
		$invoice->Person->LastName = $payment->Order->Invoice->LastName;
		$invoice->Person->Address->Line1 = $payment->Order->Invoice->Address->Line1;
		$invoice->Person->Address->Line2 = $payment->Order->Invoice->Address->Line2;
		$invoice->Person->Address->Line3 = $payment->Order->Invoice->Address->Line3;
		$invoice->Person->Address->City = $payment->Order->Invoice->Address->City;
		$invoice->Person->Address->Region->ID = $payment->Order->Invoice->Address->Region->ID;
		$invoice->Person->Address->Region->Get();
		$invoice->Person->Address->Country->ID = $payment->Order->Invoice->Address->Country->ID;
		$invoice->Person->Address->Country->Get();
		$invoice->Person->Address->Zip = $payment->Order->Invoice->Address->Zip;
		$invoice->NominalCode = $payment->Order->NominalCode;

		$line = new InvoiceLine();
		$line->Description = 'Correction invoice.';
		$line->Quantity = 1;
		$line->Price = ($payment->Amount / (100 + $invoice->TaxRate)) * 100;
		$line->Total = $line->Price * $line->Quantity;
		$line->Tax = $payment->Amount - $line->Total;

	    $invoice->SubTotal = $line->Price;
		$invoice->Tax = $line->Tax;
		$invoice->Total = $invoice->SubTotal + $invoice->Tax;

		switch(strtoupper($payment->Type)) {
			case 'AUTHORISE':
				$replacePayment = new Payment();
				$replacePayment->Type = 'AUTHORISE';
				$replacePayment->Status = 'OK';
				$replacePayment->StatusDetail = 'Payment registered due to failed transaction.';
				$replacePayment->Amount = $invoice->Total;
				$replacePayment->Order->ID = $invoice->Order->ID;
				$replacePayment->PaidOn = $invoice->DueOn;
		
				break;

			case 'REFUND':
                $gateway = new PaymentGateway();

				if($gateway->GetDefault()){
					require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/gateways/' . $gateway->ClassFile);

					$paymentProcessor = new PaymentProcessor($gateway->VendorName, $gateway->IsTestMode);

					if($invoice->Total > 0) {
						$paymentProcessor->Amount = $invoice->Total;
						$paymentProcessor->Description = $GLOBALS['COMPANY'] . ' Order #' . $payment->Order->ID;
						$paymentProcessor->Payment->Gateway->ID = $gateway->ID;
						$paymentProcessor->Payment->Order->ID = $payment->Order->ID;

						$paymentTransaction = new Payment();

						$success = false;

						$data = new DataQuery(sprintf("SELECT Payment_ID FROM payment WHERE Transaction_Type LIKE 'AUTHENTICATE' AND (Status LIKE 'REGISTERED' OR Status LIKE '3DAUTH') AND Reference!='' AND Order_ID=%d ORDER BY Payment_ID DESC LIMIT 0, 1", mysql_real_escape_string($payment->Order->ID)));
						if($data->TotalRows > 0) {
							$data2 = new DataQuery(sprintf("SELECT Payment_ID FROM payment WHERE Transaction_Type LIKE 'CANCEL' AND Status LIKE 'OK' AND Order_ID=%d AND Payment_ID>%d", mysql_real_escape_string($payment->Order->ID), mysql_real_escape_string($data->Row['Payment_ID'])));
							if($data2->TotalRows == 0) {
								$paymentTransaction->Get($data->Row['Payment_ID']);

								$success = $paymentProcessor->Authorise($paymentTransaction);
							}
							$data2->Disconnect();
						}
						$data->Disconnect();

						if($success) {
							$invoice->Payment->ID = $paymentTransaction->ID;
						} else {
							$page = new Page('Payment Error', 'An error occured whilst trying to charge the credit card. Details below:');
							$page->Display('header');

							echo sprintf('<p><a href="?orderid=%d">Back to Order Payment Transactions</a></p>', $payment->Order->ID);

							for($i=0; $i < count($paymentProcessor->Error); $i++){
								echo '<p>' . $paymentProcessor->Error[$i] . '</p>';
							}

							echo '<p>You may need to change the card details for this order. Please contact the customer and try again later.</p>';

							$page->Display('footer');
							require_once('lib/common/app_footer.php');
							exit;
						}
					}
				}

				$credit = new CreditNote();
				$credit->IsCorrection = 'Y';
                $credit->Order->ID = $payment->Order->ID;
				$credit->CreditType = 'Card Refund';
				$credit->CreditStatus = 'Authorised';
				$credit->TaxRate = $invoice->TaxRate;
				$credit->TotalNet = $invoice->SubTotal;
				$credit->TotalTax = $invoice->Tax;
				$credit->Total = $invoice->Total;
				$credit->NominalCode = $invoice->NominalCode;
				$credit->Add();

				$creditLine = new CreditNoteLine();
				$creditLine->Quantity = 1;
				$creditLine->Description = 'Correction refund.';
                $creditLine->Price = $line->Price;
				$creditLine->TotalNet = $creditLine->Price * $creditLine->Quantity;
				$creditLine->TotalTax = $payment->Amount - $creditLine->TotalNet;
				$creditLine->Total = $creditLine->TotalNet + $creditLine->TotalTax;
				$creditLine->CreditNoteID = $credit->ID;
				$creditLine->Add();

				break;
		}

		$invoice->Add();

		$line->InvoiceID = $invoice->ID;
		$line->Add();
		
		switch(strtoupper($payment->Type)) {
			case 'AUTHORISE':
				$replacePayment->Invoice->ID = $invoice->ID;
				$replacePayment->Add();
				
				$invoice->Payment->ID = $replacePayment->ID;
				$invoice->Update();
				
				break;
		}

		redirect(sprintf('Location: invoice.php?invoiceid=%d', $invoice->ID));
	}

	redirect(sprintf('Location: ?orderid=%d', $payment->Order->ID));

	require_once('lib/common/app_footer.php');
}

function view() {
	$order = new Order($_REQUEST['orderid']);

	$page = new Page('Order Payment Transactions','If you have an active payment gateway the details of every transaction between your payment gateway and Ignition is displayed below for this Order.');
	$page->Display('header');

	echo sprintf('<p><a href="order_details.php?orderid=%d">Back to Order Details</a></p>', $order->ID);

	$table = new DataTable('trans');
	$table->SetSQL(sprintf("SELECT *, IF((Status LIKE 'FAIL') AND ((Transaction_Type='AUTHORISE') OR (Transaction_Type='REFUND')), 'Y', 'N') AS Is_Correctable FROM payment WHERE Order_ID=%d", mysql_real_escape_string($order->ID)));
	$table->AddField('', 'Is_Correctable', 'hidden');
	$table->AddField('ID#', 'Payment_ID', 'right');
	$table->AddField('Type', 'Transaction_Type', 'left');
	$table->AddField('Status', 'Status', 'left');
	$table->AddField('Status Detail', 'Status_Detail', 'left');
	$table->AddField('Amount', 'Amount', 'right');
	$table->AddLink("javascript:confirmRequest('?action=correct&txid=%s', 'Are you sure you want to raise a correction invoice against this item?');", "<img src=\"images/i_document.gif\" alt=\"Correct\" border=\"0\" />", "Payment_ID", true, false, array('Is_Correctable', '=', 'Y'));
	$table->SetMaxRows(25);
	$table->SetOrderBy("Payment_ID");
	$table->Order= "desc";
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}