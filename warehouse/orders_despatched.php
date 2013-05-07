<?php
require_once('lib/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

$session->Secure();

if(isset($_REQUEST['status'])) {
	$_SESSION['status'] = $_REQUEST['status'];
}

if(!isset($_SESSION['status'])) {
	$_SESSION['status'] = 'N';
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('status','Filter by status','select',$_SESSION['status'],'alpha_numeric',0,40,false);
$form->AddOption('status','N','No filter');
$form->AddOption('status','PK','View Despatched orders only');
$form->AddOption('status','PD','View partially despatched orders');
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
		<h1>Despatch History</h1><br />
        <?php
        $window = new StandardWindow('Filter orders');
        $webForm = new StandardForm();

        echo $form->Open();
        echo $window->Open();
        echo $window->OpenContent();
        echo $webForm->Open();
        echo $webForm->AddRow($form->GetLabel('status'),$form->GetHTML('status').'<input type="submit" name="search" value="Search" class="submit">');
        echo $webForm->Close();
        echo $window->CloseContent();
        echo $window->Close();
        echo $form->Close();

        echo "<br>";

        $branchFinder = new DataQuery(sprintf("SELECT * FROM users WHERE User_ID = %d ",mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
        $branchID = $branchFinder->Row['Branch_ID'];
        $branchFinder->Disconnect();

        if($branchID == 0){
        	$branchFinder = new DataQuery("SELECT * FROM branch WHERE Is_HQ='Y'");
        	$branchID = $branchFinder->Row['Branch_ID'];
        	$branchFinder->Disconnect();
        }

		$sqlDate = ($session->Warehouse->Type == 'S') ? sprintf(" AND o.Created_On>ADDDATE(NOW(), INTERVAL -7 DAY)") : '';

        if($form->GetValue('status')=='N'){
        	if($session->Warehouse->Type == 'S') {
        		$sql = sprintf("SELECT o.* from warehouse w
					INNER JOIN order_line ol on ol.Despatch_From_ID = w.Warehouse_ID
					INNER JOIN orders o on o.Order_ID = ol.Order_ID
					where w.Warehouse_ID = %d AND ol.Despatch_ID != 0 %s
					AND (Status LIKE '%%partial%%' or o.Status LIKE 'Despatched')
					GROUP BY o.Order_ID",mysql_real_escape_string($session->Warehouse->ID), $sqlDate);
        	} elseif($session->Warehouse->Type == 'B') {
        		$sql = sprintf("SELECT o.* from warehouse w
					INNER JOIN order_line ol on ol.Despatch_From_ID = w.Warehouse_ID
					INNER JOIN orders o on o.Order_ID = ol.Order_ID
					LEFT JOIN supplier AS s ON w.Type_Reference_ID=s.Supplier_ID AND w.Type='S'
					WHERE ((w.Type='B' AND w.Warehouse_ID=%d) OR (w.Type='S' AND s.Is_Drop_Shipper='N' AND s.Drop_Shipper_ID=%d)) AND ol.Despatch_ID<>0 %s
					AND (Status LIKE '%%partial%%' or o.Status LIKE 'Despatched')
					GROUP BY o.Order_ID", mysql_real_escape_string($session->Warehouse->ID), mysql_real_escape_string($session->Warehouse->Contact->ID), $sqlDate);
        	}
        } elseif($form->GetValue('status')=='PK'){
        	if($session->Warehouse->Type == 'S') {
        		$sql = sprintf("SELECT o.* from warehouse w
					INNER JOIN order_line ol on ol.Despatch_From_ID = w.Warehouse_ID
					INNER JOIN orders o on o.Order_ID = ol.Order_ID
					where w.Warehouse_ID = %d AND ol.Despatch_ID != 0 %s
					AND o.Status LIKE 'Despatched' GROUP BY o.Order_ID",mysql_real_escape_string($session->Warehouse->ID), $sqlDate);
        	} elseif($session->Warehouse->Type == 'B') {
        		$sql = sprintf("SELECT o.* from warehouse w
					INNER JOIN order_line ol on ol.Despatch_From_ID = w.Warehouse_ID
					INNER JOIN orders o on o.Order_ID = ol.Order_ID
					LEFT JOIN supplier AS s ON w.Type_Reference_ID=s.Supplier_ID AND w.Type='S'
					WHERE ((w.Type='B' AND w.Warehouse_ID=%d) OR (w.Type='S' AND s.Is_Drop_Shipper='N' AND s.Drop_Shipper_ID=%d)) AND ol.Despatch_ID<>0 %s
					AND o.Status LIKE 'Despatched'
					GROUP BY o.Order_ID", mysql_real_escape_string($session->Warehouse->ID), mysql_real_escape_string($session->Warehouse->Contact->ID), $sqlDate);
        	}
        } else {
        	if($session->Warehouse->Type == 'S') {
        		$sql = sprintf("SELECT o.* from warehouse w
					INNER JOIN order_line ol on ol.Despatch_From_ID = w.Warehouse_ID
					INNER JOIN orders o on o.Order_ID = ol.Order_ID
					where w.Warehouse_ID = %d AND ol.Despatch_ID != 0 %s
					AND Status LIKE '%%partial%%' GROUP BY o.Order_ID
					",$session->Warehouse->ID, $sqlDate);
        	} elseif($session->Warehouse->Type == 'B') {
        		$sql = sprintf("SELECT o.* from warehouse w
					INNER JOIN order_line ol on ol.Despatch_From_ID = w.Warehouse_ID
					INNER JOIN orders o on o.Order_ID = ol.Order_ID
					LEFT JOIN supplier AS s ON w.Type_Reference_ID=s.Supplier_ID AND w.Type='S'
					WHERE ((w.Type='B' AND w.Warehouse_ID=%d) OR (w.Type='S' AND s.Is_Drop_Shipper='N' AND s.Drop_Shipper_ID=%d)) AND ol.Despatch_ID<>0 %s
					AND Status LIKE '%%partial%%'
					GROUP BY o.Order_ID", mysql_real_escape_string($session->Warehouse->ID), mysql_real_escape_string($session->Warehouse->Contact->ID), $sqlDate);
        	}
        }

        $table = new DataTable("orders");
        $table->SetSQL($sql);
        $table->AddField('Order Date', 'Ordered_On', 'left');
        $table->AddField('Order Prefix', 'Order_Prefix', 'left');
        $table->AddField('Order Number', 'Order_ID', 'right');
        $table->AddField('Status', 'Status', 'right');
        $table->AddLink("order_details.php?orderid=%s", "<img src=\"../ignition/images/folderopen.gif\" alt=\"Open Order Details\" border=\"0\">", "Order_ID");
        $table->SetMaxRows(25);
        $table->SetOrderBy("Ordered_On");
        $table->Order = "DESC";
        $table->Finalise();
        $table->DisplayTable();
        echo "<br>";
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