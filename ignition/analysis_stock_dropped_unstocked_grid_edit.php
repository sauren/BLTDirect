<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');

$session->Secure(3);

$supplier = new Supplier();

if(!isset($_REQUEST['id']) || !$supplier->Get($_REQUEST['id'])) {
	redirectTo('analysis_stock_dropped_unstocked_grid.php');
}

$supplierId = $supplier->ID;

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Supplier ID', 'hidden', $supplierId, 'numeric_unsigned', 1, 11);
$form->AddField('months', 'Months Supply', 'hidden', '12', 'numeric_unsigned', 1, 11);
$form->AddField('orders', 'Minimum Orders', 'hidden', '5', 'numeric_unsigned', 1, 11);

$unstockedGrouped = array();

$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, p.SKU, COUNT(DISTINCT ol.Order_ID) AS Orders, SUM(ol.Quantity) AS Quantities, SUM(ol.Cost*ol.Quantity) AS Cost, IF(s1c.Parent_Contact_ID>0, CONCAT_WS(' ', s1o.Org_Name, CONCAT('(', CONCAT_WS(' ', s1p.Name_First, s1p.Name_Last), ')')), CONCAT_WS(' ', s1p.Name_First, s1p.Name_Last)) AS BestSupplier, p.CacheBestCost, sp.Cost AS SupplierCost FROM (SELECT ol.Product_ID, ol.Order_ID, ol.Quantity, ol.Cost FROM order_line AS ol INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0 UNION ALL SELECT pc.Product_ID, ol.Order_ID, ol.Quantity*pc.Component_Quantity AS Quantity, ol.Cost/pc.Component_Quantity AS Cost FROM product_components AS pc INNER JOIN order_line AS ol ON ol.Product_ID=pc.Component_Of_Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0) AS ol INNER JOIN product AS p ON p.Product_ID=ol.Product_ID AND p.LockedSupplierID=0 AND p.Is_Stocked='N' LEFT JOIN supplier AS s1 ON s1.Supplier_ID=p.CacheBestSupplierID LEFT JOIN contact AS s1c ON s1c.Contact_ID=s1.Contact_ID LEFT JOIN person AS s1p ON s1p.Person_ID=s1c.Person_ID LEFT JOIN contact AS s1c2 ON s1c2.Contact_ID=s1c.Parent_Contact_ID LEFT JOIN organisation AS s1o ON s1o.Org_ID=s1c2.Org_ID LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID AND sp.Supplier_ID=%d GROUP BY ol.Product_ID HAVING Orders>=%d ORDER BY Orders DESC", $form->GetValue('months'), mysql_real_escape_string($form->GetValue('months')), mysql_real_escape_string($supplierId), mysql_real_escape_string($form->GetValue('orders'))));
while($data->Row) {
	$unstockedGrouped[] = $data->Row;

	$data->Next();	
}
$data->Disconnect();

foreach($unstockedGrouped as $stockedData) {
	$form->AddField('cost_' . $stockedData['Product_ID'], sprintf('Supplier Cost for \'%s\'', $stockedData['Product_Title']), 'text', number_format($stockedData['SupplierCost'], 2, '.', ''), 'float', 1, 11, true, 'size="3"');
}

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		foreach($unstockedGrouped as $stockedData) {
			if($form->GetValue('cost_' . $stockedData['Product_ID']) < $stockedData['SupplierCost']) {
				$product = new SupplierProduct();
				
				if($product->GetBySupplierProduct($supplierId, $stockedData['Product_ID'])) {
					$product->Cost = $form->GetValue('cost_' . $stockedData['Product_ID']);
					$product->Update();
				} else {
					$product->Cost = $form->GetValue('cost_' . $stockedData['Product_ID']);
					$product->Add();
				}
			}
		}

		redirectTo('?action=view');
	}
}

$page = new Page('Analysis / Stock Dropped Unstocked Grid Edit', 'Edit details for this analysis.');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('id');
echo $form->GetHTML('months');
echo $form->GetHTML('orders');
?>

