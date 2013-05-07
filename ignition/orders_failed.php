<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
view();
exit;

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

	$page = new Page('Orders Failed', 'Below is a list of all the failed orders available for viewing.');
	$page->Display('header');

	$table = new DataTable("orders");
	$table->SetSQL("SELECT o.*, pg.Postage_Title, pg.Postage_Days, MAX(oc.CreatedOn) AS Contacted_On FROM orders AS o LEFT JOIN postage AS pg ON o.Postage_ID=pg.Postage_ID LEFT JOIN order_contact AS oc ON oc.OrderID=o.Order_ID WHERE o.Status NOT LIKE 'Despatched' AND o.Status NOT LIKE 'Cancelled' AND o.Is_Failed='Y' AND o.Is_Awaiting_Customer='N' GROUP BY o.Order_ID");
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
	$table->AddField('Contacted Date', 'Contacted_On', 'left');
	$table->AddLink("order_details.php?orderid=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open Order Details\" border=\"0\">", "Order_ID");
	$table->AddLink("javascript:popUrl('order_cancel.php?orderid=%s', 800, 600);", "<img src=\"images/aztector_6.gif\" alt=\"Cancel\" border=\"0\">", "Order_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Ordered_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}