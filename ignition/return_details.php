<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Payment.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Invoice.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Return.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReturnLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReturnLineDespatch.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Referrer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/HtmlElement.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Warehouse.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Order.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/SupplierReturn.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Despatch.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/WarehouseStock.php');

$session->Secure(3);

$return = new ProductReturn($_REQUEST['id']);
$return->Reason->Get();
$return->GetLines();
$return->GetDespatchLines();
$return->Customer->Get();
$return->Customer->Contact->Get();
$return->OrderLine->Get();
$return->OrderLine->Product->Get();

$warehouse = new Warehouse($return->OrderLine->DespatchedFrom->ID);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Return ID', 'hidden', $return->ID, 'numeric_unsigned', 1, 11);
$form->AddField('supplier', 'Supplier', 'select', '0', 'anything', 1, 11);
$form->AddOption('supplier', '0', '');

$hasReturnSuppliers = false;

$data = new DataQuery(sprintf("SELECT s.Supplier_ID, IF((LENGTH(TRIM(o.Org_Name)) > 0) AND (LENGTH(TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last))) > 0), CONCAT_WS(' ', TRIM(o.Org_Name), CONCAT('(', TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)), ')')), IF(LENGTH(TRIM(o.Org_Name)) > 0, TRIM(o.Org_Name), TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)))) AS Supplier_Name FROM `return` AS r INNER JOIN order_line AS ol ON r.Order_Line_ID=ol.Order_Line_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' INNER JOIN supplier AS s ON s.Supplier_ID=w.Type_Reference_ID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE r.Return_ID=%d GROUP BY s.Supplier_ID ORDER BY o.Org_Name ASC, Supplier_Name ASC", mysql_real_escape_string($return->ID)));
while($data->Row) {
	$hasReturnSuppliers = true;

	$form->AddOption('supplier', $data->Row['Supplier_ID'], $data->Row['Supplier_Name']);

	$data->Next();
}
$data->Disconnect();

for($i=0; $i<count($return->Line); $i++) {
	$return->Line[$i]->Product->Get();

	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d", $warehouse->ID, mysql_real_escape_string($return->Line[$i]->Product->ID)));
	$isStocked = ($data->Row['Count'] > 0) ? true : false;
	$data->Disconnect();

	$form->AddField(sprintf('received_qty_%d', $return->Line[$i]->ID), sprintf('Received quantity for %s', $return->Line[$i]->Product->Name), 'text', $return->Line[$i]->Quantity, 'numeric_unsigned', 1, 11, true, 'size="3"');
	$form->AddField(sprintf('received_action_%d', $return->Line[$i]->ID), sprintf('Received action for %s', $return->Line[$i]->Product->Name), 'select', $return->Line[$i]->Status, 'anything', 1, 64, true);
	$form->AddOption(sprintf('received_action_%d', $return->Line[$i]->ID), 'Replaced', 'Replaced');
	$form->AddOption(sprintf('received_action_%d', $return->Line[$i]->ID), 'Refunded', 'Refunded');
	$form->AddOption(sprintf('received_action_%d', $return->Line[$i]->ID), 'Returned', 'Returned');
	$form->AddOption(sprintf('received_action_%d', $return->Line[$i]->ID), 'Unresolved', 'Unresolved');
	$form->AddField(sprintf('received_restock_%d', $return->Line[$i]->ID), sprintf('Received restocking for %s', $return->Line[$i]->Product->Name), 'checkbox', $return->Line[$i]->IsRestocking, 'boolean', 1, 1, false, ($isStocked) ? '' : 'disabled="disabled"');
	$form->AddField(sprintf('received_supplier_return_%d', $return->Line[$i]->ID), sprintf('Received return to supplier for %s', $return->Line[$i]->Product->Name), 'checkbox', 'N', 'boolean', 1, 1, false, (($form->GetValue(sprintf('received_action_%d', $return->Line[$i]->ID)) == 'Refunded') || ($form->GetValue(sprintf('received_action_%d', $return->Line[$i]->ID)) == 'Replaced')) ? '' : 'disabled="disabled"');
}

