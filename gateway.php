<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');

if($GLOBALS['USE_SSL'] && ($_SERVER['SERVER_PORT'] != $GLOBALS['SSL_PORT'])){
	$url = ($GLOBALS['USE_SSL'])?$GLOBALS['HTTPS_SERVER']:$GLOBALS['HTTP_SERVER'];
	$self = 'gateway.php';
	$qs = '';
	if(!empty($_SERVER['QUERY_STRING'])){$qs = '?' . $_SERVER['QUERY_STRING'];}
	redirect(sprintf("Location: %s%s%s", $url, $self, $qs));
}

$direct = "accountcenter.php";
$isCheckout = false;
$isReturns = false;
$isIntroduce = false;
$isCancellation = false;
$isDuplication = false;

if(param('direct')) {
	$form = new Form('gateway.php');
	if (preg_match("/{$form->RegularExp['link_relative']}/", param('direct'))) {
		$direct = htmlspecialchars(param('direct'));
	}
	if(stristr($direct, 'checkout.php')) $isCheckout = true;
	if(stristr($direct, 'returnorder.php')) $isReturns = true;
	if(stristr($direct, 'introduce.php')) $isIntroduce = true;
	if(stristr($direct, 'cancel.php')) $isCancellation = true;
	if(stristr($direct, 'duplicate.php')) $isDuplication = true;
}

$login = new Form('gateway.php');
$login->AddField('action', 'Action', 'hidden', 'login', 'alpha', 4, 6);
$login->SetValue('action', 'login');
$login->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$login->AddField('direct', 'Direct', 'hidden', $direct, 'link_relative', 1, 255);
$login->SetValue('direct', $direct);
$login->AddField('username', 'E-mail Address', 'text', '', 'anything', 6, 100);
$login->AddField('password', 'Password', 'password', '', 'password', 6, 100);

$assistant = new Form('gateway.php');
$assistant->TabIndex = 3;
$assistant->AddField('action', 'Action', 'hidden', 'assistant', 'alpha', 1, 11);
$assistant->SetValue('action', 'assistant');
$assistant->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$assistant->AddField('emailOrUser', 'Email Address', 'text', '', 'paragraph', 1, 100);
$assistant->AddField('direct', 'Direct', 'hidden', $direct, 'link_relative', 1, 255);
$assistant->SetValue('direct', $direct);

if(strtolower(param('confirm', '')) == "true"){

	if($action == "login"){
		$login->Validate();

		if($session->Login($login->GetValue('username'), $login->GetValue('password'))){
			redirect("Location: " . $login->GetValue('direct'));
		} else {
			$login->AddError("Sorry you were unable to login. Please check your email address and password and try again. If you are struggling to log in you can use our forgotten password facility to retrieve your password.");
		}
	} elseif ($action == "assistant") {
		$str = $assistant->GetValue('emailOrUser');
		if(empty($str)){
			redirect("Location: gateway.php");
		} else {
			$customer = new Customer();

			if(!$customer->IsUnique($str)){
				$customer->Get();
				$customer->Contact->Get();
				$customer->ResetPasswordEmail();

				redirect(sprintf("Location: gateway.php?assistant=successful"));
			} else {
				$assistant->AddError('Sorry, we could not find your entry in our database.');
			}
		}
	}
}

if (!file_exists('lib/' . $renderer . $_SERVER['PHP_SELF'])) {
	header('HTTP/1.1 404 Not Found');
	exit;
}

require_once('lib/' . $renderer . $_SERVER['PHP_SELF']);
require_once('lib/common/appFooter.php');