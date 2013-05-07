<?php
require_once('lib/common/app_header.php');

if($action == 'add'){
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'update'){
	$session->Secure(3);
	update();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Website.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$website = new Website();
		$website->Delete($_REQUEST['id']);
	}
	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Website.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('domain', 'Domain', 'text', '', 'anything', 0, 255);
	$form->AddField('channel', 'Channel', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('channel', '', '');

	$data = new DataQuery(sprintf("SELECT * FROM channel ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('channel', $data->Row['Channel_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if($form->Validate()){
			$website = new Website();
			$website->Channel->ID = $form->GetValue('channel');
			$website->Domain = $form->GetValue('domain');
			$website->Add();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page(sprintf('<a href="%s">Webistes</a> &gt; Add Webiste', $_SERVER['PHP_SELF']), 'Add a new website.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Adding a website');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Enter website details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('channel'), $form->GetHTML('channel') . $form->GetIcon('channel'));
	echo $webForm->AddRow($form->GetLabel('domain'), $form->GetHTML('domain') . $form->GetIcon('domain'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'websites.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Website.php');

	if(!isset($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$website = new Website($_REQUEST['id']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', '', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('domain', 'Domain', 'text', $website->Domain, 'anything', 0, 255);
	$form->AddField('channel', 'Channel', 'select', $website->Channel->ID, 'numeric_unsigned', 1, 11);
	$form->AddOption('channel', '', '');

	$data = new DataQuery(sprintf("SELECT * FROM channel ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('channel', $data->Row['Channel_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$website->Channel->ID = $form->GetValue('channel');
			$website->Domain = $form->GetValue('domain');
			$website->Update();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page(sprintf('<a href="%s">Websites</a> &gt; Update Website', $_SERVER['PHP_SELF']), 'Edit a website.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Updating a website');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Update website details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('channel'), $form->GetHTML('channel') . $form->GetIcon('channel'));
	echo $webForm->AddRow($form->GetLabel('domain'), $form->GetHTML('domain') . $form->GetIcon('domain'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'websites.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Websites', 'Listing all websites.');
	$page->Display('header');

	$table = new DataTable('websites');
	$table->SetSQL("SELECT w.*, c.Name FROM website AS w INNER JOIN channel AS c ON c.Channel_ID=w.Channel_ID");
	$table->AddField("ID#", "Website_ID");
	$table->AddField("Channel", "Name", "left");
	$table->AddField("Website", "Domain", "left");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Name");
	$table->AddLink("websites.php?action=update&id=%s","<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "Website_ID");
	$table->AddLink("javascript:confirmRequest('websites.php?action=remove&id=%s','Are you sure you want to remove this item?');","<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Website_ID");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" name="add" value="add new website" class="btn" onclick="window.location.href=\'websites.php?action=add\'">';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>