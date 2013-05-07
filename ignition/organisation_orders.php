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

	$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s</a> &gt; Order History for %s contacts', $contact->ID, $contact->Organisation->Name, $contact->Organisation->Name), sprintf('Below is the order history for all contacts of %s.', $contact->Organisation->Name));
	$page->Display('header');

	$sql = sprintf("SELECT o.*, p2.Name_First, p2.Name_Last from orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN person AS p2 ON p2.Person_ID=n.Person_ID WHERE n.Parent_Contact_ID=%d", $contact->ID);
	$table = new DataTable("orders");
	$table->SetSQL($sql);
	$table->AddField('Order Date', 'Ordered_On', 'left');
	$table->AddField('First Name', 'Name_First', 'left');
	$table->AddField('Last Name', 'Name_Last', 'left');
	$table->AddField('Order Prefix', 'Order_Prefix', 'left');
	$table->AddField('Order Number', 'Order_ID', 'right');
	$table->AddField('Order Total', 'Total', 'right');
	$table->AddField('Status', 'Status', 'right');
	$table->AddLink("order_details.php?orderid=%s","<img src=\"./images/folderopen.gif\" alt=\"Open Order Details\" border=\"0\">","Order_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Created_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function add(){
}

function update(){
}

function remove(){

}
?>