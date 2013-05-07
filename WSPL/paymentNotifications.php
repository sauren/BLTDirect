<?php
$ignoreHeader = true;
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerContact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PaymentGateway.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Log.php');

$gateway = new PaymentGateway();
$gateway->GetDefault();

require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/gateways/SagePay.php'); // todo: convert to run from database.
$paymentProcessor = new PaymentProcessor($gateway->VendorName, $gateway->IsTestMode);
$paymentProcessor->notify();

require_once('../lib/common/appFooter.php');