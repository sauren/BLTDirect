<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

$groups = array();
$groups[223] = false;
$groups[224] = false;
$groups[221] = false;
$groups[41] = false;

$zeroMatch = 'Sorry, no matches found.';

$specificationGroups = array();
$specificationValues = array();

foreach($groups as $groupId=>$groupData) {
	$data = new DataQuery(sprintf("SELECT COUNT(*) AS count FROM product_specification_combine WHERE productSpecificationGroupId=%d", mysql_real_escape_string($groupId)));	
	if($data->Row['count'] > 0) {
		$groups[$groupId] = true;
	}
	$data->Disconnect();
}

$uncombinedGroups = array();

foreach($groups as $groupId=>$groupData) {
	$uncombinedGroups[] = $groupId;
}

$data = new DataQuery(sprintf("SELECT Group_ID, Name, Data_Type FROM product_specification_group WHERE Group_ID IN (%s) ORDER BY Name ASC", implode(', ', $uncombinedGroups)));
while($data->Row) {
	$specificationGroups[$data->Row['Group_ID']] = $data->Row;
	$specificationValues[$data->Row['Group_ID']] = array();
	
	if(!$groups[$data->Row['Group_ID']]) {
		$data2 = new DataQuery(sprintf("SELECT psv.Group_ID, psv.Value_ID, psv.Value, CONCAT_WS(' ', psv.Value, psg.Units) AS UnitValue FROM product_specification_value AS psv INNER JOIN product_specification AS ps ON ps.Value_ID=psv.Value_ID INNER JOIN product_specification_group AS psg ON psg.Group_ID=psv.Group_ID WHERE psv.Group_ID=%d AND psv.Hide='N' GROUP BY psv.Value_ID ORDER BY Value ASC", mysql_real_escape_string($data->Row['Group_ID'])));
		while($data2->Row) {
			$specificationValues[$data->Row['Group_ID']][] = $data2->Row;
			
			$data2->Next();
		}
		$data2->Disconnect();
	} else {
		$data2 = new DataQuery(sprintf("SELECT id, name FROM product_specification_combine WHERE productSpecificationGroupId=%d ORDER BY name ASC", mysql_real_escape_string($data->Row['Group_ID'])));
		while($data2->Row) {
			$specificationValues[$data->Row['Group_ID']][] = $data2->Row;
			
			$data2->Next();
		}
		$data2->Disconnect();
	}

	if($data->Row['Data_Type'] == 'numeric') {
		$sortArray = array();
		$cacheArray = $specificationValues[$data->Row['Group_ID']];
		
		for($j=0; $j<count($specificationValues[$data->Row['Group_ID']]); $j++) {
			$sortArray[$specificationValues[$data->Row['Group_ID']][$j]['Value_ID']] = $specificationValues[$data->Row['Group_ID']][$j]['Value'];
		}

		asort($sortArray, SORT_NUMERIC);
		
		$specificationValues[$data->Row['Group_ID']] = array();
		
		foreach($sortArray as $valueId=>$value) {
			for($j=0; $j<count($cacheArray); $j++) {
				if($cacheArray[$j]['Value_ID'] == $valueId) {
					$specificationValues[$data->Row['Group_ID']][] = $cacheArray[$j];
					break;
				}
			}
		}
	}
	
	$data->Next();
}
$data->Disconnect();

$form = new Form($_SERVER['PHP_SELF'], 'GET');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

foreach($specificationGroups as $group) {
	$form->AddField('group_' . $group['Group_ID'], $group['Name'], 'select', '0', 'numeric_unsigned', 1, 11, false, 'onchange="getRightFinderResults(this);"');	
	$form->AddOption('group_' . $group['Group_ID'], '0', '');
	
	foreach($specificationValues[$group['Group_ID']] as $value) {
		if(!$groups[$group['Group_ID']]) {
			$form->AddOption('group_' . $group['Group_ID'], $value['Value_ID'], $value['UnitValue']);
		} else {
			$form->AddOption('group_' . $group['Group_ID'], $value['id'], $value['name']);
		}
	}
	
	if(param('finder_group_'.$group['Group_ID'])) {
		$form->SetValue('group_' . $group['Group_ID'], param('finder_group_' . $group['Group_ID']));
	}
}

$sql = '';
$sqlTotalRows = '';

$productPrices = array();
$productOffers = array();

$totalResults = 0;

