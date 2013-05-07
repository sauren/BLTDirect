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

	$suppliers = array();
	
	$data = new DataQuery(sprintf('SELECT s.Supplier_ID, IF(LENGTH(o.Org_Name)>0, CONCAT_WS(\' \', o.Org_Name, CONCAT(\'(\', CONCAT_WS(\' \', p.Name_First, p.Name_Last), \')\')), CONCAT_WS(\' \', p.Name_First, p.Name_Last)) AS Supplier, COUNT(DISTINCT s.Order_ID) AS Orders, SUM(s.Quantity) AS Quantities, SUM(s.Cost*s.Quantity) AS Cost, COUNT(DISTINCT s.Despatch_ID) AS Despatches, COUNT(DISTINCT s.Product_ID) AS Products FROM (SELECT ol.*, s.Contact_ID, sp.Cost FROM (SELECT ol.Product_ID, w.Type_Reference_ID AS Supplier_ID, ol.Order_ID, ol.Quantity, ol.Despatch_ID FROM order_line AS ol INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type=\'S\' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0 UNION ALL SELECT pc.Product_ID, w.Type_Reference_ID AS Supplier_ID, ol.Order_ID, ol.Quantity*pc.Component_Quantity AS Quantity, ol.Despatch_ID FROM product_components AS pc INNER JOIN order_line AS ol ON ol.Product_ID=pc.Component_Of_Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type=\'S\' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0) AS ol INNER JOIN product AS p ON p.Product_ID=ol.Product_ID INNER JOIN supplier AS s ON s.Supplier_ID=ol.Supplier_ID LEFT JOIN supplier_product AS sp ON sp.Product_ID=ol.Product_ID AND sp.Supplier_ID=s.Supplier_ID AND sp.Cost>0 ORDER BY ol.Product_ID ASC) AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID GROUP BY s.Supplier_ID ORDER BY Supplier ASC', mysql_real_escape_string($form->GetValue('months')), mysql_real_escape_string($form->GetValue('months'))));
	while($data->Row) {
		$suppliers[$data->Row['Supplier_ID']] = $data->Row;

		$data->Next();	
	}
	$data->Disconnect();

	$chart1FileName = 'analysis_stock_dropped-products' . rand(0, 9999999);
	$chart1Width = 600;
	$chart1Height = 400;
	$chart1Title = sprintf('Ownership of number of dropped products for the last %d months for each supplier', $form->GetValue('months'));
	$chart1Reference = sprintf('temp/charts/chart_%s.png', $chart1FileName);

	$chart2FileName = 'analysis_stock_dropped-orders' . rand(0, 9999999);
	$chart2Width = 600;
	$chart2Height = 400;
	$chart2Title = sprintf('Ownership of number of dropped orders for the last %d months for each supplier', $form->GetValue('months'));
	$chart2Reference = sprintf('temp/charts/chart_%s.png', $chart2FileName);
	
	$chart3FileName = 'analysis_stock_dropped-costs' . rand(0, 9999999);
	$chart3Width = 600;
	$chart3Height = 400;
	$chart3Title = sprintf('Ownership of costs of dropped products for the last %d months for each supplier', $form->GetValue('months'));
	$chart3Reference = sprintf('temp/charts/chart_%s.png', $chart3FileName);
	
	$chart1 = new PieChart($chart1Width, $chart1Height);
	$chart2 = new PieChart($chart2Width, $chart2Height);
	$chart3 = new PieChart($chart3Width, $chart3Height);

	$totalProducts = 0;
	$totalOrders = 0;
	$totalCost = 0;
		
	foreach($suppliers as $supplierId=>$supplierData) {
		$totalProducts += $supplierData['Products'];
		$totalOrders += $supplierData['Orders'];
		$totalCost += $supplierData['Cost'];
	}
			
	foreach($suppliers as $supplierId=>$supplierData) {
		$chart1->addPoint(new Point(sprintf('%s - %s', $supplierData['Products'], $supplierData['Supplier']), $supplierData['Products']));
		$chart2->addPoint(new Point(sprintf('%s - %s', $supplierData['Orders'], $supplierData['Supplier']), $supplierData['Orders']));
		$chart3->addPoint(new Point(sprintf('£%s - %s', number_format($supplierData['Cost'], 2, '.', ','), $supplierData['Supplier']), $supplierData['Cost'] / 1000));
	}

	$chart1->SetTitle($chart1Title);
	$chart1->SetLabelY('Distribution of products');
	$chart1->ShowText = false;
	$chart1->ShowLabels = true;
	$chart1->render($chart1Reference);

	$chart2->SetTitle($chart2Title);
	$chart2->SetLabelY('Distribution of orders');
	$chart2->ShowText = false;
	$chart2->ShowLabels = true;
	$chart2->render($chart2Reference);
	
	$chart3->SetTitle($chart3Title);
	$chart3->SetLabelY('Distribution of costs');
	$chart3->ShowText = false;
	$chart3->ShowLabels = true;
	$chart3->render($chart3Reference);

	$stocked = array();
	
	$data = new DataQuery(sprintf('SELECT ol.*, p.Product_Title, p.SKU, p.Is_Stocked, IF(LENGTH(o.Org_Name)>0, CONCAT_WS(\' \', o.Org_Name, CONCAT(\'(\', CONCAT_WS(\' \', p2.Name_First, p2.Name_Last), \')\')), CONCAT_WS(\' \', p2.Name_First, p2.Name_Last)) AS Supplier, sp.Cost, ol.Exclusive FROM (SELECT ol.Product_ID, w.Type_Reference_ID AS Supplier_ID, ol.Order_ID, ol.Quantity, ol.Despatch_ID, IF(ol2.LineCount=ol2.LineCountStocked, \'Y\', \'N\') AS Exclusive FROM order_line AS ol INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type=\'S\' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) INNER JOIN product AS p ON p.Product_ID=ol.Product_ID AND p.LockedSupplierID>0 AND p.Is_Stocked=\'Y\' LEFT JOIN (SELECT ol.Order_ID, COUNT(ol.Order_Line_ID) AS LineCount, COUNT(p.Product_ID) AS LineCountStocked FROM order_line AS ol LEFT JOIN product AS p ON p.Product_ID=ol.Product_ID AND p.LockedSupplierID>0 AND p.Is_Stocked=\'Y\' GROUP BY ol.Order_ID) AS ol2 ON ol2.Order_ID=ol.Order_ID WHERE ol.Product_ID>0 AND ol.Despatch_ID>0 GROUP BY ol.Product_ID, w.Type_Reference_ID, ol.Order_ID, ol.Despatch_ID UNION ALL SELECT pc.Product_ID, w.Type_Reference_ID AS Supplier_ID, ol.Order_ID, ol.Quantity*pc.Component_Quantity AS Quantity, ol.Despatch_ID, IF(ol2.LineCount=ol2.LineCountStocked, \'Y\', \'N\') AS Exclusive FROM product_components AS pc INNER JOIN order_line AS ol ON ol.Product_ID=pc.Component_Of_Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type=\'S\' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) INNER JOIN product AS p ON p.Product_ID=ol.Product_ID AND p.LockedSupplierID>0 AND p.Is_Stocked=\'Y\' LEFT JOIN (SELECT ol.Order_ID, COUNT(ol.Order_Line_ID) AS LineCount, COUNT(p.Product_ID) AS LineCountStocked FROM order_line AS ol LEFT JOIN product AS p ON p.Product_ID=ol.Product_ID AND p.LockedSupplierID>0 AND p.Is_Stocked=\'Y\' GROUP BY ol.Order_ID) AS ol2 ON ol2.Order_ID=ol.Order_ID WHERE ol.Product_ID>0 AND ol.Despatch_ID>0 GROUP BY pc.Product_ID, w.Type_Reference_ID, ol.Order_ID, ol.Despatch_ID) AS ol INNER JOIN product AS p ON p.Product_ID=ol.Product_ID INNER JOIN supplier AS s ON s.Supplier_ID=ol.Supplier_ID LEFT JOIN supplier_product AS sp ON sp.Product_ID=ol.Product_ID AND sp.Supplier_ID=s.Supplier_ID AND sp.Cost>0 INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS p2 ON p2.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID ORDER BY ol.Product_ID ASC, Supplier ASC', mysql_real_escape_string($form->GetValue('months')), mysql_real_escape_string($form->GetValue('months'))));
	while($data->Row) {
		$stocked[] = $data->Row;

		$data->Next();	
	}
	$data->Disconnect();
	
	$unstocked = array();
	
	$data = new DataQuery(sprintf('SELECT ol.*, p.Product_Title, p.SKU, p.Is_Stocked, IF(LENGTH(o.Org_Name)>0, CONCAT_WS(\' \', o.Org_Name, CONCAT(\'(\', CONCAT_WS(\' \', p2.Name_First, p2.Name_Last), \')\')), CONCAT_WS(\' \', p2.Name_First, p2.Name_Last)) AS Supplier, sp.Cost FROM (SELECT ol.Product_ID, w.Type_Reference_ID AS Supplier_ID, ol.Order_ID, ol.Quantity, ol.Despatch_ID FROM order_line AS ol INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type=\'S\' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0 UNION ALL SELECT pc.Product_ID, w.Type_Reference_ID AS Supplier_ID, ol.Order_ID, ol.Quantity*pc.Component_Quantity AS Quantity, ol.Despatch_ID FROM product_components AS pc INNER JOIN order_line AS ol ON ol.Product_ID=pc.Component_Of_Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type=\'S\' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0) AS ol INNER JOIN product AS p ON p.Product_ID=ol.Product_ID AND (p.LockedSupplierID=0 OR p.Is_Stocked=\'N\') INNER JOIN supplier AS s ON s.Supplier_ID=ol.Supplier_ID LEFT JOIN supplier_product AS sp ON sp.Product_ID=ol.Product_ID AND sp.Supplier_ID=s.Supplier_ID AND sp.Cost>0 INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS p2 ON p2.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID ORDER BY ol.Product_ID ASC, Supplier ASC', $form->GetValue('months'), $form->GetValue('months')));
	while($data->Row) {
		$unstocked[] = $data->Row;

		$data->Next();	
	}
	$data->Disconnect();
	
	$unstockedGrouped = array();
	
	$data = new DataQuery(sprintf('SELECT p.Product_ID, p.Product_Title, p.SKU, p.Is_Stocked, COUNT(DISTINCT ol.Order_ID) AS Orders, SUM(ol.Quantity) AS Quantities FROM (SELECT ol.Product_ID, ol.Order_ID, ol.Quantity FROM order_line AS ol INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type=\'S\' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0 UNION ALL SELECT pc.Product_ID, ol.Order_ID, ol.Quantity*pc.Component_Quantity AS Quantity FROM product_components AS pc INNER JOIN order_line AS ol ON ol.Product_ID=pc.Component_Of_Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type=\'S\' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0) AS ol INNER JOIN product AS p ON p.Product_ID=ol.Product_ID AND (p.LockedSupplierID=0 OR p.Is_Stocked=\'N\') GROUP BY ol.Product_ID ORDER BY Orders DESC', mysql_real_escape_string($form->GetValue('months')), mysql_real_escape_string($form->GetValue('months'))));
	while($data->Row) {
		$unstockedGrouped[] = $data->Row;

		$data->Next();	
	}
	$data->Disconnect();

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

	$page = new Page('Analysis / Stock Dropped', 'Analysing dropped stock for products.');
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
	<p>
		Summary of supplier distribution for products for the last <strong><?php echo $form->GetValue('months'); ?></strong> months.<br />
		Total cost of dropped products <strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></strong>.
	</p>

	<div style="text-align: center;">
		<img src="<?php echo $chart1Reference; ?>" width="<?php print $chart1Width; ?>" height="<?php print $chart1Height; ?>" alt="<?php print $chart1Title; ?>" /><br />
		<img src="<?php echo $chart2Reference; ?>" width="<?php print $chart2Width; ?>" height="<?php print $chart2Height; ?>" alt="<?php print $chart2Title; ?>" /><br />
		<img src="<?php echo $chart3Reference; ?>" width="<?php print $chart3Width; ?>" height="<?php print $chart3Height; ?>" alt="<?php print $chart3Title; ?>" /><br />
	</div>

	<br />
	<h3>Dropped Locked &amp; Stocked Exclusively Products</h3>
	<br />

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>SKU</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="center"><strong>Stocked</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Supplier</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Order ID</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Despatch ID</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Quantity</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Total</strong></td>
		</tr>

		<?php
		if(!empty($stocked)) {
			$totalCost = 0;
		
			foreach($stocked as $stockedData) {
				if($stockedData['Exclusive'] == 'Y') {
					$totalCost += $stockedData['Cost']*$stockedData['Quantity'];
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td><?php echo $stockedData['Product_Title']; ?></td>
						<td><?php echo $stockedData['SKU']; ?></td>
						<td><a href="product_profile.php?pid=<?php echo $stockedData['Product_ID']; ?>" target="_blank"><?php echo $stockedData['Product_ID']; ?></a></td>
						<td align="center"><?php echo $stockedData['Is_Stocked']; ?></td>
						<td><?php echo $stockedData['Supplier']; ?></td>
						<td><a href="order_details.php?orderid=<?php echo $stockedData['Order_ID']; ?>" target="_blank"><?php echo $stockedData['Order_ID']; ?></a></td>
						<td><a href="despatch_view.php?despatchid=<?php echo $stockedData['Despatch_ID']; ?>" target="_blank"><?php echo $stockedData['Despatch_ID']; ?></a></td>
						<td align="right"><?php echo $stockedData['Quantity']; ?></td>
						<td align="right">&pound;<?php echo number_format($stockedData['Cost'], 2, '.', ','); ?></td>
						<td align="right">&pound;<?php echo number_format($stockedData['Cost']*$stockedData['Quantity'], 2, '.', ','); ?></td>
					</tr>
					
					<?php
				}
			}
			?>
			
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td colspan="9">&nbsp;</td>
				<td align="right"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></ strong></td>
			</tr>
			
			<?php
		} else {
			?>
			
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td colspan="10" align="center">There are no items available for viewing.</td>
			</tr>
			
			<?php			
		}
		?>
		
	</table>
	
	<br />
	<h3>Dropped Locked &amp; Stocked Non-Exclusively Products</h3>
	<br />

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>SKU</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="center"><strong>Stocked</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Supplier</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Order ID</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Despatch ID</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Quantity</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Total</strong></td>
		</tr>

		<?php
		if(!empty($stocked)) {
			$totalCost = 0;
		
			foreach($stocked as $stockedData) {
				if($stockedData['Exclusive'] == 'N') {
					$totalCost += $stockedData['Cost']*$stockedData['Quantity'];
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td><?php echo $stockedData['Product_Title']; ?></td>
						<td><?php echo $stockedData['SKU']; ?></td>
						<td><a href="product_profile.php?pid=<?php echo $stockedData['Product_ID']; ?>" target="_blank"><?php echo $stockedData['Product_ID']; ?></a></td>
						<td align="center"><?php echo $stockedData['Is_Stocked']; ?></td>
						<td><?php echo $stockedData['Supplier']; ?></td>
						<td><a href="order_details.php?orderid=<?php echo $stockedData['Order_ID']; ?>" target="_blank"><?php echo $stockedData['Order_ID']; ?></a></td>
						<td><a href="despatch_view.php?despatchid=<?php echo $stockedData['Despatch_ID']; ?>" target="_blank"><?php echo $stockedData['Despatch_ID']; ?></a></td>
						<td align="right"><?php echo $stockedData['Quantity']; ?></td>
						<td align="right">&pound;<?php echo number_format($stockedData['Cost'], 2, '.', ','); ?></td>
						<td align="right">&pound;<?php echo number_format($stockedData['Cost']*$stockedData['Quantity'], 2, '.', ','); ?></td>
					</tr>
					
					<?php
				}
			}
			?>
			
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td colspan="9">&nbsp;</td>
				<td align="right"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></ strong></td>
			</tr>
			
			<?php
		} else {
			?>
			
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td colspan="10" align="center">There are no items available for viewing.</td>
			</tr>
			
			<?php			
		}
		?>
		
	</table>
	
	<br />
	<h3>Dropped Not Locked Or Not Stocked Products</h3>
	<br />

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>SKU</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="center"><strong>Stocked</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Supplier</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Order ID</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Despatch ID</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Quantity</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Total</strong></td>
		</tr>

		<?php
		if(!empty($unstocked)) {
			$totalCost = 0;
		
			foreach($unstocked as $stockedData) {
				$totalCost += $stockedData['Cost']*$stockedData['Quantity'];
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $stockedData['Product_Title']; ?></td>
					<td><?php echo $stockedData['SKU']; ?></td>
					<td><a href="product_profile.php?pid=<?php echo $stockedData['Product_ID']; ?>" target="_blank"><?php echo $stockedData['Product_ID']; ?></a></td>
					<td align="center"><?php echo $stockedData['Is_Stocked']; ?></td>
					<td><?php echo $stockedData['Supplier']; ?></td>
					<td><a href="order_details.php?orderid=<?php echo $stockedData['Order_ID']; ?>" target="_blank"><?php echo $stockedData['Order_ID']; ?></a></td>
					<td><a href="despatch_view.php?despatchid=<?php echo $stockedData['Despatch_ID']; ?>" target="_blank"><?php echo $stockedData['Despatch_ID']; ?></a></td>
					<td align="right"><?php echo $stockedData['Quantity']; ?></td>
					<td align="right">&pound;<?php echo number_format($stockedData['Cost'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($stockedData['Cost']*$stockedData['Quantity'], 2, '.', ','); ?></td>
				</tr>
				
				<?php
			}
			?>
			
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td colspan="9">&nbsp;</td>
				<td align="right"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></ strong></td>
			</tr>
			
			<?php
		} else {
			?>
			
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td colspan="10" align="center">There are no items available for viewing.</td>
			</tr>
			
			<?php			
		}
		?>
		
	</table>
	
	<br />
	<h3>Dropped Not Locked Or Not Stocked Products Grouped</h3>
	<br />

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>SKU</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="center"><strong>Stocked</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Orders</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Quantity</strong></td>
		</tr>

		<?php
		if(!empty($unstockedGrouped)) {
			foreach($unstockedGrouped as $stockedData) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $stockedData['Product_Title']; ?></td>
					<td><?php echo $stockedData['SKU']; ?></td>
					<td><a href="product_profile.php?pid=<?php echo $stockedData['Product_ID']; ?>" target="_blank"><?php echo $stockedData['Product_ID']; ?></a></td>
					<td align="center"><?php echo $stockedData['Is_Stocked']; ?></td>
					<td align="right"><?php echo $stockedData['Orders']; ?></td>
					<td align="right"><?php echo $stockedData['Quantities']; ?></td>
				</tr>
				
				<?php
			}
		} else {
			?>
			
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td colspan="6" align="center">There are no items available for viewing.</td>
			</tr>
			
			<?php			
		}
		?>
		
	</table>
		
	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}