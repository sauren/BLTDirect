<?php
require_once('lib/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Invoice.php');

$session->Secure(3);

if(isset($_REQUEST['invoiceid'])){
	$invoice = new Invoice($_REQUEST['invoiceid']);
	$invoice->GetLines();
}

echo $invoice->GetDocument();
?>