if(param('confirm')) {
	$sqlSelect = '';
	$sqlFrom = '';
	$sqlWhere = '';

	$index = 0;
	
	foreach($specificationGroups as $group) {
		if($form->GetValue('group_' . $group['Group_ID']) > 0) {
			$index++;
			
			if(!$groups[$group['Group_ID']]) {
				$sqlFrom .= sprintf('INNER JOIN product_specification AS ps%1$d ON ps%1$d.Product_ID=p.Product_ID AND ps%1$d.Value_ID=%2$d ', $index, $form->GetValue('group_' . $group['Group_ID']));
			} else {
				$sqlFrom .= sprintf('INNER JOIN product_specification AS ps%1$d ON ps%1$d.Product_ID=p.Product_ID INNER JOIN product_specification_combine_value AS pscv%1$d ON pscv%1$d.productSpecificationValueId=ps%1$d.Value_ID AND pscv%1$d.productSpecificationCombineId=%2$d ', $index, $form->GetValue('group_' . $group['Group_ID']));
			}
		}
	}

	$sql = sprintf("SELECT p.Product_ID, p.Product_Title, p.Discontinued, p.Discontinued_Show_Price, p.Product_Codes, p.Cache_Specs_Primary, p.Meta_Title, p.SKU, p.Order_Min, p.Average_Despatch, p.CacheBestCost, p.CacheRecentCost, pi.Image_Thumb, MIN(ws.Backorder_Expected_On) AS Backorder_Expected_On %s FROM product AS p LEFT JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID AND ws.Is_Backordered='Y' LEFT JOIN product_images AS pi ON pi.Product_ID=p.Product_ID AND pi.Is_Active='Y' AND pi.Is_Primary='Y' %s WHERE p.Is_Active='Y' AND p.Is_Demo_Product='N' AND p.Discontinued='N' AND p.Integration_ID=0 AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) %s GROUP BY p.Product_ID", $sqlSelect, $sqlFrom, $sqlWhere);
	$sqlTotalRows = sprintf("SELECT COUNT(*) AS TotalRows FROM product AS p %s WHERE p.Is_Active='Y' AND p.Is_Demo_Product='N' AND p.Discontinued='N' AND p.Integration_ID=0 AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) %s", $sqlFrom, $sqlWhere);

	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_prices SELECT p.Product_ID, pp.Price_Base_Our, pp.Price_Base_RRP, pp.Quantity, pp.Price_Starts_On FROM product AS p INNER JOIN product_prices AS pp ON p.Product_ID=pp.Product_ID AND pp.Price_Starts_On<=NOW() %s WHERE p.Is_Active='Y' AND p.Is_Demo_Product='N' AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) %s", mysql_real_escape_string($sqlFrom), mysql_real_escape_string($sqlWhere)));
	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_offers SELECT p.Product_ID, po.Price_Offer, po.Offer_Start_On FROM product AS p INNER JOIN product_offers AS po ON p.Product_ID=po.Product_ID AND ((po.Offer_Start_On<=NOW() AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On='000-00-00 00:00:00') OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On<=NOW() AND po.Offer_End_On='0000-00-00 00:00:00')) %s WHERE p.Is_Active='Y' AND p.Is_Demo_Product='N' AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) %s", mysql_real_escape_string($sqlFrom), mysql_real_escape_string($sqlWhere)));

	$data = new DataQuery(sprintf("SELECT * FROM temp_prices ORDER BY Price_Starts_On ASC"));
	while($data->Row) {
		if(!isset($productPrices[$data->Row['Product_ID']])) {
			$productPrices[$data->Row['Product_ID']] = array();
		}

		$item = array();
		$item['Price_Base_Our'] = $data->Row['Price_Base_Our'];
		$item['Price_Base_RRP'] = $data->Row['Price_Base_RRP'];

		$productPrices[$data->Row['Product_ID']][$data->Row['Quantity']] = $item;

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT * FROM temp_offers ORDER BY Offer_Start_On ASC"));
	while($data->Row) {
		if(!isset($productOffers[$data->Row['Product_ID']])) {
			$productOffers[$data->Row['Product_ID']] = array();
		}

		$item = array();
		$item['Price_Offer'] = $data->Row['Price_Offer'];

		$productOffers[$data->Row['Product_ID']][$data->Row['Price_Offer']] = $item;

		$data->Next();
	}
	$data->Disconnect();
	
	$data = new DataQuery($sqlTotalRows);
	$totalResults = $data->Row['TotalRows'];
	$data->Disconnect();
}

$table = new DataTable('products');
$table->SetSQL($sql);
$table->SetExtractVars();
$table->SetTotalRowSQL($sqlTotalRows);
$table->SetMaxRows(15);
$table->SetOrderBy('Product_Title');
$table->Order = 'ASC';

if(!empty($sql)) {
	$table->Finalise();
	$table->ExecuteSQL();
}
//require_once('lib/mobile' . $_SERVER['PHP_SELF']);
require_once('lib/' . $renderer . $_SERVER['PHP_SELF']);
require_once('lib/common/appFooter.php');