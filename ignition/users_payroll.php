<?php
require_once('lib/common/app_header.php');

if($action == "update"){
	$session->Secure(3);
	update();
	exit();
} else {
	view();
	exit();
}

function update() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Person.php');

	$user = new User($_REQUEST['id']);
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'User ID', 'hidden', $user->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('ispayroll', 'Is Payroll', 'checkbox', $user->IsPayroll, 'boolean', 1, 1, false);
	$form->AddField('iscasualworker', 'Is Casual Worker', 'checkbox', $user->IsCasualWorker, 'boolean', 1, 1, false);
	$form->AddField('hours', 'Hours', 'text', $user->Hours, 'float', 1, 11);
	
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$user->IsPayroll = $form->GetValue('ispayroll');
			$user->IsCasualWorker = $form->GetValue('iscasualworker');
			$user->Hours = $form->GetValue('hours');
			$user->Update();
			$user->Recalculate();

			redirect("Location: ?action=view");
		}
	}

	$page = new Page('Update a User', 'You are about to edit this users information. This may affect other settings for this user. If you are unsureplease contact your administrator.');
	$page->AddToHead('<script language="javascript" src="js/regions.php" type="text/javascript"></script>');
	$page->AddOnLoad("document.getElementById('username').focus();");
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Update User');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();

	echo $window->AddHeader('Please complete the following fields. Required fields are marked with an asterisk (*).');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('Username', $user->Username);
	echo $webForm->AddRow($form->GetLabel('ispayroll'), $form->GetHTML('ispayroll') . $form->GetIcon('ispayroll'));
	echo $webForm->AddRow($form->GetLabel('iscasualworker'), $form->GetHTML('iscasualworker') . $form->GetIcon('iscasualworker'));
	echo $webForm->AddRow($form->GetLabel('hours'), $form->GetHTML('hours') . $form->GetIcon('hours'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=view\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

	$form = new Form($_SERVER['PHP_SELF']);	
	$form->AddField('action', 'Action', 'hidden', 'view', 'alpha', 4, 4);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

	$users = array();
	
	if(isset($_REQUEST['confirm'])) {
		foreach($_REQUEST as $key => $value) {
			if(preg_match('/^select_([0-9]+)$/', $key, $matches)) {
				$users[] = $matches[1];
			}
		}
	}
	
	$scripts = '';
	
	if(!empty($users)) {
		$scripts = sprintf('<script language="javascript" type="text/javascript">
			window.onload = function() {
				popUrl(\'users_print.php?users=%s\', 800, 600);
			}
			</script>', implode(',', $users));
	}
	
	$page = new Page('Users', 'Below is a complete list of users.  You may add, edit or remove users if your access level has read and write permissions for this area.');
	$page->AddToHead($scripts);
	$page->Display('header');
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	
	$table = new DataTable('users');
	$table->SetSQL("SELECT u.*, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS User FROM users AS u INNER JOIN person AS p ON u.Person_ID=p.Person_ID");
	$table->AddField("ID", "User_ID", "right");
	$table->AddField("Is Active", "Is_Active", "center");
	$table->AddField("Username", "User_Name", "left");
	$table->AddField("User", "User", "left");
	$table->AddField("Is Payroll", "Is_Payroll", "center");
	$table->AddField("Is Casual Worker", "Is_Casual_Worker", "center");
	$table->AddField('Hours', 'Hours', 'right');
	$table->AddInput('', 'N', 'Y', 'select', 'User_ID', 'checkbox');
	$table->AddLink("user_timesheet_logs.php?id=%s", "<img src=\"./images/icon_clock_1.gif\" alt=\"Timesheet Log\" border=\"0\">", "User_ID");
	$table->AddLink("user_holiday.php?id=%s", "<img src=\"./images/icon_regions_2.gif\" alt=\"Holidays\" border=\"0\">", "User_ID");
	$table->AddLink("user_documents.php?userid=%s", "<img src=\"./images/icon_view_1.gif\" alt=\"Documents\" border=\"0\">", "User_ID");
	$table->AddLink("?action=update&id=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "User_ID");
	$table->SetMaxRows(25);
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo '<input type="submit" name="print" value="print selected" class="btn" />';

	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}