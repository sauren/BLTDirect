<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/chart/libchart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('topfrom', 'Top Products (From)', 'text', '1', 'numeric_unsigned', 1, 11, true, 'size="2"');
$form->AddField('topto', 'Top Products (To)', 'text', '1000', 'numeric_unsigned', 1, 11, true, 'size="2"');

$products = array();
$orders = array();
$type = array();
$suppliers = array();

$data = new DataQuery(sprintf('SELECT Product_ID FROM product WHERE Position_Orders_Recent BETWEEN %d AND %d ORDER BY Position_Orders_Recent ASC', mysql_real_escape_string($form->GetValue('topfrom')), mysql_real_escape_string($form->GetValue('topto'))));
while($data->Row) {
	$products[] = $data->Row['Product_ID'];

	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf('SELECT w.Warehouse_ID, w.Warehouse_Name, COUNT(DISTINCT o.Order_ID) AS Orders_Before FROM warehouse AS w INNER JOIN order_line AS ol ON ol.Despatch_From_ID=w.Warehouse_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID WHERE o.Status IN (\'Unread\', \'Pending\', \'Purchasing\', \'Packing\', \'Partially Despatched\') AND o.Is_Declined=\'N\' AND o.Is_Warehouse_Declined=\'N\' AND ol.Despatch_ID=0 AND w.Type=\'S\' GROUP BY w.Warehouse_ID ORDER BY Orders_Before DESC'));
while($data->Row) {
	$item = $data->Row;
	$item['Orders_After'] = $item['Orders_Before'];
	
	$orders[$data->Row['Warehouse_ID']] = $item;

	$data->Next();	
}
$data->Disconnect();

if(!empty($products)) {
	$data = new DataQuery(sprintf('SELECT w.Warehouse_ID, w.Warehouse_Name, COUNT(DISTINCT o.Order_ID) AS Orders_After FROM warehouse AS w INNER JOIN order_line AS ol ON ol.Despatch_From_ID=w.Warehouse_ID AND ol.Product_ID NOT IN (%s) INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID WHERE o.Status IN (\'Unread\', \'Pending\', \'Purchasing\', \'Packing\', \'Partially Despatched\') AND o.Is_Declined=\'N\' AND o.Is_Warehouse_Declined=\'N\' AND ol.Despatch_ID=0 AND w.Type=\'S\' GROUP BY w.Warehouse_ID ORDER BY Orders_After DESC', implode(', ', $products)));
	while($data->Row) {
		$orders[$data->Row['Warehouse_ID']]['Orders_After'] = $data->Row['Orders_After'];

		$data->Next();	
	}
	$data->Disconnect();
}

