<?php
require_once ('lib/common/app_header.php');

if ($action == 'add') {
	$session->Secure(3);
	add();
	exit();
} elseif ($action == 'remove') {
	$session->Secure(3);
	remove();
	exit();
} elseif ($action == 'update') {
	$session->Secure(3);
	update();
	exit();
} else {
	$session->Secure(2);
	view();
	exit();
}

function remove() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactStatus.php');
	
	if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$status = new ContactStatus();
		$status->Delete($_REQUEST['id']);
		
		$data = new DataQuery(sprintf("UPDATE contact SET Contact_Status_ID=0 WHERE Contact_Status_ID=%d", mysql_real_escape_string($_REQUEST['id'])));
		$data->Disconnect();
	}
	
	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactStatus.php');
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('name', 'Status', 'text', '', 'anything', 1, 128, true);
	
	if (isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if ($form->Validate()) {
			$status = new ContactStatus();
			$status->Name = $form->GetValue('name');
			$status->Add();
			
			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}
	
	$page = new Page(sprintf('<a href="%s">Status Types</a> &gt; Add Type', $_SERVER['PHP_SELF']), 'Add a new contact status type here.');
	$page->AddOnLoad("document.getElementById('name').focus();");
	$page->Display('header');
	
	if (!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}
	
	$window = new StandardWindow('Adding a Status Type');
	$webForm = new StandardForm();
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Enter a status type.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'contact_status.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	
	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}

function update() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactStatus.php');
	
	$status = new ContactStatus($_REQUEST['id']);
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', '', 'hidden', $status->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Status', 'text', $status->Status, 'anything', 1, 128, true);
	
	if (isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if ($form->Validate()) {
			$status->Name = $form->GetValue('name');
			$status->Update();
			
			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}
	
	$page = new Page(sprintf('<a href="%s">Status Types</a> &gt; Edit Type', $_SERVER['PHP_SELF']), 'Edit this contact status type here.');
	$page->AddOnLoad("document.getElementById('name').focus();");
	$page->Display('header');
	
	if (!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}
	
	$window = new StandardWindow('Editing a Status Type');
	$webForm = new StandardForm();
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Enter a status type.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'contact_status.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	
	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}

function view() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	
	$page = new Page('Status Types', 'Listing all available contact status types.');
	$page->Display('header');
	
	$table = new DataTable('types');
	$table->SetSQL("SELECT * FROM contact_status");
	$table->AddField("ID#", "Contact_Status_ID");
	$table->AddField("Type", "Name", "left");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Name");
	$table->AddLink("contact_status.php?action=update&id=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "Contact_Status_ID");
	$table->AddLink("javascript:confirmRequest('contact_status.php?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Contact_Status_ID");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo '<input type="button" name="add" value="add new type" class="btn" onclick="window.location.href=\'contact_status.php?action=add\'">';
	
	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}
?>