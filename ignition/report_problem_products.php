<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$page = new Page('Problem Product Report', '');
$page->Display('header');
	
new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_product SELECT ol.Product_ID, COUNT(DISTINCT o.Order_ID) AS Orders, (SUM(ol.Quantity) / 2) AS Optimum_Quantity FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE o.Created_On>=ADDDATE(NOW(), -60) AND o.Status LIKE 'Despatched' GROUP BY ol.Product_ID HAVING Orders>=10"));
new DataQuery(sprintf("ALTER TABLE temp_product ADD INDEX Product_ID (Product_ID)"));
?>

<h3>Products Overstocked</h3>
<p>Listing all products overstocked by at least 100% based on sales data for the past 60 days.</p>

<table width="100%" border="0">
	<tr>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Optimum Quantity</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Stocked Quantity</strong></td>
		<td style="border-bottom:1px solid #aaaaaa; text-align: right;"><strong>Percent Overstock</strong></td>
	</tr>
	
	<?php
	$data = new DataQuery(sprintf("SELECT p.Product_ID, p2.Product_Title, p.Optimum_Quantity, SUM(ws.Quantity_In_Stock) Total_Stocked, ((SUM(ws.Quantity_In_Stock) / p.Optimum_Quantity) * 100) AS Overstock_Percent FROM temp_product AS p INNER JOIN product AS p2 ON p.Product_ID=p2.Product_ID INNER JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type LIKE 'B' GROUP BY p.product_ID HAVING Optimum_Quantity<=(Total_Stocked / 2) ORDER BY Overstock_Percent DESC"));
	if($data->TotalRows > 0) {
		while($data->Row) {
			?>
		
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><?php echo $data->Row['Product_ID']; ?></td>
				<td><a href="product_profile.php?pid=<?php echo $data->Row['Product_ID']; ?>" target="_blank"><?php echo strip_tags($data->Row['Product_Title']); ?></a></td>
				<td><?php echo round($data->Row['Optimum_Quantity']); ?></td>
				<td><?php echo round($data->Row['Total_Stocked']); ?></td>
				<td align="right"><?php echo number_format($data->Row['Overstock_Percent'], 2, '.', ','); ?>%</td>
			</tr>
	
			<?php
			$data->Next();
		}
	} else {
		?>
		
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td colspan="2" align="center">There are no items available for viewing.</td>
		</tr>
	
		<?php
	}
	$data->Disconnect();
	?>
	
</table>

<?php
$page->Display('footer');