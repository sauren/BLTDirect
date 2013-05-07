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
/*
///////////////////////////////////////////
Function:	remove()
Author:		Geoff Willings
Date:		08 Feb 2005
///////////////////////////////////////////
*/
function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Manufacturer.php');
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$manufacturer = new Manufacturer;
		$manufacturer->ID = $_REQUEST['id'];
		$manufacturer->Remove();

		redirect("Location: manufacturers.php");
	} else {
		viewCurrencies();
	}
}
/*
///////////////////////////////////////////
Function:	add()
Author:		Geoff Willings
Date:		08 Feb 2005
///////////////////////////////////////////
*/
function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Manufacturer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form("manufacturers.php");
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('manufacturer', 'Manufacturer Name', 'text', '', 'alpha_numeric', 1, 32);
	$form->AddField('url', 'Manufacturer\'s Website', 'text', 'http://', 'link', 3, 255);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$manufacturer = new Manufacturer;
			$manufacturer->Name = $form->GetValue('manufacturer');
			$manufacturer->URL = $form->GetValue('url');
			$manufacturer->Add();
			
			redirect("Location: manufacturers.php");
		}
	}

	$page = new Page('Add a New Manufacturer','Please complete the form below.');
	$page->Display('header');

	// Show Error Report if Form Object validation fails
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Add Manufacturer');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('manufacturer'), $form->GetHTML('manufacturer') . $form->GetIcon('manufacturer'));
	echo $webForm->AddRow($form->GetLabel('url'), $form->GetHTML('url') . $form->GetIcon('url'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'manufacturers.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
require_once('lib/common/app_footer.php');
}
/*
///////////////////////////////////////////
Function:	update()
Author:		Geoff Willings
Date:		08 Feb 2005
///////////////////////////////////////////
*/
function update(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Manufacturer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$manufacturer = new Manufacturer($_REQUEST['id']);

	$form = new Form("manufacturers.php");
	$form->AddField('action', 'Action', 'hidden', 'update', 'update', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'ID', 'hidden', $manufacturer->ID, 'nuermic_unsigned', 1, 11);
	$form->AddField('manufacturer', 'Manufacturer Name', 'text', $manufacturer->Name, 'alpha_numeric', 1, 32);
	$form->AddField('url', 'Manufacturer\'s Website', 'text', $manufacturer->URL, 'link', 3, 255);

	// Check if the form has been submitted
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			// Hurrah! Create a new entry.
			$manufacturer->Name = $form->GetValue('manufacturer');
			$manufacturer->URL = $form->GetValue('url');
			$manufacturer->Update();
			
			redirect("Location: manufacturers.php");
		}
	}

	$page = new Page('Update Manufacturer','Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Add Manufacturer');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('manufacturer'), $form->GetHTML('manufacturer') . $form->GetIcon('manufacturer'));
	echo $webForm->AddRow($form->GetLabel('url'), $form->GetHTML('url') . $form->GetIcon('url'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'manufacturers.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
/*
///////////////////////////////////////////
Function:	view()
Author:		Geoff Willings
Date:		08 Feb 2005
///////////////////////////////////////////
*/
function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');

	$page = new Page('Manufacturer Settings','This area allows you to maintain multiple manufacturers for your online shop and products.');
	$page->Display('header');

	$table = new DataTable('manf');
	$table->SetSQL("select * from manufacturer");
	$table->AddField('ID#', 'Manufacturer_ID', 'right');
	$table->AddField('Manufacturer', 'Manufacturer_Name', 'left');
	$table->AddLink("manufacturers.php?action=update&id=%s",
					"<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">",
					"Manufacturer_ID");
	$table->AddLink("javascript:confirmRequest('manufacturers.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this Manufacturer? IMPORTANT: removing a manufacturer may affect some product profiles and other areas of your site.');",
					"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
					"Manufacturer_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Manufacturer_Name");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo '<input type="button" name="add" value="add a new manufacturer" class="btn" onclick="window.location.href=\'manufacturers.php?action=add\'">';
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}