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

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/BlacklistUserAgent.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$userAgent = new BlacklistUserAgent();
		$userAgent->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/BlacklistUserAgent.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('agent', 'User Agent', 'text', '', 'anything', 0, 255, true);
	$form->AddField('reason', 'Reason', 'textarea', '', 'anything', 0, 255, false, 'style="width: 300px;" rows="5"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){		
		if($form->Validate()){
			$userAgent = new BlacklistUserAgent();
			$userAgent->UserAgent = $form->GetValue('agent');
			$userAgent->Reason = $form->GetValue('reason');
			$userAgent->Add();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page(sprintf('<a href="%s">User Agent Blacklist</a> &gt; Add User Agent', $_SERVER['PHP_SELF']), 'Add a new user agent to this blacklist.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Adding an User Agent');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Enter an user agent.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('agent'), $form->GetHTML('agent') . $form->GetIcon('agent'));
	echo $webForm->AddRow($form->GetLabel('reason'), $form->GetHTML('reason') . $form->GetIcon('reason'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'blacklist_user_agent.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/BlacklistUserAgent.php');

	if(!isset($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}
	
	$userAgent = new BlacklistUserAgent($_REQUEST['id']);
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', '', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('agent', 'User Agent', 'text', $userAgent->UserAgent, 'anything', 0, 255, true);
	$form->AddField('reason', 'Reason', 'textarea', $userAgent->Reason, 'anything', 0, 255, false, 'style="width: 300px;" rows="5"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){		
		if($form->Validate()){
			$userAgent->UserAgent = $form->GetValue('agent');
			$userAgent->Reason = $form->GetValue('reason');
			$userAgent->Update();
	
			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page(sprintf('<a href="%s">User Agent Blacklist</a> &gt; Update User Agent', $_SERVER['PHP_SELF']), 'Edit an user agent for this blacklist.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Updating an User Agent');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Update an user agent.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('agent'), $form->GetHTML('agent') . $form->GetIcon('agent'));
	echo $webForm->AddRow($form->GetLabel('reason'), $form->GetHTML('reason') . $form->GetIcon('reason'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'blacklist_user_agent.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('User Agent Blacklist', 'Listing all blacklisted user agents.');
	$page->Display('header');

	$table = new DataTable('blacklist');
	$table->SetSQL("SELECT * FROM blacklist_user_agent");
	$table->AddField("ID#", "Blacklist_User_Agent_ID");
	$table->AddField("User Agent", "User_Agent", "left");
	$table->AddField("Reason", "Reason", "left");
	$table->AddField("Blacklist Date", "Created_On", "left");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Blacklist_User_Agent_ID");
	$table->AddLink("blacklist_user_agent.php?action=update&id=%s","<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "Blacklist_User_Agent_ID");
	$table->AddLink("javascript:confirmRequest('blacklist_user_agent.php?action=remove&id=%s','Are you sure you want to remove this item?');","<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Blacklist_User_Agent_ID");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" name="add" value="add new user agent" class="btn" onclick="window.location.href=\'blacklist_user_agent.php?action=add\'">';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>