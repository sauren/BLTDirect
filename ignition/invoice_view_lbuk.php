<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Invoice.php');

$session->Secure(3);

if(isset($_REQUEST['invoiceid'])){
	$connection = new MySQLConnection($GLOBALS['SYNC_DB_HOST'][0], $GLOBALS['SYNC_DB_NAME'][0], $GLOBALS['SYNC_DB_USERNAME'][0], $GLOBALS['SYNC_DB_PASSWORD'][0]);
	
	$invoice = new Invoice($_REQUEST['invoiceid'], $connection);
	$invoice->GetLines($connection);

	echo $invoice->GetDocument(array(), $connection);
}