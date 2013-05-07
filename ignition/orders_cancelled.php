<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderContact.php');

if($action == 'contact') {
	$session->Secure(3);
	contact();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function view() {
	$page = new Page('Cancelled Orders', 'Listing all cancelled orders.');
	$page->Display('header');
	
	$table = new DataTable('orders');
	$table->SetSQL("SELECT o.*, pg.Postage_Title, pg.Postage_Days, DATE(MAX(oc.CreatedOn)) AS Contacted_On FROM orders AS o LEFT JOIN postage AS pg ON o.Postage_ID=pg.Postage_ID LEFT JOIN order_contact AS oc ON oc.OrderID=o.Order_ID WHERE o.Status IN ('Cancelled') GROUP BY o.Order_ID");
	$table->AddField('Order Date', 'Ordered_On', 'left');
	$table->AddField('Organisation', 'Billing_Organisation_Name', 'left');
	$table->AddField('Name', 'Billing_First_Name', 'left');
	$table->AddField('Surname', 'Billing_Last_Name', 'left');
	$table->AddField('Order Prefix', 'Order_Prefix', 'left');
	$table->AddField('Order Number', 'Order_ID', 'right');
	$table->AddField('Order Total', 'Total', 'right');
	$table->AddField('Postage', 'Postage_Title', 'left');
	$table->AddField('Contacted Date', 'Contacted_On', 'left');
	$table->AddLink("?action=contact&orderid=%s", "<img src=\"images/icon_clock_1.gif\" alt=\"Update Contacted Date\" border=\"0\" />", "Order_ID");
	$table->AddLink("order_details.php?orderid=%s", "<img src=\"images/folderopen.gif\" alt=\"Open Order Details\" border=\"0\" />", "Order_ID");
	$table->SetOrderBy("Ordered_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}


function contact() {
	if(isset($_REQUEST['orderid'])) {
		$contact = new OrderContact();
		$contact->OrderID = $_REQUEST['orderid'];
		$contact->Add();
	}

	redirect('Location: ?action=view');
}