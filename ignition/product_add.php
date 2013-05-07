<?php
require_once('lib/common/app_header.php');

require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$session->Secure(3);

$form = new Form("product_add.php");
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('sku', 'SKU#', 'text', '', 'paragraph', 1, 30, false);
$form->AddField('title', 'Title', 'text', '', 'paragraph', 3, 255);
$form->AddField('blurb', 'Blurb', 'textarea', '', 'paragraph', 1, 255, true, 'style="width:100%; height:200px"');
$form->AddField('description', 'Description', 'textarea', '', 'paragraph', 1, 2000, true, 'style="width:100%; height:300px"');
$form->AddField('orderRule','Order Shipping Rule','Select','M','alpha',1,3,false);
$form->AddOption('orderRule','M','Manually select in order desk');
$form->AddOption('orderRule','W','Ship from warehouse');
$form->AddOption('orderRule','S','Ship from supplier');
$form->AddField('manufacturer', 'Manufacturer', 'select', '0', 'numeric_unsigned', 1, 11, false);
$form->AddOption('manufacturer', '0', '');
$form->AddField('sync', 'Synchronise Across Sites', 'checkbox', 'N', 'boolean', 1, 1, false);

$man = new DataQuery("select * from manufacturer order by Manufacturer_Name asc");
do{
	$form->AddOption('manufacturer', $man->Row['Manufacturer_ID'], $man->Row['Manufacturer_Name']);
	$man->Next();
} while($man->Row);
$man->Disconnect();

$form->AddField('model', 'Model', 'text', '', 'paragraph', 1, 30, false);
$form->AddField('variant', 'Variant', 'text', '', 'paragraph', 1, 30, false);
$form->AddField('quality', 'Quality', 'select', '', 'paragraph', 1, 30, false);
$form->AddOption('quality', '', '');
$form->AddOption('quality', 'Value', 'Value');
$form->AddOption('quality', 'Premium', 'Premium');
$form->AddField('qualitytext', 'Quality Text', 'text', '', 'paragraph', 1, 120, false);

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$product = new Product();
		$product->Quality = $form->GetValue('quality');
		$product->QualityText = $form->GetValue('qualitytext');
		$product->Name = $form->GetValue('title');
		$product->SKU = $form->GetValue('sku');
		$product->Manufacturer->ID = $form->GetValue('manufacturer');
		$product->Model = $form->GetValue('model');
		$product->Variant = $form->GetValue('variant');
		$product->Description = $form->GetValue('description');
		$product->Blurb = $form->GetValue('blurb');
		$product->OrderRule = $form->GetValue('orderRule');
		$product->Add(($form->GetValue('sync') == 'Y') ? true : false);

		redirect(sprintf("Location: product_profile.php?pid=%d", $product->ID));
	}
}

$page = new Page('Add a New Product','The more information you supply the better your system will become');
$page->SetEditor(true);
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo "<br>";
}

$window = new StandardWindow('Update');
$webForm = new StandardForm;

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');

echo $window->Open();
echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('quality'), $form->GetHTML('quality') . $form->GetIcon('quality'));
echo $webForm->AddRow($form->GetLabel('qualitytext'), $form->GetHTML('qualitytext') . $form->GetIcon('qualitytext'));
echo $webForm->AddRow($form->GetLabel('sync'), $form->GetHTML('sync') . ' (Copies this product onto all sites)');
echo $webForm->AddRow($form->GetLabel('sku'), $form->GetHTML('sku') . $form->GetIcon('sku'));
echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
echo $webForm->AddRow($form->GetLabel('manufacturer'), $form->GetHTML('manufacturer') . $form->GetIcon('manufacturer'));
echo $webForm->AddRow($form->GetLabel('orderRule'),$form->GetHTML('orderRule').$form->GetIcon('orderRule'));
echo $webForm->AddRow($form->GetLabel('model'), $form->GetHTML('model') . $form->GetIcon('model'));
echo $webForm->AddRow($form->GetLabel('variant'), $form->GetHTML('variant') . $form->GetIcon('variant'));
echo $webForm->AddRow($form->GetLabel('blurb'), $form->GetHTML('blurb'));
echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description'));
echo $webForm->AddRow("&nbsp;", sprintf('<input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');