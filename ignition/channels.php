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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Channel.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$channel = new Channel();
		$channel->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Channel.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('name', 'Name', 'text', '', 'anything', 0, 255);
	$form->AddField('domain', 'Domain', 'text', '', 'anything', 0, 255);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if($form->Validate()){
			$channel = new Channel();
			$channel->Name = $form->GetValue('name');
			$channel->Domain = $form->GetValue('domain');
			$channel->Add();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page(sprintf('<a href="%s">Channels</a> &gt; Add Channel', $_SERVER['PHP_SELF']), 'Add a new channel.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Adding a channel');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Enter channel details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('domain'), $form->GetHTML('domain') . $form->GetIcon('domain'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'channels.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Channel.php');

	if(!isset($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$channel = new Channel($_REQUEST['id']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', '', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', $channel->Name, 'anything', 0, 255);
	$form->AddField('domain', 'Domain', 'text', $channel->Domain, 'anything', 0, 255);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$channel->Name = $form->GetValue('name');
			$channel->Domain = $form->GetValue('domain');
			$channel->Update();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page(sprintf('<a href="%s">Channels</a> &gt; Update Channel', $_SERVER['PHP_SELF']), 'Edit a channel.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Updating a channel');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Update channel details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('domain'), $form->GetHTML('domain') . $form->GetIcon('domain'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'channels.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Channels', 'Listing all channels.');
	$page->Display('header');

	$table = new DataTable('channels');
	$table->SetSQL("SELECT * FROM channel");
	$table->AddField("ID#", "Channel_ID");
	$table->AddField("Channel", "Name", "left");
	$table->AddField("Domain", "Domain", "left");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Name");
	$table->AddLink("channels.php?action=update&id=%s","<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "Channel_ID");
	$table->AddLink("javascript:confirmRequest('channels.php?action=remove&id=%s','Are you sure you want to remove this item?');","<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Channel_ID");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" name="add" value="add new channel" class="btn" onclick="window.location.href=\'channels.php?action=add\'">';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>