<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
view();
exit;

function view() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Next Day Delivery Orders', 'Below is a list of all pending orders with next day delivery.');
	$page->Display('header');

	$sqlSelect = sprintf("SELECT o.*, IF(ol.Backorder_Expected_On='0000-00-00 00:00:00', '', ol.Backorder_Expected_On) AS Backorder_Date, CONCAT(o.Order_Prefix, o.Order_ID) AS Order_Number, CONCAT_WS(' ', TRIM(CONCAT_WS(' ', o.Billing_First_Name, o.Billing_Last_Name)), REPLACE(CONCAT('(', o.Billing_Organisation_Name, ')'), '()', '')) AS Billing_Contact, po.Postage_Title, po.Postage_Days ");
	$sqlFrom = sprintf("FROM orders AS o LEFT JOIN postage AS po ON o.Postage_ID=po.Postage_ID LEFT JOIN order_line AS ol ON o.Order_ID=ol.Order_ID AND (ol.Line_Status NOT LIKE 'Cancelled' AND ol.Line_Status NOT LIKE 'Despatched' AND ol.Line_Status NOT LIKE 'Invoiced') ");
	$sqlWhere = sprintf("WHERE o.Status NOT IN ('Cancelled', 'Despatched', 'Incomplete', 'Unauthenticated') AND (o.Postage_ID=9 OR o.Postage_ID=11) AND o.Is_Awaiting_Customer='N' ");
	$sqlGroup = sprintf("GROUP BY o.Order_ID");

	$table = new DataTable("orders");
	$table->SetSQL(sprintf('%s%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere, $sqlGroup));
	$table->AddBackgroundCondition('Is_Notes_Unread', 'Y', '==', '#99C5FF', '#77B0EE');
	$table->AddBackgroundCondition(array('Deadline_On', 'Deadline_On'), array(date('Y-m-d H:i:s'), '0000-00-00 00:00:00'), array('<', '>'), '#FFB3B3', '#FF9D9D');
	$table->AddBackgroundCondition('Postage_Days', '1', '==', '#FFF499', '#EEE177');
	$table->AddField('', 'Postage_Days', 'hidden');
	$table->AddField('Deadline', 'Deadline_On', 'hidden');
	$table->AddField('Unread Notes', 'Is_Notes_Unread', 'hidden');
	$table->AddField('Order Date', 'Created_On', 'left');
	$table->AddField('Order Number', 'Order_Number', 'left');
	$table->AddField('Contact', 'Billing_Contact', 'left');
	$table->AddField('Status', 'Status', 'left');
	$table->AddField('Postage', 'Postage_Title', 'left');
	$table->AddField('Total', 'Total', 'right');
	$table->AddField('Backordered', 'Backordered', 'center');
	$table->AddField('Expected', 'Backorder_Date', 'left');
	$table->AddLink("order_details.php?orderid=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open Order\" border=\"0\">", "Order_ID");
	$table->AddLink("javascript:popUrl('order_cancel.php?orderid=%s', 800, 600);", "<img src=\"images/aztector_6.gif\" alt=\"Cancel\" border=\"0\">", "Order_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Created_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}