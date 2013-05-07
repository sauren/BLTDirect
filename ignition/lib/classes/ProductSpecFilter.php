<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductSpecValue.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Category.php");

class ProductSpecFilter {
	var $MaxRows;
	var $MaxCols;
	var $Category;
	var $Categories;
	var $SpecGroup;
	var $Filter;
	var $FilterQueryString;
	var $FilterValue;
	var $SearchString;
	
	private $categoryCache;
	
	public function __construct() {
		$this->MaxRows = Setting::GetValue('spec_filter_max_rows');
		$this->MaxCols = Setting::GetValue('spec_filter_max_cols');
		$this->Category = array();
		$this->Categories = array();
		$this->SpecGroup = array();
		$this->Filter = array();
		$this->FilterValue = array();
		
		$this->categoryCache = array();
	}

	function Build() {
		$prelimGroups = array();
		$categoryExclusions = array();

		foreach(explode('&', $_SERVER['QUERY_STRING']) as $pair){
			$pair = explode('=', $pair);

			if(strtolower($pair[0]) == 'filter'){
				$filters = explode(',', $pair[1]);

				foreach($filters as $filter) {
					if(is_numeric($filter)) {
						$specValue = new ProductSpecValue();

						if($specValue->Get($filter)) {
							$specValue->Group->Get();
							
							$this->Filter[$specValue->Group->ID] = $specValue;
						}
					}
				}
			}
		}

		foreach(explode('&', $_SERVER['QUERY_STRING']) as $pair){
			$pair = explode('=', $pair);

			if(strtolower($pair[0]) == 'cat'){
				$filters = explode(',', $pair[1]);

				foreach($filters as $filter) {
					if(is_numeric($filter)) {
						if($filter > 1) {
							$category = new Category();
	
							if($category->Get($filter)) {
								$this->Category[] = $category;
							}
						}
					}
				}
			}
		}

		foreach($this->Filter as $groupId=>$specValue) {
			if($this->Filter[$groupId]->Group->ParentID > 0) {
				if(!isset($this->Filter[$this->Filter[$groupId]->Group->ParentID])) {
					unset($this->Filter[$groupId]);
				}
			}
		}

		foreach($this->Filter as $groupId=>$specValue) {
			$this->FilterValue[] = $specValue->ID;
		}

		$queryParts = array();

		if(count($this->Category) > 0) {
			$categories = array();

			foreach($this->Category as $category) {
				$categories[] = $category->ID;
			}

			$queryParts[] = sprintf('cat=%s', implode(',', $categories));

			if(count($categories) == 1) {
				if(isset($categories[0])) {
					$category = new Category($categories[0]);

					$queryParts[] = sprintf('nm=%s', urlencode($category->MetaTitle));
				}
			}
		}
		
		if(count($this->FilterValue) > 0) {
			$queryParts[] = sprintf('filter=%s', implode(',', $this->FilterValue));
		}

		if(!empty($this->SearchString)) {
			$queryParts[] = sprintf('search=%s', $this->SearchString);
		}

		$this->FilterQueryString = implode('&amp;', $queryParts);

		if(count($this->Category) > 0) {
			$categories = array();
			
			$this->getCategories();

			foreach($this->Category as $category) {
				$this->collectCategories($category->ID);
		
				$categories[] = $category->ID;
			}
			
			$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group_category WHERE Category_ID=%s", implode(' OR Category_ID=', $categories)));
			while($data->Row) {
				$categoryExclusions[$data->Row['Group_ID']] = $data->Row['Group_ID'];

				$data->Next();
			}
			$data->Disconnect();
		}

		$data = new DataQuery(sprintf("SELECT Group_ID, Parent_ID, Name, Data_Type FROM product_specification_group WHERE Is_Filterable='Y' AND Is_Hidden='N' ORDER BY Sequence_Number, Group_ID ASC"));
		while($data->Row) {
			if(!isset($this->Filter[$data->Row['Group_ID']])) {
				if(!isset($categoryExclusions[$data->Row['Group_ID']])) {
					if(($data->Row['Parent_ID'] == 0) || (($data->Row['Parent_ID'] > 0) && isset($this->Filter[$data->Row['Parent_ID']]))) {
						$prelimGroups[] = $data->Row;
					}
				}
			}

			$data->Next();
		}
		$data->Disconnect();
		
		if(count($prelimGroups) > 0) {
			$groups = array();
			
			for($i=0; $i<count($prelimGroups); $i++) {
				$groups[] = $prelimGroups[$i]['Group_ID'];
			}

			$sqlSelect = 'SELECT psv.Group_ID, psv.Value_ID, psv.Value, CONCAT_WS(\' \', psv.Value, psg.Units) AS UnitValue, COUNT(DISTINCT p.Product_ID) AS Products ';
			$sqlFrom = 'FROM product_specification_group AS psg INNER JOIN product_specification_value AS psv ON psv.Group_ID=psg.Group_ID INNER JOIN product_specification AS ps ON ps.Value_ID=psv.Value_ID INNER JOIN product AS p ON p.Product_ID=ps.Product_ID LEFT JOIN product_barcode AS pb ON pb.ProductID=p.Product_ID ';
			$sqlWhere = sprintf('WHERE (psv.Group_ID=%1$s) AND p.Is_Active=\'Y\' AND p.Is_Demo_Product=\'N\' AND p.Discontinued=\'N\' AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start=\'0000-00-00 00:00:00\' AND p.Sales_End=\'0000-00-00 00:00:00\') OR (p.Sales_Start=\'0000-00-00 00:00:00\' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End=\'0000-00-00 00:00:00\')) ', implode(' OR psv.Group_ID=', $groups));
			$sqlGroup = 'GROUP BY psv.Value_ID ';
			$sqlOrder = 'ORDER BY psv.Group_ID ASC, psv.Value ASC ';
			
			if(count($this->Filter) > 0) {
				foreach($this->Filter as $groupId=>$specValue) {
					$sqlFrom .= sprintf('INNER JOIN product_specification AS ps%1$d ON ps%1$d.Product_ID=p.Product_ID AND ps%1$d.Value_ID=%2$d ', mysql_real_escape_string($groupId), mysql_real_escape_string($specValue->ID));
				}
			}
	
			if(count($this->Category) > 0) {
				$sqlFrom .= 'INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.product_ID ';
				$sqlWhere .= sprintf('AND (pic.Category_ID=%1$s) ', implode(' OR pic.Category_ID=', $this->Categories));
			}
	
			if(!empty($this->SearchString)) {
				$searchString = '';
				$searchFullTextSearch = array();
				$searchWhereItems = array();

				$searchString = $this->SearchString;
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

				$sqlSelect .= sprintf(', MATCH(Product_Title, Product_Codes, Product_Description, Meta_Title, Meta_Description, Meta_Keywords, Cache_Specs) AGAINST(\'%1$s\') AS Score ', implode(' ', $searchFullTextSearch));
				$sqlWhere .= sprintf('AND (%1$s) ', implode(' AND ', $searchWhereItems));
			}
			
			$filterCacheName = sprintf('filter_%s', md5(sprintf("%s%s%s%s%s", $sqlSelect, $sqlFrom, $sqlWhere, $sqlGroup, $sqlOrder)));
			$filterModified = CacheFile::modified($filterCacheName);

			if(($filterModified === false) || (($filterModified + (86400 * 7)) < time())) {
				$filterCache = array();
				
				$data = new DataQuery(sprintf("%s%s%s%s%s", $sqlSelect, $sqlFrom, $sqlWhere, $sqlGroup, $sqlOrder));
				while($data->Row) {
					$filterCache[] = serialize($data->Row);
		
					$data->Next();
				}
				$data->Disconnect();
				
				CacheFile::save($filterCacheName, implode("\n", $filterCache));
			}
			
			$filterCache = CacheFile::load($filterCacheName);
			
			foreach($filterCache as $cache) {
				$cacheArray = unserialize($cache);
				
				for($i=0; $i<count($prelimGroups); $i++) {
					if($prelimGroups[$i]['Group_ID'] == $cacheArray['Group_ID']) {
						$prelimGroups[$i]['Values'][] = $cacheArray;
						break;
					}
				}
			}
			
			
			for($i=0; $i<count($prelimGroups); $i++) {
				if(isset($prelimGroups[$i]['Values']) && count($prelimGroups[$i]['Values']) > 0) {
					if($prelimGroups[$i]['Data_Type'] == 'numeric') {
						$sortArray = array();
						$cacheArray = $prelimGroups[$i]['Values'];
						
						for($j=0; $j<count($prelimGroups[$i]['Values']); $j++) {
							$sortArray[$prelimGroups[$i]['Values'][$j]['Value_ID']] = $prelimGroups[$i]['Values'][$j]['Value'];
						}
						
						asort($sortArray, SORT_NUMERIC);
						
						$prelimGroups[$i]['Values'] = array();
						
						foreach($sortArray as $valueId=>$value) {
							for($j=0; $j<count($cacheArray); $j++) {
								if($cacheArray[$j]['Value_ID'] == $valueId) {
									$prelimGroups[$i]['Values'][] = $cacheArray[$j];
									break;
								}
							}		
						}
					}
					
					$this->SpecGroup[] = $prelimGroups[$i];
				}
			}
		}
	}

