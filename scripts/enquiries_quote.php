<?php
ini_set('max_execution_time', '900');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Enquiry.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Quote.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$enquiry = new Enquiry();

$data = new DataQuery(sprintf("SELECT Enquiry_ID FROM enquiry WHERE Status LIKE 'Closed'"));
while($data->Row) {
	$enquiry->Get($data->Row['Enquiry_ID']);
	$enquiry->Customer->Get();
	$enquiry->Customer->Contact->Get();

	$quote = new Quote();

	if($enquiry->Customer->Contact->Parent->ID > 0) {
		$sql = sprintf("SELECT q.Quote_ID FROM quote AS q INNER JOIN customer AS c ON c.Customer_ID=q.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE n.Parent_Contact_ID=%d AND q.Created_On BETWEEN '%s' AND '%s' AND q.Status LIKE 'Pending'", $enquiry->Customer->Contact->Parent->ID, $enquiry->CreatedOn, $enquiry->ClosedOn);
	} else {
		$sql = sprintf("SELECT q.Quote_ID FROM quote AS q WHERE q.Customer_ID=%d AND q.Created_On BETWEEN '%s' AND '%s' AND q.Status LIKE 'Pending'", $enquiry->Customer->ID, $enquiry->CreatedOn, $enquiry->ClosedOn);
	}

	$data2 = new DataQuery($sql);
	while($data2->Row) {
		$quote->Get($data2->Row['Quote_ID']);
		$quote->Status = 'Cancelled';
		$quote->Update();

		$data2->Next();
	}
	$data2->Disconnect();

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();
?>