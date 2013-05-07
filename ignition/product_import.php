<?php
require_once('lib/common/app_header.php');

$session->Secure(2);

/*
* Upload a CSV file to the temp directory

* Set the file delimeter and enclosure characters

* Cross Check the field names (with example) to Product Fields

* Mark each product as imported through csv

* Delete the temporary csv file
*/
$productFields = array('ignore' => 'Ignore',
'title' => 'Title',
'desc' =>  'Long Description',
'blurb' => 'Short Description',
'cat' => 'Product Category',
'man' => 'Manufacturer',
'tech' => 'Technical Specification',
'model' => 'Model',
'var' => 'Variant',
'id' => 'Product ID',
'sku' => 'SKU',
'price' => 'Our Price',
'rrp' => 'RRP Price',
'metat' => 'Meta Title',
'metak' => 'Meta Keywords',
'metad' => 'Meta Description',
'weight' => 'Weight (Kg)',
'width' => 'Shelf Width (m)',
'height' => 'Shelf Height (m)',
'depth' => 'Shelf Depth (m)',
'stocked' => 'Stocked',
'monitor' => 'Monitor Stock',
'alert' => 'Stock Alert On (Qty)',
'suspend' => 'Stock Suspend On (Qty)',
'image' => 'Large Image',
'thumb' => 'Thumbnail Image',
'imgtitle' => 'Image Title',
'imgdesc' => 'Image Description',
'ship' => 'Shipping Class',
'tax' =>  'Tax Class',
'imported' => 'Stock is Imported (Y/N)',
'guarantee' => 'Guarantee (Days)',
'etamin' => 'Estimated Despatch Days (Min)',
'etamax' => 'Estimated Despatch Days (Max)',
'ordermin' => 'Minimum Order Quantity',
'ordermax' => 'Maximum Order Quantity',
'discountlimit' => 'Discount Limit',
'startdatetime' => 'Start Sales On (YYYY-MM-DD)',
'enddatetime' => 'End Sales On (YYYY-MM-DD)',
'startuk' => 'Start Sales On (DD/MM/YYYY)',
'enduk' => 'End Sales On (DD/MM/YYYY)',
'startsus' => 'Start Sales On (MM/DD/YYYY)',
'endus' => 'End Sales On (MM/DD/YYYY)',
'suppuse' => 'Supplier Username',
'prefsup' => 'Preferred Supplier (Y/N)',
'cost' => 'Cost');
$serve = (isset($_REQUEST['serve']))?strtolower($_REQUEST['serve']):NULL;

if(!is_null($serve)){
	if($serve == 'imported' && $action == 'clear'){
		clearImported();
		exit();
	} elseif ($serve == 'imported'){
		done();
		exit;
	}
} else {
	if($action == "step1"){
		step1();
		exit;
	} elseif($action == "step2"){
		step2();
		exit;
	} elseif($action == "abort"){
		abort();
		exit;
	} else {
		start();
		exit;
	}
}

function clearImported(){
	$sql = "update product set Is_Data_Imported='N' where Is_Data_Imported='Y'";
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	$data = new DataQuery($sql);
	redirect("Location: product_import.php?serve=imported");
}

function done(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	$pageTile = 'CSV Imported Products';
	if(isset($_REQUEST['num'])){
		$pageTile = $_REQUEST['num'] . ' Products were Imported.';
	}
	$page = new Page($pageTile,'The following products are tagged as imported view CSV and are probably in need of your attention. The import tag will remain until the product has been updated.'.$_SESSION['errimp']);
	$page->Display("header");
	$sqlString = "select Product_ID, Product_Title, SKU, Created_On from product where Is_Data_Imported='Y'";
	$table = new DataTable('prodImport');
	$table->SetSQL($sqlString);
	$table->AddField('Auto ID#', 'Product_ID', 'right');
	$table->AddField('SKU', 'SKU', 'left');
	$table->AddField('Product Title', 'Product_Title', 'left');
	$table->AddField('Created On', 'Created_On', 'left');
	$table->AddLink("product_profile.php?pid=%s",
	"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Imported Product\" border=\"0\">",
	"Product_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Created_On");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo '<br /><input type="button" class="btn" name="clear import list" id="clear" value="clear import list" onclick="confirmRequest(\'product_import.php?serve=imported&action=clear\',\'Are you sure you would like to clear your import history. Note: this will not affect the products you have imported other than their imported status.\');" />';
	$page->Display("footer");
}

