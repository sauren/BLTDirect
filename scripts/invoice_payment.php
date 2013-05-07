<?php
ini_set('max_execution_time', '3600');
ini_set('display_errors','on');
ini_set('memory_limit', '1024M');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Invoice.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PaymentGateway.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();
$GLOBALS['SITE_LIVE'] = false;

$gateway = new PaymentGateway();

if($gateway->GetDefault()) {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/gateways/' . $gateway->ClassFile);

	$data101 = new DataQuery(sprintf("SELECT * FROM invoice WHERE Invoice_Total>0 AND Created_On>'2010-03-15 00:00:00' AND Payment_Method_ID=1 AND Payment_ID=0"));
	while($data101->Row) {
		$invoice = new Invoice($data101->Row['Invoice_ID']);

		$paymentProcessor = new PaymentProcessor($gateway->VendorName, $gateway->IsTestMode);
		$paymentProcessor->Amount = $invoice->Total;
		$paymentProcessor->Description = $GLOBALS['COMPANY'] . ' Invoice #' . $invoice->ID;
		$paymentProcessor->Payment->Gateway->ID = $gateway->ID;
		$paymentProcessor->Payment->Order->ID = $invoice->Order->ID;

		$payment = new Payment();

		$processPayment = false;
		$success = false;

		// check for AUTHENTICATE transaction
		$data = new DataQuery(sprintf("SELECT Payment_ID FROM payment WHERE Transaction_Type LIKE 'AUTHENTICATE' AND (Status LIKE 'REGISTERED' OR Status LIKE '3DAUTH') AND Reference!='' AND Order_ID=%d ORDER BY Payment_ID DESC LIMIT 0, 1", $invoice->Order->ID));
		if($data->TotalRows > 0) {
			$data83 = new DataQuery(sprintf("SELECT Payment_ID FROM payment WHERE Transaction_Type LIKE 'CANCEL' AND Status LIKE 'OK' AND Order_ID=%d AND Payment_ID>%d", $invoice->Order->ID, $data->Row['Payment_ID']));
			if($data83->TotalRows == 0) {
				$payment->Get($data->Row['Payment_ID']);

				$success = $paymentProcessor->Authorise($payment);

				if(!$success) {
					$processPayment = true;
				}
			} else {
				$processPayment = true;
			}
			$data83->Disconnect();
		} else {
			$processPayment = true;
		}
		$data->Disconnect();

		if($processPayment) {
			echo "Payment failed: ".$invoice->ID.'<br />';
		} else {
            $paymentProcessor->Payment->Invoice->ID = $invoice->ID;

			if(!empty($gateway->ID)){
				$invoice->Payment = $paymentProcessor->Payment->ID;
				$invoice->Paid = $invoice->Total;
				$invoice->Update();

				$paymentProcessor->Payment->Invoice->ID = $invoice->ID;
				$paymentProcessor->Payment->PaidOn = getDatetime();
				$paymentProcessor->Payment->Update();
			}

			echo "Payment success: ".$invoice->ID.'<br />';
		}

		$data101->Next();
	}
	$data101->Disconnect();
}

$GLOBALS['DBCONNECTION']->Close();