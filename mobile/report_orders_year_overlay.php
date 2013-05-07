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
		
		<h1>Orders Year Overlay Report</h1>
		
		<?php
		$years = array();

		for($i=date('Y')-1; $i<=date('Y'); $i++) {
			$years[] = $i;	
		}

		$items = array();
		
		for($i=0; $i<=53; $i++) {
			$items[$i] = array();
			
			foreach($years as $year) {
				$items[$i][$year] = array('T' => array('Count' => 0, 'Turnover' => 0), 'W' => array('Count' => 0, 'Turnover' => 0));
			}	
		}

		foreach($years as $year) {
			$data = new DataQuery(sprintf("SELECT DATE_FORMAT(o.Created_On, '%%Y') AS Year, DATE_FORMAT(o.Created_On, '%%u') AS Week, o.Order_Prefix, COUNT(o.Order_ID) AS Orders, SUM(o.Total-o.TotalTax) AS Turnover FROM orders AS o WHERE o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY Year, Week, o.Order_Prefix HAVING Year=%d ORDER BY Week ASC", $year));
			while($data->Row) {
				$week = (int) $data->Row['Week'];
				$prefix = null;

				switch($data->Row['Order_Prefix']) {
					case 'W':	
					case 'U':
					case 'L':
					case 'M':
						$prefix = 'W';
						break;
					case 'T':
						$prefix = 'T';
						break;
				}
				
				if(!is_null($prefix)) {
					$items[$week][$year][$prefix]['Count'] += $data->Row['Orders'];
					$items[$week][$year][$prefix]['Turnover'] += $data->Row['Turnover'];
				}
				
				$data->Next();	
			}
			$data->Disconnect();
		}
		?>

		<br />
		<h2>Web &amp; Telesales (<?php echo $years[0], '-', $years[1]; ?>)</h2>
		<p>Order counts and turnover for the past two years.</p>
		
		<table width="100%" border="0">
			<tr>
				<td colspan="1">&nbsp;</td>
				
				<?php
				foreach($years as $year) {
					echo sprintf('<td colspan="2" align="center" style="border-left: 1px solid #aaaaaa; font-size: 16px;"><strong>%s</strong></td>', $year);
				}
				?>
				
				<td colspan="2" align="center" style="border-left: 1px solid #aaaaaa; font-size: 16px;"><strong>% Difference</strong></td>
				
				<?php
				foreach($years as $year) {
					echo sprintf('<td colspan="2" align="center" style="border-left: 1px solid #aaaaaa; font-size: 16px;"><strong>%s</strong></td>', $year);
				}
				?>
				
				<td colspan="2" align="center" style="border-left: 1px solid #aaaaaa; font-size: 16px;"><strong>% Difference</strong></td>
			</tr>
			<tr>
				<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;"><strong>Week</strong><br />Number</td>
				
				<?php
				foreach($years as $year) {
					?>
					
					<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong>Orders</strong><br />Web</td>
					<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;" align="right"><strong>Turnover</strong><br />Web</td>
					
					<?php
				}
				?>
				
				<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong>Orders</strong><br />Web</td>
				<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;" align="right"><strong>Turnover</strong><br />Web</td>
				
				<?php
				foreach($years as $year) {
					?>
					
					<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong>Orders</strong><br />Telesales</td>
					<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;" align="right"><strong>Turnover</strong><br />Telesales</td>
					
					<?php
				}
				?>
				
				<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong>Orders</strong><br />Telesales</td>
				<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;" align="right"><strong>Turnover</strong><br />Telesales</td>
			</tr>

			<?php
			$total = array();

			foreach($years as $year) {	
				$total[$year] = array('W' => array('Count' => '', 'Turnover' => ''), 'T' => array('Count' => '', 'Turnover' => ''));
			}
			
			foreach($items as $week=>$item) {
				if($week < date('W')) {
					foreach($item as $year=>$yearData) {
						$total[$year]['W']['Count'] += $yearData['W']['Count'];
						$total[$year]['W']['Turnover'] += $yearData['W']['Turnover'];
						$total[$year]['T']['Count'] += $yearData['T']['Count'];
						$total[$year]['T']['Turnover'] += $yearData['T']['Turnover'];
					}
				}
				
				$style = array('W' => array('Count' => '', 'Turnover' => ''), 'T' => array('Count' => '', 'Turnover' => ''));
				
				if($items[$week][$years[0]]['W']['Count'] > 0) {
					$percent = ($items[$week][$years[1]]['W']['Count'] / $items[$week][$years[0]]['W']['Count']) * 100;
					$style['W']['Count'] = ' color: #' . (($percent < 100) ? 'c00' : '0c0') . ';';
				}
				
				if($items[$week][$years[0]]['W']['Turnover'] > 0) {
					$percent = ($items[$week][$years[1]]['W']['Turnover'] / $items[$week][$years[0]]['W']['Turnover']) * 100;
					$style['W']['Turnover'] = ' color: #' . (($percent < 100) ? 'c00' : '0c0') . ';';
				}
				
				if($items[$week][$years[0]]['T']['Count'] > 0) {
					$percent = ($items[$week][$years[1]]['T']['Count'] / $items[$week][$years[0]]['T']['Count']) * 100;
					$style['T']['Count'] = ' color: #' . (($percent < 100) ? 'c00' : '0c0') . ';';
				}
				
				if($items[$week][$years[0]]['T']['Turnover'] > 0) {
					$percent = ($items[$week][$years[1]]['T']['Turnover'] / $items[$week][$years[0]]['T']['Turnover']) * 100;
					$style['T']['Turnover'] = ' color: #' . (($percent < 100) ? 'c00' : '0c0') . ';';
				}
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td style="font-size: 14px;"><?php echo $week; ?></td>
					
					<?php
					foreach($item as $year=>$yearData) {
						?>
						
						<td style="font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><?php echo $yearData['W']['Count']; ?></td>
						<td style="font-size: 14px;" align="right">&pound; <?php echo number_format(round($yearData['W']['Turnover'], 2), 2, '.', ','); ?></td>
						
						<?php
					}
					?>
					
					<td style="font-size: 14px;<?php echo $style['W']['Count']; ?> border-left: 1px solid #aaaaaa;" align="right"><?php echo number_format(round(($items[$week][$years[0]]['W']['Count'] > 0) ? ($items[$week][$years[1]]['W']['Count'] / $items[$week][$years[0]]['W']['Count']) * 100 : 0, 2), 2, '.', ','); ?> %</td>
					<td style="font-size: 14px;<?php echo $style['W']['Turnover']; ?>" align="right"><?php echo number_format(round(($items[$week][$years[0]]['W']['Turnover'] > 0) ? ($items[$week][$years[1]]['W']['Turnover'] / $items[$week][$years[0]]['W']['Turnover']) * 100 : 0, 2), 2, '.', ','); ?> %</td>
					
					<?php
					foreach($item as $year=>$yearData) {
						?>
						
						<td style="font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><?php echo $yearData['T']['Count']; ?></td>
						<td style="font-size: 14px;" align="right">&pound; <?php echo number_format(round($yearData['T']['Turnover'], 2), 2, '.', ','); ?></td>
						
						<?php
					}
					?>
					
					<td style="font-size: 14px;<?php echo $style['T']['Count']; ?> border-left: 1px solid #aaaaaa;" align="right"><?php echo number_format(round(($items[$week][$years[0]]['T']['Count'] > 0) ? ($items[$week][$years[1]]['T']['Count'] / $items[$week][$years[0]]['T']['Count']) * 100 : 0, 2), 2, '.', ','); ?> %</td>
					<td style="font-size: 14px;<?php echo $style['T']['Turnover']; ?>" align="right"><?php echo number_format(round(($items[$week][$years[0]]['T']['Turnover'] > 0) ? ($items[$week][$years[1]]['T']['Turnover'] / $items[$week][$years[0]]['T']['Turnover']) * 100 : 0, 2), 2, '.', ','); ?> %</td>
				</tr>
				
				<?php
			}
			?>
			
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>&nbsp;</td>
				
				<?php
				foreach($years as $year) {
					echo '<td style="border-left: 1px solid #aaaaaa;">&nbsp;</td>';
					echo '<td>&nbsp;</td>';
				}
				?>
				
				<td style="font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong><?php echo number_format(round(($total[$years[0]]['W']['Count'] > 0) ? ($total[$years[1]]['W']['Count'] / $total[$years[0]]['W']['Count']) * 100 : 0, 2), 2, '.', ','); ?> %</strong></td>
				<td style="font-size: 14px;" align="right"><strong><?php echo number_format(round(($total[$years[0]]['W']['Turnover'] > 0) ? ($total[$years[1]]['W']['Turnover'] / $total[$years[0]]['W']['Turnover']) * 100 : 0, 2), 2, '.', ','); ?> %</strong></td>
				
				<?php
				foreach($years as $year) {
					echo '<td style="border-left: 1px solid #aaaaaa;">&nbsp;</td>';
					echo '<td>&nbsp;</td>';
				}
				?>
				
				<td style="font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong><?php echo number_format(round(($total[$years[0]]['T']['Count'] > 0) ? ($total[$years[1]]['T']['Count'] / $total[$years[0]]['T']['Count']) * 100 : 0, 2), 2, '.', ','); ?> %</strong></td>
				<td style="font-size: 14px;" align="right"><strong><?php echo number_format(round(($total[$years[0]]['T']['Turnover'] > 0) ? ($total[$years[1]]['T']['Turnover'] / $total[$years[0]]['T']['Turnover']) * 100 : 0, 2), 2, '.', ','); ?> %</strong></td>
			</tr>	
		</table>
		<br />

		<?php
		$years = array();

		for($i=date('Y')-2; $i<=date('Y')-1; $i++) {
			$years[] = $i;	
		}

		$items = array();
		
		for($i=0; $i<=53; $i++) {
			$items[$i] = array();
			
			foreach($years as $year) {
				$items[$i][$year] = array('T' => array('Count' => 0, 'Turnover' => 0), 'W' => array('Count' => 0, 'Turnover' => 0));
			}	
		}

		foreach($years as $year) {
			$data = new DataQuery(sprintf("SELECT DATE_FORMAT(o.Created_On, '%%Y') AS Year, DATE_FORMAT(o.Created_On, '%%u') AS Week, o.Order_Prefix, COUNT(o.Order_ID) AS Orders, SUM(o.Total-o.TotalTax) AS Turnover FROM orders AS o WHERE o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY Year, Week, o.Order_Prefix HAVING Year=%d ORDER BY Week ASC", $year));
			while($data->Row) {
				$week = (int) $data->Row['Week'];
				$prefix = null;

				switch($data->Row['Order_Prefix']) {
					case 'W':	
					case 'U':
					case 'L':
					case 'M':
						$prefix = 'W';
						break;
					case 'T':
						$prefix = 'T';
						break;
				}
				
				if(!is_null($prefix)) {
					$items[$week][$year][$prefix]['Count'] += $data->Row['Orders'];
					$items[$week][$year][$prefix]['Turnover'] += $data->Row['Turnover'];
				}
				
				$data->Next();	
			}
			$data->Disconnect();
		}
		?>

		<br />
		<h2>Web &amp; Telesales (<?php echo $years[0], '-', $years[1]; ?>)</h2>
		<p>Order counts and turnover for the past two years.</p>
		
		<table width="100%" border="0">
			<tr>
				<td colspan="1">&nbsp;</td>
				
				<?php
				foreach($years as $year) {
					echo sprintf('<td colspan="2" align="center" style="border-left: 1px solid #aaaaaa; font-size: 16px;"><strong>%s</strong></td>', $year);
				}
				?>
				
				<td colspan="2" align="center" style="border-left: 1px solid #aaaaaa; font-size: 16px;"><strong>% Difference</strong></td>
				
				<?php
				foreach($years as $year) {
					echo sprintf('<td colspan="2" align="center" style="border-left: 1px solid #aaaaaa; font-size: 16px;"><strong>%s</strong></td>', $year);
				}
				?>
				
				<td colspan="2" align="center" style="border-left: 1px solid #aaaaaa; font-size: 16px;"><strong>% Difference</strong></td>
			</tr>
			<tr>
				<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;"><strong>Week</strong><br />Number</td>
				
				<?php
				foreach($years as $year) {
					?>
					
					<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong>Orders</strong><br />Web</td>
					<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;" align="right"><strong>Turnover</strong><br />Web</td>
					
					<?php
				}
				?>
				
				<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong>Orders</strong><br />Web</td>
				<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;" align="right"><strong>Turnover</strong><br />Web</td>
				
				<?php
				foreach($years as $year) {
					?>
					
					<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong>Orders</strong><br />Telesales</td>
					<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;" align="right"><strong>Turnover</strong><br />Telesales</td>
					
					<?php
				}
				?>
				
				<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong>Orders</strong><br />Telesales</td>
				<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;" align="right"><strong>Turnover</strong><br />Telesales</td>
			</tr>

			<?php
			$total = array();

			foreach($years as $year) {	
				$total[$year] = array('W' => array('Count' => '', 'Turnover' => ''), 'T' => array('Count' => '', 'Turnover' => ''));
			}
			
			foreach($items as $week=>$item) {
				if($week < date('W')) {
					foreach($item as $year=>$yearData) {
						$total[$year]['W']['Count'] += $yearData['W']['Count'];
						$total[$year]['W']['Turnover'] += $yearData['W']['Turnover'];
						$total[$year]['T']['Count'] += $yearData['T']['Count'];
						$total[$year]['T']['Turnover'] += $yearData['T']['Turnover'];
					}
				}
				
				$style = array('W' => array('Count' => '', 'Turnover' => ''), 'T' => array('Count' => '', 'Turnover' => ''));
				
				if($items[$week][$years[0]]['W']['Count'] > 0) {
					$percent = ($items[$week][$years[1]]['W']['Count'] / $items[$week][$years[0]]['W']['Count']) * 100;
					$style['W']['Count'] = ' color: #' . (($percent < 100) ? 'c00' : '0c0') . ';';
				}
				
				if($items[$week][$years[0]]['W']['Turnover'] > 0) {
					$percent = ($items[$week][$years[1]]['W']['Turnover'] / $items[$week][$years[0]]['W']['Turnover']) * 100;
					$style['W']['Turnover'] = ' color: #' . (($percent < 100) ? 'c00' : '0c0') . ';';
				}
				
				if($items[$week][$years[0]]['T']['Count'] > 0) {
					$percent = ($items[$week][$years[1]]['T']['Count'] / $items[$week][$years[0]]['T']['Count']) * 100;
					$style['T']['Count'] = ' color: #' . (($percent < 100) ? 'c00' : '0c0') . ';';
				}
				
				if($items[$week][$years[0]]['T']['Turnover'] > 0) {
					$percent = ($items[$week][$years[1]]['T']['Turnover'] / $items[$week][$years[0]]['T']['Turnover']) * 100;
					$style['T']['Turnover'] = ' color: #' . (($percent < 100) ? 'c00' : '0c0') . ';';
				}
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td style="font-size: 14px;"><?php echo $week; ?></td>
					
					<?php
					foreach($item as $year=>$yearData) {
						?>
						
						<td style="font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><?php echo $yearData['W']['Count']; ?></td>
						<td style="font-size: 14px;" align="right">&pound; <?php echo number_format(round($yearData['W']['Turnover'], 2), 2, '.', ','); ?></td>
						
						<?php
					}
					?>
					
					<td style="font-size: 14px;<?php echo $style['W']['Count']; ?> border-left: 1px solid #aaaaaa;" align="right"><?php echo number_format(round(($items[$week][$years[0]]['W']['Count'] > 0) ? ($items[$week][$years[1]]['W']['Count'] / $items[$week][$years[0]]['W']['Count']) * 100 : 0, 2), 2, '.', ','); ?> %</td>
					<td style="font-size: 14px;<?php echo $style['W']['Turnover']; ?>" align="right"><?php echo number_format(round(($items[$week][$years[0]]['W']['Turnover'] > 0) ? ($items[$week][$years[1]]['W']['Turnover'] / $items[$week][$years[0]]['W']['Turnover']) * 100 : 0, 2), 2, '.', ','); ?> %</td>
					
					<?php
					foreach($item as $year=>$yearData) {
						?>
						
						<td style="font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><?php echo $yearData['T']['Count']; ?></td>
						<td style="font-size: 14px;" align="right">&pound; <?php echo number_format(round($yearData['T']['Turnover'], 2), 2, '.', ','); ?></td>
						
						<?php
					}
					?>
					
					<td style="font-size: 14px;<?php echo $style['T']['Count']; ?> border-left: 1px solid #aaaaaa;" align="right"><?php echo number_format(round(($items[$week][$years[0]]['T']['Count'] > 0) ? ($items[$week][$years[1]]['T']['Count'] / $items[$week][$years[0]]['T']['Count']) * 100 : 0, 2), 2, '.', ','); ?> %</td>
					<td style="font-size: 14px;<?php echo $style['T']['Turnover']; ?>" align="right"><?php echo number_format(round(($items[$week][$years[0]]['T']['Turnover'] > 0) ? ($items[$week][$years[1]]['T']['Turnover'] / $items[$week][$years[0]]['T']['Turnover']) * 100 : 0, 2), 2, '.', ','); ?> %</td>
				</tr>
				
				<?php
			}
			?>
			
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>&nbsp;</td>
				
				<?php
				foreach($years as $year) {
					echo '<td style="border-left: 1px solid #aaaaaa;">&nbsp;</td>';
					echo '<td>&nbsp;</td>';
				}
				?>
				
				<td style="font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong><?php echo number_format(round(($total[$years[0]]['W']['Count'] > 0) ? ($total[$years[1]]['W']['Count'] / $total[$years[0]]['W']['Count']) * 100 : 0, 2), 2, '.', ','); ?> %</strong></td>
				<td style="font-size: 14px;" align="right"><strong><?php echo number_format(round(($total[$years[0]]['W']['Turnover'] > 0) ? ($total[$years[1]]['W']['Turnover'] / $total[$years[0]]['W']['Turnover']) * 100 : 0, 2), 2, '.', ','); ?> %</strong></td>
				
				<?php
				foreach($years as $year) {
					echo '<td style="border-left: 1px solid #aaaaaa;">&nbsp;</td>';
					echo '<td>&nbsp;</td>';
				}
				?>
				
				<td style="font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong><?php echo number_format(round(($total[$years[0]]['T']['Count'] > 0) ? ($total[$years[1]]['T']['Count'] / $total[$years[0]]['T']['Count']) * 100 : 0, 2), 2, '.', ','); ?> %</strong></td>
				<td style="font-size: 14px;" align="right"><strong><?php echo number_format(round(($total[$years[0]]['T']['Turnover'] > 0) ? ($total[$years[1]]['T']['Turnover'] / $total[$years[0]]['T']['Turnover']) * 100 : 0, 2), 2, '.', ','); ?> %</strong></td>
			</tr>	
		</table>
		
	<html>

	<?php
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();