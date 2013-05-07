<?php
ini_set('max_execution_time', '1800');

require_once('lib/common/app_header.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");

$secure = isset($_SESSION['Mobile']['Secure']) ? $_SESSION['Mobile']['Secure'] : false;

if($secure) {
	$periods = array();
	$periods[] = array(0, 12);
	$periods[] = array(12, 18);
	$periods[] = array(18, 24);
	$periods[] = array(24, 0);
	
	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_product SELECT ol.Product_ID, MAX(o.Created_On) AS Last_Ordered_On FROM product AS p INNER JOIN order_line AS ol ON ol.Product_ID=p.Product_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY ol.Product_ID"));
	new DataQuery(sprintf("CREATE INDEX Product_ID ON temp_product (Product_ID)"));
	new DataQuery(sprintf("CREATE INDEX Last_Ordered_On ON temp_product (Last_Ordered_On)"));
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
		<script language="javascript" type="text/javascript" src="../ignition/js/HttpRequest.js"></script>
	</head>
	<body>
	
	<h1>Stock Report</h1>
	
	<?php foreach($periods as $period) {
		$name = sprintf('Value (%s)', ($period[1] > 0) ? sprintf('Products sold between %s and %s months', $period[0], $period[1]) : sprintf('Product sold over %s months', $period[0]));
		?>
	
		<h2><?php echo $name; ?></h2>
		<p>Stock value for all internal warehouse branches which is not Archived or Written Off.</p>

		<table width="100%" border="0" cellpadding="3" cellspacing="0">
			<tr style="background-color: #eeeeee;">
				<td style="border-bottom: 1px solid #dddddd;"><strong>Warehouse</strong></td>
				<td style="border-bottom: 1px solid #dddddd;" align="right" width="15%"><strong>Quantity</strong></td>
				<td style="border-bottom: 1px solid #dddddd;" align="right" width="15%"><strong>Cost</strong></td>
				<td style="border-bottom: 1px solid #dddddd;" align="right" width="15%"><strong>Price</strong></td>
			</tr>
			
			<?php
			$totalQuantity = 0;
			$totalCost = 0;
			$totalPrice = 0;
			
			$data = new DataQuery(sprintf("SELECT w.Warehouse_ID, w.Warehouse_Name, SUM(ws.Quantity_In_Stock) AS Stock, SUM(ws.Cost*ws.Quantity_In_Stock) AS Value FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B' INNER JOIN product AS p ON p.Product_ID=ws.Product_ID AND p.Product_Type<>'G' INNER JOIN temp_product AS ol ON ol.Product_ID=ws.Product_ID%s%s WHERE ws.Quantity_In_Stock>0 AND ws.Is_Archived='N' AND ws.Is_Writtenoff='N' GROUP BY w.Warehouse_ID", sprintf(" AND ol.Last_Ordered_On<ADDDATE(NOW(), INTERVAL -%d MONTH)", mysql_real_escape_string($period[0])), ($period[1] > 0) ? sprintf(" AND ol.Last_Ordered_On>=ADDDATE(NOW(), INTERVAL -%d MONTH)", mysql_real_escape_string($period[1])) : ''));
			while($data->Row) {
				$price = 0;
				
				$data2 = new DataQuery(sprintf("SELECT ws.Product_ID, ws.Quantity_In_Stock, IFNULL(po.Price_Offer, ppc.Price_Base_Our) as PriceCurrent
					FROM warehouse_stock AS ws
					INNER JOIN product AS p ON p.Product_ID=ws.Product_ID AND p.Product_Type<>'G'
					INNER JOIN temp_product AS ol ON ol.Product_ID=ws.Product_ID%s%s
					INNER JOIN product_prices_current AS ppc ON ppc.Product_ID = p.Product_ID
					LEFT JOIN (
						select * from product_offers as po
						where po.Offer_Start_On < NOW() and po.Offer_End_On >= NOW()
					) AS po ON po.Product_ID = p.Product_ID
					WHERE ws.Warehouse_ID=%d AND ws.Quantity_In_Stock>0 AND ws.Is_Archived='N' AND ws.Is_Writtenoff = 'N'
					GROUP BY ws.Product_ID", sprintf(" AND ol.Last_Ordered_On<ADDDATE(NOW(), INTERVAL -%d MONTH)", mysql_real_escape_string($period[0])), ($period[1] > 0) ? sprintf(" AND ol.Last_Ordered_On>=ADDDATE(NOW(), INTERVAL -%d MONTH)", mysql_real_escape_string($period[1])) : '', $data->Row['Warehouse_ID']));

				while($data2->Row) {
					$price += $data2->Row['PriceCurrent'] * $data2->Row['Quantity_In_Stock'];
					$data2->Next();	
				}
				$data2->Disconnect();
				
				$totalQuantity += $data->Row['Stock'];
				$totalCost += $data->Row['Value'];
				$totalPrice += $price;
				?>
			
				<tr>
					<td style="border-top:1px solid #dddddd;"><?php echo $data->Row['Warehouse_Name']; ?></td>
					<td style="border-top:1px solid #dddddd;" align="right"><?php echo $data->Row['Stock']; ?></td>
					<td style="border-top:1px solid #dddddd;" align="right"><?php echo number_format($data->Row['Value'], 2, '.', ','); ?></td>
					<td style="border-top:1px solid #dddddd;" align="right"><?php echo number_format($price, 2, '.', ','); ?></td>
				</tr>
				
				<?php
				$data->Next();
			}
			$data->Disconnect();
			?>
			
			<tr>
				<td style="border-top:1px solid #dddddd;"></td>
				<td style="border-top:1px solid #dddddd;" align="right"><strong><?php echo $totalQuantity; ?></strong></td>
				<td style="border-top:1px solid #dddddd;" align="right"><strong><?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
				<td style="border-top:1px solid #dddddd;" align="right"><strong><?php echo number_format($totalPrice, 2, '.', ','); ?></strong></td>
			</tr>
		</table>
		
		<?php
	} ?>
	
	<h2>Archived Stock</h2>
	<p>Stock value for all archived stock within internal warehouse branches which is not Written Off.</p>

	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr style="background-color: #eeeeee;">
			<td style="border-bottom: 1px solid #dddddd;"><strong>Warehouse</strong></td>
			<td style="border-bottom: 1px solid #dddddd;" align="right" width="15%"><strong>Quantity</strong></td>
			<td style="border-bottom: 1px solid #dddddd;" align="right" width="15%"><strong>Cost</strong></td>
			<td style="border-bottom: 1px solid #dddddd;" align="right" width="15%"><strong>Price</strong></td>
		</tr>
		
		<?php
		$totalQuantity = 0;
		$totalCost = 0;
		$totalPrice = 0;
		
		$data = new DataQuery(sprintf("SELECT w.Warehouse_ID, w.Warehouse_Name, SUM(ws.Quantity_In_Stock) AS Stock, SUM(ws.Cost*ws.Quantity_In_Stock) AS Value FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B' INNER JOIN product AS p ON p.Product_ID=ws.Product_ID AND p.Product_Type<>'G' WHERE ws.Quantity_In_Stock>0 AND ws.Is_Archived='Y' AND ws.Is_Writtenoff = 'N' GROUP BY w.Warehouse_ID"));
		while($data->Row) {
			$price = 0;
			
			$data2 = new DataQuery(sprintf("SELECT ws.Product_ID, ws.Quantity_In_Stock, IFNULL(po.Price_Offer, ppc.Price_Base_Our) as PriceCurrent
				FROM warehouse_stock AS ws
				INNER JOIN product AS p ON p.Product_ID=ws.Product_ID AND p.Product_Type<>'G'
				INNER JOIN product_prices_current AS ppc ON ppc.Product_ID = p.Product_ID
				LEFT JOIN (
					select * from product_offers as po
					where po.Offer_Start_On < NOW() and po.Offer_End_On >= NOW()
				) AS po ON po.Product_ID = p.Product_ID
				WHERE ws.Warehouse_ID=%d AND ws.Quantity_In_Stock>0 AND ws.Is_Archived='Y' AND ws.Is_Writtenoff = 'N'
				GROUP BY ws.Product_ID", $data->Row['Warehouse_ID']));
			while($data2->Row) {
				$price += $data2->Row['PriceCurrent'] * $data2->Row['Quantity_In_Stock'];	
				$data2->Next();	
			}
			$data2->Disconnect();
			
			$totalQuantity += $data->Row['Stock'];
			$totalCost += $data->Row['Value'];
			$totalPrice += $price;
			?>
		
			<tr>
				<td style="border-top:1px solid #dddddd;"><?php echo $data->Row['Warehouse_Name']; ?></td>
				<td style="border-top:1px solid #dddddd;" align="right"><?php echo $data->Row['Stock']; ?></td>
				<td style="border-top:1px solid #dddddd;" align="right"><?php echo number_format($data->Row['Value'], 2, '.', ','); ?></td>
				<td style="border-top:1px solid #dddddd;" align="right"><?php echo number_format($price, 2, '.', ','); ?></td>
			</tr>
			
			<?php
			$data->Next();
		}
		$data->Disconnect();
		?>
		
		<tr>
			<td style="border-top:1px solid #dddddd;"></td>
			<td style="border-top:1px solid #dddddd;" align="right"><strong><?php echo $totalQuantity; ?></strong></td>
			<td style="border-top:1px solid #dddddd;" align="right"><strong><?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
			<td style="border-top:1px solid #dddddd;" align="right"><strong><?php echo number_format($totalPrice, 2, '.', ','); ?></strong></td>
		</tr>
	</table>

	<h2>Written Off Stock</h2>
	<p>Values for all stock written-off within all internal warehouse branches. Including any stock archived.</p>

	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr style="background-color: #eeeeee;">
			<td style="border-bottom: 1px solid #dddddd;"><strong>Warehouse</strong></td>
			<td style="border-bottom: 1px solid #dddddd;" align="right" width="15%"><strong>Quantity</strong></td>
			<td style="border-bottom: 1px solid #dddddd;" align="right" width="15%"><strong>Cost</strong></td>
			<td style="border-bottom: 1px solid #dddddd;" align="right" width="15%"><strong>Price</strong></td>
		</tr>
		
		<?php
		$totalQuantity = 0;
		$totalCost = 0;
		$totalPrice = 0;
		
		$data = new DataQuery(sprintf("SELECT w.Warehouse_ID, w.Warehouse_Name, SUM(ws.Quantity_In_Stock) AS Stock, SUM(ws.Quantity_In_Stock * ws.Cost) AS Value
FROM warehouse_stock AS ws
INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B'
INNER JOIN product AS p ON p.Product_ID=ws.Product_ID AND p.Product_Type<>'G'
WHERE ws.Quantity_In_Stock > 0 AND ws.Is_Writtenoff = 'Y'
GROUP BY w.Warehouse_ID"));
		while($data->Row) {
			$price = 0;
			
			$data2 = new DataQuery(sprintf("SELECT ws.Product_ID, ws.Quantity_In_Stock, ppc.Price_Base_Our as Price
FROM warehouse_stock AS ws
INNER JOIN product AS p ON p.Product_ID=ws.Product_ID AND p.Product_Type<>'G'
INNER JOIN product_prices_current AS ppc ON p.Product_ID=ppc.Product_ID
WHERE ws.Warehouse_ID=%d AND ws.Quantity_In_Stock > 0 AND ws.Is_Writtenoff='Y'", $data->Row['Warehouse_ID']));
			while($data2->Row) {
				$price += ($data2->Row['Quantity_In_Stock'] * $data2->Row['Price']);
				$data2->Next();
			}
			$data2->Disconnect();
			
			$totalQuantity += $data->Row['Stock'];
			$totalCost += $data->Row['Value'];
			$totalPrice += $price;
			?>
		
			<tr>
				<td style="border-top:1px solid #dddddd;"><?php echo $data->Row['Warehouse_Name']; ?></td>
				<td style="border-top:1px solid #dddddd;" align="right"><?php echo $data->Row['Stock']; ?></td>
				<td style="border-top:1px solid #dddddd;" align="right"><?php echo number_format($data->Row['Value'], 2, '.', ','); ?></td>
				<td style="border-top:1px solid #dddddd;" align="right"><?php echo number_format($price, 2, '.', ','); ?></td>
			</tr>
			
			<?php
			$data->Next();
		}
		$data->Disconnect();
		?>
		
		<tr>
			<td style="border-top:1px solid #dddddd;"></td>
			<td style="border-top:1px solid #dddddd;" align="right"><strong><?php echo $totalQuantity; ?></strong></td>
			<td style="border-top:1px solid #dddddd;" align="right"><strong><?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
			<td style="border-top:1px solid #dddddd;" align="right"><strong><?php echo number_format($totalPrice, 2, '.', ','); ?></strong></td>
		</tr>
	</table>
		
	</body>
	</html>
	<?php
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();