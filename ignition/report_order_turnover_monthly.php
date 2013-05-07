<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReportCache.php');

$session->Secure(2);
view();
exit();

function view() {
	$reportCache = new ReportCache();
	
	if(!isset($_REQUEST['id']) || !$reportCache->Get($_REQUEST['id'])) {
		$reportCache->Report->GetByReference('orderturnovermonthly');
		
		if(!$reportCache->GetMostRecent()) {
			redirect('Location: report.php');
		}
	}
	
	$reportCache->Report->Get();
	
	$page = new Page(sprintf('<a href="reports.php">Reports</a> &gt; <a href="reports.php?action=open&id=%d">Open Report</a> &gt; %s', $reportCache->Report->ID, $reportCache->Report->Name), sprintf('Report data for the \'%s\'', cDatetime($reportCache->CreatedOn, 'shortdatetime')));
	$page->Display('header');
	
	$data = $reportCache->GetData();
	?>
	
	<br />
	<h3>Web Growth</h3>
	<p>Turnover on all web orders showing growth over same period previous year.</p>
	
	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"><strong></strong></td>
			
			<?php
			$light = true;
			
			foreach($data['WebSales'] as $year=>$dataYear) {
				if($year >= 2007)  {
					echo sprintf('<td style="border-bottom:1px solid #aaaaaa; background-color: #%s;" align="right"><strong>%s</strong></td>', $light ? 'eee' : 'transparent', $year);
					echo sprintf('<td style="border-bottom:1px solid #aaaaaa; background-color: #%s;" align="right"><strong></strong></td>', $light ? 'eee' : 'transparent');
				
					$light = !$light;
				}
			}
			?>
			
		</tr>
		
		<?php
		for($j=1; $j<=12; $j++) {
			?>
			
			<tr>
				<td><?php echo date('F', mktime(0, 0, 0, $j, 1, date('Y'))); ?></td>
			
				<?php
				$light = true;
				
				foreach($data['WebSales'] as $year=>$dataYear) {
					if($year >= 2007)  {
						$previous = isset($data['WebSales'][$year - 1]) ? $data['WebSales'][$year - 1][$j]['Data'][1] : 0;
						$growth = (($previous > 0) && ($dataYear[$j]['Data'][1] > 0)) ? (($dataYear[$j]['Data'][1] / $previous) * 100) - 100 : 0;
						
						echo sprintf('<td style="background-color: #%s;" align="right">&pound; %s</td>', $light ? 'eee' : 'transparent', number_format($dataYear[$j]['Data'][1], 2, '.', ','));
						echo sprintf('<td style="background-color: #%s;" align="right">%s %%</td>', $light ? 'eee' : 'transparent', ($growth < 0) ? sprintf('<strong style="color: #f00;">%s</strong>', number_format($growth, 2, '.', ',')) : number_format($growth, 2, '.', ','));
						
						$light = !$light;
					}
				}	
				?>
				
			</tr>
			
			<?php
		}
		?>
		
		<tr>
			<td></td>
		
			<?php
			$light = true;
			
			foreach($data['WebSales'] as $year=>$dataYear) {
				if($year >= 2007)  {
					$growth = 0;
					$total = 0;
					
					for($j=1; $j<=12; $j++) {
						if(($year != date('Y')) || ($j < date('m'))) {
							$total += $dataYear[$j]['Data'][1];
						}
					}
					
					if(isset($data['WebSales'][$year - 1])) {
						$previous = 0;
						
						for($j=1; $j<=12; $j++) {
							if(($year != date('Y')) || ($j < date('m'))) {
								$previous += $data['WebSales'][$year - 1][$j]['Data'][1];
							}
						}
					
						$growth = (($previous > 0) && ($total > 0)) ? (($total / $previous) * 100) - 100 : 0;
					}
					
					echo sprintf('<td style="background-color: #%s;" align="right"></td>', $light ? 'eee' : 'transparent');
					echo sprintf('<td style="background-color: #%s;" align="right"><strong>%s %%</strong></td>', $light ? 'eee' : 'transparent', number_format($growth, 2, '.', ','));
					
					$light = !$light;
				}
			}	
			?>
			
		</tr>
	</table>
	
	<br />
	<h3>Residual Web Growth</h3>
	<p>Turnover on all residual web orders showing growth over same period previous year.</p>
	
	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"><strong></strong></td>
			
			<?php
			$light = true;
			
			foreach($data['WebSales'] as $year=>$dataYear) {
				if($year >= 2007)  {
					echo sprintf('<td style="border-bottom:1px solid #aaaaaa; background-color: #%s;" align="right"><strong>%s</strong></td>', $light ? 'eee' : 'transparent', $year);
					echo sprintf('<td style="border-bottom:1px solid #aaaaaa; background-color: #%s;" align="right"><strong></strong></td>', $light ? 'eee' : 'transparent');
					
					$light = !$light;
				}
			}
			?>
			
		</tr>
		
		<?php
		for($j=1; $j<=12; $j++) {
			?>
			
			<tr>
				<td><?php echo date('F', mktime(0, 0, 0, $j, 1, date('Y'))); ?></td>
			
				<?php
				$light = true;
				
				foreach($data['WebSales'] as $year=>$dataYear) {
					if($year >= 2007)  {
						$previous = isset($data['WebSales'][$year - 1]) ? $data['WebSales'][$year - 1][$j]['Data'][3] : 0;
						$growth = (($previous > 0) && ($dataYear[$j]['Data'][3] > 0)) ? (($dataYear[$j]['Data'][3] / $previous) * 100) - 100 : 0;
						
						echo sprintf('<td style="background-color: #%s;" align="right">&pound; %s</td>', $light ? 'eee' : 'transparent', number_format($dataYear[$j]['Data'][3], 2, '.', ','));
						echo sprintf('<td style="background-color: #%s;" align="right">%s %%</td>', $light ? 'eee' : 'transparent', ($growth < 0) ? sprintf('<strong style="color: #f00;">%s</strong>', number_format($growth, 2, '.', ',')) : number_format($growth, 2, '.', ','));
						
						$light = !$light;
					}
				}	
				?>
				
			</tr>
			
			<?php
		}
		?>
		
		<tr>
			<td></td>
		
			<?php
			$light = true;
			
			foreach($data['WebSales'] as $year=>$dataYear) {
				if($year >= 2007)  {
					$growth = 0;
					$total = 0;
					
					for($j=1; $j<=12; $j++) {
						if(($year != date('Y')) || ($j < date('m'))) {
							$total += $dataYear[$j]['Data'][3];
						}
					}
					
					if(isset($data['WebSales'][$year - 1])) {
						$previous = 0;
						
						for($j=1; $j<=12; $j++) {
							if(($year != date('Y')) || ($j < date('m'))) {
								$previous += $data['WebSales'][$year - 1][$j]['Data'][3];
							}
						}
					
						$growth = (($previous > 0) && ($total > 0)) ? (($total / $previous) * 100) - 100 : 0;
					}
					
					echo sprintf('<td style="background-color: #%s;" align="right"></td>', $light ? 'eee' : 'transparent');
					echo sprintf('<td style="background-color: #%s;" align="right"><strong>%s %%</strong></td>', $light ? 'eee' : 'transparent', number_format($growth, 2, '.', ','));
					
					$light = !$light;
				}
			}	
			?>
			
		</tr>
	</table>
	
	<?php
	$overheads = array();
	
	$dataOverheads = new DataQuery(sprintf("SELECT o.* FROM overhead AS o INNER JOIN overhead_type AS ot ON ot.Overhead_Type_ID=o.Overhead_Type_ID AND Developer_Key LIKE 'advertising' WHERE o.Name LIKE '%%Google%%'"));
	while($dataOverheads->Row) {
		$overheads[] = $dataOverheads->Row;
		
		$dataOverheads->Next();	
	}
	$dataOverheads->Disconnect();
	?>
	
	<br />
	<h3>First Web Growth</h3>
	<p>Turnover on all first web orders showing growth over same period previous year.</p>
	
	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"><strong></strong></td>
			
			<?php
			$light = true;
			
			foreach($data['WebSales'] as $year=>$dataYear) {
				if($year >= 2007)  {
					echo sprintf('<td style="border-bottom:1px solid #aaaaaa; background-color: #%s;" align="right"><strong>%s</strong></td>', $light ? 'eee' : 'transparent', $year);
					echo sprintf('<td style="border-bottom:1px solid #aaaaaa; background-color: #%s;" align="right"><strong>Google</strong></td>', $light ? 'eee' : 'transparent');
					echo sprintf('<td style="border-bottom:1px solid #aaaaaa; background-color: #%s;" align="right"><strong></strong></td>', $light ? 'eee' : 'transparent');
					
					$light = !$light;
				}
			}
			?>
			
		</tr>
		
		<?php
		for($j=1; $j<=12; $j++) {
			?>
			
			<tr>
				<td><?php echo date('F', mktime(0, 0, 0, $j, 1, date('Y'))); ?></td>
			
				<?php
				$light = true;
				
				foreach($data['WebSales'] as $year=>$dataYear) {
					if($year >= 2007)  {
						$start = date('Y-m-d H:i:s', mktime(0, 0, 0, $j, 1, $year));
						$end = date('Y-m-d H:i:s', mktime(0, 0, 0, $j+1, 1, $year));
						
						$previous = isset($data['WebSales'][$year - 1]) ? $data['WebSales'][$year - 1][$j]['Data'][5] : 0;
						$growth = (($previous > 0) && ($dataYear[$j]['Data'][5] > 0)) ? (($dataYear[$j]['Data'][5] / $previous) * 100) - 100 : 0;
						
						$overheadAmount = 0;
						
						foreach($overheads as $overhead) {
							$days = 0;
							
							$item = array();
							$item['Start'] = $overhead['Start_Date'];
							$item['End'] = $overhead['End_Date'];

							if($item['Start'] < $start) {
								$item['Start'] = $start;
							}

							if($item['End'] > $end) {
								$item['End'] = $end;
							}
		
							$startDate = $item['Start'];
		
							while($startDate < $item['End']) {
								$days++;
								$startDate = date('Y-m-d 00:00:00', mktime(0, 0, 0, date('m', strtotime($startDate)), date('d', strtotime($startDate)) + 1, date('Y', strtotime($startDate))));
							}
		
							switch($overhead['Period']) {
								case 'Y':
									$years = 0;

									$startDate = $item['Start'];

									while($startDate < $item['End']) {
										$years++;
										$startDate = date('Y-m-d 00:00:00', mktime(0, 0, 0, date('m', strtotime($startDate)), date('d', strtotime($startDate)), date('Y', strtotime($startDate)) + 1));
									}

									$startTime = strtotime($item['Start']);
									$endTime = strtotime($item['End']);

									if($years == 1) {
										$overheadAmount += $days * ($overhead['Value'] / 365);
									} elseif($years >= 2) {
										$overheadAmount += (365 - (((int) date('d', $startTime)) - 1)) * ($overhead['Value'] / 365);
										$overheadAmount += (date('z', $endTime - 86400) + 1) * ($overhead['Value'] / 365);
									}

									if($years >= 3) {
										$years -= 2;

										for($i=0; $i<$years; $i++) {
											$overheadAmount += $overhead['Value'];
										}
									}
			
									break;
									
								case 'M':
									$months = 0;
									$startDate = $item['Start'];

									while($startDate < $item['End']) {
										$months++;
										$startDate = date('Y-m-d 00:00:00', mktime(0, 0, 0, date('m', strtotime($startDate)) + 1, date('d', strtotime($startDate)), date('Y', strtotime($startDate))));
									}

									$startTime = strtotime($item['Start']);
									$endTime = strtotime($item['End']);

									if($months == 1) {
										$overheadAmount += $days * ($overhead['Value'] / date('t', $startTime));
									} elseif($months >= 2) {
										$overheadAmount += (date('t', $startTime) - (((int) date('d', $startTime)) - 1)) * ($overhead['Value'] / date('t', $startTime));
										$overheadAmount += date('d', $endTime - 86400) * ($overhead['Value'] / date('t', $endTime - 86400));
									}

									if($months >= 3) {
										$months -= 2;

										for($i=0; $i<$months; $i++) {
											$overheadAmount += $overhead['Value'];
										}
									}
									break;
									
								case 'D':
									$overheadAmount += $days * $overhead['Value'];
									
									break;
							}
						}
						
						echo sprintf('<td style="background-color: #%s;" align="right">&pound; %s</td>', $light ? 'eee' : 'transparent', number_format($dataYear[$j]['Data'][5], 2, '.', ','));
						echo sprintf('<td style="background-color: #%s;" align="right">&pound; %s</td>', $light ? 'eee' : 'transparent', number_format($overheadAmount, 2, '.', ','));
						echo sprintf('<td style="background-color: #%s;" align="right">%s %%</td>', $light ? 'eee' : 'transparent', ($growth < 0) ? sprintf('<strong style="color: #f00;">%s</strong>', number_format($growth, 2, '.', ',')) : number_format($growth, 2, '.', ','));
						
						$light = !$light;
					}
				}	
				?>
				
			</tr>
			
			<?php
		}
		?>
		
		<tr>
			<td></td>
		
			<?php
			$light = true;
			
			foreach($data['WebSales'] as $year=>$dataYear) {
				if($year >= 2007)  {
					$growth = 0;
					$total = 0;
					
					for($j=1; $j<=12; $j++) {
						if(($year != date('Y')) || ($j < date('m'))) {
							$total += $dataYear[$j]['Data'][5];
						}
					}
					
					if(isset($data['WebSales'][$year - 1])) {
						$previous = 0;
						
						for($j=1; $j<=12; $j++) {
							if(($year != date('Y')) || ($j < date('m'))) {
								$previous += $data['WebSales'][$year - 1][$j]['Data'][5];
							}
						}
					
						$growth = (($previous > 0) && ($total > 0)) ? (($total / $previous) * 100) - 100 : 0;
					}
					
					echo sprintf('<td style="background-color: #%s;" align="right"></td>', $light ? 'eee' : 'transparent');
					echo sprintf('<td style="background-color: #%s;" align="right"></td>', $light ? 'eee' : 'transparent');
					echo sprintf('<td style="background-color: #%s;" align="right"><strong>%s %%</strong></td>', $light ? 'eee' : 'transparent', number_format($growth, 2, '.', ','));
					
					$light = !$light;
				}
			}	
			?>
			
		</tr>
	</table>
	
	<br />
	<p>Sales on all first web orders showing growth over same period previous year.</p>
	
	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"><strong></strong></td>
			
			<?php
			$light = true;
			
			foreach($data['WebSales'] as $year=>$dataYear) {
				if($year >= 2007)  {
					echo sprintf('<td style="border-bottom:1px solid #aaaaaa; background-color: #%s;" align="right"><strong>%s</strong></td>', $light ? 'eee' : 'transparent', $year);
					echo sprintf('<td style="border-bottom:1px solid #aaaaaa; background-color: #%s;" align="right"><strong>Google</strong></td>', $light ? 'eee' : 'transparent');
					echo sprintf('<td style="border-bottom:1px solid #aaaaaa; background-color: #%s;" align="right"><strong></strong></td>', $light ? 'eee' : 'transparent');
					
					$light = !$light;
				}
			}
			?>
			
		</tr>
		
		<?php
		for($j=1; $j<=12; $j++) {
			?>
			
			<tr>
				<td><?php echo date('F', mktime(0, 0, 0, $j, 1, date('Y'))); ?></td>
			
				<?php
				$light = true;
				
				foreach($data['WebSales'] as $year=>$dataYear) {
					if($year >= 2007)  {
						$start = date('Y-m-d H:i:s', mktime(0, 0, 0, $j, 1, $year));
						$end = date('Y-m-d H:i:s', mktime(0, 0, 0, $j+1, 1, $year));
						
						$previous = isset($data['WebSales'][$year - 1]) ? $data['WebSales'][$year - 1][$j]['Data'][4] : 0;
						$growth = (($previous > 0) && ($dataYear[$j]['Data'][4] > 0)) ? (($dataYear[$j]['Data'][4] / $previous) * 100) - 100 : 0;
						
						$dataGoogle = new DataQuery(sprintf("SELECT Conversions FROM google_conversion WHERE Month='%s'", $start));
						$conversions = $dataGoogle->Row['Conversions'];
						$dataGoogle->Disconnect();
						
						echo sprintf('<td style="background-color: #%s;" align="right">%d</td>', $light ? 'eee' : 'transparent', $dataYear[$j]['Data'][4]);
						echo sprintf('<td style="background-color: #%s;" align="right">%d</td>', $light ? 'eee' : 'transparent', $conversions);
						echo sprintf('<td style="background-color: #%s;" align="right">%s %%</td>', $light ? 'eee' : 'transparent', ($growth < 0) ? sprintf('<strong style="color: #f00;">%s</strong>', number_format($growth, 2, '.', ',')) : number_format($growth, 2, '.', ','));
						
						$light = !$light;
					}
				}	
				?>
				
			</tr>
			
			<?php
		}
		?>
		
		<tr>
			<td></td>
		
			<?php
			$light = true;
			
			foreach($data['WebSales'] as $year=>$dataYear) {
				if($year >= 2007)  {
					$growth = 0;
					$total = 0;
					
					for($j=1; $j<=12; $j++) {
						if(($year != date('Y')) || ($j < date('m'))) {
							$total += $dataYear[$j]['Data'][4];
						}
					}
					
					if(isset($data['WebSales'][$year - 1])) {
						$previous = 0;
						
						for($j=1; $j<=12; $j++) {
							if(($year != date('Y')) || ($j < date('m'))) {
								$previous += $data['WebSales'][$year - 1][$j]['Data'][4];
							}
						}
					
						$growth = (($previous > 0) && ($total > 0)) ? (($total / $previous) * 100) - 100 : 0;
					}
					
					echo sprintf('<td style="background-color: #%s;" align="right"></td>', $light ? 'eee' : 'transparent');
					echo sprintf('<td style="background-color: #%s;" align="right"></td>', $light ? 'eee' : 'transparent');
					echo sprintf('<td style="background-color: #%s;" align="right"><strong>%s %%</strong></td>', $light ? 'eee' : 'transparent', number_format($growth, 2, '.', ','));
					
					$light = !$light;
				}
			}	
			?>
			
		</tr>
	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}