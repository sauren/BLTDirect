<?php
require_once('lib/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

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
		<h1>Backordered Orders</h1><br />

		<?php
        $sqlSelect = '';
        $sqlFrom = '';
        $sqlWhere = '';
        $sqlGroup = '';

		if($session->Warehouse->Type == 'S') {
			$sqlSelect .= sprintf("SELECT o.*, p.Postage_Title, p.Postage_Days, SUM(ol.Line_Total-ol.Line_Discount) AS Warehouse_Total, IF(MIN(ol2.Backorder_Expected_On)<>'0000-00-00 00:00:00', MIN(ol2.Backorder_Expected_On), '') AS Backorder_Date ");
			$sqlFrom .= sprintf("FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Despatch_From_ID=%d AND ol.Despatch_ID=0 LEFT JOIN order_line AS ol2 ON ol2.Order_ID=o.Order_ID AND ol2.Despatch_From_ID=%d AND ol2.Despatch_ID=0 AND ol2.Line_Status LIKE 'Backordered' INNER JOIN postage AS p ON o.Postage_ID=p.Postage_ID ", $session->Warehouse->ID, $session->Warehouse->ID);
			$sqlWhere .= sprintf("WHERE o.Is_Declined='N' AND o.Is_Failed='N' AND o.Is_Warehouse_Declined='N' AND (o.Status LIKE 'Packing' OR o.Status LIKE 'Partially Despatched') AND ol2.Order_Line_ID IS NOT NULL ");
			$sqlGroup .= sprintf("GROUP BY o.Order_ID ");
		} else {
            $sqlSelect .= sprintf("SELECT o.*, p.Postage_Days, p.Postage_Title, SUM(ol.Line_Total-ol.Line_Discount) AS Warehouse_Total, IF(MIN(ol2.Backorder_Expected_On)='0000-00-00 00:00:00', '', MIN(ol2.Backorder_Expected_On)) AS Backorder_Date ");
			$sqlFrom .= sprintf("FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID AND ol.Despatch_ID=0 INNER JOIN warehouse AS w ON ol.Despatch_From_ID=w.Warehouse_ID LEFT JOIN order_line AS ol2 ON ol2.Order_Line_ID=ol.Order_Line_ID AND ol2.Backorder_Expected_On<>'0000-00-00 00:00:00' AND ol2.Despatch_From_ID=w.Warehouse_ID LEFT JOIN supplier AS s ON w.Type_Reference_ID=s.Supplier_ID AND w.Type='S' LEFT JOIN postage AS p ON o.Postage_ID=p.Postage_ID ");
			$sqlWhere .= sprintf("WHERE ((w.Type='B' AND w.Warehouse_ID=%d) OR (w.Type='S' AND s.Is_Drop_Shipper='N' AND s.Drop_Shipper_ID=%d)) AND ((o.Total=0) OR (o.Total>0 AND o.TotalTax>0)) AND (ol.Line_Status LIKE 'Backordered' OR o.Backordered='Y') AND o.Status NOT LIKE 'Despatched' AND o.Status NOT LIKE 'Cancelled' AND o.Is_Declined='N' AND o.Is_Failed='N' AND o.Is_Warehouse_Declined='N' ", $session->Warehouse->ID, $session->Warehouse->Contact->ID);
			$sqlGroup .= sprintf("GROUP BY o.Order_ID ");
		}

		$table = new DataTable('orders');
        $table->SetSQL(sprintf('%s%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere, $sqlGroup));
        $table->AddBackgroundCondition('Is_Restocked', 'Y', '==', '#FFD399', '#EEB577');
        $table->AddBackgroundCondition('Postage_Days', '1', '==', '#FFF499', '#EEE177');

        if($session->Warehouse->Type == 'B') {
        	$table->AddBackgroundCondition('Is_Absent_Stock_Profile', 'Y', '==', '#9F77EE', '#BB99FF');
        }

        $table->AddBackgroundCondition('Is_Warehouse_Undeclined', 'Y', '==', '#99FF99', '#77EE77');
        $table->AddBackgroundCondition('TaxExemptCode', '', '!=', '#FF9999', '#EE7777');
        $table->AddBackgroundCondition('Is_Plain_Label', 'Y', '==', '#99C5FF', '#77B0EE');
        $table->AddBackgroundCondition('Warehouse_Total', '100.00', '>', '#99FFFB', '#8EECE8');
        $table->AddField('', 'Is_Restocked', 'hidden');
        $table->AddField('', 'TaxExemptCode', 'hidden');
        $table->AddField('', 'Is_Warehouse_Undeclined', 'hidden');
        $table->AddField('', 'Postage_Days', 'hidden');
        $table->AddField('', 'Is_Plain_Label', 'hidden');
        $table->AddField('', 'Warehouse_Total', 'hidden');
        $table->AddField('', 'Is_Absent_Stock_Profile', 'hidden');
        $table->AddField('Order Date', 'Created_On', 'left');
        $table->AddField('Postage Details', 'Postage_Title', 'left');
        $table->AddField('Order Prefix', 'Order_Prefix', 'center');
        $table->AddField('Order Number', 'Order_ID', 'left');
        $table->AddField('Status', 'Status', 'left');
        $table->AddField('Expected', 'Backorder_Date', 'left');
        $table->AddLink("order_details.php?orderid=%s", "<img src=\"../ignition/images/folderopen.gif\" alt=\"Open Order Details\" border=\"0\">", "Order_ID");
        $table->SetMaxRows(25);
        $table->SetOrderBy("Created_On");
        $table->Order = "DESC";
        $table->Finalise();
        $table->DisplayTable();
        echo '<br />';
        $table->DisplayNavigation();
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