<?php
$maxItems = isset($maxItems) ? $maxItems : 10;

$cache = Zend_Cache::factory('Output', $GLOBALS['CACHE_BACKEND'], array('lifetime' => 86400, 'automatic_serialization' => true));
$cacheId = 'best_' . $subCategory->ID;
$cacheData = array();

if(($cacheData = $cache->load($cacheId)) === false) {
	$cacheData = array();
	
	$data = new DataQuery(sprintf("SELECT p.Product_ID FROM product AS p INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID AND pic.Category_ID IN (%s) WHERE p.Position_Orders_Recent>0 AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' GROUP BY p.Product_ID ORDER BY p.Position_Orders_Recent ASC LIMIT 0, %d", implode(', ', getDirectionalCategories(array($subCategory->ID))), mysql_real_escape_string($maxItems)));
	while($data->Row) {
		$cacheData[] = $data->Row['Product_ID'];
		
		$data->Next();
	}
	$data->Disconnect();
	
	$cache->save($cacheData, $cacheId);
}