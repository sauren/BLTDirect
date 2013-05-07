<?php
ini_set('max_execution_time', '900');

require_once('lib/common/app_header.php');

DataQuery::allowCaching(false);

if($action == 'check') {
	$session->Secure(3);
	check();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$fields = 30;

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('invoiceid', 'Invoice ID', 'text', '', 'numeric_unsigned', 1, 11);
	$form->AddField('invoicedate', 'Invoice Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('supplier', 'Supplier', 'select', '0', 'numeric_unsigned', 1, 11);

	$data = new DataQuery(sprintf("SELECT s.Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Supplier FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID INNER JOIN organisation AS o ON o.Org_ID=c.Org_ID"));
	while($data->Row) {
		$form->AddOption('supplier', $data->Row['Supplier_ID'], $data->Row['Supplier']);

		$data->Next();
	}
	$data->Disconnect();

	for($i = 0; $i < $fields; $i++) {
		$form->AddField('order_'.$i, '['.($i+1).'] Order ID', 'text', '', 'numeric_unsigned', 1, 11, false);
	}

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()) {
			$orders = array();

			foreach($_REQUEST as $key=>$value) {
				for($i = 0; $i < $fields; $i++) {
					if('order_'.$i == $key) {
						if(trim(strlen($value)) > 0) {
							$orders[$value] = $value;
						}
					}
				}
			}

			redirect(sprintf("Location: %s?action=check&supplier=%d&invoiceid=%s&invoicedate=%s&orders=%s", $_SERVER['PHP_SELF'], $form->GetValue('supplier'), $form->GetValue('invoiceid'), $form->GetValue('invoicedate'), implode(',', $orders)));
		}
	}

	$page = new Page('Invoice Control');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Invoice control.");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Enter the orders for which you wish to check supplier invoices against.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHtml('supplier') . $form->GetIcon('supplier'));
	echo $webForm->AddRow($form->GetLabel('invoiceid'), $form->GetHtml('invoiceid') . $form->GetIcon('invoiceid'));
	echo $webForm->AddRow($form->GetLabel('invoicedate'), $form->GetHtml('invoicedate') . $form->GetIcon('invoicedate'));
	echo $webForm->Close();

	echo $window->AddHeader('Invoice check the following orders.');
	echo $window->OpenContent();
	echo $webForm->Open();

	for($i = 0; $i < $fields; $i++) {
		echo $webForm->AddRow('['.($i+1).'] Order ID #', $form->GetHTML('order_'.$i));
	}

	echo $webForm->AddRow('&nbsp','<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function check() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierShippingCalculator.php');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'check', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('invoiceid', 'Invoice ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('invoicedate', 'Invoice Date', 'hidden', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('supplier', 'Supplier', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('orders', 'Orders', 'hidden', '', 'anything', 0, 2048);

	$ordersStr = explode(',', $form->GetValue('orders'));
	$orders = array();

	foreach($ordersStr as $orderId) {
		if(is_numeric($orderId)) {
			$order = new Order($orderId);
			$order->GetLines();

			$orders[] = $order;

			foreach($order->Line as $line) {
				$form->AddField(sprintf('qty_line_%d', $line->ID), sprintf('Quantity Invoiced for %s', $line->Product->Title), 'text', '', 'numeric_unsigned', 1, 11, false, 'size="3"');
				$form->AddField(sprintf('price_line_%d', $line->ID), sprintf('Invoice Price for %s', $line->Product->Title), 'text', '', 'float', 1, 11, false, 'size="3"');
			}
		}
	}

	$supplier = new Supplier($form->GetValue('supplier'));
	$supplier->Contact->Get();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()) {
			$discrepancies = array();

			foreach($orders as $order) {
				$item['Order'] = $order;
				$item['Quantity'] = array();
				$item['Price'] = array();

				foreach($order->Line as $line) {
					if(strlen($form->GetValue(sprintf('qty_line_%d', $line->ID))) > 0) {
						$item['Quantity'][$line->ID] = $form->GetValue(sprintf('qty_line_%d', $line->ID));
					}

					if(strlen($form->GetValue(sprintf('price_line_%d', $line->ID))) > 0) {
						$item['Price'][$line->ID] = $form->GetValue(sprintf('price_line_%d', $line->ID));
					}
				}

				if((count($item['Quantity']) > 0) || (count($item['Price']) > 0)) {
					$discrepancies[] = $item;
				}
			}

			report($supplier->ID, $form->GetValue('invoiceid'), $form->GetValue('invoicedate'), $discrepancies);
			exit;
		}
	}

	/*$shippingCosts = array();

	$data = new DataQuery(sprintf("SELECT * FROM supplier_shipping WHERE Supplier_ID=%d", $supplier->ID));
	while($data->Row) {
		$shippingCosts[] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();*/

	$page = new Page($supplier->Contact->Person->GetFullName() . ' (Invoice #'.$form->GetValue('invoiceid').' - ' . $form->GetValue('invoicedate') . ')');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('invoiceid');
	echo $form->GetHTML('invoicedate');
	echo $form->GetHTML('supplier');
	echo $form->GetHTML('orders');

	$invoiceTotal = 0;

	foreach($orders as $order) {
		?>

		<h3>Order #<?php echo $order->ID; ?></h3>
		<p>Listing despatched orders lines for <?php echo $supplier->Contact->Person->GetFullName(); ?> only.</p>

		<table width="100%" border="0">
			<tr>
				<td width="12%" style="border-bottom:1px solid #aaaaaa"><strong>Qty Despatched</strong></td>
				<td width="10%" style="border-bottom:1px solid #aaaaaa"><strong>Qty Invoiced</strong></td>
				<td style="border-bottom:1px solid #aaaaaa"><strong>Product</strong></td>
				<td width="8%" align="center" style="border-bottom:1px solid #aaaaaa"><strong>Quickfind</strong></td>
				<td width="8%" align="right" style="border-bottom:1px solid #aaaaaa"><strong>Cost Price</strong></td>
				<td width="12%" align="right" style="border-bottom:1px solid #aaaaaa"><strong>Line Cost Price</strong></td>
				<td width="12%" align="right" style="border-bottom:1px solid #aaaaaa"><strong>Invoice Price</strong></td>
			</tr>

			<?php
			$totalCost = 0;

			foreach($order->Line as $line) {
				$line->DespatchedFrom->Get();

				if($line->DespatchedFrom->Contact->ID == $supplier->ID) {
					$totalCost += $line->Cost * $line->Quantity;
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td><?php echo $line->Quantity; ?>x</td>
						<td><?php echo $form->GetHTML(sprintf('qty_line_%d', $line->ID)); ?>x</td>
						<td><?php echo $line->Product->Name; ?></td>
						<td align="center"><a href="product_profile.php?pid=<?php echo $line->Product->ID; ?>"><?php echo $line->Product->ID; ?></a></td>
						<td align="right">&pound;<?php echo number_format($line->Cost, 2, '.', ','); ?></td>
						<td align="right">&pound;<?php echo number_format($line->Cost * $line->Quantity, 2, '.', ','); ?></td>
						<td align="right">&pound;<?php echo $form->GetHTML(sprintf('price_line_%d', $line->ID)); ?></td>
					</tr>

					<?php
				}
			}
			?>

			</table>
			<br />

			<?php
			$productCost = 0;
			$productWeight = 0;

			for($i=0; $i<count($order->Line); $i++) {
				$order->Line[$i]->Product->Get();

				$productCost += $order->Line[$i]->Cost * $order->Line[$i]->Quantity;
				$productWeight += $order->Line[$i]->Product->Weight * $order->Line[$i]->Quantity;
			}

			$shippignCalculator = new SupplierShippingCalculator($order->Shipping->Address->Country->ID, $order->Shipping->Address->Region->ID, $productCost, $productWeight, $order->Postage->ID, $supplier->ID);

			for($i=0; $i<count($order->Line); $i++) {
				$shippignCalculator->Add($order->Line[$i]->Quantity, $order->Line[$i]->Product->ShippingClass->ID);
			}

			$shippingCost = $shippignCalculator->GetTotal();

			$orderTotal = 0;
			$orderTotal += $totalCost;
			$orderTotal += $shippingCost;
			?>

			<table width="100%" border="0">
				<tr>
					<td width="75%" style="border-bottom:1px solid #aaaaaa"><strong>Item</strong></td>
					<td width="25%" align="right" style="border-bottom:1px solid #aaaaaa"><strong>Value</strong></td>
				</tr>

				<?php
				if($order->Postage->ID > 0) {
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td><?php echo$order->Postage->Name; ?></td>
						<td align="right">&pound;<?php echo number_format($shippingCost, 2, '.', ','); ?></td>
					</tr>

					<?php
				}
				?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>Products</td>
				<td align="right">&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></td>
			</tr>
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>&nbsp;</td>
				<td align="right"><strong>&pound;<?php echo number_format($orderTotal, 2, '.', ','); ?></strong></td>
			</tr>
		</table>
		<br />

		<?php
		$invoiceTotal += $orderTotal;
	}
	?>

	<table width="100%" border="0">
		<tr>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Total Invoice Amount</strong></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td align="right"><strong>&pound;<?php echo number_format($invoiceTotal, 2, '.', ','); ?></strong></td>
		</tr>
	</table>
	<br />

	<input type="submit" value="submit" name="submit" class="btn" />

	<?php
	echo $form->Close();
}

