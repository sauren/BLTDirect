<?php
require_once('../lib/classes/ApplicationHeader.php');

$items = array();

$data = new DataQuery(sprintf("SELECT cc.Customer_ID, cc.Customer_Contact_ID, CONCAT_WS(' ', cc.Name_Title, cc.Name_First, cc.Name_Last) AS Contact_Name, TRIM(BOTH ',' FROM TRIM(REPLACE(CONCAT_WS(', ', a.Address_Line_1, a.Address_Line_2, a.Address_Line_3), ', , ', ''))) AS Address, a.City, UPPER(a.Zip) AS Zip, IF(LENGTH(a.Region_Name) > 0, a.Region_Name, r.Region_Name) AS Region, c.Country FROM customer_contact AS cc INNER JOIN customer AS cu ON cu.Customer_ID=cc.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=cu.Contact_ID INNER JOIN address AS a ON cc.Address_ID=a.Address_ID LEFT JOIN regions AS r ON r.Region_ID=a.Region_ID LEFT JOIN countries AS c ON c.Country_ID=a.Country_ID WHERE n.Is_Test='Y'"));
while($data->Row) {
	$items[] = sprintf("%d, %d, '%s'", $data->Row['Customer_ID'], $data->Row['Customer_Contact_ID'], sprintf('%s, %s, %s, %s, %s', $data->Row['Contact_Name'], $data->Row['Address'], $data->Row['City'], $data->Row['Zip'], $data->Row['Region']));

	$data->Next();
}
$data->Disconnect();

header("Content-Type: text/html; charset=UTF-8");
?>
var customerContacts = new Array(<?php echo implode(', ', $items); ?>);

function propogateCustomerContacts(target, customerId){
	var customerContact = document.getElementById(target);
	var n = 1;

	customerContact.options.length = 1;

	for(var i=0; i < customerContacts.length; i+=3){
		if(customerId == customerContacts[i]){
			customerContact.options[n++] = new Option(customerContacts[i+2], customerContacts[i+1]);
		}
	}

	customerContact.selectedIndex = 0;

	if(customerContact.options.length == 1){
		customerContact.disabled = true;
	} else {
		customerContact.disabled = false;
	}
}
<?php
$GLOBALS['DBCONNECTION']->Close();
?>