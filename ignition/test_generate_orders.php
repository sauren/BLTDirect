<?php
require_once('lib/common/app_header.php');

if($action == "step3"){
	$session->Secure(2);
	step3();
	exit;
} elseif($action == "step2"){
	$session->Secure(3);
	step2();
	exit;
} else {
	$session->Secure(2);
	step1();
	exit;
}

function step1() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Test.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerContact.php');

	$test = new Test();

	if(!$test->Get($_REQUEST['id'])) {
		echo '<script language="javascript" type="text/javascript">window.close();</script>';
		exit;
	}

	$page = new Page('Generate Dummy Orders', 'Generate dummy orders for suppliers and products of this test case.');
	$page->Display('header');

	$data = new DataQuery(sprintf("SELECT ts.TestSupplierID, ts.CustomerID, ts.CustomerContactID, w.Warehouse_Name FROM test_supplier AS ts INNER JOIN test_supplier_product AS tsp ON tsp.TestSupplierID=ts.TestSupplierID INNER JOIN warehouse AS w ON w.Type_Reference_ID=ts.SupplierID AND w.Type='S' WHERE ts.CustomerID>0 GROUP BY ts.TestSupplierID"));
	if($data->TotalRows > 0) {
		while($data->Row) {
			$customer = new Customer($data->Row['CustomerID']);
			$customer->Contact->Get();
			?>

			<h3><?php echo $data->Row['Warehouse_Name']; ?></h3>
			<br />

			<table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
				<tr>
					<td valign="top" class="billing">
						<p>
							<strong>Organisation/Indidivual:</strong><br />
							<?php
							echo $customer->Contact->Person->GetFullName();
							echo '<br />';
							echo $customer->Contact->Person->Address->GetFormatted('<br />');
							?>
						</p>
					</td>
					<td valign="top" class="shipping">
						<p>
							<strong>Shipping Address:</strong><br />

							<?php
							if($data->Row['CustomerContactID'] > 0) {
								$customerContact = new CustomerContact($data->Row['CustomerContactID']);

								echo $customerContact->GetFullName();
								echo '<br />';
								echo $customerContact->Address->GetFormatted('<br />');
							} else {
								echo $customer->Contact->Person->GetFullName();
								echo '<br />';
								echo $customer->Contact->Person->Address->GetFormatted('<br />');
							}
							?>
						</p>
					</td>
				</tr>
			</table>
			<br />

			<table border="0" cellpadding="4" cellspacing="0" class="orderDetails">
				<tr>
					<th>Quantity</th>
					<th>Product</th>
					<th>Quickfind</th>
				</tr>

				<?php
				$data2 = new DataQuery(sprintf("SELECT tsp.Quantity, p.Product_ID, p.Product_Title FROM test_supplier_product AS tsp INNER JOIN product AS p ON p.Product_ID=tsp.ProductID WHERE tsp.TestSupplierID=%d ORDER BY p.Product_Title ASC", $data->Row['TestSupplierID']));
				while($data2->Row) {
					?>

					<tr>
						<td><?php echo $data2->Row['Quantity']; ?></td>
						<td><?php echo strip_tags($data2->Row['Product_Title']); ?></td>
						<td><?php echo $data2->Row['Product_ID']; ?></td>
					</tr>

					<?php
					$data2->Next();
				}
				$data2->Disconnect();
				?>

			</table>
			<br />

			<?php
			$data->Next();
		}

		echo sprintf('<input type="button" name="continue" value="continue" class="btn" onclick="window.location.href=\'test_generate_orders.php?action=step2&id=%d\'" />', $test->ID);
	} else {
		echo 'There are no eligible suppliers for generating dummy orders against at this time.';
	}
	$data->Disconnect();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function step2() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Test.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerContact.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');

	$test = new Test();

	if(!$test->Get($_REQUEST['id'])) {
		echo '<script language="javascript" type="text/javascript">window.close();</script>';
		exit;
	}

	$orders = array();

	$data = new DataQuery(sprintf("SELECT ts.TestSupplierID, ts.SupplierID, ts.CustomerID, ts.CustomerContactID, w.Warehouse_ID FROM test_supplier AS ts INNER JOIN test_supplier_product AS tsp ON tsp.TestSupplierID=ts.TestSupplierID INNER JOIN warehouse AS w ON w.Type_Reference_ID=ts.SupplierID AND w.Type='S' WHERE ts.CustomerID>0 GROUP BY ts.TestSupplierID"));
	if($data->TotalRows > 0) {
		while($data->Row) {
			$customer = new Customer($data->Row['CustomerID']);
			$customer->Contact->Get();

			if($data->Row['CustomerContactID'] > 0) {
				$customerContact = new CustomerContact($data->Row['CustomerContactID']);

				$shipping = $customerContact->GetFullName();
				$shippingOrg = '';
			} else {
				$shipping = $customer->Contact->Person;
				$shippingOrg = ($customer->Contact->HasParent) ? $customer->Contact->Parent->Organisation->Name : '';
			}

			$order = new Order();
			$order->PaymentMethod->GetByReference('credit');
			$order->Prefix = 'D';
			$order->Customer = $customer;
			$order->Billing = $customer->Contact->Person;
			$order->BillingOrg = ($customer->Contact->HasParent) ? $customer->Contact->Parent->Organisation->Name : '';
			$order->Shipping = $shipping;
			$order->ShippingOrg = $shippingOrg;
			$order->Status = 'Packing';
			$order->Postage->ID = 1;
			$order->OrderedOn = date('Y-m-d H:i:s');
			$order->OwnedBy = $GLOBALS['SESSION_USER_ID'];
			$order->Add();

			$data2 = new DataQuery(sprintf("SELECT tsp.Quantity, tsp.ProductID FROM test_supplier_product AS tsp INNER JOIN product AS p ON p.Product_ID=tsp.ProductID WHERE tsp.TestSupplierID=%d ORDER BY p.Product_Title ASC", $data->Row['TestSupplierID']));
			while($data2->Row) {
				$order->AddLine($data2->Row['Quantity'], $data2->Row['ProductID']);

				$data2->Next();
			}
			$data2->Disconnect();

			$order->GetLines();

			for($i=0; $i<count($order->Line); $i++) {
				$data2 = new DataQuery(sprintf("SELECT sp.Cost FROM supplier_product AS sp WHERE sp.Product_ID=%d AND sp.Supplier_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID), $data->Row['SupplierID']));
				$cost = ($data2->TotalRows > 0) ? $data2->Row['Cost'] : 0;
				$data2->Disconnect();

				$order->Line[$i]->Cost = $cost;
				$order->Line[$i]->DespatchedFrom->ID = $data->Row['Warehouse_ID'];
				$order->Line[$i]->Update();
			}

			$order->Recalculate();

			$orders[] = $order->ID;

			$data->Next();
		}

		redirect(sprintf("Location: %s?action=step3&id=%d&orders=%s", $_SERVER['PHP_SELF'], $test->ID, implode(',', $orders)));
	}
	$data->Disconnect();
}

function step3() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Test.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');

	$test = new Test();

	if(!$test->Get($_REQUEST['id'])) {
		echo '<script language="javascript" type="text/javascript">window.close();</script>';
		exit;
	}

	$orders = explode(',', $_REQUEST['orders']);

	$page = new Page('Generate Dummy Orders', 'A barrage of dummy orders have been generated and sent to packing.');
	$page->Display('header');
	?>

	<table border="0" cellpadding="4" cellspacing="0" class="orderDetails">
		<tr>
			<th>Order</th>
			<th>Supplier</th>
			<th style="text-align: right;">Total</th>
		</tr>

		<?php
		foreach($orders as $orderId) {
			$order = new Order($orderId);
			$order->GetLines();
			?>

			<tr>
				<td><a href="order_details.php?orderid=<?php echo $order->ID; ?>" target="_blank"><?php echo sprintf('%s%d', $order->Prefix, $order->ID); ?></a></td>
				<td><?php echo $order->Line[0]->DespatchedFrom->Name; ?></td>
				<td align="right">&pound;<?php echo $order->Total; ?></td>
			</tr>

			<?php
		}
		?>

	</table>
	<br />

	<input type="button" name="close" value="close" class="btn" onclick="window.close();" />

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>