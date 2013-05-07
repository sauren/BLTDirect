<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/chart/libchart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');

$session->Secure(2);
view();
exit;

function view() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('months', 'Months Supply', 'select', '1', 'numeric_unsigned', 1, 11);

	for($i=1; $i<=12; $i++) {
		$form->AddOption('months', $i, $i);
	}

	$products = array();
	$suppliers = array();
	$data = new DataQuery(sprintf('SELECT Product_ID, Product_Title, SKU, LockedSupplierID, Cost, SUM(Quantity) AS Quantity FROM (SELECT p.Product_ID, p.Product_Title, p.SKU, p.LockedSupplierID, sp.Cost, SUM(ol.Quantity) AS Quantity FROM product AS p INNER JOIN order_line AS ol ON ol.Product_ID=p.Product_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Status NOT IN (\'Cancelled\', \'Unauthenticated\') AND o.Created_On>ADDDATE(NOW(), INTERVAL -%1$d MONTH) LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID AND sp.Supplier_ID=p.LockedSupplierID WHERE p.Is_Stocked=\'Y\' AND p.Product_Type=\'S\' GROUP BY p.Product_ID UNION ALL SELECT p2.Product_ID, p2.Product_Title, p2.SKU, p2.LockedSupplierID, sp.Cost, SUM(ol.Quantity*pc.Component_Quantity) AS Quantity FROM product AS p INNER JOIN product_components AS pc ON pc.Component_Of_Product_ID=p.Product_ID INNER JOIN product AS p2 ON p2.Product_ID=pc.Product_ID INNER JOIN order_line AS ol ON ol.Product_ID=p.Product_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Status NOT IN (\'Cancelled\', \'Incomplete\', \'Unauthenticated\') AND o.Created_On>ADDDATE(NOW(), INTERVAL -%1$d MONTH) LEFT JOIN supplier_product AS sp ON sp.Product_ID=p2.Product_ID AND sp.Supplier_ID=p2.LockedSupplierID WHERE p2.Is_Stocked=\'Y\' AND p.Product_Type=\'G\' GROUP BY p2.Product_ID) AS p GROUP BY Product_ID ORDER BY Product_ID ASC', mysql_real_escape_string($form->GetValue('months'))));

	while($data->Row) {
		$products[$data->Row['Product_ID']] = $data->Row;

		$data->Next();	
	}
	$data->Disconnect();

	if(!empty($products)) {
		$productData = array();
		
		foreach($products as $productId=>$productItem) {
			if($productItem['LockedSupplierID'] > 0) {
				$products[$productId]['Supplier_ID'] = $productItem['LockedSupplierID'];
			} else {
				$productData[] = $productItem['Product_ID'];
			}
		}

		if(!empty($productData)) {		
			$data = new DataQuery(sprintf('SELECT sp.Supplier_ID, sp.Product_ID, sp.Cost FROM supplier_product AS sp WHERE sp.Cost>0 AND sp.Product_ID IN (%s) ORDER BY sp.Cost DESC', implode(', ', $productData)));
			while($data->Row) {
				$products[$data->Row['Product_ID']]['Supplier_ID'] = $data->Row['Supplier_ID'];
				$products[$data->Row['Product_ID']]['Cost'] = $data->Row['Cost'];

				$data->Next();
			}
			$data->Disconnect();
		}
	}

	foreach($products as $productId=>$productItem) {
		if(!isset($suppliers[$productItem['Supplier_ID']])) {
			$supplier = new Supplier($productItem['Supplier_ID']);
			$supplier->Contact->Get();
			
			$suppliers[$productItem['Supplier_ID']] = array('Supplier' => $supplier, 'Cost' => 0, 'Products' => 0, 'ProductData' => array());
		}
		
		$suppliers[$productItem['Supplier_ID']]['Cost'] += $productItem['Cost'] * $products[$productItem['Product_ID']]['Quantity'];
		$suppliers[$productItem['Supplier_ID']]['Products']++;
		$suppliers[$productItem['Supplier_ID']]['ProductData'][$productItem['Product_ID']] = $products[$productItem['Product_ID']];
	}

	$chart1FileName = 'analysis_stock_cost-products' . rand(0, 9999999);
	$chart1Width = 600;
	$chart1Height = 400;
	$chart1Title = sprintf('Ownership of product distribution of stocked products for next %d months for each supplier', $form->GetValue('months'));
	$chart1Reference = sprintf('temp/charts/chart_%s.png', $chart1FileName);

	$chart2FileName = 'analysis_stock_cost-costs' . rand(0, 9999999);
	$chart2Width = 600;
	$chart2Height = 400;
	$chart2Title = sprintf('Ownership of cost distribution of stocked products for next %d months for each supplier', $form->GetValue('months'));
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

	$page = new Page('Analysis / Stock Products', 'Analysing stock products for purchasing products from best costed supplier.');
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
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	?>

	<br />
	<h3>Analysis Summary</h3>
	<p>Summary of supplier distribution for the stocked products for the next <strong><?php echo $form->GetValue('months'); ?></strong> months.</p>

	<div style="text-align: center;">
		<img src="<?php echo $chart1Reference; ?>" width="<?php print $chart1Width; ?>" height="<?php print $chart1Height; ?>" alt="<?php print $chart1Title; ?>" /><br />
		<img src="<?php echo $chart2Reference; ?>" width="<?php print $chart2Width; ?>" height="<?php print $chart2Height; ?>" alt="<?php print $chart2Title; ?>" /><br />
	</div>

	<br />
	<h3>Supplier Costs</h3>
	<p>Cost to buy the stocked products for the next <strong><?php echo $form->GetValue('months'); ?></strong> months.</p>

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
							<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" width="20%"><strong>SKU</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" width="10%"><strong>Quickfind</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" width="5%" align="right"><strong>Quantity</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" width="5%" align="right"><strong>Cost</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" width="5%" align="right"><strong>Total Cost</strong></td>
						</tr>
						
						<?php
						foreach($supplierData['ProductData'] as $product) {
							?>
							
							<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
								<td><?php echo $product['Product_Title']; ?></td>
								<td><?php echo $product['SKU']; ?></td>
								<td><a href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>"><?php echo $product['Product_ID']; ?></a></td>
								<td align="right"><?php echo round($product['Quantity']); ?></td>
								<td align="right">&pound;<?php echo number_format(round($product['Cost'], 2), 2, '.', ','); ?></td>
								<td align="right">&pound;<?php echo number_format(round($product['Cost'] * $product['Quantity'], 2), 2, '.', ','); ?></td>
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

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}