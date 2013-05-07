<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

$lines = array();
$warehouses = array();

$data = new DataQuery(sprintf("SELECT ol.*, ol.Price * ol.Quantity AS Line_Price FROM order_line AS ol INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Status NOT IN ('Despatched', 'Cancelled') WHERE ol.Line_Status LIKE 'Backordered' AND ol.Product_ID>0 AND ol.Despatch_ID=0 ORDER BY w.Warehouse_Name ASC, ol.Order_Line_ID ASC"));
while($data->Row) {
	$lines[] = $data->Row;
	
	$data->Next();
}
$data->Disconnect();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

for($i=0; $i<count($lines); $i++) {
	$form->AddField('despatch_from_' . $lines[$i]['Order_Line_ID'], 'Despatch From', 'select', $lines[$i]['Despatch_From_ID'], 'numeric_unsigned', 1, 11);
	
	$data = new DataQuery("SELECT * FROM warehouse ORDER BY Warehouse_Name ASC");
	while($data->Row) {
		if($data->Row['Type'] == 'B') {
			$data2 = new DataQuery(sprintf("SELECT SUM(Quantity_In_Stock) AS Quantity FROM warehouse_stock WHERE Product_ID=%d AND Warehouse_ID=%d", mysql_real_escape_string($lines[$i]['Product_ID']), $data->Row['Warehouse_ID']));
			if($data2->TotalRows > 0) {
				$form->AddOption('despatch_from_' . $lines[$i]['Order_Line_ID'], $data->Row['Warehouse_ID'], sprintf('%s (%d)', $data->Row['Warehouse_Name'], $data2->Row['Quantity']));
			} else {
				$form->AddOption('despatch_from_' . $lines[$i]['Order_Line_ID'], $data->Row['Warehouse_ID'], $data->Row['Warehouse_Name']);
			}
			$data2->Disconnect();			
		} else {
			$data2 = new DataQuery(sprintf("SELECT Cost, Modified_On FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", $data->Row['Type_Reference_ID'], mysql_real_escape_string($lines[$i]['Product_ID'])));
			if($data2->TotalRows > 0) {
				$form->AddOption('despatch_from_' . $lines[$i]['Order_Line_ID'], $data->Row['Warehouse_ID'], sprintf('%s (&pound;%s) [%s]', $data->Row['Warehouse_Name'], $data2->Row['Cost'], cDatetime($data2->Row['Modified_On'], 'shortdate')));
			} else {
				$form->AddOption('despatch_from_' . $lines[$i]['Order_Line_ID'], $data->Row['Warehouse_ID'], $data->Row['Warehouse_Name']);
			}
			$data2->Disconnect();
		}

		$data->Next();
	}
	$data->Disconnect();		
}

if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm'] == 'true'))) {
	if($form->Validate()) {
		for($i=0; $i<count($lines); $i++) {
			new DataQuery(sprintf("UPDATE order_line SET Despatch_From_ID=%d WHERE Order_Line_ID=%d", $form->GetValue('despatch_from_' . $lines[$i]['Order_Line_ID']), mysql_real_escape_string($lines[$i]['Order_Line_ID'])));
		}

		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}
}

$page = new Page('Orders Backordered', 'All backorders for all suppliers within the selected period.');
$page->Display('header');

echo $form->Open();
echo $form->GetHTML('confirm');
?>

<table cellspacing="0" class="orderDetails">
	<tr>
		<th nowrap="nowrap" style="padding-right: 5px;">Qty</th>
		<th nowrap="nowrap" style="padding-right: 5px;">Order</th>
		<th nowrap="nowrap" style="padding-right: 5px;">Product</th>
		<th nowrap="nowrap" style="padding-right: 5px;">Despatch From</th>	
		<th nowrap="nowrap" style="padding-right: 5px; text-align: center;">Quickfind</th>
		<th nowrap="nowrap" style="padding-right: 5px; text-align: right;"> </th>
		<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Line Price</th>
		<th nowrap="nowrap" style="padding-right: 5px;">Backorder</th>
	</tr>
	
	<?php
	for($i=0; $i<count($lines); $i++) {
		?>
		
		<tr>
			<td nowrap="nowrap"><?php echo $lines[$i]['Quantity']; ?></td>
			<td nowrap="nowrap"><a href="order_details.php?orderid=<?php echo $lines[$i]['Order_ID']; ?>" target="_blank"><?php echo $lines[$i]['Order_ID']; ?></a></td>
			<td nowrap="nowrap"><?php echo $lines[$i]['Product_Title']; ?></td>
			<td nowrap="nowrap"><?php echo $form->GetHTML('despatch_from_' . $lines[$i]['Order_Line_ID']); ?></td>
			<td align="center" nowrap="nowrap"><a href="product_profile.php?pid=<?php print $lines[$i]['Product_ID']; ?>" target="_blank"><?php echo $lines[$i]['Product_ID']; ?></a></td>
			<td nowrap="nowrap" align="right">&pound;<?php echo number_format($lines[$i]['Price'], 2, '.', ','); ?></td>
			<td nowrap="nowrap" align="right">&pound;<?php echo number_format($lines[$i]['Line_Price'], 2, '.', ','); ?></td>
			<td nowrap="nowrap"><?php echo cDatetime($lines[$i]['Backorder_Expected_On'], 'shortdate'); ?>&nbsp;</td>
		</tr>
		
		<?php
	}
	?>
	
</table><br />

<input type="submit" value="update" name="update" class="btn" />

<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');