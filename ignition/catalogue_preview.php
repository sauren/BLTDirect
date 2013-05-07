<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Catalogue.php');

$session->Secure(2);

$catalogue = new Catalogue();

if($catalogue->Get($_REQUEST['id'])) {
	echo $catalogue->PrepareTemplate(true);
}

require_once('lib/common/app_footer.php');
?>