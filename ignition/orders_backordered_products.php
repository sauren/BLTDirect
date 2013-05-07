<?php
require_once('lib/common/app_header.php');

if($action == 'vieworders'){
	$session->Secure(3);
	viewOrders();
	exit;
} elseif($action == 'send') {
	$session->Secure(2);
	send();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Backordered Products', 'Listing products marked for backordering.');
	$page->Display('header');

	$table = new DataTable('products');
	$table->SetSQL(sprintf("SELECT p.Product_ID, p.SKU, p.Product_Title, w.Warehouse_Name, ws.Stock_ID, ws.Backorder_Expected_On, COUNT(DISTINCT o.Order_ID) AS Order_Count FROM product AS p INNER JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID LEFT JOIN order_line AS ol ON ol.Product_ID=p.Product_ID AND ol.Despatch_From_ID=w.Warehouse_ID AND ol.Line_Status NOT LIKE 'Invoiced' AND ol.Line_Status NOT LIKE 'Cancelled' AND ol.Line_Status NOT LIKE 'Despatched' LEFT JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Is_Warehouse_Backordered='Y' WHERE ws.Is_Backordered='Y' GROUP BY p.Product_ID"));
	$table->AddField('ID#', 'Product_ID', 'right');
	$table->AddField('SKU', 'SKU', 'left');
	$table->AddField('Product', 'Product_Title', 'left');
	$table->AddField('Warehouse', 'Warehouse_Name', 'left');
	$table->AddField('Expected On', 'Backorder_Expected_On', 'left');
	$table->AddField('Affected Orders', 'Order_Count', 'left');
	$table->AddLink(sprintf("warehouse_stock_edit.php?sid=%%s&direct=%s", urlencode(sprintf('%s%s', $_SERVER['PHP_SELF'], (strlen($_SERVER['QUERY_STRING']) > 0) ? sprintf('?%s', $_SERVER['QUERY_STRING']) : ''))), "<img src=\"./images/icon_edit_1.gif\" alt=\"Update Stock Settings\" border=\"0\">", "Stock_ID");
	$table->AddLink("orders_backordered_products.php?action=vieworders&sid=%s", "<img src=\"./images/folderopen.gif\" alt=\"View Orders\" border=\"0\">", "Stock_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy('Product_ID');
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function viewOrders(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseStock.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');

	if(!isset($_REQUEST['sid'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$stock = new WarehouseStock();
	if(!$stock->Get($_REQUEST['sid'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$page = new Page(sprintf('<a href="%s">Backordered Products</a> &gt; Orders On Backorder', $_SERVER['PHP_SELF']), 'Listing orders for the selected product for backordering.');
	$page->Display('header');

	if(isset($_REQUEST['status']) && ($_REQUEST['status'] == 'sent')) {
		$recipients = isset($_REQUEST['recipients']) ? $_REQUEST['recipients'] : 0;

		if($recipients > 0) {
			$bubble = new Bubble('Notifications successfully sent!', sprintf('<em>%d</em> updated notifications were successfully sent.', $recipients));
		} else {
			$bubble = new Bubble('Notifications unrequired!', 'There were no updated notifications to send.');
		}

		echo $bubble->GetHTML();
		echo '<br />';
	}

	$table = new DataTable('orders');
	$table->SetSQL(sprintf("SELECT o.*, pg.Postage_Title, pg.Postage_Days FROM orders AS o LEFT JOIN postage AS pg ON o.Postage_ID=pg.Postage_ID INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Product_ID=%d AND ol.Despatch_From_ID=%d AND ol.Line_Status NOT LIKE 'Invoiced' AND ol.Line_Status NOT LIKE 'Cancelled' AND ol.Line_Status NOT LIKE 'Despatched' WHERE o.Is_Warehouse_Backordered='Y' GROUP BY o.Order_ID", mysql_real_escape_string($stock->Product->ID), mysql_real_escape_string($stock->Warehouse->ID)));
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
	$table->AddLink("order_details.php?orderid=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open Order Details\" border=\"0\">", "Order_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy('Ordered_On');
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo sprintf('<input type="button" class="btn" name="backorder" value="send new notification" onclick="window.self.location.href = \'%s?action=send&sid=%d\';" />', $_SERVER['PHP_SELF'], $stock->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function send() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseStock.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderNote.php');
	require_once($GLOBALS['DIR_WS_ADMIN'] . 'services/google-checkout/classes/GoogleRequest.php');

	if(!isset($_REQUEST['sid'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$stock = new WarehouseStock();
	if(!$stock->Get($_REQUEST['sid'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$recipients = 0;

	if(strtotime($stock->BackorderExpectedOn) > time()) {
		$order = new Order();
		$line = new OrderLine();

		$data = new DataQuery(sprintf("SELECT o.Order_ID, ol.Order_Line_ID FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Product_ID=%d AND ol.Despatch_From_ID=%d AND ol.Line_Status NOT LIKE 'Invoiced' AND ol.Line_Status NOT LIKE 'Cancelled' AND ol.Line_Status NOT LIKE 'Despatched' WHERE o.Is_Warehouse_Backordered='Y' GROUP BY o.Order_ID", mysql_real_escape_string($stock->Product->ID), mysql_real_escape_string($stock->Warehouse->ID)));
		while($data->Row) {
			$order->Get($data->Row['Order_ID']);
			$order->Customer->Get();
			$order->Customer->Contact->Get();

			$line->Get($data->Row['Order_Line_ID']);

			if($line->BackorderExpectedOn != $stock->BackorderExpectedOn) {
				$expected = strtotime($stock->BackorderExpectedOn);
				$now = strtotime(date('Y-m-d 00:00:00'));

				$delay = $expected - $now;
				$days = $delay / 86400;

				$note = new OrderNote();
				$note->Message = sprintf('<strong>%s</strong><br />Quickfind Code: %d<br /><br />', $line->Product->Name, $line->Product->ID);
				$note->Message .= sprintf('This product is currently out of stock, delivery on this product will now be %d days.  Please visit your <a href="https://www.bltdirect.com/orders.php" target="_blank">orders</a> within your account centre should you wish to cancel this order.', $days);
				$note->TypeID = 7;
				$note->OrderID = $order->ID;
				$note->IsPublic = 'Y';
				$note->IsAlert = 'N';
				$note->Add();

				$note->SendToCustomer($order->Customer->Contact->Person->GetFullName(), $order->Customer->GetEmail());

				if($order->PaymentMethod->Reference == 'google') {
					if($line->BackorderExpectedOn > '0000-00-00 00:00:00') {
						$googleRequest = new GoogleRequest();
						$googleRequest->sendBuyerMessage($order->CustomID, sprintf('The product %s is currently out of stock, delivery of this product is now expected on %s (%d days).', $line->Product->Name, date('d/m/Y', $expected), $days));
					} else {
						$googleRequest = new GoogleRequest();
						$googleRequest->sendBuyerMessage($order->CustomID, sprintf('The product %s is currently out of stock, delivery of this product is expected on %s (%d days).', $line->Product->Name, cDatetime($expected, 'shortdate'), $days));
						$googleRequest->backorderItems($order->CustomID, array($line->Product->ID));
					}
				}

				$order->Backorder();

				$line->Status = 'Backordered';
				$line->BackorderExpectedOn = $stock->BackorderExpectedOn;
				$line->Update();

				$recipients++;
			}

			$data->Next();
		}
		$data->Disconnect();
	}

	redirect(sprintf("Location: %s?action=vieworders&sid=%d&status=sent&recipients=%d", $_SERVER['PHP_SELF'], $stock->ID, $recipients));
}