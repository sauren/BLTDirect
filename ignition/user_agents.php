<?php
require_once('lib/common/app_header.php');

if($action == 'update') {
	$session->Secure(3);
	update();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function update() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserAgent.php');

	if(!isset($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$userAgent = new UserAgent($_REQUEST['id']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', '', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('bot', 'Is Bot', 'checkbox', $userAgent->IsBot, 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()){
			$userAgent->IsBot = $form->GetValue('bot');
			$userAgent->Update();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page(sprintf('<a href="%s">User Agents</a> &gt; Update User Agent', $_SERVER['PHP_SELF']), 'Edit this user agents details.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Updating User Agent');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Update a user agent.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('String', $userAgent->String);
	echo $webForm->AddRow($form->GetLabel('bot'), $form->GetHTML('bot') . $form->GetIcon('bot'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'user_agents.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('User Agents', 'Listing all collected user agents.');
	$page->Display('header');

	$table = new DataTable('agents');
	$table->SetSQL("SELECT * FROM user_agent");
	$table->AddBackgroundCondition('Is_Bot', 'Y', '==', '#FFF499', '#EEE177');
	$table->AddField("ID#", "User_Agent_ID");
	$table->AddField("User Agent", "String", "left");
	$table->AddField("Is Bot", "Is_Bot", "center");
	$table->AddLink("user_agents.php?action=update&id=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Update\" border=\"0\" />", "User_Agent_ID");
	$table->AddLink("stat_sessions.php?agent=%s", "<img src=\"images/icon_search_1.gif\" alt=\"View Sessions\" border=\"0\" />", "User_Agent_ID");
	$table->SetMaxRows(100);
	$table->SetOrderBy("String");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>