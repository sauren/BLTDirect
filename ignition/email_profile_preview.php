<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Email.php');

$session->Secure(2);

$email = new Email();

if($email->Get($_REQUEST['id'])) {
	echo $email->PrepareTemplate();
}

require_once('lib/common/app_footer.php');