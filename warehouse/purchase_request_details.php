<?php
require_once('lib/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseRequest.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseRequestLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProductPrice.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProductPriceCollection.php');

$session->Secure(3);

$supplierId = $session->Warehouse->Contact->ID;

$purchaseRequest = new PurchaseRequest($_REQUEST['id']);
$purchaseRequest->GetLines();

$isEditable = (strtolower($purchaseRequest->Status) == 'pending') ? true : false;

if($purchaseRequest->Supplier->ID != $supplierId) {
	redirect(sprintf("Location: purchase_requests_pending.php"));
}

if($action == "confirm") {
	$purchaseRequest->Status = 'Confirmed';
	$purchaseRequest->Update();

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $purchaseRequest->ID));
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Purchase Request ID', 'hidden', $purchaseRequest->ID, 'numeric_unsigned', 1, 11);

$prices = new SupplierProductPriceCollection();
$pricesStore = array();

for($k=0; $k<count($purchaseRequest->Line); $k++) {
	$purchaseRequest->Line[$k]->Product->Get();

	$prices->Reset();
	$prices->GetPrices($purchaseRequest->Line[$k]->Product->ID, $purchaseRequest->Supplier->ID);

	$pricesStore[$k] = $prices->GetPrice($purchaseRequest->Line[$k]->Quantity);

	if($isEditable) {
		$form->AddField(sprintf('cost_%d', $purchaseRequest->Line[$k]->ID), sprintf('Cost for \'%s\'', $purchaseRequest->Line[$k]->Product->Name), 'text', $pricesStore[$k], 'float', 1, 11, true, 'size="5"');
		$form->AddField(sprintf('stocked_%d', $purchaseRequest->Line[$k]->ID), sprintf('Is Stocked for \'%s\'', $purchaseRequest->Line[$k]->Product->Name), 'checkbox', $purchaseRequest->Line[$k]->IsStocked, 'boolean', 1, 1, false, sprintf('onclick="toggleFields(this, %d);"', $purchaseRequest->Line[$k]->ID));
		$form->AddField(sprintf('arrival_%d', $purchaseRequest->Line[$k]->ID), sprintf('Stock Arrival (Days) for \'%s\'', $purchaseRequest->Line[$k]->Product->Name), 'text', $purchaseRequest->Line[$k]->StockArrivalDays, 'float', 1, 11, true, 'size="5"' . (($purchaseRequest->Line[$k]->IsStocked == 'Y') ? ' disabled="disabled"': ''));
		$form->AddField(sprintf('available_%d', $purchaseRequest->Line[$k]->ID), sprintf('Stock Available for \'%s\'', $purchaseRequest->Line[$k]->Product->Name), 'text', $purchaseRequest->Line[$k]->StockAvailable, 'float', 1, 11, true, 'size="5"' . (($purchaseRequest->Line[$k]->IsStocked == 'Y') ? ' disabled="disabled"': ''));
	}
}