<br />
<h3>Dropped Not Locked Or Not Stocked Products Grouped</h3>
<br />

<table width="100%" border="0">
	<tr>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>SKU</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Best Cost Supplier</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Best Cost</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Supplier Cost</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Orders</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Quantity</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Cost</strong></td>
	</tr>

	<?php
	if(!empty($unstockedGrouped)) {
		$totalCost = 0;

		foreach($unstockedGrouped as $stockedData) {
			$totalCost += $stockedData['Cost'];

			$productSuppliers =  array();

			$data = new DataQuery(sprintf("SELECT s.Supplier_ID, IF(c.Parent_Contact_ID>0, CONCAT_WS(' ', o.Org_Name, CONCAT('(', CONCAT_WS(' ', p.Name_First, p.Name_Last), ')')), CONCAT_WS(' ', p.Name_First, p.Name_Last)) AS Supplier, COUNT(ol.Order_ID) AS Orders, SUM(ol.Quantity) AS Quantities, SUM(ol.Cost*ol.Quantity) AS Cost_Total, FORMAT(AVG(ol.Cost), 2) AS Cost_Average, sp.Cost AS Cost_Current, SUM(sp.Cost*ol.Quantity) AS Cost_Current_Total FROM (SELECT ol.Product_ID, w.Type_Reference_ID AS Supplier_ID, ol.Order_ID, ol.Quantity, ol.Cost FROM order_line AS ol INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID=%d AND ol.Despatch_ID>0 UNION ALL SELECT ol.Product_ID, w.Type_Reference_ID AS Supplier_ID, ol.Order_ID, ol.Quantity*pc.Component_Quantity, ol.Cost/pc.Component_Quantity AS Cost FROM product_components AS pc INNER JOIN order_line AS ol ON ol.Product_ID=pc.Component_Of_Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE pc.Product_ID=%d AND ol.Despatch_ID>0) AS ol INNER JOIN supplier AS s ON s.Supplier_ID=ol.Supplier_ID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID LEFT JOIN supplier_product AS sp ON sp.Supplier_ID=s.Supplier_ID AND sp.Product_ID=ol.Product_ID WHERE s.Supplier_ID=%d GROUP BY ol.Supplier_ID ORDER BY Supplier ASC", mysql_real_escape_string($form->GetValue('months')), mysql_real_escape_string($stockedData['Product_ID']), mysql_real_escape_string($form->GetValue('months')), mysql_real_escape_string($stockedData['Product_ID']), mysql_real_escape_string($supplierId)));
			while($data->Row) {
				$productSuppliers[] = $data->Row;

				$data->Next();	
			}
			$data->Disconnect();
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><a href="product_profile.php?pid=<?php echo $stockedData['Product_ID']; ?>" target="_blank"><?php echo $stockedData['Product_ID']; ?></a></td>
				<td><?php echo $stockedData['Product_Title']; ?></td>
				<td><?php echo $stockedData['SKU']; ?></td>
				<td><?php echo $stockedData['BestSupplier']; ?></td>
				<td align="right">&pound;<?php echo number_format($stockedData['CacheBestCost'], 2, '.', ','); ?></td>
				<td align="right" nowrap="nowrap">&pound;<?php echo $form->GetHTML('cost_' . $stockedData['Product_ID']); ?></td>
				<td align="right"><?php echo $stockedData['Orders']; ?></td>
				<td align="right"><?php echo $stockedData['Quantities']; ?></td>
				<td align="right">&pound;<?php echo number_format($stockedData['Cost'], 2, '.', ','); ?></td>
			</tr>
			
			<?php
		}
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td align="right"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
		</tr>

		<?php
	} else {
		?>
		
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td colspan="9" align="center">There are no items available for viewing.</td>
		</tr>
		
		<?php
	}
	?>
	
</table>
<br />

<input type="submit" class="btn" name="update" value="update" />

<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');