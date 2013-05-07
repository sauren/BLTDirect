<?php
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

$data = new DataQuery(sprintf("SELECT c.Contact_ID, o.Phone_1 FROM organisation AS o INNER JOIN contact AS c ON c.Org_ID=o.Org_ID WHERE o.Org_Name LIKE 'City Electrical Factors (%%'"));
while($data->Row) {
	$customer = new Customer();
	$customer->Username = '0@no-email.com';
	$customer->Contact->Parent = new Contact();
	$customer->Contact->Parent->ID = $data->Row['Contact_ID'];
	$customer->Contact->Person->Name = 'Joe';
	$customer->Contact->Person->LastName = 'Bloggs';
	$customer->Contact->Person->Phone1 = $data->Row['Phone_1'];
	$customer->Contact->Person->Email = '0@no-email.com';
	$customer->Contact->Type = 'I';
	$customer->Contact->Add();
	$customer->Add();
	
	$data->Next();
}
$data->Disconnect();