for($i=0; $i<count($return->DespatchLine); $i++) {
	$return->DespatchLine[$i]->Product->Get();

	$form->AddField(sprintf('despatch_qty_%d', $return->DespatchLine[$i]->ID), sprintf('Received quantity for %s', $return->DespatchLine[$i]->Product->Name), 'text', $return->DespatchLine[$i]->Quantity, 'numeric_unsigned', 1, 11, true, 'size="3"');
}

$highlightDespatchProducts = false;

if($action == 'resolved') {
	for($i=0; $i<count($return->Line); $i++) {
		if($form->GetValue(sprintf('received_action_%d', $return->Line[$i]->ID)) == 'Replaced') {
			if(count($return->DespatchLine) == 0) {
				$form->AddError('You must insert products to despatch before you are able to resolve this return.');
				$highlightDespatchProducts = true;
			}
		}
	}

	if($form->Valid) {
		if(count($return->DespatchLine) > 0) {
			$order = new Order();
			$order->GenerateFromReturn($return);
		}

		for($i=0; $i<count($return->Line); $i++) {
			if($return->Line[$i]->IsRestocking == 'Y') {
				$data = new DataQuery(sprintf("SELECT MIN(Cost) AS Cost FROM supplier_product WHERE Cost>0 AND Product_ID=%d", mysql_real_escape_string($return->Line[$i]->Product->ID)));
				$cost = ($data->TotalRows > 0) ? $data->Row['Cost'] : 0;
				$data->Disconnect();
				
				$warehouseStock = new WarehouseStock();
				$warehouseStock->Product->ID = $return->Line[$i]->Product->ID;
				$warehouseStock->Warehouse->ID = $warehouse->ID;
				$warehouseStock->QuantityInStock = $return->Line[$i]->Quantity;
				$warehouseStock->Cost = $cost;
				$warehouseStock->Add();
						
				$data = new DataQuery(sprintf("SELECT o.Order_ID FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE o.Is_Warehouse_Undeclined='Y' AND o.Is_Restocked='N' AND ol.Product_ID=%d GROUP BY o.Order_ID", mysql_real_escape_string($return->Line[$i]->Product->ID)));
				while($data->Row) {
					$data2 = new DataQuery(sprintf("UPDATE orders SET Is_Restocked='Y' WHERE Order_ID=%d", $data->Row['Order_ID']));
					$data2->Disconnect();

					$data->Next();
				}
				$data->Disconnect();
			}

			if(strtolower($return->Line[$i]->Status) == 'refunded') {
				$return->IsRefunding ='Y';
			}

			if($return->OrderLine->DespatchedFrom->Type == 'S') {
				if($form->GetValue(sprintf('received_supplier_return_%d', $return->Line[$i]->ID)) == 'Y') {
					$despatch = new Despatch();
					$despatch->IsIgnition = true;

					if($return->OrderLine->DespatchID > 0) {
						$despatch->Get($return->OrderLine->DespatchID);
					}

					$cost = 0;

					$data = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Supplier_ID=%d", mysql_real_escape_string($return->Line[$i]->Product->ID), mysql_real_escape_string($return->OrderLine->DespatchedFrom->Contact->ID)));
					if($data->TotalRows > 0) {
						$cost = $data->Row['Cost'];
					}
					$data->Disconnect();

					if($cost == 0) {
						$data = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d ORDER BY Preferred_Supplier ASC LIMIT 0, 1", mysql_real_escape_string($return->Line[$i]->Product->ID)));
						if($data->TotalRows > 0) {
							$cost = $data->Row['Cost'];
						}
						$data->Disconnect();
					}

					$supplierReturn = new SupplierReturn();
					$supplierReturn->Supplier->ID = $return->OrderLine->DespatchedFrom->Contact->ID;
					$supplierReturn->Product->ID = $return->Line[$i]->Product->ID;
					$supplierReturn->Order->ID = $return->Order->ID;
					$supplierReturn->Cost = $cost;
					$supplierReturn->Quantity = $return->Line[$i]->Quantity;
					$supplierReturn->PurchasedOn = ($despatch->ID > 0) ? $despatch->CreatedOn : '0000-00-00 00:00:00';
					$supplierReturn->Add();
				}
			}
		}

		$return->Status = 'Resolved';
		$return->SendEmail('resolved');
		$return->Update();

		redirect(sprintf("Location: return_details.php?id=%d", $return->ID));
	}
}

