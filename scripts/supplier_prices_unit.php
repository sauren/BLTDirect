<?php
ini_set('max_execution_time', '30000');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');

$prices = array();

$data = new DataQuery(sprintf("SELECT sp.Supplier_ID, sp.Product_ID, sp.Cost FROM supplier_product_price AS sp WHERE sp.Quantity=1 ORDER BY Supplier_Product_Price_ID ASC"));
while($data->Row) {
	if(!isset($prices[$data->Row['Product_ID']])) {
		$prices[$data->Row['Product_ID']] = array();
	}
	
	$prices[$data->Row['Product_ID']][$data->Row['Supplier_ID']] = $data->Row['Cost'];
	
	$data->Next();
}
$data->Disconnect();

foreach($prices as $productId=>$productData) {
	foreach($productData as $supplierId=>$cost) {
		$data = new DataQuery(sprintf("SELECT Supplier_Product_ID FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", $supplierId, $productId));
		if($data->TotalRows) {
			new DataQuery(sprintf("UPDATE supplier_product SET Cost=%f WHERE Supplier_Product_ID=%d", $cost, $data->Row['Supplier_Product_ID']));
		} else {
			$product = new SupplierProduct();
			$product->Supplier->ID = $supplierId;
			$product->Product->ID = $productId;
			$product->Cost = $cost;
			$product->Add();
		}
		$data->Disconnect();
	}
}

$GLOBALS['DBCONNECTION']->Close();