<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

if($action == 'report') {
	$session->Secure(2);
	report();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start(){
	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('range', 'Date range', 'select', 'none', 'alpha_numeric', 0, 32);
	$form->AddOption('range', 'none', '-- None --');
	$form->AddOption('range', 'all', '-- All --');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'thisminute', 'This Minute');
	$form->AddOption('range', 'thishour', 'This Hour');
	$form->AddOption('range', 'thisday', 'This Day');
	$form->AddOption('range', 'thismonth', 'This Month');
	$form->AddOption('range', 'thisyear', 'This Year');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lasthour', 'Last Hour');
	$form->AddOption('range', 'last3hours', 'Last 3 Hours');
	$form->AddOption('range', 'last6hours', 'Last 6 Hours');
	$form->AddOption('range', 'last12hours', 'Last 12 Hours');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastday', 'Last Day');
	$form->AddOption('range', 'last2days', 'Last 2 Days');
	$form->AddOption('range', 'last3days', 'Last 3 Days');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastmonth', 'Last Month');
	$form->AddOption('range', 'last3months', 'Last 3 Months');
	$form->AddOption('range', 'last6months', 'Last 6 Months');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastyear', 'Last Year');
	$form->AddOption('range', 'last2years', 'Last 2 Years');
	$form->AddOption('range', 'last3years', 'Last 3 Years');

	if(isset($_REQUEST['confirm'])) {
		if(($form->GetValue('range') != 'none') && (strlen($form->GetValue('range')) > 1)) {
			switch($form->GetValue('range')) {
				case 'all': 		$start = date('Y-m-d H:i:s', 0);
									$end = date('Y-m-d H:i:s');
									break;

				case 'thisminute': 	$start = date('Y-m-d H:i:00');
									$end = date('Y-m-d H:i:s');
									break;
				case 'thishour': 	$start = date('Y-m-d H:00:00');
									$end = date('Y-m-d H:i:s');
									break;
				case 'thisday': 	$start = date('Y-m-d 00:00:00');
									$end = date('Y-m-d H:i:s');
									break;
				case 'thismonth': 	$start = date('Y-m-01 00:00:00');
									$end = date('Y-m-d H:i:s');
									break;
				case 'thisyear': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")));
									$end = date('Y-m-d H:i:s');
									break;

				case 'lasthour': 	$start = date('Y-m-d H:00:00', mktime(date("H")-1, 0, 0, date("m"), date("d"),  date("Y")));
									$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
									break;
				case 'last3hours': 	$start = date('Y-m-d H:00:00', mktime(date("H")-3, 0, 0, date("m"), date("d"),  date("Y")));
									$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
									break;
				case 'last6hours': 	$start = date('Y-m-d H:00:00', mktime(date("H")-6, 0, 0, date("m"), date("d"),  date("Y")));
									$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
									break;
				case 'last12hours': $start = date('Y-m-d H:00:00', mktime(date("H")-12, 0, 0, date("m"), date("d"),  date("Y")));
									$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
									break;

				case 'lastday': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
									$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
									break;
				case 'last2days': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-2, date("Y")));
									$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
									break;
				case 'last3days': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));
									$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
									break;

				case 'lastmonth': 	$start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-1, 1,  date("Y")));
									$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
									break;
				case 'last3months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-3, 1,  date("Y")));
									$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
									break;
				case 'last6months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-6, 1,  date("Y")));
									$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
									break;

				case 'lastyear': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-1));
									$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
									break;
				case 'last2years': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-2));
									$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
									break;
				case 'last3years': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-3));
									$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
									break;
			}

			redirectTo(sprintf('?action=report&start=%s&end=%s', $start, $end));
		} else {
			if($form->Validate()) {
				redirectTo(sprintf('?action=report&start=%s&end=%s', sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2))))))));
			}
		}
	}

	$page = new Page('Stock Costs Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');
	
	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}
	
	$window = new StandardWindow("Report on Stock Costs.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Filter out products sold for particular orders.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('range'), $form->GetHTML('range'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Or select the date range from below for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start'));
	echo $webForm->AddRow($form->GetLabel('end'), $form->GetHTML('end'));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->AddHeader('Click below to submit your request');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Start Date', 'hidden', '0000-00-00 00:00:00', 'anything', 1, 19);
	$form->AddField('end', 'End Date', 'hidden', '0000-00-00 00:00:00', 'anything', 1, 19);
	
	$scripts = sprintf('
		<script language="javascript" type="text/javascript">
		var toggleData = function(rank) {
			var data = document.getElementById(\'data-\' + rank);
			var image = document.getElementById(\'image-\' + rank);
			
			if(data && image) {
				if(data.style.display == \'\') {
					data.style.display = \'none\';
					image.src = \'images/button-plus.gif\';
				} else {
					data.style.display = \'\';
					image.src = \'images/button-minus.gif\';
				}
			}
		}	
		</script>');
	
	$page = new Page('Stock Costs Report: ' . cDatetime($form->GetValue('start'), 'longdatetime') . ' to ' . cDatetime($form->GetValue('end'), 'longdatetime'), '');
	$page->AddToHead($scripts);
	$page->Display('header');
	?>

	<h3>Top 500 Products</h3>
	<br />

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;">&nbsp;</td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Rank</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Product Name</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Best Cost Supplier</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Best Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Quantity</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Orders</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Purchase Quantity</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Purchase Cost</strong></td>
		</tr>

		<?php
		$totalPurchaseCost = 0;
		
		$rank = 1;
		
		$warehouses = array();
		
		$data = new DataQuery(sprintf("SELECT ol.Product_ID, w.Warehouse_Name, SUM(ol.Quantity) AS Quantity, COUNT(DISTINCT ol.Order_ID) AS Orders, ROUND(AVG(ol.Cost), 2) AS Average_Cost, ROUND(AVG((ol.Line_Total-ol.Line_Discount)/ol.Quantity), 2) AS Average_Net FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID WHERE o.Created_On BETWEEN '%s' AND '%s' GROUP BY ol.Product_ID, ol.Despatch_From_ID ORDER BY ol.Product_ID ASC", mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
		while($data->Row) {
			if(!isset($warehouses[$data->Row['Product_ID']])) {
				$warehouses[$data->Row['Product_ID']] = array();
			}
			
			$warehouses[$data->Row['Product_ID']][] = $data->Row;
			
			$data->Next();
		}
		$data->Disconnect();
		
		$data = new DataQuery(sprintf("SELECT ol.Product_ID, p.Product_Title, p.Product_Type, SUM(ol.Quantity) AS Quantity, COUNT(DISTINCT ol.Order_ID) AS Orders, SUM(ol.Cost) AS Cost FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN product AS p ON p.Product_ID=ol.Product_ID LEFT JOIN (SELECT Product_ID FROM warehouse_stock GROUP BY Product_ID) AS ws ON ws.Product_ID=ol.Product_ID WHERE o.Created_On BETWEEN '%s' AND '%s' AND (p.Product_Type='G' OR ws.Product_ID IS NOT NULL) GROUP BY ol.Product_ID ORDER BY Quantity DESC LIMIT 500", mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
		while($data->Row) {
			$bestSupplier = '';
			$bestCost = 0;
			
			$purchaseQuantity = 0;
			$purchaseCost = 0;
			
			switch($data->Row['Product_Type']) {
				case 'G':
					$data2 = new DataQuery(sprintf("SELECT Product_ID, Component_Quantity FROM product_components WHERE Component_Of_Product_ID=%d", $data->Row['Product_ID']));
					while($data2->Row) {
						$data3 = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Cost>0 ORDER BY Cost ASC LIMIT 0, 1", $data2->Row['Product_ID']));
						if($data3->TotalRows > 0) {
							$bestCost += $data3->Row['Cost'] * $data2->Row['Component_Quantity'];
						}				
						$data3->Disconnect();
						
						$data3 = new DataQuery(sprintf("SELECT SUM(pl.Quantity) AS Quantity, SUM(pl.Cost*pl.Quantity) AS Cost FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID AND pl.Product_ID=%d WHERE p.Order_ID=0 AND p.Purchase_Status NOT LIKE 'Irrelevant' AND p.Purchased_On BETWEEN '%s' AND '%s'", $data2->Row['Product_ID'], $form->GetValue('start'), mysql_real_escape_string($form->GetValue('end'))));
						if($data3->TotalRows > 0) {
							$purchaseQuantity += $data3->Row['Quantity'];
							$purchaseCost += $data3->Row['Cost'];
						}
						$data3->Disconnect();
						
						$data2->Next();			
					}
					$data2->Disconnect();		
					
					break;
					
				default:
					$data2 = new DataQuery(sprintf("SELECT w.Warehouse_Name, sp.Cost FROM supplier_product AS sp INNER JOIN warehouse AS w ON w.Type_Reference_ID=sp.Supplier_ID AND w.Type='S' WHERE sp.Product_ID=%d AND sp.Cost>0 ORDER BY sp.Cost ASC LIMIT 0, 1", mysql_real_escape_string($data->Row['Product_ID'])));
					if($data2->TotalRows > 0) {
						$bestSupplier = $data2->Row['Warehouse_Name'];
						$bestCost = $data2->Row['Cost'];
					}
					$data2->Disconnect();
					
					$data2 = new DataQuery(sprintf("SELECT SUM(pl.Quantity) AS Quantity, SUM(pl.Cost*pl.Quantity) AS Cost FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID AND pl.Product_ID=%d WHERE p.Order_ID=0 AND p.Purchase_Status NOT LIKE 'Irrelevant' AND p.Purchased_On BETWEEN '%s' AND '%s'", $data->Row['Product_ID'], mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
					if($data2->TotalRows > 0) {
						$purchaseQuantity = $data2->Row['Quantity'];
						$purchaseCost = $data2->Row['Cost'];
					}
					$data2->Disconnect();
					break;
			}
			
			$totalPurchaseCost += $purchaseCost;
			?>

			<tr>
				<td style="border-bottom: 1px dashed #aaaaaa;"><a href="javascript:toggleData(<?php echo $rank; ?>);"><img src="images/button-plus.gif" id="image-<?php echo $rank; ?>" border="0" /></a></td>
				<td style="border-bottom: 1px dashed #aaaaaa;"><?php print $rank; ?></td>
				<td style="border-bottom: 1px dashed #aaaaaa;"><a href="product_profile.php?pid=<?php echo $data->Row['Product_ID']; ?>"><?php echo strip_tags($data->Row['Product_Title']); ?></a></td>
				<td style="border-bottom: 1px dashed #aaaaaa;"><?php echo $data->Row['Product_ID']; ?></td>
				<td style="border-bottom: 1px dashed #aaaaaa;"><?php echo $bestSupplier; ?></td>
				<td style="border-bottom: 1px dashed #aaaaaa;" align="right"><?php echo number_format($bestCost, 2, '.', ','); ?></td>
				<td style="border-bottom: 1px dashed #aaaaaa;" align="right"><?php echo $data->Row['Quantity']; ?></td>
				<td style="border-bottom: 1px dashed #aaaaaa;" align="right"><?php echo $data->Row['Orders']; ?></td>
				<td style="border-bottom: 1px dashed #aaaaaa;" align="right"><?php echo $data->Row['Cost']; ?></td>
				<td style="border-bottom: 1px dashed #aaaaaa;" align="right"><?php echo $purchaseQuantity; ?></td>
				<td style="border-bottom: 1px dashed #aaaaaa;" align="right"><?php echo number_format($purchaseCost, 2, '.', ','); ?></td>
			</tr>
			<tr style="display: none;" id="data-<?php echo $rank; ?>">
				<td style="border-bottom: 1px dashed #aaaaaa;">&nbsp;</td>
				<td style="border-bottom: 1px dashed #aaaaaa;" colspan="10">
				
					<table width="100%" border="0">
						<tr>
							<th nowrap="nowrap" width="40%" style="padding-right: 5px;" align="left">Warehouse</th>
							<th nowrap="nowrap" width="15%" style="padding-right: 5px;" align="right"><strong>Quantity</strong></td>
							<th nowrap="nowrap" width="15%" style="padding-right: 5px;" align="right"><strong>Orders</strong></td>
							<th nowrap="nowrap" width="15%" style="padding-right: 5px;" align="right">Average Cost</th>
							<th nowrap="nowrap" width="15%" style="padding-right: 5px;" align="right">Average Net</th>
						</tr>
						
						<?php
						foreach($warehouses[$data->Row['Product_ID']] as $warehouse) {
							?>
							
							<tr>
								<td><?php echo $warehouse['Warehouse_Name']; ?></td>
								<td align="right"><?php echo number_format($warehouse['Quantity'], 2, '.', ','); ?></td>
								<td align="right"><?php echo number_format($warehouse['Orders'], 2, '.', ','); ?></td>
								<td align="right">&pound;<?php echo number_format($warehouse['Average_Cost'], 2, '.', ','); ?></td>
								<td align="right">&pound;<?php echo number_format($warehouse['Average_Net'], 2, '.', ','); ?></td>
							</tr>
							
							<?php
						}
						?>
						
					</table>					
				
				</td>
			</tr>
		
			<?php
			$rank++;

			$data->Next();
		}
		$data->Disconnect();
		?>
		
		<tr>
			<td style="border-bottom: 1px dashed #aaaaaa;" colspan="10">&nbsp;</td>
			<td style="border-bottom: 1px dashed #aaaaaa;" align="right"><strong><?php echo number_format($totalPurchaseCost, 2, '.', ','); ?></strong></td>
		</tr>
	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}