if(strtolower($return->Status) == 'unread') {
	$return->Status = 'Pending';
	$return->Update();
}

$warehouseEditable = false;
$isReceived = false;
$isResolved = false;
$isRefused = false;
$isReplacing = false;
$status = strtolower($return->Status);

if($status == 'resolved')
$isResolved = true;
elseif($status == 'refused')
$isRefused = true;
elseif($status == 'waiting')
$isWaiting = true;
elseif($status == 'pending')
$isPending = true;
elseif($status == 'replacing')
$isReplacing = true;

if($status != 'pending' && $status != 'refused' && $status != 'unread') {
	$isAuthorised = true;

	if($status != 'waiting') {
		$isReceived = true;
	}
}

if($isResolved && isset($_REQUEST['view']) && ($_REQUEST['view'] == 'refunding')) {
	$invoice = new Invoice($return->OrderLine->InvoiceID);
	$payment = new Payment();

	$data = new DataQuery(sprintf("SELECT * FROM payment WHERE (Transaction_Type LIKE 'PAYMENT' OR Transaction_Type LIKE 'AUTHORISE') AND Status LIKE 'OK' AND Invoice_ID=%d", mysql_real_escape_string($invoice->ID)));
	if($data->TotalRows > 0) {
		$payment->Get($data->Row['Payment_ID']);
	}
	$data->Disconnect();

	if(!empty($payment->ID)){
		$direct = sprintf('order_refund.php?orderid=%s&paymentid=%d', $invoice->Order->ID, $payment->ID);
	} else {
		$direct = sprintf('order_refund.php?orderid=%s&paymentid=onaccount', $invoice->Order->ID);
	}

	$productIds = array();

	for($i=0; $i<count($return->Line); $i++){
		if(strtolower($return->Line[$i]->Status) == 'refunded') {
			$productIds[] = sprintf('product_%s=%s', $return->Line[$i]->Product->ID, $return->Line[$i]->Quantity);
		}
	}

	if(count($productIds) > 0) {
		redirect(sprintf("Location: %s&%s", $direct, implode('&', $productIds)));
	} else {
		redirect(sprintf("Location: %s", $direct));
	}
}

if($action == "authorise return" && isset($_REQUEST['confirm'])) {
	if($form->Valid) {
		$return->Authorisation = 'R';
		$return->Status = 'Waiting';
		$return->Update();
		$return->SendEmail('authorised');

		redirect("Location: return_details.php?id=". $return->ID);
	}
} elseif($action == 'authorise despatch' && isset($_REQUEST['confirm'])) {
	if($form->Valid) {
		$return->Authorisation = 'D';
		$return->Status = 'Replacing';
		$return->SendEmail('replacing');
		$return->Update();

		redirect("Location: return_details.php?id=". $return->ID);
	}
}

