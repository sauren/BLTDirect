<?php
ini_set('max_execution_time', '3000');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Payment.php');

$data = new DataQuery(sprintf("SELECT o.Order_ID, o.Custom_Order_No, p2.Amount FROM orders AS o INNER JOIN payment AS p2 ON p2.Order_ID=o.Order_ID AND p2.Status LIKE 'INITIATED' LEFT JOIN payment AS p ON p.Order_ID=o.Order_ID AND p.Status LIKE 'OK' WHERE o.Payment_Method_ID=8 AND o.Status LIKE 'Pending' AND p.Payment_ID IS NULL ORDER BY o.Order_ID DESC"));
while($data->Row) {
	$payment = new Payment();
	$payment->Order->ID = $data->Row['Order_ID'];
	$payment->Type = 'PAYMENT';
	$payment->SecurityKey = 'GoogleCheckout';
	$payment->Reference = '';
	$payment->Status = 'OK';
	$payment->StatusDetail = 'Payment taken through Google Checkout.';
	$payment->Amount = $data->Row['Amount'];
	$payment->Add();
			
	$data->Next();
}
$data->Disconnect();