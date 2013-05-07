<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$session->Secure(2);
view();
exit;

function view() {
	$page = new Page('Bidding Orders', 'Listing all orders currently available for bidding.');
	$page->Display('header');

	$table = new DataTable('orders');
	$table->SetSQL("SELECT o.*, pg.Postage_Title, pg.Postage_Days, COUNT(ob.OrderBiddingID) AS Bids FROM orders AS o LEFT JOIN postage AS pg ON o.Postage_ID=pg.Postage_ID LEFT JOIN order_bidding AS ob ON ob.OrderID=o.Order_ID AND ob.IsAccepted='N' WHERE o.Status LIKE 'Pending' AND o.Is_Bidding='Y' GROUP BY o.Order_ID");
    $table->AddBackgroundCondition('Bids', '0', '>', '#99C5FF', '#77B0EE');
    $table->AddBackgroundCondition('Postage_Days', '1', '==', '#FFF499', '#EEE177');
	$table->AddField('', 'Postage_Days', 'hidden');
    $table->AddField('', 'Bids', 'hidden');
	$table->AddField('Order Date', 'Ordered_On', 'left');
	$table->AddField('Organisation', 'Billing_Organisation_Name', 'left');
	$table->AddField('Name', 'Billing_First_Name', 'left');
	$table->AddField('Surname', 'Billing_Last_Name', 'left');
	$table->AddField('Order Prefix', 'Order_Prefix', 'left');
	$table->AddField('Order Number', 'Order_ID', 'right');
	$table->AddField('Order Total', 'Total', 'right');
	$table->AddField('Postage', 'Postage_Title', 'left');
	$table->AddLink("order_details.php?orderid=%s", "<img src=\"images/folderopen.gif\" alt=\"Open Order Details\" border=\"0\" />", "Order_ID");
	$table->AddLink("javascript:popUrl('order_cancel.php?orderid=%s', 800, 600);", "<img src=\"images/aztector_6.gif\" alt=\"Cancel\" border=\"0\">", "Order_ID");
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