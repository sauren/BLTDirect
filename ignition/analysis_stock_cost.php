<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/chart/libchart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');

if($action == 'export') {
	$session->Secure(2);
	export();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function view() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('months', 'Months Supply', 'select', '1', 'numeric_unsigned', 1, 11);

	for($i=1; $i<=3; $i++) {
		$form->AddOption('months', $i, $i);
	}

	$form->AddField('topfrom', 'Top Products (From)', 'text', '1', 'numeric_unsigned', 1, 11, true, 'size="2"');
	$form->AddField('topto', 'Top Products (To)', 'text', '100', 'numeric_unsigned', 1, 11, true, 'size="2"');

	$months = 3;

	$products = array();
	$costs = array();
	$dropped = array();
	$suppliers = array();
	$suppliers2 = array();

	$data = new DataQuery(sprintf('SELECT p.Product_ID, p.Product_Type, p.Product_Title, p.SKU, p.Position_Orders_Recent, SUM(ol.Quantity)/%1$d*%5$d AS Quantity FROM product AS p INNER JOIN order_line AS ol ON ol.Product_ID=p.Product_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Status NOT IN (\'Cancelled\', \'Incomplete\', \'Unauthenticated\') AND o.Created_On>ADDDATE(NOW(), INTERVAL -%1$d MONTH) WHERE p.Position_Orders_Recent BETWEEN %2$d AND %3$d AND p.Position_Orders_Recent>0 GROUP BY p.Product_ID ORDER BY p.Position_Orders_Recent ASC LIMIT 0, %4$d', $months, $form->GetValue('topfrom'), mysql_real_escape_string($form->GetValue('topto')), $form->GetValue('topto') + 1 - $form->GetValue('topfrom'), mysql_real_escape_string($form->GetValue('months'))));

	while($data->Row) {
		if(!isset($products[$data->Row['Product_ID']])) {
			$products[$data->Row['Product_ID']] = $data->Row;
		} else {
			$products[$data->Row['Product_ID']]['Quantity'] += $data->Row['Quantity'];
		}
		
		if($data->Row['Product_Type'] == 'G') {
			$data2 = new DataQuery(sprintf('SELECT p.Product_ID, p.Product_Type, p.Product_Title, p.SKU, p.Position_Orders_Recent, pc.Component_Quantity*%d AS Quantity FROM product_components AS pc INNER JOIN product AS p ON p.Product_ID=pc.Product_ID WHERE pc.Component_Of_Product_ID=%d AND pc.Is_Active=\'Y\'', mysql_real_escape_string($data->Row['Quantity']), mysql_real_escape_string($data->Row['Product_ID'])));
			while($data2->Row) {
				if(!isset($products[$data2->Row['Product_ID']])) {
					$products[$data2->Row['Product_ID']] = $data2->Row;
				} else {
					$products[$data2->Row['Product_ID']]['Quantity'] += $data2->Row['Quantity'];
				}
				
				$data2->Next();	
			}
			$data2->Disconnect();
		}

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
					if($products[$data->Row['Product_ID']]['Product_Type'] == 'S') {
						$costs[$data->Row['Product_ID']] = $data->Row;
					}
				}

				$data->Next();	
			}
			$data->Disconnect();
			
			$data = new DataQuery(sprintf('SELECT ol.Product_ID, SUM(ol.Quantity) AS Quantity, w.Type_Reference_ID AS Supplier_ID FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Despatch_ID>0 AND ol.Product_ID IN (%1$s) INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type=\'S\' WHERE o.Created_On>ADDDATE(NOW(), INTERVAL -%2$d MONTH) GROUP BY ol.product_ID', implode(', ', $productData), mysql_real_escape_string($form->GetValue('months'))));
			while($data->Row) {
				if(!isset($dropped[$data->Row['Product_ID']])) {
					$dropped[$data->Row['Product_ID']] = $data->Row;
				}

				$data->Next();	
			}
			$data->Disconnect();
		}
	}

	foreach($costs as $productId=>$costData) {
		if(!isset($suppliers[$costData['Supplier_ID']])) {
			$supplier = new Supplier($costData['Supplier_ID']);
			$supplier->Contact->Get();
			
			$suppliers[$costData['Supplier_ID']] = array('Supplier' => $supplier, 'Cost' => 0, 'Products' => 0, 'ProductData' => array());
		}
		
		$suppliers[$costData['Supplier_ID']]['Cost'] += $costData['Cost'] * $products[$costData['Product_ID']]['Quantity'];
		$suppliers[$costData['Supplier_ID']]['Products']++;
		$suppliers[$costData['Supplier_ID']]['ProductData'][$products[$costData['Product_ID']]['Position_Orders_Recent']] = $products[$costData['Product_ID']];
	}

	foreach($dropped as $productId=>$droppedData) {
		if(!isset($suppliers2[$droppedData['Supplier_ID']])) {
			$supplier = new Supplier($droppedData['Supplier_ID']);
			$supplier->Contact->Get();
			
			$suppliers2[$droppedData['Supplier_ID']] = array('Supplier' => $supplier, 'Products' => 0, 'ProductData' => array());
		}
		
		$suppliers2[$droppedData['Supplier_ID']]['Products']++;
		$suppliers2[$droppedData['Supplier_ID']]['ProductData'][$products[$productId]['Position_Orders_Recent']] = array('Product' => $products[$productId], 'Quantity' => $droppedData['Quantity']);
	}

	$chart1FileName = 'analysis_stock_cost-products' . rand(0, 9999999);
	$chart1Width = 600;
	$chart1Height = 400;
	$chart1Title = sprintf('Ownership of product distribution of top %d - %d products for next %d months for each supplier', $form->GetValue('topfrom'), $form->GetValue('topto'), $form->GetValue('months'));
	$chart1Reference = sprintf('temp/charts/chart_%s.png', $chart1FileName);

	$chart2FileName = 'analysis_stock_cost-costs' . rand(0, 9999999);
	$chart2Width = 600;
	$chart2Height = 400;
	$chart2Title = sprintf('Ownership of cost distribution of top %d - %d products for next %d months for each supplier', $form->GetValue('topfrom'), $form->GetValue('topto'), $form->GetValue('months'));
	$chart2Reference = sprintf('temp/charts/chart_%s.png', $chart2FileName);

	$chart1 = new PieChart($chart1Width, $chart1Height);
	$chart2 = new PieChart($chart2Width, $chart2Height);

	$totalCost = 0;
	$totalProducts = 0;
		
	foreach($suppliers as $supplierId=>$supplierData) {
		$totalCost += $supplierData['Cost'];
		$totalProducts += $supplierData['Products'];
	}
			
	foreach($suppliers as $supplierId=>$supplierData) {
		$supplierName1 = trim(sprintf('%s%% - %s <%s>', round(($supplierData['Products'] / $totalProducts) * 100), $supplierData['Supplier']->Contact->Parent->Organisation->Name, trim(sprintf('%s %s', $supplierData['Supplier']->Contact->Person->Name, $supplierData['Supplier']->Contact->Person->LastName))));
		$supplierName2 = trim(sprintf('%s%% - %s <%s>', round(($supplierData['Cost'] / $totalCost) * 100), $supplierData['Supplier']->Contact->Parent->Organisation->Name, trim(sprintf('%s %s', $supplierData['Supplier']->Contact->Person->Name, $supplierData['Supplier']->Contact->Person->LastName))));
			
		$chart1->addPoint(new Point($supplierName1, $supplierData['Products']));
		$chart2->addPoint(new Point($supplierName2, $supplierData['Cost'] / 1000));
	}

	$chart1->SetTitle($chart1Title);
	$chart1->SetLabelY('Distribution of products');
	$chart1->ShowText = false;
	$chart1->ShowLabels = true;
	$chart1->render($chart1Reference);

	$chart2->SetTitle($chart2Title);
	$chart2->SetLabelY('Distribution of costs');
	$chart2->ShowText = false;
	$chart2->ShowLabels = true;
	$chart2->render($chart2Reference);

	$script = sprintf('<script language="javascript" type="text/javascript">
		var toggleProducts = function(id, reference) {
			var element = document.getElementById(\'products-\' + id + \'-\' + reference);
			var image = document.getElementById(\'image-\' + id + \'-\' + reference);
			
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

	$page = new Page('Analysis / Stock Cost', 'Analysing stock costs for purchasing products from best costed supplier.');
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
	?>

	<br />
	<h3>Analysis Summary</h3>
	<p>Summary of supplier distribution for the top products within the range <strong><?php echo sprintf('%s - %s', $form->GetValue('topfrom'), $form->GetValue('topto')); ?></strong> for the next <strong><?php echo $form->GetValue('months'); ?></strong> months.</p>

	<div style="text-align: center;">
		<img src="<?php echo $chart1Reference; ?>" width="<?php print $chart1Width; ?>" height="<?php print $chart1Height; ?>" alt="<?php print $chart1Title; ?>" /><br />
		<img src="<?php echo $chart2Reference; ?>" width="<?php print $chart2Width; ?>" height="<?php print $chart2Height; ?>" alt="<?php print $chart2Title; ?>" /><br />
	</div>

	<br />
	<h3>Supplier Costs</h3>
	<p>Cost to buy the top products within the range <strong><?php echo sprintf('%s - %s', $form->GetValue('topfrom'), $form->GetValue('topto')); ?></strong> for the next <strong><?php echo $form->GetValue('months'); ?></strong> months.</p>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Supplier</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" width="10%" align="right"><strong>Products</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" width="10%" align="right"><strong>Cost</strong></td>
		</tr>

		<?php
		$totalCost = 0;
		$totalProducts = 0;
		
		foreach($suppliers as $supplierId=>$supplierData) {
			$totalCost += $supplierData['Cost'];
			$totalProducts += $supplierData['Products'];
			
			$supplierName = trim(sprintf('%s &lt;%s&gt;', $supplierData['Supplier']->Contact->Parent->Organisation->Name, trim(sprintf('%s %s', $supplierData['Supplier']->Contact->Person->Name, $supplierData['Supplier']->Contact->Person->LastName))));
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td width="1%"><a href="javascript:toggleProducts(<?php echo $supplierId; ?>, 'costing');"><img src="images/button-plus.gif" alt="Toggle Products" id="image-<?php echo $supplierId; ?>-costing" /></a></td>
				<td><?php echo $supplierName; ?></td>
				<td align="right"><?php echo $supplierData['Products']; ?></td>
				<td align="right">&pound;<?php echo number_format($supplierData['Cost'], 2, '.', ','); ?></td>
			</tr>
			<tr style="display: none;" id="products-<?php echo $supplierId; ?>-costing">
				<td></td>
				<td colspan="3">
				
					<table width="100%" border="0">
						<tr>
							<td style="border-bottom:1px solid #aaaaaa;" width="5%"><strong>Position</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" width="20%"><strong>SKU</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" width="10%"><strong>Quickfind</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" width="5%" align="right"><strong>Quantity</strong></td>
						</tr>
						
						<?php
						ksort($supplierData['ProductData']);
						
						foreach($supplierData['ProductData'] as $product) {
							?>
							
							<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
								<td><?php echo $product['Position_Orders_Recent']; ?></td>
								<td><?php echo $product['Product_Title']; ?></td>
								<td><?php echo $product['SKU']; ?></td>
								<td><a href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>"><?php echo $product['Product_ID']; ?></a></td>
								<td align="right"><?php echo round($product['Quantity']); ?></td>
							</tr>
			
							<?php
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
			<td align="right"><strong><?php echo $totalProducts; ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
		</tr>
	</table>
	<br />
	
	<input type="button" name="export" value="export" class="btn" onclick="window.self.location.href = '?action=export&months=<?php echo $form->GetValue('months'); ?>&topfrom=<?php echo $form->GetValue('topfrom'); ?>&topto=<?php echo $form->GetValue('topto'); ?>';" />
	<br />

	<br />
	<h3>Drop Shippers</h3>
	<p>Supplier despatches for the top products within the range <strong><?php echo sprintf('%s - %s', $form->GetValue('topfrom'), $form->GetValue('topto')); ?></strong> for the previous <strong><?php echo $form->GetValue('months'); ?></strong> months.</p>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Supplier</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" width="10%" align="right"><strong>Products</strong></td>
		</tr>

		<?php
		$totalProducts = 0;
		
		foreach($suppliers2 as $supplierId=>$supplierData) {
			$totalProducts += $supplierData['Products'];
			
			$supplierName = trim(sprintf('%s &lt;%s&gt;', $supplierData['Supplier']->Contact->Parent->Organisation->Name, trim(sprintf('%s %s', $supplierData['Supplier']->Contact->Person->Name, $supplierData['Supplier']->Contact->Person->LastName))));
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td width="1%"><a href="javascript:toggleProducts(<?php echo $supplierId; ?>, 'dropshipped');"><img src="images/button-plus.gif" alt="Toggle Products" id="image-<?php echo $supplierId; ?>-dropshipped" /></a></td>
				<td><?php echo $supplierName; ?></td>
				<td align="right"><?php echo $supplierData['Products']; ?></td>
			</tr>
			<tr style="display: none;" id="products-<?php echo $supplierId; ?>-dropshipped">
				<td></td>
				<td colspan="2">
				
					<table width="100%" border="0">
						<tr>
							<td style="border-bottom:1px solid #aaaaaa;" width="5%"><strong>Position</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" width="20%"><strong>SKU</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" width="10%"><strong>Quickfind</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" width="5%" align="right"><strong>Quantity</strong></td>
						</tr>
						
						<?php
						ksort($supplierData['ProductData']);
						
						foreach($supplierData['ProductData'] as $product) {
							?>
							
							<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
								<td><?php echo $product['Product']['Position_Orders_Recent']; ?></td>
								<td><?php echo $product['Product']['Product_Title']; ?></td>
								<td><?php echo $product['Product']['SKU']; ?></td>
								<td><a href="product_profile.php?pid=<?php echo $product['Product']['Product_ID']; ?>"><?php echo $product['Product']['Product_ID']; ?></a></td>
								<td align="right"><?php echo $product['Quantity']; ?></td>
							</tr>
			
							<?php
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
			<td align="right"><strong><?php echo $totalProducts; ?></strong></td>
		</tr>
	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function export() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('months', 'Months Supply', 'hidden', '1', 'numeric_unsigned', 1, 11);
	$form->AddField('topfrom', 'Top Products (From)', 'hidden', '1', 'numeric_unsigned', 1, 11);
	$form->AddField('topto', 'Top Products (To)', 'hidden', '100', 'numeric_unsigned', 1, 11);
	
	$months = 3;

	$products = array();
	$costs = array();
	$data = new DataQuery(sprintf('SELECT p.Product_ID, p.Product_Type, p.Product_Title, p.SKU, p.Position_Orders_Recent, SUM(ol.Quantity)/%1$d*%5$d AS Quantity FROM product AS p INNER JOIN order_line AS ol ON ol.Product_ID=p.Product_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Status NOT IN (\'Cancelled\', \'Incomplete\', \'Unauthenticated\') AND o.Created_On>ADDDATE(NOW(), INTERVAL -%1$d MONTH) WHERE p.Position_Orders_Recent BETWEEN %2$d AND %3$d AND p.Position_Orders_Recent>0 GROUP BY p.Product_ID ORDER BY p.Position_Orders_Recent ASC LIMIT 0, %4$d', $months, $form->GetValue('topfrom'), mysql_real_escape_string($form->GetValue('topto')), $form->GetValue('topto') + 1 - $form->GetValue('topfrom'), mysql_real_escape_string($form->GetValue('months'))));
	while($data->Row) {
		if(!isset($products[$data->Row['Product_ID']])) {
			$products[$data->Row['Product_ID']] = $data->Row;
		} else {
			$products[$data->Row['Product_ID']]['Quantity'] += $data->Row['Quantity'];
		}
		
		if($data->Row['Product_Type'] == 'G') {
			$data2 = new DataQuery(sprintf('SELECT p.Product_ID, p.Product_Type, p.Product_Title, p.SKU, p.Position_Orders_Recent, pc.Component_Quantity*%d AS Quantity FROM product_components AS pc INNER JOIN product AS p ON p.Product_ID=pc.Product_ID WHERE pc.Component_Of_Product_ID=%d AND pc.Is_Active=\'Y\'', mysql_real_escape_string($data->Row['Quantity']), mysql_real_escape_string($data->Row['Product_ID'])));
			while($data2->Row) {
				if(!isset($products[$data2->Row['Product_ID']])) {
					$products[$data2->Row['Product_ID']] = $data2->Row;
				} else {
					$products[$data2->Row['Product_ID']]['Quantity'] += $data2->Row['Quantity'];
				}
				
				$data2->Next();	
			}
			$data2->Disconnect();
		}

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
					if($products[$data->Row['Product_ID']]['Product_Type'] == 'S') {
						$costs[$data->Row['Product_ID']] = $data->Row;
					}
				}

				$data->Next();	
			}
			$data->Disconnect();
		}
	}

	foreach($costs as $productId=>$costData) {
		$products[$productId]['Cost'] = $costData['Cost'];
		$products[$productId]['Cost_Total'] = $costData['Cost'] * $products[$productId]['Quantity'];
	}

	$fileDate = getDatetime();
	$fileDate = substr($fileDate, 0, strpos($fileDate, ' '));

	$fileName = sprintf('blt_analysis_stock_cost_%s.csv', $fileDate);
	
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Type: application/force-download");
	header("Content-Disposition: attachment; filename=" . basename($fileName) . ";");
	header("Content-Transfer-Encoding: binary");

	$line = array();
	$line[] = 'Product ID';
	$line[] = 'Type';
	$line[] = 'Name';
	$line[] = 'SKU';
	$line[] = 'Position';
	$line[] = 'Quantity';
	$line[] = 'Cost';
	$line[] = 'Cost Total';

	echo getCsv($line);

	foreach($products as $product) {
		$line = array();
		$line[] = $product['Product_ID'];
		$line[] = $product['Product_Type'];
		$line[] = $product['Product_Title'];
		$line[] = $product['SKU'];
		$line[] = $product['Position_Orders_Recent'];
		$line[] = $product['Quantity'];
		$line[] = $product['Cost'];
		$line[] = $product['Cost_Total'];

		echo getCsv($line);
	}
}

function getCsv($row, $fd=',', $quot='"') {
	$str ='';

	foreach($row as $cell){
		$cell = str_replace($quot, $quot.$quot, $cell);

		if((strchr($cell, $fd) !== false) || (strchr($cell, $quot) !== false) || (strchr($cell, "\n") !== false)) {
			$str .= $quot.$cell.$quot.$fd;
		} else {
			$str .= $quot.$cell.$quot.$fd;
		}
	}

	return substr($str, 0, -1)."\n";
}