<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CatalogueSection.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CategoryCatalogueImage.php');

class Catalogue {
	var $ID;
	var $Title;
	var $Description;
	var $IsPriced;
	var $IsExportingThumbnails;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $SortMethod;
	var $SortSpecificationID;

	function Catalogue($id = null) {
		$this->IsPriced = 'Y';
		$this->IsExportingThumbnails = 'N';
		$this->SortMethod = 'Code';

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM catalogue WHERE Catalogue_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Title = $data->Row['Title'];
			$this->Description = $data->Row['Description'];
			$this->IsPriced = $data->Row['Is_Priced'];
			$this->IsExportingThumbnails = $data->Row['Is_Exporting_Thumbnails'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO catalogue (Title, Description, Is_Priced, Is_Exporting_Thumbnails, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Title), mysql_real_escape_string($this->Description), mysql_real_escape_string($this->IsPriced), mysql_real_escape_string($this->IsExportingThumbnails), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update() {

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("UPDATE catalogue SET Title='%s', Description='%s', Is_Priced='%s', Is_Exporting_Thumbnails='%s', Modified_On=NOW(), Modified_By=%d WHERE Catalogue_ID=%d", mysql_real_escape_string($this->Title), mysql_real_escape_string($this->Description), mysql_real_escape_string($this->IsPriced), mysql_real_escape_string($this->IsExportingThumbnails), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM catalogue WHERE Catalogue_ID=%d", mysql_real_escape_string($this->ID)));

		$section = new CatalogueSection();

		$data = new DataQuery(sprintf("SELECT Catalogue_Section_ID FROM catalogue_section WHERE Catalogue_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$section->Delete($data->Row['Catalogue_Section_ID']);

			$data->Next();
		}
		$data->Disconnect();
	}

	function PrepareTemplate($preview = false) {
		$template = '';

		$findReplace = new FindReplace();
		$findReplace->Add('/\[CATALOGUE_TITLE\]/', $this->Title);
		$findReplace->Add('/\[CATALOGUE_DESCRIPTION\]/', $this->Description);
		$findReplace->Add('/\[CATALOGUE_CONTENT\]/', $this->PrepareSection($preview));

		$standardTemplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/catalogue/template_standard.tpl");

		for($i=0; $i < count($standardTemplate); $i++){
			$template .= $findReplace->Execute($standardTemplate[$i]);
		}

		return $template;
	}

	function PrepareSection($preview = false) {
		$template = '';

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM catalogue_section WHERE Catalogue_ID=%d ORDER BY Sequence_Number ASC", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$template .= '<div class="section">';
			$template .= '<div class="section-info">';
			$template .= sprintf('<h2>%s</h2>', $data->Row['Title']);
			$template .= sprintf('<p>%s</p>', $data->Row['Description']);
			$template .= '</div>';
			$template .= $this->PrepareCategory($preview, $data->Row['Catalogue_Section_ID']);
			$template .= '<div class="clear"></div>';
			$template .= '</div>';

			$data->Next();
		}
		$data->Disconnect();

		return $template;
	}

	function PrepareCategory($preview = false, $sectionId = 0) {
		$template = '';

		$sections = array();
		$columns = 2;
		$current = 0;
		$catalogueImage = new CategoryCatalogueImage();
		$host = ($_SERVER['SERVER_PORT'] != $GLOBALS['SSL_PORT']) ? $GLOBALS['HTTP_SERVER'] : $GLOBALS['HTTPS_SERVER'];

		for($i=0; $i<$columns; $i++) {
			$sections[$i] = array();
		}

		$data = new DataQuery(sprintf("SELECT * FROM catalogue_section_category WHERE Catalogue_Section_ID=%d ORDER BY Sequence_Number ASC", mysql_real_escape_string($sectionId)));
		while($data->Row) {
			$sections[$current][] = $data->Row;

			$current++;

			if($current >= $columns) {
				$current = 0;
			}

			$data->Next();
		}
		$data->Disconnect();

		$template .= '<table class="columns"><tr>';

		for($i=0; $i<$columns; $i++) {
			$template .= sprintf('<td width="%s%%" class="column">', (100 / $columns));

			for($j=0; $j<count($sections[$i]); $j++) {
				$background = '';
				$specifications = array();

				$data = new DataQuery(sprintf("SELECT psg.Group_ID, psg.Name FROM catalogue_section_category_specification AS cscs LEFT JOIN product_specification_group AS psg ON psg.Group_ID=cscs.Specification_Group_ID AND psg.Is_Hidden='N' WHERE Catalogue_Section_Category_ID=%d ORDER BY cscs.Sequence_Number ASC", mysql_real_escape_string($sections[$i][$j]['Catalogue_Section_Category_ID'])));
				while($data->Row) {
					$specifications[$data->Row['Group_ID']] = $data->Row['Name'];

					$data->Next();
				}
				$data->Disconnect();

				if($sections[$i][$j]['Category_Catalogue_Image_ID'] > 0) {
					if($catalogueImage->Get($sections[$i][$j]['Category_Catalogue_Image_ID'])) {
						if(file_exists($GLOBALS['CATEGORY_CATALOGUE_THUMB_DIR_FS'] . $catalogueImage->Thumb->FileName)) {
							$background .= sprintf('style="background-image: url(%s%s%s); padding: 120px 0 0 0;"', substr($host, 0, -1), $GLOBALS['CATEGORY_CATALOGUE_THUMB_DIR_WS'], $catalogueImage->Thumb->FileName);
						}
					}
				}

				$template .= sprintf('<div class="category" %s>', $background);
				$template .= '<div class="category-info">';
				$template .= sprintf('<h3>%s</h3>', $sections[$i][$j]['Title']);
				$template .= sprintf('<p>%s</p>', $sections[$i][$j]['Description']);

				$template .= '<table>';
				$template .= '<tbody>';
				$template .= '<tr>';
				$template .= '<th>Code</th>';
				$template .= sprintf('<th><img src="%simages/blue_bulb.gif" alt="QuickFind" width="16" height="16" /></th>', $host);

				foreach($specifications as $specificationId=>$specificationName) {
					$template .= sprintf('<th>%s</th>', $specificationName);
				}

				if($this->IsPriced == 'Y') {
					$template .= '<th style="text-align: right;">Price</th>';
				}

				$template .= '</tr>';

				$exclusions = array();

				$data = new DataQuery(sprintf("SELECT Catalogue_Section_Category_Exclusion_ID, Category_ID FROM catalogue_section_category_exclusion WHERE Catalogue_Section_Category_ID=%d", mysql_real_escape_string($sections[$i][$j]['Catalogue_Section_Category_ID'])));
				while($data->Row) {
					$exclusions[$data->Row['Category_ID']] = $data->Row['Catalogue_Section_Category_Exclusion_ID'];

					$data->Next();
				}
				$data->Disconnect();

				$products = array();
				$result = $this->GetSubCategoryProducts($sections[$i][$j]['Category_ID'], $exclusions);

				foreach($result as $productId) {
					$products[$productId] = array();
				}

				foreach($products as $productId=>$value) {
					$products[$productId]['Object'] = new Product($productId);
					$products[$productId]['Specification'] = array();

					$data = new DataQuery(sprintf("SELECT psg.Group_ID, psv.Value, CONCAT_WS(' ', psv.Value, psg.Units) AS UnitValue FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON psv.Value_ID=ps.Value_ID INNER JOIN product_specification_group AS psg ON psg.Group_ID=psv.Group_ID AND psg.Is_Hidden='N' INNER JOIN catalogue_section_category_specification AS cscs ON psg.Group_ID=cscs.Specification_Group_ID AND cscs.Catalogue_Section_Category_ID=%d WHERE ps.Product_ID=%d ORDER BY cscs.Sequence_Number ASC", $sections[$i][$j]['Catalogue_Section_Category_ID'], mysql_real_escape_string($productId)));
					while($data->Row) {
						$products[$productId]['Specification'][$data->Row['Group_ID']] = $data->Row['UnitValue'];

						$data->Next();
					}
					$data->Disconnect();
				}

				$this->SortMethod = $sections[$i][$j]['Sort_Method'];
				$this->SortSpecificationID = $sections[$i][$j]['Sort_Specification_Group_ID'];

				uasort($products, array($this, 'compareProduct'));

				foreach($products as $productId=>$value) {
					$template .= '<tr>';
					$template .= sprintf('<td>%s</td>', $value['Object']->SKU);
					$template .= sprintf('<td><a href="%s" target="_blank" title="%s">%s</a></td>', ($preview) ? sprintf('%signition/product_profile.php?pid=%d', $host, $value['Object']->ID) : sprintf('%sproduct.php?pid=%d', $host, $value['Object']->ID), $value['Object']->Name, $value['Object']->ID);

					foreach($specifications as $specificationId=>$specificationName) {
						$template .= sprintf('<td>%s</td>', isset($value['Specification'][$specificationId]) ? $value['Specification'][$specificationId] : '');
					}

					if($this->IsPriced == 'Y') {
						$template .= sprintf('<td align="right">&pound;%s</td>', number_format($value['Object']->PriceCurrent, 2, '.', ''));
					}

					$template .= '</tr>';
				}

				$template .= '</tbody>';
				$template .= '</table>';

				$template .= '</div>';
				$template .= '</div>';
			}

			$template .= '</td>';
		}

		$template .= '</tr></table>';

		return $template;
	}

	function GetSubCategoryProducts($categoryId, $exclusions = array(), $products = array()) {
		if(!is_numeric($categoryId)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT p.Product_ID FROM product_in_categories AS pic INNER JOIN product AS p ON p.Product_ID=pic.Product_ID WHERE pic.Category_ID=%d", mysql_real_escape_string($categoryId)));
		while($data->Row) {
			$products[] = $data->Row['Product_ID'];

			$data->Next();
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", $categoryId));
		while($data->Row) {
			if(!isset($exclusions[$data->Row['Category_ID']])) {
				$products = array_merge($this->GetSubCategoryProducts($data->Row['Category_ID'], $exclusions), $products);
			}

			$data->Next();
		}
		$data->Disconnect();

		return $products;
	}

	function compareProduct($a, $b) {
		switch($this->SortMethod) {
			case 'Quickfind':
				return strnatcmp($a['Object']->ID, $b['Object']->ID);
			case 'Code':
				return strnatcmp($a['Object']->SKU, $b['Object']->SKU);
			case 'Specification':
				$aSpec = isset($a['Specification'][$this->SortSpecificationID]) ? $a['Specification'][$this->SortSpecificationID] : '';
				$bSpec = isset($b['Specification'][$this->SortSpecificationID]) ? $b['Specification'][$this->SortSpecificationID] : '';

				return strnatcmp($aSpec, $bSpec);
		}
	}
}
?>