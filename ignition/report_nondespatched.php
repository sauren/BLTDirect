<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$session->Secure(2);
report();
exit();

function report() {
	$warehouses = array();
	
	$data = new DataQuery(sprintf("SELECT w.Warehouse_ID, w.Warehouse_Name FROM supplier AS s INNER JOIN warehouse AS w ON w.Type_Reference_ID=s.Supplier_ID AND w.Type='S' INNER JOIN order_line AS ol ON ol.Despatch_From_ID=w.Warehouse_ID AND ol.Line_Status LIKE '' GROUP BY w.Warehouse_ID"));
	while($data->Row) {
		$warehouses[] = array(
			'WarehouseID' => $data->Row['Warehouse_ID'],
			'WarehouseName' => $data->Row['Warehouse_Name'],
			'OrderLines' => array());
		
		$data->Next();
	}
	$data->Disconnect();
	
	for($i=0; $i<count($warehouses); $i++) {
		$data = new DataQuery(sprintf("SELECT Order_ID, Product_ID, Product_Title, Quantity, (Line_Total - Line_Discount) AS Price, (Cost * Quantity) AS Cost FROM order_line WHERE Despatch_From_ID=%d AND Line_Status LIKE '' ORDER BY Order_ID ASC, Product_ID ASC", mysql_real_escape_string($warehouses[$i]['WarehouseID'])));
		while($data->Row) {
			$warehouses[$i]['OrderLines'][] = array(
				'OrderID' => $data->Row['Order_ID'],
				'ProductID' => $data->Row['Product_ID'],
				'ProductTitle' => strip_tags($data->Row['Product_Title']),
				'Quantity' => $data->Row['Quantity'],
				'Price' => $data->Row['Price'],
				'Cost' => $data->Row['Cost']);			
			
			$data->Next();
		}
		$data->Disconnect();		
	}
	
	$page = new Page('Non Despatched Report', '');
	$page->Display('header');
	
	foreach($warehouses as $warehouse) {
		?>

		<br /> 
		<h3><?php echo $warehouse['WarehouseName']; ?></h3>
		<p><?php echo count($warehouse['OrderLines']); ?> non despatched order lines for this supplier.</p>
		
		<table width="100%" border="0">
			<tr>
				<td width="10%" style="border-bottom:1px solid #aaaaaa;"><strong>Order ID</strong></td>
				<td width="10%" style="border-bottom:1px solid #aaaaaa;"><strong>Product ID</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;"><strong>Product Title</strong></td>
				<td width="10%" align="right" style="border-bottom:1px solid #aaaaaa;"><strong>Quantity</strong></td>
				<td width="10%" align="right" style="border-bottom:1px solid #aaaaaa;"><strong>Cost</strong></td>
				<td width="10%" align="right" style="border-bottom:1px solid #aaaaaa;"><strong>Price</strong></td>				
			</tr>
			
			<?php
			foreach($warehouse['OrderLines'] as $line) {
				?>
				
				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><a href="order_details.php?orderid=<?php echo $line['OrderID']; ?>"><?php echo $line['OrderID']; ?></a></td>
					<td><a href="product_profile.php?pid=<?php echo $line['ProductID']; ?>"><?php echo $line['ProductID']; ?></a></td>
					<td><?php echo $line['ProductTitle']; ?></td>
					<td align="right"><?php echo $line['Quantity']; ?></td>
					<td align="right">&pound;<?php echo number_format($line['Price'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($line['Cost'], 2, '.', ','); ?></td>
				</tr>
				
				<?php
			}
			?>
		</table>
		
		<?php
	}

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>