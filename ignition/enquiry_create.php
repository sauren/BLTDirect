<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$session->Secure(2);

if($action == 'use'){
	useCid();
	exit();
} elseif(($action == 'find') || isset($_REQUEST['customers_Current']) || isset($_REQUEST['customers_Sort'])){
	find();
	exit();
} else {
	start();
	exit();
}

function useCid(){
	redirect(sprintf('Location: enquiry_summary.php?customerid=%d', $_REQUEST['cid']));
}

function find(){
	$sqlSelect = sprintf("SELECT cu.Customer_ID, cu.Username, cu.Is_Active, c.Contact_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Contact_Name, o.Org_Name, CONCAT_WS(' ', p2.Name_First, p2.Name_Last) AS Account_Manager ");
	$sqlFrom = sprintf("FROM contact AS c INNER JOIN customer AS cu ON cu.Contact_ID=c.Contact_ID INNER JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID LEFT JOIN users AS u ON u.User_ID=c.Account_Manager_ID LEFT JOIN person AS p2 ON p2.Person_ID=u.Person_ID ");
	$sqlWhere = sprintf("WHERE c.Contact_Type='I' ");
	$sqlMisc = sprintf("GROUP BY c.Contact_ID ");

	if(strlen($_REQUEST['orderid']) > 0) {
		$sqlFrom .= "INNER JOIN orders AS o2 ON o2.Customer_ID=cu.Customer_ID ";
		$sqlWhere .= sprintf(" AND o2.Order_ID=%d ", $_REQUEST['orderid']);
	}
		
	if(strlen($_REQUEST['contactid']) > 0) {
		$sqlWhere .= sprintf(" AND c.Contact_ID=%d", $_REQUEST['contactid']);
	}
	
	if(strlen($_REQUEST['fname']) > 0) {
		$sqlWhere .= sprintf(" AND p.Name_First_Search LIKE '%s%%'", mysql_real_escape_string(preg_replace('/[^a-zA-Z\p{L}\.\'\s\&\-\\\\\/\-]/u', '', $_REQUEST['fname'])));
	}

	if(strlen($_REQUEST['lname']) > 0) {
		$sqlWhere .= sprintf(" AND p.Name_Last_Search LIKE '%s%%'", mysql_real_escape_string(preg_replace('/[^a-zA-Z\p{L}\.\'\s\&\-\\\\\/\-]/u', '', $_REQUEST['lname'])));
	}

	if(strlen($_REQUEST['org']) > 0) {
		$sqlWhere .= sprintf(" AND o.Org_Name_Search LIKE '%s%%'", mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $_REQUEST['org'])));
	}

	if(strlen($_REQUEST['postcode']) > 0) {
		$sqlFrom .= sprintf("LEFT JOIN address AS a ON a.Address_ID=p.Address_ID ");
		$sqlWhere .= sprintf(" AND a.Zip_Search LIKE '%s%%'", mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $_REQUEST['postcode'])));
	}
		
	$page = new Page('Create New Enquiry', '');
	$page->Display('header');

	echo sprintf("Please select from below or create a new registration. When selecting from below be sure to confirm name, address and username information.<br /><br />");

	$table = new DataTable("customers");
	$table->SetSQL($sqlSelect.$sqlFrom.$sqlWhere.$sqlMisc);
	$table->AddField('ID#', 'Contact_ID', 'left');
	$table->AddField('Organisation', 'Org_Name', 'left');
	$table->AddField('Name', 'Contact_Name', 'left');
	$table->AddField('Active', 'Is_Active', 'center');
	$table->AddField('Username', 'Username', 'left');
	$table->AddField('Account Manager', 'Account_Manager', 'left');
	$table->AddLink("enquiry_create.php?action=use&cid=%s","<img src=\"./images/icon_edit_1.gif\" alt=\"Update Contact\" border=\"0\">","Customer_ID");
	$table->Order = "ASC";
	$table->SetMaxRows(25);
	$table->SetOrderBy("Customer_ID");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
	
	echo '<br />';
	echo '<input type="button" name="new registration" value="new registration" class="btn" onclick="window.location.href=\'enquiry_register.php\';" />';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function start(){
	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'find', 'alpha', 4, 4);
	$form->SetValue('action', 'find');
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('orderid', 'Order ID', 'text', '', 'numeric_unsigned', 1, 11, false);
	$form->AddField('contactid', 'Contact ID', 'text', '', 'numeric_unsigned', 1, 11, false);
	$form->AddField('fname', 'First Name', 'text', '', 'paragraph', 1, 255, false);
	$form->AddField('lname', 'Last Name', 'text', '', 'paragraph', 1, 255, false);
	$form->AddField('org', 'Organisation', 'text', '', 'paragraph', 1, 255, false);
	$form->AddField('postcode', 'Postcode', 'text', '', 'anything', 1, 32, false);

	$page = new Page('Create New Enquiry', '');
	$page->Display('header');

	$window = new StandardWindow("Select a customer.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHtml('action');
	echo $form->GetHtml('confirm');
	echo $window->Open();
	echo $window->AddHeader('Search for customers by any of the below fields.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('orderid'), $form->GetHTML('orderid'));
	echo $webForm->AddRow($form->GetLabel('contactid'), $form->GetHTML('contactid'));
	echo $webForm->AddRow($form->GetLabel('fname'), $form->GetHTML('fname'));
	echo $webForm->AddRow($form->GetLabel('lname'), $form->GetHTML('lname'));
	echo $webForm->AddRow($form->GetLabel('org'), $form->GetHTML('org'));
	echo $webForm->AddRow($form->GetLabel('postcode'), $form->GetHTML('postcode'));
	echo $webForm->AddRow('', '<input type="submit" name="searchButton" value="search" class="btn" />');
	echo $webForm->Close();

	echo $window->AddHeader('Create a new customer.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('', '<input type="button" name="new registration" value="new registration" class="btn" onclick="window.location.href=\'enquiry_register.php\';" />');
	echo $webForm->Close();

	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}