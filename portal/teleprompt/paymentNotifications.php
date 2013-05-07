<?php
$ignoreHeader = true;

require_once('../../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Session.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Page.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/User.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/UserRecent.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Country.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/GlobalTaxCalculator.php');

//$session = new Session();
//$session->Record();

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
$paymentProcessor->notify('portal/teleprompt/');

require_once('lib/common/appFooter.php');