<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$months = array(1, 2, 3);

$page = new Page('Stock Supply Report');
$page->Display('header');
?>

<h3>Months Supply</h3>

<table width="100%" border="0">
	<tr>
		<td style="border-bottom:1px solid #aaaaaa;"></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Stocked</strong><br />Best Cost</td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Stocked</strong><br />Recent Cost</td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Stocked Temporarily</strong><br />Best Cost</td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Stocked Temporarily</strong><br />Recent Cost</td>
	</tr>
	  
	<?php
	foreach($months as $month) {
		$data = new DataQuery(sprintf("SELECT SUM(ol.Quantity*p.CacheBestCost) AS BestCost, SUM(ol.Quantity*p.CacheRecentCost) AS RecentCost FROM order_line AS ol INNER JOIN product AS p ON p.Product_ID=ol.Product_ID AND p.Is_Stocked='Y' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0", mysql_real_escape_string($month)));
		$stockedCostBest = $data->Row['BestCost'];
		$stockedCostRecent = $data->Row['RecentCost'];
		$data->Disconnect();
		
		$data = new DataQuery(sprintf("SELECT SUM(ol.Quantity*p.CacheBestCost) AS BestCost, SUM(ol.Quantity*p.CacheRecentCost) AS RecentCost FROM order_line AS ol INNER JOIN product AS p ON p.Product_ID=ol.Product_ID AND p.Is_Stocked_Temporarily='Y' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0", mysql_real_escape_string($month)));
		$tempCostBest = $data->Row['BestCost'];
		$tempCostRecent = $data->Row['RecentCost'];
		$data->Disconnect();
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td><?php echo $month; ?></td>
			<td align="right">&pound;<?php echo number_format($stockedCostBest, 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($stockedCostRecent, 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($tempCostBest, 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($tempCostRecent, 2, '.', ','); ?></td>
		</tr>
			
		<?php
	}
	?>
	
</table>