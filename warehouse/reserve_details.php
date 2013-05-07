<?php
require_once('lib/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Reserve.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReserveItem.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseReserve.php');

$session->Secure(3);

$reserve = new Reserve($_REQUEST['id']);
$reserve->getLines();

if($session->Warehouse->Contact->ID != $reserve->supplier->ID) {
	redirect(sprintf('Location: reserves_pending.php'));	
}

$isEditable = (strtolower($reserve->status) != 'completed') ? true : false;

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Reserve ID', 'hidden', $reserve->id, 'numeric_unsigned', 1, 11);

if($isEditable) {
	for($i=0; $i<count($reserve->line); $i++) {
		$data = new DataQuery(sprintf("SELECT Supplier_SKU FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($reserve->supplier->ID), mysql_real_escape_string($reserve->line[$i]->product->ID)));
		$supplierSku = $data->Row['Supplier_SKU'];
		$data->Disconnect();

		$form->AddField('product_quantity_'.$reserve->line[$i]->id, sprintf('Quantity for \'%s\'', $reserve->line[$i]->product->Name), 'text', 0, 'numeric_unsigned', 1, 11, false, 'size="5"');
		$form->AddField('product_sku_'.$reserve->line[$i]->id, sprintf('SKU for \'%s\'', $reserve->line[$i]->product->Name), 'text', $supplierSku, 'paragraph', 1, 30, false);
	}
}

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		for($i=0; $i<count($reserve->line); $i++) {
			if($form->GetValue('product_quantity_'.$reserve->line[$i]->id) > $reserve->line[$i]->quantityRemaining) {
				$form->AddError(sprintf('Quantity Reserving for \'%s\' cannot exceed the quantity remaining.', $reserve->line[$i]->product->Name), 'product_quantity_'.$reserve->line[$i]->id);
			}
		}

		if($form->Valid) {
			for($i=0; $i<count($reserve->line); $i++) {
				$data = new DataQuery(sprintf("SELECT Supplier_Product_ID FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($reserve->supplier->ID), mysql_real_escape_string($reserve->line[$i]->product->ID)));
				if($data->TotalRows > 0) {
					$supplierProduct = new SupplierProduct($data->Row['Supplier_Product_ID']);
					$supplierProduct->SKU = $form->GetValue('product_sku_'.$reserve->line[$i]->id);
					$supplierProduct->Update();
				} else {
					$supplierProduct = new SupplierProduct();
					$supplierProduct->Supplier->ID = $reserve->supplier->ID;
					$supplierProduct->Product->ID = $reserve->line[$i]->product->ID;
					$supplierProduct->SKU = $form->GetValue('product_sku_'.$reserve->line[$i]->id);
					$supplierProduct->Add();
				}
				$data->Disconnect();
			}

			$warehouse = new Warehouse();

			if($warehouse->GetByType($reserve->supplier->ID, 'S')) {
				for($i=0; $i<count($reserve->line); $i++) {
					if($form->GetValue('product_quantity_'.$reserve->line[$i]->id) > 0) {
						$warehouseReserve = new WarehouseReserve();
						$warehouseReserve->warehouse->ID = $warehouse->ID;
						$warehouseReserve->product->ID = $reserve->line[$i]->product->ID;
						$warehouseReserve->quantity = $form->GetValue('product_quantity_'.$reserve->line[$i]->id);
						$warehouseReserve->add();

						$reserve->line[$i]->quantityRemaining -= $form->GetValue('product_quantity_'.$reserve->line[$i]->id);
						$reserve->line[$i]->update();
					}
				}
			}

			$complete = true;

			for($i=0; $i<count($reserve->line); $i++) {
				if($reserve->line[$i]->quantityRemaining > 0) {
					$complete = false;
				}
			}

			if($complete) {
				$reserve->complete();
			}

			redirect(sprintf('Location: ?id=%d', $reserve->id));
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
			<h1>Reserve Details</h1>
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
				        <th>Reserve:</th>
				        <td>#<?php echo $reserve->id; ?></td>
				      </tr>
				      <tr>
				        <th>Status:</th>
				        <td><?php echo $reserve->status; ?></td>
				      </tr>
				      <tr>
				        <th>Supplier:</th>
				        <td>
				        	<?php
				        	$supplier = new Supplier($reserve->supplier->ID);
				        	$supplier->Contact->Get();

				        	echo $supplier->Contact->Person->GetFullName();
				        	?>
				        </td>
				      </tr>
				      <tr>
				        <th>&nbsp;</th>
				        <td>&nbsp;</td>
				      </tr>
				      <tr>
				        <th>Created On:</th>
				        <td><?php echo cDatetime($reserve->createdOn, 'shortdate'); ?></td>
				      </tr>
				      <tr>
				        <th>Created By:</th>
				        <td>
				        	<?php
				        	$user = new User();
				        	$user->ID = $reserve->createdBy;

				        	if($user->Get()) {
				        		echo trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName));
				        	}
				        	?>
				        	&nbsp;
				        </td>
				      </tr>
				    </table><br />

			   </td>
			  </tr>
			  <tr>
			  	<td valign="top">

					<?php
					if($isEditable) {
						if($reserve->status != 'Completed') {
							echo sprintf('<input name="complete" type="submit" value="Complete" class="submit" /> ', $reserve->id);
						}
					}
					?>

					<input name="print" type="button" value="Print" class="greySubmit" onclick="popUrl('reserve_print.php?id=<?php echo $reserve->id; ?>', 800, 600);" />

					<br />

			  	</td>
			  	<td align="right" valign="top"></td>
			  </tr>
			  <tr>
			    <td colspan="2">
					<br />

					<div style="background-color: #eee; padding: 10px 0 10px 0;">
					 	<p><span class="pageSubTitle">Products</span><br /><span class="pageDescription">Listing products requesting reserving.</span></p>

					 	<table cellspacing="0" class="orderDetails">
							<tr>
						        <th nowrap="nowrap" style="padding-right: 5px;">Quantity</th>
								<th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
						        <th nowrap="nowrap" style="padding-right: 5px;">Name</th>
						        <th nowrap="nowrap" style="padding-right: 5px;">SKU</th>
						        <th nowrap="nowrap" style="padding-right: 5px;">Expires</th>
						        <th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Cost</th>

						        <?php
						        if($isEditable) {
						        	echo '<th nowrap="nowrap" style="padding-right: 5px;">Quantity Remaining</th>';
						        	echo '<th nowrap="nowrap" style="padding-right: 5px;">Quantity Reserving</th>';
						        }
						        ?>
						        
							</tr>

							<?php
							if(count($reserve->line) > 0) {
								for($i=0; $i<count($reserve->line); $i++) {
									?>

									<tr>
										<td nowrap="nowrap"><?php echo number_format(round($reserve->line[$i]->quantity, 2), 2, '.', ''); ?></td>
										<td nowrap="nowrap"><?php echo $reserve->line[$i]->product->ID; ?></td>
										<td nowrap="nowrap"><?php echo $reserve->line[$i]->product->Name; ?></td>
										<td nowrap="nowrap">
											<?php
											if($isEditable) {
												echo $form->GetHTML('product_sku_'.$reserve->line[$i]->id);
											} else {
												$data = new DataQuery(sprintf("SELECT Supplier_SKU FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($reserve->supplier->ID), mysql_real_escape_string($reserve->line[$i]->product->ID)));
												echo $data->Row['Supplier_SKU'];
												$data->Disconnect();
											}
											?>
										</td>
										<td nowrap="nowrap">
											<?php
											$data = new DataQuery(sprintf("SELECT DropSupplierExpiresOn FROM product WHERE Product_ID=%d AND DropSupplierID=%d", mysql_real_escape_string($reserve->line[$i]->product->ID), mysql_real_escape_string($reserve->supplier->ID)));
											echo ($data->TotalRows > 0) ? cDatetime($data->Row['DropSupplierExpiresOn'], 'shortdate') : '';
											$data->Disconnect();
											?>
										</td>
										<td nowrap="nowrap" align="right">
											<?php
											$data = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Supplier_ID=%d", mysql_real_escape_string($reserve->line[$i]->product->ID), mysql_real_escape_string($reserve->supplier->ID)));
											echo ($data->TotalRows > 0) ? sprintf('&pound;%s', number_format(round($data->Row['Cost'], 2), 2, '.', ',')) : '';
											$data->Disconnect();
											?>
										</td>

										<?php
						        		if($isEditable) {
						        			echo sprintf('<td nowrap="nowrap">%s</td>', number_format(round($reserve->line[$i]->quantityRemaining, 2), 2, '.', ''));
											echo sprintf('<td nowrap="nowrap">%s</td>', $form->GetHTML('product_quantity_'.$reserve->line[$i]->id));
										}
										?>
										
									</tr>

									<?php
								}
							} else {
						      	?>

						      	<tr>
						      		<td colspan="<?php echo ($isEditable) ? 8 : 6; ?>" align="center">No products available for viewing.</td>
						      	</tr>

						      	<?php
							}
						  	?>
					    </table>
					    <br />

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