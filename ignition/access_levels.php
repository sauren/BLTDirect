<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/AccessLevel.php');

if($action == 'add'){
	$session->Secure(3);
	addLevels();
	exit;
} elseif($action == 'remove'){
	$session->Secure(3);
	removeLevels();
	exit;
} elseif($action == 'update'){
	$session->Secure(3);
	updateLevels();
	exit;
} else {
	$session->Secure(2);
	getLevels();
	exit;
}

function removeLevels(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == 'true'){
		if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
			$remove = new AccessLevel();
			$remove->delete($_REQUEST['id']);
		}
	}
	$url = sprintf("access_levels.php?action=view%s", extractVars('action,confirm,id'));
	redirect(sprintf("Location: %s", $url));
}

function addLevels(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	// Define a new form
	$form = new Form("access_levels.php");
	$form->AddField('action', '', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('level', 'Level Name', 'text', '', 'alpha_numeric', 3, 100);

	// Check if the form has been submitted
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			// Hurrah! Create a new entry.
			$insertForm = new AccessLevel();
			$insertForm->accessLevel = $form->GetValue('level');
			$insertForm->add();
			
			redirect("Location: access_levels.php");
		}
	}

	// Continue if invalid to catch errors
	$page = new Page(
	'Adding an Access Level',
	'Adding a new access level allows you to control how users of your system access content
					and functionality. For instance, you may not want telesales staff to have write permissions
					on your product profiles. Once you have added an Access Level you will be able to start adding pages
					and permissions to this level.'
	);

	$page->AddOnLoad("document.getElementById('level').focus();");
	$page->Display('header');
	// Show Error Report if Form Object validation fails
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	// Continue with usual page
	$window = new StandardWindow('Adding an Access Level');

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Please supply a unique name for your access level below. Access level names should indicate the general operations of its uers e.g. Telesales.');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('level'), $form->GetHTML('level') . $form->GetIcon('level'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'access_levels.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function updateLevels(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$level = new AccessLevel($_REQUEST['id']);
	
	// Define a new form
	$form = new Form("access_levels.php");
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Access ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('level', 'Level Name', 'text', $level->accessLevel, 'alpha_numeric', 3, 100);

	// Check if the form has been submitted
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			// Hurrah! Create a new entry.
			$level->accessLevel = $form->GetValue('level');
			$level->update();
			
			redirect("Location: access_levels.php");
		}
	}

	// Continue if invalid to catch errors
	$page = new Page(
	'Updating an Access Level',
	'Updating the name of an access level will not damage any of your user\'s profiles or their sessions. They will only
					notice a change in their Access Level name.'
	);

	$page->Display('header');
	// Show Error Report if Form Object validation fails
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	// Continue with usual page
	$window = new StandardWindow('Update Access Level ' . $level->accessLevel);
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Please supply a unique name for your access level below. Access level names should indicate the general operations of its uers e.g. Telesales.');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('level'), $form->GetHTML('level') . $form->GetIcon('level'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'access_levels.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function getLevels(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	$page = new Page(
	'Ignition Access Levels',
	'Add, Edit, and Remove Access levels and their associated page
					permissions. Note: Changing access levels may affect your existing
					Ignition users.');
	$page->Display('header');

	// Initialise DataTable
	$table = new DataTable('test');
	$table->SetSQL("SELECT * FROM access_levels");
	$table->AddField("ID", "Access_ID", "right");
	$table->AddField("Access Level", "Access_Level", "left");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Access_Level");
	$table->AddLink("permissions.php?action=view&level=%s",
	"<img src=\"./images/icon_view_2.gif\" alt=\"View Permissions for this Access Level\" border=\"0\">",
	"Access_ID");
	$table->AddLink("access_levels.php?action=update&id=%s",
	"<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">",
	"Access_ID");
	$table->AddLink("javascript:confirmRequest('access_levels.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this item?');",
	"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
	"Access_ID");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo '<input type="button" name="add" value="add new level" class="btn" onclick="window.location.href=\'access_levels.php?action=add\'">';
	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}
?>