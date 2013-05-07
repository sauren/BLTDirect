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

$limit = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : 10000;
$iterations = isset($_REQUEST['iterations']) ? $_REQUEST['iterations'] : 10;

for($i=0; $i<$iterations; $i++) {
	new DataQuery(sprintf("INSERT INTO `customer_session_archive` (`Session_ID`, `PHP_Session_ID`, `Is_Active`, `Referrer`, `Referrer_Search_Term`, `Created_On`, `Token`, `IP_Address`, `User_Agent_ID`, `Customer_ID`, `Affiliate_ID`) SELECT cs.`Session_ID`, cs.`PHP_Session_ID`, cs.`Is_Active`, cs.`Referrer`, cs.`Referrer_Search_Term`, cs.`Created_On`, cs.`Token`, cs.`IP_Address`, cs.`User_Agent_ID`, cs.`Customer_ID`, cs.`Affiliate_ID` FROM `customer_session` AS cs LEFT JOIN `customer_session_archive` AS csa ON cs.Session_ID=csa.Session_ID WHERE csa.Session_ID IS NULL LIMIT 0, %d", $limit));

	sleep(2);
}

$GLOBALS['DBCONNECTION']->Close();