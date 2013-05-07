<?php
require_once('lib/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');

if($action == 'export') {
	$session->Secure(2);
	export();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function view() {
	global $session;

	$months = 12;
	$minimumOrders = 5;

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

	$unstockedGrouped = array();

	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, p.SKU, COUNT(DISTINCT ol.Order_ID) AS Orders, SUM(ol.Quantity) AS Quantities, SUM(ol.Cost*ol.Quantity) AS Cost, p.CacheBestSupplierID, IF(s1c.Parent_Contact_ID>0, CONCAT_WS(' ', s1o.Org_Name, CONCAT('(', CONCAT_WS(' ', s1p.Name_First, s1p.Name_Last), ')')), CONCAT_WS(' ', s1p.Name_First, s1p.Name_Last)) AS BestSupplier, p.CacheBestCost, sp.Cost AS SupplierCost, sp.Modified_On FROM (SELECT ol.Product_ID, ol.Order_ID, ol.Quantity, ol.Cost FROM order_line AS ol INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0 UNION ALL SELECT pc.Product_ID, ol.Order_ID, ol.Quantity*pc.Component_Quantity AS Quantity, ol.Cost/pc.Component_Quantity AS Cost FROM product_components AS pc INNER JOIN order_line AS ol ON ol.Product_ID=pc.Component_Of_Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0) AS ol INNER JOIN product AS p ON p.Product_ID=ol.Product_ID AND p.LockedSupplierID=0 AND p.Is_Stocked='N' LEFT JOIN supplier AS s1 ON s1.Supplier_ID=p.CacheBestSupplierID LEFT JOIN contact AS s1c ON s1c.Contact_ID=s1.Contact_ID LEFT JOIN person AS s1p ON s1p.Person_ID=s1c.Person_ID LEFT JOIN contact AS s1c2 ON s1c2.Contact_ID=s1c.Parent_Contact_ID LEFT JOIN organisation AS s1o ON s1o.Org_ID=s1c2.Org_ID LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID AND sp.Supplier_ID=%d GROUP BY ol.Product_ID HAVING Orders>=%d ORDER BY Orders DESC", mysql_real_escape_string($months), mysql_real_escape_string($months), mysql_real_escape_string($session->Warehouse->Contact->ID), mysql_real_escape_string($minimumOrders)));
	while($data->Row) {
		$unstockedGrouped[] = $data->Row;

		$data->Next();	
	}
	$data->Disconnect();

	foreach($unstockedGrouped as $stockedData) {
		$form->AddField('cost_' . $stockedData['Product_ID'], sprintf('Supplier Cost for \'%s\'', $stockedData['Product_Title']), 'text', number_format($stockedData['SupplierCost'], 2, '.', ''), 'float', 1, 11, true, 'size="3"');
	}

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			foreach($unstockedGrouped as $stockedData) {
				if($form->GetValue('cost_' . $stockedData['Product_ID']) > 0) {
					if($form->GetValue('cost_' . $stockedData['Product_ID']) < $stockedData['SupplierCost']) {
						$product = new SupplierProduct();
						
						if($product->GetBySupplierProduct($session->Warehouse->Contact->ID, $stockedData['Product_ID'])) {
							$product->Cost = $form->GetValue('cost_' . $stockedData['Product_ID']);
							$product->Update();
						} else {
							$product->Cost = $form->GetValue('cost_' . $stockedData['Product_ID']);
							$product->Add();
						}
					}
				}
			}

			redirectTo('?action=view');
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
					<h1>Stock Dropped Report</h1>
					<br />

					<?php
					if(!$form->Valid) {
						echo $form->GetError();
						echo '<br />';
					}

					echo $form->Open();
					echo $form->GetHTML('confirm');
					?>

					<h3>Best Supplier</h3>
					<br />

					<table width="100%" border="0">
						<tr>
							<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;"><strong>SKU</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Best Cost</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Supplier Cost</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;"><strong>Date Priced</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Orders</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Quantity</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Cost</strong></td>
						</tr>

						<?php
						if(!empty($unstockedGrouped)) {
							$totalCost = 0;

							foreach($unstockedGrouped as $stockedData) {
								if($stockedData['CacheBestSupplierID'] == $session->Warehouse->Contact->ID) {
									$totalCost += $stockedData['Cost'];
									?>

									<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
										<td><?php echo $stockedData['Product_ID']; ?></td>
										<td><?php echo $stockedData['Product_Title']; ?></td>
										<td><?php echo $stockedData['SKU']; ?></td>
										<td align="right">&pound;<?php echo number_format($stockedData['CacheBestCost'], 2, '.', ','); ?></td>
										<td align="right" nowrap="nowrap">&pound;<?php echo $form->GetHTML('cost_' . $stockedData['Product_ID']); ?></td>
										<td><?php echo cDatetime($stockedData['Modified_On'], 'shortdate'); ?></td>
										<td align="right"><?php echo $stockedData['Orders']; ?></td>
										<td align="right"><?php echo $stockedData['Quantities']; ?></td>
										<td align="right">&pound;<?php echo number_format($stockedData['Cost'], 2, '.', ','); ?></td>
									</tr>
									
									<?php
								}
							}
							?>

							<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td align="right"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
							</tr>

							<?php
						} else {
							?>
							
							<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
								<td colspan="8" align="center">There are no items available for viewing.</td>
							</tr>
							
							<?php
						}
						?>
						
					</table>
					<br />

					<h3>Not Best Supplier</h3>
					<br />

					<table width="100%" border="0">
						<tr>
							<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;"><strong>SKU</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Best Cost</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Supplier Cost</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;"><strong>Date Priced</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Orders</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Quantity</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Cost</strong></td>
						</tr>

						<?php
						if(!empty($unstockedGrouped)) {
							$totalCost = 0;

							foreach($unstockedGrouped as $stockedData) {
								if($stockedData['CacheBestSupplierID'] != $session->Warehouse->Contact->ID) {
									$totalCost += $stockedData['Cost'];
									?>

									<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
										<td><?php echo $stockedData['Product_ID']; ?></td>
										<td><?php echo $stockedData['Product_Title']; ?></td>
										<td><?php echo $stockedData['SKU']; ?></td>
										<td align="right">&pound;<?php echo number_format($stockedData['CacheBestCost'], 2, '.', ','); ?></td>
										<td align="right" nowrap="nowrap">&pound;<?php echo $form->GetHTML('cost_' . $stockedData['Product_ID']); ?></td>
										<td><?php echo cDatetime($stockedData['Modified_On'], 'shortdate'); ?></td>
										<td align="right"><?php echo $stockedData['Orders']; ?></td>
										<td align="right"><?php echo $stockedData['Quantities']; ?></td>
										<td align="right">&pound;<?php echo number_format($stockedData['Cost'], 2, '.', ','); ?></td>
									</tr>
									
									<?php
								}
							}
							?>

							<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td align="right"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
							</tr>

							<?php
						} else {
							?>
							
							<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
								<td colspan="8" align="center">There are no items available for viewing.</td>
							</tr>
							
							<?php
						}
						?>
						
					</table>
					<br />

					<input type="submit" class="submit" name="update" value="Update" />
					<input type="button" class="greySubmit" name="export" value="Export" onclick="window.self.location.href = '?action=export';" />

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

	<?php
}

function export() {
	global $session;

	$months = 12;
	$minimumOrders = 5;

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

	$unstockedGrouped = array();

	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, p.SKU, COUNT(DISTINCT ol.Order_ID) AS Orders, SUM(ol.Quantity) AS Quantities, SUM(ol.Cost*ol.Quantity) AS Cost, p.CacheBestSupplierID, IF(s1c.Parent_Contact_ID>0, CONCAT_WS(' ', s1o.Org_Name, CONCAT('(', CONCAT_WS(' ', s1p.Name_First, s1p.Name_Last), ')')), CONCAT_WS(' ', s1p.Name_First, s1p.Name_Last)) AS BestSupplier, p.CacheBestCost, sp.Cost AS SupplierCost FROM (SELECT ol.Product_ID, ol.Order_ID, ol.Quantity, ol.Cost FROM order_line AS ol INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0 UNION ALL SELECT pc.Product_ID, ol.Order_ID, ol.Quantity*pc.Component_Quantity AS Quantity, ol.Cost/pc.Component_Quantity AS Cost FROM product_components AS pc INNER JOIN order_line AS ol ON ol.Product_ID=pc.Component_Of_Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0) AS ol INNER JOIN product AS p ON p.Product_ID=ol.Product_ID AND p.LockedSupplierID=0 AND p.Is_Stocked='N' LEFT JOIN supplier AS s1 ON s1.Supplier_ID=p.CacheBestSupplierID LEFT JOIN contact AS s1c ON s1c.Contact_ID=s1.Contact_ID LEFT JOIN person AS s1p ON s1p.Person_ID=s1c.Person_ID LEFT JOIN contact AS s1c2 ON s1c2.Contact_ID=s1c.Parent_Contact_ID LEFT JOIN organisation AS s1o ON s1o.Org_ID=s1c2.Org_ID LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID AND sp.Supplier_ID=%d GROUP BY ol.Product_ID HAVING Orders>=%d ORDER BY Orders DESC", mysql_real_escape_string($months), mysql_real_escape_string($months), mysql_real_escape_string($session->Warehouse->Contact->ID), mysql_real_escape_string($minimumOrders)));
	while($data->Row) {
		$unstockedGrouped[] = $data->Row;

		$data->Next();	
	}
	$data->Disconnect();

	$fileDate = getDatetime();
	$fileDate = substr($fileDate, 0, strpos($fileDate, ' '));

	$fileName = sprintf('blt_stock_dropped_not_supplied_%s.csv', $fileDate);

	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Type: application/force-download");
	header("Content-Disposition: attachment; filename=" . basename($fileName) . ";");
	header("Content-Transfer-Encoding: binary");

	$line = array();
	$line[] = 'Quickfind';
	$line[] = 'Product';
	$line[] = 'SKU';
	$line[] = 'Best Cost';
	$line[] = 'Supplier Cost';
	$line[] = 'Orders';
	$line[] = 'Quantity';
	$line[] = 'Cost';

	echo getCsv($line);

	if(!empty($unstockedGrouped)) {
		$totalCost = 0;

		foreach($unstockedGrouped as $stockedData) {
			if($stockedData['CacheBestSupplierID'] != $session->Warehouse->Contact->ID) {
				$totalCost += $stockedData['Cost'];
				
				$line = array();
				$line[] = $stockedData['Product_ID'];
				$line[] = $stockedData['Product_Title'];
				$line[] = $stockedData['SKU'];
				$line[] = number_format($stockedData['CacheBestCost'], 2, '.', '');
				$line[] = number_format($stockedData['SupplierCost'], 2, '.', '');
				$line[] = $stockedData['Orders'];
				$line[] = $stockedData['Quantities'];
				$line[] = number_format($stockedData['Cost'], 2, '.', '');

				echo getCsv($line);
			}
		}
	}
}

function getCsv($row, $fd=',', $quot='"') {
	$str ='';

	foreach($row as $cell){
		$cell = str_replace($quot, $quot.$quot, $cell);

		if((strchr($cell, $fd) !== false) || (strchr($cell, $quot) !== false) || (strchr($cell, "\n") !== false)) {
			$str .= $quot.$cell.$quot.$fd;
		} else {
			$str .= $quot.$cell.$quot.$fd;
		}
	}

	return substr($str, 0, -1)."\n";
}

function parseSearchString($value, $fields = array()) {
	$sqlWhere = '';

	if(count($fields) > 0) {
		parse_search_string(stripslashes($value), $keywords);

		if(count($keywords) > 0) {
			$sqlWhere .= ' AND (';

			for($i=0; $i < count($keywords); $i++){
				switch(strtoupper($keywords[$i])) {
					case '(':
					case ')':
					case 'AND':
					case 'OR':
						$sqlWhere .= sprintf(" %s ", $keywords[$i]);
						break;
					default:
						$sqlWhere .= " (";

						foreach($fields as $field) {
							$sqlWhere .= sprintf("%s LIKE '%%%s%%' OR ", $field, addslashes(stripslashes($keywords[$i])));
						}

						$sqlWhere = substr($sqlWhere, 0, -4);
						$sqlWhere .= ")";
						break;
				}
			}

			$sqlWhere .= ') ';
		}
	}

	return $sqlWhere;
}