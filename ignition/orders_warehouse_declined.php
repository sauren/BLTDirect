<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
view();
exit;

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

	$page = new Page('Orders Warehouse Declined', 'Below is a list of all the warehouse declined orders available for viewing.');
	$page->Display('header');

	$table = new DataTable("orders");
	$table->SetSQL("SELECT o.*, pg.Postage_Days, CONCAT(o.Order_Prefix, o.Order_ID) AS Order_Number, CONCAT_WS(' ', Billing_First_Name, Billing_Last_Name) AS Billing_Name, own2.Note FROM orders AS o LEFT JOIN postage AS pg ON pg.Postage_ID=o.Postage_ID LEFT JOIN (SELECT MAX(Order_Warehouse_Note_ID) AS Order_Warehouse_Note_ID, Order_ID FROM order_warehouse_note GROUP BY Order_ID) AS own ON own.Order_ID=o.Order_ID LEFT JOIN order_warehouse_note AS own2 ON own2.Order_Warehouse_Note_ID=own.Order_Warehouse_Note_ID WHERE o.Status NOT LIKE 'Despatched' AND o.Status NOT LIKE 'Cancelled' AND o.Is_Warehouse_Declined='Y' AND o.Is_Awaiting_Customer='N'");
	$table->AddBackgroundCondition('Is_Warehouse_Declined_Read', 'N', '==', '#99C5FF', '#77B0EE');
	$table->AddBackgroundCondition('Postage_Days', '1', '==', '#FFF499', '#EEE177');
	$table->AddField('', 'Postage_Days', 'hidden');
	$table->AddField('', 'Is_Warehouse_Declined_Read', 'hidden');
	$table->AddField('Order Date', 'Ordered_On', 'left');
	$table->AddField('Organisation', 'Billing_Organisation_Name', 'left');
	$table->AddField('Name', 'Billing_Name', 'left');
	$table->AddField('Order Number', 'Order_Number', 'left');
	$table->AddField('Order Total', 'Total', 'right');
	$table->AddField('Last Note', 'Note', 'left');
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