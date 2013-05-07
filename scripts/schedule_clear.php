<?php
ini_set('max_execution_time', '900');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$data = new DataQuery(sprintf("SELECT cs2.Contact_Schedule_ID FROM contact_schedule AS cs2 LEFT JOIN contact_schedule AS cs ON cs2.Contact_ID=cs.Contact_ID AND cs.Is_Complete='Y' WHERE cs2.Owned_By=8 AND cs2.Contact_Schedule_Type_ID=4 AND cs2.Is_Complete='N' AND cs.Contact_Schedule_ID IS NOT NULL GROUP BY cs2.Contact_Schedule_ID"));
while($data->Row) {
	new DataQuery(sprintf("DELETE FROM contact_schedule WHERE Contact_Schedule_ID=%d", $data->Row['Contact_Schedule_ID']));

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();
?>