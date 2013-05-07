<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerContact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PaymentGateway.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Payment.php');

$gateway = new PaymentGateway();
$gateway->GetDefault();
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/gateways/SagePay.php'); // todo: convert to run from database.
$paymentProcessor = new PaymentProcessor($gateway->VendorName, $gateway->IsTestMode);
$strReason = $paymentProcessor->getPaymentStatus(id_param('VendorTxCode'));
include("ui/nav.php");
include("ui/search.php");?>
		<p><img src="images/failed.png" /></p>
		<h1>Sorry...</h1>
		<p>We were unable to pre-authorise your card for &pound;<?php echo $payment->Amount; ?></p>
		<p><?php echo $strReason; ?></p>
		<p>Please call us on <strong>01473 716 418</strong> quoting your order reference <strong><?php echo $payment->Order->Prefix . $payment->Order->ID; ?></strong>.</p>
<?php include("ui/footer.php");