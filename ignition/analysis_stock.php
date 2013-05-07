<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/chart/libchart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('months', 'Months Backtrack', 'select', '1', 'numeric_unsigned', 1, 11);

for($i=1; $i<=12; $i++) {
	$form->AddOption('months', $i, $i);
}

$form->AddField('topfrom', 'Top Products (From)', 'text', '1500', 'numeric_unsigned', 1, 11, true, 'size="2"');
$form->AddField('topto', 'Top Products (To)', 'text', '10000', 'numeric_unsigned', 1, 11, true, 'size="2"');

$form2 = new Form($_SERVER['PHP_SELF']);
$form2->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form2->AddField('months', 'Months Backtrack', 'hidden', $form->GetValue('months'), 'numeric_unsigned', 1, 11);
$form2->AddField('topfrom', 'Top Products (From)', 'hidden', $form->GetValue('topfrom'), 'numeric_unsigned', 1, 11);
$form2->AddField('topto', 'Top Products (To)', 'hidden', $form->GetValue('topto'), 'numeric_unsigned', 1, 11);

if(isset($_REQUEST['confirm'])) {
	if(isset($_REQUEST['remove'])) {
		foreach($_REQUEST as $key=>$value) {
			if(preg_match('/^stock_([0-9]+)$/', $key, $matches)) {
				new DataQuery(sprintf("DELETE FROM warehouse_stock WHERE Stock_ID=%d", mysql_real_escape_string($matches[1])));
			}
		}
		
		redirectTo(sprintf('?months=%d&topfrom=%d&topto=%d', $form2->GetValue('months'), $form2->GetValue('topfrom'), $form2->GetValue('topto')));	
	}
}

$products = array();
$uncommon = array();
$locations = array();

$data = new DataQuery(sprintf('SELECT p.Product_ID, p.Product_Title, p.SKU, p.Position_Orders_Recent, SUM(ws.Quantity_In_Stock) AS Quantity_Stocked, SUM(ws.Cost*ws.Quantity_In_Stock) AS Cost, ol.Quantity AS Quantity_Sold FROM product AS p INNER JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type=\'B\' AND ws.Quantity_In_Stock>0 LEFT JOIN (SELECT ol.Product_ID, SUM(ol.Quantity) AS Quantity FROM order_line AS ol INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Status NOT IN (\'Cancelled\', \'Incomplete\', \'Unauthenticated\') AND o.Created_On>ADDDATE(NOW(), INTERVAL -%d MONTH) GROUP BY ol.Product_ID) AS ol ON ol.Product_ID=p.Product_ID WHERE p.Position_Orders_Recent BETWEEN %d AND %d GROUP BY p.Product_ID ORDER BY p.Product_ID ASC',  mysql_real_escape_string($form->GetValue('months')), mysql_real_escape_string($form->GetValue('topfrom')), mysql_real_escape_string($form->GetValue('topto'))));

while($data->Row) {
	$products[] = $data->Row;

	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf('SELECT p.Product_ID, p.Product_Title, p.SKU, p.Position_Orders_Recent, SUM(ws.Quantity_In_Stock) AS Quantity_Stocked, SUM(ws.Cost*ws.Quantity_In_Stock) AS Cost FROM product AS p INNER JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type=\'B\' AND ws.Quantity_In_Stock>0 WHERE p.Position_Orders_Recent=0 GROUP BY p.Product_ID ORDER BY p.Product_ID ASC'));
while($data->Row) {
	$uncommon[] = $data->Row;

	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf('SELECT p.Product_ID, ws.Stock_ID, ws.Shelf_Location, ws.Quantity_In_Stock, ws.Is_Writtenoff FROM product AS p INNER JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID AND ws.Quantity_In_Stock>0 AND ws.Shelf_Location<>\'\' INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type=\'B\''));
while($data->Row) {
	if(!isset($locations[$data->Row['Product_ID']])) {
		$locations[$data->Row['Product_ID']] = array();	
	}
	
	$locations[$data->Row['Product_ID']][] = $data->Row;

	$data->Next();
}
$data->Disconnect();

