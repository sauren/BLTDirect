<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');

$session->Secure(2);

$cart = new Purchase(null, $session);
$cart->PSID = $session->ID;

if(!$cart->Exists()){
	$cart->SetDefaults();
	$cart->Add();
}

$product = new Product();

if(!isset($_REQUEST['product']) || !$product->Get($_REQUEST['product'])) {
	redirectTo('warehouse_reserve_create.php');
}

$quantity = (isset($_REQUEST['quantity']) && is_numeric($_REQUEST['quantity'])) ? $_REQUEST['quantity'] : 1;

if(isset($_REQUEST['action']) && strtolower($_REQUEST['action']) == 'customise') {
	$cart->AddLine($product->ID, $quantity);
	$cart->SetSuppliers();

	redirectTo("warehouse_reserve_create.php");
}