<?php
require_once('../../lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'services/google-checkout/classes/GoogleRequest.php');

error_fatal(0);

$googleRequest = new GoogleRequest();

## UNCOMMENT TO EXECUTE TEST
//$googleRequest->chargeOrder('274153611768871', 0, '0.01');
//$googleRequest->addMerchantOrderNumber('507024352090730', '50102');
//$googleRequest->processOrder('507024352090730');
//$googleRequest->deliverOrder('507024352090730', 'FedEx', 'testthis', true);
//$googleRequest->refundOrder('507024352090730', '103.23');
//$googleRequest->cancelOrder('333897507999704');
//$googleRequest->shipItems('333897507999704', array(123, 124, 125), "UPS", "123456E");

$GLOBALS['DBCONNECTION']->Close();