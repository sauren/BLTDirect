<?php
require_once('../../../../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Document.php');

$document = new Document();
if(!$document->Get($_REQUEST['id'])) {
	header("HTTP/1.0 400 Bad Request");
} else {
	echo sprintf("%s{br}\n", $document->ID);
	echo sprintf("%s{br}\n", $document->Title);
	echo sprintf("%s{br}\n", $document->Body);
}

$GLOBALS['DBCONNECTION']->Close();
?>