$data = new DataQuery(sprintf('SELECT SUM(ol1.Count) AS Count_Pending, SUM(ol2.Count) AS Count_Purchasing, SUM(ol3.Count) AS Count_Packing, SUM(ol4.Count) AS Count_Partially_Despatched, SUM(ol5.Count) AS Count_Backordered FROM warehouse AS w LEFT JOIN (SELECT ol.Despatch_From_ID, COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE ol.Despatch_ID=0 AND o.Status LIKE \'Pending\' AND o.Is_Declined=\'N\' AND o.Is_Warehouse_Declined=\'N\' GROUP BY ol.Despatch_From_ID) AS ol1 ON ol1.Despatch_From_ID=w.Warehouse_ID LEFT JOIN (SELECT ol.Despatch_From_ID, COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE ol.Despatch_ID=0 AND o.Status LIKE \'Purchasing\' AND o.Is_Declined=\'N\' AND o.Is_Warehouse_Declined=\'N\' GROUP BY ol.Despatch_From_ID) AS ol2 ON ol2.Despatch_From_ID=w.Warehouse_ID LEFT JOIN (SELECT ol.Despatch_From_ID, COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE ol.Despatch_ID=0 AND o.Status LIKE \'Packing\' AND o.Is_Declined=\'N\' AND o.Is_Warehouse_Declined=\'N\' GROUP BY ol.Despatch_From_ID) AS ol3 ON ol3.Despatch_From_ID=w.Warehouse_ID LEFT JOIN (SELECT ol.Despatch_From_ID, COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE ol.Despatch_ID=0 AND o.Status LIKE \'Partially Despatched\' AND o.Is_Declined=\'N\' AND o.Is_Warehouse_Declined=\'N\' GROUP BY ol.Despatch_From_ID) AS ol4 ON ol4.Despatch_From_ID=w.Warehouse_ID LEFT JOIN (SELECT ol.Despatch_From_ID, COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE ol.Despatch_ID=0 AND ol.Line_Status LIKE \'Backordered\' AND o.Is_Declined=\'N\' AND o.Is_Warehouse_Declined=\'N\' GROUP BY ol.Despatch_From_ID) AS ol5 ON ol5.Despatch_From_ID=w.Warehouse_ID WHERE w.Type=\'B\''));
if($data->TotalRows) {
	$type['Count_Pending'] = array('Type' => 'Pending', 'Orders_Before' => $data->Row['Count_Pending'], 'Orders_After' => $data->Row['Count_Pending']);
	$type['Count_Purchasing'] = array('Type' => 'Purchasing', 'Orders_Before' => $data->Row['Count_Purchasing'], 'Orders_After' => $data->Row['Count_Purchasing']);
	$type['Count_Packing'] = array('Type' => 'Packing', 'Orders_Before' => $data->Row['Count_Packing'], 'Orders_After' => $data->Row['Count_Packing']);
	$type['Count_Partially_Despatched'] = array('Type' => 'Partially Despatched', 'Orders_Before' => $data->Row['Count_Partially_Despatched'], 'Orders_After' => $data->Row['Count_Partially_Despatched']);
	$type['Count_Backordered'] = array('Type' => 'Backordered', 'Orders_Before' => $data->Row['Count_Backordered'], 'Orders_After' => $data->Row['Count_Backordered']);
}
$data->Disconnect();

