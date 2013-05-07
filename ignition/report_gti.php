<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
report($start, $end);
exit();

function report($start, $end){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$page = new Page('GTI Sales Report', '');
	$page->Display('header');
	
	$products = array();
	$dates = array();
	
	$startDate = '2006-01-01 00:00:00';
	
	while(strtotime($startDate) < time()) {
		$item['Start'] = $startDate;
		$item['End'] = date('Y-m-d 00:00:00', mktime(0, 0, 0, date('m', strtotime($startDate))+1, date('d', strtotime($startDate)), date('Y', strtotime($startDate))));
		
		$dates[] = $item;
				
		$startDate = $item['End'];
	}
	
	$data = new DataQuery(sprintf("SELECT Product_ID FROM supplier_product WHERE Supplier_ID=26 AND Cost>0 ORDER BY Product_ID ASC"));
	while($data->Row) {
		foreach($dates as $date) {
			$item = array();
				
			$data2 = new DataQuery(sprintf("SELECT COUNT(DISTINCT o.Order_ID) AS Orders, SUM(ol.Quantity) AS Quantity FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID AND ol.Despatch_From_ID=1 AND ol.Product_ID=%d WHERE o.Created_On BETWEEN '%s' AND '%s'", $data->Row['Product_ID'], $date['Start'], $date['End']));
			
			$item['Orders'] = $data2->Row['Orders'];
			$item['Quantity'] = $data2->Row['Quantity'];
			if($item['Quantity'] > 0){
				$item['Average'] = $item['Quantity'] / $item['Orders'];
			}else{
				$item['Average'] = 0;
			}
			
			$data2->Disconnect();
			
			$products[$data->Row['Product_ID']][strtotime($date['Start']) . ':' . strtotime($date['End'])] = $item;
		}		
		
		$data->Next();
	}
	$data->Disconnect();
	
	foreach($products as $productId=>$dates) {
		$data = new DataQuery(sprintf("SELECT Product_ID, Product_Title FROM order_line WHERE Product_ID=%d", $productId)); 
		?>
		
		<br />
		<h3><?php echo strip_tags($data->Row['Product_Title']); ?> (<?php echo $data->Row['Product_ID']; ?>)</h3>
		<p></p>
		
		<table width="100%" border="0" >
			<tr>
				<td style="border-bottom:1px solid #aaaaaa"><strong>Date Range</strong></td>
				<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Orders</strong></td>
				<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Quantity Sold</strong></td>
				<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Average Sold per Order</strong></td>
			</tr>
			
			<?php
			foreach($dates as $dateRanges=>$figures) {
				$ranges = explode(':', $dateRanges);
				?>
			
				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo cDatetime(date('Y-m-d 00:00:00', $ranges[0]), 'shortdate'); ?> - <?php echo cDatetime(date('Y-m-d 00:00:00', $ranges[1]), 'shortdate'); ?></td>
					<td align="right"><?php echo number_format($figures['Orders'], 0, '.', ''); ?></td>
					<td align="right"><?php echo number_format($figures['Quantity'], 0, '.', ''); ?></td>
					<td align="right"><?php echo number_format($figures['Average'], 2, '.', ''); ?></td>
				</tr>
				
				<?php
			}
			?>
			
		</table>
		<br />
		
		<?php
		$data->Disconnect();
	}
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>