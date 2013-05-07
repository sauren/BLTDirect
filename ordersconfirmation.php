<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');

$session->Secure();
$order = new Order();

if($order->Get(id_param('orderid'))) {
	if($order->Customer->ID == $session->Customer->ID)
	$order->GetLines();
	else {
		redirect('Location: orders.php');
	}
}

require_once('lib/' . $renderer . $_SERVER['PHP_SELF']);
require_once('lib/common/appFooter.php');