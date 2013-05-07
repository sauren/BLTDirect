<?php
require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/ContactCreditAccount.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$account = new ContactCreditAccount();

$data = new DataQuery(sprintf("SELECT Contact_ID, Credit_Limit, Modified_On FROM customer WHERE Is_Credit_Active='Y'"));
while($data->Row) {
	$account->contact->ID = $data->Row['Contact_ID'];
	$account->limit = $data->Row['Credit_Limit'];
	$account->startedOn = $data->Row['Modified_On'];
	$account->add();

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();