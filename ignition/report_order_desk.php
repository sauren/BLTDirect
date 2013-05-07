<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReportCache.php');

$session->Secure(2);
view();
exit();

function view() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/chart/libchart.php');
	
	$reportCache = new ReportCache();
	
	if(!isset($_REQUEST['id']) || !$reportCache->Get($_REQUEST['id'])) {
		$reportCache->Report->GetByReference('orderdesk');
		
		if(!$reportCache->GetMostRecent()) {
			redirect('Location: report.php');
		}
	}
	
	$reportCache->Report->Get();
	
	$page = new Page(sprintf('<a href="reports.php">Reports</a> &gt; <a href="reports.php?action=open&id=%d">Open Report</a> &gt; %s', $reportCache->Report->ID, $reportCache->Report->Name), sprintf('Report data for the \'%s\'', cDatetime($reportCache->CreatedOn, 'shortdatetime')));
	$page->Display('header');
	
	$data = $reportCache->GetData();
	
	$query = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM orders WHERE Created_On>='%s' AND Created_On<'%s'", date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($reportCache->CreatedOn)), date('d', strtotime($reportCache->CreatedOn)), date('Y', strtotime($reportCache->CreatedOn)))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($reportCache->CreatedOn)), date('d', strtotime($reportCache->CreatedOn)) + 1, date('Y', strtotime($reportCache->CreatedOn))))));
	$totalOrders = $query->Row['Count'];
	$query->Disconnect();
	?>
	
	<br />
	<h3>Order Statistics</h3>
	<p>Total number of orders and pending order statistics for this period.</p>
	
	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;" align="left"><strong>Item</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Value</strong></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td align="left">Date Executed</td>
			<td align="right"><?php echo date('l jS F Y', strtotime($reportCache->CreatedOn)); ?></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td align="left">Time Captured</td>
			<td align="right"><?php echo date('g:i A', strtotime($reportCache->CreatedOn)); ?></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td align="left">Pending Orders</td>
			<td align="right"><?php echo $data['PendingOrders']; ?></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td align="left">Orders Created</td>
			<td align="right"><?php echo $totalOrders; ?></td>
		</tr>
	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}