<?php
ini_set('max_execution_time', '9000');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Contact.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();
$GLOBALS['SITE_LIVE'] = false;

$limit = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : 100000;
$iterations = isset($_REQUEST['iterations']) ? $_REQUEST['iterations'] : 10;

for($i=0; $i<$iterations; $i++) {
	new DataQuery(sprintf("INSERT INTO `customer_session_item_archive` (`Session_Item_ID`, `Session_ID`, `Customer_ID`, `Page_Request`, `Token`, `IP_Address`, `User_Agent_ID`, `Created_On`) SELECT csi.`Session_Item_ID`, csi.`Session_ID`, csi.`Customer_ID`, csi.`Page_Request`, csi.`Token`, csi.`IP_Address`, csi.`User_Agent_ID`, csi.`Created_On` FROM `customer_session_item` AS csi LEFT JOIN `customer_session_item_archive` AS csia ON csi.Session_Item_ID=csia.Session_Item_ID WHERE csia.Session_Item_ID IS NULL LIMIT 0, %d", $limit));

	sleep(2);
}

$GLOBALS['DBCONNECTION']->Close();