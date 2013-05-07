<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bonus.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');

$session->Secure(3);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'new', 'alpha', 3, 3);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('user', 'User', 'select', '', 'numeric_unsigned', 1, 11);
$form->AddOption('user', '', '');

$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Name FROM users AS u INNER JOIN person AS p ON u.Person_ID=p.Person_ID ORDER BY Name ASC"));
while($data->Row) {
	$form->AddOption('user', $data->Row['User_ID'], $data->Row['Name']);

	$data->Next();
}
$data->Disconnect();

$form->AddField('startdate', 'Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
$form->AddField('enddate', 'End Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
$form->AddField('bonus', 'Bonus (&pound;)', 'text', '', 'float', 1, 11);

if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
	if($form->Validate()) {
		$start = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('startdate'), 6, 4), substr($form->GetValue('startdate'), 3, 2), substr($form->GetValue('startdate'), 0, 2));
		$end = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('enddate'), 6, 4), substr($form->GetValue('enddate'), 3, 2), substr($form->GetValue('enddate'), 0, 2));

		$startTime = strtotime($start);
		$endTime = strtotime($end);

		if($endTime < $startTime) {
			$form->AddError('End Date cannot come before the Start Date.', 'enddate');
		}

		if($form->Valid) {
			$bonus = new Bonus();
			$bonus->User->ID = $form->GetValue('user');
			$bonus->StartOn = $start;
			$bonus->EndOn = $end;
			$bonus->BonusAmount = $form->GetValue('bonus');
			$bonus->Add();

			redirect(sprintf("Location: %s?status=submitted", $_SERVER['PHP_SELF']));
		}
	}
}

$page = new Page('Create New Bonus', 'Submit bonus for users here.');
$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');

if(isset($_REQUEST['status']) && ($_REQUEST['status'] == 'submitted')) {
	$bubble = new Bubble('Bonus Submitted', 'The bonus has been successfully submitted.');

	echo $bubble->GetHTML();
	echo '<br />';
}

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Enter bonus information');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');

echo $window->Open();
echo $window->AddHeader('Please complete the following fields. Required fields are marked with an asterisk (*).');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('user'), $form->GetHTML('user') . $form->GetIcon('user'));
echo $webForm->AddRow($form->GetLabel('startdate'), $form->GetHTML('startdate') . $form->GetIcon('startdate'));
echo $webForm->AddRow($form->GetLabel('enddate'), $form->GetHTML('enddate') . $form->GetIcon('enddate'));
echo $webForm->AddRow($form->GetLabel('bonus'), $form->GetHTML('bonus') . $form->GetIcon('bonus'));
echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');
?>