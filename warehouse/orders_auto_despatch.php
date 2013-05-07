<?php
require_once('lib/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Despatch.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierShippingCalculator.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ShippingCostCalculator.php');

$session->Secure();

$data = new DataQuery(sprintf("SELECT w.Warehouse_ID FROM warehouse AS w INNER JOIN branch AS b ON b.Branch_ID=w.Type_Reference_ID AND b.Is_HQ='Y' WHERE w.Type='B'"));
$warehouseId = ($data->TotalRows > 0) ? $data->Row['Warehouse_ID'] : 0;
$data->Disconnect();

if($session->Warehouse->ID != $warehouseId) {
	redirect(sprintf("Location: index.php"));
}

if($action == 'despatch') {
	$session->Secure(3);
	despatch();
	exit;
} elseif($action == 'complete') {
	$session->Secure(2);
	complete();
	exit;
} elseif($action == 'print') {
	$session->Secure(2);
	printDocuments();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function printDocuments() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Despatch.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Invoice.php');

	$validOrders = isset($_REQUEST['orders']) ? unserialize($_REQUEST['orders']) : array();
	$invoiceNotes = isset($_REQUEST['invoices']) ? unserialize($_REQUEST['invoices']) : array();
	$despatchNotes = isset($_REQUEST['despatches']) ? unserialize($_REQUEST['despatches']) : array();

	$printHtml = '';

	for($i=0; $i<count($validOrders); $i++) {
		$order = new Order($validOrders[$i]);

		$despatch = new Despatch($despatchNotes[$i]);
		$despatch->IsIgnition = true;
		$despatch->ShowCustom = false;

		$invoiceId = isset($invoiceNotes[$i]) ? $invoiceNotes[$i] : 0;

		$printHtml .= $despatch->GetDocument(($order->IsPlainLabel == 'N') ? true : false);

		$despatch->EmailCustomer();

		if($invoiceId > 0) {
			if($order->Sample == 'N') {
				$invoice = new Invoice($invoiceId);
				$invoice->ShowCustom = false;

				$printHtml .= '<br style="page-break-after:always" />';
				$printHtml .= $invoice->GetDocument();

				$invoice->EmailCustomer();
			}
		}

		$printHtml .= '<br style="page-break-after:always" />';
	}

	echo sprintf('<html>
		<script language="javascript" type="text/javascript">
			window.onload = function() {
				window.self.print();
			}
		</script>
		<body>
			%s
		</body>
		</html>', $printHtml);
}

function complete() {
	$failed = isset($_REQUEST['failures']) ? unserialize($_REQUEST['failures']) : array();
	?>

	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/templates/portal-warehouse.dwt" codeOutsideHTMLIsLocked="false" -->
	<head>
		<!-- InstanceBeginEditable name="doctitle" -->
		<title>Warehouse Portal</title>
		<!-- InstanceEndEditable -->
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link href="/ignition/css/i_content.css" rel="stylesheet" type="text/css" media="screen" />
		<link href="/warehouse/css/lightbulbs.css" rel="stylesheet" type="text/css" media="screen" />
		<link href="/warehouse/css/lightbulbs_print.css" rel="stylesheet" type="text/css" media="print" />
		<link href="/warehouse/css/default.css" rel="stylesheet" type="text/css" media="screen" />
		<script language="javascript" src="/warehouse/js/generic_1.js" type="text/javascript"></script>
		<script language="javascript" type="text/javascript">
		var toggleGroup = function(group) {
			var e = document.getElementById(group);
			if(e) {
				if(e.style.display == 'none') {
					e.style.display = 'block';
				} else {
					e.style.display = 'none';
				}
			}
		}
		</script>
	    <!-- InstanceBeginEditable name="head" -->
	    <!-- InstanceEndEditable -->
	</head>
	<body>
	<div id="Wrapper">
		<div id="Header">
			<a href="/warehouse" title="Back to Home Page"><img src="/images/template/logo_blt_1.jpg" width="185" height="70" border="0" class="logo" alt="BLT Direct Logo" /></a>
			<div id="NavBar" class="warehouse">Warehouse Portal</div>
			<div id="CapTop" class="warehouse">
				<div class="curveLeft"></div>
			</div>
			<ul id="NavTop" class="nav warehouse">
				<?php if($session->IsLoggedIn){
					echo sprintf('<li class="login"><a href="%s?action=logout" title="Logout">Logout</a></li>', $_SERVER['PHP_SELF']);
				} else {
					echo '<li class="login"><a href="/index.php" title="Login as a BLT Direct supplier or warehouse">Login</a></li>';
				}?>
				<li class="account"><a href="/warehouse/account_settings.php" title="Your BLT Direct Account">My Account</a></li>
				<li class="contact"><a href="/support.php" title="Contact BLT Direct">Contact Us</a></li>
				<li class="help"><a href="/support.php" title="Light Bulb, Lamp and Tube Help">Help</a></li>
			</ul>
		</div>

	<div id="PageWrapper">
		<div id="Page">
			<div id="PageContent"><!-- InstanceBeginEditable name="pageContent" -->

			<?php
			if(count($failed) > 0) {
				echo '<h1>Despatch Errors</h1><br />';
				echo '<p>A pop-up will now appear with all relevant documentation for printing for all successfully despatched orders.</p>';
				echo '<p><strong><u>Errors:</u></strong></p>';

				foreach($failed as $orderId=>$errors) {
					echo sprintf('<p><strong>Order #%d:</strong></p>', $orderId);

					foreach($errors as $error) {
						echo $error;
					}
				}
			} else {
				echo '<h1>Despatch Successful</h1><br />';
				echo '<p>A pop-up will now appear with all relevant documentation for printing.</p>';
			}

			echo sprintf('<script language="javascript" type="text/javascript">
					popUrl(\'%s?action=print&invoices=%s&despatches=%s&orders=%s\', 600, 500);
				</script>', $_SERVER['PHP_SELF'], urlencode($_REQUEST['invoices']), urlencode($_REQUEST['despatches']), urlencode($_REQUEST['orders']));
			?>

			<!-- InstanceEndEditable -->
			</div>
	  	</div>

		<div id="PageFooter">
			<ul class="links">
				<li><a href="/privacy.php" title="BLT Direct Privacy Policy">Privacy Policy</a></li>
				<li><a href="/support.php" title="Contact BLT Direct">Contact Us</a></li>
			</ul>
			<p class="copyright">Copyright &copy; BLT Direct, 2005. All Right Reserved.</p>
		</div>
	</div>

		<div id="LeftNav">
			<div id="CatalogueNav" class="greyNavLeft">
				<div id="NavLeftItems" class="warehouse">
				<p class="title"><strong>Warehouse Options </strong> </p>

				<ul class="rootCat">
					<?php
					if(($session->Warehouse->Type == 'B') || (($session->Warehouse->Type == 'S') && ($session->Warehouse->Contact->IsStockerOnly == 'N'))) {
						?>
						<li><a href="/warehouse/orders_pending.php">Pending Orders</a></li>

                        <?php
						if($session->Warehouse->Type == 'B') {
							echo '<li><a href="/warehouse/orders_pending_tax_free.php">Pending Orders<br />(Tax Free)</a></li>';
						}
						?>
                    
                    	<li><a href="/warehouse/orders_collections.php">Collection Orders</a></li>
						<li><a href="/warehouse/orders_backordered.php">Backordered Orders</a></li>
	                    
	                    <?php
						if(($session->Warehouse->Type == 'S') && ($session->Warehouse->Contact->IsBidder == 'Y')) {
							echo '<li><a href="/warehouse/orders_bidding.php">Bidding Orders</a></li>';
						}
						
						if($session->Warehouse->Type == 'S') {
							echo '<li><a href="/warehouse/orders_warehouse_declined.php">Warehouse Declined Orders</a></li>';
						}
						?>

						<li><a href="/warehouse/orders_despatched.php">Despatched Orders</a></li>
						<li><a href="/warehouse/orders_search.php">Search Orders</a></li>
						<?php
					}

					if($session->Warehouse->Type == 'B') {
						?>
						<li><a href="/warehouse/orders_auto_despatch.php">Auto Despatch Orders</a></li>
						<?php
					}
					?>
                
	                <li><a href="/warehouse/products_backordered.php">Products Backordered</a></li>
	                
	                <?php
					if(($session->Warehouse->Type == 'B') || (($session->Warehouse->Type == 'S') && ($session->Warehouse->Contact->IsStockerOnly == 'N'))) {
						?>

						<li><a href="/warehouse/despatches_track.php">Track Consignments</a></li>
						<li><a href="/warehouse/products_stocked.php">Stocked Products</a></li>

						<?php
					}

					if(($session->Warehouse->Type == 'S') && ($session->Warehouse->Contact->IsStockerOnly == 'N')) {
						?>

						<li><a href="/warehouse/products_held.php">Products Held</a></li>
						<li><a href="/warehouse/products_supplied.php">Products Supplied</a></li>

						<?php
						$supplier = new Supplier($session->Warehouse->Contact->ID);

						if($supplier->IsComparable == 'Y') {
							?>

							<li><a href="/warehouse/products_unsupplied.php">Unsupplied Products</a></li>

							<?php
						}
					}

					if($session->Warehouse->Type == 'B') {
						?>

						<li><a href="/warehouse/timesheets.php">Timesheets</a></li>

						<li><a href="javascript:toggleGroup('navLabels');" target="_self">Labels</a></li>
	                    <ul id="navLabels" class="subCat" style="display: none;">
	                        <li><a href="/warehouse/downloads/2nd-class-stamp.pdf" target="_blank">2<sup>nd</sup> Class Stamps</a></li>
	                    </ul>
                    
						<?php
					}

					if($session->Warehouse->Type == 'S') {
						?>

						<li><a href="javascript:toggleGroup('navReserves');" target="_self">Reserves</a></li>
						<ul id="navReserves" class="subCat" style="display: none;">
							<li><a href="/warehouse/reserves_pending.php">Pending</a></li>
							<li><a href="/warehouse/reserves_completed.php">Completed</a></li>
						</ul>

						<li><a href="javascript:toggleGroup('navPriceEnquiries');" target="_self">Price Enquiries</a></li>
						<ul id="navPriceEnquiries" class="subCat" style="display: none;">
							<li><a href="/warehouse/price_enquiries_pending.php">Pending</a></li>
							<li><a href="/warehouse/price_enquiries_completed.php">Completed</a></li>
						</ul>

						<li><a href="javascript:toggleGroup('navPurchaseRequests');" target="_self">Purchase Requests</a></li>
						<ul id="navPurchaseRequests" class="subCat" style="display: none;">
	                    	<li><a href="/warehouse/purchase_requests_pending.php">Pending</a></li>
	                    	<li><a href="/warehouse/purchase_requests_confirmed.php">Confirmed</a></li>
	                    	<li><a href="/warehouse/purchase_requests_completed.php">Completed</a></li>
						</ul>

						<li><a href="javascript:toggleGroup('navPurchaseOrders');" target="_self">Purchase Orders</a></li>
						<ul id="navPurchaseOrders" class="subCat" style="display: none;">
	                    	<li><a href="/warehouse/purchase_orders_unfulfilled.php">Unfulfilled</a></li>
	                    	<li><a href="/warehouse/purchase_orders_fulfilled.php">Fulfilled</a></li>
						</ul>

						<li><a href="javascript:toggleGroup('navReturnRequests');" target="_self">Return Requests</a></li>
						<ul id="navReturnRequests" class="subCat" style="display: none;">
	                    	<li><a href="/warehouse/supplier_return_requests_pending.php">Pending</a></li>
	                    	<li><a href="/warehouse/supplier_return_requests_confirmed.php">Confirmed</a></li>
	                    	<li><a href="/warehouse/supplier_return_requests_completed.php">Completed</a></li>
						</ul>

						<li><a href="/warehouse/supplier_return_requests_pending_purchase.php">Damages</a></li>

                        <li><a href="javascript:toggleGroup('navInvoiceQueries');" target="_self">Invoice Queries</a></li>
						<ul id="navInvoiceQueries" class="subCat" style="display: none;">
	                    	<li><a href="/warehouse/supplier_invoice_queries_pending.php">Pending</a></li>
	                    	<li><a href="/warehouse/supplier_invoice_queries_resolved.php">Resolved</a></li>
						</ul>

                        <li><a href="javascript:toggleGroup('navDebits');" target="_self">Debits</a></li>
						<ul id="navDebits" class="subCat" style="display: none;">
                    		<li><a href="/warehouse/debits_pending.php">Pending</a></li>
                    		<li><a href="/warehouse/debits_completed.php">Completed</a></li>
						</ul>

                        <li><a href="javascript:toggleGroup('navReports');" target="_self">Reports</a></li>
						<ul id="navReports" class="subCat" style="display: none;">
                    		<li><a href="/warehouse/report_orders_despatched.php">Orders Despatched</a></li>
                    		<li><a href="/warehouse/report_reserved_stock.php">Reserved Stock</a></li>
                    		<li><a href="/warehouse/report_stock_dropped.php">Stock Dropped</a></li>
						</ul>

						<?php
					}
					?>

					<li><a href="/warehouse/account_settings.php">Account Settings</a></li>
				</ul>
				</div>
				<div class="cap"></div>
				<div class="shadow"></div>
			</div>
		</div>
	</div>
	</body>
	<!-- InstanceEnd --></html>

	<?php
}

function despatch() {
	$data = new DataQuery(sprintf("SELECT w.Warehouse_ID FROM warehouse AS w INNER JOIN branch AS b ON b.Branch_ID=w.Type_Reference_ID AND b.Is_HQ='Y' WHERE w.Type='B'"));
	$warehouseId = ($data->TotalRows > 0) ? $data->Row['Warehouse_ID'] : 0;
	$data->Disconnect();

	if($warehouseId > 0) {
		$orders = array();

		new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_order SELECT o.Order_ID FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN postage AS p ON p.Postage_ID=o.Postage_ID AND p.Postage_Days>1 WHERE (o.Status LIKE 'Packing' OR o.Status LIKE 'Partially Despatched') AND o.Free_Text_Value=0 AND ol.Despatch_From_ID=%d AND ol.Despatch_ID=0 AND ol.Line_Status NOT LIKE 'Cancelled' AND o.Is_Declined='N' AND o.Is_Warehouse_Declined='N' GROUP BY o.Order_ID", mysql_real_escape_string($warehouseId)));
		new DataQuery(sprintf("ALTER TABLE temp_order ADD PRIMARY KEY (Order_ID)"));

		$data = new DataQuery(sprintf("SELECT o.Order_ID, ol.Order_Line_ID, ol.Quantity, pd.Is_Warehouse_Shipped, SUM(ws.Quantity_In_Stock) AS Quantity_In_Stock FROM temp_order AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN product AS pd ON pd.Product_ID=ol.Product_ID LEFT JOIN warehouse_stock AS ws ON ws.Product_ID=ol.Product_ID AND ws.Warehouse_ID=%d WHERE ol.Despatch_From_ID=%d AND ol.Despatch_ID=0 AND ol.Line_Status NOT LIKE 'Cancelled' GROUP BY ol.Order_Line_ID", mysql_real_escape_string($warehouseId), mysql_real_escape_string($warehouseId)));
		while($data->Row) {
			if(!isset($orders[$data->Row['Order_ID']])) {
				$orders[$data->Row['Order_ID']] = array();
			}

			$orders[$data->Row['Order_ID']][] = $data->Row;

			$data->Next();
		}
		$data->Disconnect();

		foreach($orders as $orderId=>$orderLines) {
			$canDespatch = true;

			foreach($orderLines as $orderLine) {
				$canDespatchLine = false;

				if($orderLine['Is_Warehouse_Shipped'] == 'Y') {
					if(($orderLine['Quantity_In_Stock'] - $orderLine['Quantity']) >= 10) {
						$canDespatchLine = true;
					}
				}

				if(!$canDespatchLine) {
					$canDespatch = false;
					break;
				}
			}

			if(!$canDespatch) {
				new DataQuery(sprintf("DELETE FROM temp_order WHERE Order_ID=%d", mysql_real_escape_string($orderId)));

				unset($orders[$orderId]);
			}
		}
	}

	$index = 0;
	$failed = array();
	$validOrders = array();
	$despatchNotes = array();
	$invoiceNotes = array();

	foreach($orders as $orderId=>$orderLines) {
		$order = new Order($orderId);

		$lines = array();

		$data = new DataQuery(sprintf("SELECT ol.Quantity, pd.Is_Warehouse_Shipped, SUM(ws.Quantity_In_Stock) AS Quantity_In_Stock FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN product AS pd ON pd.Product_ID=ol.Product_ID INNER JOIN postage AS p ON p.Postage_ID=o.Postage_ID AND p.Postage_Days>1 LEFT JOIN warehouse_stock AS ws ON ws.Product_ID=ol.Product_ID AND ws.Warehouse_ID=%d WHERE o.Order_ID=%d AND ol.Despatch_From_ID=%d AND ol.Despatch_ID=0 AND ol.Line_Status NOT LIKE 'Cancelled' AND o.Is_Declined='N' AND o.Is_Warehouse_Declined='N' GROUP BY ol.Order_Line_ID", mysql_real_escape_string($warehouseId), mysql_real_escape_string($order->ID), mysql_real_escape_string($warehouseId)));
		while($data->Row) {
			$lines[] = $data->Row;

			$data->Next();
		}
		$data->Disconnect();

		if(count($lines) > 0) {
			$canDespatch = true;

			foreach($lines as $line) {
				$canDespatchLine = false;

				if($line['Is_Warehouse_Shipped'] == 'Y') {
					if(($line['Quantity_In_Stock'] - $line['Quantity']) >= 10) {
						$canDespatchLine = true;
					}
				}

				if(!$canDespatchLine) {
					$canDespatch = false;
					break;
				}
			}

			if($canDespatch) {
				$order->GetLines();
				$order->Customer->Get();
				$order->Customer->Contact->Get();

                if($order->PaymentMethod->ID == 0) {
					$order->PaymentMethod->GetByReference('card');
					$order->Update();
				} else {
					$order->PaymentMethod->Get();
				}

				$warehouse = new Warehouse($warehouseId);

				$invoice = new Invoice();

				$generateInvoice = true;
				$chargeShipping = ($order->HasInvoices) ? false : true;
				$amountSubTotal = 0;
				$amountTax = 0;
				$amountShipping = 0;
				$amountDiscount = 0;

				if($order->Sample == 'Y') {
					$generateInvoice = false;
				}

				if($generateInvoice){
					$invoice->PaymentMethod->ID = $order->PaymentMethod->ID;
					$invoice->Order->ID = $order->ID;
					$invoice->Customer->ID = $order->Customer->ID;
					$invoice->Payment = 0;
					$invoice->IsDespatched = 'Y';
					$invoice->Despatch = 0;
					$invoice->Organisation = $order->InvoiceOrg;
					$invoice->Person->Title = $order->Invoice->Title;
					$invoice->Person->Name = $order->Invoice->Name;
					$invoice->Person->Initial = $order->Invoice->Initial;
					$invoice->Person->LastName = $order->Invoice->LastName;
					$invoice->Person->Address->Line1 = $order->Invoice->Address->Line1;
					$invoice->Person->Address->Line2 = $order->Invoice->Address->Line2;
					$invoice->Person->Address->Line3 = $order->Invoice->Address->Line3;
					$invoice->Person->Address->City = $order->Invoice->Address->City;
					$invoice->Person->Address->Region->ID = $order->Invoice->Address->Region->ID;
					$invoice->Person->Address->Region->Get();
					$invoice->Person->Address->Country->ID = $order->Invoice->Address->Country->ID;
					$invoice->Person->Address->Country->Get();
					$invoice->Person->Address->Zip = $order->Invoice->Address->Zip;
					$invoice->NominalCode = $order->NominalCode;
                    $invoice->IsPaid = 'N';

                    if($order->PaymentMethod->Reference == 'credit') {
						$invoice->DueOn = date('Y-m-d H:i:s', time() + (86400 * $order->Customer->CreditPeriod));

					} elseif($order->PaymentMethod->Reference == 'card') {
						$invoice->IsPaid = 'Y';
					}
				}

				$data = new DataQuery("SELECT Courier_ID FROM courier WHERE Is_Default='Y'");
				$defaultCourier = ($data->TotalRows > 0) ? $data->Row['Courier_ID'] : 0;
				$data->Disconnect();

				$despatch = new Despatch();
				$despatch->Order->ID = $order->ID;
				$despatch->DeliveryInstructions = $order->DeliveryInstructions;
				$despatch->Courier->ID = $defaultCourier;
				$despatch->Weight = 0;
				$despatch->Boxes = 1;
				$despatch->Postage->ID = $order->Postage->ID;
				$despatch->DespatchedOn = getDatetime();
				$despatch->DespatchedFrom->ID = $warehouse->ID;
				$despatch->Person->Title = $order->Shipping->Title;
				$despatch->Person->Name = $order->Shipping->Name;
				$despatch->Person->Initial = $order->Shipping->Initial;
				$despatch->Person->LastName = $order->Shipping->LastName;
				$despatch->Organisation = $order->ShippingOrg;
				$despatch->Person->Address->Line1 = $order->Shipping->Address->Line1;
				$despatch->Person->Address->Line2 = $order->Shipping->Address->Line2;
				$despatch->Person->Address->Line3 = $order->Shipping->Address->Line3;
				$despatch->Person->Address->City = $order->Shipping->Address->City;
				$despatch->Person->Address->Region->ID = $order->Shipping->Address->Region->ID;
				$despatch->Person->Address->Region->Get();
				$despatch->Person->Address->Country->ID = $order->Shipping->Address->Country->ID;
				$despatch->Person->Address->Country->Get();
				$despatch->Person->Address->Zip = $order->Shipping->Address->Zip;

				$isPartialDespatch = false;

				$discountCollection = new DiscountCollection();
				$discountCollection->Get($order->Customer);

                $cost = 0;
				$weight = 0;

				$shippingProducts = array();

				for($i=0; $i<count($order->Line); $i++){
					if(($order->Line[$i]->DespatchID == 0) && ($order->Line[$i]->Status != 'Cancelled')) {
						$found = false;

						foreach($orderLines as $orderLine) {
							if($orderLine['Order_Line_ID'] == $order->Line[$i]->ID) {
								$found = true;
								break;
							}
						}

						if($found) {
							$despatchedLine = new DespatchLine();
							$despatchedLine->Quantity = $order->Line[$i]->Quantity;
							$despatchedLine->Product->ID = $order->Line[$i]->Product->ID;
							$despatchedLine->Product->Name = $order->Line[$i]->Product->Name;
							$despatchedLine->IsComplementary = $order->Line[$i]->IsComplementary;

							$despatch->Line[] = $despatchedLine;

							$despatch->Weight += $order->Line[$i]->Product->Weight * $order->Line[$i]->Quantity;

							$order->Line[$i]->Status = 'Despatched';

							if($generateInvoice && empty($order->Line[$i]->InvoiceID)) {
								if($order->PaymentMethod->Reference != 'foc') {
									$invoice->SubTotal += $order->Line[$i]->Total;
									$invoice->Tax += $order->Line[$i]->Tax;
									$invoice->Discount += $order->Line[$i]->Discount;
								}

								$invoiceLine = new InvoiceLine;
								$invoiceLine->Quantity = $order->Line[$i]->Quantity;
								$invoiceLine->Description = $order->Line[$i]->Product->Name;
								$invoiceLine->Product->ID = $order->Line[$i]->Product->ID;
								$invoiceLine->Price = $order->Line[$i]->Price;
								$invoiceLine->Total = ($order->PaymentMethod->Reference != 'foc') ? $order->Line[$i]->Total : 0;
								$invoiceLine->Discount = ($order->PaymentMethod->Reference != 'foc') ? $order->Line[$i]->Discount : 0;
								$invoiceLine->DiscountInformation = ($order->PaymentMethod->Reference != 'foc') ? $order->Line[$i]->DiscountInformation : '';
								$invoiceLine->Tax = ($order->PaymentMethod->Reference != 'foc') ? $order->Line[$i]->Tax : 0;

								$invoice->Line[] = $invoiceLine;
							}

                            $cost += $order->Line[$i]->Cost * $order->Line[$i]->Quantity;
							$weight += $order->Line[$i]->Product->Weight * $order->Line[$i]->Quantity;

                            $shippingProducts[] = array('Quantity' => $order->Line[$i]->Quantity, 'ShippingClassID' => $order->Line[$i]->Product->ShippingClass->ID);

							$order->Line[$i]->Status = 'Despatched';

							$amountSubTotal += $order->Line[$i]->Total;
							$amountTax += $order->Line[$i]->Tax;
							$amountDiscount += $order->Line[$i]->Discount;
						} else {
							$isPartialDespatch = true;
						}
					}
				}

                if($warehouse->Type == 'S') {
					$calc = new SupplierShippingCalculator($despatch->Person->Address->Country->ID, $despatch->Person->Address->Region->ID, $cost, $weight, $despatch->Postage->ID, $warehouse->Contact->ID);
				} elseif($warehouse->Type == 'B') {
					$calc = new ShippingCostCalculator($despatch->Person->Address->Country->ID, $despatch->Person->Address->Region->ID, $cost, $weight, $despatch->Postage->ID);
				}
				
				foreach($shippingProducts as $item) {
					$calc->Add($item['Quantity'], $item['ShippingClassID']);
				}

				if(($order->IsFreeTextDespatched == 'N') && (!empty($order->FreeText) || ($order->FreeTextValue > 0))) {
					$isPartialDespatch = true;
				}

				if($order->PaymentMethod->Reference != 'foc') {
					if($generateInvoice && ((count($invoice->Line) > 0) || (($order->IsFreeTextDespatched == 'N') && (!empty($order->FreeText) || ($order->FreeTextValue > 0))))) {
						$invoice->TaxRate = $order->GetTaxRate();

						if($chargeShipping && $generateInvoice) {
							$invoice->Shipping = $order->TotalShipping;
							$invoice->Tax += $order->CalculateCustomTax($order->TotalShipping);
						}

						$invoice->Tax = round($invoice->Tax, 2);
						$invoice->Total = $invoice->SubTotal + $invoice->Tax + $invoice->Shipping - $invoice->Discount;
					}
				}

				if($chargeShipping){
					$amountShipping = $order->TotalShipping;
					$amountTax += $order->CalculateCustomTax($order->TotalShipping);
				}

				$amountTax = round($amountTax, 2);
				$amount = $amountSubTotal + $amountTax + $amountShipping - $amountDiscount;

				if($order->Sample == 'N') {
					$gateway = new PaymentGateway();

					if($order->PaymentMethod->Reference == 'google') {
						$googleRequest = new GoogleRequest();

						$despatch->Courier->Get();

						$items = array();

						for($i=0; $i<count($order->Line); $i++){
							if(($order->Line[$i]->DespatchID == 0) && ($order->Line[$i]->Status != 'Cancelled')) {
								$found = false;

								foreach($orderLines as $orderLine) {
									if($orderLine['Order_Line_ID'] == $order->Line[$i]->ID) {
										$found = true;
										break;
									}
								}

								if($found) {
									$items[] = $order->Line[$i]->Product->ID;
								}
							}
						}

						if(!$googleRequest->shipItems($order->CustomID, $items, $despatch->Courier->Name, '')) {
							$failed[$orderId] = array(sprintf('<p>%s</p>', $googleRequest->ErrorMessage));
						}
					} elseif($order->PaymentMethod->Reference == 'card') {
						if($gateway->GetDefault()) {
							require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/gateways/' . $gateway->ClassFile);

							$paymentProcessor = new PaymentProcessor($gateway->VendorName, $gateway->IsTestMode);

							$taxToAdd = 0;
							
							if(($order->IsFreeTextDespatched == 'N') && (!empty($order->FreeText) || ($order->FreeTextValue > 0))) {
								$taxToAdd += round($order->CalculateCustomTax($order->FreeTextValue), 2);
							}

							$amount = number_format($amount + (($order->IsFreeTextDespatched == 'N') && ((!empty($order->FreeText) || ($order->FreeTextValue > 0))) ? ($order->FreeTextValue + $taxToAdd) : 0), 2, '.', '');

							if($amount > 0) {
								$paymentProcessor->Amount = $amount;
								$paymentProcessor->Description = $GLOBALS['COMPANY'] . ' Invoice #' . $invoice->ID;

								$paymentProcessor->Payment->Gateway->ID = $gateway->ID;
								$paymentProcessor->Payment->Order->ID = $order->ID;

								$payment = new Payment();

								$success = false;

								$data = new DataQuery(sprintf("SELECT Payment_ID FROM payment WHERE Transaction_Type LIKE 'AUTHENTICATE' AND (Status LIKE 'REGISTERED' OR Status LIKE '3DAUTH') AND Reference!='' AND Order_ID=%d", $order->ID));
								if($data->TotalRows > 0) {
									$data83 = new DataQuery(sprintf("SELECT Payment_ID FROM payment WHERE Transaction_Type LIKE 'CANCEL' AND Status LIKE 'OK' AND Order_ID=%d", $order->ID));
									if($data83->TotalRows == 0) {
										$payment->Get($data->Row['Payment_ID']);

										$success = $paymentProcessor->Authorise($payment);
									}
									$data83->Disconnect();
								}
								$data->Disconnect();

								if(!$success){
									$invoice->IsPaid = 'N';

									if($paymentProcessor->Payment->Status == 'FAIL') {
										$order->IsFailed = 'Y';
										$order->Update();
									} else {
										if($order->IsDeclined == 'N') {
											if((stristr($paymentProcessor->Payment->StatusDetail, 'authorisation') && stristr($paymentProcessor->Payment->StatusDetail, 'declined')) || (stristr($paymentProcessor->Payment->StatusDetail, 'validation') && stristr($paymentProcessor->Payment->StatusDetail, 'failure')) || (stristr($paymentProcessor->Payment->StatusDetail, 'does not match card number'))) {
												$order->SendDeclined();
											}
										}
										
										$order->IsDeclined = 'Y';
										$order->Update();
									}

									$failed[$orderId] = array();

									for($i=0; $i < count($paymentProcessor->Error); $i++){
										$failed[$orderId][] = sprintf('<p>%s</p>', $paymentProcessor->Error[$i]);
									}
								} else {
									$order->IsDeclined = 'N';
									$order->IsFailed = 'N';
									$order->Update();
								}
							}
						}
					}
				}

				if(!isset($failed[$orderId])) {
					$validOrders[$index] = $orderId;

					for($i=0; $i<count($order->Line); $i++){
						if(($order->Line[$i]->DespatchID == 0) && ($order->Line[$i]->Status != 'Cancelled')) {
							$found = false;

							foreach($orderLines as $orderLine) {
								if($orderLine['Order_Line_ID'] == $order->Line[$i]->ID) {
									$found = true;
									break;
								}
							}

							if($found) {
								$order->Line[$i]->DespatchedFrom->ChangeQuantity($order->Line[$i]->Product->ID, $order->Line[$i]->Quantity);
							}
						}
					}

					if($generateInvoice && ((count($invoice->Line) > 0) || (($order->IsFreeTextDespatched == 'N') && (!empty($order->FreeText) || ($order->FreeTextValue > 0))))) {
						if($invoice->Total > 0) {
							$invoice->Add();

							$invoiceNotes[$index] = $invoice->ID;

							for($i=0; $i < count($invoice->Line); $i++){
								$invoice->Line[$i]->InvoiceID = $invoice->ID;
								$invoice->Line[$i]->Add();
							}
						}
					}

					$despatch->PostageCost = $calc->GetTotal();
					$despatch->Add();

					$despatchNotes[$index] = $despatch->ID;

					for($i=0; $i < count($despatch->Line); $i++){
						$despatch->Line[$i]->Despatch = $despatch->ID;
						$despatch->Line[$i]->Add();
					}

					$order->Status = ($isPartialDespatch) ? 'Partially Despatched' : 'Despatched';

					if(!$isPartialDespatch) {
						$order->Despatch(false);
					}

					if(($order->IsFreeTextDespatched == 'N') && (!empty($order->FreeText) || ($order->FreeTextValue > 0))) {
						$order->IsFreeTextDespatched = 'Y';
					}

					$order->Update();

					for($i=0; $i < count($order->Line); $i++) {
						$order->Line[$i]->Product->Get();

						if($order->Line[$i]->Status != 'Cancelled') {
							if($generateInvoice) {
								if(empty($order->Line[$i]->InvoiceID) && $generateInvoice && count($invoice->Line) > 0 && ($order->Line[$i]->Status == 'Invoiced' || $order->Line[$i]->Status == 'Despatched')) {
									if($invoice->Total > 0) {
										$order->Line[$i]->InvoiceID = $invoice->ID;
										$order->Line[$i]->Status = 'Invoiced';
									}
								}
							}
							if(empty($order->Line[$i]->DespatchID) && ($order->Line[$i]->Status == 'Invoiced' || $order->Line[$i]->Status == 'Despatched')) {
								$order->Line[$i]->DespatchID = $despatch->ID;

								if($order->Line[$i]->Cost == 0) {
                                    if($order->Line[$i]->Product->Type == 'S') {
										$data = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Cost>0 ORDER BY Preferred_Supplier ASC LIMIT 0, 1", mysql_real_escape_string($order->Line[$i]->Product->ID)));
										if($data->TotalRows > 0) {
                                            $order->Line[$i]->Cost = $data->Row['Cost'];
											$order->Line[$i]->Update();
										}
										$data->Disconnect();
									} elseif($order->Line[$i]->Product->Type == 'G') {
										$data = new DataQuery(sprintf("SELECT Product_ID, Component_Quantity FROM product_components WHERE Component_Of_Product_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID)));
										while($data->Row) {
						                    $data2 = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Cost>0 ORDER BY Preferred_Supplier ASC LIMIT 0, 1", $data->Row['Product_ID']));
											if($data2->TotalRows > 0) {
                                                $order->Line[$i]->Cost = $data2->Row['Cost'] * $data->Row['Component_Quantity'];
											}
											$data2->Disconnect();

											$data->Next();
										}
										$data->Disconnect();

										$order->Line[$i]->Update();
									}
								}
							}
							if(empty($order->Line[$i]->ID)){
								$order->Line[$i]->Add();
							} else {
								$order->Line[$i]->Update();
							}
						}
					}

					if($generateInvoice) {
						$paymentProcessor->Payment->Invoice->ID = $invoice->ID;

						if(!empty($gateway->ID)){
							$invoice->IsPaid = 'Y';
							$invoice->Payment = $paymentProcessor->Payment->ID;
							$invoice->Paid = $invoice->Total;

							$paymentProcessor->Payment->Invoice->ID = $invoice->ID;
							$paymentProcessor->Payment->PaidOn = getDatetime();
							$paymentProcessor->Payment->Update();
						}

						if($invoice->Total > 0) {
							$invoice->Despatch = $despatch->ID;
							$invoice->Update();
						}
					} else {
						if(!empty($gateway->ID)){
							$paymentProcessor->Payment->PaidOn = getDatetime();
							$paymentProcessor->Payment->Update();
						}
					}
				}

				$index++;
			}
		}
	}

	redirect(sprintf("Location: %s?action=complete&invoices=%s&despatches=%s&failures=%s&orders=%s", $_SERVER['PHP_SELF'], ($generateInvoice) ? urlencode(serialize($invoiceNotes)) : '', urlencode(serialize($despatchNotes)), urlencode(serialize($failed)), urlencode(serialize($validOrders))));
}

function view() {
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/templates/portal-warehouse.dwt" codeOutsideHTMLIsLocked="false" -->
	<head>
		<!-- InstanceBeginEditable name="doctitle" -->
		<title>Warehouse Portal</title>
		<!-- InstanceEndEditable -->
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link href="/ignition/css/i_content.css" rel="stylesheet" type="text/css" media="screen" />
		<link href="/warehouse/css/lightbulbs.css" rel="stylesheet" type="text/css" media="screen" />
		<link href="/warehouse/css/lightbulbs_print.css" rel="stylesheet" type="text/css" media="print" />
		<link href="/warehouse/css/default.css" rel="stylesheet" type="text/css" media="screen" />
		<script language="javascript" src="/warehouse/js/generic_1.js" type="text/javascript"></script>
		<script language="javascript" type="text/javascript">
		var toggleGroup = function(group) {
			var e = document.getElementById(group);
			if(e) {
				if(e.style.display == 'none') {
					e.style.display = 'block';
				} else {
					e.style.display = 'none';
				}
			}
		}
		</script>
	    <!-- InstanceBeginEditable name="head" -->
	    <!-- InstanceEndEditable -->
	</head>
	<body>
	<div id="Wrapper">
		<div id="Header">
			<a href="/warehouse" title="Back to Home Page"><img src="/images/template/logo_blt_1.jpg" width="185" height="70" border="0" class="logo" alt="BLT Direct Logo" /></a>
			<div id="NavBar" class="warehouse">Warehouse Portal</div>
			<div id="CapTop" class="warehouse">
				<div class="curveLeft"></div>
			</div>
			<ul id="NavTop" class="nav warehouse">
				<?php if($session->IsLoggedIn){
					echo sprintf('<li class="login"><a href="%s?action=logout" title="Logout">Logout</a></li>', $_SERVER['PHP_SELF']);
				} else {
					echo '<li class="login"><a href="/index.php" title="Login as a BLT Direct supplier or warehouse">Login</a></li>';
				}?>
				<li class="account"><a href="/warehouse/account_settings.php" title="Your BLT Direct Account">My Account</a></li>
				<li class="contact"><a href="/support.php" title="Contact BLT Direct">Contact Us</a></li>
				<li class="help"><a href="/support.php" title="Light Bulb, Lamp and Tube Help">Help</a></li>
			</ul>
		</div>

	<div id="PageWrapper">
		<div id="Page">
			<div id="PageContent"><!-- InstanceBeginEditable name="pageContent" -->
			<h1>Auto Despatch Orders</h1><br />

			<?php
			$data = new DataQuery(sprintf("SELECT w.Warehouse_ID FROM warehouse AS w INNER JOIN branch AS b ON b.Branch_ID=w.Type_Reference_ID AND b.Is_HQ='Y' WHERE w.Type='B'"));
			$warehouseId = ($data->TotalRows > 0) ? $data->Row['Warehouse_ID'] : 0;
			$data->Disconnect();

			if($warehouseId > 0) {
				$orders = array();

				new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_order SELECT o.Order_ID FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN postage AS p ON p.Postage_ID=o.Postage_ID AND p.Postage_Days>1 WHERE (o.Status LIKE 'Packing' OR o.Status LIKE 'Partially Despatched') AND o.Free_Text_Value=0 AND ol.Despatch_From_ID=%d AND ol.Despatch_ID=0 AND ol.Line_Status NOT LIKE 'Cancelled' AND o.Is_Declined='N' AND o.Is_Warehouse_Declined='N' GROUP BY o.Order_ID", mysql_real_escape_string($warehouseId)));
				new DataQuery(sprintf("ALTER TABLE temp_order ADD PRIMARY KEY (Order_ID)"));

				$data = new DataQuery(sprintf("SELECT o.Order_ID, ol.Quantity, pd.Is_Warehouse_Shipped, SUM(ws.Quantity_In_Stock) AS Quantity_In_Stock FROM temp_order AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN product AS pd ON pd.Product_ID=ol.Product_ID LEFT JOIN warehouse_stock AS ws ON ws.Product_ID=ol.Product_ID AND ws.Warehouse_ID=%d WHERE ol.Despatch_From_ID=%d AND ol.Despatch_ID=0 AND ol.Line_Status NOT LIKE 'Cancelled' GROUP BY ol.Order_Line_ID", mysql_real_escape_string($warehouseId), mysql_real_escape_string($warehouseId)));
				while($data->Row) {
					if(!isset($orders[$data->Row['Order_ID']])) {
						$orders[$data->Row['Order_ID']] = array();
					}

					$orders[$data->Row['Order_ID']][] = $data->Row;

					$data->Next();
				}
				$data->Disconnect();

				foreach($orders as $orderId=>$orderLines) {
					$canDespatch = true;

					foreach($orderLines as $orderLine) {
						$canDespatchLine = false;

						if($orderLine['Is_Warehouse_Shipped'] == 'Y') {
							if(($orderLine['Quantity_In_Stock'] - $orderLine['Quantity']) >= 10) {
								$canDespatchLine = true;
							}
						}

						if(!$canDespatchLine) {
							$canDespatch = false;
							break;
						}
					}

					if(!$canDespatch) {
						new DataQuery(sprintf("DELETE FROM temp_order WHERE Order_ID=%d", $orderId));

						unset($orders[$orderId]);
					}
				}

				$sql = sprintf("SELECT o.*, pg.Postage_Title FROM temp_order AS `to` INNER JOIN orders AS o ON o.Order_ID=`to`.Order_ID LEFT JOIN postage AS pg ON o.Postage_ID=pg.Postage_ID");

				$table = new DataTable('orders');
				$table->SetSQL($sql);
				$table->AddBackgroundCondition(array('Deadline_On', 'Deadline_On'), array(date('Y-m-d H:i:s'), '0000-00-00 00:00:00'), array('<', '>'), '#FFB3B3', '#FF9D9D');
				$table->AddBackgroundCondition('Postage_Title', 'Next Day Delivery', '==', '#FEFDB2', '#FEFC6B');
				$table->AddBackgroundCondition('Postage_Title', 'Next Day Before 12 O Clock', '==', '#FEFDB2', '#FEFC6B');
				$table->AddField('Order Date', 'Created_On', 'left');
				$table->AddField('Organisation', 'Billing_Organisation_Name', 'left');
				$table->AddField('Billing Name', 'Billing_First_Name', 'left');
				$table->AddField('Billing Surname', 'Billing_Last_Name', 'left');
				$table->AddField('Prefix', 'Order_Prefix', 'left');
				$table->AddField('Number', 'Order_ID', 'right');
				$table->AddField('Total', 'Total', 'right');
				$table->AddField('Postage', 'Postage_Title', 'left');
				$table->AddField('Deadline', 'Deadline_On', 'hidden');
				$table->AddLink("order_details.php?orderid=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open Order Details\" border=\"0\">", "Order_ID");
				$table->SetMaxRows(25);
				$table->SetOrderBy("Created_On");
				$table->Order = "DESC";
				$table->Finalise();
				$table->DisplayTable();
				echo '<br />';
				$table->DisplayNavigation();

				echo sprintf('<br /><input class="greySubmit" type="button" name="despatch" value="despatch" onclick="window.location.href = \'%s?action=despatch\';" />', $_SERVER['PHP_SELF']);
			}
			?>

			<!-- InstanceEndEditable -->
			</div>
	  	</div>

		<div id="PageFooter">
			<ul class="links">
				<li><a href="/privacy.php" title="BLT Direct Privacy Policy">Privacy Policy</a></li>
				<li><a href="/support.php" title="Contact BLT Direct">Contact Us</a></li>
			</ul>
			<p class="copyright">Copyright &copy; BLT Direct, 2005. All Right Reserved.</p>
		</div>
	</div>

		<div id="LeftNav">
			<div id="CatalogueNav" class="greyNavLeft">
				<div id="NavLeftItems" class="warehouse">
				<p class="title"><strong>Warehouse Options </strong> </p>

				<ul class="rootCat">
					<?php
					if(($session->Warehouse->Type == 'B') || (($session->Warehouse->Type == 'S') && ($session->Warehouse->Contact->IsStockerOnly == 'N'))) {
						?>
						<li><a href="/warehouse/orders_pending.php">Pending Orders</a></li>

                        <?php
						if($session->Warehouse->Type == 'B') {
							echo '<li><a href="/warehouse/orders_pending_tax_free.php">Pending Orders<br />(Tax Free)</a></li>';
						}
						?>
	                    
	                    <li><a href="/warehouse/orders_collections.php">Collection Orders</a></li>
						<li><a href="/warehouse/orders_backordered.php">Backordered Orders</a></li>
	                    
	                    <?php
						if(($session->Warehouse->Type == 'S') && ($session->Warehouse->Contact->IsBidder == 'Y')) {
							echo '<li><a href="/warehouse/orders_bidding.php">Bidding Orders</a></li>';
						}
						
						if($session->Warehouse->Type == 'S') {
							echo '<li><a href="/warehouse/orders_warehouse_declined.php">Warehouse Declined Orders</a></li>';
						}
						?>

						<li><a href="/warehouse/orders_despatched.php">Despatched Orders</a></li>
						<li><a href="/warehouse/orders_search.php">Search Orders</a></li>
						<?php
					}

					if($session->Warehouse->Type == 'B') {
						?>
						<li><a href="/warehouse/orders_auto_despatch.php">Auto Despatch Orders</a></li>
						<?php
					}

					if(($session->Warehouse->Type == 'B') || (($session->Warehouse->Type == 'S') && ($session->Warehouse->Contact->IsStockerOnly == 'N'))) {
						?>

						<li><a href="/warehouse/despatches_track.php">Track Consignments</a></li>
						<li><a href="/warehouse/products_stocked.php">Stocked Products</a></li>

						<?php
					}
					?>
                
	                <li><a href="/warehouse/products_backordered.php">Products Backordered</a></li>
	                
	                <?php
					if(($session->Warehouse->Type == 'S') && ($session->Warehouse->Contact->IsStockerOnly == 'N')) {
						?>

						<li><a href="/warehouse/products_held.php">Products Held</a></li>
						<li><a href="/warehouse/products_supplied.php">Products Supplied</a></li>

						<?php
						$supplier = new Supplier($session->Warehouse->Contact->ID);

						if($supplier->IsComparable == 'Y') {
							?>

							<li><a href="/warehouse/products_unsupplied.php">Unsupplied Products</a></li>

							<?php
						}
					}

					if($session->Warehouse->Type == 'B') {
						?>

						<li><a href="/warehouse/timesheets.php">Timesheets</a></li>

						<li><a href="javascript:toggleGroup('navLabels');" target="_self">Labels</a></li>
	                    <ul id="navLabels" class="subCat" style="display: none;">
	                        <li><a href="/warehouse/downloads/2nd-class-stamp.pdf" target="_blank">2<sup>nd</sup> Class Stamps</a></li>
	                    </ul>
	                    
						<?php
					}

					if($session->Warehouse->Type == 'S') {
						?>

						<li><a href="javascript:toggleGroup('navReserves');" target="_self">Reserves</a></li>
						<ul id="navReserves" class="subCat" style="display: none;">
							<li><a href="/warehouse/reserves_pending.php">Pending</a></li>
							<li><a href="/warehouse/reserves_completed.php">Completed</a></li>
						</ul>
					
						<li><a href="javascript:toggleGroup('navPriceEnquiries');" target="_self">Price Enquiries</a></li>
						<ul id="navPriceEnquiries" class="subCat" style="display: none;">
							<li><a href="/warehouse/price_enquiries_pending.php">Pending</a></li>
							<li><a href="/warehouse/price_enquiries_completed.php">Completed</a></li>
						</ul>

						<li><a href="javascript:toggleGroup('navPurchaseRequests');" target="_self">Purchase Requests</a></li>
						<ul id="navPurchaseRequests" class="subCat" style="display: none;">
	                    	<li><a href="/warehouse/purchase_requests_pending.php">Pending</a></li>
	                    	<li><a href="/warehouse/purchase_requests_confirmed.php">Confirmed</a></li>
	                    	<li><a href="/warehouse/purchase_requests_completed.php">Completed</a></li>
						</ul>

						<li><a href="javascript:toggleGroup('navPurchaseOrders');" target="_self">Purchase Orders</a></li>
						<ul id="navPurchaseOrders" class="subCat" style="display: none;">
	                    	<li><a href="/warehouse/purchase_orders_unfulfilled.php">Unfulfilled</a></li>
	                    	<li><a href="/warehouse/purchase_orders_fulfilled.php">Fulfilled</a></li>
						</ul>

						<li><a href="javascript:toggleGroup('navReturnRequests');" target="_self">Return Requests</a></li>
						<ul id="navReturnRequests" class="subCat" style="display: none;">
	                    	<li><a href="/warehouse/supplier_return_requests_pending.php">Pending</a></li>
	                    	<li><a href="/warehouse/supplier_return_requests_confirmed.php">Confirmed</a></li>
	                    	<li><a href="/warehouse/supplier_return_requests_completed.php">Completed</a></li>
						</ul>

						<li><a href="/warehouse/supplier_return_requests_pending_purchase.php">Damages</a></li>

                        <li><a href="javascript:toggleGroup('navInvoiceQueries');" target="_self">Invoice Queries</a></li>
						<ul id="navInvoiceQueries" class="subCat" style="display: none;">
	                    	<li><a href="/warehouse/supplier_invoice_queries_pending.php">Pending</a></li>
	                    	<li><a href="/warehouse/supplier_invoice_queries_resolved.php">Resolved</a></li>
						</ul>

                        <li><a href="javascript:toggleGroup('navDebits');" target="_self">Debits</a></li>
						<ul id="navDebits" class="subCat" style="display: none;">
                    		<li><a href="/warehouse/debits_pending.php">Pending</a></li>
                    		<li><a href="/warehouse/debits_completed.php">Completed</a></li>
						</ul>

                        <li><a href="javascript:toggleGroup('navReports');" target="_self">Reports</a></li>
						<ul id="navReports" class="subCat" style="display: none;">
                    		<li><a href="/warehouse/report_orders_despatched.php">Orders Despatched</a></li>
                    		<li><a href="/warehouse/report_reserved_stock.php">Reserved Stock</a></li>
                    		<li><a href="/warehouse/report_stock_dropped.php">Stock Dropped</a></li>
						</ul>

						<?php
					}
					?>

					<li><a href="/warehouse/account_settings.php">Account Settings</a></li>
				</ul>
				</div>
				<div class="cap"></div>
				<div class="shadow"></div>
			</div>
		</div>
	</div>
	</body>
	<!-- InstanceEnd --></html>
	<?php
}