<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$user = new User();

if(!isset($_REQUEST['username']) || !$user->GetByUsername($_REQUEST['username'])) {
	redirectTo('login.php');
}

if(empty($user->SecretQuestion) || empty($user->SecretAnswer)) {
	redirectTo('login.php');
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'forgotten', 'alpha', 9, 9);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('username', 'E-mail Address', 'hidden', '', 'username', 6, 100);
$form->AddField('answer', 'Answer', 'text', '', 'anything', 0, 255);

if(isset($_POST['confirm'])) {
	if(isset($_POST['action']) && ($_POST['action'] == "forgotten")) {
		if($form->Validate()) {
			if(strtolower(trim($form->GetValue('answer'))) != strtolower(trim($user->SecretAnswer))) {
				$form->AddError('The answer is incorrect.', 'answer');
			}

			if($form->Valid) {
				$user->RegeneratePassword();

				redirectTo('login.php?log=regenerated');
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
	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}
	?>
</div>

<div class="column">

	<h3>Forgotten Password?</h3>
	<br />

	<?php
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('username');
	
	echo '<label>Secret Question</label>';
	echo '<br />';
	echo sprintf('"%1$s"', $user->SecretQuestion);
	echo '<br />';
	echo '<br />';

	echo $form->GetLabel('answer');
	echo '<br />';
	echo $form->GetHTML('answer');
	echo '<br />';
	echo '<br />';

	echo '<input type="submit" name="submit" value="submit" class="btn" />';

	echo $form->Close();
	?>

</div>

</body>
</html>

<?php
require_once('lib/common/app_footer.php');