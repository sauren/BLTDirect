<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PaymentGateway.php');

$session->Secure();

$o = base64_decode($_REQUEST['o']);
$orderNum = new Cipher($o);
$orderNum->Decrypt();

$order = new Order();

if(!$order->Get($orderNum->Value) || ($order->Status != 'Unauthenticated')) {
	redirect("Location: cart.php");
}

$gateway = new PaymentGateway();
$hasGateway = $gateway->GetDefault();

if(!$hasGateway || (strtoupper($gateway->HasPreAuth) == 'N')){
	redirect("Location: cart.php");
}

require_once('lib/' . $renderer . $_SERVER['PHP_SELF']);
require_once('lib/common/appFooter.php');