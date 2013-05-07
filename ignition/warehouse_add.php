<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');

$session->Secure(3);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'register', 'alpha', 8, 8);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('type','Warehouse Type','select','','alpha',0,10,true,'onChange="propogateChoice(\'typeref\', this);"');
$form->AddOption('type','','');
$form->AddOption('type','B','Branch');
$form->AddOption('type','S','Supplier');
$form->AddField('parent', 'Parent Warehouse', 'select', '0', 'numeric_unsigned', 1, 11);
$form->AddOption('parent', '0', '');
$form->AddField('tracking', 'Next Day Tracking Required', 'checkbox', 'Y', 'boolean', 1, 1, false);

$data = new DataQuery(sprintf("SELECT Warehouse_ID, Warehouse_Name FROM warehouse WHERE Warehouse_ID<>%d", mysql_real_escape_string($wareHouse->ID)));
while($data->Row) {
	$form->AddOption('parent', $data->Row['Warehouse_ID'], $data->Row['Warehouse_Name']);

	$data->Next();
}
$data->Disconnect();

if($form->GetValue('type')=='B'){
	$form->AddField('typeref','Supplier/Branch','select','','alpha_numeric',0,60);
	$form->AddOption('typeref','','Please select');
	$data = new DataQuery("SELECT Branch_Name,Branch_ID FROM branch ORDER BY Branch_Name");
	while($data->Row){
		$form->AddOption('typeref',$data->Row['Branch_ID'],$data->Row['Branch_Name']);
		$data->Next();
	}
	$data->Disconnect();
	unset($data);
}elseif($form->GetValue('type')=='S'){
	$form->AddField('typeref','Supplier/Branch','select','','alpha_numeric',0,60);
	$form->AddOption('typeref','','Please select');
	$data = new DataQuery("SELECT s.Supplier_ID, p.Name_First, p.Name_Last, o.Org_Name FROM supplier s
							INNER JOIN contact c on s.Contact_ID = c.Contact_ID
							INNER JOIN person p on p.Person_ID = c.Person_ID
							LEFT JOIN contact c2 on c2.Contact_ID = c.Parent_Contact_ID
							LEFT JOIN organisation o on c2.Org_ID = o.Org_ID
							ORDER BY Org_Name,Name_First,Name_Last;");
	while($data->Row){
		if(empty($data->Row['Org_Name'])){
		$form->AddOption('typeref',$data->Row['Supplier_Id'],$data->Row['Name_First']." ".$data->Row['Name_Last']);
		$data->Next();
		}else{
		$form->AddOption('typeref',$data->Row['Supplier_ID'],$data->Row['Org_Name']);
		$data->Next();
		}
	}
	$data->Disconnect();
	unset($data);
}else{
	$form->AddField('typeref','Supplier/Branch','select','','alpha_numeric',0,60);
	$form->AddOption('typeref','','Please select');
}
$form->AddField('despatch','Despatch Options','select','B','alpha_numeric',0,2);
$form->AddOption('despatch','B','Email and Print Despatch Note');
$form->AddOption('despatch','E','Email Despatch Note Only');
$form->AddOption('despatch','P','Print Despatch Note Only');
$form->AddOption('despatch','N','Do nothing with the despatch note');

$form->AddField('invoice','Invoice Options','select','E','alpha_numeric',0,2);
$form->AddOption('invoice','B','Email and Print Invoice');
$form->AddOption('invoice','E','Email Invoice Only');
$form->AddOption('invoice','P','Print Invoice Only');
$form->AddOption('invoice','N','Do nothing with the Invoice ');

$form->AddField('purchase','Purchase Options','select','B','alpha_numeric',0,2);
$form->AddOption('purchase','B','Email and Print Purchase Order');
$form->AddOption('purchase','E','Email Purchase Order Only');
$form->AddOption('purchase','P','Print Purchase Order Only');
$form->AddOption('purchase','N','Do nothing with the Purchase Order');

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
	$form->Validate();
	$wareHouse = new Warehouse;
	$wareHouse->Despatch = $form->GetValue('despatch');
	$wareHouse->Invoice = $form->GetValue('invoice');
	$wareHouse->Purchase = $form->GetValue('purchase');
	$wareHouse->Type = $form->GetValue('type');
	$wareHouse->IsNextDayTrackingRequired = $form->GetValue('tracking');
	
	if($wareHouse->Type == 'B'){
		$wareHouse->Contact = new Branch;
		$data = new DataQuery(sprintf("SELECT Branch_Name,Branch_ID FROM branch WHERE Branch_ID=%d",mysql_real_escape_string($form->GetValue('typeref'))));
		$wareHouse->Name = $data->Row['Branch_Name'];
		$data->Disconnect();
		unset($data);
	}
	else{
		$wareHouse->Contact = new Supplier;
		$data = new DataQuery(sprintf("SELECT p.Name_First, p.Name_Last, o.Org_Name FROM supplier s
							INNER JOIN contact c on s.Contact_ID = c.Contact_ID
							INNER JOIN person p on p.Person_ID = c.Person_ID
							LEFT JOIN contact c2 on c2.Contact_ID = c.Parent_Contact_ID
							LEFT JOIN organisation o on c2.Org_ID = o.Org_ID
							WHERE s.Supplier_ID = %d;",mysql_real_escape_string($form->GetValue('typeref'))));
		if(empty($data->Row['Org_Name'])){
			$wareHouse->Name = $data->Row['Name_First']." ".$data->Row['Name_Last'];
		}
		else{
			$wareHouse->Name = $data->Row['Org_Name'];
		}
		$data->Disconnect();
		unset($data);
	}
	$wareHouse->Contact->ID = $form->GetValue('typeref');
	if($wareHouse->alreadyWarehouse()){
		$form->AddError('This Branch/Supplier is already a warehouse', 'typeref');
			$emailError = "<span class=\"alert\">This Branch/Supplier is already a warehouse</span>";
	}
	if($form->Valid){
		$wareHouse->Add();
		redirect("Location: warehouse_view.php");
	}
}

$page = new Page('New warehouse','Here you can add the details of a warehouse of the company');
$page->LinkScript('js/suppliers_branches.php');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo "<br />";
}

$window = new StandardWindow('Please change your details');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('action');
echo $window->Open();
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('type'),$form->GetHTML('type').$form->GetIcon('type'));
echo $webForm->AddRow($form->GetLabel('typeref'),$form->GetHTML('typeref').$form->GetIcon('typeref'));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->AddHeader('The following options determine the default print and email options for Despatch notes, Invoices and Purchase orders for this warehouse whenever a despatch is made. Please note that the Purchase order options will only affect warehouses belonging to suppliers');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('parent'),$form->GetHTML('parent').$form->GetIcon('parent'));
echo $webForm->AddRow($form->GetLabel('despatch'),$form->GetHTML('despatch').$form->GetIcon('despatch'));
echo $webForm->AddRow($form->GetLabel('invoice'),$form->GetHTML('invoice').$form->GetIcon('invoice'));
echo $webForm->AddRow($form->GetLabel('purchase'),$form->GetHTML('purchase').$form->GetIcon('purchase'));
echo $webForm->AddRow($form->GetLabel('tracking'), $form->GetHTML('tracking').$form->GetIcon('tracking'));
echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'warehouse_view.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();
echo "<br>";
$page->Display('footer');
?>