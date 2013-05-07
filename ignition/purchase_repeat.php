<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseLine.php');

$session->Secure(3);

if(!isset($_REQUEST['pid'])) {
	redirect("Location: purchase_administration.php");
}

$purchase = new Purchase($_REQUEST['pid']);
$purchase->Status = 'Unfulfilled';
$purchase->PurchasedOn = date('Y-m-d H:i:s');
$purchase->Add();

redirect("Location: purchase_administration.php");