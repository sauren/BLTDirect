<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$session->Secure(2);

$form = new Form($_SERVER['PHP_SELF'], 'GET');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('contactid', 'Contact ID', 'text', '', 'numeric_unsigned', 1, 11, false);
$form->AddField('org', 'Organisation', 'text', '', 'paragraph', 1, 255, false);
$form->AddField('postcode', 'Postcode', 'text', '', 'anything', 1, 32, false);

$page = new Page('Organisation Search','');
$page->Display('header');

$sqlSelect = '';
$sqlFrom = '';
$sqlWhere = '';

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()){
		$sqlSelect = sprintf("SELECT c.Contact_ID, o.Org_Name, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Account_Manager ");
		$sqlFrom = sprintf("FROM contact AS c INNER JOIN organisation AS o ON c.Org_ID=o.Org_ID LEFT JOIN users AS u ON u.User_ID=c.Account_Manager_ID LEFT JOIN person AS p ON p.Person_ID=u.Person_ID ");
		$sqlWhere = sprintf("WHERE c.Contact_Type='O'");

		if(strlen($form->GetValue('contactid')) > 0) {
			$sqlWhere .= sprintf(" AND c.Contact_ID=%d", mysql_real_escape_string($form->GetValue('contactid')));
		}
		
		if(strlen($form->GetValue('org')) > 0) {
			$sqlWhere .= sprintf(" AND o.Org_Name_Search LIKE '%s%%'", mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $form->GetValue('org'))));
		}

		if(strlen($form->GetValue('postcode')) > 0) {
			$sqlFrom .= sprintf("LEFT JOIN address AS a ON a.Address_ID=o.Address_ID ");
			$sqlWhere .= sprintf(" AND a.Zip_Search LIKE '%s%%'", mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', mysql_real_escape_string($form->GetValue('postcode')))));
		}
	} else {
		echo $form->GetError();
		echo '<br />';
	}
}

if(isset($_REQUEST['confirm'])) {
	if(isset($_REQUEST['merge'])) {
		if(isset($_REQUEST['select'])) {
			$contacts = array();
			
			foreach($_REQUEST as $key=>$value) {
				if(preg_match('/merge_([0-9]+)/', $key, $matches)) {
					$contacts[$matches[1]] = $matches[1];
				}
			}
			
			if(isset($contacts[$_REQUEST['select']])) {
				unset($contacts[$_REQUEST['select']]);	
			}
			
			foreach($contacts as $contactId) {
				new DataQuery(sprintf("UPDATE contact SET Parent_Contact_ID=%d WHERE Parent_Contact_ID=%d", mysql_real_escape_string($_REQUEST['select']), mysql_real_escape_string($contactId)));

				$contact = new Contact();
				$contact->Delete($contactId, true);
			}
		}

		redirectTo(sprintf('?confirm=true&contactid=%s&org=%s&postcode=%s', $form->GetValue('contactid'), $form->GetValue('org'), $form->GetValue('postcode')));
	}
}

if(isset($_REQUEST['confirm'])) {
	echo '<table width="100%"><tr><td valign="top">';
}

$window = new StandardWindow("Search for an Organisation.");
$webForm = new StandardForm;

echo $form->Open();
echo $form->GetHTML('confirm');

echo $window->Open();
echo $window->AddHeader('Search for contacts by any of the below fields.');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('contactid'), $form->GetHTML('contactid'));
echo $webForm->AddRow($form->GetLabel('org'), $form->GetHTML('org'));
echo $webForm->AddRow($form->GetLabel('postcode'), $form->GetHTML('postcode'));
echo $webForm->AddRow('', '<input type="submit" name="searchButton" value="search" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

if(isset($_REQUEST['confirm'])) {
	echo '</td><td width="10"></td><td valign="top">';

	$table = new DataTable('records');
	$table->SetExtractVars();
	$table->SetSQL(sprintf("%s%s%s", $sqlSelect, $sqlFrom, $sqlWhere));
	$table->SetTotalRowSQL(sprintf("SELECT COUNT(*) AS TotalRows %s%s", $sqlFrom, $sqlWhere));
	$table->AddField('ID#', 'Contact_ID', 'left');
	$table->AddField('Organisation', 'Org_Name', 'left');
	$table->AddField('Account Manager', 'Account_Manager', 'left');
	$table->AddInput('', 'N', 'Y', 'merge', 'Contact_ID', 'checkbox');
	$table->AddInput('', 'Y', 'Contact_ID', 'select', 'Contact_ID', 'radio');
	$table->AddLink("contact_profile.php?cid=%s","<img src=\"images/folderopen.gif\" alt=\"Open\" border=\"0\">", "Contact_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Contact_ID");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();


	echo '<br />';
	echo '<input type="submit" name="merge" value="merge" class="btn" />';
	
	echo '</td></tr></table>';
}

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');