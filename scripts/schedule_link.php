<?php
ini_set('max_execution_time', '900');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$data = new DataQuery(sprintf("SELECT Contact_Schedule_ID, Note FROM contact_schedule WHERE Note LIKE '%%recent order%%'"));
while($data->Row) {
	if(preg_match('/\(#([A-Z]{1})([0-9]*)\)/', $data->Row['Note'], $matches)) {
		$note = str_replace($matches[0], sprintf('(#<a href="order_details.php?orderid=%d">%s%d</a>)', $matches[2], $matches[1], $matches[2]), $data->Row['Note']);

		new DataQuery(sprintf("UPDATE contact_schedule SET Note='%s' WHERE Contact_Schedule_ID=%d", $note, $data->Row['Contact_Schedule_ID']));
	}

	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Contact_Schedule_ID, Note FROM contact_schedule WHERE Note LIKE '%%recent enquiry%%'"));
while($data->Row) {
	if(preg_match('/\(#([A-Z]{1})([0-9]*)\)/', $data->Row['Note'], $matches)) {
		$note = str_replace($matches[0], sprintf('(#<a href="enquiry_details.php?enquiryid=%d">%s%d</a>)', $matches[2], $matches[1], $matches[2]), $data->Row['Note']);

		new DataQuery(sprintf("UPDATE contact_schedule SET Note='%s' WHERE Contact_Schedule_ID=%d", $note, $data->Row['Contact_Schedule_ID']));
	}

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();
?>