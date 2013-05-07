<?php
require_once('../../../../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Product.php');

$product = new Product();
if(!$product->Get($_REQUEST['id'])) {
	header("HTTP/1.0 400 Bad Request");
} else {
	echo sprintf("%s{br}\n", $product->ID);
	echo sprintf("%s{br}\n", $product->Name);
}

$GLOBALS['DBCONNECTION']->Close();
?>