$script = sprintf('<script language="javascript" type="text/javascript">
	var toggleLocations = function(id) {
		var element = document.getElementById(\'locations-\' + id);
		var image = document.getElementById(\'image-\' + id);
		
		if(element) {
			if(element.style.display == \'table-row\') {
				element.style.display = \'none\';
				image.src = \'images/button-plus.gif\';
			} else {
				element.style.display = \'table-row\';
				image.src = \'images/button-minus.gif\';
			}
		}
	}
	</script>');

$page = new Page('Analysis / Stock', 'Analysing stock for products within a range of the top product positions.');
$page->AddToHead($script);
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
echo $webForm->AddRow($form->GetLabel('months'), $form->GetHTML('months'));
echo $webForm->AddRow('Top Products', $form->GetHTML('topfrom') . ' - ' . $form->GetHTML('topto'));
echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

echo $form2->Open();
echo $form2->GetHTML('confirm');
echo $form2->GetHTML('months');
echo $form2->GetHTML('topfrom');
echo $form2->GetHTML('topto');
?>

<br />
<h3>Products Stocked</h3>
<p>Stock details for the top products within the range <strong><?php echo sprintf('%s - %s', $form->GetValue('topfrom'), $form->GetValue('topto')); ?></strong>.</p>

<table width="100%" border="0">
	<tr>
		<td style="border-bottom:1px solid #aaaaaa;"></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Position</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>SKU</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Location</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Stocked</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Cost</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Sold</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Months Supply</strong></td>
	</tr>

	<?php
	$totalStocked = 0;
	$totalCost = 0;
	$totalSold = 0;
	
	foreach($products as $productData) {
		$totalStocked += $productData['Quantity_Stocked'];
		$totalCost += $productData['Cost'];
		$totalSold += $productData['Quantity_Sold'];
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td width="1%"><a href="javascript:toggleLocations(<?php echo $productData['Product_ID']; ?>);"><img src="images/button-plus.gif" alt="Toggle Locations" id="image-<?php echo $productData['Product_ID']; ?>" /></a></td>
			<td><?php echo $productData['Position_Orders_Recent']; ?></td>
			<td><?php echo $productData['Product_Title']; ?></td>
			<td><?php echo $productData['SKU']; ?></td>
			<td><a href="product_profile.php?pid=<?php echo $productData['Product_ID']; ?>"><?php echo $productData['Product_ID']; ?></a></td>
			<td>
				<?php
				if(isset($locations[$productData['Product_ID']])) {
					if(count($locations[$productData['Product_ID']]) == 1) {
						echo $locations[$productData['Product_ID']][0]['Shelf_Location'];
					} else {
						echo '<em>Multiple</em>';	
					}
				}
				?>
			</td>
			<td align="right"><?php echo $productData['Quantity_Stocked']; ?></td>
			<td align="right">&pound;<?php echo number_format($productData['Cost'], 2, '.', ','); ?></td>
			<td align="right"><?php echo $productData['Quantity_Sold']; ?></td>
			<td align="right"><?php echo !empty($productData['Quantity_Sold']) ? floor($productData['Quantity_Stocked'] / ($productData['Quantity_Sold'] / $form->GetValue('months'))) : ''; ?></td>
		</tr>
		<tr style="display: none;" id="locations-<?php echo $productData['Product_ID']; ?>">
			<td></td>
			<td colspan="9">
			
				<table width="100%" border="0">
					<tr>
						<td style="border-bottom:1px solid #aaaaaa;" width="1%"></td>
						<td style="border-bottom:1px solid #aaaaaa;" width="33%"><strong>Written Off</strong></td>
						<td style="border-bottom:1px solid #aaaaaa;" width="33%"><strong>Position</strong></td>
						<td style="border-bottom:1px solid #aaaaaa;" width="33%" align="right"><strong>Quantity</strong></td>
					</tr>
					
					<?php
					if(isset($locations[$productData['Product_ID']])) {
						foreach($locations[$productData['Product_ID']] as $location) {
							?>
							
							<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
								<?php if($location['Is_Writtenoff'] == 'Y'){ ?>
									<td><input type="checkbox" name="stock_<?php echo $location['Stock_ID']; ?>" value="Y" disabled="disabled" /></td>
								<?php } else { ?>
									<td><input type="checkbox" name="stock_<?php echo $location['Stock_ID']; ?>" value="Y" /></td>
								<?php } ?>
								<td><?php echo $location['Is_Writtenoff']; ?></td>
								<td><?php echo $location['Shelf_Location']; ?></td>
								<td align="right"><?php echo $location['Quantity_In_Stock']; ?></td>
							</tr>
			
							<?php
						}
					}
					?>
					
				</table>
				<br />
			
			</td>
		</tr>

		<?php
	}
	?>
	
	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td align="right"><strong><?php echo number_format($totalStocked, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
		<td align="right"><strong><?php echo number_format($totalSold, 2, '.', ','); ?></strong></td>
		<td></td>
	</tr>
</table>
<br />

<input type="submit" name="remove" value="remove" class="btn" />

<br /><br />
<h3>Uncommon Products Stocked</h3>
<p>Stock details for products not sold within the recent period.</p>

<table width="100%" border="0">
	<tr>
		<td style="border-bottom:1px solid #aaaaaa;"></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>SKU</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Location</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Stocked</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Cost</strong></td>
	</tr>

	<?php
	$totalStocked = 0;
	$totalCost = 0;
	$totalSold = 0;
	
	foreach($uncommon as $productData) {
		$totalStocked += $productData['Quantity_Stocked'];
		$totalCost += $productData['Cost'];
		$totalSold += $productData['Quantity_Sold'];
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td width="1%"><a href="javascript:toggleLocations(<?php echo $productData['Product_ID']; ?>);"><img src="images/button-plus.gif" alt="Toggle Locations" id="image-<?php echo $productData['Product_ID']; ?>" /></a></td>
			<td><?php echo $productData['Product_Title']; ?></td>
			<td><?php echo $productData['SKU']; ?></td>
			<td><a href="product_profile.php?pid=<?php echo $productData['Product_ID']; ?>"><?php echo $productData['Product_ID']; ?></a></td>
			<td>
				<?php
				if(isset($locations[$productData['Product_ID']])) {
					if(count($locations[$productData['Product_ID']]) == 1) {
						echo $locations[$productData['Product_ID']][0]['Shelf_Location'];
					} else {
						echo '<em>Multiple</em>';	
					}
				}
				?>
			</td>
			<td align="right"><?php echo $productData['Quantity_Stocked']; ?></td>
			<td align="right">&pound;<?php echo number_format($productData['Cost'], 2, '.', ','); ?></td>
		</tr>
		<tr style="display: none;" id="locations-<?php echo $productData['Product_ID']; ?>">
			<td></td>
			<td colspan="6">
			
				<table width="100%" border="0">
					<tr>
						<td style="border-bottom:1px solid #aaaaaa;" width="1%"></td>
						<td style="border-bottom:1px solid #aaaaaa;" width="49%"><strong>Position</strong></td>
						<td style="border-bottom:1px solid #aaaaaa;" width="50%" align="right"><strong>Quantity</strong></td>
					</tr>
					
					<?php
					if(isset($locations[$productData['Product_ID']])) {
						foreach($locations[$productData['Product_ID']] as $location) {
							?>
							
							<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
								<td><input type="checkbox" name="stock_<?php echo $location['Stock_ID']; ?>" value="Y" /></td>
								<td><?php echo $location['Shelf_Location']; ?></td>
								<td align="right"><?php echo $location['Quantity_In_Stock']; ?></td>
							</tr>
			
							<?php
						}
					}
					?>
					
				</table>
				<br />
			
			</td>
		</tr>

		<?php
	}
	?>

	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td align="right"><strong><?php echo number_format($totalStocked, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
	</tr>
</table>
<br />

<input type="submit" name="remove" value="remove" class="btn" />

<?php
echo $form2->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');