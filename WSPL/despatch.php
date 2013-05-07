<?php
require_once('lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Despatch.php');

$session->Secure();

if(id_param('despatchid')){
	$despatch = new Despatch(id_param('despatchid'));
	$despatch->GetLines();
}

$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM despatch AS d INNER JOIN orders AS o ON o.Order_ID=d.Order_ID INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE ((n.Parent_Contact_ID>0 AND n.Parent_Contact_ID=%d) OR (n.Parent_Contact_ID=0 AND n.Contact_ID=%d)) AND d.Despatch_ID=%d", $session->Customer->Contact->Parent->ID, $session->Customer->Contact->ID, id_param('despatchid')));
if($data->Row['Count'] == 0) {
	redirect(sprintf("Location: accountcenter.php"));
}
$data->Disconnect();

echo $despatch->GetDocument();