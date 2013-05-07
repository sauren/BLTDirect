<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/chart/libchart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('months', 'Months Not Sold', 'select', '3', 'numeric_unsigned', 1, 11);
$form->AddField('monthsbacktrack', 'Months Backtrack', 'select', '6', 'numeric_unsigned', 1, 11);
$form->AddField('monthssupply', 'Months Supply Required', 'select', '3', 'numeric_unsigned', 1, 11);

for($i=0; $i<=24; $i++) {
	$form->AddOption('months', $i, $i);
	$form->AddOption('monthsbacktrack', $i, $i);
	$form->AddOption('monthssupply', $i, $i);
}

$products = array();
$locations = array();
$costs = array();
$data = new DataQuery(sprintf('SELECT p.Product_ID, p.Product_Title, p.SKU, SUM(ws.Quantity_In_Stock) AS Quantity_Stocked, SUM(ws.Cost*ws.Quantity_In_Stock) AS Value, ol.Last_Ordered_On, ol2.Quantity AS Quantity_Sold FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type=\'B\' INNER JOIN product AS p ON p.Product_ID=ws.Product_ID AND p.Product_Type<>\'G\' LEFT JOIN (SELECT ol.Product_ID, MAX(o.Created_On) AS Last_Ordered_On FROM product AS p INNER JOIN order_line AS ol ON ol.Product_ID=p.Product_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Status NOT IN (\'Cancelled\', \'Unauthenticated\') GROUP BY ol.Product_ID) AS ol ON ol.Product_ID=ws.Product_ID LEFT JOIN (SELECT ol.Product_ID, SUM(ol.Quantity) AS Quantity FROM order_line AS ol INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Status NOT IN (\'Cancelled\', \'Incomplete\', \'Unauthenticated\') AND o.Created_On>ADDDATE(NOW(), INTERVAL -%d MONTH) GROUP BY ol.Product_ID) AS ol2 ON ol2.Product_ID=p.Product_ID WHERE ws.Quantity_In_Stock>0 AND (ol.Product_ID IS NULL OR ol.Last_Ordered_On<ADDDATE(NOW(), INTERVAL -%d MONTH)) GROUP BY ws.Product_ID HAVING Quantity_Sold IS NULL OR (Quantity_Stocked / (Quantity_Sold / %d))>%d ', mysql_real_escape_string($form->GetValue('monthsbacktrack')), mysql_real_escape_string($form->GetValue('months')), mysql_real_escape_string($form->GetValue('monthsbacktrack')), mysql_real_escape_string($form->GetValue('monthssupply'))));
while($data->Row) {
	$products[] = $data->Row;

	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf('SELECT p.Product_ID, ws.Shelf_Location FROM product AS p INNER JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID AND ws.Quantity_In_Stock>0 AND ws.Shelf_Location<>\'\' INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type=\'B\' GROUP BY p.Product_ID, ws.Shelf_Location'));
while($data->Row) {
	if(!isset($locations[$data->Row['Product_ID']])) {
		$locations[$data->Row['Product_ID']] = array();	
	}
	
	$locations[$data->Row['Product_ID']][] = $data->Row;

	$data->Next();	
}
$data->Disconnect();

if(!empty($products)) {
	$productData = array();
	
	foreach($products as $productItem) {
		$productData[] = $productItem['Product_ID'];
	}

	if(!empty($productData)) {
		$data = new DataQuery(sprintf('SELECT sp.Supplier_ID, sp.Product_ID, sp.Cost FROM supplier_product AS sp WHERE sp.Cost>0 AND sp.Product_ID IN (%s) ORDER BY sp.Cost ASC', implode(', ', $productData)));
		while($data->Row) {
			if(!isset($costs[$data->Row['Product_ID']])) {
				$costs[$data->Row['Product_ID']] = $data->Row;
			}

			$data->Next();	
		}
		$data->Disconnect();
	}
}

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
	
$page = new Page('Analysis / Stock Overstocked', 'Analysing stock for products which have not been sold for a given number of months.');
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
echo $webForm->AddRow($form->GetLabel('monthsbacktrack'), $form->GetHTML('monthsbacktrack'));
echo $webForm->AddRow($form->GetLabel('monthssupply'), $form->GetHTML('monthssupply'));
echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();
?>

<br />
<h3>Products Sold</h3>
<p>Stock details for products not sold within the last <strong><?php echo $form->GetValue('months'); ?></strong> months.</p>

