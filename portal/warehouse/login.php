<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

if($session->IsLoggedIn) {
	$direct = (isset($_REQUEST['direct']) && !empty($_REQUEST['direct'])) ? $_REQUEST['direct'] : 'welcome.php';

	redirect(sprintf("Location: %s", $direct));
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'login', 'alpha', 4, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('direct', 'Direct', 'hidden', '', 'anything', 0, 255, false);
$form->AddField('username', 'E-mail Address', 'text', '', 'username', 6, 100);
$form->AddField('password', 'Password', 'password', '', 'password', 6, 100);

if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
	if($form->Validate()) {
		if($session->Login($form->GetValue('username'), $form->GetValue('password'))) {
			redirect(sprintf("Location: %s?direct=%s", $_SERVER['PHP_SELF'], $form->GetValue('direct')));
		} else {
			$form->AddError("Sorry you were unable to login. Please check your email address and password and try again.");
		}
	}
}

$page = new Page('Login', '');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHtml('action');
echo $form->GetHtml('confirm');
echo $form->GetHtml('direct');

echo sprintf('%s:<br />', $form->GetLabel('username'));
echo sprintf('%s<br />', $form->GetHtml('username'));
echo sprintf('%s:<br />', $form->GetLabel('password'));
echo sprintf('%s<br />', $form->GetHtml('password'));
echo sprintf('<br /><input type="submit" class="btn" name="login" value="Login" />');

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_header.php');