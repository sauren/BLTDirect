<?php
require_once('../classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SearchFailure.php');

if(!isset($_REQUEST['criteria'])) {
	header("HTTP/1.0 400 Bad Request");
} else {
	$output = array();

	$searchString = '';
	$searchFullTextSearch = array();
	$searchWhereItems = array();

	$searchString = isset($_REQUEST['criteria']) ? $_REQUEST['criteria'] : '';
	$searchString = strtolower($searchString);
	$searchString = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $searchString));

	$data = new DataQuery(sprintf("SELECT psvr.valueRewrite, psv.Value FROM product_specification_value_rewrite AS psvr INNER JOIN product_specification_value AS psv ON psv.Value_ID=psvr.valueId WHERE '%s' LIKE CONCAT('%%', psvr.valueRewrite, '%%') GROUP BY psv.Value_ID", $searchString));
	while($data->Row) {
		$searchString = str_ireplace($data->Row['valueRewrite'], $data->Row['Value'], $searchString);

		$data->Next();
	}
	$data->Disconnect();

	$searchStringReplace = $searchString;

	$data = new DataQuery(sprintf("SELECT DISTINCT unit FROM product_specification_group_unit"));
	while($data->Row) {
		$groups = array();

		$data2 = new DataQuery(sprintf("SELECT psg.Name, psg.Units FROM product_specification_group_unit AS psgu INNER JOIN product_specification_group AS psg ON psg.Group_ID=psgu.groupId WHERE psgu.unit LIKE '%s'", $data->Row['unit']));
		while($data2->Row) {
			$groups[$data2->Row['Name']] = $data2->Row['Units'];

			$data2->Next();
		}
		$data2->Disconnect();

		preg_match_all(sprintf('/([^\s]+)(\s?)%s(\s|$)+/i', $data->Row['unit']), $searchString, $matches);

		foreach($matches[0] as $match) {
			$searchStringReplace = str_replace(trim($match), '', $searchStringReplace);
		}

		foreach($matches[1] as $match) {
			$item = array();

			foreach($groups as $groupName=>$groupUnits) {
				$item[] = sprintf('p.Cache_Specs LIKE \'%1$s=%2$s %3$s%%\' OR p.Cache_Specs LIKE \'%%;%1$s=%2$s %3$s%%\'', mysql_real_escape_string($groupName), mysql_real_escape_string(trim($match)), mysql_real_escape_string($groupUnits));
			}

			$searchWhereItems[] = sprintf('(%s)', implode(' OR ', $item));
		}

		$data->Next();
	}
	$data->Disconnect();

	$searchString = $searchStringReplace;

	$searchItems = explode(' ', $searchString);

	foreach($searchItems as $searchItem) {
		if(!empty($searchItem)) {
			$searchFullTextSearch[] = sprintf('"%s"', mysql_real_escape_string($searchItem));
			$searchWhereItems[] = sprintf('(p.Product_Title LIKE \'%1$s%%\' OR p.Product_Title LIKE \'%% %1$s%%\' OR p.Product_Codes LIKE \'%%%1$s%%\' OR p.Product_ID LIKE \'%1$s%%\' OR pb.Barcode LIKE \'%1$s%%\' OR p.Cache_Specs LIKE \'%%%1$s%%\')', mysql_real_escape_string($searchItem));
		}
	}

	if(empty($searchWhereItems)) {
		header("HTTP/1.0 400 Bad Request");
	} else {
		$limit = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : 10;

		$sql = sprintf("SELECT p.Product_ID, p.Product_Title, p.Product_Codes, MATCH(Product_Title, Product_Codes, Product_Description, Meta_Title, Meta_Description, Meta_Keywords, Cache_Specs) AGAINST('%s') AS Score, IF(Position_Orders=0, 9999999, Position_Orders) AS Position FROM product AS p LEFT JOIN product_barcode AS pb ON pb.ProductID=p.Product_ID WHERE p.Is_Active='Y' AND p.Is_Demo_Product='N' AND p.Discontinued='N' AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND (%s) GROUP BY p.Product_ID ORDER BY Position ASC LIMIT 0, %d", implode(' ', $searchFullTextSearch), implode(' AND ', $searchWhereItems), $limit);

		$searchCacheName = sprintf('search_suggestion_%s', md5($sql));
		$searchModified = CacheFile::modified($searchCacheName);
		
		if(($searchModified === false) || (($searchModified + (86400 * 0)) < time())) {
			$searchCache = array();
			
			$data = new DataQuery($sql);
			if($data->TotalRows > 0) {
				while($data->Row) {
					$product = array();
					$product[] = preg_replace('/<\/p>$/i', '', preg_replace('/^<p[^>]*>/i', '', $data->Row['Product_Title']));
					$product[] = $data->Row['Product_ID'];
					$product[] = $data->Row['Product_Codes'];

					$searchCache[] = serialize($product);
					
					$data->Next();
				}

				CacheFile::save($searchCacheName, implode("\n", $searchCache));
			} else {
				if(!empty($searchString)) {
					$searchFailure = new SearchFailure();

					if($searchFailure->getByTerm($searchString, date('Y-m-d'))) {
						$searchFailure->increment();
					} else {
						$searchFailure->frequency = 1;
						$searchFailure->add();
					}
				}
			}
			$data->Disconnect();			
		}
		
		$searchCache = CacheFile::load($searchCacheName);

		if($searchCache !== false) {
			foreach($searchCache as $cache) {
				$cacheArray = unserialize($cache);

				$output[] = implode('{br}', $cacheArray);
			}
		}

		echo implode('{br}{br}', $output);
		echo '{br}{br}{br}';

		$replacements = array();

		foreach($searchItems as $searchItem) {
			$data = new DataQuery(sprintf("SELECT term, replacement FROM search_substitute WHERE '%s' LIKE CONCAT(term, '%%')", mysql_real_escape_string($searchItem)));
			while($data->Row) {
				$replacements[] = str_replace($searchItem, $data->Row['replacement'], $searchString);
				
				$data->Next();
			}
			$data->Disconnect();
		}

		echo implode('{br}{br}', $replacements);
		echo '{br}{br}{br}';

		$substitutes = array();

		$data = new DataQuery(sprintf("SELECT 'product' AS type, p.Product_ID AS id, p.Product_Title AS title FROM search_keyword AS sk INNER JOIN search_keyword_product AS skp ON skp.searchKeywordId=sk.id INNER JOIN product AS p ON p.Product_ID=skp.productId WHERE '%s' LIKE CONCAT(term, '%%') AND p.Is_Active='Y' and p.Is_Demo_Product='N' UNION SELECT 'category' AS type, c.Category_ID AS id, c.Category_Title AS title FROM search_keyword AS sk INNER JOIN search_keyword_category AS skc ON skc.searchKeywordId=sk.id INNER JOIN product_categories AS c ON c.Category_ID=skc.categoryId WHERE '%s' LIKE CONCAT(term, '%%') ORDER BY title ASC LIMIT 10", mysql_real_escape_string($searchString), mysql_real_escape_string($searchString)));
		while($data->Row) {
			$substitutes[] = sprintf('%s{br}%s{br}%s', $data->Row['type'], $data->Row['id'], $data->Row['title']);
			
			$data->Next();
		}
		$data->Disconnect();

		echo implode('{br}{br}', $substitutes);
	}
}

$GLOBALS['DBCONNECTION']->Close();