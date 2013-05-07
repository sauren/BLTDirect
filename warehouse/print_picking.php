<?php
require_once('lib/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$session->Secure();
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
    <!-- InstanceBeginEditable name="head" --><!-- InstanceEndEditable -->
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
		if($_REQUEST['status']=='N'){
			$sql = sprintf("SELECT o.* from warehouse w
					INNER JOIN order_line ol on ol.Despatch_From_ID = w.Warehouse_ID
					INNER JOIN orders o on o.Order_ID = ol.Order_ID
					where w.Warehouse_ID = %d AND ol.Despatch_ID = 0
					",mysql_real_escape_string($session->Warehouse->ID))."AND (Status LIKE '%partial%' or o.Status LIKE 'packing') GROUP BY o.Order_ID";
		}elseif($_REQUEST['status']=='PK'){
			$sql = sprintf("SELECT o.* from warehouse w
					INNER JOIN order_line ol on ol.Despatch_From_ID = w.Warehouse_ID
					INNER JOIN orders o on o.Order_ID = ol.Order_ID
					where w.Warehouse_ID = %d AND ol.Despatch_ID = 0
					AND o.Status LIKE 'packing' GROUP BY o.Order_ID",mysql_real_escape_string($session->Warehouse->ID));
		}elseif($_REQUEST['status']=='PD'){
			$sql = sprintf("SELECT o.* from warehouse w
					INNER JOIN order_line ol on ol.Despatch_From_ID = w.Warehouse_ID
					INNER JOIN orders o on o.Order_ID = ol.Order_ID
					where w.Warehouse_ID = %d AND ol.Despatch_ID = 0
					",mysql_real_escape_string($session->Warehouse->ID))."AND Status LIKE '%partial%' GROUP BY o.Order_ID";
		}else{
			$sql = sprintf("SELECT o.* from warehouse w
					INNER JOIN order_line ol on ol.Despatch_From_ID = w.Warehouse_ID
					INNER JOIN orders o on o.Order_ID = ol.Order_ID
					where w.Warehouse_ID = %d AND ol.Despatch_ID = 0
					",mysql_real_escape_string($session->Warehouse->ID))."AND ((ol.Line_Status LIKE 'Backordered' OR o.Backordered='Y') AND ol.Line_Status NOT LIKE 'Cancelled') AND o.Status NOT LIKE 'Cancelled' GROUP BY o.Order_ID";
		}
		$orderNumber = 0;

		$data = new DataQuery($sql);

		if($data->TotalRows == 0){
			echo "There are no orders to be printed off";
		}else{
			while($data->Row){

				if($orderNumber != 0){
					if($_REQUEST['style']=='page'){
						echo "<h1 style='page-break-after:always'></h1>";
					}else{
						echo "<hr>";
					}
				}

				$orderNumber = $data->Row['Order_ID'];
				$order = new Order($orderNumber);

				$order->GetLines();
				$order->Customer->Get();
				$order->Customer->Contact->Get();
			?>
			<table width="100%"  border="0" cellspacing="0" cellpadding="0">
			<tr>
			<td>


			<table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
			<tr>
                    <td valign="top" class="shipping"><p> <strong>Shipping Address:</strong><br />
                            <?php echo $order->Shipping->GetFullName(); ?><br />
                            <?php echo $order->Shipping->Address->Line1; ?></p></td>
                  </tr>

                </table>
			    </td><td align="right" valign="middle"><table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
                  <tr>
                    <th valign="top"> Order Ref: </th>
                    <td valign="top"><?php echo $order->Prefix . $order->ID; ?></td>
                  </tr>
				  <tr>
                    <th valign="top"> Customer Ref: </th>
                    <td valign="top"><?php echo $order->CustomID; ?> &nbsp;</td>
                  </tr>
                  <tr>
                    <th valign="top">Customer: </th>
                    <td valign="top"><?php echo $order->Customer->Contact->Person->GetFullName(); ?></td>
                  </tr>
                  <tr>
                    <th>Order Status:</th>
                    <td valign="top"><?php echo $order->Status; ?></td>
                  </tr>
                  <tr>
                    <th valign="top">&nbsp </th>
                    <td valign="top">&nbsp</td>
                  </tr>
                  <tr>
                    <th valign="top">&nbsp </th>
                    <td valign="top">

					&nbsp;
					</td>
                  </tr>
                  <tr>
                    <th valign="top">&nbsp;</th>
                    <td valign="top">&nbsp;</td>
                  </tr>
                  <tr>
                    <th valign="top">Order Date: </th>
                    <td valign="top"><?php echo cDatetime($order->OrderedOn, 'shortdate'); ?></td>
                  </tr>

                </table>                </td>
              </tr>
              <tr>
                <td colspan="2"><br>                  <br>
                  <table cellspacing="0" class="orderDetails">
                  <tr>
                    <th>Qty</th>
                    <th>Product</th>
                    <th>Location</th>
                    <th>Despatched</th>
                    <?php if($session->Warehouse->Type == 'S'){

                    	echo "<th>Cost per unit</th>";
                    	echo "<th>Part No.</th>";
                    }?>
                    <th>Quickfind</th>
                  </tr>
                <?php
                  $rowCount = 0;

                  for($i=0; $i < count($order->Line); $i++){

                  	if($session->Warehouse->ID == $order->Line[$i]->DespatchedFrom->ID && $order->Line[$i]->DespatchID == 0){
                  		$rowCount++;
                  		$wareHouseId = $order->Line[$i]->DespatchedFrom->ID;
                  		$session->Warehouse->Get();
                  		$showCost = false;
                  		$prodCost = false;
                  		$supplierPartNo = '';
                  		if($session->Warehouse->Type == 'S'){
                  			$showCost = true;
                  			$costFinder = new DataQuery(sprintf('SELECT * FROM supplier_product WHERE Supplier_ID = %d AND Product_ID = %d',mysql_real_escape_string($session->Warehouse->Contact->ID),mysql_real_escape_string($order->Line[$i]->Product->ID)));
                  			$prodCost = $costFinder->Row['Cost'];
                  			$supplierPartNo = $costFinder->Row['Supplier_SKU'];
                  		}
				?>
                  <tr>
                    <td><?php echo $order->Line[$i]->Quantity; ?>x</td>
                    <td><?php echo $order->Line[$i]->Product->Name; ?></td>
					<td>
						<?php $warehouseLocation = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock
																WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>'' LIMIT 0, 1",
					mysql_real_escape_string($order->Line[$i]->DespatchedFrom->ID),
					mysql_real_escape_string($order->Line[$i]->Product->ID)));
					echo $warehouseLocation->Row['Shelf_Location'];
					$warehouseLocation->Disconnect();
								?>&nbsp;
					</td>
					<td>Not despatched</td>
					<?php
					if(strtolower($order->Line[$i]->Status) == 'cancelled'){
					?>
					<td colspan="1" align="center">Cancelled</td>
					<?php
						}
					if($showCost){
						if($prodCost == false){
							echo "<td>n\\a</td>";
						}else{
							echo sprintf("<td>%s</td>", number_format($prodCost, 2, '.', ','));
						}
						if($session->Warehouse->Type == 'S'){
							echo sprintf("<td>%s&nbsp;</td>", $supplierPartNo);
						}
					} ?>
                    <td><?php echo $order->Line[$i]->Product->ID; ?></td>
                  </tr>
                  <tr>
                    <td colspan="<?php echo ($showCost)? "7":"6";?>" align="left">Cart Weight: ~<?php echo $order->Weight; ?>Kg</td>
                  </tr>
                </table>
              </tr>
              <tr>
                <td align="left" valign="top">
                <td align="right"><table border="0" cellpadding="6" cellspacing="0" class="orderTotals">
                  <tr>
                    <th colspan="2">Shipping Information</th>
                  </tr>
                  <tr>
                    <td>Delivery Option:</td>
                    <td align="right">
                      <?php
                      $order->Postage->Get();
                      echo $order->Postage->Name;
						?>
                    </td>
                  </tr>
                </table></td>
              </tr>
            </table><br />
			<?php

			?><?php

                  	}
                  }

                  $data->Next();

				}
		}
		$data->Disconnect();

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