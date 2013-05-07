<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
view();
exit;

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$form = new Form($_SERVER['PHP_SELF'], 'get');
	$form->AddField('search', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('email', 'Email Address', 'text', '', 'paragraph', 1, 255,false);

	$page = new Page('Search Temporary Contacts', '');
	$page->Display('header');

	$sql = '';

	if(isset($_REQUEST['search']) && strtolower($_REQUEST['search']) == "true"){
		if($form->Validate()) {
			$sql = sprintf("SELECT c.Contact_ID, c.Is_Active, c.Is_Customer, c.Is_Supplier, p.Name_First, p.Name_Initial, p.Name_Last, o.Org_Name FROM contact AS c LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID WHERE c.Contact_Type='I' AND c.Is_Temporary='Y'");

			if(strlen($form->GetValue('email')) > 0) {
				$sql .= sprintf(" AND p.Email LIKE '%%%s%%'", $form->GetValue('email'));
			}

		} else {
			echo $form->GetError();
			echo "<br />";
		}
	}

	if(!empty($sql)){
		echo '<table width="100%"><tr><td valign="top">';
	}

	$window = new StandardWindow("Search for a Temporary Contact.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('search');

	echo $window->Open();
	echo $window->AddHeader('Search for temporary contacts by any of the below fields.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('email'), $form->GetHTML('email') . $form->GetHTML('excludeemail').$form->GetLabel('excludeemail'));
	echo $webForm->AddRow('', '<input type="submit" name="searchButton" value="search" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	if(!empty($sql)){
		echo '</td><td width="10"></td><td valign="top">';

		$table = new DataTable("cl");
		$table->SetSQL($sql);
		$table->AddField('ID#', 'Contact_ID', 'left');
		$table->AddField('Organisation', 'Org_Name', 'left');
		$table->AddField('First Name', 'Name_First', 'left');
		$table->AddField('Last Name', 'Name_Last', 'left');
		$table->AddField('Customer', 'Is_Customer', 'center');
		$table->AddField('Supplier', 'Is_Supplier', 'center');
		$table->AddField('Active', 'Is_Active', 'center');
		$table->AddLink("contact_profile.php?cid=%s&show=org","<img src=\"./images/icon_edit_1.gif\" alt=\"Update Contact\" border=\"0\">","Contact_ID");
		$table->AddLink("javascript:confirmRequest('contact_profile.php?action=remove&confirm=true&cid=%s','Are you sure you want to remove this contact? IMPORTANT: If a contact is being used by other areas of Ignition the contact may not be removed, but will instead be deactivated.');","<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">","Contact_ID");
		$table->Order = "desc";
		$table->SetMaxRows(25);
		$table->SetOrderBy("Contact_ID");
		$table->Finalise();
		$table->DisplayTable();
		echo "<br>";
		$table->DisplayNavigation();

		echo '</td></tr></table>';
	}

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>