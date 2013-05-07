<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/chart/libchart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseStock.php');

if($action == 'archive') {
	$session->Secure(3);
	archive();
	exit;
} else {
	$session->Secure(3);
	view();	
	exit;
}

function view() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('monthssales', 'Months Sales Quantity', 'select', '6', 'numeric_unsigned', 1, 11);
	$form->AddField('monthsignore', 'Months Ignore New Products', 'select', '6', 'numeric_unsigned', 1, 11);

	for($i=1; $i<=24; $i++) {
		$form->AddOption('monthssales', $i, $i);
		$form->AddOption('monthsignore', $i, $i);
	}

	$products = array();

	$data = new DataQuery(sprintf('SELECT p.Product_ID, p.Product_Title, p.SKU, SUM(ws.Quantity_In_Stock) AS Quantity_Stocked, SUM(ws.Cost*ws.Quantity_In_Stock) AS Cost_Value, SUM(p.CacheBestCost*ws.Quantity_In_Stock) AS Cost_Best, SUM(p.CacheRecentCost*ws.Quantity_In_Stock) AS Cost_Recent, ol.Quantity FROM product AS p INNER JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID AND ws.Is_Archived=\'N\' INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type=\'B\' LEFT JOIN (SELECT ol.Product_ID, SUM(ol.Line_Total-ol.Line_Discount) AS Value, SUM(ol.Quantity) AS Quantity FROM product AS p INNER JOIN order_line AS ol ON ol.Product_ID=p.Product_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On BETWEEN ADDDATE(NOW(), INTERVAL -%d MONTH) AND NOW() GROUP BY ol.Product_ID) AS ol ON ol.Product_ID=ws.Product_ID WHERE p.Product_Type<>\'G\' AND ws.Quantity_In_Stock>0 AND p.Created_On<ADDDATE(NOW(), INTERVAL -%d MONTH) GROUP BY ws.Product_ID UNION SELECT p2.Product_ID, p2.Product_Title, p2.SKU, SUM(ws.Quantity_In_Stock) AS Quantity_Stocked, SUM(ws.Cost*ws.Quantity_In_Stock) AS Cost_Value, SUM(p2.CacheBestCost*ws.Quantity_In_Stock) AS Cost_Best, SUM(p2.CacheRecentCost*ws.Quantity_In_Stock) AS Cost_Recent, ol.Quantity*pc.Component_Quantity AS Quantity FROM product AS p INNER JOIN product_components AS pc ON pc.Component_Of_Product_ID=p.Product_ID INNER JOIN product AS p2 ON p2.Product_ID=pc.Product_ID INNER JOIN warehouse_stock AS ws ON ws.Product_ID=p2.Product_ID AND ws.Is_Archived=\'N\' INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type=\'B\' LEFT JOIN (SELECT ol.Product_ID, SUM(ol.Line_Total-ol.Line_Discount) AS Value, SUM(ol.Quantity) AS Quantity FROM product AS p INNER JOIN order_line AS ol ON ol.Product_ID=p.Product_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On BETWEEN ADDDATE(NOW(), INTERVAL -%d MONTH) AND NOW() GROUP BY ol.Product_ID) AS ol ON ol.Product_ID=p.Product_ID WHERE p.Product_Type=\'G\' AND ws.Quantity_In_Stock>0 AND p2.Created_On<ADDDATE(NOW(), INTERVAL -%d MONTH) GROUP BY p2.Product_ID ORDER BY Product_ID ASC', mysql_real_escape_string($form->GetValue('monthssales')), mysql_real_escape_string($form->GetValue('monthsignore')), mysql_real_escape_string($form->GetValue('monthssales')), mysql_real_escape_string($form->GetValue('monthsignore'))));
	while($data->Row) {
		if(!isset($products[$data->Row['Product_ID']])) {
			$products[$data->Row['Product_ID']] = $data->Row;
		} else {
			$products[$data->Row['Product_ID']]['Quantity'] += $data->Row['Quantity'];
		}

		$data->Next();	
	}
	$data->Disconnect();

	$page = new Page('Analysis / Stock Overstocked Quantity', 'Analysing stock for products where the current stock cost exceeds the sale quantity over the given number of months.');
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
	echo $webForm->AddRow($form->GetLabel('monthssales'), $form->GetHTML('monthssales'));
	echo $webForm->AddRow($form->GetLabel('monthsignore'), $form->GetHTML('monthsignore'));
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	?>

	<br />
	<h3>Products Quantity Overstocked</h3>
	<p>Stock details for products sale quantity sold within the last <strong><?php echo $form->GetValue('monthssales'); ?></strong> where the quantity of current stock is greater.</p>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong><br />&nbsp;</td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>SKU</strong><br />&nbsp;</td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong><br />&nbsp;</td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Stocked</strong><br />&nbsp;</td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Sold</strong><br />&nbsp;</td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Registered Value</strong><br />&nbsp;</td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Best Cost</strong><br />&nbsp;</td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Recent Cost</strong><br />&nbsp;</td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Overstocked</strong><br />Current figures</td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Overstocked Value</strong><br />Current figures</td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Overstocked</strong><br />In <?php echo $form->GetValue('monthssales'); ?> months</td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Overstocked Value</strong><br />In <?php echo $form->GetValue('monthssales'); ?> months</td>
		</tr>

		<?php
		$totalStocked = 0;
		$totalSold = 0;
		$totalCostValue = 0;
		$totalCostBest = 0;
		$totalCostRecent = 0;
		$totalOverstocked = 0;
		$totalOverstockedValue = 0;
		$totalOverstockedFuture = 0;
		$totalOverstockedValueFuture = 0;
		
		foreach($products as $productData) {
			if($productData['Quantity_Stocked'] > $productData['Quantity']) {
				$cost = ($productData['Cost_Recent'] > 0) ? $productData['Cost_Recent'] : $productData['Cost_Best'];
				
				$totalStocked += $productData['Quantity_Stocked'];
				$totalSold += $productData['Quantity'];
				$totalCostValue += $productData['Cost_Value'];
				$totalCostBest += $productData['Cost_Best'];
				$totalCostRecent += $productData['Cost_Recent'];
				$totalOverstocked += $productData['Quantity_Stocked']-$productData['Quantity'];
				$totalOverstockedValue += ($cost/$productData['Quantity_Stocked'])*($productData['Quantity_Stocked']-$productData['Quantity']);
				
				$futureOverstock = $productData['Quantity_Stocked']-($productData['Quantity']*2);
				
				if($futureOverstock > 0) {
					$totalOverstockedFuture += $futureOverstock;
					$totalOverstockedValueFuture += ($cost/$productData['Quantity_Stocked'])*$futureOverstock;
				}
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $productData['Product_Title']; ?></td>
					<td><?php echo $productData['SKU']; ?></td>
					<td><a href="product_profile.php?pid=<?php echo $productData['Product_ID']; ?>"><?php echo $productData['Product_ID']; ?></a></td>
					<td align="right"><?php echo $productData['Quantity_Stocked']; ?></td>
					<td align="right"><?php echo $productData['Quantity']; ?></td>
					<td align="right">&pound;<?php echo number_format($productData['Cost_Value'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($productData['Cost_Best'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($productData['Cost_Recent'], 2, '.', ','); ?></td>
					<td align="right"><?php echo $productData['Quantity_Stocked']-$productData['Quantity']; ?></td>
					<td align="right">&pound;<?php echo number_format(($cost/$productData['Quantity_Stocked'])*($productData['Quantity_Stocked']-$productData['Quantity']), 2, '.', ','); ?></td>
					<td align="right"><?php echo ($futureOverstock > 0) ? $futureOverstock : ''; ?></td>
					<td align="right"><?php echo ($futureOverstock > 0) ? '&pound;' . number_format(($cost/$productData['Quantity_Stocked'])*$futureOverstock, 2, '.', ',') : ''; ?></td>
				</tr>

				<?php
			}
		}
		?>
		
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td></td>
			<td></td>
			<td></td>
			<td align="right"><strong><?php echo $totalStocked; ?></strong></td>
			<td align="right"><strong><?php echo $totalSold; ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalCostValue, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalCostBest, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalCostRecent, 2, '.', ','); ?></strong></td>
			<td align="right"><strong><?php echo $totalOverstocked; ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalOverstockedValue, 2, '.', ','); ?></strong></td>
			<td align="right"><strong><?php echo $totalOverstockedFuture; ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalOverstockedValueFuture, 2, '.', ','); ?></strong></td>
		</tr>
	</table>
	<br />

	<input type="button" value="archive" name="archive" class="btn" onclick="window.self.location.href = '?action=archive&monthssales=<?php echo $form->GetValue('monthssales'); ?>&monthsignore=<?php echo $form->GetValue('monthsignore'); ?>';" />

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function archive() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('monthssales', 'Months Sales Quantity', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('monthsignore', 'Months Ignore New Products', 'hidden', '0', 'numeric_unsigned', 1, 11);

	$products = array();

	$data = new DataQuery(sprintf('SELECT p.Product_ID, p.Product_Title, p.SKU, SUM(ws.Quantity_In_Stock) AS Quantity_Stocked, SUM(ws.Cost*ws.Quantity_In_Stock) AS Cost_Value, SUM(p.CacheBestCost*ws.Quantity_In_Stock) AS Cost_Best, SUM(p.CacheRecentCost*ws.Quantity_In_Stock) AS Cost_Recent, ol.Quantity FROM product AS p INNER JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID AND ws.Is_Archived=\'N\' INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type=\'B\' LEFT JOIN (SELECT ol.Product_ID, SUM(ol.Line_Total-ol.Line_Discount) AS Value, SUM(ol.Quantity) AS Quantity FROM product AS p INNER JOIN order_line AS ol ON ol.Product_ID=p.Product_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On BETWEEN ADDDATE(NOW(), INTERVAL -%d MONTH) AND NOW() GROUP BY ol.Product_ID) AS ol ON ol.Product_ID=ws.Product_ID WHERE p.Product_Type<>\'G\' AND ws.Quantity_In_Stock>0 AND p.Created_On<ADDDATE(NOW(), INTERVAL -%d MONTH) GROUP BY ws.Product_ID UNION SELECT p2.Product_ID, p2.Product_Title, p2.SKU, SUM(ws.Quantity_In_Stock) AS Quantity_Stocked, SUM(ws.Cost*ws.Quantity_In_Stock) AS Cost_Value, SUM(p2.CacheBestCost*ws.Quantity_In_Stock) AS Cost_Best, SUM(p2.CacheRecentCost*ws.Quantity_In_Stock) AS Cost_Recent, ol.Quantity*pc.Component_Quantity AS Quantity FROM product AS p INNER JOIN product_components AS pc ON pc.Component_Of_Product_ID=p.Product_ID INNER JOIN product AS p2 ON p2.Product_ID=pc.Product_ID INNER JOIN warehouse_stock AS ws ON ws.Product_ID=p2.Product_ID AND ws.Is_Archived=\'N\' INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type=\'B\' LEFT JOIN (SELECT ol.Product_ID, SUM(ol.Line_Total-ol.Line_Discount) AS Value, SUM(ol.Quantity) AS Quantity FROM product AS p INNER JOIN order_line AS ol ON ol.Product_ID=p.Product_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On BETWEEN ADDDATE(NOW(), INTERVAL -%d MONTH) AND NOW() GROUP BY ol.Product_ID) AS ol ON ol.Product_ID=p.Product_ID WHERE p.Product_Type=\'G\' AND ws.Quantity_In_Stock>0 AND p2.Created_On<ADDDATE(NOW(), INTERVAL -%d MONTH) GROUP BY p2.Product_ID ORDER BY Product_ID ASC', mysql_real_escape_string($form->GetValue('monthssales')), mysql_real_escape_string($form->GetValue('monthsignore')), mysql_real_escape_string($form->GetValue('monthssales')), mysql_real_escape_string($form->GetValue('monthsignore'))));
	while($data->Row) {
		if(!isset($products[$data->Row['Product_ID']])) {
			$products[$data->Row['Product_ID']] = $data->Row;
		} else {
			$products[$data->Row['Product_ID']]['Quantity'] += $data->Row['Quantity'];
		}

		$data->Next();	
	}
	$data->Disconnect();
	
	foreach($products as $productData) {
		if($productData['Quantity_Stocked'] > $productData['Quantity']) {
			$stockRemaining = $productData['Quantity_Stocked']-($productData['Quantity']*2);
			
			if($stockRemaining > 0) {
				$data = new DataQuery(sprintf("SELECT ws.Stock_ID, ws.Warehouse_ID, ws.Shelf_Location, ws.Quantity_In_Stock, ws.Cost FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B' WHERE ws.Product_ID=%d AND ws.Is_Archived='N' ORDER BY Stock_ID ASC", mysql_real_escape_string($productData['Product_ID'])));
				while($data->Row) {
					if($stockRemaining > 0) {
						$stock = new WarehouseStock($data->Row['Stock_ID']);
						
						if($data->Row['Quantity_In_Stock'] <= $stockRemaining) {
							$stock->IsArchived = 'Y';
							$stock->Update();
						} else {
							$stock->QuantityInStock -= $stockRemaining;
							$stock->Update();
							
							$archived = new WarehouseStock($data->Row['Stock_ID']);
							$archived->QuantityInStock = $stockRemaining;
							$archived->IsArchived = 'Y';
							$archived->Add();
						}
						
						$stockRemaining -= $data->Row['Quantity_In_Stock'];
					}
					
					$data->Next();	
				}
				$data->Disconnect();
			}
		}
	}
	
	redirectTo(sprintf('?action=view&monthssales=%s&monthsignore=%s', $form->GetValue('monthssales'), $form->GetValue('monthsignore')));
}