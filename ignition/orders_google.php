<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$session->Secure(2);
view();
exit;

function view(){
	$page = new Page('Google Orders', 'Below is a list of all google orders, which require payment confirmation.');
	$page->Display('header');

	$sql = "SELECT o.*, pg.Postage_Title, pg.Postage_Days from orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'google' LEFT JOIN payment AS p ON p.Order_ID=o.Order_ID LEFT JOIN postage AS pg ON o.Postage_ID=pg.Postage_ID where p.Status IS NULL AND (o.Status LIKE 'Unread' OR o.Status LIKE 'Pending') AND o.Is_Awaiting_Customer='N'";
	$table = new DataTable("orders");
	$table->SetSQL($sql);
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
	$table->AddLink("order_details.php?orderid=%s", "<img src=\"images/folderopen.gif\" alt=\"Open Order Details\" border=\"0\">", "Order_ID");
	$table->AddLink("javascript:popUrl('order_cancel.php?orderid=%s', 800, 600);", "<img src=\"images/aztector_6.gif\" alt=\"Cancel\" border=\"0\">", "Order_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Ordered_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}