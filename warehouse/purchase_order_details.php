<?php
require_once('lib/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseDespatch.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseDespatchLine.php');

$session->Secure(3);

$supplierId = $session->Warehouse->Contact->ID;

$purchase = new Purchase($_REQUEST['id']);
$purchase->GetLines();
$purchase->GetDespatches();

if($purchase->SupplierID != $supplierId) {
	redirect(sprintf("Location: purchase_orders_unfulfilled.php"));
}

$isEditable = ((strtolower($purchase->Status) == 'unfulfilled') || (strtolower($purchase->Status) == 'partially fulfilled')) ? true : false;


if($action == 'complete') {
	$purchase->IsSupplierComplete = 'Y';
	$purchase->Update();
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Purchase Order ID', 'hidden', $purchase->ID, 'numeric_unsigned', 1, 11);
$form->AddField('notes', 'Notes', 'textarea', $purchase->SupplierNotes, 'anything', 1, 1024, false, 'rows="3" style="width: 99%; font-family: arial, sans-serif;"');

if($isEditable) {
	for($i=0; $i<count($purchase->Line); $i++) {
		$form->AddField(sprintf('quantity_despatch_%d', $purchase->Line[$i]->ID), sprintf('Despatch Quantity for \'%s\'', $purchase->Line[$i]->Product->Name), 'text', 0, 'numeric_unsigned', 1, 11, true, 'size="5"');
	}
}

if(isset($_REQUEST['confirm'])) {
	if(isset($_REQUEST['adddespatch'])) {
		for($i=0; $i<count($purchase->Line); $i++) {
			$form->Validate(sprintf('quantity_despatch_%d', $purchase->Line[$i]->ID));
		}

		if($form->Valid) {
            for($j=0; $j<count($purchase->Despatch); $j++) {
				$purchase->Despatch[$j]->GetLines();
			}

			for($k=0; $k<count($purchase->Line); $k++) {
                $quantityDespatched = 0;

                for($j=0; $j<count($purchase->Despatch); $j++) {
					for($i=0; $i<count($purchase->Despatch[$j]->Line); $i++) {
						if($purchase->Despatch[$j]->Line[$i]->PurchaseLine->ID == $purchase->Line[$k]->ID) {
							$quantityDespatched += $purchase->Despatch[$j]->Line[$i]->Quantity;
						}
					}
				}

				if(($form->GetValue(sprintf('quantity_despatch_%d', $purchase->Line[$k]->ID)) < 0) || ($form->GetValue(sprintf('quantity_despatch_%d', $purchase->Line[$k]->ID)) > ($purchase->Line[$k]->Quantity - $quantityDespatched))) {
					$form->AddError(sprintf('Despatch Quantity for \'%s\' must be between 0 and %d.', $purchase->Line[$k]->Product->Name, ($purchase->Line[$k]->Quantity - $quantityDespatched)), sprintf('quantity_despatch_%d', $purchase->Line[$k]->ID));
				}
			}
		}

		if($form->Valid) {
			$totalQuantity = 0;

			for($i=0; $i<count($purchase->Line); $i++) {
				$totalQuantity += $form->GetValue(sprintf('quantity_despatch_%d', $purchase->Line[$i]->ID));
			}

			if($totalQuantity > 0) {
				$despatch = new PurchaseDespatch();
				$despatch->Purchase->ID = $purchase->ID;
				$despatch->Add();

				for($i=0; $i<count($purchase->Line); $i++) {
					$quantity = $form->GetValue(sprintf('quantity_despatch_%d', $purchase->Line[$i]->ID));

					if($quantity > 0) {
						$despatchLine = new PurchaseDespatchLine();
						$despatchLine->PurchaseDespatchID = $despatch->ID;
						$despatchLine->PurchaseLine->ID = $purchase->Line[$i]->ID;
						$despatchLine->Quantity = $quantity;
						$despatchLine->Add();
					}
				}

				$purchase->GetDespatches();

				for($j=0; $j<count($purchase->Despatch); $j++) {
					$purchase->Despatch[$j]->GetLines();
				}

				$isComplete = true;

				for($k=0; $k<count($purchase->Line); $k++) {
                	$quantityDespatched = 0;

                    for($j=0; $j<count($purchase->Despatch); $j++) {
						for($i=0; $i<count($purchase->Despatch[$j]->Line); $i++) {
							if($purchase->Despatch[$j]->Line[$i]->PurchaseLine->ID == $purchase->Line[$k]->ID) {
								$quantityDespatched += $purchase->Despatch[$j]->Line[$i]->Quantity;
							}
						}
					}

                    if($quantityDespatched < $purchase->Line[$k]->Quantity) {
						$isComplete = false;
					}
				}

				if($isComplete) {
					$purchase->IsSupplierComplete = 'Y';
					$purchase->Update();
				}
			}
		}
	} else {
		if(isset($_REQUEST['updatenotes'])) {
			$purchase->SupplierNotes = $form->GetValue('notes');
			$purchase->Update();
		}
	}

	if($form->Valid) {
		redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $purchase->ID));
	}
}

