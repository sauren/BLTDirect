<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Registry.php');

if($action == 'add'){
	$session->Secure(3);
	addRegistry();
	exit;
} elseif($action == 'remove'){
	$session->Secure(3);
	removeRegistry();
	exit;
} elseif($action == 'update'){
	$session->Secure(3);
	updateRegistry();
	exit;
} else {
	$session->Secure(2);
	getRegistry();
	exit;
}

function removeRegistry(){
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == 'true'){
		if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
			$remove = new Registry();
			$remove->delete($_REQUEST['id']);
		}
	}
	$url = sprintf("registry.php?action=view%s", extractVars('action,confirm,id'));

	redirect(sprintf("Location: %s", $url));
}

function updateRegistry(){

	$updateForm = new Registry($_REQUEST['id']);

	$form = new Form("registry.php");
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('file', 'File Name', 'text', $updateForm->scriptFile, 'link_relative', 5, 150);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$name = strrev($form->GetValue('file'));
			$pos = stripos($name, '.');
			$name = ucwords(str_replace('_', ' ', strrev(substr($name, $pos + 1, strlen($name)))));

			$updateForm->scriptFile = $form->GetValue('file');
			$updateForm->scriptName = $name;
			$updateForm->update();
			$url = sprintf("registry.php?action=view%s", extractVars('action,confirm,id'));
			redirect("Location: registry.php");
			exit;
		}
	}

	$page = new Page('Updating a Registry Script','');
	$page->Display('header');

	// Show Error Report if Form Object validation fails
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	$window = new StandardWindow('Update Registry');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('file'), $form->GetHTML('file') . $form->GetIcon('file'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'registry.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function addRegistry(){

	$form = new Form("registry.php");
	$form->AddField('action', '', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('file', 'File Name', 'text', '', 'link_relative', 5, 150);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){

			$name = strrev($form->GetValue('file'));
			$pos = stripos($name, '.');
			$name = ucwords(str_replace('_', ' ', strrev(substr($name, $pos + 1, strlen($name)))));

			$data = new DataQuery(sprintf("SELECT * FROM registry WHERE Script_File LIKE '%s'", mysql_real_escape_string($form->GetValue('file'))));
			if($data->TotalRows == 0) {
				$insertForm = new Registry();
				$insertForm->scriptFile = $form->GetValue('file');
				$insertForm->scriptName = $name;
				$insertForm->add();
			}
			$data->Disconnect();

			redirect("Location: registry.php");
		}
	}

	$page = new Page('Add Script to Registry','Adding a script to the registry allows you to use it in other areas of the system.');
	$page->AddOnLoad("document.getElementById('file').focus();");
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Add Script to Registry');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('file'), $form->GetHTML('file') . $form->GetIcon('file'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'registry.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
}

function getRegistry(){

	$page = new Page('Script Registry','All scripts for Ignition are maintained through the script registry and should be installed into the default ignition directory. The script registry does not currently maintain class or other common function libraries.');
	$page->Display('header');

	$table = new DataTable('registry');
	$table->SetSQL("select * from registry");
	$table->AddField('ID#', 'Registry_ID', 'right');
	$table->AddField('Script Name', 'Script_Name', 'left');
	$table->AddField('File', 'Script_File', 'left');
	$table->SetMaxRows(25);
	$table->SetOrderBy("Script_Name");
	$table->AddLink("registry.php?action=update&id=%s",
	"<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">",
	"Registry_ID");
	$table->AddLink("javascript:confirmRequest('registry.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this script from the registry? Removing this script will also affect access levels.');",
	"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
	"Registry_ID");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo '<input type="button" name="add" value="add new script" class="btn" onclick="window.location.href=\'registry.php?action=add\'">';

	$page->Display('footer');
}
require_once('lib/common/app_footer.php');