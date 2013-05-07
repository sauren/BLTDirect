<?php
require_once('lib/common/app_header.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'add'){
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'update'){
	$session->Secure(3);
	update();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

	$contact = new Contact($_REQUEST['ocid']);

	$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s</a> &gt; Invoice History for %s contacts', $contact->ID, $contact->Organisation->Name, $contact->Organisation->Name), sprintf('Below is the invoice history for all contacts of %s.', $contact->Organisation->Name));
	$page->Display('header');

	$sql = sprintf("SELECT i.*, p2.Name_First, p2.Name_Last from invoice AS i INNER JOIN customer AS c ON c.Customer_ID=i.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN person AS p2 ON p2.Person_ID=n.Person_ID WHERE n.Parent_Contact_ID=%d", $contact->ID);
	$table = new DataTable("invoices");
	$table->SetSQL($sql);
	$table->AddField('Invoice Date', 'Created_On', 'left');
	$table->AddField('First Name', 'Name_First', 'left');
	$table->AddField('Last Name', 'Name_Last', 'left');
	$table->AddField('Invoice Due', 'Invoice_Due_On', 'left');
	$table->AddField('Invoice Number', 'Invoice_ID', 'right');
	$table->AddField('Invoice Total', 'Invoice_Total', 'right');
	$table->AddLink("invoice_view.php?invoiceid=%s",
							"<img src=\"./images/folderopen.gif\" alt=\"Open Invoice Details\" border=\"0\">",
							"Invoice_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Created_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function add(){
}

function update(){
}

function remove(){

}