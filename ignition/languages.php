<?php
require_once('lib/common/app_header.php');

if($action == "add"){
	$session->Secure(3);
	add();
	exit;
} elseif($action == "remove"){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == "update"){
	$session->Secure(3);
	update();
	exit;
} else {
	view();
	exit;
}

function update(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Language.php');

	$lang = new Language($_REQUEST['id']);
	
	$form = new Form("languages.php");
	$form->AddField('action', '', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Language ID', 'hidden', $lang->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Language', 'text', $lang->Name, 'paragraph', 3, 50);
	$form->AddField('code', 'Language Code', 'text', $lang->Code, 'alpha', 2, 2);
	
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$lang->Name = $form->GetValue('name');
			$lang->Code = $form->GetValue('code');
			$lang->Update();
			
			redirect("Location: languages.php");
		}
	}
	
	$page = new Page(
				'Update a Language',
				'You are about to edit this language information. This may affect other settings for this system. If you are unsure please contact your administrator.'
				);
				
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	
	$window = new StandardWindow('Update Language');
	$webForm = new StandardForm;
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Required fields are marked with an asterisk (*).');
	echo $window->OpenContent();
	
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('code'), $form->GetHTML('code') . $form->GetIcon('code'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'languages.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

/*
///////////////////////////////////////
Function:	remove()
Author: 	Geoff Willings
Date:		22 Mar 2005
///////////////////////////////////////
*/
function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Language.php');
	
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == 'true'){
		if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
			$lang = new Language;
			$lang->Remove($_REQUEST['id']);
		}
	}
	$url = sprintf("languages.php?action=view%s", extractVars('action,confirm,id'));
	redirect(sprintf("Location: %s", $url));
}

/*
///////////////////////////////////////
Function:	add()
Author: 	Geoff Willings
Date:		22 Mar 2005
///////////////////////////////////////
*/
function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Language.php');
	
	$form = new Form("languages.php");
	$form->AddField('action', '', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('name', 'Language', 'text', '', 'paragraph', 3, 50);
	$form->AddField('code', 'Language Code', 'text', '', 'alpha', 2, 2);
	
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$lang = new Language;
			$lang->Name = $form->GetValue('name');
			$lang->Code = $form->GetValue('code');
			$lang->Add();
			
			redirect("Location: languages.php");
		}
	}
	
	$page = new Page(
				'Adding a New Language',
				'Please complete the fields below.'
				);
				
	$page->AddOnLoad("document.getElementById('name').focus();");
	$page->Display('header');
	// Show Error Report if Form Object validation fails
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	
	// Continue with usual page
	$window = new StandardWindow('Adding a New Language');
	$webForm = new StandardForm;
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	
	echo $window->AddHeader('Please complete the following fields. Required fields are marked with an asterisk (*).');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('code'), $form->GetHTML('code') . $form->GetIcon('code'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'languages.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
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
Date:		22 Mar 2005
///////////////////////////////////////////
*/
function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	$page = new Page(
				'Languages',
				'Below is a list of languages. These can be assigned to countries to produce available translations.');
	$page->Display('header');
	
	// Initialise DataTable
	$table = new DataTable('lang');
	$table->SetSQL("SELECT * FROM languages");
	$table->AddField("ID", "Language_ID", "right");
	$table->AddField("Name", "Language", "left");
	$table->AddField("Code", "Code", "left");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Language");
	$table->AddLink("languages.php?action=update&id=%s", 
					"<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", 
					"Language_ID");
	$table->AddLink("javascript:confirmRequest('languages.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this item? Note: this may affect other areas of your system. If you are not sure please contact your administrator. Would you like to continue with the removal of this language?');", 
					"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", 
					"Language_ID");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo '<input type="button" name="add" value="add a new language" class="btn" onclick="window.location.href=\'languages.php?action=add\'">';
	$page->Display('footer');
require_once('lib/common/app_footer.php');
}