<?php
ini_set('max_execution_time', '900');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Timesheet.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();
$GLOBALS['SITE_LIVE'] = false;

$data = new DataQuery(sprintf("SELECT * FROM report_packing ORDER BY Date ASC"));
while($data->Row) {
	$userId = 35;
	
	switch($data->Row['Packer']) {
		case 'Helen':
			$userId = 11;
			break;
			
		case 'Leane':
			$userId = 34;
			break;
	}
	
	$timesheet = new Timesheet();
	$timesheet->User->ID = $userId;
	$timesheet->Type = 'Packing';
	$timesheet->Date = $data->Row['Date'];
	$timesheet->Hours = $data->Row['Hours'];
	$timesheet->Add();
	
	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();
?>