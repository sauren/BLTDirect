<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Password.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserPassword.php');

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('new_password', 'New Password', 'password', '', 'password', PASSWORD_LENGTH_USER, 100, true);
$form->AddField('con_password', 'Confirm New Password', 'password', '', 'password', PASSWORD_LENGTH_USER, 100, true);

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		if($form->GetValue('con_password') != $form->GetValue('new_password')) {
			$form->AddError('The new password and the confirmation password do not match.');
		}
		
		if($form->Valid) {
			$session->User->SetPassword($form->GetValue('new_password'));

			$password = new UserPassword();
			$password->userId = $session->User->ID;

			if($password->isUsed($session->User->Password)) {
				$form->AddError('The entered password has been used recently, please select another.');
			}

			if($form->Valid) {
				$session->User->Update();

				$password->password = $session->User->Password;
				$password->add();
	
				redirect('Location: welcome.php?loginurl=' . base64_encode($session->CreateQuickLoginUrl($session->User->Username, $form->GetValue('new_password'))));
			}
		}
	}
}

$page = new Page('Renew Your Password', 'Your current password has expired and must be renewed.');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Renew Password');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');

echo $window->Open();
echo $window->AddHeader('Please complete the following fields. Required fields are marked with an asterisk (*).');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('new_password'), $form->GetHTML('new_password') . $form->GetIcon('new_password'));
echo $webForm->AddRow($form->GetLabel('con_password'), $form->GetHTML('con_password') . $form->GetIcon('con_password'));
echo $webForm->AddRow('', sprintf('<input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');