function report($supplierId, $invoiceId, $invoiceDate, $orders) {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');

	$supplier = new Supplier($supplierId);
	$supplier->Contact->Get();

	$page = new Page($supplier->Contact->Person->GetFullName() . ' (Invoice #'.$invoiceId.' - ' . $invoiceDate . ')');
	$page->Display('header');

	foreach($orders as $orderItem) {
		$order = new Order($orderItem['Order']->ID);
		$order->GetLines();
		?>

		<h3>Order #<?php echo $order->ID; ?></h3>
		<p>Listing despatched orders lines for <?php echo $supplier->Contact->Person->GetFullName(); ?> only.</p>

		<table width="100%" border="0">
			<tr>
				<td style="border-bottom:1px solid #aaaaaa"><strong>Original Quantity</strong></td>
				<td style="border-bottom:1px solid #aaaaaa"><strong>Invoiced Quantity</strong></td>
				<td style="border-bottom:1px solid #aaaaaa"><strong>Product</strong></td>
				<td align="center" style="border-bottom:1px solid #aaaaaa"><strong>Quickfind</strong></td>
				<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Cost Price</strong></td>
				<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Invoice Cost</strong></td>
				<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Difference</strong></td>
			</tr>

			<?php
			$totalCost = 0;
			$totalDifference = 0;

			foreach($order->Line as $line) {
				$line->DespatchedFrom->Get();

				if($line->DespatchedFrom->Contact->ID == $supplier->ID) {
					$difference = ((isset($orderItem['Quantity'][$line->ID]) ? $orderItem['Quantity'][$line->ID] : $line->Quantity) * (isset($orderItem['Price'][$line->ID]) ? $orderItem['Price'][$line->ID] : $line->Cost)) - ($line->Cost * $line->Quantity);

					$totalDifference += $difference;
					$totalCost += $line->Cost * $line->Quantity;
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td><?php echo $line->Quantity; ?>x</td>
						<td><?php echo isset($orderItem['Quantity'][$line->ID]) ? $orderItem['Quantity'][$line->ID] : $line->Quantity; ?>x</td>
						<td><?php echo $line->Product->Name; ?></td>
						<td align="center"><a href="product_profile.php?pid=<?php echo $line->Product->ID; ?>"><?php echo $line->Product->ID; ?></a></td>
						<td align="right">&pound;<?php echo number_format($line->Cost, 2, '.', ','); ?></td>
						<td align="right">&pound;<?php echo number_format(isset($orderItem['Price'][$line->ID]) ? $orderItem['Price'][$line->ID] : $line->Cost, 2, '.', ','); ?></td>
						<td align="right">&pound;<?php echo number_format($difference, 2, '.', ','); ?></td>
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
				<td align="right"><strong>&pound;<?php echo number_format($totalDifference, 2, '.', ','); ?></strong></td>
			</tr>
		</table><br />

		<?php
	}
}
?>