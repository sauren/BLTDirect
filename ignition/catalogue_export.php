<?php
ini_set('max_execution_time', '1800');

require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Catalogue.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CategoryCatalogueImage.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

error_fatal(0);

if($action == 'export') {
	$session->Secure(2);
	export();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function view() {
	$script = sprintf('<script language="javascript" type="text/javascript">
		window.onload = function() {
			window.self.location.href = \'%s?action=export&id=%d\';
		}
		</script>', $_SERVER['PHP_SELF'], $_REQUEST['id']);

	$page = new Page('Export Catalogue', 'Your catalogue is now being prepared.');
	$page->AddToHead($script);
	$page->Display('header');

	echo '<div style="padding: 25px 0 0 0;">';
	echo '<p style="text-align: center;"><strong>Please be patient as this may take a few minutes.</strong></p>';
	echo '<p style="text-align: center;">A file download request will appear once your catalogue has been prepared.<br />Once downloaded <a href="javascript:window.close();">click here</a> to close this window.</p>';
	echo '</div>';

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function export() {
	set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/packages/PHPExcel');

	require_once('PHPExcel.php');
	require_once('IOFactory.php');
	
	$catalogue = new Catalogue();

	if($catalogue->Get($_REQUEST['id'])) {
		$user = new User($GLOBALS['SESSION_USER_ID']);
		$headerColour = 'FF2A599D';
		$catalogueImage = new CategoryCatalogueImage();

		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()->setCreator('Ellwood Electrical');
		$objPHPExcel->getProperties()->setLastModifiedBy(trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)));
		$objPHPExcel->getProperties()->setTitle($catalogue->Title);
		$objPHPExcel->getProperties()->setSubject($catalogue->Title);
		$objPHPExcel->getProperties()->setDescription(strip_tags($catalogue->Description));

		$sections = array();

		$data = new DataQuery(sprintf("SELECT * FROM catalogue_section WHERE Catalogue_ID=%d ORDER BY Sequence_Number ASC", mysql_real_escape_string($catalogue->ID)));
		while($data->Row) {
			$sections[] = $data->Row;

			$data->Next();
		}
		$data->Disconnect();

		for($i=0; $i<count($sections); $i++) {
			if($i > 0) {
				$objPHPExcel->createSheet();
			}

			$objPHPExcel->setActiveSheetIndex($i);
			$objPHPExcel->getActiveSheet()->setTitle($sections[$i]['Title']);

			$categories = array();

			$data = new DataQuery(sprintf("SELECT * FROM catalogue_section_category WHERE Catalogue_Section_ID=%d ORDER BY Sequence_Number ASC", mysql_real_escape_string($sections[$i]['Catalogue_Section_ID'])));
			while($data->Row) {
				$categories[] = $data->Row;

				$data->Next();
			}
			$data->Disconnect();

			$widestColumn = 0;
			$row = 1;
			$imageRow = array();

			for($j=0; $j<count($categories); $j++) {
				$imageRow[$j] = $row;

				$objRichText = new PHPExcel_RichText($objPHPExcel->getActiveSheet()->getCell(getRow('A', $row)));

				$objPayable = $objRichText->createTextRun($categories[$j]['Title']);
				$objPayable->getFont()->setBold(true);
				$objPayable->getFont()->setColor(new PHPExcel_Style_Color($headerColour));

				$objPHPExcel->getActiveSheet()->setCellValue(getRow('A', $row), strip_tags($categories[$j]['Description']));

				$row++;

				$specifications = array();

				$data = new DataQuery(sprintf("SELECT psg.Group_ID, psg.Name FROM catalogue_section_category_specification AS cscs LEFT JOIN product_specification_group AS psg ON psg.Group_ID=cscs.Specification_Group_ID WHERE Catalogue_Section_Category_ID=%d ORDER BY cscs.Sequence_Number ASC", mysql_real_escape_string($categories[$j]['Catalogue_Section_Category_ID'])));
				while($data->Row) {
					$specifications[$data->Row['Group_ID']] = $data->Row['Name'];

					$data->Next();
				}
				$data->Disconnect();

				$column = ord('A');

				$objRichText = new PHPExcel_RichText($objPHPExcel->getActiveSheet()->getCell(getRow(chr($column++), $row, false)));

				$objPayable = $objRichText->createTextRun('Code');
				$objPayable->getFont()->setBold(true);

				$objRichText = new PHPExcel_RichText($objPHPExcel->getActiveSheet()->getCell(getRow(chr($column++), $row, false)));

				$objPayable = $objRichText->createTextRun('Quickfind');
				$objPayable->getFont()->setBold(true);

				foreach($specifications as $specificationId=>$specificationName) {
					$objRichText = new PHPExcel_RichText($objPHPExcel->getActiveSheet()->getCell(getRow(chr($column++), $row, false)));

					$objPayable = $objRichText->createTextRun($specificationName);
					$objPayable->getFont()->setBold(true);
				}

				if($catalogue->IsPriced == 'Y') {
					$objRichText = new PHPExcel_RichText($objPHPExcel->getActiveSheet()->getCell(getRow(chr($column++), $row, false)));

					$objPayable = $objRichText->createTextRun('Price');
					$objPayable->getFont()->setBold(true);
				}

				if($column > $widestColumn) {
					$widestColumn = $column;
				}

				$row++;

				$exclusions = array();

				$data = new DataQuery(sprintf("SELECT Catalogue_Section_Category_Exclusion_ID, Category_ID FROM catalogue_section_category_exclusion WHERE Catalogue_Section_Category_ID=%d", mysql_real_escape_string($categories[$j]['Catalogue_Section_Category_ID'])));
				while($data->Row) {
					$exclusions[$data->Row['Category_ID']] = $data->Row['Catalogue_Section_Category_Exclusion_ID'];

					$data->Next();
				}
				$data->Disconnect();

				$products = array();
				$result = $catalogue->GetSubCategoryProducts($categories[$j]['Category_ID'], $exclusions);

				foreach($result as $productId) {
					$products[$productId] = array();
				}

				foreach($products as $productId=>$value) {
					$products[$productId]['Object'] = new Product($productId);
					$products[$productId]['Specification'] = array();

					$data = new DataQuery(sprintf("SELECT psg.Group_ID, psv.Value, CONCAT_WS(' ', psv.Value, psg.Units) AS UnitValue FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON psv.Value_ID=ps.Value_ID INNER JOIN product_specification_group AS psg ON psg.Group_ID=psv.Group_ID INNER JOIN catalogue_section_category_specification AS cscs ON psg.Group_ID=cscs.Specification_Group_ID AND cscs.Catalogue_Section_Category_ID=%d WHERE ps.Product_ID=%d ORDER BY cscs.Sequence_Number ASC", mysql_real_escape_string($categories[$j]['Catalogue_Section_Category_ID']), mysql_real_escape_string($productId)));
					while($data->Row) {
						$products[$productId]['Specification'][$data->Row['Group_ID']] = $data->Row['UnitValue'];

						$data->Next();
					}
					$data->Disconnect();
				}

				$GLOBALS['SortMethod'] = $categories[$j]['Sort_Method'];
				$GLOBALS['SortSpecificationID'] = $categories[$j]['Sort_Specification_Group_ID'];

				uasort($products, 'compareProduct');

				foreach($products as $productId=>$value) {
					$column = ord('A');

					$objPHPExcel->getActiveSheet()->setCellValue(getRow(chr($column++), $row, false), $value['Object']->SKU);
					$objPHPExcel->getActiveSheet()->setCellValue(getRow(chr($column), $row, false), $value['Object']->ID);
					$objPHPExcel->getActiveSheet()->getStyle(getRow(chr($column++), $row, false))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

					foreach($specifications as $specificationId=>$specificationName) {
						$objPHPExcel->getActiveSheet()->setCellValue(getRow(chr($column++), $row, false), isset($value['Specification'][$specificationId]) ? $value['Specification'][$specificationId] : '');
					}

					if($catalogue->IsPriced == 'Y') {
						$objPHPExcel->getActiveSheet()->setCellValue(getRow(chr($column), $row, false), number_format($value['Object']->PriceCurrent, 2, '.', ''));
					}

					$objPHPExcel->getActiveSheet()->getStyle(getRow(chr($column++), $row, false))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

					$row++;
				}

				$row++;
			}

			for($j=0; $j<count($categories); $j++) {
				if($categories[$j]['Category_Catalogue_Image_ID'] > 0) {
					if($catalogueImage->Get($categories[$j]['Category_Catalogue_Image_ID'])) {
						if(file_exists($GLOBALS['CATEGORY_CATALOGUE_IMAGE_DIR_FS'] . (($catalogue->IsExportingThumbnails == 'Y') ? $catalogueImage->Thumb->FileName : $catalogueImage->Large->FileName))) {
							$objDrawing = new PHPExcel_Worksheet_Drawing();
							$objDrawing->setName($categories[$j]['Title']);
							$objDrawing->setDescription(strip_tags($categories[$j]['Description']));
							$objDrawing->setPath(sprintf('..%s%s', $GLOBALS['CATEGORY_CATALOGUE_IMAGE_DIR_WS'], ($catalogue->IsExportingThumbnails == 'Y') ? $catalogueImage->Thumb->FileName : $catalogueImage->Large->FileName));
							$objDrawing->setCoordinates(sprintf('%s%d', chr($widestColumn), $imageRow[$j] + 2));
							$objDrawing->getShadow()->setVisible(true);
							$objDrawing->getShadow()->setDirection(45);
							$objDrawing->setOffsetX(20);
							$objDrawing->setOffsetY(20);
							$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
						}
					}
				}
			}
		}

		$objPHPExcel->setActiveSheetIndex(0);

		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Type: application/force-download');
		header('Content-Type: application/octet-stream');
		header('Content-Type: application/download');
		header(sprintf("Content-Disposition: attachment;filename=%s", sprintf('catalogue_%d.xlsx', $catalogue->ID)));
		header('Content-Transfer-Encoding: binary');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
	}

	require_once('lib/common/app_footer.php');
}

function getRow($column, &$row, $increment = true) {
	$currentRow = ($increment) ? $row++ : $row;

	return sprintf('%s%d', $column, $currentRow);
}

function compareProduct($a, $b) {
	switch($GLOBALS['SortMethod']) {
		case 'Quickfind':
			return strnatcmp($a['Object']->ID, $b['Object']->ID);
		case 'Code':
			return strnatcmp($a['Object']->SKU, $b['Object']->SKU);
		case 'Specification':
			$aSpec = isset($a['Specification'][$GLOBALS['SortSpecificationID']]) ? $a['Specification'][$GLOBALS['SortSpecificationID']] : '';
			$bSpec = isset($b['Specification'][$GLOBALS['SortSpecificationID']]) ? $b['Specification'][$GLOBALS['SortSpecificationID']] : '';

			return strnatcmp($aSpec, $bSpec);
	}
}