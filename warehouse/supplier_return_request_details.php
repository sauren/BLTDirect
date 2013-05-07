<?php
require_once('lib/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierReturnRequest.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');

$session->Secure(3);

$supplierId = $session->Warehouse->Contact->ID;

$returnRequest = new SupplierReturnRequest($_REQUEST['id']);
$returnRequest->GetLines();
$returnRequest->Courier->Get();

if($returnRequest->Order->ID > 0) {
	$returnRequest->Order->GetLines();

} elseif($returnRequest->Purchase->ID > 0) {
	for($k=0; $k<count($returnRequest->Line); $k++) {
		$returnRequest->Line[$k]->PurchaseLine->Get();
	}
}

$isEditable = (strtolower($returnRequest->Status) == 'pending') ? true : false;

if($returnRequest->Supplier->ID != $supplierId) {
	redirect(sprintf("Location: supplier_return_requests_pending.php"));
}

if($action == "confirm") {
	if(!empty($returnRequest->AuthorisationNumber)) {
		$returnRequest->Status = 'Confirmed';
		$returnRequest->Update();
	}

	redirect(sprintf("Location: %s?id=%d&complete=%s", $_SERVER['PHP_SELF'], $returnRequest->ID, !empty($returnRequest->AuthorisationNumber) ? 'true' : 'false'));
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Supplier Return Request ID', 'hidden', $returnRequest->ID, 'numeric_unsigned', 1, 11);
$form->AddField('authorisation', 'Authorisation Number', 'text', $returnRequest->AuthorisationNumber, 'anything', 0, 60, false);

for($k=0; $k<count($returnRequest->Line); $k++) {
	$returnRequest->Line[$k]->Type->Get();
	$returnRequest->Line[$k]->Product->Get();

	if($returnRequest->Line[$k]->RelatedProduct->ID > 0) {
		$returnRequest->Line[$k]->RelatedProduct->Get();
	}

	if($isEditable) {
		$form->AddField(sprintf('handling_method_%d', $returnRequest->Line[$k]->ID), sprintf('Handling Method for \'%s\'', $returnRequest->Line[$k]->Product->Name), 'select', $returnRequest->Line[$k]->HandlingMethod, 'alpha', 1, 1, true);
		$form->AddOption(sprintf('handling_method_%d', $returnRequest->Line[$k]->ID), 'R', 'Percentage');
		$form->AddOption(sprintf('handling_method_%d', $returnRequest->Line[$k]->ID), 'F', 'Fixed');
		$form->AddField(sprintf('handling_charge_%d', $returnRequest->Line[$k]->ID), sprintf('Handling Charge for \'%s\'', $returnRequest->Line[$k]->Product->Name), 'text', $returnRequest->Line[$k]->HandlingCharge, 'float', 1, 11, true, 'size="5"');
		$form->AddField(sprintf('rejected_%d', $returnRequest->Line[$k]->ID), sprintf('Is Rejected for \'%s\'', $returnRequest->Line[$k]->Product->Name), 'checkbox', $returnRequest->Line[$k]->IsRejected, 'boolean', 1, 1, false);
		$form->AddField(sprintf('reason_%d', $returnRequest->Line[$k]->ID), sprintf('Rejected Reason for \'%s\'', $returnRequest->Line[$k]->Product->Name), 'textarea', $returnRequest->Line[$k]->RejectedReason, 'anything', 0, 240, false, 'rows="2" style="font-family: arial, sans-serif; width: 100%;"');
	}
}

if(isset($_REQUEST['confirm'])) {
	if(isset($_REQUEST['update']) || isset($_REQUEST['updateproducts'])) {
		for($k=0; $k<count($returnRequest->Line); $k++) {
			$form->Validate(sprintf('handling_method_%d', $returnRequest->Line[$k]->ID));
			$form->Validate(sprintf('handling_charge_%d', $returnRequest->Line[$k]->ID));
			$form->Validate(sprintf('rejected_%d', $returnRequest->Line[$k]->ID));
			$form->Validate(sprintf('reason_%d', $returnRequest->Line[$k]->ID));
		}

		if($form->Valid) {
			for($k=0; $k<count($returnRequest->Line); $k++) {
				$returnRequest->Line[$k]->HandlingMethod = $form->GetValue(sprintf('handling_method_%d', $returnRequest->Line[$k]->ID));
				$returnRequest->Line[$k]->HandlingCharge = $form->GetValue(sprintf('handling_charge_%d', $returnRequest->Line[$k]->ID));
				$returnRequest->Line[$k]->IsRejected = $form->GetValue(sprintf('rejected_%d', $returnRequest->Line[$k]->ID));
				$returnRequest->Line[$k]->RejectedReason = $form->GetValue(sprintf('reason_%d', $returnRequest->Line[$k]->ID));
				$returnRequest->Line[$k]->Update();
			}

			$returnRequest->LinesFetched = false;
		}
	}

	if(isset($_REQUEST['update'])) {
		$form->Validate('authorisation');

		if($form->Valid) {
			$returnRequest->AuthorisationNumber = $form->GetValue('authorisation');
		}
	}

	if($form->Valid) {
		$returnRequest->Recalculate();

		redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $returnRequest->ID));
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
			<h1>Return Request Details</h1>
			<br />

			<?php
			if(isset($_REQUEST['complete']) && ($_REQUEST['complete'] == 'false')) {
				$bubble = new Bubble('Could Not Complete', 'You must specify an authorisation number before confirming this return request.');

				echo $bubble->GetHTML();
				echo '<br />';
			}

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
			        <th>Return Request:</th>
			        <td>#<?php echo $returnRequest->ID; ?></td>
			      </tr>
			      <tr>
			        <th>Status:</th>
			        <td><?php echo $returnRequest->Status; ?></td>
			      </tr>
			      <tr>
			        <th>Supplier:</th>
			        <td>
			        	<?php
						$data = new DataQuery(sprintf("SELECT s.Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Supplier_Name FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE s.Supplier_ID=%d", mysql_real_escape_string($returnRequest->Supplier->ID)));
						echo ($data->TotalRows > 0) ? $data->Row['Supplier_Name'] : '&nbsp;';
						$data->Disconnect();
			        	?>
			        </td>
			      </tr>

			      <?php
			      if($returnRequest->Order->ID > 0) {
			      	?>

				      <tr>
				        <th>Order:</th>
				        <td><?php echo $returnRequest->Order->ID; ?></td>
				      </tr>

				     <?php
			      }

			      if($returnRequest->Purchase->ID > 0) {
			      	?>

				      <tr>
				        <th>Purchase:</th>
				        <td><?php echo $returnRequest->Purchase->ID; ?></td>
				      </tr>

				     <?php
			      }
			      ?>

			      <tr>
			        <th>Courier:</th>
			        <td><?php echo $returnRequest->Courier->Name; ?></td>
			      </tr>
			      <tr>
			        <th>Authorisation Number:</th>
			        <td><?php echo ($isEditable) ? $form->GetHTML('authorisation') : $returnRequest->AuthorisationNumber; ?></td>
			      </tr>
			      <tr>
			        <th>&nbsp;</th>
			        <td>&nbsp;</td>
			      </tr>
			      <tr>
			        <th>Created On:</th>
			        <td><?php echo cDatetime($returnRequest->CreatedOn, 'shortdate'); ?></td>
			      </tr>
			    </table><br />

			   </td>
			  </tr>
			  <tr>
			  	<td valign="top"></td>
			  	<td align="right" valign="top">

				  	<?php
					if($isEditable) {
						?>

						<input name="update" type="submit" value="Update" class="greySubmit" />

						<?php
					}
					?>

				</td>
			  </tr>
			  <tr>
			    <td colspan="2">
			    	<br />

					<?php
					if($isEditable) {
						?>

						<div style="background-color: #eee; padding: 10px 0 10px 0;">
				 			<p><span class="pageSubTitle">Finished?</span><br /><span class="pageDescription">Confirm this product return request. You must provide an authorsation number before finishing.</span></p>

		    				<input name="confirm" type="button" value="Confirm" class="submit" onclick="confirmRequest('<?php echo $_SERVER['PHP_SELF']; ?>?action=confirm&id=<?php echo $returnRequest->ID; ?>', 'Are you sure you wish to confirm this return request?');" />
		    			</div><br />

						<?php
					}
					?>

					<div style="background-color: #eee; padding: 10px 0 10px 0;">
					 	<p><span class="pageSubTitle">Products</span><br /><span class="pageDescription">Listing products requesting for return.</span></p>

						<?php
						$columns = 11;
						?>

					 	<table cellspacing="0" class="orderDetails">
							<tr>
								<th nowrap="nowrap" style="padding-right: 5px;">Quantity<br />&nbsp;</th>
								<th nowrap="nowrap" style="padding-right: 5px;">Quickfind<br />&nbsp;</th>
					      		<th nowrap="nowrap" style="padding-right: 5px;">Name<br />&nbsp;</th>
					      		<th nowrap="nowrap" style="padding-right: 5px;">Type<br />&nbsp;</th>
					      		<th nowrap="nowrap" style="padding-right: 5px;">Related<br />Product</th>
					      		<th nowrap="nowrap" style="padding-right: 5px;">Reason<br />&nbsp;</th>

					      		<?php
					      		if($returnRequest->Purchase->ID > 0) {
					      			echo '<th nowrap="nowrap" style="padding-right: 5px;">Advice<br />Note</th>';
					      			$columns++;
					      		}
					      		?>

					      		<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Cost<br />&nbsp;</th>

					      		<?php
					      		if($isEditable) {
					      			echo '<th nowrap="nowrap" style="padding-right: 5px;">Handling<br />Method</th>';
					      			$columns++;
					      		}
					      		?>

					      		<th nowrap="nowrap" style="padding-right: 5px;">Handling<br />Charge</th>
					      		<th nowrap="nowrap" style="padding-right: 5px; text-align: center;">Is Rejected<br />&nbsp;</th>
					      		<th nowrap="nowrap" style="padding-right: 5px;">Rejected<br />Reason</th>
					      		<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Total<br />&nbsp;</th>
					      	</tr>

							<?php
							if(count($returnRequest->Line) > 0) {
								for($k=0; $k<count($returnRequest->Line); $k++) {
									$cost = 0;

									if($returnRequest->Line[$k]->IsRejected == 'N') {
										$cost += $returnRequest->Line[$k]->Cost * $returnRequest->Line[$k]->Quantity;

										switch($returnRequest->Line[$k]->HandlingMethod) {
											case 'R':
												$cost -= ($cost / 100) * $returnRequest->Line[$k]->HandlingCharge;
												break;
											case 'F':
												$cost -= $returnRequest->Line[$k]->HandlingCharge;
												break;
										}
									}

									$handlingCharge = ($isEditable) ? $form->GetHTML(sprintf('handling_charge_%d', $returnRequest->Line[$k]->ID)) : number_format($returnRequest->Line[$k]->HandlingCharge, 2, '.', '');

									switch($returnRequest->Line[$k]->HandlingMethod) {
										case 'R':
											$handlingText = sprintf('%s%%', $handlingCharge);
											break;
										case 'F':
											$handlingText = sprintf('&pound;%s', $handlingCharge);
											break;
										default:
											$handlingText = '';
											break;
									}
									?>

									<tr>
							      		<td nowrap="nowrap"><?php echo number_format($returnRequest->Line[$k]->Quantity, 2, '.', ''); ?></td>
							      		<td nowrap="nowrap"><?php echo $returnRequest->Line[$k]->Product->ID; ?></td>
							      		<td><?php echo $returnRequest->Line[$k]->Product->Name; ?></td>
							      		<td nowrap="nowrap"><?php echo $returnRequest->Line[$k]->Type->Name; ?></td>
							      		<td><?php echo ($returnRequest->Line[$k]->RelatedProduct->ID > 0) ? sprintf('%d: %s', $returnRequest->Line[$k]->RelatedProduct->ID, $returnRequest->Line[$k]->RelatedProduct->Name) : 'None'; ?></td>
							      		<td nowrap="nowrap"><?php echo $returnRequest->Line[$k]->Reason; ?></td>

							      		<?php
							      		if($returnRequest->Purchase->ID > 0) {
							      			echo sprintf('<td nowrap="nowrap">%s</td>', $returnRequest->Line[$k]->PurchaseLine->AdviceNote);
							      		}
							      		?>

							      		<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($returnRequest->Line[$k]->Cost, 2), 2, '.', ','); ?></td>

							      		<?php
							      		if($isEditable) {
							      			echo sprintf('<td nowrap="nowrap">%s</td>', $form->GetHTML(sprintf('handling_method_%d', $returnRequest->Line[$k]->ID)));
							      		}
							      		?>

							      		<td nowrap="nowrap"><?php echo $handlingText; ?></td>
							      		<td nowrap="nowrap" align="center"><?php echo ($isEditable) ? $form->GetHTML(sprintf('rejected_%d', $returnRequest->Line[$k]->ID)) : $returnRequest->Line[$k]->IsRejected; ?></td>
							      		<td nowrap="nowrap"><?php echo ($isEditable) ? $form->GetHTML(sprintf('reason_%d', $returnRequest->Line[$k]->ID)) : $returnRequest->Line[$k]->RejectedReason; ?></td>
								      	<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($cost, 2), 2, '.', ','); ?></td>
									</tr>

									<?php
								}
								?>

								<tr>
									<td colspan="<?php echo $columns - 1; ?>">&nbsp;</td>
							      	<td nowrap="nowrap" align="right"><strong>&pound;<?php echo number_format(round($returnRequest->Total, 2), 2, '.', ','); ?></strong></td>
								</tr>

								<?php
							} else {
						      	?>

						      	<tr>
									<td colspan="<?php echo $columns; ?>" align="center">No products available for viewing.</td>
						      	</tr>

						      	<?php
							}
							?>
					    </table>
					    <br />

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