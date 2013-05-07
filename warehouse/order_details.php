<?php
require_once('lib/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderWarehouseNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Referrer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierShippingCalculator.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseStock.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Package.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Return.php');

$session->Secure();

if(!isset($_REQUEST['orderid'])) {
	redirect("Location: orders_pending.php");
}

$order = new Order($_REQUEST['orderid']);
$order->GetLines();
$order->Customer->Get();
$order->Customer->Contact->Get();
$order->GetTransactions();

if(($order->IsDeclined == 'Y') || ($order->IsFailed == 'Y')) {
	redirect(sprintf("Location: orders_pending.php"));
}

$currentUser = new User($GLOBALS['SESSION_USER_ID']);

$isEditable = (((strtolower($order->Status) == 'unread') || (strtolower($order->Status) == 'pending')) && ($session->Warehouse->Type == 'B')) ? true : false;
$isWarehouseEditable = ((strtolower($order->Status) == 'unread') || (strtolower($order->Status) == 'pending') || (strtolower($order->Status) == 'packing') || (strtolower($order->Status) == 'partially despatched')) ? true : false;

if($order->ReceivedOn == '0000-00-00 00:00:00'){
	$order->Received();
}

if($order->ParentID > 0) {
	$parent = new Order($order->ParentID);
	$parent->GetLines();
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('orderid', 'Order ID', 'hidden', $order->ID, 'numeric_unsigned', 1, 11);
$form->AddField('type', 'Type', 'select', '0', 'numeric_unsigned', 1, 11, false);
$form->AddOption('type', '0', '');
$form->AddField('note', 'Note', 'textarea', '', 'anything', 1, 2048, false, 'style="width: 300px;" rows="7"');
$disabled = '';

if($order->Coupon->ID > 0) {
	$order->Coupon->Get();
	if($order->Coupon->IsInvisible == 'Y') {
		$disabled = ' disabled="disabled"';
	}
}

$form->AddField('freeText', 'Free Text', 'text', $order->FreeText, 'paragraph', 0, 255, false, 'style="width:100%;"'.$disabled);
$form->AddField('freeTextValue', 'Free Text Value', 'text', (isset($order->FreeTextValue) ? $order->FreeTextValue : '0.00'), 'float', 0, 11, false, 'size="4"'.$disabled);

if($session->Warehouse->Type == 'B') {
	$form->AddField('ref', 'Custom Ref', 'text', $order->CustomID, 'anything', 1, 32, false);
}

$data = new DataQuery(sprintf("SELECT * FROM order_warehouse_note_type ORDER BY Name ASC"));
while($data->Row) {
	$form->AddOption('type', $data->Row['Order_Warehouse_Note_Type_ID'], $data->Row['Name']);

	$data->Next();
}
$data->Disconnect();

$cost = 0;
$weight = 0;

$supplierProducts = array();
$shippingProducts = array();

for($i=0; $i < count($order->Line); $i++){
	if($order->Line[$i]->Product->ID > 0) {
		$order->Line[$i]->Product->Get();
	}
	
	$order->Line[$i]->DespatchedFrom->Contact->Get();

	if((($session->Warehouse->Type == 'S') && ($session->Warehouse->ID == $order->Line[$i]->DespatchedFrom->ID)) || ((($order->Line[$i]->DespatchedFrom->Type == 'B') && ($session->Warehouse->ID == $order->Line[$i]->DespatchedFrom->ID)) || (($order->Line[$i]->DespatchedFrom->Type == 'S') && ($order->Line[$i]->DespatchedFrom->Contact->IsDropShipper == 'N') && ($order->Line[$i]->DespatchedFrom->Contact->DropShipperID == $session->Warehouse->Contact->ID)))) {
		$cost += $order->Line[$i]->Cost * $order->Line[$i]->Quantity;
		$weight += $order->Line[$i]->Product->Weight * $order->Line[$i]->Quantity;

		$shippingProducts[] = array('Quantity' => $order->Line[$i]->Quantity, 'ShippingClassID' => $order->Line[$i]->Product->ShippingClass->ID);

		if($isEditable) {
			if(($order->Line[$i]->Status != 'Invoiced') && ($order->Line[$i]->Status != 'Despatched') && ($order->Line[$i]->Status != 'Cancelled')) {
				$form->AddField('qty_' . $order->Line[$i]->ID, 'Quantity of ' . $order->Line[$i]->Product->Name, 'text',  $order->Line[$i]->Quantity, 'numeric_unsigned', 1, 9, true, 'size="3"');
			}
		}

		if($session->Warehouse->Type == 'S') {
			$form->AddField('days_' . $order->Line[$i]->ID, 'Arrival Days for ' . $order->Line[$i]->Product->Name, 'text', '', 'numeric_unsigned', 1, 11, false, 'size="3"');

			$data = new DataQuery(sprintf("SELECT Supplier_Product_ID, Supplier_Product_Number, Supplier_SKU, Cost FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($session->Warehouse->Contact->ID), mysql_real_escape_string($order->Line[$i]->Product->ID)));
			if($data->TotalRows > 0) {
				$supplierProducts[$i] = $data->Row;
			}
			$data->Disconnect();

			if($isWarehouseEditable) {
				if(($order->Line[$i]->Status != 'Cancelled') && ($order->Line[$i]->Status != 'Invoiced') && ($order->Line[$i]->Status != 'Despatched')) {
					$form->AddField('sku_' . $order->Line[$i]->ID, 'SKU of ' . $order->Line[$i]->Product->Name, 'text', $supplierProducts[$i]['Supplier_SKU'], 'anything', 1, 64, false);
					$form->AddField('cost_' . $order->Line[$i]->ID, 'Cost Price of ' . $order->Line[$i]->Product->Name, 'text', (strlen($supplierProducts[$i]['Cost']) > 0) ? $supplierProducts[$i]['Cost'] : '0.00', 'float', 1, 7, true, 'size="5"');

					if($session->Warehouse->Contact->ShowProduct == 'Y') {
						$form->AddField('productnumber_' . $order->Line[$i]->ID, 'Product Number of ' . $order->Line[$i]->Product->Name, 'text', $supplierProducts[$i]['Supplier_Product_Number'], 'numeric_unsigned', 1, 11, false, 'size="10"');
					}
				}
			}
		}
	}
}

if($session->Warehouse->Type == 'S') {
	$calc = new SupplierShippingCalculator($order->Billing->Address->Country->ID, $order->Billing->Address->Region->ID, $cost, $weight, $order->Postage->ID, $session->Warehouse->Contact->ID);

	foreach($shippingProducts as $item) {
		$calc->Add($item['Quantity'], $item['ShippingClassID']);
	}

	$shippingTotal = number_format(round($calc->GetTotal(), 2), 2, '.', ',');

	$form->AddField('shipping_cost', 'Shipping Cost', 'text', $shippingTotal, 'float', 1, 11, false, 'size="5"');
}

if(isset($_REQUEST['confirm'])) {
	if(isset($_REQUEST['addnote'])) {
		$form->InputFields['type']->Required = true;
		$form->InputFields['note']->Required = true;

		if($form->GetValue('type') == 0) {
			$form->AddError('Type must have a selected value.', 'type');
		}

		if($form->Validate()) {
			$order->IsWarehouseDeclined = 'Y';
			$order->IsWarehouseDeclinedRead = 'N';
			$order->Update();

			$warehouseNote = new OrderWarehouseNote();
			$warehouseNote->IsAlert = 'Y';
			$warehouseNote->Note = $form->GetValue('note');
			$warehouseNote->Order->ID = $order->ID;
			$warehouseNote->Type->ID = $form->GetValue('type');
			$warehouseNote->Warehouse->ID = $session->Warehouse->ID;
			$warehouseNote->Add();

			redirect(sprintf("Location: %s?orderid=%d", $_SERVER['PHP_SELF'], $order->ID));
		}
	} elseif(isset($_REQUEST['alteration'])) {
		$form->InputFields['shipping_cost']->Required = true;

		if($form->Validate()) {
			$order->IsWarehouseDeclined = 'Y';
			$order->IsWarehouseDeclinedRead = 'N';
			$order->Update();

			$warehouseNote = new OrderWarehouseNote();
			$warehouseNote->IsAlert = 'Y';
			$warehouseNote->Note = sprintf('Shipping cost alteration request from %s to %s.', $shippingTotal, number_format(round($form->GetValue('shipping_cost'), 2), 2, '.', ','));
			$warehouseNote->Order->ID = $order->ID;
			$warehouseNote->Type->ID = 3;
			$warehouseNote->Warehouse->ID = $session->Warehouse->ID;
			$warehouseNote->Add();

			redirect(sprintf("Location: %s?orderid=%d", $_SERVER['PHP_SELF'], $order->ID));
		}
	} elseif(isset($_REQUEST['update'])) {
		if($form->Validate()) {
			$order->Recalculate();

			$stock = array();

			for($i=0; $i < count($order->Line); $i++){
				if($session->Warehouse->ID == $order->Line[$i]->DespatchedFrom->ID) {
					if($isEditable) {
						if(($order->Line[$i]->Status != 'Invoiced') && ($order->Line[$i]->Status != 'Cancelled') && ($order->Line[$i]->Status != 'Despatched')) {
							$order->Line[$i]->Quantity = $form->GetValue('qty_' . $order->Line[$i]->ID);
							$order->Line[$i]->Update();
						}
					}

					if($session->Warehouse->Type == 'S') {
						if($isWarehouseEditable) {
							$days = $form->GetValue('days_' . $order->Line[$i]->ID);

							if(!empty($days)) {
								$stock[$order->Line[$i]->ID] = $days;
							}

							if(($order->Line[$i]->Status != 'Cancelled') && ($order->Line[$i]->Status != 'Invoiced') && ($order->Line[$i]->Status != 'Despatched')) {
								if((strlen($supplierProducts[$i]['Supplier_Product_ID']) > 0) && (is_numeric($supplierProducts[$i]['Supplier_Product_ID'])) && ($supplierProducts[$i]['Supplier_Product_ID'] > 0)) {
									$product = new SupplierProduct($supplierProducts[$i]['Supplier_Product_ID']);
									$product->SKU = trim($form->GetValue('sku_' . $order->Line[$i]->ID));

									if($session->Warehouse->Contact->ShowProduct == 'Y') {
										$product->SupplierProductNumber = trim($form->GetValue('productnumber_' . $order->Line[$i]->ID));
									}

									$product->Update();
								} else {
									$product = new SupplierProduct();
									$product->Cost = '0.00';
									$product->SKU = trim($form->GetValue('sku_' . $order->Line[$i]->ID));

	                                if($session->Warehouse->Contact->ShowProduct == 'Y') {
										$product->SupplierProductNumber = trim($form->GetValue('productnumber_' . $order->Line[$i]->ID));
									}

									$product->Product->ID = $order->Line[$i]->Product->ID;
									$product->Supplier->ID = $session->Warehouse->Contact->ID;
									$product->Add();
								}

								$newCost = number_format($form->GetValue('cost_' . $order->Line[$i]->ID), 2, '.', '');
								if($newCost > 0) {
									if($newCost != $order->Line[$i]->Cost) {
										$order->Line[$i]->Product->Get();

										$order->IsWarehouseDeclined = 'Y';
										$order->IsWarehouseDeclinedRead = 'N';

										$warehouseNote = new OrderWarehouseNote();
										$warehouseNote->IsAlert = 'Y';
										$warehouseNote->Note = sprintf('%s has requested that the cost price of %s (#%d) be changed from &pound;%s to &pound;%s within the Warehouse Portal.', $session->Warehouse->Name, $order->Line[$i]->Product->Name, $order->Line[$i]->Product->ID, $supplierProducts[$i]['Cost'], $newCost);
										$warehouseNote->Order->ID = $order->ID;
										$warehouseNote->Type->ID = 5;
										$warehouseNote->Warehouse->ID = $session->Warehouse->ID;
										$warehouseNote->Add();
									}
								}
							}
						}
					}
				}
			}

			if($session->Warehouse->Type == 'B') {
				$order->CustomID = $form->GetValue('ref');
			}

			if(!empty($stock)) {
				$note = array();

				for($i=0; $i<count($order->Line); $i++) {
					if(isset($stock[$order->Line[$i]->ID])) {
						$note[] = sprintf('Stock for product \'%s\' (quickfind #%d) will arrive in stock in %d days.', $order->Line[$i]->Product->Name, $order->Line[$i]->Product->ID, $stock[$order->Line[$i]->ID]);
					}
				}

				$warehouseNote = new OrderWarehouseNote();
				$warehouseNote->IsAlert = 'Y';
				$warehouseNote->Note = implode('<br /><br />', $note);;
				$warehouseNote->Order->ID = $order->ID;
				$warehouseNote->Type->ID = 1;
				$warehouseNote->Warehouse->ID = $session->Warehouse->ID;
				$warehouseNote->Add();

				$order->IsWarehouseDeclined = 'Y';
				$order->IsWarehouseDeclinedRead = 'N';
			}

			$order->Update();

			redirect("Location: order_details.php?orderid=". $order->ID);
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
	var toggleLine = function(lineId) {
		var e = null;

		e = document.getElementById('line_' + lineId);
		if(e) {
			if(e.style.display == 'none') {
				e.style.display = '';

				e = document.getElementById('toggle_line_' + lineId);
				if(e) {
					e.src = 'images/aztector_3.gif';
					e.alt = 'Collapse';
				}
			} else {
				e.style.display = 'none';

				e = document.getElementById('toggle_line_' + lineId);
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
			<h1>Despatch Details</h1>
			<p>Please check if this Order requires further action.</p>

	        <?php
	        if($session->Warehouse->Type == 'S') {
		        $zeroCost = 0;

		        for($i=0; $i < count($order->Line); $i++){
		        	if($session->Warehouse->ID == $order->Line[$i]->DespatchedFrom->ID) {
		        		if(($order->Line[$i]->Status != 'Cancelled') && ($order->Line[$i]->Status != 'Invoiced') && ($order->Line[$i]->Status != 'Despatched')) {
		        			if($order->Line[$i]->Cost == 0) {
		        				$zeroCost++;
		        			}
		        		}
		        	}
		        }

		        if($zeroCost > 0) {
		        	$bubble = new Bubble('Warning!', 'You must specify a cost price before you can despatch an order line.');

		        	echo $bubble->GetHTML();
		        	echo '<br />';
		        }
	        }

	        if(!$form->Valid){
	        	echo $form->GetError();
	        	echo "<br />";
	        }

	        echo $form->Open();
	        echo $form->GetHTML('confirm');
	        echo $form->GetHTML('orderid');
	?>
	<script language="javascript" type="text/javascript">
	if(<?php echo ($order->HasAlerts($session->Warehouse->ID)) ? 'true' : 'false'; ?>){
		popUrl('order_alerts.php?oid=<?php echo $order->ID; ?>', 500, 400);
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
		<td valign="top"><img src="images/icon_alert_2.gif" width="16"
			height="16" align="absmiddle"> <strong>Shipping Information Not
		Found:</strong><br>
		Sorry could not find any shipping settings for this location. Please
		contact your system administrator for changing shipping location.</td>
	</tr>
</table>
<br />
	<?php
		} else {
	?>
	<table class="error" cellspacing="0">
	<tr>
		<td valign="top"><img src="images/icon_alert_2.gif" width="16"
			height="16" align="absmiddle"> <strong>Shipping Information Needed:</strong><br>
		Please select an Appropriate Shipping Option: <?php echo $order->PostageOptions; ?>
	    </td>
	</tr>
</table>
<br />
	<?php
		}
	}

if(($session->Warehouse->Type == 'S') && (Setting::GetValue('sage_pay_active') == 'false')) {
	$bubble = new Bubble('Payment Gateway Unavailable', 'You will be unable to process orders as our third party payment gateway &quot;Sage Pay&quot; is experiencing technical difficulties.<br />Please check back soon.');

	echo $bubble->GetHTML();
	echo '<br />';
}

if($order->Invoice->Address->Country->ID == 0) {
	$bubble = new Bubble('Missing Invoice Details', 'This order cannot be invoiced and despatched until at least an invoice country value is provided.');

	echo $bubble->GetHTML();
	echo '<br />';
}

if($order->IsWarehouseDeclined == 'Y') {
	$bubble = new Bubble('Warehouse Declined', 'This order is currently warehouse declined and cannot be despatched until BLT Direct have resolved this status.');

	echo $bubble->GetHTML();
	echo '<br />';
}
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td valign="top">


		<table cellpadding="0" cellspacing="0" border="0"
			class="invoiceAddresses">
			<tr>
				<td valign="top" class="shipping">
				<p><strong>Shipping Address:</strong><br />
                    			<?php echo sprintf('%s%s', (empty($order->ShippingOrg)) ? '' : sprintf('%s<br />', $order->ShippingOrg), $order->Shipping->GetFullName()); ?><br />
                            	<?php echo $order->Shipping->Address->GetFormatted('<br />'); ?><br />
				<br />
                            	<?php echo $order->Customer->Contact->Person->GetPhone('<br />'); ?>
                            </p>
				</td>
			</tr>
		</table>
		<br />

	    <?php
	    if($order->IsWarehouseDeclined == 'N') {
	                $data = new DataQuery(sprintf("SELECT o.Order_ID FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID INNER JOIN warehouse AS w ON ol.Despatch_From_ID=w.Warehouse_ID AND w.Warehouse_ID=%d INNER JOIN postage AS p ON o.Postage_ID=p.Postage_ID WHERE ol.Despatch_ID=0 AND (o.Status LIKE '%%partial%%' OR o.Status LIKE 'packing' OR o.Status LIKE 'backordered') AND o.Is_Declined='N' AND o.Is_Warehouse_Declined='N' AND o.Created_On>'%s' GROUP BY o.Order_ID ORDER BY o.Created_On ASC LIMIT 0, 1", mysql_real_escape_string($session->Warehouse->ID), mysql_real_escape_string($order->CreatedOn)));
	                if($data->TotalRows > 0) {
	                	echo sprintf('<input type="button" class="greySubmit" name="back" value="Back" onclick="window.self.location.href=\'order_details.php?orderid=%d\'" /> ', $data->Row['Order_ID']);
	                }
	                $data->Disconnect();

	                $data = new DataQuery(sprintf("SELECT o.Order_ID FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID INNER JOIN warehouse AS w ON ol.Despatch_From_ID=w.Warehouse_ID AND w.Warehouse_ID=%d INNER JOIN postage AS p ON o.Postage_ID=p.Postage_ID WHERE ol.Despatch_ID=0 AND (o.Status LIKE '%%partial%%' OR o.Status LIKE 'packing' OR o.Status LIKE 'backordered') AND o.Is_Declined='N' AND o.Is_Warehouse_Declined='N' AND o.Created_On<'%s' GROUP BY o.Order_ID ORDER BY o.Created_On DESC LIMIT 0, 1",mysql_real_escape_string($session->Warehouse->ID), mysql_real_escape_string($order->CreatedOn)));
	                if($data->TotalRows > 0) {
	                	echo sprintf('<input type="button" class="greySubmit" name="next" value="Next" onclick="window.self.location.href=\'order_details.php?orderid=%d\'" /> ', $data->Row['Order_ID']);
	                }
	                $data->Disconnect();
		}
	    ?>

    </td>
    <td></td>
	<td align="right" valign="top">

		<table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">

			<?php
			if($order->ParentID > 0) {
				$isVisible = false;

				for($i=0; $i<count($parent->Line); $i++) {
					if($parent->Line[$i]->DespatchedFrom->ID == $session->Warehouse->ID) {
						$isVisible = true;
					}
				}

				if($isVisible) {
					?>

		            <tr>
						<th>Orginal Order Ref:</th>
						<td><a href="order_details.php?orderid=<?php echo $parent->ID; ?>"><?php echo $parent->Prefix . $parent->ID; ?></a></td>
					</tr>

					<?php
				}
			}
			?>

			<tr>
				<th>Order Ref:</th>
				<td><?php echo $order->Prefix . $order->ID; ?></td>
			</tr>

            <?php
			if(($order->Prefix == 'R') || ($order->Prefix == 'B')) {
				$return = new ProductReturn($order->ReturnID);
				$return->Reason->Get();
                ?>

				<tr>
					<th>Return Reason:</th>
					<td><?php echo $return->Reason->Description; ?></td>
				</tr>

            	<?php
			}
			?>

			<tr>
	        	<th valign="middle">Customer Ref:</th>
	        	<td valign="middle"><?php echo ($session->Warehouse->Type == 'B') ? $form->GetHTML('ref') : $order->CustomID; ?></td>
	        </tr>
			<tr>
				<th>Customer:</th>
				<td><?php echo $order->Customer->Contact->Person->GetFullName(); ?></td>
			</tr>
			<tr>
				<th>Order Status:</th>
				<td><?php echo $order->Status; ?></td>
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
				        <td><?php echo $boStatus; ?></td>
					</tr>
	                  <tr>
	                    <th>Payment Method: </th>
	                    <td><?php echo $order->GetPaymentMethod(); ?>
	                  </tr>
	                  <tr>
	                    <th>Card: </th>
	                    <td>
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
	                    <th>&nbsp;</th>
	                    <td>&nbsp;</td>
	                  </tr>
	                  <tr>
	                    <th>Order Date:</th>
	                    <td><?php echo cDatetime($order->OrderedOn, 'shortdate'); ?></td>
	                  </tr>
				</table>
		</td>
	</tr>
	<tr>
		<td colspan="3">
			<br>
			<br>

			<table cellspacing="0" class="orderDetails">
				<tr>
                    <?php
					if($session->Warehouse->Type == 'B') {
						echo '<th nowrap="nowrap">&nbsp;</th>';
					}
					?>

					<th>Qty</th>
					<th>Product</th>

					<?php
					if($session->Warehouse->Type == 'S') {
						echo '<th>Warehouse</th>';
					} else {
						echo '<th>Qty Incoming</th>';
					}
					?>

					<th>Location</th>
					<th>Despatched</th>

					<?php
					if($session->Warehouse->Type == 'S'){
						echo "<th>Cost</th>";
						echo "<th>SKU</th>";

						if($session->Warehouse->Contact->ShowProduct == 'Y') {
							echo '<th>Product Number</th>';
						}
					} else {
						echo "<th>Stocked</th>";
					}
		            ?>

		            <th>Quickfind</th>

		            <?php
					if($session->Warehouse->Type == 'S') {
						echo '<th>Stock Arrival</th>';
					}
					?>

					<th>Backorder</th>
				</tr>

	              <?php
	              $rowCount = 0;

	              $allDespatched = true;

	              for($i=0; $i < count($order->Line); $i++){
	                if((($session->Warehouse->Type == 'S') && ($session->Warehouse->ID == $order->Line[$i]->DespatchedFrom->ID)) || ((($order->Line[$i]->DespatchedFrom->Type == 'B') && ($session->Warehouse->ID == $order->Line[$i]->DespatchedFrom->ID)) || (($order->Line[$i]->DespatchedFrom->Type == 'S') && ($order->Line[$i]->DespatchedFrom->Contact->IsDropShipper == 'N') && ($order->Line[$i]->DespatchedFrom->Contact->DropShipperID == $session->Warehouse->Contact->ID)))) {
	                  	$rowCount++;
	                  	$wareHouseId = $order->Line[$i]->DespatchedFrom->ID;

	                  	$backgroundColor = 'ffffff';

	                  	if(($order->Line[$i]->Status != "Invoiced") && ($order->Line[$i]->Status != "Cancelled") && ($order->Line[$i]->Status != 'Despatched')) {
	                  		if($order->IsRestocked == 'Y') {
	                  			$backgroundColor = 'ffd399';
	                  		} elseif($order->IsWarehouseUndeclined == 'Y') {
	                  			$backgroundColor = '99ff99';
	                  		}
	                  	}

					    if($order->Line[$i]->Product->Stocked == 'Y') {
					      	$branchStock = 0;

					      	$data = new DataQuery(sprintf("SELECT SUM(ws.Quantity_In_Stock) AS Quantity FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B' WHERE ws.Product_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID)));
					    	$branchStock += $data->Row['Quantity'];
					      	$data->Disconnect();

					      	if($branchStock == 0) {
					      		$backgroundColor = 'bb99ff';
					      	}
					    }

	                  	echo sprintf('<tr style="background-color: #%s;">', $backgroundColor);

                        if($session->Warehouse->Type == 'B') {
							echo sprintf('<td nowrap="nowrap" width="1%%"><a href="javascript:toggleLine(\'%d\');"><img id="toggle_line_%d" align="absmiddle" src="images/aztector_4.gif" alt="Expand" border="0" /></a></td>', $order->Line[$i]->ID, $order->Line[$i]->ID);
						}
						?>

		                <td>
							<?php
							if($isEditable) {
								?>
								<a href="order_details.php?orderid=<?php echo $order->ID; ?>&action=remove&line=<?php echo $order->Line[$i]->ID; ?>"><img src="images/icon_trash_1.gif" alt="Remove" border="0" /></a>
								<?php
							}

							if(($isEditable) && ($order->Line[$i]->Status != 'Invoiced') && ($order->Line[$i]->Status != 'Cancelled') && ($order->Line[$i]->Status != 'Despatched')) {
								echo $form->GetHTML('qty_'. $order->Line[$i]->ID);
							} else {
								echo $order->Line[$i]->Quantity;
							}
							?>x
						</td>
						<td><?php echo $order->Line[$i]->Product->Name; ?></td>
						<td>
							<?php
							if($order->Line[$i]->DespatchedFrom->Type == 'S') {
								$order->Line[$i]->DespatchedFrom->Contact->Contact->Get();

								echo $order->Line[$i]->DespatchedFrom->Contact->Contact->Parent->Organisation->Name;
							} else {
								$data = new DataQuery(sprintf("SELECT SUM(pl.Quantity_Decremental) AS Quantity_Incoming FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID WHERE p.For_Branch>0 AND pl.Quantity_Decremental>0 AND pl.Product_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID)));
								echo number_format($data->Row['Quantity_Incoming'], 0, '.', '') . 'x';
								$data->Disconnect();
							}
							?>
						</td>
						<td>
							<?php
							$warehouseLocation = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>'' LIMIT 0, 1", mysql_real_escape_string($order->Line[$i]->DespatchedFrom->ID), mysql_real_escape_string($order->Line[$i]->Product->ID)));
							echo $warehouseLocation->Row['Shelf_Location'];
							$warehouseLocation->Disconnect();
							?>
						</td>
						<td>
							<?php
							if(!empty($order->Line[$i]->DespatchID)) {
								echo '<a href="despatch_view.php?despatchid=' . $order->Line[$i]->DespatchID . '" target="_blank"><img src="./images/icon_tick_2.gif" border="0" /></a>';
							} else {
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

						if($session->Warehouse->Type == 'S'){
							echo sprintf('<td>&pound;%s Per item</td>', ($isWarehouseEditable && (($order->Line[$i]->Status != 'Cancelled') && ($order->Line[$i]->Status != 'Invoiced') && ($order->Line[$i]->Status != 'Despatched'))) ? $form->GetHTML('cost_'.$order->Line[$i]->ID) : $order->Line[$i]->Cost);
							echo sprintf('<td>%s&nbsp;</td>', ($isWarehouseEditable && (($order->Line[$i]->Status != 'Cancelled') && ($order->Line[$i]->Status != 'Invoiced') && ($order->Line[$i]->Status != 'Despatched'))) ? $form->GetHTML('sku_'.$order->Line[$i]->ID) : $supplierProducts[$i]['Supplier_SKU']);

                            if($session->Warehouse->Contact->ShowProduct == 'Y') {
                                echo sprintf('<td>%s&nbsp;</td>', ($isWarehouseEditable && (($order->Line[$i]->Status != 'Cancelled') && ($order->Line[$i]->Status != 'Invoiced') && ($order->Line[$i]->Status != 'Despatched'))) ? $form->GetHTML('productnumber_'.$order->Line[$i]->ID) : (($supplierProducts[$i]['Supplier_Product_Number'] > 0) ? $supplierProducts[$i]['Supplier_Product_Number'] : '-'));
							}
						} elseif($session->Warehouse->Type == 'B') {
							$stocked = false;
	                  		$qtyStocked = 0;
	                  		$warehouseId = $order->Line[$i]->DespatchedFrom->ID;

	                  		while($warehouseId > 0) {
	                  			$data = new DataQuery(sprintf("SELECT SUM(Quantity_In_Stock) AS Quantity FROM warehouse_stock WHERE Product_ID=%d AND Warehouse_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID), mysql_real_escape_string($warehouseId)));
		                  		if($data->TotalRows > 0) {
		                  			$qtyStocked += $data->Row['Quantity'];

		                  			$stocked = true;
		                  		}
		                  		$data->Disconnect();

	                  			$data = new DataQuery(sprintf("SELECT Parent_Warehouse_ID FROM warehouse WHERE Warehouse_ID=%d", mysql_real_escape_string($warehouseId)));
	                  			if($data->TotalRows > 0) {
	                  				$warehouseId = $data->Row['Parent_Warehouse_ID'];
	                  			} else {
	                  				$warehouseId = 0;
	                  			}
	                  			$data->Disconnect();
	                  		}

							if($stocked) {
								echo sprintf('<td>%sx</td>', $qtyStocked);
							} else {
								echo '<td>&nbsp;</td>';
							}
						}
						?>

	                    <td><a href="../product.php?pid=<?php echo $order->Line[$i]->Product->ID; ?>"><?php echo $order->Line[$i]->Product->ID; ?></a></td>

	                    <?php
					  	if($session->Warehouse->Type == 'S') {
	                    	echo sprintf('<td nowrap="nowrap">%s days</td>', $form->GetHTML('days_' . $order->Line[$i]->ID));
						}

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
						    <td>Expected:<br /><a href="order_backorder.php?orderid=<?php echo $order->ID; ?>&orderlineid=<?php echo $order->Line[$i]->ID; ?>&redirect=<?php echo $_SERVER['PHP_SELF']; ?>"><?php print ($order->Line[$i]->BackorderExpectedOn > '0000-00-00 00:00:00') ? cDatetime($order->Line[$i]->BackorderExpectedOn, 'shortdate') : 'Unknown'; ?></a></td>
	                    	<?php
		                } else {
	                    	echo '<td>&nbsp;</td>';
		                }
	                    ?>

	                  </tr>

	                  <?php
					  if($session->Warehouse->Type == 'B') {
					  	  ?>

		                  <tr id="line_<?php echo $order->Line[$i]->ID; ?>" style="display: none;">
		                  	<td></td>
	                  		<td colspan="8">

	                  			<table cellspacing="0" class="orderDetails">
									<tr>
										<th width="50%">Warehouse</th>
										<th width="25%">Quantity Stocked</th>
										<th width="25%">Quantity Incoming</th>
									</tr>

                                    <?php
									$data = new DataQuery(sprintf("SELECT w.Warehouse_Name, SUM(ws.Quantity_In_Stock) AS Quantity FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B' WHERE ws.Product_ID=%d GROUP BY ws.Warehouse_ID", mysql_real_escape_string($order->Line[$i]->Product->ID)));
									if($data->TotalRows > 0) {
										while($data->Row) {
											?>

											<tr>
												<td><?php echo $data->Row['Warehouse_Name']; ?></td>
												<td><?php echo $data->Row['Quantity_In_Stock']; ?></td>
												<td>
													<?php
													$data1 = new DataQuery(sprintf("SELECT SUM(pl.Quantity_Decremental) AS Quantity_Incoming FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID WHERE p.For_Branch>0 AND pl.Quantity_Decremental>0 AND pl.Product_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID)));
													echo $data1->Row['Quantity_Incoming'].'x';
													$data1->Disconnect();
													?>
												</td>
											</tr>

											<?php
											$data->Next();
										}
									} else {
										?>

										<tr>
											<td colspan="3" align="center">There are no items available for viewing.</td>
										</tr>

										<?php
									}
									$data->Disconnect();
	                  				?>

	                  			</table>

	                  		</td>
		                  </tr>

						  <?php
						  $dataComponents = new DataQuery(sprintf("SELECT * FROM product_components WHERE Component_Of_Product_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID)));
						  if($dataComponents->TotalRows > 0) {
				  			while($dataComponents->Row) {
								$component = new Product($dataComponents->Row['Product_ID']);
								?>

								<tr style="background-color: #eee;">

									<?php
									if($session->Warehouse->Type == 'B') {
										echo '<td>&nbsp;</td>';
									}
									?>

									<td nowrap="nowrap">Component: <?php print ($dataComponents->Row['Component_Quantity']*$order->Line[$i]->Quantity); ?>x</td>
									<td><?php echo $component->Name; ?></td>
									<td>-</td>
									<td>
										<?php
										$warehouseLocation = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>'' LIMIT 0, 1", mysql_real_escape_string($order->Line[$i]->DespatchedFrom->ID), mysql_real_escape_string($component->ID)));
										echo $warehouseLocation->Row['Shelf_Location'];
										$warehouseLocation->Disconnect();
										?>
									</td>
									<td>-</td>

									<?php
									if(strtolower($order->Line[$i]->Status) == 'cancelled'){
										echo '<td>-</td>';
									}

									if($session->Warehouse->Type == 'S'){
										echo '<td>-</td>';
										echo '<td>-</td>';

			                            if($session->Warehouse->Contact->ShowProduct == 'Y') {
			                                echo '<td>-</td>';
										}
									} elseif($session->Warehouse->Type == 'B') {
										echo '<td>-</td>';
									}
									?>

									<td><a href="../product.php?pid=<?php echo $component->ID; ?>"><?php echo $component->ID; ?></a></td>
									<td>-</td>
									<td>-</td>
								</tr>

								<?php
								$dataComponents->Next();
							}
						  }
					 	   $dataComponents->Disconnect();
					  }
                  }
			  }

			  if($session->Warehouse->Type == 'B') {
				  if($order->PaymentMethod->Reference != 'google') {
					if($isEditable) {
						?>
						<tr>
							<td nowrap="nowrap">Free text:</td>
							<td colspan="<?php echo ($order->PaymentMethod->Reference == 'google') ? 7 : 6; ?>"><?php echo $form->GetHTML('freeText'); ?></td>
							<td align="right" nowrap="nowrap"><?php echo '&pound;&nbsp;'.$form->GetHTML('freeTextValue'); ?></td>
						</tr>
						<?php
					} elseif(!empty($order->FreeText) || (strlen($order->FreeText) > 0) || ($order->FreeTextValue > 0)) {
						?>
						<tr>
							<td nowrap="nowrap">Free text:</td>
							<td colspan="<?php echo ($order->PaymentMethod->Reference == 'google') ? 7 : 6; ?>"><?php echo $order->FreeText.'&nbsp;'; ?></td>
							<td align="right" nowrap="nowrap"><?php echo '&pound;'.$order->FreeTextValue; ?></td>
						</tr>
						<?php
					}
				  }
			  }

	          if($rowCount == 0){
	            echo sprintf('<tr><td colspan="%d" align="center">There are no order lines in this order that are too be shipped from your branch.</td></tr>', ($session->Warehouse->Type == 'S') ? 9 : 9);
	          }
			  ?>
	        <tr>
				<td colspan="<?php echo ($session->Warehouse->Type == 'S') ? (($session->Warehouse->Contact->ShowProduct == 'Y') ? 11 : 10) : 9; ?>" align="left">Cart Weight: ~<?php echo $order->Weight; ?>Kg</td>
			</tr>
		</table>
		<br />

		</td>
	</tr>
	<tr>
		<td align="left" valign="top" width="49.5%">

	                	<?php
	                	if($order->IsWarehouseDeclined == 'N') {
	                		if(!$allDespatched) {
	                            if(($session->Warehouse->Type == 'B') || (($session->Warehouse->Type == 'S') && (Setting::GetValue('sage_pay_active') == 'true'))) {
                            		if($order->Invoice->Address->Country->ID > 0) {
	                					?>

										<input name="Despatch Order" type="button" id="Despatch Order" value="Despatch Order" class="submit" onclick="popUrl('order_despatch.php?orderid=<?php echo $order->ID; ?>&warehouseid=<?php echo $wareHouseId;?>', 650, 450);" />

		                				<?php
									}
								}
			                }
						}

		                if(($session->Warehouse->Type == 'B') || (($session->Warehouse->Type == 'S') && $isWarehouseEditable)) {
		                	?>
		                	<input name="update" type="submit" value="Update" class="greySubmit" />
		                	<?php
		                }
		                ?>

		                <input name="Print Order" type="button" value="Print Order" class="greySubmit" onclick="window.self.print();" />

		<br />
		<br />
		<br />

		<?php
		if($session->Warehouse->Type == 'B') {
			$sizes = $order->CalculatePackages();
			?>

			<table border="0" cellpadding="6" cellspacing="0" class="orderTotals" width="100%">
				<tr>
					<th colspan="2">Packing Requirements</th>
				</tr>

				<?php
				foreach($sizes as $packageId=>$qty) {
					$package = new Package($packageId);
					?>

					<tr>
						<td><?php echo $qty; ?>x</td>
						<td><?php echo $package->Name; ?></td>
					</tr>

					<?php
				}
				?>

			</table>
			<br />

			<?php
		}
		?>

		<table border="0" cellpadding="7" cellspacing="0">
			<tr>
				<td valign="top">

				<table border="0" cellpadding="7" cellspacing="0"
					class="orderTotals">
					<tr>
						<th colspan="2">New Warehouse Notification</th>
					</tr>
					<tr>
						<td><strong><?php echo $form->GetLabel('type'); ?></strong></td>
						<td><?php echo $form->GetHTML('type'); ?></td>
					</tr>
					<tr>
						<td><strong><?php echo $form->GetLabel('note'); ?></strong></td>
						<td><?php echo $form->GetHTML('note'); ?></td>
					</tr>
				</table>

				<br />
				<input name="addnote" type="submit" value="Add Note"
					class="greySubmit" /></td>
				<td valign="top">

				<table border="0" cellpadding="7" cellspacing="0"
					class="orderTotals">
					<tr>
						<th>Warehouse Notes</th>
					</tr>

					                  <?php
					                  $data = new DataQuery(sprintf("SELECT Order_Warehouse_Note_ID FROM order_warehouse_note WHERE Order_ID=%d AND Warehouse_ID=%d", $order->ID, $session->Warehouse->ID));
					                  while($data->Row){
					                  	$note = new OrderWarehouseNote($data->Row['Order_Warehouse_Note_ID']);
					                  	?>

					                  	<tr>
						<td>
						<p><strong>Subject:</strong> <?php echo $note->Type->Name; ?><br />
						<strong>Date:</strong> <?php echo cDatetime($note->CreatedOn); ?><br />
						<strong>Author:</strong> <?php echo ($note->CreatedBy > 0) ? $GLOBALS['COMPANY'] : $session->Warehouse->Name; ?>
												</p>

												<?php echo $note->Note; ?>
											</td>
					</tr>

										<?php
					                  	$data->Next();
					                  }
					                  $data->Disconnect();
									  ?>

					                </table>

				</td>
			</tr>
		</table>

		</td>
		<td width="1%"></td>
		<td align="right" valign="top" width="49.5%">

			<?php
        	if($order->ParentID > 0) {
        		$data = new DataQuery(sprintf("SELECT d.Despatch_ID, d.Created_On FROM order_line AS ol INNER JOIN despatch AS d ON d.Despatch_ID=ol.Despatch_ID WHERE ol.Order_ID=%d AND ol.Despatch_From_ID=%d", mysql_real_escape_string($parent->ID), mysql_real_escape_string($session->Warehouse->ID)));
        		if($data->TotalRows > 0) {
					?>

                    <table border="0" cellpadding="7" cellspacing="0" class="orderTotals" width="100%">
						<tr>
							<th colspan="2">Orginal Order Despatches</th>
						</tr>

						<?php
						while($data->Row) {
							?>

                            <tr>
                            	<td><?php echo cDatetime($data->Row['Created_On'], 'shortdatetime'); ?></td>
                            	<td align="right"><a href="despatch_view.php?despatchid=<?php echo $data->Row['Despatch_ID']; ?>" target="_blank"><?php echo $data->Row['Despatch_ID']; ?></a></td>
							</tr>

							<?php
							$data->Next();
						}
						?>

					</table>
					<br />

					<?php
				}
				$data->Disconnect();
			}
			?>

			<table border="0" cellpadding="7" cellspacing="0" class="orderTotals" width="100%">
				<tr>
					<th colspan="2">Tax &amp; Shipping</th>
				</tr>
				<tr>
					<td>Delivery Option:</td>
					<td align="left">
							<?php
							if(!$isEditable){
								$order->Postage->Get();

								echo $order->Postage->Name;
							} else {
								echo $order->PostageOptions;
							}
							?>
	                    </td>
				</tr>

				<?php
				if($session->Warehouse->Type == 'S') {
					?>
					<tr>
						<td>Shipping Cost</td>
						<td>
							<?php echo $form->GetHTML('shipping_cost'); ?>
							<input type="submit" name="alteration" value="Submit Alteration" class="greySubmit" />
						</td>
					</tr>
					<?php
				}
				?>

				<tr>
					<td>Tax Exempt:</td>
					<td align="left"><?php echo (!empty($order->TaxExemptCode)) ? 'Yes' : 'No'; ?></td>
				</tr>
		                  <?php
		                  if(!empty($order->TaxExemptCode)) {
	                  		?>
			                <tr>
					<td>Tax Exemption Code:</td>
					<td align="left"><?php echo $order->TaxExemptCode; ?>&nbsp;</td>
				</tr>

		                <?php
					  }
					  ?>
	                </table>

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