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
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Email.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$email = new Email();
		$email->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Email.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('campaign', 'Associated Campaign', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('campaign', '', '');

	$data = new DataQuery(sprintf("SELECT * FROM campaign ORDER BY Title ASC"));
	while($data->Row) {
		$form->AddOption('campaign', $data->Row['Campaign_ID'], $data->Row['Title']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$email = new Email();
			$email->CampaignID = $form->GetValue('campaign');
			$email->Add();

			redirect(sprintf("Location: email_profile.php?id=%d", $email->ID));
		}
	}

	$page = new Page('<a href="emails.php">Emails</a> &gt; Add New Email', 'Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add Email');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('campaign'), $form->GetHTML('campaign') . $form->GetIcon('campaign'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'emails.php\';"> <input type="submit" name="continue" value="continue" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Emails', 'This area allows you to view previously built emails.');
	$page->Display('header');

	$table = new DataTable('emails');
	$table->SetSQL("SELECT e.*, c.Title AS Campaign FROM email AS e LEFT JOIN campaign AS c ON e.CampaignID=c.Campaign_ID");
	$table->AddField('ID#', 'EmailID', 'right');
	$table->AddField('Campaign', 'Campaign', 'left');
	$table->AddField('Created On', 'CreatedOn', 'left');
	$table->AddLink("email_profile.php?id=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open Email Builder\" border=\"0\">", "EmailID");
	$table->AddLink("javascript:confirmRequest('emails.php?action=remove&id=%s','Are you sure you want to remove this email?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "EmailID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("CreatedOn");
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" name="add" value="build new email" class="btn" onclick="window.location.href=\'emails.php?action=add\'">';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}