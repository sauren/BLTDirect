<?php
require_once('lib/common/app_header.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
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

	$sql = sprintf("SELECT cp.Customer_Product_ID, p.SKU, p.Product_ID, p.Product_Title, p2.Name_First, p2.Name_Last from customer_product as cp inner join product AS p ON p.Product_ID=cp.Product_ID INNER JOIN customer AS c ON c.Customer_ID=cp.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN person AS p2 ON p2.Person_ID=n.Person_ID WHERE n.Parent_Contact_ID=%d", mysql_real_escape_string($contact->ID));
	$table = new DataTable("invoices");
	$table->SetSQL($sql);
	$table->AddField('Quickfind', 'Product_ID', 'left');
	$table->AddField('First Name', 'Name_First', 'left');
	$table->AddField('Last Name', 'Name_Last', 'left');
	$table->AddField('Product Title', 'Product_Title', 'left');
	$table->AddField('SKU', 'SKU', 'left');
	$table->AddLink("product_profile.php?pid=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open Product Details\" border=\"0\">", "Product_ID");
	$table->AddLink("javascript:confirmRequest('?action=remove&id=%s&ocid=".$contact->ID."','Are you sure you want to remove this product from the customers collection? Note: you will NOT lose any product information by performing this operation.');","<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">","Customer_Product_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Product_ID");
	$table->Order = "ASC";
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function remove(){
	if(isset($_REQUEST['id'])) {
		new DataQuery(sprintf("DELETE FROM customer_product WHERE Customer_Product_ID=%d", mysql_real_escape_string($_REQUEST['id'])));
	}

	redirect(sprintf("Location: ?ocid=%d", $_REQUEST['ocid']));
}