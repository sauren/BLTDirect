<?php
ini_set('max_execution_time', '1800');
ini_set('memory_limit', '1024M');

chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Cron.php');

$cron = new Cron();
$cron->scriptName = 'Just Lamps Integration';
$cron->scriptFileName = 'integrate_justlamps.php';
$cron->mailLogLevel = Cron::LOG_LEVEL_WARNING;

## BEGIN SCRIPT
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Category.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Manufacturer.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Product.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/ProductImage.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/ProductInCategory.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/ProductPrice.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/ProductSpec.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/ProductSpecValue.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/SupplierMarkup.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/SupplierProduct.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/UrlAlias.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/WarehouseStock.php');

function refreshSpecification($productId, $specGroupId, $specValue) {
	$data = new DataQuery(sprintf("SELECT Value_ID FROM product_specification_value WHERE Group_ID=%d AND Value LIKE '%s' LIMIT 0, 1", $specGroupId, mysql_real_escape_string($specValue)));
	if($data->TotalRows > 0) {
		$value = new ProductSpecValue();
		$value->ID = $data->Row['Value_ID'];
	} else {
		$value = new ProductSpecValue();
		$value->Value = $specValue;
		$value->Group->ID = $specGroupId;
		$value->Add();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT Specification_ID FROM product_specification WHERE Value_ID=%d AND Product_ID=%d LIMIT 0, 1", $value->ID, $productId));
	if($data->TotalRows == 0) {
        $data = new DataQuery(sprintf("SELECT Specification_ID FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON psv.Value_ID=ps.Value_ID WHERE ps.Product_ID=%d AND psv.Group_ID=%d", $productId, $specGroupId));
		while($data->Row) {
			$delete = new ProductSpec();
			$delete->ID = $data->Row['Specification_ID'];
			$delete->Delete();

			$data->Next();
		}
		$data->Disconnect();

		$specification = new ProductSpec();
		$specification->Product->ID = $productId;
		$specification->Value->ID = $value->ID;
		$specification->Add();
	}
	$data->Disconnect();
}

$connection = new MySQLConnection($GLOBALS['JL_DB_HOST'], $GLOBALS['JL_DB_NAME'], $GLOBALS['JL_DB_USERNAME'], $GLOBALS['JL_DB_PASSWORD']);
$targetCategoryId = 227;

$data = new DataQuery(sprintf("SELECT Type_Reference_ID FROM warehouse WHERE Warehouse_ID=%d", mysql_real_escape_string($GLOBALS['JL_WAREHOUSE'])));
$targetSupplierId = ($data->TotalRows > 0) ? $data->Row['Type_Reference_ID'] : 0;
$data->Disconnect();
$markup = new SupplierMarkup();
$markup->GetBySupplierID($targetSupplierId);

if($targetSupplierId > 0) {
	$integrationCount = 0;
	$existingItems = array();
	$manufacturerItems = array();
	$categoryItems = array();
	$usedItems = array();
	$productItems = array();

	$data = new DataQuery(sprintf("SELECT Product_ID, Integration_ID FROM product WHERE Integration_ID>0 AND Discontinued='N'"));
	while($data->Row) {
		$existingItems[$data->Row['Integration_ID']] = $data->Row['Product_ID'];

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT * FROM uk_trade"), $connection);
	if($data->TotalRows > 0) {
		while($data->Row) {
			$integrationCount++;
			$integrationId = $data->Row['ID'];

			unset($existingItems[$integrationId]);

			$manufacturerId = 0;
			$manufacturerName = ucwords(strtolower(trim($data->Row['Manufacturer'])));
			$manufacturerHash = md5($manufacturerName);

			if(!isset($manufacturerItems[$manufacturerHash])) {
				$data2 = new DataQuery(sprintf("SELECT Manufacturer_ID FROM manufacturer WHERE Manufacturer_Name LIKE '%s' LIMIT 0, 1", mysql_real_escape_string($manufacturerName)));
				if($data2->TotalRows > 0) {
					$manufacturerId = $data2->Row['Manufacturer_ID'];
					
					$manufacturer = new Manufacturer($manufacturerId);
					$manufacturer->IsDataProjector = 'Y';
					$manufacturer->Update();
				} else {
					$manufacturer = new Manufacturer();
					$manufacturer->Name = $manufacturerName;
					$manufacturer->IsDataProjector = 'Y';
					$manufacturer->Add();

					$manufacturerId = $manufacturer->ID;
				}
				$data2->Disconnect();

				$manufacturerItems[$manufacturerHash] = array('ManufacturerID' => $manufacturerId, 'ManufacturerName' => $manufacturerName, 'Products' => array(), 'CategoryID' => 0);
			} else {
				$manufacturerId = $manufacturerItems[$manufacturerHash]['ManufacturerID'];
			}

			$productName = sprintf('%s %s (%s)', $manufacturerName, $data->Row['ModelNo'], trim($data->Row['Suffix']));

			$description = sprintf('The %s for %s ', $productName, $data->Row['Display']);
			$description .= (!empty($data->Row['Wattage']) || !empty($data->Row['Lamphours'])) ? 'runs ' : '';
			$description .= !empty($data->Row['Wattage']) ? sprintf('at %sW ', $data->Row['Wattage']) : '';
			$description .= !empty($data->Row['Lamphours']) ? sprintf('for %s hours ', $data->Row['Lamphours']) : '';

			$description = trim($description);
			$description .= '.';

			if(stristr(trim($data->Row['Suffix']), 'diamond') === false) {
				$description .= ' As supplied by the original projector light bulb manufacturer for the optimum performance.';
			} else {
				$description .= ' Authorised by the original light bulb manufacturers, this is a lower cost alternative to the original manufacturers projector lamp. Complete with a new chassis, this lamp gives identical performance to the original manufacturers light bulb or lamp with an unprecedented 4 month warranty, although endorsed by a number of projector manufacturers, the lamp may invalidate some projector warranties; please check if the warranty on the projector is still current or affected before purchase.';
			}
			
			$data2 = new DataQuery(sprintf("SELECT Product_ID FROM product WHERE Integration_ID=%d", $integrationId));
			if($data2->TotalRows > 0) {
				$product = new Product($data2->Row['Product_ID']);
				$product->SKU = $data->Row['Manupartcode'];
				$product->Name = $productName;
				$product->Manufacturer->ID = $manufacturerId;
				$product->Model = $data->Row['ModelNo'];
				$product->Blurb = $description;
				$product->Description = $product->Blurb;
				$product->MetaTitle = sprintf('%s %s %s %s %s', $manufacturerName, $data->Row['ModelNo'], $data->Row['LampType'], $data->Row['Display'], trim($data->Row['Suffix']));
				$product->MetaDescription = $product->Blurb;
				$product->MetaKeywords = strtolower(str_replace(' ', ', ', sprintf('%s %s %s %s %s %s', $manufacturerName, $data->Row['ModelNo'], $data->Row['Display'], trim($data->Row['Suffix']), $data->Row['Wattage'], $data->Row['LampType'])));
				$product->GoogleBaseSuffix = 'Projector Lamp';
				$product->OrderMax = $data->Row['Available_Stock'];
				$product->DespatchDaysMin = (($data->Row['Typical_Leadtime'] - 2) <= 0) ? 1 : $data->Row['Typical_Leadtime'] - 2;
				$product->DespatchDaysMax = $data->Row['Typical_Leadtime'] + 2;
				$product->Discontinued = 'N';
				$product->DiscontinuedOn = '0000-00-00 00:00:00';
				$product->Update();

				$manufacturerItems[$manufacturerHash]['Products'][] = $product->ID;

				$data3 = new DataQuery(sprintf("SELECT COUNT(Product_Image_ID) AS Count FROM product_images WHERE Product_ID=%d", $product->ID));
				if($data3->Row['Count'] == 0) {
					$image = new ProductImage();
					$image->ParentID = $product->ID;
					$image->Thumb->FileName = 'projector-lamp-thumb.jpg';
					$image->Thumb->Width = 100;
					$image->Thumb->Height = 81;
					$image->Large->FileName = 'projector-lamp-large.jpg';
					$image->Large->Width = 148;
					$image->Large->Height = 120;
					$image->Name = $product->Name;
					$image->Description = $product->Name;
					$image->IsDefault = 'Y';
					$image->Add();
				}
				$data3->Disconnect();

				$newPrice = round($data->Row['Trade_Price'] + ($markup->Value * $data->Row['Trade_Price']), 2);

				$price = new ProductPrice();
				$price->ProductID = $product->ID;
				$price->GetProductPrice();

				if(bccomp($price->PriceOurs, $newPrice, 2) != 0) {
					$price->PriceOurs = $newPrice;
					$price->PriceStartsOn = date('Y-m-d H:i:s');
					$price->Add();
				}

				$data3 = new DataQuery(sprintf("SELECT Stock_ID FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d", mysql_real_escape_string($GLOBALS['JL_WAREHOUSE']), $product->ID));
				$warehouseStockId = ($data3->TotalRows > 0) ? $data3->Row['Stock_ID'] : 0;
				$data3->Disconnect();

				if($warehouseStockId > 0) {
					$stock = new WarehouseStock($warehouseStockId);
					$stock->QuantityInStock = $data->Row['Available_Stock'];
					$stock->Update();
				} else {
					$stock = new WarehouseStock();
					$stock->Product->ID = $product->ID;
					$stock->QuantityInStock = $data->Row['Available_Stock'];
					$stock->Warehouse->ID = $GLOBALS['JL_WAREHOUSE'];
					$stock->Stocked = 'Y';
					$stock->Moniter = 'Y';
					$stock->Add();
				}

				$data3 = new DataQuery(sprintf("SELECT Supplier_Product_ID FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", $targetSupplierId, $product->ID));
				$supplierProductId = ($data3->TotalRows > 0) ? $data3->Row['Supplier_Product_ID'] : 0;
				$data3->Disconnect();

				if($supplierProductId > 0) {
					$supplier = new SupplierProduct($supplierProductId);
					$supplier->Cost = number_format(round($data->Row['Trade_Price'], 2), 2, '.', '');
					$supplier->SKU = $data->Row['Manupartcode'];
					$supplier->Update();
				} else {
					$supplier = new SupplierProduct();
					$supplier->Supplier->Get(24);
					$supplier->Product->ID = $product->ID;
					$supplier->PreferredSup = 'Y';
					$supplier->Cost = number_format(round($data->Row['Trade_Price'], 2), 2, '.', '');
					$supplier->SKU = $data->Row['Manupartcode'];
					$supplier->Add();
				}
			} else {
				$product = new Product();
				$product->IntegrationID = $integrationId;
				$product->SKU = $data->Row['Manupartcode'];
				$product->Name = $productName;
				$product->Manufacturer->ID = $manufacturerId;
				$product->Model = $data->Row['ModelNo'];
				$product->Blurb = $description;
				$product->Description = $product->Blurb;
				$product->MetaTitle = sprintf('%s %s %s %s %s', $manufacturerName, $data->Row['ModelNo'], $data->Row['LampType'], $data->Row['Display'], trim($data->Row['Suffix']));
				$product->MetaDescription = $product->Blurb;
				$product->MetaKeywords = sprintf('%s %s %s %s %s %s', $manufacturerName, $data->Row['ModelNo'], $data->Row['Display'], trim($data->Row['Suffix']), $data->Row['Wattage'], $data->Row['LampType']);
				$product->GoogleBaseSuffix = 'Projector Lamp';
				$product->OrderMin = 1;
				$product->OrderMax = $data->Row['Available_Stock'];
				$product->DespatchDaysMin = (($data->Row['Typical_Leadtime'] - 2) <= 0) ? 1 : $data->Row['Typical_Leadtime'] - 2;
				$product->DespatchDaysMax = $data->Row['Typical_Leadtime'] + 2;
				$product->TaxClass->GetDefault();
				$product->ShippingClass->ID = 19;
				$product->Add();

				$manufacturerItems[$manufacturerHash]['Products'][] = $product->ID;

				$image = new ProductImage();
				$image->ParentID = $product->ID;
				$image->Thumb->FileName = 'projector-lamp-thumb.jpg';
				$image->Thumb->Width = 100;
				$image->Thumb->Height = 81;
				$image->Large->FileName = 'projector-lamp-large.jpg';
				$image->Large->Width = 148;
				$image->Large->Height = 120;
				$image->Name = $product->Name;
				$image->Description = $product->Name;
				$image->IsDefault = 'Y';
				$image->Add();

				$price = new ProductPrice();
				$price->ProductID = $product->ID;
				$price->Quantity = 1;
				$price->PriceOurs = $data->Row['Trade_Price'] + ($markup->Value * $data->Row['Trade_Price']);
				$price->PriceStartsOn = date('Y-m-d H:i:s');
				$price->Add();

				$stock = new WarehouseStock();
				$stock->Product->ID = $product->ID;
				$stock->QuantityInStock = $data->Row['Available_Stock'];
				$stock->Warehouse->ID = $GLOBALS['JL_WAREHOUSE'];
				$stock->Stocked = 'Y';
				$stock->Moniter = 'Y';
				$stock->Add();

				$supplier = new SupplierProduct();
				$supplier->Supplier->Get($targetSupplierId);
				$supplier->Product->ID = $product->ID;
				$supplier->PreferredSup = 'Y';
				$supplier->Cost = number_format(round($data->Row['Trade_Price'], 2), 2, '.', '');
				$supplier->SKU = $data->Row['Manupartcode'];
				$supplier->Add();
			}
			$data2->Disconnect();

			$specGroupId = 137;
			$specValue = $manufacturerName;

			refreshSpecification($product->ID, $specGroupId, $specValue);

			if(!empty($data->Row['Wattage'])) {
				$specGroupId = 211;
				$specValue = str_replace(' ', '', $data->Row['Wattage']);

				refreshSpecification($product->ID, $specGroupId, $specValue);
			} else {
                $data2 = new DataQuery(sprintf("SELECT Specification_ID FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON psv.Value_ID=ps.Value_ID WHERE ps.Product_ID=%d AND psv.Group_ID=%d", $product->ID, $specGroupId));
				while($data2->Row) {
					$delete2 = new ProductSpec();
					$delete2->ID = $data2->Row['Specification_ID'];
					$delete2->Delete();

					$data2->Next();
				}
				$data2->Disconnect();
			}

			if(!empty($data->Row['LampType'])) {
				$specGroupId = 110;
				$specValue = $data->Row['LampType'];

				refreshSpecification($product->ID, $specGroupId, $specValue);
			} else {
                $data2 = new DataQuery(sprintf("SELECT Specification_ID FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON psv.Value_ID=ps.Value_ID WHERE ps.Product_ID=%d AND psv.Group_ID=%d", $product->ID, $specGroupId));
				while($data2->Row) {
					$delete3 = new ProductSpec();
					$delete3->ID = $data2->Row['Specification_ID'];
					$delete3->Delete();
					
					$data2->Next();
				}
				$data2->Disconnect();
			}

			if(!empty($data->Row['Lamphours'])) {
				$specGroupId = 93;
				$specValue = $data->Row['Lamphours'];

				refreshSpecification($product->ID, $specGroupId, $specValue);
			} else {
                $data2 = new DataQuery(sprintf("SELECT Specification_ID FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON psv.Value_ID=ps.Value_ID WHERE ps.Product_ID=%d AND psv.Group_ID=%d", $product->ID, $specGroupId));
				while($data2->Row) {
					$delete4 = new ProductSpec();
					$delete4->ID = $data2->Row['Specification_ID'];
					$delete4->Delete();

					$data2->Next();
				}
				$data2->Disconnect();
			}

			$data->Next();
		}
	}
	$data->Disconnect();

	$cron->log(sprintf('Integrated %d Just Lamps data records.', $integrationCount), Cron::LOG_LEVEL_INFO);

	foreach($existingItems as $integrationId => $productId) {
		$product = new Product($productId);
		$product->Discontinued = 'Y';
		$product->DiscontinuedOn = date('Y-m-d H:i:s');
		$product->Update();

		$cron->log(sprintf('Discontinued: %s [#%d], Integration ID: #%d.', $product->Name, $product->ID, $integrationId), Cron::LOG_LEVEL_INFO);
	}

	$data = new DataQuery(sprintf("SELECT Category_ID, Category_Title FROM product_categories WHERE Category_Parent_ID=%d", $targetCategoryId));
	while($data->Row) {
		$categoryItems[$data->Row['Category_ID']] = $data->Row['Category_Title'];

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT pic.Product_ID, pic.Category_ID FROM product_categories AS pc INNER JOIN product_in_categories AS pic ON pc.Category_ID=pic.Category_ID WHERE pc.Category_Parent_ID=%d", $targetCategoryId));
	while($data->Row) {
		if(!isset($productItems[$data->Row['Product_ID']])) {
			$productItems[$data->Row['Product_ID']] = array();
		}

		$productItems[$data->Row['Product_ID']][$data->Row['Category_ID']] = $data->Row['Category_ID'];

		$data->Next();
	}
	$data->Disconnect();

	$product = new Product();

	foreach($manufacturerItems as $itemHash => $manufacturerData) {
		$found = false;
		$categoryName = $manufacturerData['ManufacturerName'];

		foreach($categoryItems as $categoryId => $categoryTitle) {
			if(!empty($categoryTitle)) {
				if(stristr($categoryName, $categoryTitle) !== false) {
					$found = true;
					$manufacturerItems[$itemHash]['CategoryID'] = $categoryId;
					$usedItems[$categoryId] = $categoryId;

					break;
				}
			}
		}

		if(!$found) {
			$category = new Category();
			$category->Parent = new Category();
			$category->Parent->ID = $targetCategoryId;
			$category->Name = $categoryName;
			$category->Description = sprintf('A range of %s projector lamps and light bulbs for use as direct replacements in most %s projector units.', $manufacturerData['ManufacturerName'], $manufacturerData['ManufacturerName']);
			$category->MetaTitle = sprintf('%s & Light Bulbs', $category->Name);
			$category->MetaDescription = $category->Name;
			$category->MetaKeywords = strtolower(sprintf('%s, Light Bulbs, Projector Lamps, Data Projector Lamps', str_replace(' ', ', ', $category->Name)));
			$category->IsFilterAvailable = 'N';
			$category->ShowImage = 'N';
			$category->UseUrlAlias = 'Y';
			$category->Add();

			$manufacturerItems[$itemHash]['CategoryID'] = $category->ID;

			$urlAlias = new UrlAlias();
			$urlAlias->Alias = str_replace(' ', '-', $category->Name);
			$urlAlias->Type = 'Category';
			$urlAlias->ReferenceID = $category->ID;
			$urlAlias->Add();
		} else {
			$category = new Category();

			if($category->Get($manufacturerItems[$itemHash]['CategoryID'])) {
				$category->Description = sprintf('A range of %s projector lamps and light bulbs for use as direct replacements in most %s projector units.', $manufacturerData['ManufacturerName'], $manufacturerData['ManufacturerName']);
				$category->MetaTitle = sprintf('%s & Light Bulbs', $category->Name);
				$category->MetaDescription = $category->Name;
				$category->MetaKeywords = strtolower(sprintf('%s, Light Bulbs, Projector Lamps, Data Projector Lamps', str_replace(' ', ', ', $category->Name)));
				$category->IsActive = 'Y';
				$category->Update();
			} else {
				$cron->log(sprintf('Could not find existing Category ID [#%d] for updating.', $category->ID), Cron::LOG_LEVEL_WARNING);
			}
		}

		foreach($categoryItems as $categoryId => $categoryTitle) {
			if(!isset($usedItems[$categoryId])) {
				$category = new Category();

				if($category->Get($categoryId) && ($category->IsActive == 'Y')) {
					$category->IsActive = 'N';
					$category->Update();

					$cron->log(sprintf('Deactivated: %s [#%d].', $category->Name, $category->ID), Cron::LOG_LEVEL_INFO);
				}
			}
		}

		foreach($manufacturerData['Products'] as $productId) {
			if(!isset($productItems[$productId][$manufacturerItems[$itemHash]['CategoryID']])) {
				$product->ID = $productId;
				$product->AddToCategory($manufacturerItems[$itemHash]['CategoryID']);
			}

			if(isset($productItems[$productId])) {
				foreach($productItems[$productId] as $categoryId) {
					if($manufacturerItems[$itemHash]['CategoryID'] != $categoryId) {
						$delete5 = new ProductInCategory();
						$delete5->productId = $productId;
						$delete5->categoryId = $categoryId;
						$delete5->deleteByProductAndCategory();
					}
				}
			}
		}
	}
} else {
	$cron->log('Could not find Just Lamps associated warehouse supplier.', Cron::LOG_LEVEL_ERROR);
}
## END SCRIPT

$cron->execute();
$cron->output();

$GLOBALS['DBCONNECTION']->Close();