if(isset($_REQUEST['confirm'])) {
	if(isset($_REQUEST['updateproducts'])) {
		if($form->Validate()) {
			for($k=0; $k<count($purchaseRequest->Line); $k++) {
				$purchaseRequest->Line[$k]->IsStocked = $form->GetValue(sprintf('stocked_%d', $purchaseRequest->Line[$k]->ID));
				$purchaseRequest->Line[$k]->StockArrivalDays = ($purchaseRequest->Line[$k]->IsStocked == 'N') ? $form->GetValue(sprintf('arrival_%d', $purchaseRequest->Line[$k]->ID)) : 0;
				$purchaseRequest->Line[$k]->StockAvailable = ($purchaseRequest->Line[$k]->IsStocked == 'N') ? $form->GetValue(sprintf('available_%d', $purchaseRequest->Line[$k]->ID)) : 0;
				$purchaseRequest->Line[$k]->Update();

				if($pricesStore[$k] != $form->GetValue(sprintf('cost_%d', $purchaseRequest->Line[$k]->ID))) {
                    $price = new SupplierProductPrice();
					$price->Product->ID = $purchaseRequest->Line[$k]->Product->ID;
					$price->Supplier->ID = $purchaseRequest->Supplier->ID;
					$price->Quantity = $purchaseRequest->Line[$k]->Quantity;
					$price->Cost = $form->GetValue(sprintf('cost_%d', $purchaseRequest->Line[$k]->ID));
					$price->Add();
				}
			}

			redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $purchaseRequest->ID));
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
	<script language="javascript" type="text/javascript">
    var toggleFields = function(obj, id) {
    	var arrival = document.getElementById('arrival_'+id);
    	var available = document.getElementById('available_'+id);

    	if(arrival && available) {
			if(obj.checked) {
				arrival.setAttribute('disabled', 'disabled');
				available.setAttribute('disabled', 'disabled');
			} else {
				arrival.removeAttribute('disabled');
				available.removeAttribute('disabled');
			}
		}
    }
    </script>
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
			<h1>Purchase Request Details</h1>
			<br />

			<?php
			if(!$form->Valid){
				echo $form->GetError();
				echo '<br />';
			}

			echo $form->Open();
			echo $form->GetHTML('confirm');
			echo $form->GetHTML('id');
			?>

			<table width="100%" border="0" cellspacing="0" cellpadding="0">
			  <tr>
			    <td align="left" valign="top"></td>
			    <td align="right" valign="top">

			    <table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
			      <tr>
			        <th>Purchase Request:</th>
			        <td>#<?php echo $purchaseRequest->ID; ?></td>
			      </tr>
			      <tr>
			        <th>Status:</th>
			        <td><?php echo $purchaseRequest->Status; ?></td>
			      </tr>
			      <tr>
			        <th>Supplier:</th>
			        <td>
			        	<?php
						$data = new DataQuery(sprintf("SELECT s.Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Supplier_Name FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE s.Supplier_ID=%d", mysql_real_escape_string($purchaseRequest->Supplier->ID)));
						echo ($data->TotalRows > 0) ? $data->Row['Supplier_Name'] : '&nbsp;';
						$data->Disconnect();
			        	?>
			        </td>
			      </tr>
			      <tr>
			        <th>&nbsp;</th>
			        <td>&nbsp;</td>
			      </tr>
			      <tr>
			        <th>Created On:</th>
			        <td><?php echo cDatetime($purchaseRequest->CreatedOn, 'shortdate'); ?></td>
			      </tr>
			    </table><br />

			   </td>
			  </tr>
			  <tr>
			    <td colspan="2">
			    	<br />

					<?php
					if($isEditable) {
						?>

						<div style="background-color: #eee; padding: 10px 0 10px 0;">
				 			<p><span class="pageSubTitle">Finished?</span><br /><span class="pageDescription">Confirm these stock settings by clicking the button below.</span></p>

		    				<input name=confirm type="button" value="Confirm" class="submit" onclick="confirmRequest('<?php echo $_SERVER['PHP_SELF']; ?>?action=confirm&id=<?php echo $purchaseRequest->ID; ?>', 'Are you sure you wish to confirm these stock settings?');" />
		    			</div><br />

						<?php
					}
					?>

					<div style="background-color: #eee; padding: 10px 0 10px 0;">
					 	<p><span class="pageSubTitle">Products</span><br /><span class="pageDescription">Listing stock requested for a purchase order.</span></p>

					 	<table cellspacing="0" class="orderDetails">
							<tr>
								<th nowrap="nowrap" style="padding-right: 5px;">Quantity</th>
								<th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
					      		<th nowrap="nowrap" style="padding-right: 5px;">Name</th>
					      		<th nowrap="nowrap" style="padding-right: 5px;">SKU</th>
					      		<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Cost</th>
					      		<th nowrap="nowrap" style="padding-right: 5px; text-align: center;">Is Stocked</th>
					      		<th nowrap="nowrap" style="padding-right: 5px;">Stock Arrival (Days)</th>
					      		<th nowrap="nowrap" style="padding-right: 5px;">Stock Available</th>
					      	</tr>

							<?php
							if(count($purchaseRequest->Line) > 0) {
								for($k=0; $k<count($purchaseRequest->Line); $k++) {
									$data = new DataQuery(sprintf("SELECT Supplier_SKU FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($purchaseRequest->Supplier->ID), mysql_real_escape_string($purchaseRequest->Line[$k]->Product->ID)));
									$sku = ($data->TotalRows > 0) ? $data->Row['Supplier_SKU'] : '';
									$data->Disconnect();
									?>

									<tr>
										<td nowrap="nowrap"><?php echo $purchaseRequest->Line[$k]->Quantity; ?></td>
							      		<td nowrap="nowrap"><?php echo $purchaseRequest->Line[$k]->Product->ID; ?></td>
							      		<td nowrap="nowrap"><?php echo $purchaseRequest->Line[$k]->Product->Name; ?></td>
							      		<td nowrap="nowrap"><?php echo $sku; ?></td>
							      		<td nowrap="nowrap" align="right">&pound;<?php echo ($isEditable) ? $form->GetHTML(sprintf('cost_%d', $purchaseRequest->Line[$k]->ID)) : number_format(round($pricesStore[$k], 2), 2, '.', ','); ?></td>
							      		<td nowrap="nowrap" align="center"><?php echo ($isEditable) ? $form->GetHTML(sprintf('stocked_%d', $purchaseRequest->Line[$k]->ID)) : $purchaseRequest->Line[$k]->IsStocked; ?></td>
							      		<td nowrap="nowrap"><?php echo ($isEditable) ? $form->GetHTML(sprintf('arrival_%d', $purchaseRequest->Line[$k]->ID)) : $purchaseRequest->Line[$k]->StockArrivalDays; ?></td>
							      		<td nowrap="nowrap"><?php echo ($isEditable) ? $form->GetHTML(sprintf('available_%d', $purchaseRequest->Line[$k]->ID)) : $purchaseRequest->Line[$k]->StockAvailable; ?></td>
									</tr>

									<?php
								}
							} else {
						      	?>

						      	<tr>
									<td colspan="8" align="center">No products available for viewing.</td>
						      	</tr>

						      	<?php
							}
							?>
					    </table><br />

						<?php
						if($isEditable) {
							?>

							<table cellspacing="0" cellpadding="0" border="0" width="100%">
								<tr>
									<td align="left">
										<input type="submit" name="updateproducts" value="Update" class="greySubmit" />
									</td>
									<td align="right"></td>
								</tr>
							</table>

							<?php
						}
						?>

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