<table width="100%" border="0">
	<tr>
		<td style="border-bottom:1px solid #aaaaaa;"></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>SKU</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Location</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Stocked</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Registered Value</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Best Cost</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Price</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Sold</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Months Supply</strong></td>
	</tr>

	<?php
	$totalStocked = 0;
	$totalValue = 0;
	$totalCost = 0;
	$totalPrice = 0;
	$totalSold = 0;
	
	foreach($products as $productData) {
		if($productData['Quantity_Sold'] > 0) {
			$product = new Product($productData['Product_ID']);

			$cost = isset($costs[$productData['Product_ID']]) ? $costs[$productData['Product_ID']]['Cost'] : 0;
			$price = $product->PriceCurrent * $productData['Quantity_Stocked'];
						
			$totalStocked += $productData['Quantity_Stocked'];
			$totalValue += $productData['Value'];
			$totalCost += $cost;
			$totalPrice += $price;
			$totalSold += $productData['Quantity_Sold'];
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td width="1%">
					<?php
					if(isset($locations[$productData['Product_ID']]) && (count($locations[$productData['Product_ID']]) > 1)) {
						?>
						
						<a href="javascript:toggleLocations(<?php echo $productData['Product_ID']; ?>);"><img src="images/button-plus.gif" alt="Toggle Locations" id="image-<?php echo $productData['Product_ID']; ?>" /></a>
						
						<?php
					}
					?>
				</td>
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
				<td align="right">&pound;<?php echo number_format($productData['Value'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($cost, 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($price, 2, '.', ','); ?></td>
				<td align="right"><?php echo $productData['Quantity_Sold']; ?></td>
				<td align="right"><?php echo !empty($productData['Quantity_Sold']) ? floor($productData['Quantity_Stocked'] / ($productData['Quantity_Sold'] / $form->GetValue('monthsbacktrack'))) : ''; ?></td>
			</tr>
			<tr style="display: none;" id="locations-<?php echo $productData['Product_ID']; ?>">
			<td></td>
			<td colspan="10">
			
				<table width="100%" border="0">
					<tr>
						<td style="border-bottom:1px solid #aaaaaa;" width="5%"><strong>Position</strong></td>
					</tr>
					
					<?php
					if(isset($locations[$productData['Product_ID']])) {
						foreach($locations[$productData['Product_ID']] as $location) {
							?>
							
							<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
								<td><?php echo $location['Shelf_Location']; ?></td>
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
	}
	?>
	
	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td align="right"><strong><?php echo $totalStocked; ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalValue, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalPrice, 2, '.', ','); ?></strong></td>
		<td align="right"><strong><?php echo number_format($totalSold, 2, '.', ','); ?></strong></td>
		<td></td>
	</tr>
</table>
<br />

<br />
<h3>Products Not Sold</h3>
<p>Stock details for products not sold within the last <strong><?php echo $form->GetValue('monthsbacktrack'); ?></strong> months.</p>

<table width="100%" border="0">
	<tr>
		<td style="border-bottom:1px solid #aaaaaa;"></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>SKU</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Location</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Stocked</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Registered Value</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Best Cost</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Price</strong></td>
	</tr>

	<?php
	$totalStocked = 0;
	$totalValue = 0;
	$totalCost = 0;
	$totalPrice = 0;
	
	foreach($products as $productData) {
		if($productData['Quantity_Sold'] == 0) {
			$product = new Product($productData['Product_ID']);

			$cost = isset($costs[$productData['Product_ID']]) ? $costs[$productData['Product_ID']]['Cost'] : 0;
			$price = $product->PriceCurrent * $productData['Quantity_Stocked'];
						
			$totalStocked += $productData['Quantity_Stocked'];
			$totalValue += $productData['Value'];
			$totalCost += $cost;
			$totalPrice += $price;
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td width="1%">
					<?php
					if(isset($locations[$productData['Product_ID']]) && (count($locations[$productData['Product_ID']]) > 1)) {
						?>
						
						<a href="javascript:toggleLocations(<?php echo $productData['Product_ID']; ?>);"><img src="images/button-plus.gif" alt="Toggle Locations" id="image-<?php echo $productData['Product_ID']; ?>" /></a>
						
						<?php
					}
					?>
				</td>
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
				<td align="right">&pound;<?php echo number_format($productData['Value'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($cost, 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($price, 2, '.', ','); ?></td>
			</tr>
			<tr style="display: none;" id="locations-<?php echo $productData['Product_ID']; ?>">
			<td></td>
			<td colspan="8">
			
				<table width="100%" border="0">
					<tr>
						<td style="border-bottom:1px solid #aaaaaa;" width="5%"><strong>Position</strong></td>
					</tr>
					
					<?php
					if(isset($locations[$productData['Product_ID']])) {
						foreach($locations[$productData['Product_ID']] as $location) {
							?>
							
							<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
								<td><?php echo $location['Shelf_Location']; ?></td>
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
	}
	?>
	
	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td align="right"><strong><?php echo $totalStocked; ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalValue, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalPrice, 2, '.', ','); ?></strong></td>
	</tr>
</table>

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');