for($k=0; $k<count($purchase->Despatch); $k++) {
	$purchase->Despatch[$k]->GetLines();

	for($i=0; $i<count($purchase->Despatch[$k]->Line); $i++) {
		$purchase->Despatch[$k]->Line[$i]->PurchaseLine->Get();
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
	var toggleDespatch = function(despatchId) {
		var e = null;

		e = document.getElementById('despatch_' + despatchId);
		if(e) {
			if(e.style.display == 'none') {
				e.style.display = '';

				e = document.getElementById('despatch_toggle_' + despatchId);
				if(e) {
					e.src = 'images/aztector_3.gif';
					e.alt = 'Collapse';
				}
			} else {
				e.style.display = 'none';

				e = document.getElementById('despatch_toggle_' + despatchId);
				if(e) {
					e.src = 'images/aztector_4.gif';
					e.alt = 'Expand';
				}
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
			<h1>Purchase Order Details</h1>
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
			        <th>Purchase Order:</th>
			        <td>#<?php echo $purchase->ID; ?></td>
			      </tr>
			      <tr>
			        <th>Status:</th>
			        <td><?php echo $purchase->Status; ?></td>
			      </tr>
			      <tr>
			        <th>Supplier:</th>
			        <td>
			        	<?php
						$data = new DataQuery(sprintf("SELECT s.Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Supplier_Name FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE s.Supplier_ID=%d", mysql_real_escape_string($purchase->SupplierID)));
						echo ($data->TotalRows > 0) ? $data->Row['Supplier_Name'] : '&nbsp;';
						$data->Disconnect();
			        	?>
			        </td>
			      </tr>
                  <tr>
			        <th>Complete:</th>
			        <td><?php echo ($purchase->IsSupplierComplete == 'Y') ? 'Yes' : 'No'; ?></td>
			      </tr>
			      <tr>
			        <th>&nbsp;</th>
			        <td>&nbsp;</td>
			      </tr>
			      <tr>
			        <th>Created On:</th>
			        <td><?php echo cDatetime($purchase->CreatedOn, 'shortdate'); ?></td>
			      </tr>
			    </table><br />

			   </td>
			  </tr>
			  <tr>
			  	<td align="left">
			  		<input type="button" name="print" value="Print" class="greySubmit" onclick="popUrl('purchase_order_print.php?id=<?php echo $purchase->ID; ?>', 800, 600);" />

			  		<?php
			  		if($purchase->IsSupplierComplete == 'N') {
						echo sprintf('<input type="button" name="complete" value="Complete" class="greySubmit" onclick="window.self.location.href = \'%s?action=complete&id=%d\';" />', $_SERVER['PHP_SELF'], $purchase->ID);
					}
					?>

			  	</td>
			  	<td align="right"></td>
			  </tr>
			  <tr>
			    <td colspan="2">
			    	<br />

                    <div style="background-color: #eee; padding: 10px 0 10px 0;">
					 	<p><span class="pageSubTitle">Notes</span><br /><span class="pageDescription">Notes registered against this purchase.</span></p>

					    <?php
					    echo sprintf('<fieldset style="border: none; padding: 0;">%s</fieldset>', $form->GetHTML('notes'));
					    echo '<br />';
						?>

						<table cellspacing="0" cellpadding="0" border="0" width="100%">
							<tr>
								<td align="left">
									<input type="submit" name="updatenotes" value="Update" class="greySubmit" />
								</td>
								<td align="right"></td>
							</tr>
						</table>

					</div>
					<br />

					<div style="background-color: #eee; padding: 10px 0 10px 0;">
					 	<p><span class="pageSubTitle">Products</span><br /><span class="pageDescription">Listing stock requested for a purchase order.</span></p>

					 	<table cellspacing="0" class="orderDetails">
							<tr>
								<th nowrap="nowrap" style="padding-right: 5px;">Quantity</th>
								<th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
					      		<th nowrap="nowrap" style="padding-right: 5px;">Name</th>
					      		<th nowrap="nowrap" style="padding-right: 5px;">SKU</th>
					      		<th nowrap="nowrap" style="padding-right: 5px;">Despatched</th>
					      		<th nowrap="nowrap" style="padding-right: 5px;">Outstanding</th>

								<?php
								if($isEditable) {
									echo '<th nowrap="nowrap" style="padding-right: 5px;">Despatch Quantity</th>';
								}
								?>

					      		<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Cost</th>
					      		<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Total</th>
					      	</tr>

							<?php
							if(count($purchase->Line) > 0) {
								$totalCost = 0;

								for($k=0; $k<count($purchase->Line); $k++) {
									$quantityDespatched = 0;

                                    for($j=0; $j<count($purchase->Despatch); $j++) {
										for($i=0; $i<count($purchase->Despatch[$j]->Line); $i++) {
											if($purchase->Despatch[$j]->Line[$i]->PurchaseLine->ID == $purchase->Line[$k]->ID) {
												$quantityDespatched += $purchase->Despatch[$j]->Line[$i]->Quantity;
											}
										}
									}
									?>

									<tr>
							      		<td nowrap="nowrap"><?php echo $purchase->Line[$k]->Quantity; ?></td>
							      		<td nowrap="nowrap"><?php echo $purchase->Line[$k]->Product->ID; ?></td>
							      		<td nowrap="nowrap"><?php echo $purchase->Line[$k]->Product->Name; ?></td>
							      		<td nowrap="nowrap"><?php echo $purchase->Line[$k]->SKU; ?></td>
							      		<td nowrap="nowrap"><?php echo $quantityDespatched; ?></td>
							      		<td nowrap="nowrap"><?php echo $purchase->Line[$k]->Quantity - $quantityDespatched; ?></td>

							      		<?php
							      		if($isEditable) {
							      			echo sprintf('<td nowrap="nowrap">%s</td>', $form->GetHTML(sprintf('quantity_despatch_%d', $purchase->Line[$k]->ID)));
							      		}
							      		?>

							      		<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($purchase->Line[$k]->Cost, 2), 2, '.', ','); ?></td>
							      		<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($purchase->Line[$k]->Cost * $purchase->Line[$k]->Quantity, 2), 2, '.', ','); ?></td>
									</tr>

									<?php
									$totalCost += $purchase->Line[$k]->Cost * $purchase->Line[$k]->Quantity;
								}
								?>

								<tr>
						      		<td nowrap="nowrap" colspan="<?php echo ($isEditable) ? 8 : 7; ?>"></td>
						      		<td nowrap="nowrap" align="right"><strong>&pound;<?php echo number_format(round($totalCost, 2), 2, '.', ','); ?></strong></td>
								</tr>

								<?php
							} else {
						      	?>

						      	<tr>
									<td colspan="<?php echo ($isEditable) ? 9 : 8; ?>" align="center">No products available for viewing.</td>
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
									<td align="left"></td>
									<td align="right">
										<input type="submit" name="adddespatch" value="Add Despatch" class="greySubmit" />
									</td>
								</tr>
							</table>

							<?php
						}
						?>

					</div>
					<br />

                    <div style="background-color: #eee; padding: 10px 0 10px 0;">
					 	<p><span class="pageSubTitle">Despatches</span><br /><span class="pageDescription">Listing despatches made for this purchase order.</span></p>

					 	<table cellspacing="0" class="orderDetails">
							<tr>
								<th nowrap="nowrap" style="padding-right: 5px;">&nbsp;</th>
								<th nowrap="nowrap" style="padding-right: 5px;">Date</th>
					      		<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Total</th>
					      		<th nowrap="nowrap" width="1%">&nbsp;</th>
					      	</tr>

							<?php
							if(count($purchase->Despatch) > 0) {
								$totalCost = 0;

								for($k=0; $k<count($purchase->Despatch); $k++) {
									$cost = 0;

									for($i=0; $i<count($purchase->Despatch[$k]->Line); $i++) {
										$cost += $purchase->Despatch[$k]->Line[$i]->PurchaseLine->Cost * $purchase->Despatch[$k]->Line[$i]->PurchaseLine->Quantity;
									}
									?>

									<tr>
										<td nowrap="nowrap" width="1%"><a href="javascript:toggleDespatch('<?php echo $purchase->Despatch[$k]->ID; ?>');"><img id="despatch_toggle_<?php echo $purchase->Despatch[$k]->ID; ?>" align="absmiddle" src="images/aztector_4.gif" alt="Expand" border="0" /></a></td>
							      		<td nowrap="nowrap"><?php echo $purchase->Despatch[$k]->CreatedOn; ?></td>
							      		<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($cost, 2), 2, '.', ','); ?></td>
							      		<td nowrap="nowrap" align="center"><a href="javascript:popUrl('purchase_order_print_despatch.php?id=<?php echo $purchase->Despatch[$k]->ID; ?>', 800, 600);"><img src="images/icon_print_1.gif" alt="Print" /></a></td>
									</tr>
									<tr id="despatch_<?php echo $purchase->Despatch[$k]->ID; ?>" style="display: none; background-color: #fff;">
										<td>&nbsp;</td>
										<td colspan="2">

                                            <table cellspacing="0" class="orderDetails">
												<tr>
													<th nowrap="nowrap" style="padding-right: 5px;">Quantity</th>
													<th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
					      							<th nowrap="nowrap" style="padding-right: 5px;">Name</th>
					      							<th nowrap="nowrap" style="padding-right: 5px;">SKU</th>
					      							<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Cost</th>
					      							<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Total</th>
					      						</tr>

												<?php
                                                for($i=0; $i<count($purchase->Despatch[$k]->Line); $i++) {
													?>

													<tr>
							      						<td nowrap="nowrap"><?php echo $purchase->Despatch[$k]->Line[$i]->Quantity; ?></td>
							      						<td nowrap="nowrap"><?php echo $purchase->Despatch[$k]->Line[$i]->PurchaseLine->Product->ID; ?></td>
							      						<td nowrap="nowrap"><?php echo $purchase->Despatch[$k]->Line[$i]->PurchaseLine->Product->Name; ?></td>
							      						<td nowrap="nowrap"><?php echo $purchase->Despatch[$k]->Line[$i]->PurchaseLine->SKU; ?></td>
							      						<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($purchase->Despatch[$k]->Line[$i]->PurchaseLine->Cost, 2), 2, '.', ','); ?></td>
							      						<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($purchase->Despatch[$k]->Line[$i]->PurchaseLine->Cost * $purchase->Despatch[$k]->Line[$i]->Quantity, 2), 2, '.', ','); ?></td>
													</tr>

													<?php
												}
												?>
										    </table>
										    <br />

										</td>
										<td>&nbsp;</td>
									</tr>

									<?php
									$totalCost += $cost;
								}
								?>

								<tr>
						      		<td nowrap="nowrap" colspan="2"></td>
						      		<td nowrap="nowrap" align="right"><strong>&pound;<?php echo number_format(round($totalCost, 2), 2, '.', ','); ?></strong></td>
						      		<td nowrap="nowrap"></td>
								</tr>

								<?php
							} else {
						      	?>

						      	<tr>
									<td colspan="4" align="center">No despatches available for viewing.</td>
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