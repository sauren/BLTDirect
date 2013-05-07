<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');

if($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function view() {
	$page = new Page('Tax Exempt Orders', 'Listing all orders with tax exemption codes pending verification.');
	$page->Display('header');

	$table = new DataTable('orders');
	$table->SetSQL("SELECT o.*, pg.Postage_Title, pg.Postage_Days FROM orders AS o LEFT JOIN postage AS pg ON o.Postage_ID=pg.Postage_ID WHERE o.Status NOT IN ('Cancelled', 'incomplete', 'Unauthenticated', 'Despatched') AND o.TaxExemptCode<>'' AND o.IsTaxExemptValid='N' GROUP BY o.Order_ID");
	$table->AddBackgroundCondition('Postage_Days', '1', '==', '#FFF499', '#EEE177');
	$table->AddField('', 'Postage_Days', 'hidden');
	$table->AddField('Order Date', 'Ordered_On', 'left');
	$table->AddField('Organisation', 'Billing_Organisation_Name', 'left');
	$table->AddField('Name', 'Billing_First_Name', 'left');
	$table->AddField('Surname', 'Billing_Last_Name', 'left');
	$table->AddField('Order Prefix', 'Order_Prefix', 'left');
	$table->AddField('Order Number', 'Order_ID', 'right');
	$table->AddField('Order Total', 'Total', 'right');
	$table->AddField('Postage', 'Postage_Title', 'left');
	$table->AddLink("order_details.php?orderid=%s", "<img src=\"images/folderopen.gif\" alt=\"Open Order Details\" border=\"0\" />", "Order_ID");
	$table->AddLink("javascript:confirmRequest('orders_new.php?action=remove&orderid=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\" />", "Order_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Ordered_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function remove() {
	if(isset($_REQUEST['orderid'])) {
		$order = new Order();
		$order->Delete($_REQUEST['orderid']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}