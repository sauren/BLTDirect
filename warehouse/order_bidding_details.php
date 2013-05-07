<?php
require_once('lib/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderBidding.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');

$session->Secure(3);

$supplierId = $session->Warehouse->Contact->ID;

$order = new Order($_REQUEST['orderid']);
$order->GetBids();
$order->GetLines();

$isEditable = (strtolower($order->Status) == 'pending') ? true : false;

if(!$isEditable) {
	redirect(sprintf("Location: orders_bidding.php"));
}

$reductionMultiplier = 1 - Setting::GetValue('order_bid_reduction');

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('orderid', 'Order ID', 'hidden', '', 'numeric_unsigned', 1, 11);

for($k=0; $k<count($order->Line); $k++) {
	if($order->Line[$k]->Product->ID > 0) {
		$order->Line[$k]->DespatchedFrom->Get();

		if($order->Line[$k]->DespatchedFrom->Type == 'S') {
			if($order->Line[$k]->DespatchedFrom->Contact->ID != $supplierId) {
				$bidPlaced = false;

				for($i=0; $i<count($order->Bid); $i++) {
					if($order->Bid[$i]->Supplier->ID == $supplierId) {
                        if($order->Bid[$i]->Product->ID == $order->Line[$k]->Product->ID) {
							$bidPlaced = true;

							break;
						}
					}
				}

				if(!$bidPlaced) {
					$form->AddField(sprintf('line_bid_%d', $order->Line[$k]->ID), sprintf('Bid Cost for \'%s\'', $order->Line[$k]->Product->Name), 'text', '0', 'float', 1, 11, true, 'size="10"');
				}
			}
		}
	}
}

if(isset($_REQUEST['confirm'])) {
	if(isset($_REQUEST['passbidding'])) {
		$order->IsBidding = 'N';
		$order->Update();
		
		redirect(sprintf("Location: orders_bidding.php"));
		
	} elseif(isset($_REQUEST['placebids'])) {
		if($form->Validate()) {
			for($k=0; $k<count($order->Line); $k++) {
	            if($order->Line[$k]->Product->ID > 0) {
					if($order->Line[$k]->DespatchedFrom->Type == 'S') {
						if($order->Line[$k]->DespatchedFrom->Contact->ID != $supplierId) {
	                        $bidPlaced = false;

							for($i=0; $i<count($order->Bid); $i++) {
								if($order->Bid[$i]->Supplier->ID == $supplierId) {
			                        if($order->Bid[$i]->Product->ID == $order->Line[$k]->Product->ID) {
										$bidPlaced = true;

										break;
									}
								}
							}

							if(!$bidPlaced) {
								$bid = new OrderBidding();
	                            $bid->OrderID = $order->ID;
								$bid->Supplier->ID = $supplierId;
								$bid->Product->ID = $order->Line[$k]->Product->ID;
								$bid->CostOriginal = $order->Line[$k]->Cost;
								$bid->CostBid = $form->GetValue(sprintf('line_bid_%d', $order->Line[$k]->ID));
								
								if($bid->CostBid > 0) {
									$bid->Add();
								}
							}
						}
					}
				}
			}

			redirect(sprintf("Location: ?orderid=%d", $order->ID));
		}
	}
}
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
			<h1>Order Bidding Details</h1>
			<br />

			<?php
			if(!$form->Valid){
				echo $form->GetError();
				echo '<br />';
			}

			echo $form->Open();
			echo $form->GetHTML('confirm');
			echo $form->GetHTML('orderid');
			?>

			<table width="100%" border="0" cellspacing="0" cellpadding="0">
			  <tr>
			    <td align="left" valign="top"></td>
			    <td align="right" valign="top">

			    <table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
			      <tr>
			        <th>Order:</th>
			        <td>#<?php echo $order->Prefix, $order->ID; ?></td>
			      </tr>
			      <tr>
			        <th>Status:</th>
			        <td><?php echo $order->Status; ?></td>
			      </tr>
			      <tr>
			        <th>&nbsp;</th>
			        <td>&nbsp;</td>
			      </tr>
			      <tr>
			        <th>Created On:</th>
			        <td><?php echo cDatetime($order->CreatedOn, 'shortdate'); ?></td>
			      </tr>
			    </table>
			    <br />

			   </td>
			  </tr>
			  <tr>
			    <td colspan="2">
			    	<br />

					<div style="background-color: #eee; padding: 10px 0 10px 0;">
					 	<p><span class="pageSubTitle">Products</span><br /><span class="pageDescription">Listing biddable products for this order.</span></p>

					 	<table cellspacing="0" class="orderDetails">
							<tr>
								<th nowrap="nowrap" style="padding-right: 5px;">Quantity</th>
					      		<th nowrap="nowrap" style="padding-right: 5px;">Product</th>
								<th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
					      		<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Current Cost</th>
					      		<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Bid Cost</th>
					      	</tr>

							<?php
							$hasProducts = false;

                            if(count($order->Line) > 0) {
								for($k=0; $k<count($order->Line); $k++) {
									if($order->Line[$k]->Product->ID > 0) {
                                        if($order->Line[$k]->DespatchedFrom->Type == 'S') {
                                        	if($order->Line[$k]->DespatchedFrom->Contact->ID != $supplierId) {
												$hasProducts = true;
											}
										}
									}
								}
							}

							if($hasProducts) {
								for($k=0; $k<count($order->Line); $k++) {
									if($order->Line[$k]->Product->ID > 0) {
                                        if($order->Line[$k]->DespatchedFrom->Type == 'S') {
                                        	if($order->Line[$k]->DespatchedFrom->Contact->ID != $supplierId) {
												?>

												<tr>
							      					<td nowrap="nowrap"><?php echo $order->Line[$k]->Quantity; ?></td>
							      					<td><?php echo $order->Line[$k]->Product->Name; ?></td>
							      					<td nowrap="nowrap"><a href="../product.php?pid=<?php echo $order->Line[$k]->Product->ID; ?>" target="_blank"><?php echo $order->Line[$k]->Product->ID; ?></a></td>
								      				<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($order->Line[$k]->Cost * $reductionMultiplier, 2), 2, '.', ','); ?></td>
								      				<td nowrap="nowrap" align="right">
								      					<?php
                                                        $bidPlaced = false;
                                                        $bidCost = 0;

														for($i=0; $i<count($order->Bid); $i++) {
															if($order->Bid[$i]->Supplier->ID == $supplierId) {
										                        if($order->Bid[$i]->Product->ID == $order->Line[$k]->Product->ID) {
																	$bidPlaced = true;
																	$bidCost = $order->Bid[$i]->CostBid;

																	break;
																}
															}
														}

														if(!$bidPlaced) {
															echo '&pound;', $form->GetHTML(sprintf('line_bid_%d', $order->Line[$k]->ID));
														} else {
															echo '&pound;', $bidCost;
														}
														?>
								      				</td>
												</tr>

												<?php
											}
										}
									}
								}
							} else {
						      	?>

						      	<tr>
									<td colspan="5" align="center">No products available for viewing.</td>
						      	</tr>

						      	<?php
							}
							?>
					    </table>
					    <br />

					    <input type="submit" name="placebids" value="place bids" class="submit" />
					    <input type="submit" name="passbidding" value="pass bidding" class="greySubmit" />

					</div>

			    </td>
			  </tr>
			</table>

			<?php
			echo $form->Close();
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