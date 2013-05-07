<?php
require_once('lib/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Referrer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseStock.php');

$session->Secure();

if(!isset($_REQUEST['orderid'])) {
	redirect("Location: products_despatch.php");
}

$order = new Order($_REQUEST['orderid']);

if($action == "printlabels") {

	$prints = isset($_REQUEST['prints']) ? $_REQUEST['prints'] : 1;

	echo sprintf('<html>
				<link href="css/printlabels.css" rel="stylesheet" type="text/css" />
				<script>
					function printDocs(){
						window.self.print();
						window.self.close();
					}
				</script>
			  <body onload="printDocs();">');

	for($i = 0; $i < $prints; $i++) {
			?>

			<table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
				<tr>
					<td valign="top" class="shipping">
						<p><strong>Shipping Address:</strong><br />
			            <?php echo (empty($order->ShippingOrg)) ? $order->Shipping->GetFullName() : $order->ShippingOrg; ?><br />
			            <?php echo $order->Shipping->Address->GetFormatted('<br />'); ?></p>
					</td>
				</tr>
			</table>

            <?php
            if(($i+1) != $prints) {
            	echo '<br style="page-break-after:always" />';
            }
	}

	echo '</body></html>';

} else {

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 1, 12);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('orderid', 'Order ID', 'hidden', $order->ID, 'numeric_unsigned', 1, 11);
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/templates/portal-warehouse.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Despatch Details</title>
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
	    var printLabels = function() {
	    	var prints = document.getElementById('prints');
	    	var noPrints = 1;

	    	if(prints) {
	    		noPrints = prints.value;
	    	}

	    	popUrl('product_despatch_details.php?action=printlabels&orderid=<?php echo $order->ID; ?>&prints=' + noPrints, 650, 450);
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
			<h1>Despatch Details</h1>
	        <p>Please check if this Order requires further action. </p>
	        <?php
	        $order->GetLines();
	        $order->Customer->Get();
	        $order->Customer->Contact->Get();
	        $order->GetTransactions();
	        $referrer = new Referrer($order->Referrer);
	        $currentUser = new User($GLOBALS['SESSION_USER_ID']);

	        $warehouseEditable = false;
	        $isEditable = false;
	        if((strtolower($order->Status) != 'despatched') && (strtolower($order->Status) != 'cancelled') && (strtolower($order->Status) != 'partially despatched')){
	        	if((strtolower($order->Status)!='packing')){
	        		$isEditable = true;
	        		$warehouseEditable = true;
	        	}else{
	        		$warehouseEditable = true;
	        	}
	        }

	        if($order->ReceivedOn == '0000-00-00 00:00:00'){
	        	$order->Received();
	        }

	        for($i=0; $i < count($order->Line); $i++){
	        	$form->AddField('qty_' . $order->Line[$i]->ID, 'Quantity of ' . $order->Line[$i]->Product->Name, 'text',  $order->Line[$i]->Quantity, 'numeric_unsigned', 1, 9, true, 'size="3"');
	        }

	        if(!$form->Valid){
	        	echo $form->GetError();
	        	echo "<br>";
	        }

	        echo $form->Open();
	        echo $form->GetHTML('confirm');
	        echo $form->GetHTML('orderid');

	        $orderNoteAlert = 'no';
	        if($order->HasAlerts()){
	        	$orderNoteAlert = 'yes';
	        }
	?>
	<script language="javascript" type="text/javascript">
	var isPrompt  = '<?php echo $orderNoteAlert; ?>';
	var refreshCart;
	if(isPrompt == 'yes'){
		popUrl('./order_alerts.php?oid=<?php echo $order->ID; ?>', 500, 400);
	}

	function changeDelivery(num){
		if(num == ''){
			alert('Please Select a Delivery Option');
		} else {
			window.location.href = 'order_details.php?orderid=<?php echo $order->ID; ?>&changePostage=' + num;
		}
	}
	</script>

	<?php
	if(isset($_REQUEST['postage']) && $_REQUEST['postage'] == 'error'){
		$order->CalculateShipping();

		if($order->Error){
	?>
	<table class="error" cellspacing="0">
	  <tr>
	    <td valign="top"><img src="/ignition_1/ignition/images/icon_alert_2.gif" width="16" height="16" align="absmiddle">	<strong>Shipping Information Not Found:</strong><br>
		Sorry could not find any shipping settings for this location. Please change shipping location. <a href="order_changeAddress.php?orderid=<?php echo $order->ID; ?>&type=shipping">Click Here</a>
	    </td>
	  </tr>
	</table>
	<br />
	<?php
		} else {
	?>
	<table class="error" cellspacing="0">
	  <tr>
	    <td valign="top"><img src="/ignition_1/ignition/images/icon_alert_2.gif" width="16" height="16" align="absmiddle">	<strong>Shipping Information Needed:</strong><br>
		Please select an Appropriate Shipping Option: <?php echo $order->PostageOptions; ?>
	    </td>
	  </tr>
	</table>
	<br />
	<?php
		}
	}
	?>

				<table width="100%"  border="0" cellspacing="0" cellpadding="0">
	              <tr>
				    <td valign="top">


	                  <table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
	                  <tr>
	                    <td valign="top" class="billing"><p> <strong>Organisation/Individual:</strong><br />
	                            <?php echo (empty($order->BillingOrg))?$order->Billing->GetFullName():$order->BillingOrg;  ?> <br />
	                            <?php echo $order->Billing->Address->GetFormatted('<br />');  ?></p></td>
	                    <td valign="top" class="shipping"><p> <strong>Shipping Address:</strong><br />
	                            <?php echo (empty($order->ShippingOrg))?$order->Shipping->GetFullName():$order->ShippingOrg;  ?> <br />
	                            <?php echo $order->Shipping->Address->GetFormatted('<br />');  ?></p></td>
	                    <td valign="top" class="shipping"><p> <strong>Invoice Address:</strong><br />
	                            <?php echo (empty($order->InvoiceOrg))?$order->Invoice->GetFullName():$order->InvoiceOrg;  ?> <br />
	                            <?php echo $order->Invoice->Address->GetFormatted('<br />');  ?></p></td>
	                  </tr>

	                </table>
				    </td>
				    <td align="right" valign="top">

				    <table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
	                  <tr>
	                    <th valign="top">Order Ref: </th>
	                    <td valign="top"><?php echo $order->Prefix . $order->ID; ?></td>
	                  </tr>
					  <tr>
	                    <th valign="top">Customer Ref: </th>
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
	                    <th>Backordered:</th>
	                    <?php
	                    if($order->Backordered == 'N') {
	                    	$boStatus = 'No';

	                    	for($i=0; $i < count($order->Line); $i++){
	                    		if(strtolower(trim($order->Line[$i]->Status)) == 'backordered') {
	                    			$boStatus = 'Yes';
	                    		}
	                    	}
	                    } else {
	                    	$boStatus = 'Yes';
	                    }
						?>
				        <td valign="top"><?php echo $boStatus; ?></td>
	                  </tr>
	                  <tr>
	                    <th valign="top">Payment Method: </th>
	                    <td valign="top"><?php echo $order->GetPaymentMethod(); ?>
	                  </tr>
	                  <tr>
	                    <th valign="top">Card: </th>
	                    <td valign="top">
						<?php
						if($session->Warehouse->Type == 'B'){
							echo '<a href="order_payment.php?orderid='.$order->ID.'">'.$order->Card->PrivateNumber().'</a>';
						} else {
							echo $order->Card->PrivateNumber();
						}
						?>
						&nbsp;
						</td>
	                  </tr>
	                  <tr>
	                    <th valign="top">&nbsp;</th>
	                    <td valign="top">&nbsp;</td>
	                  </tr>
	                  <tr>
	                    <th valign="top">Order Date:</th>
	                    <td valign="top"><?php echo cDatetime($order->OrderedOn, 'shortdate'); ?></td>
	                  </tr>

	                </table>                </td>
	              </tr>
	              <tr>
	                <td colspan="2"><br><br>

	                  <table cellspacing="0" class="orderDetails">
	                  <tr>
	                    <th>Qty</th>
	                    <th>Product</th>
	                    <th>Location</th>
	                    <th>Despatched</th>
						<?php
						if($session->Warehouse->Type == 'S'){
							echo "<th>Cost</th>";
						} else {
							echo "<th>Stocked</th>";
						}
	                    ?>
	                    <th>Quickfind</th>
	                    <th>Backorder</th>
	                  </tr>
	                  <?php
	                  $rowCount = 0;
	                  /*if($currentUser->Branch->ID == 0){
	                  $hqCheck = new DataQuery("SELECT * FROM branch WHERE Is_HQ = 'Y'");
	                  $branchID = $hqCheck->Row['Branch_ID'];
	                  }else{

	                  $branchID = $currentUser->Branch->ID;

	                  }*/
	                  $allDespatched = true;
	                  for($i=0; $i < count($order->Line); $i++){
	                  	/*$branchCheck = new DataQuery(sprintf("SELECT Type_Reference_ID,Type FROM warehouse w where w.Warehouse_ID = %d ",$order->Line[$i]->DespatchedFrom->ID));
	                  	if($branchCheck->Row['Type'] == 'B' && $branchID == $branchCheck->Row['Type_Reference_ID'] ){
	                  	*/

	                  	if($session->Warehouse->ID == $order->Line[$i]->DespatchedFrom->ID){
	                  		$rowCount++;
	                  		$wareHouseId = $order->Line[$i]->DespatchedFrom->ID;
	                  		$session->Warehouse->Get();

	                  		$prodCost = false;

	                  		if($session->Warehouse->Type == 'S'){
	                  			$costFinder = new DataQuery(sprintf('SELECT * FROM supplier_product WHERE Supplier_ID = %d AND Product_ID = %d',$session->Warehouse->Contact->ID,$order->Line[$i]->Product->ID));
	                  			$prodCost = $costFinder->Row['Cost'];
	                  			$sku = $costFinder->Row['Supplier_SKU'];
	                  			$costFinder->Disconnect();
	                  		}
				?>
	                  <tr>
	                    <td>
						<?php if ($isEditable){ ?>
						<a href="order_details.php?orderid=<?php echo $order->ID; ?>&action=remove&line=<?php echo $order->Line[$i]->ID; ?>"><img src="images/icon_trash_1.gif" alt="Remove" border="0" /></a>
						<?php } ?>
						<?php echo ($isEditable)?$form->GetHTML('qty_'. $order->Line[$i]->ID):$order->Line[$i]->Quantity; ?>x</td>
	                    <td>
							<?php echo $order->Line[$i]->Product->Name; ?><br />
							Part Number: <?php print (strlen($sku) > 0) ? $sku : 'Unknown'; ?>
						</td>
						<td>
							<?php $warehouseLocation = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock
																	WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>'' LIMIT 0, 1",
				$order->Line[$i]->DespatchedFrom->ID,
				$order->Line[$i]->Product->ID));
				echo $warehouseLocation->Row['Shelf_Location'];
				$warehouseLocation->Disconnect();
									?>&nbsp;
						</td>
						<td>
							<?php if(!empty($order->Line[$i]->DespatchID))
							echo '<a href="despatch_view.php?despatchid=' . $order->Line[$i]->DespatchID . '" target="_blank"><img src="./images/icon_tick_2.gif" border="0" /></a>';
							else{
								$allDespatched = false;
								echo "Not despatched";
							}
							?>
						</td>
						<?php
						if(strtolower($order->Line[$i]->Status) == 'cancelled'){
						?>
						<td colspan="1" align="center">Cancelled</td>
						<?php
						}
							if($session->Warehouse->Type == 'S'){?>
						<td>
						<?php
						if($prodCost == false){
							echo "N/A";
						}else{
							echo "&pound;".number_format($prodCost, 2, '.', ',')." Per item";
						}
						?>
						&nbsp;</td>

						<?php
							} elseif($session->Warehouse->Type == 'B'){
								$stock = new WarehouseStock();
								$stock->GetViaWarehouseProduct($session->Warehouse->ID, $order->Line[$i]->Product->ID);

								echo sprintf('<td>%dx</td>', $stock->QuantityInStock);
							}
						?>
	                    <td><a href="../product.php?pid=<?php echo $order->Line[$i]->Product->ID; ?>"><?php echo $order->Line[$i]->Product->ID; ?></a></td>
	                    <?php
	                    if(!stristr($order->Line[$i]->Status, 'Backordered')){
	                    	if(empty($order->Line[$i]->DespatchID)) {
								?>
	                    		<td><input name="Backorder" type="button" id="Backorder" value="Backorder" class="greySubmit" onclick="window.location.href='order_backorder.php?orderid=<?php echo $order->ID; ?>&orderlineid=<?php echo $order->Line[$i]->ID; ?>';" /></td>
	                    		<?php
	                    	} else {
	                    		echo '<td>&nbsp;</td>';
	                    	}
	                    } elseif(empty($order->Line[$i]->DespatchID)) {
	                    	?>
	                    	<td>Expected:<br /><?php print ($order->Line[$i]->BackorderExpectedOn > '0000-00-00 00:00:00') ? cDatetime($order->Line[$i]->BackorderExpectedOn, 'shortdate') : 'Unknown'; ?></td>
	                    	<?php
	                    }else{
	                    	echo '<td>&nbsp;</td>';
	                    }
	                    ?>
	                  </tr>
	                  <?php
	                  	}
	                  	//$branchCheck->Disconnect();

	                  }

	                  if($rowCount == 0){
	                  	echo "<tr><td colspan='7' align = 'center'>There are no order lines in this order that are too be shipped from your branch.</td></tr>";
	                  }
						?>
	                  <tr>
	                    <td colspan="7" align="left">Cart Weight: ~<?php echo $order->Weight; ?>Kg</td>
	                  </tr>
	                </table>
	               <br />
	               </td>
	              </tr>
	              <tr>
	                <td align="left" valign="top">
	                	<?php if(!$allDespatched){ ?>
		                <input name="Despatch Order" type="button" id="Despatch Order" value="Despatch Order" class="submit" onclick="popUrl('despatch_product.php?orderid=<?php echo $order->ID; ?>&warehouseid=<?php echo $wareHouseId;?>', 650, 450);" />

		                <?php }?>

		                <input name="Print Order" type="button" id="Print Order" value="Print Order" class="greySubmit" onclick='window.self.print()'/>

		                <br /><br /><br />


		                <table border="0" cellpadding="7" cellspacing="0" class="orderTotals">
		                  <tr>
		                    <th colspan="2">Print Shipping Address Labels</th>
		                  </tr>
		                  <tr>
		                    <td>Number of labels:</td>
		                    <td align="right">
		                    	<select name="prints" id="prints">
		                    		<option value="1" selected="selected">1</option>
		                    		<?php
		                    		for($i = 2; $i <= 10; $i++) {
		                    				?>
		                    				<option value="<?php print $i; ?>"><?php print $i; ?></option>
		                    				<?php
		                    		}
		                    		?>
		                    	</select>
			                </td>
		                  </tr>
		                </table>

		                <br />
		                <input name="Print Shipping Labels" type="button" id="Print Shipping Labels" value="Print Labels" class="greySubmit" onclick="printLabels();" />

	                </td>
	                <td align="right" valign="top">
	                <table border="0" cellpadding="7" cellspacing="0" class="orderTotals">
	                  <tr>
	                    <th colspan="2">Shipping Information</th>
	                  </tr>
	                  <tr>
	                    <td>Delivery Option:</td>
	                    <td align="right">
	                      <?php
	                      if (!$isEditable){
	                      	$order->Postage->Get();
	                      	echo $order->Postage->Name;
	                      } else {
	                      	$order->Recalculate();
	                      	echo $order->PostageOptions;
	                      }
							?>
	                    </td>
	                  </tr>
	                </table></td>
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
<?php
}
?>