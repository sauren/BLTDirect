<?php
require_once('lib/common/app_header.php');

if($action == "add"){
	$session->Secure(3);
	add();
	exit;
} elseif($action == "update"){
	$session->Secure(3);
	update();
	exit;
} elseif($action == "remove"){
	$session->Secure(3);
	remove();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Courier.php');
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$courier = new Courier;
		$courier->Delete($_REQUEST['id']);
		redirect("Location: couriers.php");
		exit;
	} else {
		view();
	}
}


function add(){
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Courier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$form = new Form("couriers.php");
$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('courier', 'Courier Name', 'text', '', 'alpha_numeric', 3, 60);
$form->AddField('account', 'Account Ref', 'text', '', 'alpha_numeric', 1, 45, false);
$form->AddField('url', 'Courier Tracking URL', 'textarea', '', 'link', NULL, NULL, false);
$form->AddField('default', 'Is Default Courier', 'checkbox', 'N', 'boolean', 1, 1, false);
$form->AddField('tracking', 'Is Tracking Active', 'checkbox', 'N', 'boolean', 1, 1, false);
		
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$courier = new Courier();
			$courier->Name = $form->GetValue('courier');
			$courier->AccountRef = $form->GetValue('account');
			$courier->URL = $form->GetValue('url');
			$courier->IsDefault = $form->GetValue('default');
			$courier->IsTrackingActive = $form->GetValue('tracking');
			$courier->Add();
			
			redirect("Location: couriers.php");
		}
	}
	
	$page = new Page('Add a New Courier','Please complete the form below.');
	$page->Display('header');
	
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	
	$window = new StandardWindow('Add Courier');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('courier'), $form->GetHTML('courier') . $form->GetIcon('courier'));
	echo $webForm->AddRow($form->GetLabel('account'), $form->GetHTML('account') . $form->GetIcon('account'));
	echo $webForm->AddRow($form->GetLabel('url'), $form->GetHTML('url') . $form->GetIcon('url'));
	echo $webForm->AddRow($form->GetLabel('default'), $form->GetHTML('default') . $form->GetIcon('default'));
	echo $webForm->AddRow($form->GetLabel('tracking'), $form->GetHTML('tracking') . $form->GetIcon('tracking'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'couriers.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');	
}

function update(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Courier.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	
	$courier = new Courier($_REQUEST['id']);
	
	$form = new Form("couriers.php");
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Courier ID', 'hidden', $courier->ID, 'numeric_unsigned', 1, 4);
	$form->AddField('courier', 'Courier Name', 'text', $courier->Name, 'alpha_numeric', 3, 60);
	$form->AddField('account', 'Account Ref', 'text', $courier->AccountRef, 'alpha_numeric', 1, 45, false);
	$form->AddField('url', 'Courier Tracking URL', 'textarea', $courier->URL, 'link', NULL, NULL, false);
	$form->AddField('default', 'Is Default Courier', 'checkbox', $courier->IsDefault, 'boolean', 1, 1, false);
	$form->AddField('tracking', 'Is Tracking Active', 'checkbox', $courier->IsTrackingActive, 'boolean', 1, 1, false);
		
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$courier->Name = $form->GetValue('courier');
			$courier->AccountRef = $form->GetValue('account');
			$courier->URL = $form->GetValue('url');
			$courier->IsDefault = $form->GetValue('default');
			$courier->IsTrackingActive = $form->GetValue('tracking');
			$courier->Update();
			
			redirect("Location: couriers.php");
		}
	}
	
	$page = new Page('Update Courier: ' . $courier->Name,'Please complete the form below.');
	$page->Display('header');
	
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	
	$window = new StandardWindow('Update Courier');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('courier'), $form->GetHTML('courier') . $form->GetIcon('courier'));
	echo $webForm->AddRow($form->GetLabel('account'), $form->GetHTML('account') . $form->GetIcon('account'));
	echo $webForm->AddRow($form->GetLabel('url'), $form->GetHTML('url') . $form->GetIcon('url'));
	echo $webForm->AddRow($form->GetLabel('default'), $form->GetHTML('default') . $form->GetIcon('default'));
	echo $webForm->AddRow($form->GetLabel('tracking'), $form->GetHTML('tracking') . $form->GetIcon('tracking'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'couriers.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	
	$page = new Page('Courier Settings','This area allows you to maintain multiple couriers for your system.');
	$page->Display('header');
	$table = new DataTable('couriers');
	$table->SetSQL("select * from courier");
	$table->AddField('ID#', 'Courier_ID', 'right');
	$table->AddField('Courier', 'Courier_Name', 'left');
	$table->AddField('Account', 'Account_Ref', 'left');
	$table->AddField('Is Default', 'Is_Default', 'center');
	$table->AddField('Is Tracking Active', 'Is_Tracking_Active', 'center');
	$table->AddLink("couriers.php?action=update&id=%s",  "<img src=\"./images/icon_edit_1.gif\" alt=\"Update Settings\" border=\"0\">",  "Courier_ID");
	$table->AddLink("javascript:confirmRequest('couriers.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this courier? IMPORTANT: removing a courier may affect order tracking on existing orders. If you are unsure please contact your administrator.');",  "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",  "Courier_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Courier_Name");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
	
	echo '<br /><input type="button" name="add" value="add a new courier" class="btn" onclick="window.location.href=\'couriers.php?action=add\'">';
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>