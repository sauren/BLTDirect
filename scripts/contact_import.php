<?php
require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/MySQLConnection.php');

$localConnection = new MySQLConnection('bltdirect.com', 'ell05', 'ell05', 'Teit7v');
$remoteConnection = new MySQLConnection('78.136.12.34', 'elements_1', 'elements', 'elm0156');

$GLOBALS['DBCONNECTION'] = $remoteConnection;

$data = new DataQuery(sprintf("SELECT c.Contact_ID, o.*, a.*, r.Region_Name, co.Country FROM contact AS c INNER JOIN organisation AS o ON o.Org_ID=c.Org_ID INNER JOIN address AS a ON a.Address_ID=o.Address_ID LEFT JOIN regions AS r ON r.Region_ID=a.Region_ID LEFT JOIN countries AS co ON co.Country_ID=a.Country_ID WHERE o.Org_Name LIKE 'Senate (%%' OR o.Org_Name LIKE 'Edmundson Electrical %%' OR o.Org_Name LIKE '%% Electrical Wholesale %%' ORDER BY o.Org_Name ASC"));
while($data->Row) {
	if(!stristr($data->Row['Org_Name'], 'ems') && !stristr($data->Row['Org_Name'], 'gone')) {
		echo $data->Row['Org_Name'] . '<br />';

		$contact = new Contact();
		$contact->Type = 'O';
		$contact->Organisation->Name = $data->Row['Org_Name'];
		$contact->Organisation->Address->Line1 = $data->Row['Address_Line_1'];
		$contact->Organisation->Address->Line2 = $data->Row['Address_Line_2'];
		$contact->Organisation->Address->Line3 = $data->Row['Address_Line_3'];
		$contact->Organisation->Address->City = $data->Row['City'];
		$contact->Organisation->Address->Region->Name = $data->Row['Region_Name'];
		$contact->Organisation->Address->Zip = $data->Row['Zip'];
		$contact->Organisation->Phone1 = $data->Row['Phone_1'];
		$contact->Organisation->Fax = $data->Row['Fax'];
		$contact->Organisation->Email = $data->Row['Email'];
		$contact->Organisation->Url = $data->Row['URL'];
		$contact->Organisation->CompanyNo = $data->Row['Company_Number'];
		$contact->Organisation->TaxNo = $data->Row['Tax_Number'];

		$GLOBALS['DBCONNECTION'] = $localConnection;

		$contact->Organisation->Address->Region->GetIDFromString();
		$contact->Organisation->Address->Country->GetIDFromString($data->Row['Country']);
		$contact->Add();

		$GLOBALS['DBCONNECTION'] = $remoteConnection;

		$data2 = new DataQuery(sprintf("SELECT c.Contact_ID, p.*, a.*, r.Region_Name, co.Country FROM contact AS c INNER JOIN person AS p ON p.Person_ID=c.Person_ID INNER JOIN address AS a ON a.Address_ID=p.Address_ID LEFT JOIN regions AS r ON r.Region_ID=a.Region_ID LEFT JOIN countries AS co ON co.Country_ID=a.Country_ID WHERE c.Parent_Contact_ID=%d", $data->Row['Contact_ID']));
		while($data2->Row) {
			$customer = new Customer();
			$customer->Contact->Parent = new Contact();
			$customer->Contact->Parent->ID = $contact->ID;
			$customer->Contact->Type = 'I';
			$customer->Contact->IsCustomer = 'Y';
			$customer->Contact->Person->Title = $data2->Row['Name_Title'];
			$customer->Contact->Person->Name = $data2->Row['Name_First'];
			$customer->Contact->Person->LastName = $data2->Row['Name_Last'];
			$customer->Contact->Person->Phone1 = $data2->Row['Phone_1'];
			$customer->Contact->Person->Fax = $data2->Row['Fax'];
			$customer->Contact->Person->Mobile = $data2->Row['Mobile'];
			$customer->Contact->Person->Email = $data2->Row['Email'];
			$customer->Contact->Person->Address->Line1 = $data2->Row['Address_Line_1'];
			$customer->Contact->Person->Address->Line2 = $data2->Row['Address_Line_2'];
			$customer->Contact->Person->Address->Line3 = $data2->Row['Address_Line_3'];
			$customer->Contact->Person->Address->City = $data2->Row['City'];
			$customer->Contact->Person->Address->Region->Name = $data2->Row['Region_Name'];
			$customer->Contact->Person->Address->Zip = $data2->Row['Zip'];

			$GLOBALS['DBCONNECTION'] = $localConnection;

			$customer->Contact->Person->Address->Region->GetIDFromString();
			$customer->Contact->Person->Address->Country->GetIDFromString($data2->Row['Country']);
			$customer->Contact->Add();
			$customer->Add(false);

			$GLOBALS['DBCONNECTION'] = $remoteConnection;

			$data2->Next();
		}
		$data2->Disconnect();
	}

	$data->Next();
}
$data->Disconnect();