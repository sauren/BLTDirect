<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Report Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'Report End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
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
	$form->AddField('products', 'Products', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('products', '', '');
	$form->AddOption('products', '250', '250');
	$form->AddOption('products', '500', '500');
	$form->AddOption('products', '750', '750');
	$form->AddOption('products', '1000', '1000');
		
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
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

			report($start, $end, $form->GetValue('products'));
			exit;
		} else {

			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))), $form->GetValue('products'));
				exit;
			}
		}
	}

	$page = new Page('Top Products Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	
	$window = new StandardWindow("Report on Top Products.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select a product count for this report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('products'), $form->GetHTML('products'));
	echo $webForm->Close();
	echo $window->CloseContent();
	
	echo $window->AddHeader('Select one of the predefined date ranges for your report.');
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
	echo $webForm->AddRow('&nbsp;', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report($start, $end, $productCount) {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');

	$products = array();
	$warehouses = array(2, 3, 6, 10);
	$totals = array('0' => 0);
	
	foreach($warehouses as $warehouseId) {
		$totals[$warehouseId] = 0;
	}
	
	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, p.Position_Orders_Recent, o.Orders, o.Quantities FROM product AS p LEFT JOIN (SELECT ol.Product_ID, COUNT(DISTINCT o.Order_ID) AS Orders, SUM(ol.Quantity) AS Quantities FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE o.Status LIKE 'Despatched' AND o.Created_On>='%s' AND o.Created_On<'%s' GROUP BY ol.Product_ID) AS o ON o.Product_ID=p.Product_ID WHERE p.Position_Orders_Recent BETWEEN 1 AND %d GROUP BY p.Product_ID ORDER BY p.Position_Orders_Recent ASC", mysql_real_escape_string($start), mysql_real_escape_string($end), mysql_real_escape_string($productCount)));
	while($data->Row) {
		$item = $data->Row;
		$item['Suppliers'] = array();
		
		$products[] = $item;
		
		$data->Next();
	}
	$data->Disconnect();
	
	$data = new DataQuery(sprintf("SELECT w.Warehouse_ID, w.Warehouse_Name, sp.Product_ID, sp.Cost FROM warehouse AS w INNER JOIN supplier AS s ON s.Supplier_ID=w.Type_Reference_ID LEFT JOIN supplier_product AS sp ON sp.Supplier_ID=s.Supplier_ID AND sp.Cost>0 INNER JOIN product AS p ON p.Product_ID=sp.Product_ID AND p.Position_Orders_Recent BETWEEN 1 AND %d WHERE w.Warehouse_ID IN (%s) GROUP BY sp.Product_ID, w.Warehouse_ID", mysql_real_escape_string($productCount), implode(', ', $warehouses)));
	while($data->Row) {
		for($i=0; $i<count($products); $i++) {
			if($products[$i]['Product_ID'] == $data->Row['Product_ID']) {
				$products[$i]['Suppliers'][] = $data->Row;
				break;
			}
		}
		
		$data->Next();
	}
	$data->Disconnect();
	
	$page = new Page('Top Products Report: ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->Display('header');
	?>
	
	<br /><h3>Top <?php echo $productCount; ?> Products</h3>
	<p>Top products with supplier stats.</p>

	<table width="100%" cellspacing="0">
		<tr>
			<td style="border: none;" colspan="5">&nbsp;</td>
			<td style="border: none; text-align: center; background-color: #ffe0ad; font-size: 11pt; padding: 10px;" colspan="<?php echo count($warehouses); ?>">Supplier</td>
			<td style="border: none; text-align: center; background-color: #ffadad; font-size: 11pt; padding: 10px;">Best Buy</td>
		</tr>
		<tr>
			<th style="border-bottom: 1px solid #999999; padding: 5px; text-align: left;"><strong>Position</strong></th>
			<th style="border-bottom: 1px solid #999999; padding: 5px; text-align: left;"><strong>Product Name</strong></th>
			<th style="border-bottom: 1px solid #999999; padding: 5px; text-align: right;"><strong>Quickfind</strong></th>
			<th style="border-bottom: 1px solid #999999; padding: 5px; text-align: right;"><strong>Quantity</strong></th>
			<th style="border-bottom: 1px solid #999999; padding: 5px; text-align: right;"><strong>Orders</strong></th>
			
			<?php
			foreach($warehouses as $warehouseId) {
				$warehouse = new Warehouse($warehouseId);
				
				echo sprintf('<th style="border-bottom: 1px solid #999999; padding: 5px; background-color: #ffe0ad; text-align: right;"><strong>%s</strong></th>', $warehouse->Name);	
			}
			?>
			
			<th style="border-bottom: 1px solid #999999; padding: 5px; background-color: #ffadad; text-align: right;"><strong>Total</strong></th>
		</tr>
	  
		<?php
		for($i=0; $i<count($products); $i++) {
			?>
			
			<tr>
				<td style="border-bottom: 1px dashed #aaaaaa; padding: 5px;" align=";eft"><?php echo $products[$i]['Position_Orders_Recent']; ?></td>
				<td style="border-bottom: 1px dashed #aaaaaa; padding: 5px;"><a href="product_profile.php?pid=<?php echo $products[$i]['Product_ID']; ?>" target="_blank"><?php echo $products[$i]['Product_Title']; ?></a></td>
				<td style="border-bottom: 1px dashed #aaaaaa; padding: 5px;" align="right"><?php echo $products[$i]['Product_ID']; ?></td>
				<td style="border-bottom: 1px dashed #aaaaaa; padding: 5px;" align="right"><?php echo $products[$i]['Quantities']; ?></td>
				<td style="border-bottom: 1px dashed #aaaaaa; padding: 5px;" align="right"><?php echo $products[$i]['Orders']; ?></td>
				
				<?php
				$bestBuy = 0;
				$bestBuyWarehouseId = 0;
				
				foreach($warehouses as $warehouseId) {
					for($j=0; $j<count($products[$i]['Suppliers']); $j++) {
						if($products[$i]['Suppliers'][$j]['Warehouse_ID'] == $warehouseId) {
							$total = $products[$i]['Suppliers'][$j]['Cost'] * $products[$i]['Quantities'];
							
							if(($bestBuy == 0 ) || (($total > 0) && ($total < $bestBuy))) {
								$bestBuy = $total;
								$bestBuyWarehouseId = $products[$i]['Suppliers'][$j]['Warehouse_ID'];
							}
							
							break;
						}
					}
				}
				
				$totals[0] += $bestBuy;
				
				foreach($warehouses as $warehouseId) {
					$found = false;
					
					for($j=0; $j<count($products[$i]['Suppliers']); $j++) {
						if($products[$i]['Suppliers'][$j]['Warehouse_ID'] == $warehouseId) {
							$total = $products[$i]['Suppliers'][$j]['Cost'] * $products[$i]['Quantities'];
							
							echo sprintf('<td style="border-bottom: 1px dashed #aaaaaa; padding: 5px; background-color: #%s;" align="right">%s</td>', ($bestBuyWarehouseId == $products[$i]['Suppliers'][$j]['Warehouse_ID']) ? 'ffe0ad' : 'ffca95', number_format($total, 2, '.', ','));
							
							$totals[$products[$i]['Suppliers'][$j]['Warehouse_ID']] += $total;
							
							$found = true;
							break;
						}
					}
					
					if(!$found) {
						echo sprintf('<td style="border-bottom: 1px dashed #aaaaaa; padding: 5px; background-color: #ffca95;" align="right">-</td>');
					}
				}
				
				echo sprintf('<td style="border-bottom: 1px dashed #aaaaaa; padding: 5px; background-color: #ff9595;" align="right">%s</td>', ($bestBuy > 0) ? number_format($bestBuy, 2, '.', ',') : '-');
				?>
				
			</tr>
			
			<?php
		}
		?>
		
		<tr>
			<td colspan="5">&nbsp;</td>
			
			<?php
			foreach($warehouses as $warehouseId) {
				echo sprintf('<td style="padding: 5px; background-color: #ffe0ad; text-align: right;"><strong>%s</strong></td>', number_format($totals[$warehouseId], 2, '.', ','));	
			}
			
			echo sprintf('<td style="padding: 5px; background-color: #ffadad; text-align: right;"><strong>%s</strong></td>', number_format($totals[0], 2, '.', ','));
			?>
		</tr>
	</table>
	
	<?php
	$page->Display('footer');
}