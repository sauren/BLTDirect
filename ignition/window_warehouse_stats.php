<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$session->Secure(2);
?>
<html>
<head>
	<title>Ignition Window</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="stylesheet" href="css/i_import.css" type="text/css" />
	<script src="js/HttpRequest.js" language="javascript"></script>
	<script src="js/HttpRequestData.js" language="javascript"></script>
	<script language="javascript" type="text/javascript">
		var refresh = function(period) {
			setTimeout(function() {
				window.location.reload(true);
			}, period);
		}

		window.onload = function() {
			refresh(300000);
		}
	</script>
	<style>
		body {
			margin: 0;
			padding: 10px;
		}
	</style>
</head>
<body>

<table cellspacing="0" cellpadding="0" width="100%" border="0">
	<tr>
		<th style="text-align: left; padding: 10px 0 0 0;" colspan="2">
			<span style="font-size: 9px;">All Warehouses</span>
			<hr style="height: 1px;" />
		</th>
	</tr>

	<?php
	$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o WHERE o.Status LIKE 'Unread'"));
	?>

	<tr>
		<td nowrap="nowrap" valign="top">New Orders</td>
		<td nowrap="nowrap" valign="top" align="right"><?php echo $data->Row['Count']; ?></td>
	</tr>

	<?php
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o WHERE o.Status IN ('Unread', 'Pending', 'Purchasing', 'Packing', 'Partially Despatched')"));
	?>

	<tr>
		<td nowrap="nowrap" valign="top">Total Orders</td>
		<td nowrap="nowrap" valign="top" align="right"><?php echo $data->Row['Count']; ?></td>
	</tr>

	<?php
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT w.Warehouse_ID, w.Warehouse_Name, COUNT(DISTINCT ol.Order_Line_ID) AS OrderLines FROM warehouse AS w INNER JOIN order_line AS ol ON ol.Despatch_From_ID=w.Warehouse_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID WHERE o.Status IN ('Unread', 'Pending', 'Purchasing', 'Packing', 'Partially Despatched') AND o.Is_Declined='N' AND o.Is_Warehouse_Declined='N' AND ol.Despatch_ID=0 GROUP BY w.Warehouse_ID ORDER BY OrderLines DESC"));
	while($data->Row) {
		$stats = array();

		$data2 = new DataQuery(sprintf("SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE ol.Despatch_From_ID=%d AND ol.Despatch_ID=0 AND o.Status LIKE 'Pending' AND o.Is_Declined='N' AND o.Is_Warehouse_Declined='N'", $data->Row['Warehouse_ID']));
		$stats['Pending'] = $data2->Row['Count'];
		$data2->Disconnect();
		
		$data2 = new DataQuery(sprintf("SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE ol.Despatch_From_ID=%d AND ol.Despatch_ID=0 AND o.Status LIKE 'Purchasing' AND o.Is_Declined='N' AND o.Is_Warehouse_Declined='N'", $data->Row['Warehouse_ID']));
		$stats['Purchasing'] = $data2->Row['Count'];
		$data2->Disconnect();

		$data2 = new DataQuery(sprintf("SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE ol.Despatch_From_ID=%d AND ol.Despatch_ID=0 AND o.Status LIKE 'Packing' AND o.Is_Declined='N' AND o.Is_Warehouse_Declined='N'", $data->Row['Warehouse_ID']));
		$stats['Packing'] = $data2->Row['Count'];
		$data2->Disconnect();
		
		$data2 = new DataQuery(sprintf("SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE ol.Despatch_From_ID=%d AND ol.Despatch_ID=0 AND o.Status LIKE 'Partially Despatched' AND o.Is_Declined='N' AND o.Is_Warehouse_Declined='N'", $data->Row['Warehouse_ID']));
		$stats['Partially Despatched'] = $data2->Row['Count'];
		$data2->Disconnect();

		$data2 = new DataQuery(sprintf("SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE ol.Despatch_From_ID=%d AND ol.Despatch_ID=0 AND ol.Line_Status LIKE 'Backordered' AND o.Is_Declined='N' AND o.Is_Warehouse_Declined='N'", $data->Row['Warehouse_ID']));
		$stats['Backorder'] = $data2->Row['Count'];
		$data2->Disconnect();
		?>

		<tr>
			<th style="text-align: left; padding: 10px 0 0 0;" colspan="2">
				<span style="font-size: 9px;"><?php echo $data->Row['Warehouse_Name']; ?></span>
				<hr style="height: 1px;" />
			</th>
		</tr>

		<?php
		foreach($stats as $identifier=>$value) {
			?>

			<tr>
				<td nowrap="nowrap" valign="top"><?php echo $identifier; ?></td>
				<td nowrap="nowrap" valign="top" align="right"><?php echo $value; ?></td>
			</tr>

			<?php
		}

		$data->Next();
	}
	$data->Disconnect();
	?>

</table>

</body>
</html>
<?php
require_once('lib/common/app_footer.php');