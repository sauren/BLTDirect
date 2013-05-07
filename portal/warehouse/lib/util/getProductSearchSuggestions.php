<?php
require_once('../../../../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductCookie.php");

if(!isset($_REQUEST['criteria'])) {
	header("HTTP/1.0 400 Bad Request");
} else {
	$products = array();
	$output = array();
	$items = array();

	$limit = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : 10;
	$criteria = explode(' ', trim($_REQUEST['criteria']));

	for($i=0; $i<count($criteria); $i++) {
		$items[] = sprintf('(Product_Title LIKE \'%1$s%%\' OR Product_Title LIKE \'%% %1$s%%\' OR SKU LIKE \'%%%1$s%%\' OR Product_ID LIKE \'%1$s%%\')', mysql_real_escape_string($criteria[$i]));
	}

	$data = new DataQuery(sprintf("SELECT Product_ID, Product_Title, SKU FROM product WHERE %s AND Discontinued='N' AND Is_Demo_Product='N' ORDER BY Product_Title ASC LIMIT 0, %d", implode(' AND ', mysql_real_escape_string($items)), mysql_real_escape_string($limit)));
	while($data->Row) {
		$products[] = array(strip_tags($data->Row['Product_Title']), $data->Row['Product_ID'], $data->Row['SKU']);

		$data->Next();
	}
	$data->Disconnect();

	foreach($products as $product) {
		$output[] = implode('{br}', $product);
	}

	echo implode('{br}{br}', $output);

	$cookie = new ProductCookie();

	$maxItems = 5;
	$recentProducts = $cookie->GetProducts();
	$recentCount = count($recentProducts);

	if($recentCount > 0) {
		$limit = ($recentCount > $maxItems) ? $maxItems : $recentCount;
		$items = array();
		$products = array();
		$output = array();

		echo '{br}{br}{br}';

		foreach($recentProducts as $productId) {
			$subItems = array();

			for($i=0; $i<count($criteria); $i++) {
				$subItems[] = sprintf('(Product_Title LIKE \'%1$s%%\' OR Product_Title LIKE \'%% %1$s%%\' OR SKU LIKE \'%%%1$s%%\' OR Product_ID LIKE \'%1$s%%\')', mysql_real_escape_string($criteria[$i]));
			}

			if(count($subItems) > 0) {
				$items[] = sprintf('(Product_ID=%1$s AND (%2$s))', $productId, implode(' OR ', $subItems));
			}
		}

		$data = new DataQuery(sprintf("SELECT Product_ID, Product_Title, SKU FROM product WHERE (%s) AND Discontinued='N' AND Is_Demo_Product='N' ORDER BY Product_Title ASC LIMIT 0, %d", implode(' OR ', mysql_real_escape_string($items)), mysql_real_escape_string($limit)));
		while($data->Row) {
			$products[] = array(strip_tags($data->Row['Product_Title']), $data->Row['Product_ID'], $data->Row['SKU']);

			$data->Next();
		}
		$data->Disconnect();

		foreach($products as $product) {
			$output[] = implode('{br}', $product);
		}

		echo implode('{br}{br}', $output);
	}
}

$GLOBALS['DBCONNECTION']->Close();
?>