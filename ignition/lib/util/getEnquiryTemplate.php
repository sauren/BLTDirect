<?php
require_once('../classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/EnquiryTemplate.php');

$template = new EnquiryTemplate();
if(!$template->Get($_REQUEST['id'])) {
	header("HTTP/1.0 400 Bad Request");
} else {
	echo sprintf("%s{br}\n", $template->ID);
	echo sprintf("%s{br}\n", $template->Title);
	echo sprintf("%s{br}\n", $template->Template);
}

$GLOBALS['DBCONNECTION']->Close();
?>