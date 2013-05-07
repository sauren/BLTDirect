<?php
ini_set('max_execution_time', '900');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Enquiry.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$enquiry = new Enquiry();

$data = new DataQuery(sprintf("SELECT Enquiry_ID FROM enquiry WHERE Status NOT LIKE 'Closed' AND Created_On<'2009-01-01 00:00:00'"));
while($data->Row) {
	$enquiry->Get($data->Row['Enquiry_ID']);
	$enquiry->IsRequestingClosure = 'N';
	$enquiry->IsPendingAction = 'N';
	$enquiry->Status = 'Closed';
	$enquiry->ClosedOn = date('Y-m-d H:i:s');
	$enquiry->ClosedType->ID = 0;
	$enquiry->Update();

	$enquiry->SendClosed();

	echo $data->Row['Enquiry_ID'] . '<br />';

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();
?>