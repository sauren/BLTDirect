<?php
ini_set('max_execution_time', '3000');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Order.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/OrderNote.php');

$order = new Order(137336);
$order->PaymentMethod->Get();
$order->Customer->Get();
$order->Customer->Contact->Get();

$order->IsNotReceived = 'N';
$order->Update();

if($order->PaymentMethod->Reference == 'google') {
	$order->Card = new Card();
	$order->CustomID = '';
}

$order->IsCustomShipping = 'Y';
$order->TotalShipping = 0;
$order->OrderedOn = date('Y-m-d H:i:s');
$order->CustomID = '';
$order->Status = 'Unread';
$order->Prefix = 'N';
$order->Referrer = '';
$order->PaymentMethod->GetByReference('foc');
$order->ParentID = $order->ID;
$order->Add();

$order->Recalculate();