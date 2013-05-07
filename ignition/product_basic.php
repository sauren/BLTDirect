<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$product = new Product($_REQUEST['pid']);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('pid', 'ID', 'hidden', $product->ID, 'numeric_unsigned', 1, 11);
$form->AddField('type', 'Type', 'Select', $product->Type, 'alpha', 1, 1);
$form->AddOption('type', 'S', 'Standard');
$form->AddOption('type', 'G', 'Group');
$form->AddField('sku', 'SKU#', 'text', $product->SKU, 'paragraph', 1, 30, false);
$form->AddField('title', 'Title', 'textarea', $product->HTMLTitle, 'anything', 3, 100);
$form->AddField('similar', 'Similar Text', 'text', $product->SimilarText, 'paragraph', 1, 240, false);
$form->AddField('orderRule','Order Warehouse Rule','Select',$product->OrderRule,'alpha', 1, 3, false);
$form->AddOption('orderRule','M','Manually select in order desk');
$form->AddOption('orderRule','W','Automatically ship when ordered');
$form->AddField('manufacturer', 'Manufacturer', 'select', $product->Manufacturer->ID, 'numeric_unsigned', 1, 11, false);
$form->AddOption('manufacturer', '0', '');

$data = new DataQuery("SELECT Manufacturer_ID, Manufacturer_Name FROM manufacturer ORDER BY Manufacturer_Name ASC");
while($data->Row) {
	$form->AddOption('manufacturer', $data->Row['Manufacturer_ID'], $data->Row['Manufacturer_Name']);

	$data->Next();
}
$data->Disconnect();

$form->AddField('model', 'Model', 'text', $product->Model, 'paragraph', 1, 30, false);
$form->AddField('variant', 'Variant', 'text', $product->Variant, 'paragraph', 1, 30, false);

if($product->IsDemo == 'Y') {
	$form->AddField('isdemo', 'Is Demo', 'checkbox', $product->IsDemo, 'boolean', 1, 1, false);
}

$form->AddField('iscomplementary', 'Is Complementary', 'checkbox', $product->IsComplementary, 'boolean', 1, 1, false);
$form->AddField('isbestseller', 'Is Best Seller', 'checkbox', $product->IsBestSeller, 'boolean', 1, 1, false);
$form->AddField('quality', 'Quality', 'select', $product->Quality, 'paragraph', 1, 30, false);
$form->AddOption('quality', '', '');
$form->AddOption('quality', 'Value', 'Value');
$form->AddOption('quality', 'Premium', 'Premium');
$form->AddField('qualitytext', 'Quality Text', 'text', $product->QualityText, 'paragraph', 1, 120, false);

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$product->Quality = $form->GetValue('quality');
		$product->QualityText = $form->GetValue('qualitytext');
		$product->Type = $form->GetValue('type');
		$product->Name = $form->GetValue('title');
		$product->SimilarText = $form->GetValue('similar');
		$product->SKU = $form->GetValue('sku');
		$product->Manufacturer->ID = $form->GetValue('manufacturer');
		$product->Model = $form->GetValue('model');
		$product->Variant = $form->GetValue('variant');
		$product->OrderRule = $form->GetValue('orderRule');

		if($product->IsDemo == 'Y') {
			$product->IsDemo = $form->GetValue('isdemo');
		}

		$product->IsComplementary = $form->GetValue('iscomplementary');
		$product->IsBestSeller = $form->GetValue('isbestseller');
		$product->Update();

		redirect(sprintf("Location: product_profile.php?pid=%d", $product->ID));
	}
}

$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> > Basic Settings', $_REQUEST['pid']),'The more information you supply the better your system will become');
$page->SetEditor(true);
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
echo $webForm->AddRow($form->GetLabel('quality'), $form->GetHTML('quality') . $form->GetIcon('quality'));
echo $webForm->AddRow($form->GetLabel('qualitytext'), $form->GetHTML('qualitytext') . $form->GetIcon('qualitytext'));
echo $webForm->AddRow($form->GetLabel('type'), $form->GetHTML('type') . $form->GetIcon('type'));
echo $webForm->AddRow($form->GetLabel('sku'), $form->GetHTML('sku') . $form->GetIcon('sku'));
echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
echo $webForm->AddRow($form->GetLabel('manufacturer'), $form->GetHTML('manufacturer') . $form->GetIcon('manufacturer'));
echo $webForm->AddRow($form->GetLabel('orderRule'),$form->GetHTML('orderRule').$form->GetIcon('orderRule'));
echo $webForm->AddRow($form->GetLabel('model'), $form->GetHTML('model') . $form->GetIcon('model'));
echo $webForm->AddRow($form->GetLabel('variant'), $form->GetHTML('variant') . $form->GetIcon('variant'));

if($product->IsDemo == 'Y') {
	echo $webForm->AddRow($form->GetLabel('isdemo'), $form->GetHTML('isdemo') . $form->GetIcon('isdemo'));
}

echo $webForm->AddRow($form->GetLabel('iscomplementary'), $form->GetHTML('iscomplementary') . $form->GetIcon('iscomplementary'));
echo $webForm->AddRow($form->GetLabel('isbestseller'), $form->GetHTML('isbestseller') . $form->GetIcon('isbestseller'));
echo $webForm->AddRow($form->GetLabel('similar'), $form->GetHTML('similar') . $form->GetIcon('similar'));
echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_profile.php?pid=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $product->ID, $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');