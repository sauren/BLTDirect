<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$session->Secure(2);
view();
exit;

function view() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('months', 'Months Supply', 'select', '12', 'numeric_unsigned', 1, 11);

	for($i=1; $i<=12; $i++) {
		$form->AddOption('months', $i, $i);
	}
	
	$form->AddField('orders', 'Minimum Orders', 'select', '5', 'numeric_unsigned', 1, 11);

	for($i=0; $i<=25; $i++) {
		$form->AddOption('orders', $i, $i);
	}

	$form->AddField('supplier', 'Supplier', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('supplier', '0', '');

	$data = new DataQuery(sprintf("SELECT s.Supplier_ID, IF(c.Parent_Contact_ID>0, CONCAT_WS(' ', o.Org_Name, CONCAT('(', CONCAT_WS(' ', p.Name_First, p.Name_Last), ')')), CONCAT_WS(' ', p.Name_First, p.Name_Last)) AS Supplier FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID ORDER BY Supplier ASC"));
	while($data->Row) {
		$form->AddOption('supplier', $data->Row['Supplier_ID'], $data->Row['Supplier']);

		$data->Next();
	}
	$data->Disconnect();

	$supplierId = $form->GetValue('supplier');

	$unstockedGrouped = array();
	
	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, p.SKU, p.CacheBestSupplierID, COUNT(DISTINCT ol.Order_ID) AS Orders, SUM(ol.Quantity) AS Quantities, SUM(ol.Cost*ol.Quantity) AS Cost, IF(s1c.Parent_Contact_ID>0, CONCAT_WS(' ', s1o.Org_Name, CONCAT('(', CONCAT_WS(' ', s1p.Name_First, s1p.Name_Last), ')')), CONCAT_WS(' ', s1p.Name_First, s1p.Name_Last)) AS BestSupplier, p.CacheBestCost%s FROM (SELECT ol.Product_ID, ol.Order_ID, ol.Quantity, ol.Cost FROM order_line AS ol INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0 UNION ALL SELECT pc.Product_ID, ol.Order_ID, ol.Quantity*pc.Component_Quantity AS Quantity, ol.Cost/pc.Component_Quantity AS Cost FROM product_components AS pc INNER JOIN order_line AS ol ON ol.Product_ID=pc.Component_Of_Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0) AS ol INNER JOIN product AS p ON p.Product_ID=ol.Product_ID AND p.LockedSupplierID=0 AND p.Is_Stocked='N' LEFT JOIN supplier AS s1 ON s1.Supplier_ID=p.CacheBestSupplierID LEFT JOIN contact AS s1c ON s1c.Contact_ID=s1.Contact_ID LEFT JOIN person AS s1p ON s1p.Person_ID=s1c.Person_ID LEFT JOIN contact AS s1c2 ON s1c2.Contact_ID=s1c.Parent_Contact_ID LEFT JOIN organisation AS s1o ON s1o.Org_ID=s1c2.Org_ID%s GROUP BY ol.Product_ID HAVING Orders>=%d ORDER BY Orders DESC", ($supplierId > 0) ? ', sp.Cost AS SupplierCost' : '', $form->GetValue('months'), $form->GetValue('months'), ($supplierId > 0) ? sprintf(' LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID AND sp.Supplier_ID=%d', $supplierId) : '', $form->GetValue('orders')));
	while($data->Row) {
		$unstockedGrouped[] = $data->Row;

		$data->Next();	
	}
	$data->Disconnect();

	$script = sprintf('<script language="javascript" type="text/javascript">
		function toggleRow(rowId) {
			var row = document.getElementById(\'row-\' + rowId);
			var image = document.getElementById(\'image-\' + rowId);
			
			if(row) {
				row.style.display = (row.style.display == \'none\') ? \'table-row\' : \'none\';
				
				if(image) {
					image.src = (row.style.display == \'none\') ? \'images/button-plus.gif\' : \'images/button-minus.gif\';
				}
			}
		}	
		</script>');
	
	$page = new Page('Analysis / Stock Dropped Unstocked Grid', 'Analysing dropped stock for unstocked products.');
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
	echo $webForm->AddRow($form->GetLabel('orders'), $form->GetHTML('orders'));
	echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHTML('supplier'));
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$suppliers = array();

	foreach($unstockedGrouped as $stockedData) {
		$suppliers[$stockedData['CacheBestSupplierID']] = $stockedData['BestSupplier'];
	}

	foreach($suppliers as $groupedSupplierId=>$groupedSupplier) {
		?>

		<br />
		<h3><?php echo $groupedSupplier; ?></h3>
		<p>Dropped not locked or not stocked products grouped.</p>

		<table width="100%" border="0">
			<tr>
				<td style="border-bottom:1px solid #aaaaaa;" width="1%">&nbsp;</td>
				<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;"><strong>SKU</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Best Cost</strong></td>

				<?php
				if($supplierId > 0) {
					?>
					<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Supplier Cost</strong></td>
					<?php
				}
				?>

				<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Orders</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Quantity</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Cost</strong></td>
			</tr>

			<?php
			if(!empty($unstockedGrouped)) {
				$totalCost = 0;

				foreach($unstockedGrouped as $stockedData) {
					if($groupedSupplierId == $stockedData['CacheBestSupplierID']) {
						$totalCost += $stockedData['Cost'];

						$productSuppliers =  array();

						$data = new DataQuery(sprintf("SELECT s.Supplier_ID, IF(c.Parent_Contact_ID>0, CONCAT_WS(' ', o.Org_Name, CONCAT('(', CONCAT_WS(' ', p.Name_First, p.Name_Last), ')')), CONCAT_WS(' ', p.Name_First, p.Name_Last)) AS Supplier, COUNT(ol.Order_ID) AS Orders, SUM(ol.Quantity) AS Quantities, SUM(ol.Cost*ol.Quantity) AS Cost_Total, FORMAT(AVG(ol.Cost), 2) AS Cost_Average, sp.Cost AS Cost_Current, SUM(sp.Cost*ol.Quantity) AS Cost_Current_Total FROM (SELECT ol.Product_ID, w.Type_Reference_ID AS Supplier_ID, ol.Order_ID, ol.Quantity, ol.Cost FROM order_line AS ol INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID=%d AND ol.Despatch_ID>0 UNION ALL SELECT ol.Product_ID, w.Type_Reference_ID AS Supplier_ID, ol.Order_ID, ol.Quantity*pc.Component_Quantity, ol.Cost/pc.Component_Quantity AS Cost FROM product_components AS pc INNER JOIN order_line AS ol ON ol.Product_ID=pc.Component_Of_Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE pc.Product_ID=%d AND ol.Despatch_ID>0) AS ol INNER JOIN supplier AS s ON s.Supplier_ID=ol.Supplier_ID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID LEFT JOIN supplier_product AS sp ON sp.Supplier_ID=s.Supplier_ID AND sp.Product_ID=ol.Product_ID WHERE TRUE%s GROUP BY ol.Supplier_ID ORDER BY Supplier ASC", mysql_real_escape_string($form->GetValue('months')), mysql_real_escape_string($stockedData['Product_ID']), mysql_real_escape_string($form->GetValue('months')), mysql_real_escape_string ($stockedData['Product_ID']), ($supplierId > 0) ? sprintf(' AND s.Supplier_ID=%d', $supplierId) : ''));
						while($data->Row) {
							$productSuppliers[] = $data->Row;

							$data->Next();	
						}
						$data->Disconnect();
						?>

						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
							<td><a href="javascript:toggleRow(<?php echo $stockedData['Product_ID']; ?>);"><img src="images/button-plus.gif" id="image-<?php echo $stockedData['Product_ID']; ?>" /></a></td>
							<td><a href="product_profile.php?pid=<?php echo $stockedData['Product_ID']; ?>" target="_blank"><?php echo $stockedData['Product_ID']; ?></a></td>
							<td><?php echo $stockedData['Product_Title']; ?></td>
							<td><?php echo $stockedData['SKU']; ?></td>
							<td align="right">&pound;<?php echo number_format($stockedData['CacheBestCost'], 2, '.', ','); ?></td>

							<?php
							if($supplierId > 0) {
								?>
								<td align="right">&pound;<?php echo number_format($stockedData['SupplierCost'], 2, '.', ','); ?></td>
								<?php
							}
							?>

							<td align="right"><?php echo $stockedData['Orders']; ?></td>
							<td align="right"><?php echo $stockedData['Quantities']; ?></td>
							<td align="right">&pound;<?php echo number_format($stockedData['Cost'], 2, '.', ','); ?></td>
						</tr>
						<tr id="row-<?php echo $stockedData['Product_ID']; ?>" style="display: none;">
							<td>&nbsp;</td>
							<td colspan="<?php echo ($supplierId > 0) ? 8 : 7; ?>">
							
								<table width="100%" border="0">
									<tr>
										<td style="border-bottom:1px solid #aaaaaa;"><strong>Supplier</strong></td>
										<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Orders</strong></td>
										<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Quantity</strong></td>
										<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Average Cost</strong></td>
										<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Cost Total</strong></td>
										<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Current Cost</strong></td>
										<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Current Cost Total</strong></td>
									</tr>

									<?php
									if(!empty($productSuppliers)) {
										foreach($productSuppliers as $productSupplier) {
											?>
											
											<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
												<td><?php echo $productSupplier['Supplier']; ?></td>
												<td align="right"><?php echo $productSupplier['Orders']; ?></td>
												<td align="right"><?php echo $productSupplier['Quantities']; ?></td>
												<td align="right">&pound;<?php echo number_format($productSupplier['Cost_Average'], 2, '.', ','); ?></td>
												<td align="right">&pound;<?php echo number_format($productSupplier['Cost_Total'], 2, '.', ','); ?></td>
												<td align="right">&pound;<?php echo number_format($productSupplier['Cost_Current'], 2, '.', ','); ?></td>
												<td align="right">&pound;<?php echo number_format($productSupplier['Cost_Current_Total'], 2, '.', ','); ?></td>
											</tr>
											
											<?php
										}
									} else {
										?>
					
										<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
											<td colspan="7" align="center">There are no items available for viewing.</td>
										</tr>
										
										<?php
									}
									?>
									
								</table>
									
							</tr>
						</tr>
						
						<?php
					}
				}
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>

					<?php
					if($supplierId > 0) {
						?>
						<td>&nbsp;</td>
						<?php
					}
					?>

					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td align="right"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
				</tr>

				<?php
			} else {
				?>
				
				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td colspan="<?php echo ($supplierId > 0) ? 9 : 8; ?>" align="center">There are no items available for viewing.</td>
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