<?php
require_once('lib/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Despatch.php');

function getWarehouses($warehouseId) {
	$warehouses = array($warehouseId);

	$data = new DataQuery(sprintf("SELECT Warehouse_ID FROM warehouse WHERE Parent_Warehouse_ID=%d", $warehouseId));
	while($data->Row) {
		$warehouses = array_merge($warehouses, getWarehouses($data->Row['Warehouse_ID']));

		$data->Next();
	}
	$data->Disconnect();

	return $warehouses;
}

$session->Secure();

$warehouses = getWarehouses($session->Warehouse->ID);

$form = new Form($_SERVER['PHP_SELF'], 'GET');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('orderid', 'Order ID', 'text', '', 'numeric_unsigned', 1, 11, false);
$form->AddField('start', 'Despatched After', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
$form->AddField('end', 'Despatched Before', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');

$sqlSelect = '';
$sqlFrom = '';
$sqlWhere = '';

if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
	if($form->Validate()) {
		$sqlSelect = sprintf("SELECT o.Order_ID, o.Order_Prefix, d.Despatch_ID, d.Despatched_On, d.Consignment, d.Courier_ID, c.Courier_Name ");
		$sqlFrom = sprintf("FROM despatch AS d INNER JOIN orders AS o ON o.Order_ID=d.Order_ID INNER JOIN courier AS c ON c.Courier_ID=d.Courier_ID ");
		$sqlWhere = sprintf("WHERE (d.Despatch_From_ID=%s) ", implode(' OR d.Despatch_From_ID=', $warehouses));

		if(strlen($form->GetValue('start')) > 0) {
			$sqlWhere .= sprintf("AND d.Despatched_On>='%s' ", sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)));
		}

		if(strlen($form->GetValue('end')) > 0) {
			$sqlWhere .= sprintf("AND d.Despatched_On<='%s' ", sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)));
		}

		if(strlen($form->GetValue('orderid')) > 0) {
			$sqlWhere .= sprintf("AND d.Order_ID=%d ", $form->GetValue('orderid'));
		}
	}
}

if(isset($_REQUEST['update'])) {
	$despatches = array();

	foreach($_REQUEST as $key=>$value) {
		if(preg_match('/(consignment|courier)_([\d]*)/', $key, $matches)) {
			$despatches[$matches[2]] = $matches[2];			
		}
	}

	foreach($despatches as $despatchId) {
		$data = new DataQuery(sprintf("SELECT Consignment, Courier_ID FROM despatch WHERE Despatch_ID=%d", $despatchId));
		if($data->TotalRows > 0) {
			$consignmentUpdated = false;

			$key = 'courier_' . $despatchId;
			
			if(isset($_REQUEST[$key])) {
				$value = $_REQUEST[$key];
				
				if($data->Row['Courier_ID'] != $value) {
					if($value > 0) {
						$consignmentUpdated = true;
					}

					new DataQuery(sprintf("UPDATE despatch SET Courier_ID=%d WHERE Despatch_ID=%d", $value, $despatchId));
				}
			}
			
			$key = 'consignment_' . $despatchId;

			if(isset($_REQUEST[$key])) {
				$value = $_REQUEST[$key];
				
				if(strtolower(trim($data->Row['Consignment'])) != strtolower(trim($value))) {
					if(strlen(trim($value)) > 0) {
						$consignmentUpdated = true;
					}

					new DataQuery(sprintf("UPDATE despatch SET Consignment='%s' WHERE Despatch_ID=%d", addslashes(stripslashes($value)), $despatchId));
				}
			}
			
			if($consignmentUpdated) {
				$despatch = new Despatch($despatchId);
				$despatch->EmailConsignment();
				$despatch->SmsConsignment();
			}
		}
		$data->Disconnect();
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/templates/portal-warehouse.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Track Consignments</title>
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
    <script language="javascript" src="/ignition/js/scw.js" type="text/javascript"></script>
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
		<h1>Track Consignments</h1><br />

        <?php
        if(!$form->Valid) {
        	echo $form->GetError();
        	echo '<br />';
        }

        echo $form->Open();
        echo $form->GetHTML('confirm');

        $window = new StandardWindow('Search for despatched consignments');
        $webForm = new StandardForm();

        echo $window->Open();
        echo $window->OpenContent();
        echo $webForm->Open();
        echo $webForm->AddRow($form->GetLabel('orderid'), $form->GetHTML('orderid'));
        echo $webForm->AddRow('', '<input type="submit" name="search" value="Search" class="submit">');
        echo $webForm->Close();
        echo $window->CloseContent();

        echo $window->OpenContent();
        echo $webForm->Open();
        echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start'));
        echo $webForm->AddRow($form->GetLabel('end'), $form->GetHTML('end'));
        echo $webForm->AddRow('', '<input type="submit" name="search" value="Search" class="submit">');
        echo $webForm->Close();
        echo $window->CloseContent();
        echo $window->Close();

        if(strlen(sprintf('%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere)) > 0) {
	        echo '<br />';

			$table = new DataTable('results');
			$table->SetExtractVars();
			$table->SetSQL(sprintf('%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere));
			$table->AddField('Despatch Date', 'Despatched_On', 'left');
			$table->AddField('Despatch ID#', 'Despatch_ID', 'left');
			$table->AddField('Order ID#', 'Order_ID', 'left');
			$table->AddInput('Courier', 'Y', 'Courier_ID', 'courier', 'Despatch_ID', 'select');
			$table->AddInputOption('Courier', '0', '');

			$data = new DataQuery(sprintf("SELECT Courier_ID, Courier_Name FROM courier ORDER BY Courier_Name ASC"));
			while($data->Row) {
				$table->AddInputOption('Courier', $data->Row['Courier_ID'], $data->Row['Courier_Name']);

				$data->Next();
			}
			$data->Disconnect();

			$table->AddInput('Consignment', 'Y', 'Consignment', 'consignment', 'Despatch_ID', 'text');
			$table->SetMaxRows(25);
	        $table->SetOrderBy('Despatched_On');
	        $table->Order = 'ASC';
			$table->Finalise();
			$table->DisplayTable();

			echo '<br />';

			$table->DisplayNavigation();

			echo '<br />';
			echo '<input type="submit" name="update" value="Update" class="submit" />';
        }

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