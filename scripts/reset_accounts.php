<?php
require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');

$contact = new Contact();

$data = new DataQuery(sprintf("SELECT ca.*, c2.Contact_ID AS Parent_Contact_ID FROM contact_account AS ca INNER JOIN contact AS c ON c.Contact_ID=ca.Contact_ID INNER JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID AND c2.Account_Manager_ID=0 WHERE ca.End_Account_On>'2010-11-15 11:51:00' ORDER BY ca.End_Account_On"));
while($data->Row) {
	$contact->Get($data->Row['Parent_Contact_ID']);
	$contact->AccountManagerID = $data->Row['Account_Manager_ID'];
	$contact->Update();

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();