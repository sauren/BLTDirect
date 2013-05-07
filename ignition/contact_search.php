<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');

$form = new Form($_SERVER['PHP_SELF'], 'GET');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('contactid', 'Contact ID', 'text', '', 'numeric_unsigned', 1, 11, false);
$form->AddField('fname', 'First Name', 'text', '', 'paragraph', 1, 255, false);
$form->AddField('lname', 'Last Name', 'text', '', 'paragraph', 1, 255, false);
$form->AddField('org', 'Organisation', 'text', '', 'paragraph', 1, 255, false);
$form->AddField('postcode', 'Postcode', 'text', '', 'anything', 1, 32, false);

$sqlSelect = '';
$sqlFrom = '';
$sqlWhere = '';

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()){
		$sqlSelect = sprintf("SELECT c.Contact_ID, c.Is_Customer, c.Is_Supplier, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Contact_Name, o.Org_Name, CONCAT_WS(' ', p2.Name_First, p2.Name_Last) AS Account_Manager ");
		$sqlFrom = sprintf("FROM contact AS c INNER JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID LEFT JOIN users AS u ON u.User_ID=c.Account_Manager_ID LEFT JOIN person AS p2 ON p2.Person_ID=u.Person_ID ");
		$sqlWhere = sprintf("WHERE c.Contact_Type='I'");

		if(strlen($form->GetValue('contactid')) > 0) {
			$sqlWhere .= sprintf(" AND c.Contact_ID=%d", $form->GetValue('contactid'));
		}
		
		if(strlen($form->GetValue('fname')) > 0) {
			$sqlWhere .= sprintf(" AND p.Name_First_Search LIKE '%s%%'", mysql_real_escape_string(preg_replace('/[^a-zA-Z\p{L}\.\'\s\&\-\\\\\/\-]/u', '', $form->GetValue('fname'))));
		}

		if(strlen($form->GetValue('lname')) > 0) {
			$sqlWhere .= sprintf(" AND p.Name_Last_Search LIKE '%s%%'", mysql_real_escape_string(preg_replace('/[^a-zA-Z\p{L}\.\'\s\&\-\\\\\/\-]/u', '', $form->GetValue('lname'))));
		}

		if(strlen($form->GetValue('org')) > 0) {
			$sqlWhere .= sprintf(" AND o.Org_Name_Search LIKE '%s%%'", mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $form->GetValue('org'))));
		}

		if(strlen($form->GetValue('postcode')) > 0) {
			$sqlFrom .= sprintf("LEFT JOIN address AS a ON a.Address_ID=p.Address_ID ");
			$sqlWhere .= sprintf(" AND a.Zip_Search LIKE '%s%%'", mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $form->GetValue('postcode'))));
		}
	} else {
		echo $form->GetError();
		echo '<br />';
	}
}

$page = new Page('Search Contacts', '');
$page->Display('header');

if(isset($_REQUEST['confirm'])) {
	echo '<table width="100%"><tr><td valign="top">';
}

$window = new StandardWindow("Search for a Contact.");
$webForm = new StandardForm;

echo $form->Open();
echo $form->GetHTML('confirm');

echo $window->Open();
echo $window->AddHeader('Search for contacts by any of the below fields.');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('contactid'), $form->GetHTML('contactid'));
echo $webForm->AddRow($form->GetLabel('fname'), $form->GetHTML('fname'));
echo $webForm->AddRow($form->GetLabel('lname'), $form->GetHTML('lname'));
echo $webForm->AddRow($form->GetLabel('org'), $form->GetHTML('org'));
echo $webForm->AddRow($form->GetLabel('postcode'), $form->GetHTML('postcode'));
echo $webForm->AddRow('', '<input type="submit" name="search" value="search" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

if(isset($_REQUEST['confirm'])) {
	echo '</td><td width="10"></td><td valign="top">';

	$table = new DataTable('records');
	$table->SetExtractVars();
	$table->SetSQL(sprintf("%s%s%s", $sqlSelect, $sqlFrom, $sqlWhere));
	$table->SetTotalRowSQL(sprintf("SELECT COUNT(*) AS TotalRows %s%s", $sqlFrom, $sqlWhere));
	$table->AddField('ID#', 'Contact_ID', 'left');
	$table->AddField('Organisation', 'Org_Name', 'left');
	$table->AddField('Name', 'Contact_Name', 'left');
	$table->AddField('Customer', 'Is_Customer', 'center');
	$table->AddField('Supplier', 'Is_Supplier', 'center');
	$table->AddField('Account Manager', 'Account_Manager', 'left');
	$table->AddLink("contact_profile.php?cid=%s","<img src=\"images/folderopen.gif\" alt=\"Open\" border=\"0\">", "Contact_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Contact_ID");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '</td></tr></table>';
}

$page->Display('footer');
require_once('lib/common/app_footer.php');