<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReportCache.php');

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
			<script language="javascript" type="text/javascript" src="../ignition/js/HttpRequest.js"></script>
		</head>
		<body>
		
		<?php
		$dataCache = new DataQuery(sprintf("SELECT rc.ReportCacheID, rc.CreatedOn FROM report_cache AS rc INNER JOIN report AS r ON r.ReportID=rc.ReportID WHERE r.Reference LIKE 'warehouseshipped' ORDER BY rc.CreatedOn DESC LIMIT 0, 1"));
		if($dataCache->TotalRows > 0) {
			$reportCache = new ReportCache();
			$reportCache->Get($dataCache->Row['ReportCacheID']);
			$reportCache->Report->Get();
	
			$data = $reportCache->GetData();

			if(isset($data['Items'])) {
				$data = array('12' => $data);
			}
			?>

			<h1>Warehouse Shipped Report</h1>
			
			<?php	
			foreach($data as $month=>$monthData) {
				?>
				
				<br />
				<h2>Branch Shipped Orders - <?php echo $month; ?> Month(s)</h2>
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
		}
		$dataCache->Disconnect();
		?>

		</body>
	</html>

	<?php
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();