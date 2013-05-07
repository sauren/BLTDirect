<?php
ini_set('max_execution_time', '1800');

require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");

$secure = isset($_SESSION['Mobile']['Secure']) ? $_SESSION['Mobile']['Secure'] : false;

if($secure) {
	$start = date('Y-m-d 00:00:00');
	$end = date('Y-m-d H:i:s');

	$users = array(8, 40, 45);
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

	<h1>Order Markup Report</h1>
	<p style="margin-bottom: 0;"><?php echo cDatetime($start, 'longdatetime'); ?> - <?php echo cDatetime($end, 'longdatetime'); ?></p>

	<?php
	foreach($users as $userId) {
		$user = new User($userId);
		?>

		<br />
        <h3><?php echo trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)); ?></h3>
		<p>Listing markup on discounted products ordered by this user for the chosen period.</p>

        <table width="100%" border="0" cellpadding="3" cellspacing="0">
			<tr style="background-color:#eeeeee;">
				<td style="border-bottom: 1px solid #aaaaaa;" nowrap="nowrap"><strong>Order</strong></td>
				<td style="border-bottom: 1px solid #aaaaaa;" nowrap="nowrap"><strong>Order Date</strong></td>
				<td style="border-bottom: 1px solid #aaaaaa;" nowrap="nowrap" align="right"><strong>Order Total</strong></td>
				<td style="border-bottom: 1px solid #aaaaaa;" nowrap="nowrap"><strong>Customer</strong></td>
				<td style="border-bottom: 1px solid #aaaaaa;" nowrap="nowrap"><strong>Product ID</strong></td>
				<td style="border-bottom: 1px solid #aaaaaa;" nowrap="nowrap"><strong>Product</strong></td>
				<td style="border-bottom: 1px solid #aaaaaa;" nowrap="nowrap" align="right"><strong>Qty</strong></td>
				<td style="border-bottom: 1px solid #aaaaaa;" nowrap="nowrap" align="right"><strong>Discount</strong></td>
				<td style="border-bottom: 1px solid #aaaaaa;" nowrap="nowrap" align="right"><strong>Total</strong></td>
				<td style="border-bottom: 1px solid #aaaaaa;" nowrap="nowrap" align="right"><strong>Profit</strong><br />Amount</td>
				<td style="border-bottom: 1px solid #aaaaaa;" nowrap="nowrap" align="right"><strong>Profit</strong><br />Percentage</td>
				<td style="border-bottom: 1px solid #aaaaaa;" nowrap="nowrap" align="right"><strong>Markup</strong><br />Original</td>
				<td style="border-bottom: 1px solid #aaaaaa;" nowrap="nowrap" align="right"><strong>Markup</strong><br />Post Discount</td>
			</tr>

			<?php
			$data = new DataQuery(sprintf("SELECT o.Order_Prefix, o.Order_ID, o.SubTotal, CONCAT_WS(' ', o.Billing_First_Name, o.Billing_Last_Name) AS Customer, DATE(o.Created_On) AS Created_Date, p.Product_ID, p.Product_Title, ol.Quantity, ol.Line_Discount, ol.Line_Total, ol.Line_Total-ol.Line_Discount AS Total, ol.Cost*ol.Quantity AS Cost FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN product AS p ON p.Product_ID=ol.Product_ID WHERE o.Created_By=%d AND o.Created_On>'%s' AND o.Created_On<'%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')' AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' ORDER BY o.Order_ID ASC", mysql_real_escape_string($userId), $start, $end));

			if($data->TotalRows > 0) {
				$averageDiscount = 0;
				$averageMarkupOriginal = 0;
				$averageMarkupDiscount = 0;

				$totalAmount = 0;
				$totalProfit = 0;

				while($data->Row) {
	                $averageDiscount += $data->Row['Line_Discount'];
	                $averageMarkupOriginal += (($data->Row['Line_Total'] - $data->Row['Cost']) / $data->Row['Cost']) * 100;
					$averageMarkupDiscount += (($data->Row['Total'] - $data->Row['Cost']) / $data->Row['Cost']) * 100;

                    $totalAmount += $data->Row['Total'];
					$totalProfit += $data->Row['Total'] - $data->Row['Cost'];
					?>

					<tr>
						<td><?php echo $data->Row['Order_Prefix'].$data->Row['Order_ID']; ?></td>
						<td><?php echo $data->Row['Created_Date']; ?></td>
						<td align="right">&pound;<?php echo number_format(round($data->Row['SubTotal'], 2), 2, '.', ','); ?></td>
						<td><?php echo $data->Row['Customer'] ?></td>
						<td><?php echo $data->Row['Product_ID']; ?></td>
						<td><?php echo strip_tags($data->Row['Product_Title']); ?></td>
						<td align="right"><?php echo $data->Row['Quantity']; ?></td>
						<td align="right">&pound;<?php echo number_format(round($data->Row['Line_Discount'], 2), 2, '.', ','); ?></td>
						<td align="right">&pound;<?php echo number_format(round($data->Row['Total'], 2), 2, '.', ','); ?></td>
						<td align="right">&pound;<?php echo number_format(round($data->Row['Total'] - $data->Row['Cost'], 2), 2, '.', ','); ?></td>
						<td align="right"><?php echo number_format(round((($data->Row['Total'] - $data->Row['Cost']) / $data->Row['Total']) * 100, 2), 2, '.', ','); ?>%</td>
						<td align="right"><?php echo number_format(round((($data->Row['Line_Total'] - $data->Row['Cost']) / $data->Row['Cost']) * 100, 2), 2, '.', ','); ?>%</td>
						<td align="right"><?php echo number_format(round((($data->Row['Total'] - $data->Row['Cost']) / $data->Row['Cost']) * 100, 2), 2, '.', ','); ?>%</td>
					</tr>

					<?php
					$data->Next();
				}
				?>

	            <tr style="background-color:#eeeeee;">
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td align="right"><strong>&pound;<?php echo number_format(round($averageDiscount / $data->TotalRows, 2), 2, '.', ','); ?></strong></td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td align="right"><strong><?php echo number_format(round($averageMarkupOriginal / $data->TotalRows, 2), 2, '.', ','); ?>%</strong></td>
					<td align="right"><strong><?php echo number_format(round($averageMarkupDiscount / $data->TotalRows, 2), 2, '.', ','); ?>%</strong></td>
				</tr>
                <tr style="background-color:#eeeeee;">
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td align="right"><strong>&pound;<?php echo number_format(round($totalAmount, 2), 2, '.', ','); ?></strong></td>
					<td align="right"><strong>&pound;<?php echo number_format(round($totalProfit, 2), 2, '.', ','); ?></strong></td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
				</tr>

				<?php
			} else {
				?>

	            <tr>
					<td colspan="13" align="center">There are no items available for viewing.</td>
				</tr>

				<?php
			}
			$data->Disconnect();
			?>

		</table>

		<?php
	}
	?>

	</body>
	</html>
	<?php
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();