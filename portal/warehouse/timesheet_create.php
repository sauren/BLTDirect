<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Timesheet.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');

$session->Secure(3);

$user = new User($GLOBALS['SESSION_USER_ID']);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'new', 'alpha', 3, 3);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('type', 'Type', 'select', '', 'anything', 1, 32);
$form->AddOption('type', '', '');
$form->AddOption('type', 'Holiday', 'Holiday');
$form->AddOption('type', 'Packing', 'Packing');
$form->AddOption('type', 'Premium', 'Premium');
$form->AddOption('type', 'Sick', 'Sick');
$form->AddOption('type', 'Standard', 'Standard');
$form->AddField('description', 'Description', 'textarea', '', 'anything', null, null, ($user->RequireTimesheetDescription == 'Y') ? true : false, 'rows="3"');
$form->AddField('startdate', 'Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
$form->AddField('enddate', 'End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
$form->AddField('hours', 'Hours (Per Day)', 'text', '', 'float', 1, 11);

if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
	if($form->Validate()) {
		if(strlen($form->GetValue('enddate')) == 0) {
			$timesheet = new Timesheet();
			$timesheet->User->ID = $user->ID;
			$timesheet->Type = $form->GetValue('type');
			$timesheet->Description = $form->GetValue('description');
			$timesheet->Date = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('startdate'), 6, 4), substr($form->GetValue('startdate'), 3, 2), substr($form->GetValue('startdate'), 0, 2));
			$timesheet->Hours = ($timesheet->Type != 'Sick') ? $form->GetValue('hours') : 0;
			$timesheet->Add();

			redirect(sprintf("Location: %s?status=submitted", $_SERVER['PHP_SELF']));
		} else {
			$start = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('startdate'), 6, 4), substr($form->GetValue('startdate'), 3, 2), substr($form->GetValue('startdate'), 0, 2));
			$end = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('enddate'), 6, 4), substr($form->GetValue('enddate'), 3, 2), substr($form->GetValue('enddate'), 0, 2));

			$temp = $start;

			$endTime = strtotime($end);
			$tempTime = strtotime($temp);

			if($endTime < $tempTime) {
				$form->AddError('End Date cannot come before the Start Date.', 'enddate');
			}

			if($form->Valid) {
				while($tempTime <= $endTime) {
					$timesheet = new Timesheet();
					$timesheet->User->ID = $user->ID;
					$timesheet->Type = $form->GetValue('type');
					$timesheet->Description = $form->GetValue('description');
					$timesheet->Date = sprintf('%s 00:00:00', substr($temp, 0, 10));
					$timesheet->Hours = ($timesheet->Type != 'Sick') ? $form->GetValue('hours') : 0;
					$timesheet->Add();

					$temp = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', $tempTime), date('d', $tempTime) + 1, date('Y', $tempTime)));
					$tempTime = strtotime($temp);
				}

				redirect(sprintf("Location: %s?status=submitted", $_SERVER['PHP_SELF']));
			}
		}
	}
}

$page = new Page('Create New Timesheet', 'Submit your timesheets here.');
$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');

if(isset($_REQUEST['status']) && ($_REQUEST['status'] == 'submitted')) {
	$bubble = new Bubble('Timesheet Submitted', 'Your timesheet has been successfully submitted.');

	echo $bubble->GetHTML();
	echo '<br />';
}

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Enter timesheet information');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');

echo $window->Open();
echo $window->AddHeader('Please complete the following fields. Required fields are marked with an asterisk (*).');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow('Timesheet For', trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)));
echo $webForm->AddRow($form->GetLabel('type'), $form->GetHTML('type') . $form->GetIcon('type'));
echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
echo $webForm->AddRow($form->GetLabel('startdate'), $form->GetHTML('startdate') . $form->GetIcon('startdate'));
echo $webForm->AddRow($form->GetLabel('enddate'), $form->GetHTML('enddate') . $form->GetIcon('enddate') . '<br />(Only required if entering time for a range of dates.)');
echo $webForm->AddRow($form->GetLabel('hours'), $form->GetHTML('hours') . $form->GetIcon('hours'));
echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');