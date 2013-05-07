<?php
ini_set('max_execution_time', '900');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/ContactAccount.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$account = new ContactAccount();

$data = new DataQuery(sprintf("SELECT Contact_ID, Account_Manager_ID FROM contact WHERE Account_Manager_ID>0"));
while($data->Row) {
	$account->ContactID = $data->Row['Contact_ID'];
	$account->AccountManagerID = $data->Row['Account_Manager_ID'];
	$account->Add();

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();
?>