=<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$page = new Page('Orders Current Report');
$page->Display('header');

$products = array();

$data = new DataQuery(sprintf('SELECT p.Product_ID, p.Product_Title, p.LockedSupplierID, IF(o2.Org_ID>0, CONCAT_WS(\' \', o2.Org_Name, CONCAT(\'(\', CONCAT_WS(\' \', p2.Name_First, p2.Name_Last), \')\')), CONCAT_WS(\' \', p2.Name_First, p2.Name_Last)) AS Supplier, p.Is_Stocked, SUM(ol.Quantity) AS Quantity, ws.Backorder_Expected_On, SUM(ws.Quantity_In_Stock) AS Quantity_Stocked, p3.Quantity_Incoming FROM order_line AS ol INNER JOIN product AS p ON p.Product_ID=ol.Product_ID LEFT JOIN supplier AS s ON s.Supplier_ID=p.LockedSupplierID LEFT JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN person AS p2 ON p2.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o2 ON o2.Org_ID=c2.Org_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Status NOT IN (\'Cancelled\', \'Incomplete\', \'Unauthenticated\', \'Despatched\') AND o.Is_Declined=\'N\' INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type=\'B\' LEFT JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID LEFT JOIN (SELECT pl.Product_ID, SUM(pl.Quantity_Decremental) AS Quantity_Incoming FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID AND pl.Quantity_Decremental>0 WHERE p.For_Branch>0 GROUP BY pl.Product_ID) AS p3 ON p3.Product_ID=p.Product_ID WHERE ol.Despatch_ID=0 GROUP BY p.Product_ID HAVING Quantity_Stocked<Quantity ORDER BY ol.Product_ID ASC'));
while($data->Row) {
	$products[] = $data->Row;

	$data->Next();
}
$data->Disconnect();
?>

<br />
<h3>Products Combined</h3>
<br />

<table width="100%" border="0">
	<tr>
		<td style="border-bottom: 1px solid #aaaaaa;"><strong>Product</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;"><strong>Quickfind</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;" align="center"><strong>Is Stocked</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;"><strong>Locked Supplier</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;"><strong>Backorder Expected</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;" align="right"><strong>Quantity Incoming</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;" align="right"><strong>Quantity In Stock</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;" align="right"><strong>Quantity</strong></td>
	</tr>
	  
	<?php
	foreach($products as $product) {
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td><?php echo $product['Product_Title']; ?></td>
			<td><a href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>" target="_blank"><?php echo $product['Product_ID']; ?></a></td>
			<td align="center"><?php echo $product['Is_Stocked']; ?></td>
			<td><?php echo $product['Supplier']; ?></td>
			<td><?php echo ($product['Backorder_Expected_On'] == '0000-00-00 00:00:00') ? '' : substr($product['Backorder_Expected_On'], 0, 10); ?></td>
			<td align="right"><?php echo $product['Quantity_Incoming']; ?></td>
			<td align="right"><?php echo $product['Quantity_Stocked']; ?></td>
			<td align="right"><?php echo $product['Quantity']; ?></td>
		</tr>
			
		<?php
	}
	?>
	
</table>

<?php
$products = array();

$data = new DataQuery(sprintf('SELECT o.Order_ID, p.Product_ID, p.Product_Title, p.LockedSupplierID, IF(o2.Org_ID>0, CONCAT_WS(\' \', o2.Org_Name, CONCAT(\'(\', CONCAT_WS(\' \', p2.Name_First, p2.Name_Last), \')\')), CONCAT_WS(\' \', p2.Name_First, p2.Name_Last)) AS Supplier, p.Is_Stocked, ol.Quantity, ws.Backorder_Expected_On, w.Warehouse_ID, w.Warehouse_Name, SUM(ws.Quantity_In_Stock) AS Quantity_Stocked, p3.Quantity_Incoming FROM order_line AS ol INNER JOIN product AS p ON p.Product_ID=ol.Product_ID LEFT JOIN supplier AS s ON s.Supplier_ID=p.LockedSupplierID LEFT JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN person AS p2 ON p2.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o2 ON o2.Org_ID=c2.Org_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Status NOT IN (\'Cancelled\', \'Incomplete\', \'Unauthenticated\', \'Despatched\') AND o.Is_Declined=\'N\' INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID LEFT JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID LEFT JOIN (SELECT p.Purchase_ID, pl.Product_ID, SUM(pl.Quantity_Decremental) AS Quantity_Incoming FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID AND pl.Quantity_Decremental>0 WHERE p.For_Branch>0 GROUP BY p.Purchase_ID, pl.Product_ID) AS p3 ON p3.Product_ID=p.Product_ID WHERE ol.Despatch_ID=0 AND w.Type=\'B\' GROUP BY ol.Order_Line_ID HAVING Quantity_Stocked<Quantity ORDER BY w.Warehouse_Name ASC, o.Order_ID ASC, ol.Product_ID ASC'));
while($data->Row) {
	$products[] = $data->Row;

	$data->Next();
}
$data->Disconnect();

$orders = array();

foreach($products as $product) {
	$orders[$product['Order_ID']] = true;
}
?>

<br />
<h3>Current Summary</h3>
<br />

<table width="100%" border="0">
	<tr>
		<td style="border-bottom: 1px solid #aaaaaa;"><strong>Item</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;" align="right"><strong>Value</strong></td>
	</tr>
	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td>Total Orders</td>
		<td align="right"><?php echo count($orders); ?></td>
	</tr>
</table>

<br />
<h3>Current Products</h3>
<br />

<table width="100%" border="0">
	<tr>
		<td style="border-bottom: 1px solid #aaaaaa;"><strong>Warehouse</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;"><strong>Product</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;"><strong>Quickfind</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;" align="center"><strong>Is Stocked</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;"><strong>Locked Supplier</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;"><strong>Order</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;"><strong>Backorder Expected</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;" align="right"><strong>Quantity Incoming</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;" align="right"><strong>Quantity In Stock</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;" align="right"><strong>Quantity</strong></td>
	</tr>
	  
	<?php
	foreach($products as $product) {
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td><?php echo $product['Warehouse_Name']; ?></td>
			<td><?php echo $product['Product_Title']; ?></td>
			<td><a href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>" target="_blank"><?php echo $product['Product_ID']; ?></a></td>
			<td align="center"><?php echo $product['Is_Stocked']; ?></td>
			<td><?php echo $product['Supplier']; ?></td>
			<td><a href="order_details.php?orderid=<?php echo $product['Order_ID']; ?>" target="_blank"><?php echo $product['Order_ID']; ?></a></td>
			<td><?php echo ($product['Backorder_Expected_On'] == '0000-00-00 00:00:00') ? '' : substr($product['Backorder_Expected_On'], 0, 10); ?></td>
			<td align="right"><?php echo $product['Quantity_Incoming']; ?></td>
			<td align="right"><?php echo $product['Quantity_Stocked']; ?></td>
			<td align="right"><?php echo $product['Quantity']; ?></td>
		</tr>
			
		<?php
	}
	?>
	
</table>

<?php
require_once('lib/common/app_footer.php');