function checkField($field){
	global $productFields;
	/*for($a=0; $a<count($productFields); $a++){
	if($productFields[$a] == $field) return $a;

	}*/
	foreach($productFields as $key=>$value){
		if($productFields[$key] == $field) return $key;
	}
	return 0;
}

function step2(){
	require_once("./lib/classes/IFile.php");
	require_once("./lib/classes/Form.php");
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CsvImport.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpec.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecGroup.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValue.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');

	global $productFields;
	$filename = $_REQUEST['file'];
	$delim = stripslashes($_REQUEST['delimit']);
	$encl = stripslashes($_REQUEST['encl']);

	$file = new IFile($filename, "./temp");
	$filename = $file->Directory . "/" . $file->FileName;
	$csv = new CsvImport($filename, $delim, $encl);
	$csv->HasFieldNames = true;

	if($csv->Open()){
		$form = new Form("product_import.php");
		$form->AddField('action', '', 'hidden', 'step2', 'alpha_numeric', 5, 5);
		$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('file', 'CSV File', 'hidden', $file->FileName, 'link_relative', 3, 255);
		$form->AddField('delimit', 'Values Separated by', 'hidden', $delim, 'paragraph', 1, 1);
		$form->AddField('encl', 'Values Enclosed in', 'hidden', $encl, 'paragraph', 1, 7);

		// Now add appropriate fields
		for($i=0; $i < count($csv->FieldNames); $i++){
			$form->AddField('field'.$i, $csv->FieldNames[$i], 'select', checkField($csv->FieldNames[$i]), 'alpha_numeric', 0, 255);
			/*for($j=0; $j<count($productFields); $j++){
			$form->AddOption('field'.$i, $j, $productFields[$j]);
			}*/
			foreach($productFields as $key=>$value){
				$form->AddOption('field'.$i, $key, $value);
			}
		}

		// Check if the form has been submitted
		// This is done with the confirm request
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			// Check that the form validates.
			if($form->Validate()){

				$tSpecs = array();
				$rrpPrice;
				$price;
				$base = new Product;
				$category;
				$priceFix = array(",", "£", "$");
				$productsImported = 0;
				$startDateFormat;
				$endDateFormat;
				$supProd = array();
				$supProd['Username'] = null;
				$supProd['Preferred'] = null;
				$supProd['Cost'] = null;
                $fieldColumns = array();
				// Go through each csv column
				for($i=0; $i < count($csv->FieldNames); $i++){
					$fieldValue = $_REQUEST['field'.$i];
                    $fieldColumns[] = $fieldValue;
					switch($fieldValue){
						case 'id':
							$base->ID = $i;
							break;
						case 'title':
							$base->Name = $i;
							break;
						case 'desc':
							$base->Description = $i;
							break;
						case 'blurb':
							$base->Blurb = $i;
							break;
						case 'cat':
							$category = $i;
							break;
						case 'man':
							$base->Manufacturer->Name = $i;
							break;
						case 'tech':
							$tSpecs[$csv->FieldNames[$i]] = $i;
							break;
						case 'model':
							$base->Model = $i;
							break;
						case 'var':
							$base->Variant = $i;
							break;
						case 'sku':
							$base->SKU = $i;
							break;
						case 'price':
							$base->PriceOurs = $i;
							break;
						case 'rrp':
							$base->PriceRRP = $i;
							break;
						case 'metat':
							$base->MetaTitle = $i;
							break;
						case 'metak':
							$base->MetaKeywords = $i;
							break;
						case 'metad':
							$base->MetaDescription = $i;
							break;
						case 'weight':
							$base->Weight = $i;
							break;
						case 'width':
							$base->Width = $i;
							break;
						case 'height':
							$base->Height = $i;
							break;
						case 'depth':
							$base->Depth = $i;
							break;
						case 'stocked':
							$base->Stocked = $i;
							break;
						case 'monitor':
							$base->StockMonitor = $i;
							break;
						case 'alert':
							$base->StockAlert = $i;
							break;
						case 'suspend':
							$base->StockSuspend = $i;
							break;
						case 'image':
							$base->DefaultImage->Large->ID = $i;
							break;
						case 'thumb':
							$base->DefaultImage->Thumb->ID = $i;
							break;
						case 'imgtitle':
							$base->DefaultImage->Name = $i;
							break;
						case 'imgdesc':
							$base->DefaultImage->Description = $i;
							break;
						case 'ship':
							$base->ShippingClass->ID = $i;
							break;
						case 'tax':
							$base->TaxClass->ID = $i;
							break;
						case 'imported':
							$base->StockImported = $i;
							break;
						case 'guarantee':
							$base->Guarantee = $i;
							break;
						case 'etamin':
							$base->DespatchDaysMin = $i;
							break;
						case 'etamax':
							$base->DespatchDaysMax = $i;
							break;
						case 'ordermin':
							$base->OrderMin = $i;
							break;
						case 'ordermax':
							$base->OrderMax = $i;
							break;
						case 'discountlimit':
							$base->DiscountLimit = $i;
							break;
						case 'startdatetime':
							$base->SalesStart = $i;
							$startDateFormat = 'mysql';
							break;
						case 'enddatetime':
							$base->SalesEnd = $i;
							$endDateFormat = 'mysql';
							break;
						case 'startuk':
							$base->SalesStart = $i;
							$startDateFormat = 'uk';
							break;
						case 'enduk':
							$base->SalesEnd = $i;
							$endDateFormat = 'uk';
							break;
						case 'startus':
							$base->SalesStart = $i;
							$startDateFormat = 'us';
							break;
						case 'endus':
							$base->SalesEnd = $i;
							$endDateFormat = 'us';
							break;
						case 'suppuse':
							$supProd['Username'] = $i;
							break;
						case 'prefsup':
							$supProd['Preferred'] = $i;
							break;
						case 'cost':
							$supProd['Cost'] = $i;
							break;
					}
				}

				// Go through each csv line and create a product
				while($csv->Data){
					$prod = new Product;

					/* Check Manufacturer */
					if(isset($base->Manufacturer->Name) && !empty($csv->Data[$base->Manufacturer->Name])){
						$prod->Manufacturer->Name = $csv->Data[$base->Manufacturer->Name];
						if(!$prod->Manufacturer->Exists()) $prod->Manufacturer->Add();
					}

					/*
					Add Shipping Class
					We need to add the shipping class first if it doesn't exists so that we do not need
					to update the product later. It is more efficient this way.
					*/
					if(!empty($base->ShippingClass->ID) && !empty($csv->Data[$base->ShippingClass->ID])){
						$prod->ShippingClass->Name = $csv->Data[$base->ShippingClass->ID];
						if(!$prod->ShippingClass->Exists()){
							$prod->ShippingClass->IsDefault = 'N';
							$prod->ShippingClass->Add();
						}
						//print_r($prod->ShippingClass);
					}

					/*
					Add Tax Class
					We need to add the tax class first if it doesn't exist so that we do not need
					to update the product later. It is more efficient this way.
					*/
					if(!empty($base->TaxClass->ID) && !empty($csv->Data[$base->TaxClass->ID])){
						$prod->TaxClass->Name = $csv->Data[$base->TaxClass->ID];
						if(!$prod->TaxClass->Exists()) $prod->TaxClass->Add();
					}

					// Check ID
					if(!is_null($base->ID) && is_numeric($csv->Data[$base->ID]) && !empty($csv->Data[$base->ID])){
						$prod->ID = $csv->Data[$base->ID];
						$importAction = "update";
						$prod->Get();

					} else {
						$importAction = "add";
					}
					// This Data Is Being Imported
					$prod->DataImported = 'Y';

					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->Manufacturer->Name]))){
						$obID = new DataQuery(sprintf("SELECT * FROM manufacturer WHERE Manufacturer_Name = '%s'", mysql_real_escape_string($csv->Data[$base->Manufacturer->Name])));
						$prod->Manufacturer->ID = $obID->Row['Manufacturer_ID'];
						$obID->Disconnect();
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->ShippingClass->ID]))){
						$obID = new DataQuery(sprintf("SELECT * FROM shipping_class WHERE Shipping_Class_Title = '%s'", mysql_real_escape_string($csv->Data[$base->ShippingClass->ID])));
						$prod->ShippingClass->ID = $obID->Row['Shipping_Class_ID'];
						$obID->Disconnect();
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->TaxClass->ID]))){
						$obID = new DataQuery(sprintf("SELECT * FROM tax_class WHERE Tax_Class_Title = '%s'", mysql_real_escape_string($csv->Data[$base->TaxClass->ID])));
						$prod->TaxClass->ID = $obID->Row['Tax_Class_ID'];
						$obID->Disconnect();
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->Name]))){
						$prod->Name = $csv->Data[$base->Name];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->Description]))){
						$prod->Description = $csv->Data[$base->Description];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->Blurb]))){
						$prod->Blurb = $csv->Data[$base->Blurb];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->IsActive]))){
						$prod->IsActive = ($importAction == 'add')?'Y':$csv->Data[$base->IsActive];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->Model]))){
						$prod->Model = $csv->Data[$base->Model];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->Variant]))){
						$prod->Variant = $csv->Data[$base->Variant];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->SKU]))){
						$prod->SKU = $csv->Data[$base->SKU];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->MetaTitle]))){
						$prod->MetaTitle = $csv->Data[$base->MetaTitle];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->MetaKeywords]))){
						$prod->MetaKeywords = $csv->Data[$base->MetaKeywords];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->MetaDescription]))){
						$prod->MetaDescription = $csv->Data[$base->MetaDescription];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->Weight]))){
						if($base->Weight > 0) $prod->Weight = $csv->Data[$base->Weight];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->Width]))){
						if($base->Width > 0) $prod->Width = $csv->Data[$base->Width];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->Height]))){
						if($base->Height > 0) $prod->Height = $csv->Data[$base->Height];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->Depth]))){
						if($base->Depth > 0) $prod->Depth = $csv->Data[$base->Depth];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->Stocked]))){
						$prod->Stocked = $csv->Data[$base->Stocked];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->StockMonitor]))){
						$prod->StockMonitor = $csv->Data[$base->StockMonitor];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->StockAlert]))){
						$prod->StockAlert = $csv->Data[$base->StockAlert];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->StockSuspend]))){
						$prod->StockSuspend = $csv->Data[$base->StockSuspend];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->PriceOurs]))){
						$prod->PriceOurs = $csv->Data[$base->PriceOurs];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->PriceRRP]))){
						$prod->PriceRRP = $csv->Data[$base->PriceRRP];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->StockImported]))){
						$prod->StockImported = trim($csv->Data[$base->StockImported]);
					} else {
						$prod->StockImported = 'N';
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->Guarantee]))){
						$prod->Guarantee = $csv->Data[$base->Guarantee];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->DespatchDaysMin]))){
						$prod->DespatchDaysMin = $csv->Data[$base->DespatchDaysMin];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->DespatchDaysMax]))){
						$prod->DespatchDaysMax = $csv->Data[$base->DespatchDaysMax];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->OrderMin]))){
						$prod->OrderMin = $csv->Data[$base->OrderMin];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->OrderMax]))){
						$prod->OrderMax = $csv->Data[$base->OrderMax];
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->OrderMax]))){
						$prod->OrderMax = $csv->Data[$base->OrderMax];
					}
					if($importAction == 'add' || $importAction == 'update'){
						if($csv->Data[$base->DiscountLimit] == '' || ($csv->Data[$base->DiscountLimit] >= 0 && $csv->Data[$base->DiscountLimit] <= 100)){
							$prod->DiscountLimit = $csv->Data[$base->DiscountLimit];
						}
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->SalesStart]))){
						if(!empty($base->SalesStart) && !empty($csv->Data[$base->SalesStart])){
							$tempDate = NULL;
							if($startDateFormat == 'mysql'){
								$tempDate = $csv->Data[$base->SalesStart];
							} else if ($startDateFormat == 'uk'){
								$tempArr = explode('/', $csv->Data[$base->SalesStart]);
								if(count($tempArr == 3)) $tempDate = sprintf('%s-%s-%s', $tempArr[2], $tempArr[1], $tempArr[0]);
								unset($tempArr);
							} else if ($startDateFormat == 'us'){
								$tempArr = explode('/', $csv->Data[$base->SalesStart]);
								if(count($tempArr == 3)) $tempDate = sprintf('%s-%s-%s', $tempArr[2], $tempArr[0], $tempArr[1]);
								unset($tempArr);
							}
							if(!is_null($tempDate) && isDate($tempDate)) $prod->SalesStart = $tempDate;
						} else {
							$prod->SalesStart = getDatetime();
						}
					}
					if($importAction == 'add' || ($importAction == 'update' && !empty($csv->Data[$base->SalesEnd]))){
						if(!empty($base->SalesEnd) && !empty($csv->Data[$base->SalesEnd])){
							$tempDate = NULL;
							if($endDateFormat == 'mysql'){
								$tempDate = $csv->Data[$base->SalesEnd];
							} else if ($endDateFormat == 'uk'){
								$tempArr = explode('/', $csv->Data[$base->SalesEnd]);
								if(count($tempArr == 3)) $tempDate = sprintf('%s-%s-%s', $tempArr[2], $tempArr[1], $tempArr[0]);
								unset($tempArr);
							} else if ($endDateFormat == 'us'){
								$tempArr = explode('/', $csv->Data[$base->SalesEnd]);
								if(count($tempArr == 3)) $tempDate = sprintf('%s-%s-%s', $tempArr[2], $tempArr[0], $tempArr[1]);
								unset($tempArr);
							}
							if(!is_null($tempDate) && isDate($tempDate)) $prod->SalesEnd = $tempDate;
						}
					}

					if(($importAction == "add" && $prod->Add()) || ($importAction == "update" && $prod->Update())){
						++$productsImported;

						if(in_array('suppuse', $fieldColumns) && $csv->Data[$supProd['Username']]!=""&& $csv->Data[$supProd['Cost']]!=0){
							$data = new DataQuery(sprintf("SELECT Supplier_ID FROM supplier WHERE Username = '%s'", mysql_real_escape_string($csv->Data[$supProd['Username']])));
							if($data->TotalRows != 0) {
								$supp = new SupplierProduct();
								$supp->Supplier->ID = $data->Row['Supplier_ID'];
								$data->Disconnect();

								$supp->Product->ID =$prod->ID;
								if($csv->Data[$supProd['Preferred']] != 'N' && $csv->Data[$supProd['Preferred']] != 'Y'){
									$supp->PreferredSup = 'N';
								}
								else{
									$supp->PreferredSup = $csv->Data[$supProd['Preferred']];
								}
								$supp->Cost = $csv->Data[$supProd['Cost']];
								$supp->Add();
							}
							else{
								$erroneousImport .= "<br>Product ".$prod->ID." Did not have a supplier added as the supplier does not exist within the database.";
								$data->Disconnect();
							}
						}
						elseif(in_array('suppuse', $fieldColumns) && $csv->Data[$supProd['Username']]==""){
							$erroneousImport.="<br>Product ".$prod->ID." Did not have a supplier added as no username was given.";
						}

						elseif(in_array('suppuse', $fieldColumns) && $csv->Data[$supProd['Cost']]==0){
							$erroneousImport.="<br>Product ".$prod->ID." Did not have a supplier added as no cost was given.";
						}


						/*
						Check Category
						If the category exists use the ID as the association
						If it doesn't add a new category and use the resulting ID as the association
						*/
						$catAssoc = NULL;
						if(isset($category)){
							$cat = new Category;
							if($cat->Exists($csv->Data[$category])){
								$catAssoc = $cat->ID;
							} else {
								if(!is_object($cat->Parent)) $cat->Parent = new Category;
								$cat->Name = $csv->Data[$category];
								$cat->Parent->ID = 0;
								$cat->IsActive = 'Y';
								$cat->Add();
								$catAssoc = $cat->ID;
							}
						}

						/*
						Create association
						assuming there is a catAssoc ID number we can create an association
						between a product and a category
						*/
						if(!is_null($catAssoc) && isset($prod->ID)){
							$prod->AddToCategory($catAssoc);
						}

						/*
						Create Technical Specs for Products
						uses the $tSpecs Array.
						field 1: Field Number
						field 0: Spec Title
						*/
						foreach($tSpecs as $groupName => $valueName) {

							$group = new ProductSpecGroup();
							$value = new ProductSpecValue();

							$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Name LIKE '%s'", mysql_real_escape_string($groupName)));
							if($data->TotalRows > 0) {
								$group->ID = $data->Row['Group_ID'];
							} else {
								$group->Name = $groupName;
								$group->Reference = $group->Name;
								$group->Add();
							}
							$data->Disconnect();

							$data = new DataQuery(sprintf("SELECT Value_ID FROM product_specification_value WHERE Value LIKE '%s' AND Group_ID=%d", mysql_real_escape_string($csv->Data[$valueName]), mysql_real_escape_string($group->ID)));
							if($data->TotalRows > 0) {
								$value->ID = $data->Row['Value_ID'];
							} else {
								$value->Value = $csv->Data[$valueName];
								$value->Group->ID = $group->ID;
								$value->Add();
							}
							$data->Disconnect();

							$prod->AddSpec($value->ID);
						}

						/*
						Set the price
						*/
						if(!empty($base->PriceOurs)){
							$prod->PriceOurs = round(str_replace($priceFix, "", $prod->PriceOurs), 2);
							$prod->PriceRRP = round(str_replace($priceFix, "", $prod->PriceRRP), 2);
							if($prod->PriceOurs != 0){
								$prod->AddPrice(NULL, NULL, 'N');
							}
						}

						/*
						Add Image
						*/
						if(!empty($base->DefaultImage->Thumb->ID) || !empty($base->DefaultImage->Large->ID)){
							if(!empty($base->DefaultImage->Large->ID)) $prod->DefaultImage->Large->SetName($csv->Data[$base->DefaultImage->Large->ID]);
							if(!empty($base->DefaultImage->Thumb->ID)) $prod->DefaultImage->Thumb->SetName($csv->Data[$base->DefaultImage->Thumb->ID]);
							if(!empty($base->DefaultImage->Name)) $prod->DefaultImage->Name = $csv->Data[$base->DefaultImage->Name];
							if(!empty($base->DefaultImage->Description)) $prod->DefaultImage->Description = $csv->Data[$base->DefaultImage->Description];
							$prod->DefaultImage->ParentID = $prod->ID;
							$prod->DefaultImage->IsActive = 'Y';
							$prod->DefaultImage->IsDefault = 'Y';
							$prod->DefaultImage->Add();
						}
					}

					$csv->Next();
				}
				$csv->Close();
				$file->Delete();
				$_SESSION['errimp'] = $erroneousImport;
				redirect("Location: product_import.php?serve=imported&num=". $productsImported);
			}

		}

		$page = new Page(sprintf('Field Settings for %s', $file->FileName), 'Please use the form below to match appropriate fields with those in your CSV document.');
		$page->Display("header");
		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('file');
		echo $form->GetHTML('delimit');
		echo $form->GetHTML('encl');

		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}

		$window = new StandardWindow("Fields");
		echo $window->Open();
		echo $window->AddHeader(sprintf('Please match as many fields as you can. The fields associated with your CSV file are shown to the left. If your file does not have field titles in the first row you will see Field n: as titles. The matches you make here will be used for each of the %s rows in your CSV file.', $csv->TotalRows));
		echo $window->OpenContent();

		$webForm = new StandardForm;
		echo $webForm->Open();

		for($i=0; $i < count($csv->FieldNames); $i++){
			echo $webForm->AddRow($form->GetLabel('field'.$i), $form->GetHTML('field'.$i) . $form->GetIcon('field'.$i));
		}
		$csv->Close();
		echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="abort" value="abort" class="btn" onClick="window.self.location=\'product_import.php?action=abort&file=%s\';"> <input type="submit" name="continue" value="continue" class="btn" tabindex="%s">', $file->FileName, $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();
		$page->Display("footer");
	} else {
		echo sprintf("Cannot continue because the CSV file \"%s\" could not be opened.", $csv->FileName);
		echo ($file->Exists())?" (The file exists)":" (The file does not exist)";
	}

}

