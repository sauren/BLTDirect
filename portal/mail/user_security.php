<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$session->Secure(2);

$user = new User($session->User->ID);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('username', 'Username', 'text', $user->Username, 'email', 1, 100);
$form->AddField('password', 'Password', 'password', '', 'password', PASSWORD_LENGTH_CUSTOMER, 100, false);
$form->AddField('cpassword', 'Confirm Password', 'password', '', 'password', PASSWORD_LENGTH_CUSTOMER, 100, false);

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
	if($form->Validate()) {
		if($form->GetValue('password') != '') {
			$user->SetPassword($form->GetValue('password'));
		}

		if($form->GetValue('password') != $form->GetValue('cpassword')){
			$form->AddError('Passwords must match.', 'cpassword');
		}

		if($form->Valid){
			$user->Username = $form->GetValue('username');
			$user->Update();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}
}

$page = new Page("Security Settings", "Edit users security details.");
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo "<br />";
}

$window = new StandardWindow('Edit users security details');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');

echo $window->Open();
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('username'),$form->GetHTML('username').$form->GetIcon('username'));
echo $webForm->AddRow($form->GetLabel('password'),$form->GetHTML('password').$form->GetIcon('password'));
echo $webForm->AddRow($form->GetLabel('cpassword'),$form->GetHTML('cpassword').$form->GetIcon('cpassword'));
echo $webForm->AddRow('', sprintf('<input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');