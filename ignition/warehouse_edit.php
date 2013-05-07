<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');

$session->Secure(3);

$wareHouse = new Warehouse($_REQUEST['wid']);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'register', 'alpha', 8, 8);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('wid', 'wid', 'hidden', 'true', 'numeric_unsigned', 0, 11);
$form->AddField('parent', 'Parent Warehouse', 'select', $wareHouse->ParentID, 'numeric_unsigned', 1, 11);
$form->AddOption('parent', '0', '');
$form->AddField('tracking', 'Next Day Tracking Required', 'checkbox', $wareHouse->IsNextDayTrackingRequired, 'boolean', 1, 1, false);

$data = new DataQuery(sprintf("SELECT Warehouse_ID, Warehouse_Name FROM warehouse WHERE Warehouse_ID<>%d", mysql_real_escape_string($wareHouse->ID)));
while($data->Row) {
	$form->AddOption('parent', $data->Row['Warehouse_ID'], $data->Row['Warehouse_Name']);

	$data->Next();
}
$data->Disconnect();

$form->AddField('despatch','Despatch Options','select',$wareHouse->Despatch,'alpha_numeric',0,2);
$form->AddOption('despatch','B','Email and Print Despatch Note');
$form->AddOption('despatch','E','Email Despatch Note Only');
$form->AddOption('despatch','P','Print Despatch Note Only');
$form->AddOption('despatch','N','Do nothing with the despatch note');
$form->AddField('invoice','Invoice Options','select',$wareHouse->Invoice,'alpha_numeric',0,2);
$form->AddOption('invoice','B','Email and Print Invoice');
$form->AddOption('invoice','E','Email Invoice Only');
$form->AddOption('invoice','P','Print Invoice Only');
$form->AddOption('invoice','N','Do nothing with the Invoice ');
$form->AddField('purchase','Purchase Options','select',$wareHouse->Purchase,'alpha_numeric',0,2);
$form->AddOption('purchase','B','Email and Print Purchase Order');
$form->AddOption('purchase','E','Email Purchase Order Only');
$form->AddOption('purchase','P','Print Purchase Order Only');
$form->AddOption('purchase','N','Do nothing with the Purchase Order');

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
	if($form->Validate()) {
		$wareHouse->ParentID = $form->GetValue('parent');
		$wareHouse->Despatch = $form->GetValue('despatch');
		$wareHouse->Invoice = $form->GetValue('invoice');
		$wareHouse->Purchase = $form->GetValue('purchase');
		$wareHouse->IsNextDayTrackingRequired = $form->GetValue('tracking');
		$wareHouse->Update();
		
		redirect("Location: warehouse_view.php");
	}
}

$page = new Page('New warehouse','Here you can add the details of a warehouse of the company');
$page->LinkScript('js/suppliers_branches.php');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Edit warehouse details');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('action');
echo $form->GetHTML('wid');
echo $window->Open();
echo $window->AddHeader('The following options determine the default print and email options for Despatch notes, Invoices and Purchase orders for this warehouse whenever a despatch is made. Please note that the Purchase order options will only affect warehouses belonging to suppliers');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('parent'),$form->GetHTML('parent').$form->GetIcon('parent'));
echo $webForm->AddRow($form->GetLabel('despatch'),$form->GetHTML('despatch').$form->GetIcon('despatch'));
echo $webForm->AddRow($form->GetLabel('invoice'),$form->GetHTML('invoice').$form->GetIcon('invoice'));
echo $webForm->AddRow($form->GetLabel('purchase'),$form->GetHTML('purchase').$form->GetIcon('purchase'));
echo $webForm->AddRow($form->GetLabel('tracking'), $form->GetHTML('tracking').$form->GetIcon('tracking'));
echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'warehouse_view.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

$page->Display('footer');
?>