$data = new DataQuery(sprintf('SELECT w.Warehouse_ID, w.Warehouse_Name, SUM(ol1.Count) AS Count_Pending, SUM(ol2.Count) AS Count_Purchasing, SUM(ol3.Count) AS Count_Packing, SUM(ol4.Count) AS Count_Partially_Despatched, SUM(ol5.Count) AS Count_Backordered FROM warehouse AS w LEFT JOIN (SELECT ol.Despatch_From_ID, COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE ol.Despatch_ID=0 AND o.Status LIKE \'Pending\' AND o.Is_Declined=\'N\' AND o.Is_Warehouse_Declined=\'N\' GROUP BY ol.Despatch_From_ID) AS ol1 ON ol1.Despatch_From_ID=w.Warehouse_ID LEFT JOIN (SELECT ol.Despatch_From_ID, COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE ol.Despatch_ID=0 AND o.Status LIKE \'Purchasing\' AND o.Is_Declined=\'N\' AND o.Is_Warehouse_Declined=\'N\' GROUP BY ol.Despatch_From_ID) AS ol2 ON ol2.Despatch_From_ID=w.Warehouse_ID LEFT JOIN (SELECT ol.Despatch_From_ID, COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE ol.Despatch_ID=0 AND o.Status LIKE \'Packing\' AND o.Is_Declined=\'N\' AND o.Is_Warehouse_Declined=\'N\' GROUP BY ol.Despatch_From_ID) AS ol3 ON ol3.Despatch_From_ID=w.Warehouse_ID LEFT JOIN (SELECT ol.Despatch_From_ID, COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE ol.Despatch_ID=0 AND o.Status LIKE \'Partially Despatched\' AND o.Is_Declined=\'N\' AND o.Is_Warehouse_Declined=\'N\' GROUP BY ol.Despatch_From_ID) AS ol4 ON ol4.Despatch_From_ID=w.Warehouse_ID LEFT JOIN (SELECT ol.Despatch_From_ID, COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE ol.Despatch_ID=0 AND ol.Line_Status LIKE \'Backordered\' AND o.Is_Declined=\'N\' AND o.Is_Warehouse_Declined=\'N\' GROUP BY ol.Despatch_From_ID) AS ol5 ON ol5.Despatch_From_ID=w.Warehouse_ID WHERE w.Type=\'S\' GROUP BY w.Warehouse_ID'));
while($data->Row) {
	$item = array();
	$item['Count_Pending'] = array('Type' => 'Pending', 'Orders_Before' => $data->Row['Count_Pending']);
	$item['Count_Purchasing'] = array('Type' => 'Purchasing', 'Orders_Before' => $data->Row['Count_Purchasing']);
	$item['Count_Packing'] = array('Type' => 'Packing', 'Orders_Before' => $data->Row['Count_Packing']);
	$item['Count_Partially_Despatched'] = array('Type' => 'Partially Despatched', 'Orders_Before' => $data->Row['Count_Partially_Despatched']);
	$item['Count_Backordered'] = array('Type' => 'Backordered', 'Orders_Before' => $data->Row['Count_Backordered']);
	
	$suppliers[$data->Row['Warehouse_ID']] = $item;
	
	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf('SELECT w.Warehouse_ID, w.Warehouse_Name, SUM(ol1.Count) AS Count_Pending, SUM(ol2.Count) AS Count_Purchasing, SUM(ol3.Count) AS Count_Packing, SUM(ol4.Count) AS Count_Partially_Despatched, SUM(ol5.Count) AS Count_Backordered FROM warehouse AS w LEFT JOIN (SELECT ol.Despatch_From_ID, COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID AND ol.Product_ID NOT IN (%1$s) WHERE ol.Despatch_ID=0 AND o.Status LIKE \'Pending\' AND o.Is_Declined=\'N\' AND o.Is_Warehouse_Declined=\'N\' GROUP BY ol.Despatch_From_ID) AS ol1 ON ol1.Despatch_From_ID=w.Warehouse_ID LEFT JOIN (SELECT ol.Despatch_From_ID, COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID AND ol.Product_ID NOT IN (%1$s) WHERE ol.Despatch_ID=0 AND o.Status LIKE \'Purchasing\' AND o.Is_Declined=\'N\' AND o.Is_Warehouse_Declined=\'N\' GROUP BY ol.Despatch_From_ID) AS ol2 ON ol2.Despatch_From_ID=w.Warehouse_ID LEFT JOIN (SELECT ol.Despatch_From_ID, COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID AND ol.Product_ID NOT IN (%1$s) WHERE ol.Despatch_ID=0 AND o.Status LIKE \'Packing\' AND o.Is_Declined=\'N\' AND o.Is_Warehouse_Declined=\'N\' GROUP BY ol.Despatch_From_ID) AS ol3 ON ol3.Despatch_From_ID=w.Warehouse_ID LEFT JOIN (SELECT ol.Despatch_From_ID, COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID AND ol.Product_ID NOT IN (%1$s) WHERE ol.Despatch_ID=0 AND o.Status LIKE \'Partially Despatched\' AND o.Is_Declined=\'N\' AND o.Is_Warehouse_Declined=\'N\' GROUP BY ol.Despatch_From_ID) AS ol4 ON ol4.Despatch_From_ID=w.Warehouse_ID LEFT JOIN (SELECT ol.Despatch_From_ID, COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID AND ol.Product_ID NOT IN (%1$s) WHERE ol.Despatch_ID=0 AND ol.Line_Status LIKE \'Backordered\' AND o.Is_Declined=\'N\' AND o.Is_Warehouse_Declined=\'N\' GROUP BY ol.Despatch_From_ID) AS ol5 ON ol5.Despatch_From_ID=w.Warehouse_ID WHERE w.Type=\'S\' GROUP BY w.Warehouse_ID', implode(', ', $products)));
while($data->Row) {
	$item = $suppliers[$data->Row['Warehouse_ID']];
	$item['Count_Pending']['Orders_After'] = $data->Row['Count_Pending'];
	$item['Count_Purchasing']['Orders_After'] = $data->Row['Count_Purchasing'];
	$item['Count_Packing']['Orders_After'] = $data->Row['Count_Packing'];
	$item['Count_Partially_Despatched']['Orders_After'] = $data->Row['Count_Partially_Despatched'];
	$item['Count_Backordered']['Orders_After'] = $data->Row['Count_Backordered'];
	
	$suppliers[$data->Row['Warehouse_ID']] = $item;
	
	$data->Next();
}
$data->Disconnect();

