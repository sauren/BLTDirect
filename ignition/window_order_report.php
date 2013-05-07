<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$session->Secure(2);
$session->User->Get();

if($session->User->IsPacker == 'Y') {
	exit;
}

$connections = getSyncConnections();

$start = date('Y-m-d 00:00:00');
$end = date('Y-m-d H:i:s');

$orderTypes = array();
$orderTypes['W'] = 'Website<br /><span style="color: #999;">bltdirect.com</span>';
$orderTypes['U'] = 'Website<br /><span style="color: #999;">bltdirect.co.uk</span>';
$orderTypes['L'] = 'Website<br /><span style="color: #999;">lightbulbsuk.co.uk</span>';
$orderTypes['M'] = 'Mobile';
$orderTypes['T'] = 'Telesales';
$orderTypes['F'] = 'Fax';
$orderTypes['E'] = 'Email';
$orderTypes['N'] = 'Not Received';
$orderTypes['R'] = 'Return';
$orderTypes['B'] = 'Broken';
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
		<th style="text-align: left; padding: 10px 0 0 0;">
			<span style="font-size: 9px;">Order Type</span>
			<hr style="height: 1px;" />
		</th>
		<th style="text-align: right; padding: 10px 0 0 0;">
			<span style="font-size: 9px;">#</span>
			<hr style="height: 1px;" />
		</th>
		<th style="text-align: right; padding: 10px 0 0 0;">
			<span style="font-size: 9px;">Gross</span>
			<hr style="height: 1px;" />
		</th>
	</tr>

	<?php
	$totalOrders = 0;
	$totalGross = 0;

	for($i=0; $i<count($connections); $i++) {
		$data = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS Count, o.Order_Prefix, SUM(o.Total) AS Total FROM orders AS o WHERE o.Created_On BETWEEN '%s' AND '%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N' GROUP BY Order_Prefix", mysql_real_escape_string($start), mysql_real_escape_string($end)), $connections[$i]['Connection']);
		while ($data->Row) {
			$totalOrders += $data->Row['Count'];
			$totalGross += $data->Row['Total'];
			?>

			<tr>
				<td nowrap="nowrap" valign="top"><?php echo $orderTypes[$data->Row['Order_Prefix']]; ?></td>
				<td nowrap="nowrap" valign="top" align="right"><?php echo $data->Row['Count']; ?></td>
				<td nowrap="nowrap" valign="top" align="right"><?php echo number_format($data->Row['Total'], 2, '.', ','); ?></td>
			</tr>

			<?php
			$data->Next();
		}
		$data->Disconnect();
	}
	?>

	<tr>
		<td nowrap="nowrap" valign="top">&nbsp;</td>
		<td nowrap="nowrap" valign="top" align="right"><strong><?php echo $totalOrders; ?></strong></td>
		<td nowrap="nowrap" valign="top" align="right"><strong><?php echo number_format($totalGross, 2, '.', ','); ?></strong></td>
	</tr>
</table>
<br />

<table cellspacing="0" cellpadding="0" width="100%" border="0">
	<tr>
		<th style="text-align: left; padding: 10px 0 0 0;">
			<span style="font-size: 9px;">Estimated</span>
			<hr style="height: 1px;" />
		</th>
		<th style="text-align: right; padding: 10px 0 0 0;">
			<span style="font-size: 9px;">#</span>
			<hr style="height: 1px;" />
		</th>
		<th style="text-align: right; padding: 10px 0 0 0;">
			<span style="font-size: 9px;">Gross</span>
			<hr style="height: 1px;" />
		</th>
	</tr>

	<?php
	$data = new DataQuery(sprintf("SELECT COUNT(Order_ID) AS Count, SUM(Total) AS Total FROM orders WHERE Created_On>='%s' AND Created_On<'%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N'", date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($start)), 1, date('Y', strtotime($start)))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($start)), date('d', strtotime($start)), date('Y', strtotime($start))))));
	$totalOrders = $data->Row['Count'];
	$totalTotal = $data->Row['Total'];
	$data->Disconnect();
	?>

	<tr>
		<td nowrap="nowrap" valign="top">Monthly</td>
		<td nowrap="nowrap" valign="top" align="right"><?php echo (date('d') > 1) ? number_format(($totalOrders / (date('d') - 1)) * date('t'), 0, '.', '') : 0; ?></td>
		<td nowrap="nowrap" valign="top" align="right"><?php echo (date('d') > 1) ? number_format(($totalTotal / (date('d') - 1)) * date('t'), 2, '.', ',') : 0.00; ?></td>
	</tr>

	<?php
	?>
</table>
<br />

<table cellspacing="0" cellpadding="0" width="100%" border="0">
	<tr>
		<th style="text-align: left; padding: 10px 0 0 0;">
			<span style="font-size: 9px;">Name</span>
			<hr style="height: 1px;" />
		</th>
		<th style="text-align: right; padding: 10px 0 0 0;">
			<span style="font-size: 9px;">#</span>
			<hr style="height: 1px;" />
		</th>
		<th style="text-align: right; padding: 10px 0 0 0;">
			<span style="font-size: 9px;">Gross</span>
			<hr style="height: 1px;" />
		</th>
	</tr>

	<?php
	$totalOrders = 0;
	$totalGross = 0;
	$data = new DataQuery(sprintf("SELECT COUNT(Order_ID) AS Count, Created_By, SUM(Total) AS Total FROM orders WHERE Created_By>0 AND Created_On BETWEEN '%s' AND '%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix='T' GROUP BY Created_By", mysql_real_escape_string($start), mysql_real_escape_string($end)));

	while($data->Row) {
		$user = new User($data->Row['Created_By']);

		$totalOrders += $data->Row['Count'];
		$totalGross += $data->Row['Total'];
		?>

		<tr>
			<td nowrap="nowrap" valign="top"><?php echo trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)); ?></td>
			<td nowrap="nowrap" valign="top" align="right"><?php echo $data->Row['Count']; ?></td>
			<td nowrap="nowrap" valign="top" align="right"><?php echo number_format($data->Row['Total'], 2, '.', ','); ?></td>
		</tr>

		<?php
		$data->Next();
	}
	$data->Disconnect();
	?>

	<tr>
		<td nowrap="nowrap" valign="top">&nbsp;</td>
		<td nowrap="nowrap" valign="top" align="right"><strong><?php echo $totalOrders; ?></strong></td>
		<td nowrap="nowrap" valign="top" align="right"><strong><?php echo number_format($totalGross, 2, '.', ','); ?></strong></td>
	</tr>
</table>

</body>
</html>
<?php
require_once('lib/common/app_footer.php');
?>