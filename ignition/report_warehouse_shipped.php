<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReportCache.php');

$session->Secure(2);
view();
exit();

function view() {
	$reportCache = new ReportCache();
	
	if(!isset($_REQUEST['id']) || !$reportCache->Get($_REQUEST['id'])) {
		$reportCache->Report->GetByReference('warehouseshipped');
		
		if(!$reportCache->GetMostRecent()) {
			redirect('Location: report.php');
		}
	}
	
	$reportCache->Report->Get();
	
	$page = new Page(sprintf('<a href="reports.php">Reports</a> &gt; <a href="reports.php?action=open&id=%d">Open Report</a> &gt; %s', $reportCache->Report->ID, $reportCache->Report->Name), sprintf('Report data for the \'%s\'', cDatetime($reportCache->CreatedOn, 'shortdatetime')));
	$page->Display('header');

	$data = $reportCache->GetData();

	if(isset($data['Items'])) {
		$data = array('12' => $data);
	}
	
	foreach($data as $month=>$monthData) {
		?>
		
		<br />
		<h3>Branch Shipped Orders - <?php echo $month; ?> Month(s)</h3>
		<p>Percentage of orders shipped from our branches for the period <?php echo cDatetime($monthData['Start'], 'shortdate'); ?> to <?php echo cDatetime($monthData['End'], 'shortdate'); ?> against the projected percentile if the top number of products were stocked.</p>
		
		<table width="100%" border="0">
			<tr>
				<td style="border-bottom:1px solid #aaaaaa;" align="left"><strong>Item</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Products Stocked</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Orders Shipped</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Percentage</strong></td>
			</tr>
		
			<?php
			foreach($monthData['Items'] as $productCount=>$item) {
				if(!isset($item['Visible']) || isset($item['Visible']) && ($item['Visible'])) {
					$percentage = '';
					
					if($productCount > 0) {
						if(isset($monthData['Items'][$item['ProductsStocked']])) {
							$percentage .= number_format(($monthData['Items'][$item['ProductsStocked']]['OrdersShipped'] / $monthData['TotalOrders']) * 100, 2, '.', ',');
							$percentage .= '%';
							$percentage .= ' / ';
						}
					}
					
					$percentage .= number_format(($item['OrdersShipped'] / $monthData['TotalOrders']) * 100, 2, '.', ',');
					$percentage .= '%';
					?>
			
					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td align="left"><?php echo ($productCount > 0) ? sprintf('Projected Branch Shipped (Top %d Products)', $productCount) : 'Actual Branch Shipped'; ?></td>
						<td align="right"><?php echo ($productCount > 0) ? sprintf('%d / %d', $item['ProductsStocked'], $productCount) : ''; ?></td>
						<td align="right"><?php echo $item['OrdersShipped']; ?></td>
						<td align="right"><?php echo $percentage; ?></td>
					</tr>
			
					<?php
				}
			}
			?>
		
		</table>
		<br />
	
		<?php
	}
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}