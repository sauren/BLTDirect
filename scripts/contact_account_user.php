<?php
ini_set('max_execution_time', '900');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Contact.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$userId = 31;
$account = new ContactAccount();

$data = new DataQuery(sprintf("SELECT c.Contact_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Contact_Name, MAX(od.Created_On) AS Last_Ordered_On, MAX(cs.Completed_On) AS Last_Contacted_On, COUNT(DISTINCT od.Order_ID) AS Order_Count FROM contact AS c INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID LEFT JOIN customer AS cu ON c.Contact_ID=cu.Contact_ID LEFT JOIN orders AS od ON cu.Customer_ID=od.Customer_ID LEFT JOIN contact_schedule AS cs ON c.Contact_ID=cs.Contact_ID AND cs.Is_Complete='Y' WHERE c.Account_Manager_ID=%d GROUP BY c.Contact_ID", $userId));
while($data->Row) {
	if(($data->Row['Last_Ordered_On'] < '2009-04-01 00:00:00') && ($data->Row['Last_Contacted_On'] < '2009-04-01 00:00:00')) {
		$contact = new Contact($data->Row['Contact_ID']);
		$contact->AccountManagerID = 0;
		$contact->Update();

		echo $data->Row['Contact_Name'] . '<br />';
	}

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();