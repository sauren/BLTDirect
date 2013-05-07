<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Password.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Template.php');

if($action == "add"){
	$session->Secure(3);
	add();
	exit();
} elseif($action == "remove"){
	$session->Secure(3);
	remove();
	exit();
} elseif($action == "update"){
	$session->Secure(3);
	update();
	exit();
} elseif($action == "regeneratepassword"){
	$session->Secure(3);
	regeneratePassword();
	exit();
} elseif($action == "unlock"){
	$session->Secure(3);
	unlock();
	exit();
} else {
	$session->Secure(2);
	view();
	exit();
}

function regeneratePassword() {
	$user = new User();

	if(isset($_REQUEST['uid']) && $user->Get($_REQUEST['uid'])) {
		$user->RegeneratePassword();
	}

	redirect('Location: users.php');
}

function unlock() {
	$user = new User();

	if(isset($_REQUEST['id']) && $user->Get($_REQUEST['id'])) {
		$user->IsLocked = 'N';
		$user->FailedLogins = 0;
		$user->Update();
	}

	redirect('Location: users.php');
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
	$form->AddField('username', 'E-mail Address', 'text', $user->Username, 'username', 1, 100);
	$form->AddField('old_password', 'Old Password', 'password', '', 'password', 6, 100, false);
	$form->AddField('new_password', 'New Password', 'password', '', 'password', PASSWORD_LENGTH_USER, 100, false);
	$form->AddField('con_password', 'Confirm New Password', 'password', '', 'password', PASSWORD_LENGTH_USER, 100, false);
	$form->AddField('is_active', 'Active', 'checkbox', $user->IsActive, 'boolean', 1, 1, false);
	$form->AddField('secretquestion', 'Secret Question', 'text', $user->SecretQuestion, 'anything', 0, 255, false);
	$form->AddField('secretanswer', 'Secret Answer', 'text', $user->SecretAnswer, 'anything', 0, 255, false);
	$form->AddField('title', 'Title', 'select', $user->Person->Title, 'alpha', 1, 4);
	$form->AddField('fname', 'First Name', 'text', $user->Person->Name, 'alpha_numeric', 1, 60);
	$form->AddField('iname', 'Initial', 'text', $user->Person->Initial, 'alpha', 1, 1, false, 'size="1"');
	$form->AddField('lname', 'Last Name', 'text', $user->Person->LastName, 'alpha_numeric', 1, 60);
	$form->AddField('phone', 'Daytime Phone', 'text', $user->Person->Phone1, 'telephone', NULL, NULL, false);
	$form->AddField('mobile', 'Mobile Phone', 'text', $user->Person->Mobile, 'telephone', NULL, NULL, false);
	$form->AddField('address1', 'Property Name/Number', 'text', $user->Person->Address->Line1, 'alpha_numeric', 1, 150, false);
	$form->AddField('address2', 'Street', 'text', $user->Person->Address->Line2, 'alpha_numeric', 1, 150, false);
	$form->AddField('address3', 'Area', 'text', $user->Person->Address->Line3, 'alpha_numeric', 1, 150, false);
	$form->AddField('city', 'City', 'text', $user->Person->Address->City, 'alpha_numeric', 1, 150, false);
	$form->AddField('country', 'Country', 'select', $user->Person->Address->Country->ID, 'numeric_unsigned', 1, 11, false, 'onchange="propogateRegions(\'region\', this);"');
	$form->AddField('postcode', 'Postcode', 'text', $user->Person->Address->Zip, 'alpha_numeric', 1, 10, false);
	$form->AddField('ipaccess', 'IP Access', 'text', $user->IP->Access, 'anything', 1, 2000, true);
	$form->AddField('iprestrictions', 'IP Restrictions', 'text', $user->IP->Restrictions, 'anything', 1, 2000, false);
	$form->AddField('branch','Branch','select',$user->Branch->ID,'alpha_numeric',1,60,false);

	$data = new DataQuery("SELECT * FROM person_title ORDER BY Person_Title");
	while ($data->Row) {
		$form->AddOption('title', $data->Row['Person_Title'], $data->Row['Person_Title']);
		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery("SELECT * FROM countries ORDER BY Country ASC");
	$form->AddOption('country', '', '');
	while ($data->Row) {
		$form->AddOption('country', $data->Row['Country_ID'], $data->Row['Country']);
		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT Region_ID, Region_Name FROM regions where Country_ID=%d ORDER BY Region_Name ASC", mysql_real_escape_string($form->GetValue('country'))));

	$regionCount = (isset($data->TotalRows) && !is_null($data->TotalRows)) ? $data->TotalRows : 0;

	if ($regionCount > 0) {
		$form->AddField('region', 'Region', 'select', $user->Person->Address->Region->ID, 'numeric_unsigned', 1, 11, false);
		$form->AddOption('region', '0', '');

		while ($data->Row) {
			$form->AddOption('region', $data->Row['Region_ID'], $data->Row['Region_Name']);
			$data->Next();
		}
	} else {
		$form->AddField('region', 'Region', 'select', $user->Person->Address->Region->ID, 'numeric_unsigned', 1, 11, false, 'disabled="disabled"');
		$form->AddOption('region', '0', '');
	}

	$levelData = new DataQuery("select * from access_levels");
	$levels = array();
	do {
		$levels[$levelData->Row['Access_ID']] = $levelData->Row['Access_Level'];
		$form->AddField('level' . $levelData->Row['Access_ID'], $levelData->Row['Access_Level'], 'checkbox', $user->HasAccess($levelData->Row['Access_ID']) ? 'Y' : 'N', 'boolean', NULL, NULL, false);
		$levelData->Next();
	} while ($levelData->Row);
	$levelData->Disconnect();

	$branch = new DataQuery("SELECT * FROM branch");
	$form->AddOption('branch',0,"Default (HQ)");
	while ($branch->Row) {
		$form->AddOption('branch',$branch->Row['Branch_ID'],$branch->Row['Branch_Name']);
		$branch->Next();
	}
	$branch->Disconnect();
	
	$form->AddField('ispacker', 'Is Packer', 'checkbox', $user->IsPacker, 'boolean', 1, 1, false);
	$form->AddField('ispayroll', 'Is Payroll', 'checkbox', $user->IsPayroll, 'boolean', 1, 1, false);
	$form->AddField('iscasualworker', 'Is Casual Worker', 'checkbox', $user->IsCasualWorker, 'boolean', 1, 1, false);
	$form->AddField('hours', 'Hours', 'text', $user->Hours, 'float', 1, 11);
	$form->AddField('timesheetdescription', 'Timesheet Description', 'checkbox', $user->RequireTimesheetDescription, 'boolean', 1, 1, false);
	$form->AddField('showsessions', 'Show Sessions', 'checkbox', $user->ShowSessions, 'boolean', 1, 1, false);
	$form->AddField('canbypassworktasks', 'Can Bypass Work Tasks', 'checkbox', $user->CanBypassWorkTasks, 'boolean', 1, 1, false);
	$form->AddField('secondarymailbox', 'Secondary Mailbox', 'text', $user->SecondaryMailbox, 'username', 1, 120, false);
	$form->AddField('secondarymailboxpassword', 'Secondary Mailbox Password', 'password', $user->GetSecondaryMailboxPassword(), 'password', 1, 120, false);
	
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		$oldPassword = $form->GetValue('old_password');
		$newPassword = $form->GetValue('new_password');
		$conPassword = $form->GetValue('con_password');
	
		if($form->Validate()){
			if(!empty($oldPassword)){
				if(empty($newPassword)){
					$form->AddError('Please set the new password', 'new_password');
				}
				if(empty($conPassword)){
					$form->AddError('Please confirm the new password', 'con_password');
				}
				if($conPassword != $newPassword){
					$form->AddError('The new password and the confirmation password do not match.');
				}
				if(md5($oldPassword) != $user->Password) {
					$form->AddError('The old password was not correct.');
				}
			}
			
			if($form->Valid){
				if(!empty($oldPassword)){
					$user->SetPassword($newPassword);
				}
			
				$user->IsActive = $form->GetValue('is_active');
				$user->SecretQuestion = $form->GetValue('secretquestion');
				$user->SecretAnswer = $form->GetValue('secretanswer');
				$user->Username = $form->GetValue('username');
				$user->IsPacker = $form->GetValue('ispacker');
				$user->IsPayroll = $form->GetValue('ispayroll');
				$user->IsCasualWorker = $form->GetValue('iscasualworker');
				$user->Hours = $form->GetValue('hours');
				$user->RequireTimesheetDescription = $form->GetValue('timesheetdescription');
				$user->ShowSessions = $form->GetValue('showsessions');
				$user->CanBypassWorkTasks = $form->GetValue('canbypassworktasks');
				$user->Branch->ID = $form->GetValue('branch');
				$user->IP->Access = $form->GetValue('ipaccess');
				$user->IP->Restrictions = $form->GetValue('iprestrictions');
				$user->Person->Title = $form->GetValue('title');
				$user->Person->Name = $form->GetValue('fname');
				$user->Person->LastName = $form->GetValue('lname');
				$user->Person->Initial = $form->GetValue('iname');
				$user->Person->Phone = $form->GetValue('phone');
				$user->Person->Mobile = $form->GetValue('mobile');
				$user->Person->Email = $form->GetValue('username');
				$user->Person->Address->Line1 = $form->GetValue('address1');
				$user->Person->Address->Line2 = $form->GetValue('address2');
				$user->Person->Address->Line3 = $form->GetValue('address3');
				$user->Person->Address->City = $form->GetValue('city');
				$user->Person->Address->Country->ID = $form->GetValue('country');
				$user->Person->Address->Region->ID = $form->GetValue('region');
				$user->Person->Address->Zip = $form->GetValue('postcode');
				$user->Person->Update();
				$user->SecondaryMailbox = $form->GetValue('secondarymailbox');
				$user->SetSecondaryMailboxPassword($form->GetValue('secondarymailboxpassword'));
				$user->Update();
				$user->Recalculate();
				
				$levelVals = array();
				foreach ($levels as $accessId=>$level) {
					$levelVals[$accessId] = $form->GetValue('level' . $accessId);
				}
				$user->SetAccessLevels($levelVals);
	
				redirect("Location: users.php");
			}
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
	echo $webForm->AddRow($form->GetLabel('username'), $form->GetHTML('username') . $form->GetIcon('username'));
	echo $webForm->AddRow($form->GetLabel('is_active'), $form->GetHTML('is_active') . $form->GetIcon('is_active'));
	echo $webForm->AddRow($form->GetLabel('branch'),$form->GetHTML('branch').$form->GetIcon('branch'));
	echo $webForm->AddRow($form->GetLabel('ipaccess'),$form->GetHTML('ipaccess').$form->GetIcon('ipaccess'));
	echo $webForm->AddRow($form->GetLabel('iprestrictions'),$form->GetHTML('iprestrictions').$form->GetIcon('iprestrictions'));
	echo $webForm->AddRow('Note:', 'Comma seperate IP addresses for multiple, single, IP entity or hypenate IP addresses for ranges.<br />Example: 10.0.0.1, 10.0.0.2, 10.0.0.5-10.0.0.10');
	echo $webForm->AddRow($form->GetLabel('ispacker'), $form->GetHTML('ispacker') . $form->GetIcon('ispacker'));
	echo $webForm->AddRow($form->GetLabel('ispayroll'), $form->GetHTML('ispayroll') . $form->GetIcon('ispayroll'));
	echo $webForm->AddRow($form->GetLabel('iscasualworker'), $form->GetHTML('iscasualworker') . $form->GetIcon('iscasualworker'));
	echo $webForm->AddRow($form->GetLabel('hours'), $form->GetHTML('hours') . $form->GetIcon('hours'));
	echo $webForm->AddRow($form->GetLabel('timesheetdescription'), $form->GetHTML('timesheetdescription') . $form->GetIcon('timesheetdescription'));
	echo $webForm->AddRow($form->GetLabel('showsessions'), $form->GetHTML('showsessions') . $form->GetIcon('showsessions'));
	echo $webForm->AddRow($form->GetLabel('canbypassworktasks'), $form->GetHTML('canbypassworktasks') . $form->GetIcon('canbypassworktasks'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Change password (optional)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('old_password'), $form->GetHTML('old_password') . $form->GetIcon('old_password'));
	echo $webForm->AddRow($form->GetLabel('new_password'), $form->GetHTML('new_password') . $form->GetIcon('new_password'));
	echo $webForm->AddRow($form->GetLabel('con_password'), $form->GetHTML('con_password') . $form->GetIcon('con_password'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Secret Question');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('secretquestion'), $form->GetHTML('secretquestion') . $form->GetIcon('secretquestion'));
	echo $webForm->AddRow($form->GetLabel('secretanswer'), $form->GetHTML('secretanswer') . $form->GetIcon('secretanswer'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Your access levels.');
	echo $window->OpenContent();
	echo $webForm->Open();
	
	foreach ($levels as $num=>$level) {
		echo $webForm->AddRow($form->GetLabel('level' . $num), $form->GetHTML('level' . $num) . $form->GetIcon('level' . $num));
	}
	
	echo $webForm->Close();
	echo $window->CloseContent();
	
	echo $window->AddHeader('Secondary mailbox settings.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('secondarymailbox'), $form->GetHTML('secondarymailbox') . $form->GetIcon('secondarymailbox'));
	echo $webForm->AddRow($form->GetLabel('secondarymailboxpassword'), $form->GetHTML('secondarymailboxpassword') . $form->GetIcon('secondarymailboxpassword'));
	echo $webForm->Close();
	echo $window->CloseContent();
	
	echo $window->AddHeader('Your contact details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('fname'), $form->GetHTML('fname') . $form->GetIcon('fname'));
	echo $webForm->AddRow($form->GetLabel('iname'), $form->GetHTML('iname') . $form->GetIcon('iname'));
	echo $webForm->AddRow($form->GetLabel('lname'), $form->GetHTML('lname') . $form->GetIcon('lname'));
	echo $webForm->AddRow($form->GetLabel('phone'), $form->GetHTML('phone') . $form->GetIcon('phone'));
	echo $webForm->AddRow($form->GetLabel('mobile'), $form->GetHTML('mobile') . $form->GetIcon('mobile'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Your address.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('address1'), $form->GetHTML('address1') . $form->GetIcon('address1'));
	echo $webForm->AddRow($form->GetLabel('address2'), $form->GetHTML('address2') . $form->GetIcon('address2'));
	echo $webForm->AddRow($form->GetLabel('address3'), $form->GetHTML('address3') . $form->GetIcon('address3'));
	echo $webForm->AddRow($form->GetLabel('city'), $form->GetHTML('city') . $form->GetIcon('city'));
	echo $webForm->AddRow($form->GetLabel('country'), $form->GetHTML('country') . $form->GetIcon('country'));
	echo $webForm->AddRow($form->GetLabel('region'), $form->GetHTML('region') . $form->GetIcon('region'));
	echo $webForm->AddRow($form->GetLabel('postcode'), $form->GetHTML('postcode') . $form->GetIcon('postcode'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'users.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function remove() {
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$user = new User();
		$user->Remove($_REQUEST['id']);
	}

	redirect(sprintf("Location: users.php?action=view"));
}

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Password.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Person.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

	$user = new User();
	
	$password = new Password(PASSWORD_LENGTH_USER);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('username', 'E-mail Address', 'text', '', 'username', 1, 100);
	$form->AddField('password', 'Password', 'text', $password->Value, 'password', PASSWORD_LENGTH_USER, 100, true);
	$form->AddField('is_active', 'Active', 'checkbox', 'Y', 'boolean', 1, 1, false);
	$form->AddField('secretquestion', 'Secret Question', 'text', $user->SecretQuestion, 'anything', 0, 255, false);
	$form->AddField('secretanswer', 'Secret Answer', 'text', $user->SecretAnswer, 'anything', 0, 255, false);
	$form->AddField('title', 'Title', 'select', 'Mr', 'alpha', 1, 4);
	$form->AddField('fname', 'First Name', 'text', '', 'alpha_numeric', 1, 60);
	$form->AddField('iname', 'Initial', 'text', '', 'alpha', 1, 1, false, 'size="1"');
	$form->AddField('lname', 'Last Name', 'text', '', 'alpha_numeric', 1, 60);
	$form->AddField('phone', 'Daytime Phone', 'text', '', 'telephone', NULL, NULL, false);
	$form->AddField('mobile', 'Mobile Phone', 'text', '', 'telephone', NULL, NULL, false);
	$form->AddField('address1', 'Property Name/Number', 'text', '', 'alpha_numeric', 1, 150, false);
	$form->AddField('address2', 'Street', 'text', '', 'alpha_numeric', 1, 150, false);
	$form->AddField('address3', 'Area', 'text', '', 'alpha_numeric', 1, 150, false);
	$form->AddField('city', 'City', 'text', '', 'alpha_numeric', 1, 150, false);
	$form->AddField('country', 'Country', 'select', $GLOBALS['SYSTEM_COUNTRY'], 'numeric_unsigned', 1, 11, false, 'onchange="propogateRegions(\'region\', this);"');
	$form->AddField('postcode', 'Postcode/Zip', 'text', '', 'alpha_numeric', 1, 10, false);
	$form->AddField('ipaccess', 'IP Access', 'text', '', 'anything', 1, 2000, false);
	$form->AddField('iprestrictions', 'IP Restrictions', 'text', '', 'anything', 1, 2000, false);

	$data = new DataQuery("SELECT * FROM person_title ORDER BY Person_Title");
	while ($data->Row) {
		$form->AddOption('title', $data->Row['Person_Title'], $data->Row['Person_Title']);

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery("SELECT * FROM countries ORDER BY Country ASC");
	$form->AddOption('country', '', '');
	while ($data->Row) {
		$form->AddOption('country', $data->Row['Country_ID'], $data->Row['Country']);

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT Region_ID, Region_Name FROM regions where Country_ID=%d ORDER BY Region_Name ASC", mysql_real_escape_string($form->GetValue('country'))));

	$regionCount = (isset($data->TotalRows) && !is_null($data->TotalRows)) ? $data->TotalRows : 0;

	if ($regionCount > 0) {
		$form->AddField('region', 'Region', 'select', '', 'numeric_unsigned', 1, 11, false);
		$form->AddOption('region', '0', '');

		while ($data->Row) {
			$form->AddOption('region', $data->Row['Region_ID'], $data->Row['Region_Name']);
			$data->Next();
		}
	} else {
		$form->AddField('region', 'Region', 'select', '', 'numeric_unsigned', 1, 11, false, 'disabled="disabled"');
		$form->AddOption('region', '0', '');
	}

	$levelData = new DataQuery("select * from access_levels");
	$levels = array();
	do {
		$levels[$levelData->Row['Access_ID']] = $levelData->Row['Access_Level'];
		$form->AddField('level' . $levelData->Row['Access_ID'], $levelData->Row['Access_Level'], 'checkbox', $user->HasAccess($levelData->Row['Access_ID']) ? 'Y' : 'N', 'boolean', NULL, NULL, false);
		$levelData->Next();
	} while ($levelData->Row);
	$levelData->Disconnect();

	$form->AddField('branch','Branch','select',$user->Branch->ID,'alpha_numeric',1,60,false);
	$form->AddOption('branch', '0', '');
	
	$branch = new DataQuery("SELECT * FROM branch");
	while ($branch->Row) {
		$form->AddOption('branch', $branch->Row['Branch_ID'], $branch->Row['Branch_Name']);
		$branch->Next();
	}
	$branch->Disconnect();
	
	$form->AddField('ispacker', 'Is Packer', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('ispayroll', 'Is Payroll', 'checkbox', $user->IsPayroll, 'boolean', 1, 1, false);
	$form->AddField('iscasualworker', 'Is Casual Worker', 'checkbox', $user->IsCasualWorker, 'boolean', 1, 1, false);
	$form->AddField('hours', 'Hours', 'text', $user->Hours, 'float', 1, 11);
	$form->AddField('timesheetdescription', 'Timesheet Description', 'checkbox', $user->RequireTimesheetDescription, 'boolean', 1, 1, false);
	$form->AddField('showsessions', 'Show Sessions', 'checkbox', $user->ShowSessions, 'boolean', 1, 1, false);
	$form->AddField('canbypassworktasks', 'Can Bypass Work Tasks', 'checkbox', $user->CanBypassWorkTasks, 'boolean', 1, 1, false);
	$form->AddField('secondarymailbox', 'Secondary Mailbox', 'text', '', 'username', 1, 120, false);
	$form->AddField('secondarymailboxpassword', 'Secondary Mailbox Password', 'password', '', 'password', 1, 120, false);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$user->IsActive = $form->GetValue('is_active');
			$user->SecretQuestion = $form->GetValue('secretquestion');
			$user->SecretAnswer = $form->GetValue('secretanswer');
			$user->Username = $form->GetValue('username');
			$user->SetPassword($form->GetValue('password'));
			$user->IsPacker = $form->GetValue('ispacker');
			$user->IsPayroll = $form->GetValue('ispayroll');
			$user->IsCasualWorker = $form->GetValue('iscasualworker');
			$user->Hours = $form->GetValue('hours');
			$user->RequireTimesheetDescription = $form->GetValue('timesheetdescription');
			$user->ShowSessions = $form->GetValue('showsessions');
			$user->CanBypassWorkTasks = $form->GetValue('canbypassworktasks');
			$user->Branch->ID = $form->GetValue('branch');
			$user->IP->Access = $form->GetValue('ipaccess');
			$user->IP->Restrictions = $form->GetValue('iprestrictions');
			$user->Person->Title = $form->GetValue('title');
			$user->Person->Name = $form->GetValue('fname');
			$user->Person->LastName = $form->GetValue('lname');
			$user->Person->Initial = $form->GetValue('iname');
			$user->Person->Phone = $form->GetValue('phone');
			$user->Person->Person->Mobile = $form->GetValue('mobile');
			$user->Person->Email = $form->GetValue('username');
			$user->Person->Address->Line1 = $form->GetValue('address1');
			$user->Person->Address->Line2 = $form->GetValue('address2');
			$user->Person->Address->Line3 = $form->GetValue('address3');
			$user->Person->Address->City = $form->GetValue('city');
			$user->Person->Address->Country->ID = $form->GetValue('country');
			$user->Person->Address->Region->ID = $form->GetValue('region');
			$user->Person->Address->Zip = $form->GetValue('postcode');
			$user->SecondaryMailbox = $form->GetValue('secondarymailbox');
			$user->SetSecondaryMailboxPassword($form->GetValue('secondarymailboxpassword'));
			
			if($user->IsUnique()) {
				$user->Person->Add();
				$user->Add();
				$user->Recalculate();
					
				$levelVals = array();
				foreach ($levels as $accessId=>$level) {
					$levelVals[$accessId] = $form->GetValue('level' . $accessId);
				}
				$user->SetAccessLevels($levelVals);

				redirect("Location: users.php");
			} else {
				$form->AddError("The username you specified is not unique.");
			}
		}
	}

	$page = new Page('Adding a New User', 'Note: a new password is automatically generated for new users.');
	$page->AddToHead('<script language="javascript" src="js/regions.php" type="text/javascript"></script>');
	$page->AddOnLoad("document.getElementById('username').focus();");
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Adding a New User');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();

	echo $window->AddHeader('Please complete the following fields. Required fields are marked with an asterisk (*).');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('username'), $form->GetHTML('username') . $form->GetIcon('username'));
	echo $webForm->AddRow($form->GetLabel('is_active'), $form->GetHTML('is_active') . $form->GetIcon('is_active'));
	echo $webForm->AddRow($form->GetLabel('branch'),$form->GetHTML('branch').$form->GetIcon('branch'));
	echo $webForm->AddRow($form->GetLabel('ipaccess'),$form->GetHTML('ipaccess').$form->GetIcon('ipaccess'));
	echo $webForm->AddRow($form->GetLabel('iprestrictions'),$form->GetHTML('iprestrictions').$form->GetIcon('iprestrictions'));
	echo $webForm->AddRow('Note:', 'Comma seperate IP addresses for multiple, single, IP entity or hypenate IP addresses for ranges.<br />Example: 10.0.0.1, 10.0.0.2, 10.0.0.5-10.0.0.10');
	echo $webForm->AddRow($form->GetLabel('ispacker'), $form->GetHTML('ispacker') . $form->GetIcon('ispacker'));
	echo $webForm->AddRow($form->GetLabel('ispayroll'), $form->GetHTML('ispayroll') . $form->GetIcon('ispayroll'));
	echo $webForm->AddRow($form->GetLabel('iscasualworker'), $form->GetHTML('iscasualworker') . $form->GetIcon('iscasualworker'));
	echo $webForm->AddRow($form->GetLabel('hours'), $form->GetHTML('hours') . $form->GetIcon('hours'));
	echo $webForm->AddRow($form->GetLabel('timesheetdescription'), $form->GetHTML('timesheetdescription') . $form->GetIcon('timesheetdescription'));
	echo $webForm->AddRow($form->GetLabel('showsessions'), $form->GetHTML('showsessions') . $form->GetIcon('showsessions'));
	echo $webForm->AddRow($form->GetLabel('canbypassworktasks'), $form->GetHTML('canbypassworktasks') . $form->GetIcon('canbypassworktasks'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Password');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('password'), $form->GetHTML('password') . $form->GetIcon('password'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Secret Question');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('secretquestion'), $form->GetHTML('secretquestion') . $form->GetIcon('secretquestion'));
	echo $webForm->AddRow($form->GetLabel('secretanswer'), $form->GetHTML('secretanswer') . $form->GetIcon('secretanswer'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Your access levels.');
	echo $window->OpenContent();
	echo $webForm->Open();
	
	foreach ($levels as $num=>$level) {
		echo $webForm->AddRow($form->GetLabel('level' . $num), $form->GetHTML('level' . $num) . $form->GetIcon('level' . $num));
	}
	echo $webForm->Close();
	echo $window->CloseContent();
	
	echo $window->AddHeader('Secondary mailbox settings.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('secondarymailbox'), $form->GetHTML('secondarymailbox') . $form->GetIcon('secondarymailbox'));
	echo $webForm->AddRow($form->GetLabel('secondarymailboxpassword'), $form->GetHTML('secondarymailboxpassword') . $form->GetIcon('secondarymailboxpassword'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Your contact details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('fname'), $form->GetHTML('fname') . $form->GetIcon('fname'));
	echo $webForm->AddRow($form->GetLabel('iname'), $form->GetHTML('iname') . $form->GetIcon('iname'));
	echo $webForm->AddRow($form->GetLabel('lname'), $form->GetHTML('lname') . $form->GetIcon('lname'));
	echo $webForm->AddRow($form->GetLabel('phone'), $form->GetHTML('phone') . $form->GetIcon('phone'));
	echo $webForm->AddRow($form->GetLabel('mobile'), $form->GetHTML('mobile') . $form->GetIcon('mobile'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Your address.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('address1'), $form->GetHTML('address1') . $form->GetIcon('address1'));
	echo $webForm->AddRow($form->GetLabel('address2'), $form->GetHTML('address2') . $form->GetIcon('address2'));
	echo $webForm->AddRow($form->GetLabel('address3'), $form->GetHTML('address3') . $form->GetIcon('address3'));
	echo $webForm->AddRow($form->GetLabel('city'), $form->GetHTML('city') . $form->GetIcon('city'));
	echo $webForm->AddRow($form->GetLabel('country'), $form->GetHTML('country') . $form->GetIcon('country'));
	echo $webForm->AddRow($form->GetLabel('region'), $form->GetHTML('region') . $form->GetIcon('region'));
	echo $webForm->AddRow($form->GetLabel('postcode'), $form->GetHTML('postcode') . $form->GetIcon('postcode'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'users.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
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
	$table->AddField("", "IsLocked", "hidden");
	$table->AddField("ID", "User_ID", "right");
	$table->AddField("Is Active", "Is_Active", "center");
	$table->AddField("Username", "User_Name", "left");
	$table->AddField("User", "User", "left");
	$table->AddField("Is Packer", "Is_Packer", "center");
	$table->AddField("Is Payroll", "Is_Payroll", "center");
	$table->AddField("Is Casual Worker", "Is_Casual_Worker", "center");
	$table->AddField('Hours', 'Hours', 'right');
	$table->AddInput('', 'N', 'Y', 'select', 'User_ID', 'checkbox');
	$table->AddLink("?action=regeneratepassword&uid=%s", "<img src=\"../images/icons/arrow-circle.png\" alt=\"Regenerate Password\" border=\"0\">", "User_ID");
	$table->AddLink("?action=unlock&id=%s", "<img src=\"../images/icons/lock-unlock.png\" alt=\"Unlock\" border=\"0\">", "User_ID", true, false, array('IsLocked', '!=', 'N'));
	$table->AddLink("user_timesheet_logs.php?id=%s", "<img src=\"./images/icon_clock_1.gif\" alt=\"Timesheet Log\" border=\"0\">", "User_ID");
	$table->AddLink("user_holiday.php?id=%s", "<img src=\"./images/icon_regions_2.gif\" alt=\"Holidays\" border=\"0\">", "User_ID");
	$table->AddLink("user_documents.php?userid=%s", "<img src=\"./images/icon_view_1.gif\" alt=\"Documents\" border=\"0\">", "User_ID");
	$table->AddLink("users.php?action=update&id=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "User_ID");
	$table->AddLink("javascript:confirmRequest('users.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this item?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "User_ID");
	$table->SetMaxRows(25);
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo '<input type="button" name="add" value="add new user" class="btn" onclick="window.location.href=\'users.php?action=add\'" />';
	echo '<input type="submit" name="print" value="print selected" class="btn" />';

	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}