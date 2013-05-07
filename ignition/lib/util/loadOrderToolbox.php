<?php
require_once('../classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

$product = new Product();

echo sprintf('min=%d', ($product->Get($_REQUEST['id'])) ? $product->OrderMin : 0);

$GLOBALS['DBCONNECTION']->Close();
?>