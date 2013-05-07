<?php
require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');

$contact = new Contact();

$data = new DataQuery(sprintf("SELECT Contact_ID FROM contact WHERE Account_Manager_ID=31"));
while($data->Row) {
	$contact->Get($data->Row['Contact_ID']);
	$contact->AccountManagerID = 40;
	$contact->Update();

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();