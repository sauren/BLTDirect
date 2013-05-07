<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
view();

/*
///////////////////////////////////////////
Function:	view()
Author:		Geoff Willings
Date:		07 Feb 2005
///////////////////////////////////////////
*/
function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('businessOnly', 'Export Businesses Only', 'checkbox', 'N', 'boolean', NULL, NULL, false);
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$str = 'all';
			if($form->GetValue('businessOnly') == 'Y'){
				$str = 'business';
			}
			export($str);
			exit();
		}
	}
	$page = new Page('Export Customer CSV', '');

	$page->Display('header');
	// Show Error Report if Form Object validation fails
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	$window = new StandardWindow("Export Products from a Category.");
	$webForm = new StandardForm;
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('','Export the details of all active customers');
	echo $webForm->AddRow('&nbsp;', $form->GetHTML('businessOnly') . $form->GetLabel('businessOnly'));
	echo $webForm->AddRow('&nbsp;','<input type="submit" name="export" value="export" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	echo "<br>";
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}


function export($str){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	$lines = array();

	$fileDate = getDatetime();
	$fileDate = substr($fileDate, 0, strpos($fileDate, ' '));

	$filename = "ignition_customer_export_" . $fileDate.'.csv';

	// Set File Headers
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Type: application/force-download");
	$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);

	if((is_integer(strpos($userAgent, "msie"))) && (is_integer(strpos($userAgent, "win")))){
		header("Content-Disposition: filename=" . basename($filename) . ";");
	} else {
		header("Content-Disposition: attachment; filename=" . basename($filename) . ";");
	}
	header("Content-Transfer-Encoding: binary");

	outputColumns($str);

	// output folder contents
	getFolderContent($str);
}

function getFolderContent($str){
	if(strtolower($str) == 'business'){
		$sql = sprintf("select count(o.Order_ID) as Score, o.Customer_ID, p.Name_First, p.Name_Last, p.Email, p.Phone_1, a.*,co.Parent_Contact_ID, r.Region_Name AS Name_Region,cu.Country, co.Org_ID from orders as o
inner join customer as c on o.Customer_ID=c.Customer_ID
inner join contact as co on c.Contact_ID=co.Contact_ID
INNER JOIN person p ON co.Person_ID = p.Person_ID
				INNER JOIN address a ON p.Address_ID = a.Address_ID
				INNER JOIN regions r ON a.Region_ID = r.Region_ID
				INNER JOIN countries cu ON a.Country_ID = cu.Country_ID
				WHERE c.Is_Active = 'Y' AND co.Org_ID = 0
and co.Parent_Contact_ID != 0
group by Customer_ID
order by Score desc");
	} else {
		$sql = sprintf("select p.Name_First, p.Name_Last, p.Email, p.Phone_1, a.*,co.Parent_Contact_ID, r.Region_Name AS Name_Region,cu.Country, co.Org_ID FROM customer c
				INNER JOIN contact co ON c.Contact_ID = co.Contact_ID
				INNER JOIN person p ON co.Person_ID = p.Person_ID
				INNER JOIN address a ON p.Address_ID = a.Address_ID
				INNER JOIN regions r ON a.Region_ID = r.Region_ID
				INNER JOIN countries cu ON a.Country_ID = cu.Country_ID
				WHERE c.Is_Active = 'Y' AND co.Org_ID = 0;");
	}

	$customers = new DataQuery($sql);
	while($customers->Row){
		$line = array();
		if(strtolower($str) == 'business') $line[] = $customers->Row['Score'];
		if($customers->Row['Parent_Contact_ID'] != 0){
			$orgName = new DataQuery(sprintf("SELECT Org_Name FROM contact c INNER JOIN organisation o ON c.Org_ID = o.Org_ID WHERE c.Contact_ID = %d", mysql_real_escape_string($customers->Row['Parent_Contact_ID'])));
			$line[] = $orgName->Row['Org_Name'];
			$orgName->Disconnect();
		}else{
			$line[] = "N/A";
		}
		$line[] = $customers->Row['Name_First'];
		$line[] = $customers->Row['Name_Last'];
		$line[] = $customers->Row['Email'];
		$line[] = $customers->Row['Phone_1'];
		$line[] = $customers->Row['Address_Line_1'];
		$line[] = $customers->Row['Address_Line_2'];
		$line[] = $customers->Row['Address_Line_3'];
		$line[] = $customers->Row['City'];
		$line[] = $customers->Row['Name_Region'];
		$line[] = $customers->Row['Country'];
		$line[] = $customers->Row['Zip'];

		print(getCsv($line));
		unset($line);
		$customers->Next();
	}
	$customers->Disconnect();

}



function getCsv($row, $fd=',', $quot='"'){
	$str ='';
	foreach($row as $cell){
		$cell = str_replace($quot, $quot.$quot, $cell);

		if (strchr($cell, $fd) !== FALSE || strchr($cell, $quot) !== FALSE || strchr($cell, "\n") !== FALSE) {
			$str .= $quot.$cell.$quot.$fd;
		}
		else {
			$str .= $cell.$fd;
		}
	}

	return substr($str, 0, -1)."\n";
}

function outputColumns($str){
	$line = array();
	if(strtolower($str) == 'business') $line[] = 'Total Orders';
	$line[] = 'Company Name';
	$line[] = 'First Name';
	$line[] = 'Last Name';
	$line[] = 'Email Address';
	$line[] = 'Phone Number';
	$line[] = 'Address Line 1';
	$line[] = 'Address Line 2';
	$line[] = 'Address Line 3';
	$line[] = 'City';
	$line[] = 'Region';
	$line[] = 'Country';
	$line[] = 'Zip';

	print(getCsv($line));
}
?>