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
		
		<h1>Sales Rep Orders Year Overlay Report</h1>
		
		<?php
		$years = array();

		for($i=date('Y')-1; $i<=date('Y'); $i++) {
			$years[] = $i;	
		}

		$items = array();
		
		for($i=0; $i<=53; $i++) {
			$items[$i] = array();
			
			foreach($years as $year) {
				$items[$i][$year] = array(0 => array('Count' => 0, 'Turnover' => 0), 8 => array('Count' => 0, 'Turnover' => 0), 40 => array('Count' => 0, 'Turnover' => 0), 45 => array('Count' => 0, 'Turnover' => 0));
			}	
		}

		foreach($years as $year) {
			$data = new DataQuery(sprintf("SELECT DATE_FORMAT(o.Created_On, '%%Y') AS Year, DATE_FORMAT(o.Created_On, '%%u') AS Week, o.Created_By, COUNT(o.Order_ID) AS Orders, SUM(o.Total-o.TotalTax) AS Turnover FROM orders AS o WHERE o.Order_Prefix='T' AND o.Status NOT IN ('Cancelled', 'Unauthenticated', 'Incomplete') AND o.Created_By>0 GROUP BY Year, Week, o.Created_By HAVING Year=%d ORDER BY Week ASC", $year));
			while($data->Row) {
				$week = (int) $data->Row['Week'];

				$userId = $data->Row['Created_By'];

				if(!isset($items[$week][$year][$userId])) {
					$userId = 0;
				}
			
				if(isset($items[$week][$year][$userId])) {
					$items[$week][$year][$userId]['Count'] += $data->Row['Orders'];
					$items[$week][$year][$userId]['Turnover'] += $data->Row['Turnover'];
				}

				$data->Next();
			}
			$data->Disconnect();
		}
		?>

		<br />
		<h2>Alex &amp; Marie (<?php echo $years[0], '-', $years[1]; ?>)</h2>
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

				<?php
				foreach($years as $year) {
					echo sprintf('<td colspan="2" align="center" style="border-left: 1px solid #aaaaaa; font-size: 16px;"><strong>%s</strong></td>', $year);
				}
				?>
			</tr>
			<tr>
				<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;"><strong>Week</strong><br />Number</td>
				
				<?php
				foreach($years as $year) {
					?>
					
					<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong>Orders</strong><br />Marie</td>
					<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;" align="right"><strong>Turnover</strong><br />Marie</td>
					
					<?php
				}
				?>
				
				<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong>Orders</strong><br />Marie</td>
				<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;" align="right"><strong>Turnover</strong><br />Marie</td>
				
				<?php
				foreach($years as $year) {
					?>
					
					<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong>Orders</strong><br />Alex</td>
					<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;" align="right"><strong>Turnover</strong><br />Alex</td>
					
					<?php
				}
				?>
				
				<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong>Orders</strong><br />Alex</td>
				<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;" align="right"><strong>Turnover</strong><br />Alex</td>

				<?php
				foreach($years as $year) {
					?>
					
					<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong>Orders</strong><br />Marc</td>
					<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;" align="right"><strong>Orders</strong><br />Others</td>
					
					<?php
				}
				?>
			</tr>

			<?php
			$total = array();

			foreach($years as $year) {	
				$total[$year] = array(8 => array('Count' => '', 'Turnover' => ''), 40 => array('Count' => '', 'Turnover' => ''));
			}
			
			foreach($items as $week=>$item) {
				if($week < date(8)) {
					foreach($item as $year=>$yearData) {
						$total[$year][8]['Count'] += $yearData[8]['Count'];
						$total[$year][8]['Turnover'] += $yearData[8]['Turnover'];
						$total[$year][40]['Count'] += $yearData[40]['Count'];
						$total[$year][40]['Turnover'] += $yearData[40]['Turnover'];
					}
				}
				
				$style = array(8 => array('Count' => '', 'Turnover' => ''), 40 => array('Count' => '', 'Turnover' => ''));
				
				if($items[$week][$years[0]][8]['Count'] > 0) {
					$percent = ($items[$week][$years[1]][8]['Count'] / $items[$week][$years[0]][8]['Count']) * 100;
					$style[8]['Count'] = ' color: #' . (($percent < 100) ? 'c00' : '0c0') . ';';
				}
				
				if($items[$week][$years[0]][8]['Turnover'] > 0) {
					$percent = ($items[$week][$years[1]][8]['Turnover'] / $items[$week][$years[0]][8]['Turnover']) * 100;
					$style[8]['Turnover'] = ' color: #' . (($percent < 100) ? 'c00' : '0c0') . ';';
				}
				
				if($items[$week][$years[0]][40]['Count'] > 0) {
					$percent = ($items[$week][$years[1]][40]['Count'] / $items[$week][$years[0]][40]['Count']) * 100;
					$style[40]['Count'] = ' color: #' . (($percent < 100) ? 'c00' : '0c0') . ';';
				}
				
				if($items[$week][$years[0]][40]['Turnover'] > 0) {
					$percent = ($items[$week][$years[1]][40]['Turnover'] / $items[$week][$years[0]][40]['Turnover']) * 100;
					$style[40]['Turnover'] = ' color: #' . (($percent < 100) ? 'c00' : '0c0') . ';';
				}
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td style="font-size: 14px;"><?php echo $week; ?></td>
					
					<?php
					foreach($item as $year=>$yearData) {
						?>
						
						<td style="font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><?php echo $yearData[8]['Count']; ?></td>
						<td style="font-size: 14px;" align="right">&pound; <?php echo number_format(round($yearData[8]['Turnover'], 2), 2, '.', ','); ?></td>
						
						<?php
					}
					?>
					
					<td style="font-size: 14px;<?php echo $style[8]['Count']; ?> border-left: 1px solid #aaaaaa;" align="right"><?php echo number_format(round(($items[$week][$years[0]][8]['Count'] > 0) ? ($items[$week][$years[1]][8]['Count'] / $items[$week][$years[0]][8]['Count']) * 100 : 0, 2), 2, '.', ','); ?> %</td>
					<td style="font-size: 14px;<?php echo $style[8]['Turnover']; ?>" align="right"><?php echo number_format(round(($items[$week][$years[0]][8]['Turnover'] > 0) ? ($items[$week][$years[1]][8]['Turnover'] / $items[$week][$years[0]][8]['Turnover']) * 100 : 0, 2), 2, '.', ','); ?> %</td>
					
					<?php
					foreach($item as $year=>$yearData) {
						?>
						
						<td style="font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><?php echo $yearData[40]['Count']; ?></td>
						<td style="font-size: 14px;" align="right">&pound; <?php echo number_format(round($yearData[40]['Turnover'], 2), 2, '.', ','); ?></td>
						
						<?php
					}
					?>
					
					<td style="font-size: 14px;<?php echo $style[40]['Count']; ?> border-left: 1px solid #aaaaaa;" align="right"><?php echo number_format(round(($items[$week][$years[0]][40]['Count'] > 0) ? ($items[$week][$years[1]][40]['Count'] / $items[$week][$years[0]][40]['Count']) * 100 : 0, 2), 2, '.', ','); ?> %</td>
					<td style="font-size: 14px;<?php echo $style[40]['Turnover']; ?>" align="right"><?php echo number_format(round(($items[$week][$years[0]][40]['Turnover'] > 0) ? ($items[$week][$years[1]][40]['Turnover'] / $items[$week][$years[0]][40]['Turnover']) * 100 : 0, 2), 2, '.', ','); ?> %</td>

					<?php
					foreach($item as $year=>$yearData) {
						?>
						
						<td style="font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><?php echo $yearData[45]['Count']; ?></td>
						<td style="font-size: 14px;" align="right"><?php echo $yearData[0]['Count']; ?></td>
						<?php
					}
					?>
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
				
				<td style="font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong><?php echo number_format(round(($total[$years[0]][8]['Count'] > 0) ? ($total[$years[1]][8]['Count'] / $total[$years[0]][8]['Count']) * 100 : 0, 2), 2, '.', ','); ?> %</strong></td>
				<td style="font-size: 14px;" align="right"><strong><?php echo number_format(round(($total[$years[0]][8]['Turnover'] > 0) ? ($total[$years[1]][8]['Turnover'] / $total[$years[0]][8]['Turnover']) * 100 : 0, 2), 2, '.', ','); ?> %</strong></td>
				
				<?php
				foreach($years as $year) {
					echo '<td style="border-left: 1px solid #aaaaaa;">&nbsp;</td>';
					echo '<td>&nbsp;</td>';
				}
				?>
				
				<td style="font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong><?php echo number_format(round(($total[$years[0]][40]['Count'] > 0) ? ($total[$years[1]][40]['Count'] / $total[$years[0]][40]['Count']) * 100 : 0, 2), 2, '.', ','); ?> %</strong></td>
				<td style="font-size: 14px;" align="right"><strong><?php echo number_format(round(($total[$years[0]][40]['Turnover'] > 0) ? ($total[$years[1]][40]['Turnover'] / $total[$years[0]][40]['Turnover']) * 100 : 0, 2), 2, '.', ','); ?> %</strong></td>

				<?php
				foreach($years as $year) {
					echo '<td style="border-left: 1px solid #aaaaaa;">&nbsp;</td>';
					echo '<td>&nbsp;</td>';
				}
				?>
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
				$items[$i][$year] = array(0 => array('Count' => 0, 'Turnover' => 0), 8 => array('Count' => 0, 'Turnover' => 0), 40 => array('Count' => 0, 'Turnover' => 0), 45 => array('Count' => 0, 'Turnover' => 0));
			}	
		}

		foreach($years as $year) {
			$data = new DataQuery(sprintf("SELECT DATE_FORMAT(o.Created_On, '%%Y') AS Year, DATE_FORMAT(o.Created_On, '%%u') AS Week, o.Created_By, COUNT(o.Order_ID) AS Orders, SUM(o.Total-o.TotalTax) AS Turnover FROM orders AS o WHERE o.Order_Prefix='T' AND o.Status NOT IN ('Cancelled', 'Unauthenticated', 'Incomplete') AND o.Created_By>0 GROUP BY Year, Week, o.Created_By HAVING Year=%d ORDER BY Week ASC", $year));
			while($data->Row) {
				$week = (int) $data->Row['Week'];

				$userId = $data->Row['Created_By'];

				if(!isset($items[$week][$year][$userId])) {
					$userId = 0;
				}
			
				if(isset($items[$week][$year][$userId])) {
					$items[$week][$year][$userId]['Count'] += $data->Row['Orders'];
					$items[$week][$year][$userId]['Turnover'] += $data->Row['Turnover'];
				}

				$data->Next();
			}
			$data->Disconnect();
		}
		?>

		<br />
		<h2>Alex &amp; Marie (<?php echo $years[0], '-', $years[1]; ?>)</h2>
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

				<?php
				foreach($years as $year) {
					echo sprintf('<td colspan="2" align="center" style="border-left: 1px solid #aaaaaa; font-size: 16px;"><strong>%s</strong></td>', $year);
				}
				?>
			</tr>
			<tr>
				<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;"><strong>Week</strong><br />Number</td>
				
				<?php
				foreach($years as $year) {
					?>
					
					<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong>Orders</strong><br />Marie</td>
					<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;" align="right"><strong>Turnover</strong><br />Marie</td>
					
					<?php
				}
				?>
				
				<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong>Orders</strong><br />Marie</td>
				<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;" align="right"><strong>Turnover</strong><br />Marie</td>
				
				<?php
				foreach($years as $year) {
					?>
					
					<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong>Orders</strong><br />Alex</td>
					<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;" align="right"><strong>Turnover</strong><br />Alex</td>
					
					<?php
				}
				?>
				
				<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong>Orders</strong><br />Alex</td>
				<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;" align="right"><strong>Turnover</strong><br />Alex</td>

				<?php
				foreach($years as $year) {
					?>
					
					<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong>Orders</strong><br />Marc</td>
					<td style="border-bottom: 1px solid #aaaaaa; font-size: 14px;" align="right"><strong>Orders</strong><br />Others</td>
					
					<?php
				}
				?>
			</tr>

			<?php
			$total = array();

			foreach($years as $year) {	
				$total[$year] = array(8 => array('Count' => '', 'Turnover' => ''), 40 => array('Count' => '', 'Turnover' => ''));
			}
			
			foreach($items as $week=>$item) {
				if($week < date(8)) {
					foreach($item as $year=>$yearData) {
						$total[$year][8]['Count'] += $yearData[8]['Count'];
						$total[$year][8]['Turnover'] += $yearData[8]['Turnover'];
						$total[$year][40]['Count'] += $yearData[40]['Count'];
						$total[$year][40]['Turnover'] += $yearData[40]['Turnover'];
					}
				}
				
				$style = array(8 => array('Count' => '', 'Turnover' => ''), 40 => array('Count' => '', 'Turnover' => ''));
				
				if($items[$week][$years[0]][8]['Count'] > 0) {
					$percent = ($items[$week][$years[1]][8]['Count'] / $items[$week][$years[0]][8]['Count']) * 100;
					$style[8]['Count'] = ' color: #' . (($percent < 100) ? 'c00' : '0c0') . ';';
				}
				
				if($items[$week][$years[0]][8]['Turnover'] > 0) {
					$percent = ($items[$week][$years[1]][8]['Turnover'] / $items[$week][$years[0]][8]['Turnover']) * 100;
					$style[8]['Turnover'] = ' color: #' . (($percent < 100) ? 'c00' : '0c0') . ';';
				}
				
				if($items[$week][$years[0]][40]['Count'] > 0) {
					$percent = ($items[$week][$years[1]][40]['Count'] / $items[$week][$years[0]][40]['Count']) * 100;
					$style[40]['Count'] = ' color: #' . (($percent < 100) ? 'c00' : '0c0') . ';';
				}
				
				if($items[$week][$years[0]][40]['Turnover'] > 0) {
					$percent = ($items[$week][$years[1]][40]['Turnover'] / $items[$week][$years[0]][40]['Turnover']) * 100;
					$style[40]['Turnover'] = ' color: #' . (($percent < 100) ? 'c00' : '0c0') . ';';
				}
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td style="font-size: 14px;"><?php echo $week; ?></td>
					
					<?php
					foreach($item as $year=>$yearData) {
						?>
						
						<td style="font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><?php echo $yearData[8]['Count']; ?></td>
						<td style="font-size: 14px;" align="right">&pound; <?php echo number_format(round($yearData[8]['Turnover'], 2), 2, '.', ','); ?></td>
						
						<?php
					}
					?>
					
					<td style="font-size: 14px;<?php echo $style[8]['Count']; ?> border-left: 1px solid #aaaaaa;" align="right"><?php echo number_format(round(($items[$week][$years[0]][8]['Count'] > 0) ? ($items[$week][$years[1]][8]['Count'] / $items[$week][$years[0]][8]['Count']) * 100 : 0, 2), 2, '.', ','); ?> %</td>
					<td style="font-size: 14px;<?php echo $style[8]['Turnover']; ?>" align="right"><?php echo number_format(round(($items[$week][$years[0]][8]['Turnover'] > 0) ? ($items[$week][$years[1]][8]['Turnover'] / $items[$week][$years[0]][8]['Turnover']) * 100 : 0, 2), 2, '.', ','); ?> %</td>
					
					<?php
					foreach($item as $year=>$yearData) {
						?>
						
						<td style="font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><?php echo $yearData[40]['Count']; ?></td>
						<td style="font-size: 14px;" align="right">&pound; <?php echo number_format(round($yearData[40]['Turnover'], 2), 2, '.', ','); ?></td>
						
						<?php
					}
					?>
					
					<td style="font-size: 14px;<?php echo $style[40]['Count']; ?> border-left: 1px solid #aaaaaa;" align="right"><?php echo number_format(round(($items[$week][$years[0]][40]['Count'] > 0) ? ($items[$week][$years[1]][40]['Count'] / $items[$week][$years[0]][40]['Count']) * 100 : 0, 2), 2, '.', ','); ?> %</td>
					<td style="font-size: 14px;<?php echo $style[40]['Turnover']; ?>" align="right"><?php echo number_format(round(($items[$week][$years[0]][40]['Turnover'] > 0) ? ($items[$week][$years[1]][40]['Turnover'] / $items[$week][$years[0]][40]['Turnover']) * 100 : 0, 2), 2, '.', ','); ?> %</td>

					<?php
					foreach($item as $year=>$yearData) {
						?>
						
						<td style="font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><?php echo $yearData[45]['Count']; ?></td>
						<td style="font-size: 14px;" align="right"><?php echo $yearData[0]['Count']; ?></td>
						<?php
					}
					?>
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
				
				<td style="font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong><?php echo number_format(round(($total[$years[0]][8]['Count'] > 0) ? ($total[$years[1]][8]['Count'] / $total[$years[0]][8]['Count']) * 100 : 0, 2), 2, '.', ','); ?> %</strong></td>
				<td style="font-size: 14px;" align="right"><strong><?php echo number_format(round(($total[$years[0]][8]['Turnover'] > 0) ? ($total[$years[1]][8]['Turnover'] / $total[$years[0]][8]['Turnover']) * 100 : 0, 2), 2, '.', ','); ?> %</strong></td>
				
				<?php
				foreach($years as $year) {
					echo '<td style="border-left: 1px solid #aaaaaa;">&nbsp;</td>';
					echo '<td>&nbsp;</td>';
				}
				?>
				
				<td style="font-size: 14px; border-left: 1px solid #aaaaaa;" align="right"><strong><?php echo number_format(round(($total[$years[0]][40]['Count'] > 0) ? ($total[$years[1]][40]['Count'] / $total[$years[0]][40]['Count']) * 100 : 0, 2), 2, '.', ','); ?> %</strong></td>
				<td style="font-size: 14px;" align="right"><strong><?php echo number_format(round(($total[$years[0]][40]['Turnover'] > 0) ? ($total[$years[1]][40]['Turnover'] / $total[$years[0]][40]['Turnover']) * 100 : 0, 2), 2, '.', ','); ?> %</strong></td>

				<?php
				foreach($years as $year) {
					echo '<td style="border-left: 1px solid #aaaaaa;">&nbsp;</td>';
					echo '<td>&nbsp;</td>';
				}
				?>
			</tr>	
		</table>
		
	<html>

	<?php
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();