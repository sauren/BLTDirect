<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$session->Secure(3);

$product = new Product($_REQUEST['pid']);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('pid', 'ID', 'hidden', $product->ID, 'numeric_unsigned', 1, 11);
$form->AddField('barcode', 'Barcode Used Internally', 'text', $product->InternalBarcode, 'anything', 0, 30, false);
$form->AddField('weight', 'Weight (kg)', 'text', $product->Weight, 'float', 1, 11, false);
$form->AddField('width', 'Shelf Width (m)', 'text', $product->Width, 'float', 1, 11, false);
$form->AddField('height', 'Shelf Height (m)', 'text', $product->Height, 'float', 1, 11, false);
$form->AddField('depth', 'Shelf Depth (m)', 'text', $product->Depth, 'float', 1, 11, false);
$form->AddField('volume', 'Volume (m<sup>3</sup>)', 'text', $product->Volume, 'float', 1, 11, false);
$form->AddField('units', 'Units Per Pallet', 'text', $product->UnitsPerPallet, 'numeric_unsigned', 1, 11, false);
$form->AddField('suspend', 'Suspend Sales On', 'text', $product->StockSuspend, 'numeric_signed', 1, 11, false);
$form->AddField('alert','Stock Level Alert','text',$product->StockAlert,'numeric_signed',1,11,false);
$form->AddField('reorder','Stock Reorder Quantity','text',$product->StockReorderQuantity,'numeric_signed',1,11,false);
$form->AddField('orderRule','Order Warehouse Rule','Select',$product->OrderRule,'alpha',1,3,false);
$form->AddOption('orderRule','M','Manually select in order desk');
$form->AddOption('orderRule','W','Automatically ship when ordered');
$form->AddField('stocked', 'Is Stocked', 'checkbox', $product->Stocked, 'boolean', 1, 1, false);
$form->AddField('stockedtemporarily', 'Is Stocked Temporarily', 'checkbox', $product->StockedTemporarily, 'boolean', 1, 1, false);
$form->AddField('imported', 'Is Stock Imported', 'checkbox', $product->StockImported, 'boolean', 1, 1, false);
$form->AddField('monitor', 'Monitor Stock', 'checkbox', $product->StockMonitor, 'boolean', 1, 1, false);
$form->AddField('warehouseshipped', 'Is Warehouse Shipped', 'checkbox', $product->IsWarehouseShipped, 'boolean', 1, 1, false);

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
	if($form->Validate()) {
		$product->InternalBarcode = $form->GetValue('barcode');
		$product->Weight = $form->GetValue('weight');
		$product->Width = $form->GetValue('width');
		$product->Height = $form->GetValue('height');
		$product->Depth = $form->GetValue('depth');
		$product->Volume = $form->GetValue('volume');
		$product->UnitsPerPallet = $form->GetValue('units');
		$product->StockSuspend = $form->GetValue('suspend');
		$product->Stocked = $form->GetValue('stocked');
		$product->StockedTemporarily = $form->GetValue('stockedtemporarily');
		$product->StockImported = $form->GetValue('imported');
		$product->StockMonitor = $form->GetValue('monitor');
		$product->OrderRule = $form->GetValue('orderRule');
		$product->StockAlert = $form->GetValue('alert');
		$product->StockReorderQuantity = $form->GetValue('reorder');
		$product->IsWarehouseShipped = $form->GetValue('warehouseshipped');
		$product->Update();

		redirect(sprintf("Location: product_profile.php?pid=%d", $product->ID));
	}
}

$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> > Stock Settings', $_REQUEST['pid']),'The more information you supply the better your system will become');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Update');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
echo $form->GetHTML('pid');

echo $window->Open();
echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('barcode'), $form->GetHTML('barcode') . $form->GetIcon('barcode') . sprintf('<a href="product_barcodes.php?pid=%d">Manufacturer Barcodes</a>', $_REQUEST['pid']));
echo $webForm->AddRow($form->GetLabel('weight'), $form->GetHTML('weight') . $form->GetIcon('weight'));
echo $webForm->AddRow($form->GetLabel('width'), $form->GetHTML('width') . $form->GetIcon('width'));
echo $webForm->AddRow($form->GetLabel('height'), $form->GetHTML('height') . $form->GetIcon('height'));
echo $webForm->AddRow($form->GetLabel('depth'), $form->GetHTML('depth') . $form->GetIcon('depth'));
echo $webForm->AddRow($form->GetLabel('volume'), $form->GetHTML('volume') . $form->GetIcon('volume'));
echo $webForm->AddRow($form->GetLabel('units'), $form->GetHTML('units') . $form->GetIcon('units'));
echo $webForm->AddRow($form->GetLabel('suspend'), $form->GetHTML('suspend') . $form->GetIcon('suspend'));
echo $webForm->AddRow($form->GetLabel('alert'),$form->GetHTML('alert').$form->GetIcon('alert'));
echo $webForm->AddRow($form->GetLabel('reorder'),$form->GetHTML('reorder').$form->GetIcon('reorder'));
echo $webForm->AddRow($form->GetLabel('orderRule'),$form->GetHTML('orderRule').$form->GetIcon('orderRule'));
echo $webForm->AddRow($form->GetLabel('stocked'), $form->GetHTML('stocked') . $form->GetIcon('stocked'));
echo $webForm->AddRow($form->GetLabel('stockedtemporarily'), $form->GetHTML('stockedtemporarily') . $form->GetIcon('stockedtemporarily'));
echo $webForm->AddRow($form->GetLabel('imported'), $form->GetHTML('imported') . $form->GetIcon('imported'));
echo $webForm->AddRow($form->GetLabel('monitor'), $form->GetHTML('monitor') . $form->GetIcon('monitor'));
echo $webForm->AddRow($form->GetLabel('warehouseshipped'), $form->GetHTML('warehouseshipped') . $form->GetIcon('warehouseshipped'));
echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_profile.php?pid=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $product->ID, $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');
?>