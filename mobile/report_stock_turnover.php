<?php

require_once('lib/common/app_header.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");

$secure = isset($_SESSION['Mobile']['Secure']) ? $_SESSION['Mobile']['Secure'] : false;

if($secure) {
	$startDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m') - 3, date('d') + 1, date('Y')));
	$endDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')));

	$data = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN product AS p ON p.Product_ID=ol.Product_ID AND p.Is_Stocked='Y' WHERE o.Created_On>='%s' AND o.Created_On<'%s'", $startDate, $endDate));
	$totalStocked = $data->Row['Turnover'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN product AS p ON p.Product_ID=ol.Product_ID AND p.Is_Stocked='N' WHERE o.Created_On>='%s' AND o.Created_On<'%s'", $startDate, $endDate));
	$totalNotStocked = $data->Row['Turnover'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN product AS p ON p.Product_ID=ol.Product_ID AND p.Is_Stocked='N' AND o.SubTotal>=100 WHERE o.Created_On>='%s' AND o.Created_On<'%s'", $startDate, $endDate));
	$totalNotStockedOver100 = $data->Row['Turnover'];
	$data->Disconnect();
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
	
	<h1>Stock Turnover Report</h1>
	
	<h2>Turnover</h2>
	<p><?php echo cDatetime($startDate, 'shortdate'); ?> to <?php echo cDatetime($endDate, 'shortdate'); ?></p>

	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr style="background-color: #eeeeee;">
			<td style="border-bottom: 1px solid #dddddd;"><strong>Item</strong></td>
			<td style="border-bottom: 1px solid #dddddd;" align="right" width="15%"><strong>Total</strong></td>
		</tr>
		<tr>
			<td style="border-top:1px solid #dddddd;">Stocked Products</td>
			<td style="border-top:1px solid #dddddd;" align="right">&pound;<?php echo number_format(round($totalStocked, 2), 2, '.', ','); ?></td>
		</tr>
		<tr>
			<td style="border-top:1px solid #dddddd;">Not Stocked Products</td>
			<td style="border-top:1px solid #dddddd;" align="right">&pound;<?php echo number_format(round($totalNotStocked, 2), 2, '.', ','); ?></td>
		</tr>
		<tr>
			<td style="border-top:1px solid #dddddd;">Not Stocked Products (Over &pound;100)</td>
			<td style="border-top:1px solid #dddddd;" align="right">&pound;<?php echo number_format(round($totalNotStockedOver100, 2), 2, '.', ','); ?></td>
		</tr>
	</table>
		
	</body>
	</html>
	<?php
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();