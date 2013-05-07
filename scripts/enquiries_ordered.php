<?php
ini_set('max_execution_time', '900');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$enquiry = new Enquiry();

$data = new DataQuery(sprintf("SELECT e.Enquiry_ID FROM enquiry AS e LEFT JOIN enquiry_type AS et ON et.Enquiry_Type_ID=e.Enquiry_Type_ID INNER JOIN customer AS c ON c.Customer_ID=e.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN person AS p ON p.Person_ID=n.Person_ID LEFT JOIN contact AS n2 ON n.Parent_Contact_ID=n2.Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=n2.Org_ID LEFT JOIN users AS u ON u.User_ID=e.Owned_By LEFT JOIN person AS p2 ON p2.Person_ID=u.Person_ID INNER JOIN orders AS o2 ON o2.Customer_ID=e.Customer_ID AND o2.Created_On BETWEEN e.Created_On AND NOW() AND o2.Is_Sample='N' LEFT JOIN orders AS o3 ON o3.Customer_ID=e.Customer_ID WHERE e.Status NOT LIKE 'Closed' AND e.Modified_On<ADDDATE(NOW(), INTERVAL -7 DAY) GROUP BY e.Enquiry_ID"));
while($data->Row) {
	$enquiry->Get($data->Row['Enquiry_ID']);
	$enquiry->IsRequestingClosure = 'N';
	$enquiry->IsPendingAction = 'N';
	$enquiry->Status = 'Closed';
	$enquiry->ClosedOn = date('Y-m-d H:i:s');
	$enquiry->ClosedType->ID = 0;
	$enquiry->Update();

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();
?>