<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TelePrompt.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');

$session->Secure(2);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Welcome to the Teleprompt Portal</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link href="css/i_import.css" rel="stylesheet" type="text/css">
</head>
<body>
	<p><strong>Welcome to the Teleprompt Portal</strong></p>

	<?php if (isset($_REQUEST['loginurl'])) {
	$bubble = new Bubble('Quick Login URL', sprintf('<p>Your new quick login URL follows. Please copy it now as it cannot be recovered later except by resetting your password.</p> <p>%s</p>', base64_decode($_REQUEST['loginurl'])));

	echo $bubble->GetHTML();
	echo '<br />';	
	} ?>
	
	<?php
	$prompt = new TelePrompt();
	$prompt->Output('welcomescreen');

	echo $prompt->Body;
	?>
	
</body>
</html>
<?php
require_once('lib/common/app_footer.php');