if($action == "delete"){
	$return->Delete();

	redirect("Location: return_search.php");

} elseif($action == "removeline"){
	if(isset($_REQUEST['rid'])) {
		$line = new ProductReturnLine();
		$line->Delete($_REQUEST['rid']);
	}

	redirect(sprintf("Location: return_details.php?id=%d", $return->ID));

} elseif($action == "removedespatchline"){
	if(isset($_REQUEST['rid'])) {
		$line = new ProductReturnLineDespatch();
		$line->Delete($_REQUEST['rid']);
	}

	redirect(sprintf("Location: return_details.php?id=%d", $return->ID));

} elseif($action == "resend"){
	$return->SendEmail();

	redirect(sprintf("Location: return_details.php?id=%d", $return->ID));

} elseif($action == 'received'){
	$return->Status = 'Received';
	$return->ReceivedOn = now();
	$return->SendEmail('received');
	$return->Update();

	redirect(sprintf("Location: return_details.php?id=%d", $return->ID));

} elseif($action == 'refuse' && isset($_REQUEST['confirm'])) {
	if($form->Valid){
		$return->Status = 'refused';
		$return->Update();

		redirect("Location: return_notes.php?subject=1&sendEmail=Y&id=". $return->ID);
	}

} elseif(($action == 'update') && isset($_REQUEST['confirm'])){
	if($form->Validate()) {

		for($i=0; $i<count($return->Line); $i++) {
			$return->Line[$i]->Status = $form->GetValue(sprintf('received_action_%d', $return->Line[$i]->ID));
			$return->Line[$i]->IsRestocking = $form->GetValue(sprintf('received_restock_%d', $return->Line[$i]->ID));
			$return->Line[$i]->Quantity = $form->GetValue(sprintf('received_qty_%d', $return->Line[$i]->ID));

			if($return->Line[$i]->Quantity <= 0) {
				$return->Line[$i]->Quantity = 1;
			}

			$return->Line[$i]->Update();
		}

		redirect(sprintf("Location: return_details.php?id=%d", $return->ID));
	}

} elseif(($action == 'add received product') && isset($_REQUEST['confirm'])){
	$product = new Product();

	if($product->Get($_REQUEST['pidReceived'])){
		$return->AddLine($_REQUEST['pidReceived'], $_REQUEST['qtyReceived']);
	} else {
		$form->AddError(sprintf('The product \'%s\' could not be found in the database.', $_REQUEST['pidReceived']));
	}

	if($form->Valid) {
		redirect(sprintf("Location: return_details.php?id=%d", $return->ID));
	}
} elseif(($action == 'add despatch product') && isset($_REQUEST['confirm'])){
	$product = new Product();

	if($product->Get($_REQUEST['pidDespatch'])){
		$return->AddDespatchLine($_REQUEST['pidDespatch'], $_REQUEST['qtyDespatch']);
	} else {
		$form->AddError(sprintf('The product \'%s\' could not be found in the database.', $_REQUEST['pidDespatch']));
	}

	if($form->Valid) {
		redirect(sprintf("Location: return_details.php?id=%d", $return->ID));
	}
}

if(isset($_REQUEST['confirm'])) {
	if(isset($_REQUEST['addsupplierreturnrequest'])) {
		if($form->GetValue('supplier') > 0) {
			$return->Order->Get();
			$return->Order->GetLines();

			for($i=0; $i<count($return->Order->Line); $i++) {
				$return->Order->Line[$i]->Get();
				$return->Order->Line[$i]->DespatchedFrom->Get();
			}

			$request = new SupplierReturnRequest();
            $request->Supplier->ID = $form->GetValue('supplier');
            $request->Order->ID = $return->Order->ID;
			$request->Status = 'Pending';
			$request->Add();

            for($i=0; $i<count($return->Line); $i++) {
                for($j=0; $j<count($return->Order->Line); $j++) {
                	if($return->Line[$i]->Product->ID == $return->Order->Line[$j]->Product->ID) {
                		if($return->Order->Line[$j]->DespatchedFrom->Type == 'S') {
                			if($return->Order->Line[$j]->DespatchedFrom->Contact->ID == $request->Supplier->ID) {
								$requestLine = new SupplierReturnRequestLine();
								$requestLine->SupplierReturnRequestID = $request->ID;
								$requestLine->Product->ID = $return->Line[$i]->Product->ID;
								$requestLine->Quantity = $return->Line[$i]->Quantity;
								$requestLine->Add();
							}
						}
					}
				}
			}

			$request->Recalculate();

			$return->SupplierReturnRequest->ID = $request->ID;
			$return->Update();

			redirect(sprintf('Location: supplier_return_request_details.php?requestid=%d', $request->ID));
		}
	}
}