foreach($suppliers as $supplierId=>$supplierData) {
	$type['Count_Pending']['Orders_After'] += $supplierData['Count_Pending']['Orders_Before'] - $supplierData['Count_Pending']['Orders_After'];
	$type['Count_Purchasing']['Orders_After'] += $supplierData['Count_Purchasing']['Orders_Before'] - $supplierData['Count_Purchasing']['Orders_After'];
	$type['Count_Packing']['Orders_After'] += $supplierData['Count_Packing']['Orders_Before'] - $supplierData['Count_Packing']['Orders_After'];
	$type['Count_Partially_Despatched']['Orders_After'] += $supplierData['Count_Partially_Despatched']['Orders_Before'] - $supplierData['Count_Partially_Despatched']['Orders_After'];
	$type['Count_Backordered']['Orders_After'] += $supplierData['Count_Backordered']['Orders_Before'] - $supplierData['Count_Backordered']['Orders_After'];
}

$type['Count_Partially_Despatched']['Orders_After'] = 0;
$type['Count_Backordered']['Orders_After'] = 0;

$page = new Page('Analysis / Orders Supplied', 'Analysing orders supplied differences for when products within a range of the top product positions are stocked.');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Analysis parameters');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');

echo $window->Open();
echo $window->AddHeader('Configure your analysis parameters here.');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow('Top Products', $form->GetHTML('topfrom') . ' - ' . $form->GetHTML('topto'));
echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();
?>

<br />
<h3>Supplier Orders</h3>
<p>Orders shipping by suppliers before and after we assume the top products within the range <strong><?php echo sprintf('%s - %s', $form->GetValue('topfrom'), $form->GetValue('topto')); ?></strong> are stocked.</p>

<table width="100%" border="0">
	<tr>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Warehouse</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" width="10%" align="right"><strong>Before Orders</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" width="10%" align="right"><strong>After Orders</strong></td>
	</tr>

	<?php
	$totalBefore = 0;
	$totalAfter = 0;
	
	foreach($orders as $orderData) {
		$totalBefore += $orderData['Orders_Before'];
		$totalAfter += $orderData['Orders_After'];
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td><?php echo $orderData['Warehouse_Name']; ?></td>
			<td align="right"><?php echo $orderData['Orders_Before']; ?></td>
			<td align="right"><?php echo $orderData['Orders_After']; ?></td>
		</tr>

		<?php
	}
	?>
	
	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td></td>
		<td align="right"><strong><?php echo $totalBefore; ?></strong></td>
		<td align="right"><strong><?php echo $totalAfter; ?></strong></td>
	</tr>
</table>
<br />

<br />
<h3>Branch Orders</h3>
<p>Order types shipping by internal branch before and after we assume the top products within the range <strong><?php echo sprintf('%s - %s', $form->GetValue('topfrom'), $form->GetValue('topto')); ?></strong> are stocked.</p>

<table width="100%" border="0">
	<tr>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Order Type</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" width="10%" align="right"><strong>Before Orders</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" width="10%" align="right"><strong>After Orders</strong></td>
	</tr>

	<?php
	$totalBefore = 0;
	$totalAfter = 0;
	
	foreach($type as $typeData) {
		$totalBefore += $typeData['Orders_Before'];
		$totalAfter += $typeData['Orders_After'];
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td><?php echo $typeData['Type']; ?></td>
			<td align="right"><?php echo $typeData['Orders_Before']; ?></td>
			<td align="right"><?php echo $typeData['Orders_After']; ?></td>
		</tr>

		<?php
	}
	?>
	
	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td></td>
		<td align="right"><strong><?php echo $totalBefore; ?></strong></td>
		<td align="right"><strong><?php echo $totalAfter; ?></strong></td>
	</tr>
</table>

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');