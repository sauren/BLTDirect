<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Form.php");

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('password', 'Password', 'password', '', 'password', 6, 20);

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		if($form->GetValue('password') == 'meridian') {
			$_SESSION['Mobile']['Secure'] = true;

			redirect("Location: welcome.php");
		}
	}
}
?>

<html>
<head>
	<style>
	body, th, td { font-family: arial, sans-serif; font-size: 0.8em; }
	h1, h2, h3, h4, h5, h6 { margin-bottom: 0; padding-bottom: 0; }
	h1 { font-size: 1.6em; }
	</style>
</head>
<body>

	<h1>Login</h1>

	<?php
	if(!$form->Valid) {
		echo 'Incorrect password.';	
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('confirm');

	echo $form->GetLabel('password');
	echo $form->GetHTML('password');

	echo '<input type="submit" name="login" value="Login" />';

	echo $form->Close();
	?>

</body>
</html>