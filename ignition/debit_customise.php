<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/BreadCrumb.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Debit.php');

$session->Secure(2);

global $cart;
$cart = new Debit(null,$session);
$cart->PSID = $session->ID;
if($cart->Exists()==false){
	$cart->Add();
}

$referrer = 'None (Manual Order)';

if(isset($_REQUEST['product']) && is_numeric($_REQUEST['product'])){
	$product = new Product($_REQUEST['product']);
} else {
	echo "No Product ID was received";
	exit;
}

$productQty = (isset($_REQUEST['quantity']) && is_numeric($_REQUEST['quantity']))?$_REQUEST['quantity']:1;
$productCat = (isset($_REQUEST['category']) && is_numeric($_REQUEST['category']))?$_REQUEST['category']:0;
$breadCrumb = new BreadCrumb();
$breadCrumb->Get($productCat, true);

if(isset($_REQUEST['action']) && strtolower($_REQUEST['action']) == 'customise'){
	$acceptProductCheck = new DataQuery(sprintf('SELECT * FROM warehouse_stock WHERE Product_ID = %d', mysql_real_escape_string($product->ID)));
	if($acceptProductCheck->TotalRows >0){
		$cart->AddLine($product->ID, $productQty);
		$cart->SetSuppliers();
		$acceptProductCheck->Disconnect();
	}else{
		$acceptProductCheck->Disconnect();
	}

	redirect("Location: debit_create.php");
}
?>