<?php
require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');

$contact = new Contact();

$data = new DataQuery(sprintf("SELECT Parent_Contact_ID, Account_Manager_ID FROM contact WHERE Parent_Contact_ID>0 AND Account_Manager_ID>0 GROUP BY Parent_Contact_ID"));
while($data->Row) {
	$contact->Get($data->Row['Parent_Contact_ID']);
	$contact->AccountManagerID = $data->Row['Account_Manager_ID'];
	$contact->Update();

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();