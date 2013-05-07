<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$login = new Form($_SERVER['PHP_SELF']);
$login->AddField('action', 'Action', 'hidden', 'login', 'alpha', 5, 5);
$login->SetValue('action', 'login');
$login->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$login->AddField('username', 'E-mail Address', 'text', '', 'username', 6, 100);
$login->AddField('password', 'Password', 'password', '', 'password', 6, 100);

$forgotten = new Form($_SERVER['PHP_SELF']);
$forgotten->AddField('action', 'Action', 'hidden', 'forgotten', 'alpha', 9, 9);
$forgotten->SetValue('action', 'forgotten');
$forgotten->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$forgotten->AddField('username', 'E-mail Address', 'text', '', 'username', 6, 100);

if(isset($_POST['confirm'])) {
	if(isset($_POST['action']) && ($_POST['action'] == "login")) {
		if($login->Validate()) {
			$session->Login($login->GetValue('username'), $login->GetValue('password'));
		}
	} elseif(isset($_POST['action']) && ($_POST['action'] == "forgotten")) {
		if($forgotten->Validate()) {
			$user = new User();

			if(!$user->GetByUsername($forgotten->GetValue('username'))) {
				$forgotten->AddError('The username entered could not be found.', 'username');
			} elseif(empty($user->SecretQuestion) || empty($user->SecretAnswer)) {
				$forgotten->AddError('The username entered has no secret question and answer configured.', 'username');
			}

			if($forgotten->Valid) {
				redirectTo('forgotten.php?username=' . $forgotten->GetValue('username'));
			}
		}
	}
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Ignition</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="stylesheet" href="css/i_splash.css" type="text/css">
	<link rel="stylesheet" href="css/i_content.css" type="text/css">
	<link rel="shortcut icon" href="favicon.ico" />
	<script language="javascript" type="text/javascript">
	<!--
		if(window.top.location.href != window.self.location.href){
			window.top.location.href = window.self.location.href;
		}
	//-->
	</script>
</head>
<body>

<div id="splashContainer" class="splash">
	<div id="splashImage" class="columnLeft"><img src="images/logo_ignition_2.jpg" alt="Ignition Systems from Azexis" width="229" height="270" hspace="0" vspace="0" border="0" align="left"></div>
</div>

<div class="bubbles">
	<?php
	if(isset($_REQUEST['log']) && ($_REQUEST['log'] == 'regenerated')) {
		$bubble = new Bubble('Password Regenrated', 'Your user account password has been regenerate and emailed to you with immediate effect.');

		echo $bubble->GetHTML();
		echo '<br />';
	}

	if(!$login->Valid) {
		echo $login->GetError();
		echo '<br />';
	}

	if(!$forgotten->Valid) {
		echo $forgotten->GetError();
		echo '<br />';
	}
	?>
</div>

<div class="column">
	
	<h3>Forgotten Password?</h3>
	<br />

	<?php
	echo $forgotten->Open();
	echo $forgotten->GetHTML('action');
	echo $forgotten->GetHTML('confirm');
	
	echo $forgotten->GetLabel('username');
	echo '<br />';
	echo $forgotten->GetHTML('username'); 
	echo '<br />';
	echo '<br />';

	echo '<input type="submit" name="continue" value="continue" class="btn" />';

	echo $forgotten->Close();
	?>


</div>

<div class="column">
	
	<h3>Login</h3>
	<br />

	<?php
	echo $login->Open();
	echo $login->GetHTML('action');
	echo $login->GetHTML('confirm');
	
	echo $login->GetLabel('username');
	echo '<br />';
	echo $login->GetHTML('username'); 
	echo '<br />';
	echo '<br />';

	echo $login->GetLabel('password');
	echo '<br />';
	echo $login->GetHTML('password');
	echo '<br />';
	echo '<br />';

	echo '<input type="submit" name="login" value="login" class="btn" />';

	echo $login->Close();
	?>

</div>

</body>
</html>

<?php
require_once('lib/common/app_footer.php');