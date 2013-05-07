<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');

$session->Secure(3);

$supplier = new Supplier($_REQUEST['sid']);
$supplier->Contact->Get();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('sid', 'Supplier ID', 'hidden', $_REQUEST['sid'], 'numeric_unsigned', 1, 11);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('reference', 'Reference', 'text', $supplier->Reference, 'anything', 1, 30, false);
$form->AddField('ipaccess', 'IP Access', 'text', $supplier->IP->Access, 'anything', 1, 2000, false);
$form->AddField('iprestrictions', 'IP Restrictions', 'text', $supplier->IP->Restrictions, 'anything', 1, 2000, false);
$form->AddField('comparable', 'Is Cost Prices Comparable', 'checkbox', $supplier->IsComparable, 'boolean', 1, 1, false);
$form->AddField('stocker', 'Is Stocker Only', 'checkbox', $supplier->IsStockerOnly, 'boolean', 1, 1, false);
$form->AddField('drop', 'Is Drop Shipper', 'checkbox', $supplier->IsDropShipper, 'boolean', 1, 1, false);
$form->AddField('warehouse', 'Drop Shipper (Warehouse)', 'select', $supplier->DropShipperID, 'numeric_unsigned', 1, 11);
$form->AddOption('warehouse', '0', '');
$form->AddField('showproduct', 'Show Product Information', 'checkbox', $supplier->ShowProduct, 'boolean', 1, 1, false);
$form->AddField('minimumpurchase', 'Free Shipping Minimum Purchase', 'text', $supplier->FreeShippingMinimumPurchase, 'float', 1, 11);

$data = new DataQuery(sprintf("SELECT Warehouse_ID, Warehouse_Name FROM warehouse WHERE Type='B'"));
while($data->Row) {
	$form->AddOption('warehouse', $data->Row['Warehouse_ID'], $data->Row['Warehouse_Name']);

	$data->Next();
}
$data->Disconnect();

$form->AddField('favourite', 'Is Favourite', 'checkbox', $supplier->IsFavourite, 'boolean', 1, 1, false);
$form->AddField('bidder', 'Is Bidder', 'checkbox', $supplier->IsBidder, 'boolean', 1, 1, false);
$form->AddField('autoship', 'Is Auto Ship', 'checkbox', $supplier->IsAutoShip, 'boolean', 1, 1, false);

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
	if($form->Validate()) {
		$supplier->Reference = $form->GetValue('reference');
		$supplier->IP->Access = $form->GetValue('ipaccess');
		$supplier->IP->Restrictions = $form->GetValue('iprestrictions');
		$supplier->IsComparable = $form->GetValue('comparable');
		$supplier->IsStockerOnly = $form->GetValue('stocker');
		$supplier->IsDropShipper = $form->GetValue('drop');
		$supplier->DropShipperID = $form->GetValue('warehouse');
		$supplier->IsFavourite = $form->GetValue('favourite');
		$supplier->IsBidder = $form->GetValue('bidder');
		$supplier->IsAutoShip = $form->GetValue('autoship');
		$supplier->ShowProduct = $form->GetValue('showproduct');
		$supplier->FreeShippingMinimumPurchase = $form->GetValue('minimumpurchase');
		$supplier->Update();

		redirect(sprintf("Location: contact_profile.php?cid=%d", $supplier->Contact->ID));
	}
}

$page = new Page(sprintf("<a href=contact_profile.php?cid=%d>%s %s</a> &gt Supplier Settings", $supplier->Contact->ID, $supplier->Contact->Person->Name, $supplier->Contact->Person->LastName), "View the settings of this supplier.");
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Alter settings of supplier');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('action');
echo $form->GetHTML('sid');

echo $window->Open();
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('reference'),$form->GetHTML('reference').$form->GetIcon('reference'));
echo $webForm->AddRow($form->GetLabel('ipaccess'),$form->GetHTML('ipaccess').$form->GetIcon('ipaccess'));
echo $webForm->AddRow($form->GetLabel('iprestrictions'),$form->GetHTML('iprestrictions').$form->GetIcon('iprestrictions'));
echo $webForm->AddRow($form->GetLabel('comparable'),$form->GetHTML('comparable').$form->GetIcon('comparable'));
echo $webForm->AddRow($form->GetLabel('stocker'),$form->GetHTML('stocker').$form->GetIcon('stocker'));
echo $webForm->AddRow($form->GetLabel('drop'),$form->GetHTML('drop').$form->GetIcon('drop'));
echo $webForm->AddRow($form->GetLabel('warehouse'),$form->GetHTML('warehouse').$form->GetIcon('warehouse'));
echo $webForm->AddRow($form->GetLabel('favourite'),$form->GetHTML('favourite').$form->GetIcon('favourite'));
echo $webForm->AddRow($form->GetLabel('bidder'),$form->GetHTML('bidder').$form->GetIcon('bidder'));
echo $webForm->AddRow($form->GetLabel('autoship'),$form->GetHTML('autoship').$form->GetIcon('autoship'));
echo $webForm->AddRow($form->GetLabel('showproduct'),$form->GetHTML('showproduct').$form->GetIcon('showproduct'));
echo $webForm->AddRow($form->GetLabel('minimumpurchase'),$form->GetHTML('minimumpurchase').$form->GetIcon('minimumpurchase'));
echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'contact_profile.php?cid=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $supplier->Contact->ID, $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');