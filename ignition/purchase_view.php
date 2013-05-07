<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');

$session->Secure(2);

if(isset($_REQUEST['purchaseid'])){
	$purchase = new Purchase($_REQUEST['purchaseid']);

	echo $purchase->GetDocToBuy();
}