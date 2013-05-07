<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReportCache.php');

$session->Secure(2);
view();
exit();

function view() {
	$reportCache = new ReportCache();
	
	if(!isset($_REQUEST['id']) || !$reportCache->Get($_REQUEST['id'])) {
		$reportCache->Report->GetByReference('customerreorders');
		
		if(!$reportCache->GetMostRecent()) {
			redirect('Location: report.php');
		}
	}
	
	$reportCache->Report->Get();
	
	$page = new Page(sprintf('<a href="reports.php">Reports</a> &gt; <a href="reports.php?action=open&id=%d">Open Report</a> &gt; %s', $reportCache->Report->ID, $reportCache->Report->Name), sprintf('Report data for the \'%s\'', cDatetime($reportCache->CreatedOn, 'shortdatetime')));
	$page->Display('header');

	$data = $reportCache->GetData();
	
	$period = array();
	$period[] = array('Range' => '0-30', 'Frequency' => 0);
	$period[] = array('Range' => '30-60', 'Frequency' => 0);
	$period[] = array('Range' => '60-90', 'Frequency' => 0);
	$period[] = array('Range' => '90-121', 'Frequency' => 0);
	$period[] = array('Range' => '121-182', 'Frequency' => 0);
	$period[] = array('Range' => '182-365', 'Frequency' => 0);
	$period[] = array('Range' => '365-547', 'Frequency' => 0);
	$period[] = array('Range' => '547-730', 'Frequency' => 0);
	$period[] = array('Range' => '730-912', 'Frequency' => 0);
	$period[] = array('Range' => '912-1095', 'Frequency' => 0);
	$period[] = array('Range' => '1095-0', 'Frequency' => 0);
	
	for($i=0; $i<count($period); $i++) {
		$parts = explode('-', $period[$i]['Range']);
	
		$start = $parts[0] * 86400;
		$end = $parts[1] * 86400;
	
		foreach($data as $customerItem) {
			if(($end == 0) && ($customerItem['AverageTimestamp'] >= $start)) {
				$period[$i]['Frequency'] += 1;
			} elseif(($customerItem['AverageTimestamp'] >= $start) && ($customerItem['AverageTimestamp'] < $end)) {
				$period[$i]['Frequency'] += 1;
			}
		}
	}
	?>
	
	<br />
	<h3>Reorder Periods</h3>
	<p>Percentage of customers who re-order between the below periods.</p>
	
	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;" align="left"><strong>Period</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Frequency</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Percentage</strong></td>
		</tr>
	
		<?php
		for($i=0; $i<count($period); $i++) {
			?>
	
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td align="left"><?php echo $period[$i]['Range']; ?></td>
				<td align="right"><?php echo $period[$i]['Frequency']; ?></td>
				<td align="right"><?php echo number_format(($period[$i]['Frequency'] / count($data)) * 100, 2, '.', ','); ?>%</td>
			</tr>
	
			<?php
		}
		?>
	
	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}