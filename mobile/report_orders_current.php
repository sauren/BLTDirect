<?php
require_once('lib/common/app_header.php');

$secure = isset($_SESSION['Mobile']['Secure']) ? $_SESSION['Mobile']['Secure'] : false;

if($secure) {
	?>

	<html>
		<head>
			<style>
				body, th, td {
					font-family: arial, sans-serif;
					font-size: 0.8em;
				}
				h1, h2, h3, h4, h5, h6 {
					margin-bottom: 0;
					padding-bottom: 0;
				}
				h1 {
					font-size: 1.6em;
				}
				h2 {
					font-size: 1.2em;
				}
				p {
					margin-top: 0;
				}
			</style>
		</head>
		<body>
		
		<h1>Orders Current</h1>
		
		<?php
		$products = array();

		$data = new DataQuery(sprintf('SELECT p.Product_ID, p.Product_Title, p.LockedSupplierID, IF(o2.Org_ID>0, CONCAT_WS(\' \', o2.Org_Name, CONCAT(\'(\', CONCAT_WS(\' \', p2.Name_First, p2.Name_Last), \')\')), CONCAT_WS(\' \', p2.Name_First, p2.Name_Last)) AS Supplier, p.Is_Stocked, SUM(ol.Quantity) AS Quantity, ws.Backorder_Expected_On, SUM(ws.Quantity_In_Stock) AS Quantity_Stocked, SUM(p3.Quantity_Incoming) AS Quantity_Incoming FROM order_line AS ol INNER JOIN product AS p ON p.Product_ID=ol.Product_ID LEFT JOIN supplier AS s ON s.Supplier_ID=p.LockedSupplierID LEFT JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN person AS p2 ON p2.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o2 ON o2.Org_ID=c2.Org_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Status NOT IN (\'Cancelled\', \'Incomplete\', \'Unauthenticated\', \'Despatched\') INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type=\'B\' LEFT JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID LEFT JOIN (SELECT pl.Product_ID, SUM(pl.Quantity_Decremental) AS Quantity_Incoming FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID AND pl.Quantity_Decremental>0 WHERE p.For_Branch>0 GROUP BY pl.Product_ID) AS p3 ON p3.Product_ID=p.Product_ID WHERE ol.Despatch_ID=0 GROUP BY p.Product_ID HAVING Quantity_Stocked<Quantity ORDER BY ol.Product_ID ASC'));
		while($data->Row) {
			$products[] = $data->Row;

			$data->Next();
		}
		$data->Disconnect();
		?>

		<br />
		<h3>Products Combined</h3>
		<br />

		<table width="100%" border="0" cellpadding="3" cellspacing="0">
			<tr style="background-color:#eeeeee;">
				<td style="border-bottom: 1px solid #dddddd;"><strong>Product</strong></td>
				<td style="border-bottom: 1px solid #dddddd;"><strong>Quickfind</strong></td>
				<td style="border-bottom: 1px solid #dddddd;" align="center"><strong>Is Stocked</strong></td>
				<td style="border-bottom: 1px solid #dddddd;"><strong>Locked Supplier</strong></td>
				<td style="border-bottom: 1px solid #dddddd;"><strong>Backorder Expected</strong></td>
				<td style="border-bottom: 1px solid #dddddd;" align="right"><strong>Quantity Incoming</strong></td>
				<td style="border-bottom: 1px solid #dddddd;" align="right"><strong>Quantity In Stock</strong></td>
				<td style="border-bottom: 1px solid #dddddd;" align="right"><strong>Quantity</strong></td>
			</tr>
			  
			<?php
			foreach($products as $product) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td style="border-top:1px solid #dddddd;"><?php echo $product['Product_Title']; ?></td>
					<td style="border-top:1px solid #dddddd;"><?php echo $product['Product_ID']; ?></td>
					<td style="border-top:1px solid #dddddd;" align="center"><?php echo $product['Is_Stocked']; ?></td>
					<td style="border-top:1px solid #dddddd;"><?php echo $product['Supplier']; ?></td>
					<td style="border-top:1px solid #dddddd;"><?php echo ($product['Backorder_Expected_On'] == '0000-00-00 00:00:00') ? '' : substr($product['Backorder_Expected_On'], 0, 10); ?></td>
					<td style="border-top:1px solid #dddddd;"align="right"><?php echo $product['Quantity_Incoming']; ?></td>
					<td style="border-top:1px solid #dddddd;"align="right"><?php echo $product['Quantity_Stocked']; ?></td>
					<td style="border-top:1px solid #dddddd;"align="right"><?php echo $product['Quantity']; ?></td>
				</tr>
					
				<?php
			}
			?>
			
		</table>

		<?php
		$products = array();

		$data = new DataQuery(sprintf('SELECT o.Order_ID, p.Product_ID, p.Product_Title, p.LockedSupplierID, IF(o2.Org_ID>0, CONCAT_WS(\' \', o2.Org_Name, CONCAT(\'(\', CONCAT_WS(\' \', p2.Name_First, p2.Name_Last), \')\')), CONCAT_WS(\' \', p2.Name_First, p2.Name_Last)) AS Supplier, p.Is_Stocked, ol.Quantity, ws.Backorder_Expected_On, w.Warehouse_ID, w.Warehouse_Name, SUM(ws.Quantity_In_Stock) AS Quantity_Stocked, p3.Purchase_ID, p3.Quantity_Incoming FROM order_line AS ol INNER JOIN product AS p ON p.Product_ID=ol.Product_ID LEFT JOIN supplier AS s ON s.Supplier_ID=p.LockedSupplierID LEFT JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN person AS p2 ON p2.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o2 ON o2.Org_ID=c2.Org_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Status NOT IN (\'Cancelled\', \'Incomplete\', \'Unauthenticated\', \'Despatched\') INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID LEFT JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID LEFT JOIN (SELECT p.Purchase_ID, pl.Product_ID, SUM(pl.Quantity_Decremental) AS Quantity_Incoming FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID AND pl.Quantity_Decremental>0 WHERE p.For_Branch>0 GROUP BY p.Purchase_ID, pl.Product_ID) AS p3 ON p3.Product_ID=p.Product_ID WHERE ol.Despatch_ID=0 AND w.Type=\'B\' GROUP BY ol.Order_Line_ID, p3.Purchase_ID HAVING Quantity_Stocked<Quantity ORDER BY w.Warehouse_Name ASC, o.Order_ID ASC, ol.Product_ID ASC'));
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

		<table width="100%" border="0" cellpadding="3" cellspacing="0">
			<tr style="background-color:#eeeeee;">
				<td style="border-bottom: 1px solid #dddddd;"><strong>Item</strong></td>
				<td style="border-bottom: 1px solid #dddddd;" align="right"><strong>Value</strong></td>
			</tr>
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td style="border-top:1px solid #dddddd;">Total Orders</td>
				<td style="border-top:1px solid #dddddd;" align="right"><?php echo count($orders); ?></td>
			</tr>
		</table>

		<br />
		<h3>Current Products</h3>
		<br />

		<table width="100%" border="0" cellpadding="3" cellspacing="0">
			<tr style="background-color:#eeeeee;">
				<td style="border-bottom: 1px solid #dddddd;"><strong>Warehouse</strong></td>
				<td style="border-bottom: 1px solid #dddddd;"><strong>Product</strong></td>
				<td style="border-bottom: 1px solid #dddddd;"><strong>Quickfind</strong></td>
				<td style="border-bottom: 1px solid #dddddd;" align="center"><strong>Is Stocked</strong></td>
				<td style="border-bottom: 1px solid #dddddd;"><strong>Locked Supplier</strong></td>
				<td style="border-bottom: 1px solid #dddddd;"><strong>Order</strong></td>
				<td style="border-bottom: 1px solid #dddddd;"><strong>Backorder Expected</strong></td>
				<td style="border-bottom: 1px solid #dddddd;"><strong>Purchase</strong></td>
				<td style="border-bottom: 1px solid #dddddd;" align="right"><strong>Quantity Incoming</strong></td>
				<td style="border-bottom: 1px solid #dddddd;" align="right"><strong>Quantity In Stock</strong></td>
				<td style="border-bottom: 1px solid #dddddd;" align="right"><strong>Quantity</strong></td>
			</tr>
			  
			<?php
			foreach($products as $product) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td style="border-top:1px solid #dddddd;"><?php echo $product['Warehouse_Name']; ?></td>
					<td style="border-top:1px solid #dddddd;"><?php echo $product['Product_Title']; ?></td>
					<td style="border-top:1px solid #dddddd;"><?php echo $product['Product_ID']; ?></td>
					<td style="border-top:1px solid #dddddd;" align="center"><?php echo $product['Is_Stocked']; ?></td>
					<td style="border-top:1px solid #dddddd;"><?php echo $product['Supplier']; ?></td>
					<td style="border-top:1px solid #dddddd;"><?php echo $product['Order_ID']; ?></td>
					<td style="border-top:1px solid #dddddd;"><?php echo ($product['Backorder_Expected_On'] == '0000-00-00 00:00:00') ? '' : substr($product['Backorder_Expected_On'], 0, 10); ?></td>
					<td style="border-top:1px solid #dddddd;"><?php echo $product['Purchase_ID']; ?></td>
					<td style="border-top:1px solid #dddddd;" align="right"><?php echo $product['Quantity_Incoming']; ?></td>
					<td style="border-top:1px solid #dddddd;" align="right"><?php echo $product['Quantity_Stocked']; ?></td>
					<td style="border-top:1px solid #dddddd;" align="right"><?php echo $product['Quantity']; ?></td>
				</tr>
					
				<?php
			}
			?>
			
		</table>
		
	<html>

	<?php
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();