<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Return.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Page.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataTable.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Order.php');

$session->Secure(3);

$order = new Order($_REQUEST['orderid']);
$order->GetLines();

$page = new Page(sprintf('<a href="order_details.php?orderid=%d">Order #%s%d</a> &gt; Return History', $order->ID, $order->Prefix, $order->ID), '');
$page->Display('header');

$lines = array();

foreach($order->Line as $l) {
	$lines[] = $l->ID;
}

if(count($lines) > 0) {
	if(count($lines) > 1) {
		$where .= sprintf('WHERE r.Order_Line_ID=%s', implode(' OR r.Order_Line_ID=', $lines));
	} elseif(count($lines) == 1) {
		$where .= sprintf('WHERE r.Order_Line_ID=%d', $lines[0]);
	}

	$table = new DataTable('Returns');
	$table->SetSQL(sprintf("SELECT r.Return_ID, r.Status, r.Requested_On, r.Return_ID, r.Note, r.Admin_Note, rr.Reason_Title FROM `return` AS r LEFT JOIN return_reason AS rr ON r.Reason_ID=rr.Reason_ID %s", $where));
	$table->AddField('Return ID', 'Return_ID', 'right');
	$table->AddField('Status', 'Status', 'left');
	$table->AddField('Reason', 'Reason_Title', 'left');
	$table->AddField('Customer Note', 'Note', 'left');
	$table->AddField('Admin Note', 'Admin_Note', 'left');
	$table->AddLink('return_details.php?id=%s', '<img src="./images/folderopen.gif" alt="Open Return Details" border="0">', 'Return_ID');
	$table->SetMaxRows(25);
	$table->SetOrderBy('Requested_On');
	$table->Finalise();
	$table->DisplayTable();
	echo "<br/>";
	$table->DisplayNavigation();
} else {
	echo '<p>There are no order lines for this order.</p>';
}

$page->Display('footer');
require_once('lib/common/app_footer.php');
?>