$script = sprintf('<script language="javascript" type="text/javascript">
	var foundReceivedProduct = function(pid) {
		var e = document.getElementById(\'productReceived\');
		if(e) {
			e.value = pid;
		}
	}
	</script>');

$script .= sprintf('<script language="javascript" type="text/javascript">
	var foundDespatchProduct = function(pid) {
		var e = document.getElementById(\'productDespatch\');
		if(e) {
			e.value = pid;
		}
	}
	</script>');

$style = sprintf('<style media="print">
		.pageSubTitle {
			color: #000;
		}

		a {
			color: #000;
		}
	</style>');

$page = new Page(sprintf('%s Return Details for %s', $return->ID, $return->Customer->Contact->Person->GetFullName()));
$page->AddToHead($script);
$page->AddToHead($style);
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo "<br>";
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('id');
?>

<table width="100%"  border="0" cellspacing="0" cellpadding="0">
  <tr>
		<td valign="top">

      <table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
      <tr>
					<td valign="top" class="shipping">
						<p>
							<strong>Customer Address:</strong><br />
							<?php echo $return->Customer->Contact->Person->GetFullName(); ?><br />
							<?php echo $return->Customer->Contact->Person->Address->GetFormatted('<br />'); ?><br /><br />
							<?php echo $return->Customer->Contact->Person->GetPhone('<br />'); ?>
						</p>
					</td>
      </tr>
    </table>

	  <?php
	  if(strtolower($return->Status) != 'cancelled'){
		 ?>
      <p><br /><br />
        <?php if(!$isAuthorised){ ?>
        <input name="cancel" type="button" id="cancel" value="cancel" class="btn" onclick="popUrl('./return_cancel.php?id=<?php echo $return->ID; ?>', 650, 450);" />
        <input name="delete" type="button" id="delete" value="delete" class="btn" onclick="confirmRequest('./return_details.php?id=<?php echo $return->ID; ?>&amp;action=delete', 'Are you sure you would like to delete this order permanently?');" />
        <?php } elseif(!$isReceived) { ?>
        <input name="action" type="submit" id="received" value="received" class="btn" />

<?php
        } elseif(strtolower($return->Status) != 'resolved') {
        	$unresolved = false;
        	if(count($return->Line) > 0){
        		foreach($return->Line as $l){
        			if(strtolower($l->Status) == 'unresolved')
        			$unresolved = true;
        		}
        	} else {
        		$unresolved = true;
        	}

        	if(!$unresolved || ($isReplacing && count($return->Line) > 0)){
        		echo '<input name="action" type="submit" id="resolved" value="resolved" class="btn" />';
        	}
        }

        if(!$isAuthorised){
		?>
        <input name="action" type="submit" id="authoriseReturn" value="authorise return" class="btn" />
        <input name="action" type="submit" id="authoriseDespatch" value="authorise despatch" class="btn" />
		<?php
		if(!$isRefused){
		?>
        <input name="action" type="submit" id="refuse" value="refuse" class="btn" onclick="window.location.href='return_notes.php?id=<?php echo $return->ID ?>'" />
		<?php
		}
        }
		?>
        </p>
        <?php } ?>
    </td><td align="right" valign="middle"><table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
      <tr>
        <th valign="top">Return:</th>
        <td valign="top"><?php echo $return->Prefix . $return->ID; ?></td>
      </tr>

      <?php
      if($return->SupplierReturnRequest->ID > 0) {
      	  ?>

	      <tr>
	        <th valign="top">Supplier Return Request:</th>
	        <td valign="top"><a href="supplier_return_request_details.php?requestid=<?php echo $return->SupplierReturnRequest->ID; ?>"><?php echo $return->SupplierReturnRequest->ID; ?></a></td>
	      </tr>

	      <?php
	  }
	  ?>

	  <tr>
        <th valign="top">Order:</th>
        <td valign="top"><a href="order_details.php?orderid=<?php echo $return->Order->ID; ?>"><?php echo $return->Order->ID; ?></a></td>
      </tr>
      <tr>
        <th valign="top">Order Date:</th>
        <td valign="top"><?php $return->Order->Get(); echo cDatetime($return->Order->CreatedOn, 'shortdate'); ?></td>
      </tr>
      <tr>
        <th valign="top">Customer:</th>
        <td valign="top"><a href="contact_profile.php?cid=<?php echo $return->Customer->Contact->ID; ?>"><?php echo $return->Customer->Contact->Person->GetFullName(); ?></a></td>
      </tr>
      <tr>
        <th valign="top">Status:</th>
        <td valign="top"><?php echo isset($status) ? ucfirst($status) : $return->Status; ?></td>
      </tr>
      <tr>
        <th valign="top">&nbsp;</th>
        <td valign="top">&nbsp;</td>
      </tr>
      <tr>
        <th valign="top">Emailed On:</th>
        <td valign="top"><?php echo (!empty($return->EmailedOn))?cDatetime($return->EmailedOn, 'shortdate'):'';  ?>&nbsp;</td>
      </tr>
      <tr>
        <th valign="top">Emailed To:</th>
        <td valign="top"><?php echo (empty($return->EmailedTo) ? '' : $return->EmailedTo . '&nbsp;'); ?><a href="return_details.php?id=<?php echo $return->ID; ?>&amp;action=resend" title="Click to Resend Order Confirmation to Customer">(resend)</a></td>
      </tr>
      <tr>
        <th valign="top">Received On:</th>
        <td valign="top"><?php echo cDatetime($return->ReceivedOn, 'shortdate'); ?></td>
      </tr>
    </table>

    </td>
  </tr>
  <tr>
    <td colspan="2">
    	<br /><br />

		<?php
		if($isResolved && isset($_REQUEST['view']) && ($_REQUEST['view'] == 'refunding')) {
			?>

			<div style="background-color: #f6f6f6; padding: 10px;">
					<p><span class="pageSubTitle">Products Refunding</span><br /><span class="pageDescription">Listing received products for refunding to the customer.</span></p>

					<table cellspacing="0" class="orderDetails">
						<tr>
							<th>Qty</th>
							<th>Product</th>
							<th style="text-align: center;">Quickfind</th>
						</tr>

						<?php
						for($i=0; $i<count($return->Line); $i++){
							if(strtolower($return->Line[$i]->Status) == 'refunded') {
								?>

								<tr>
									<td><?php echo $return->Line[$i]->Quantity; ?>x</td>
									<td><?php echo $return->Line[$i]->Product->Name; ?></td>
									<td align="center"><a href="product_profile.php?pid=<?php echo $return->Line[$i]->Product->ID; ?>"><?php echo $return->Line[$i]->Product->ID; ?></a></td>
								</tr>

								<?php
							}
						}
						?>

					</table><br />

					<?php
					if(strtolower($return->Status) != 'resolved') {
						?>

						<strong>Add Product</strong> <a href="javascript:popUrl('popFindProduct.php?callback=foundReceivedProduct', 650, 500);"><img src="./images/icon_search_1.gif" alt="Search for products" border="0" align="absmiddle" /></a><br />
						<input type="text" id="productReceived" name="pidReceived" value="" /> x <input type="text" id="qtyReceived" name="qtyReceived" value="1" size="3" maxlength="11" /> <input type="submit" name="action" value="add received product" class="btn" />

						<?php
					}
					?>

				</div><br />

			<?php
		} else {
			$data = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Preferred_Supplier='Y' AND Product_ID=%d", mysql_real_escape_string($return->OrderLine->Product->ID)));
			$costPrice = ($data->TotalRows > 0) ? $data->Row['Cost'] : 0;
			$data->Disconnect();
			?>

			<div style="background-color: #f6f6f6; padding: 10px;">
				<p><span class="pageSubTitle">Originally Ordered Product</span><br /><span class="pageDescription">Some products are made up of multiple components. If you are only despatching some components, please add them to the despatch list below. If there is only one component in the product, or you are replacing all components in the product, you can add the products quickfind code directly.</span></p>

				<table cellspacing="0" class="orderDetails">
					<tr>
						<th>Qty</th>
						<th>Product</th>
						<th>Quickfind</th>
						<th>Cost Price</th>
						<th>Sale Price</th>
					</tr>
					<tr>
						<td><?php echo $return->Quantity; ?>x</td>
						<td><?php echo $return->OrderLine->Product->Name; ?></td>
						<td><a href="product_profile.php?pid=<?php echo $return->OrderLine->Product->ID; ?>"><?php echo $return->OrderLine->Product->ID; ?></a></td>
						<td>&pound;<?php echo number_format($costPrice, 2, '.', ','); ?></td>
						<td>&pound;<?php echo number_format($return->OrderLine->Product->PriceCurrent, 2, '.', ','); ?></td>
					</tr>
				</table>

			</div><br />

			<?php
			if($isReceived) {
				?>

				<div style="background-color: #f6f6f6; padding: 10px;">
					<p><span class="pageSubTitle">Products Received</span><br /><span class="pageDescription">List the products received from the customer here.</span></p>

					<table cellspacing="0" class="orderDetails">
						<tr>
							<th>Qty</th>
							<th>Product</th>
							<th style="text-align: center;">Quickfind</th>
							<th>Action</th>
							<th style="text-align: center;">Restocking</th>

							<?php
							if(strtolower($return->Status) != 'resolved') {
								if($return->OrderLine->DespatchedFrom->Type == 'S') {
									echo '<th style="text-align: center;">Return To Supplier</th>';
								}
							}
							?>

						</tr>

						<?php
						for($i=0; $i<count($return->Line); $i++){
							?>

							<tr>

								<?php
								if(strtolower($return->Status) != 'resolved') {
									?>

									<td>
										<a href="javascript:confirmRequest('return_details.php?id=<?php echo $return->ID; ?>&action=removeline&rid=<?php echo $return->Line[$i]->ID; ?>', 'Are you sure you wish to remove this product?')"><img src="images/icon_trash_1.gif" alt="Remove" border="0" /></a>
										<?php echo $form->GetHTML(sprintf('received_qty_%d', $return->Line[$i]->ID)); ?> x
									</td>

									<?php
								} else {
									?>

									<td><?php echo $return->Line[$i]->Quantity; ?>x</td>

									<?php
								}
								?>

								<td><?php echo $return->Line[$i]->Product->Name; ?></td>
								<td align="center"><a href="product_profile.php?pid=<?php echo $return->Line[$i]->Product->ID; ?>"><?php echo $return->Line[$i]->Product->ID; ?></a></td>

								<?php
								if(strtolower($return->Status) != 'resolved') {
									?>

									<td><?php echo $form->GetHTML(sprintf('received_action_%d', $return->Line[$i]->ID)); ?>&nbsp;</td>
									<td align="center"><?php echo $form->GetHTML(sprintf('received_restock_%d', $return->Line[$i]->ID)); ?></td>

									<?php
									if($return->OrderLine->DespatchedFrom->Type == 'S') {
										?>
										<td align="center"><?php echo $form->GetHTML(sprintf('received_supplier_return_%d', $return->Line[$i]->ID)); ?></td>
										<?php
									}
									?>

									<?php
								} else {
									?>

									<td><?php echo ucfirst($return->Line[$i]->Status); ?></td>
									<td align="center"><?php echo ($return->Line[$i]->IsRestocking == 'Y') ? '<img src="./images/icon_tick_2.gif" border="0" />' : '&nbsp;'; ?></td>

									<?php
								}
								?>

							</tr>

							<?php
						}

						if(count($return->Line) == 0) {
							?>

							<tr>
								<td colspan="<?php echo (!$isResolved && ($return->OrderLine->DespatchedFrom->Type == 'S')) ? 6 : 5; ?>" align="center">No products available for viewing.</td>
							</tr>

							<?php
						}
						?>

					</table>
					<br />

					<table width="100%">
						<tr>
							<td align="left">

                                <?php
								if(strtolower($return->Status) != 'resolved') {
									?>

									<strong>Add Product</strong> <a href="javascript:popUrl('popFindProduct.php?callback=foundReceivedProduct', 650, 500);"><img src="./images/icon_search_1.gif" alt="Search for products" border="0" align="absmiddle" /></a><br />
									<input type="text" id="productReceived" name="pidReceived" value="" /> x <input type="text" id="qtyReceived" name="qtyReceived" value="1" size="3" maxlength="11" /> <input type="submit" name="action" value="add received product" class="btn" />

									<?php
								}
								?>

							</td>
							<td align="right">

                                <?php
								if($return->SupplierReturnRequest->ID == 0) {
									if($hasReturnSuppliers) {
										echo $form->GetHTML('supplier');
										echo '<input type="submit" name="addsupplierreturnrequest" value="add supplier return request" class="btn" />';
									}
								}
								?>

							</td>
						</tr>
					</table>
					<br />

				</div><br />
				
				<?php
				$isDespatchable = false;
				
				for($i=0; $i<count($return->Line); $i++) {
					if($return->Line[$i]->Status == 'Replaced') {
						$isDespatchable = true;
						break;
					}
				}
				
				if($isDespatchable) {
					?>

					<div style="background-color: #f6f6f6; padding: 10px; <?php echo ($highlightDespatchProducts) ? 'background-color: #FAF9B5;' : '' ?>">
						<p><span class="pageSubTitle">Despatch Products</span><br /><span class="pageDescription">List the products you wish to despatch from this return to the customer.</span></p>

						<table cellspacing="0" class="orderDetails">
							<tr>
								<th>Qty</th>
								<th>Product</th>
								<th style="text-align: center;">Quickfind</th>
							</tr>

							<?php
							for($i=0; $i<count($return->DespatchLine); $i++){
								?>

								<tr>

									<?php
									if(strtolower($return->Status) != 'resolved') {
										?>

										<td>
											<a href="javascript:confirmRequest('return_details.php?id=<?php echo $return->ID; ?>&action=removedespatchline&rid=<?php echo $return->DespatchLine[$i]->ID; ?>', 'Are you sure you wish to remove this product?')"><img src="images/icon_trash_1.gif" alt="Remove" border="0" /></a>
											<?php echo $form->GetHTML(sprintf('despatch_qty_%d', $return->DespatchLine[$i]->ID)); ?> x
										</td>

										<?php
									} else {
										?>

										<td><?php echo $return->DespatchLine[$i]->Quantity; ?>x</td>

										<?php
									}
									?>

									<td><?php echo $return->DespatchLine[$i]->Product->Name; ?></td>
									<td align="center"><a href="product_profile.php?pid=<?php echo $return->DespatchLine[$i]->Product->ID; ?>"><?php echo $return->DespatchLine[$i]->Product->ID; ?></a></td>
								</tr>

								<?php
							}

							if(count($return->DespatchLine) == 0) {
								?>

								<tr>
									<td colspan="<?php echo (!$isResolved) ? 5 : 4; ?>" align="center">No products available for viewing.</td>
								</tr>

								<?php
							}
							?>

						</table><br />

						<?php
						if(strtolower($return->Status) != 'resolved') {
							?>

							<strong>Add Product</strong> <a href="javascript:popUrl('popFindProduct.php?callback=foundDespatchProduct', 650, 500);"><img src="./images/icon_search_1.gif" alt="Search for products" border="0" align="absmiddle" /></a><br />
							<input type="text" id="productDespatch" name="pidDespatch" value="" /> x <input type="text" id="qtyDespatch" name="qtyDespatch" value="1" size="1" maxlength="2" /> <input type="submit" name="action" value="add despatch product" class="btn" />

							<?php
						}
						?>

					</div><br />

					<?php
				}
			}
		}
		?>

    </td>
  </tr>
  <tr>
    <td align="left" valign="top">

	    <?php
	    if(strtolower($return->Status) != 'resolved') {
	    	if($isReceived) {
				?>

      			<input type="submit" name="action" value="update" class="btn" />
				<br /><br /><br />

				<?php
	    	}
	    }
		?>

      <strong>Additional Information:</strong>
      <br />
      <table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
        <tr>
          <th valign="top">Reason:</th>
          <td valign="top">
          <?php echo $return->Reason->Title;
          if(!empty($return->Reason->Description))
          echo ' &ndash; ' . nl2br(htmlspecialchars($return->Reason->Description));
          ?>
		  </td>
        </tr>
        <tr>
          <th valign="top">Customer Note:</th>
          <td valign="top">
		  <?php echo nl2br(htmlspecialchars($return->Note)); ?>
		  </td>
        </tr>
        <tr>
        <th valign="top"><a href="return_note.php?id=<?php echo $return->ID ?>">Admin Note:</a></th>
          <td valign="top">
		  <?php echo nl2br(htmlspecialchars($return->AdminNote)); ?>
		  </td>
        </tr>
        <tr>
          <th valign="top"><a href="return_notes.php?id=<?php echo $return->ID ?>">Return Notes:</a></th>
          <td valign="top"><a href="return_notes.php?id=<?php echo $return->ID ?>">Add new note</a></td>
        </tr>
        <tr>
          <th valign="top">Despatched From:</th>
          <td valign="top"><?php echo $warehouse->Name; ?>
          </td>
        </tr>
      </table>

     </td>
  </tr>
</table>

<?php
echo $form->Close();

$page->Display('footer');
