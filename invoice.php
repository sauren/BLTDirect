<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Invoice.php');

$session->Secure();

if(id_param('invoiceid')){
	$invoice = new Invoice(id_param('invoiceid'));
	$invoice->GetLines();
}

$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM invoice AS i INNER JOIN customer AS c ON c.Customer_ID=i.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE ((n.Parent_Contact_ID>0 AND n.Parent_Contact_ID=%d) OR (n.Parent_Contact_ID=0 AND n.Contact_ID=%d)) AND i.Invoice_ID=%d", mysql_real_escape_string($session->Customer->Contact->Parent->ID), mysql_real_escape_string($session->Customer->Contact->ID), mysql_real_escape_string(id_param('invoiceid'))));

if($data->Row['Counter'] == 0) {
	redirect(sprintf("Location: invoices.php"));
}
$data->Disconnect();

echo $invoice->GetDocument();
?>