	function GetFilterQueryString($filterExclusions = array(), $categoryExclusions = array()) {
		if(!is_array($filterExclusions)) {
			if(is_numeric($filterExclusions)) {
				$filterExclusions = array($filterExclusions);
			} else {
				$filterExclusions = array();
			}
		}

		if(!is_array($categoryExclusions)) {
			if(is_numeric($categoryExclusions)) {
				$categoryExclusions = array($categoryExclusions);
			} else {
				$categoryExclusions = array();
			}
		}

		if((count($filterExclusions) > 0) || (count($categoryExclusions) > 0)) {
			$filterValue = array();

			for($i=0; $i<count($this->FilterValue); $i++) {
				$found = false;

				for($j=0; $j<count($filterExclusions); $j++) {
					if($this->FilterValue[$i] == $filterExclusions[$j]) {
						$found = true;
						break;
					}
				}

				if(!$found) {
					$filterValue[] = $this->FilterValue[$i];
				}
			}

			$categoryValue = array();

			for($i=0; $i<count($this->Category); $i++) {
				$found = false;

				for($j=0; $j<count($categoryExclusions); $j++) {
					if($this->Category[$i]->ID == $categoryExclusions[$j]) {
						$found = true;
						break;
					}
				}

				if(!$found) {
					$categoryValue[] = $this->Category[$i]->ID;
				}
			}

			$queryParts = array();

			if(count($categoryValue) > 0) {
				$queryParts[] = sprintf('cat=%s', implode(',', $categoryValue));

				if(count($categoryValue) == 1) {
					if(isset($categoryValue[0])) {
						$category = new Category($categoryValue[0]);

						$queryParts[] = sprintf('nm=%s', urlencode($category->MetaTitle));
					}
				}
			}

			if(count($filterValue) > 0) {
				$queryParts[] = sprintf('filter=%s', implode(',', $filterValue));
			}

			if(!empty($this->SearchString)) {
				$queryParts[] = sprintf('search=%s', urlencode($this->SearchString));
			}

			return implode('&amp;', $queryParts);
		} else {
			return $this->FilterQueryString;
		}
	}

	private function collectCategories($id = 0) {
		if($id > 0) {
			$this->Categories[] = $id;
			
			if(isset($this->categoryCache[$id])) {
				for($i=0; $i<count($this->categoryCache[$id]); $i++) {
					$this->collectCategories($this->categoryCache[$id][$i]);
				}
			}
		}
	}
	
	private function getCategories() {
		$data = new DataQuery(sprintf("SELECT Category_ID, Category_Parent_ID FROM product_categories"));
		while($data->Row) {
			$key = $data->Row['Category_Parent_ID'];
			
			if(!isset($this->categoryCache[$key])) {
				$this->categoryCache[$key] = array();
			}
			
			$this->categoryCache[$key][] = $data->Row['Category_ID'];

			$data->Next();
		}
		$data->Disconnect();
	}
}