function step1(){
	require_once("./lib/classes/Form.php");
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once("./lib/classes/IFile.php");

	$form = new Form("product_import.php");
	$form->EncType = "multipart/form-data";
	$form->AddField('action', '', 'hidden', 'step1', 'alpha_numeric', 5, 5);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('csvFile', 'CSV File', 'file', '', 'file', 3, 255);
	$form->AddField('delimit', 'Values Separated by', 'text', ',', 'paragraph', 1, 1);
	$form->AddField('encl', 'Values Enclosed in', 'text', '"', 'paragraph', 1, 1);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$file = new IFile(NULL, "./temp");
			$file->OnConflict = 'makeunique';
			$file->Extensions = "csv,txt";
			if($file->Upload('csvFile')){
				redirect(sprintf("Location: product_import.php?action=step2&file=%s&delimit=%s&encl=%s", $file->FileName, $form->GetValue("delimit"), $form->GetValue("encl")));
				exit;
			} else {
				$errors = $file->GetError();
				for($i=0; $i<count($errors); $i++) $form->AddError($errors[$i]);
			}
		}
	}

	$page = new Page('The CSV File', 'Please use the form below to select the CSV file you wish to import.');
	$page->SetFocus("csvFile");
	$page->Display("header");
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Select a CSV File");
	echo $window->Open();
	echo $window->AddHeader('Please select a CSV file from your PC using the file browser field below.');
	echo $window->OpenContent();

	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('csvFile'), $form->GetHTML('csvFile') . $form->GetIcon('csvFile'));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->AddHeader("The following fields are optional. They have been set to default values for your convenience.");
	echo $window->OpenContent();

	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('delimit'), $form->GetHTML('delimit') . $form->GetIcon('delimit'));
	echo $webForm->AddRow($form->GetLabel('encl'), $form->GetHTML('encl') . $form->GetIcon('encl'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="abort" value="abort" class="btn" onClick="window.self.location=\'product_import.php\';"> <input type="submit" name="continue" value="continue" class="btn" tabindex="%s">', $form->GetTabIndex()));

	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display("footer");
}

function abort(){
	require_once("./lib/classes/IFile.php");

	$filename = $_REQUEST['file'];

	$file = new IFile($filename, "./temp");
	if($file->Exists()) $file->Delete();

	redirect("Location: ./product_import.php");
}

function start(){
	require_once("./lib/classes/Form.php");

	$form = new Form("product_import.php");
	$form->AddField('action', '', 'hidden', 'step1', 'alpha_numeric', 5, 5);

	$page = new Page('Product Import','Welcome to the product import tool.');
	$page->Display("header");
	echo "<p>Ignition allows you to import products through CSV files. The more fields available in your CSV file the better. Before you begin please ensure that you have the following required fields within your CSV file:</p>";
	echo "<ul>";
	echo "<li>Title</li>";
	echo "<li>Description</li>";
	echo "</ul>";
	echo '<p>For a demo CSV file with the most common import fields please <a href="./lib/templates/productCsvImportTemplate.zip">Click Here</a>, or click continue to start your import.</p>';

	echo $form->Open();
	echo $form->GetHTML('action');
	echo '<input type="button" name="view all imported products" value="view all imported products" class="btn" onclick="window.location.href=\'product_import.php?serve=imported\';" /> <input type="submit" name="continue" value="continue" class="btn">';
	echo $form->Close();

	$page->Display("footer");
}