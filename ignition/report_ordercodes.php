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
	$form->AddField('product', 'Product QuickFind Code', 'text', '', 'numeric_unsigned', 1, 10);
	$form->AddField('start', 'Report Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'Report End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('startyear', 'Start Year', 'select', '', 'alpha_numeric', 0, 4, false);
	$form->AddOption('startyear', '', '-- None --');
	$form->AddField('endyear', 'End Year', 'select', '', 'alpha_numeric', 0, 4, false);
	$form->AddOption('endyear', '', '-- None --');
	
	for($i=2006; $i<=date('Y')+1; $i++) {
		$form->AddOption('startyear', $i, $i);
		$form->AddOption('endyear', $i, $i);
	}
	
	$form->AddField('exclude', 'Exclude orders with more than', 'text', '8', 'numeric', 1, 10);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if(($form->GetValue('startyear') != '') && ($form->GetValue('endyear') != '')) {
			report(date(sprintf('%s-01-01 00:00:00', $form->GetValue('startyear'))), date(sprintf('%s-01-01 00:00:00', $form->GetValue('endyear'))), $form->GetValue('product'), $form->GetValue('exclude'), true);
			exit;
		} else {
			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))), $form->GetValue('product'), $form->GetValue('exclude'), false);
				exit;
			}
		}
	}

	$page = new Page('Orders Containing Product', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');
	
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Report on Orders Containing Product");
	$webForm = new StandardForm;


	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select a product by QuickFind code.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('product'), $form->GetHTML('product'));
	echo $webForm->AddRow($form->GetLabel('exclude'), $form->GetHTML('exclude') . 'of this product.');
	echo $webForm->Close();
	echo $window->CloseContent();
	
	echo $window->AddHeader('Select one of the predefined date ranges for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('startyear'), $form->GetHTML('startyear'));
	echo $webForm->AddRow($form->GetLabel('endyear'), $form->GetHTML('endyear'));
	echo $webForm->Close();
	echo $window->CloseContent();
	
	echo $window->AddHeader('Select the date range from below for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start'));
	echo $webForm->AddRow($form->GetLabel('end'), $form->GetHTML('end'));
	echo $webForm->Close();
	echo $webForm->Open();
	echo $webForm->AddRow('&nbsp;', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report($start, $end, $productId, $exclude, $isMonthly = false){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

	$product = new Product();
	$product->Get($productId);
		
	if($isMonthly) {
		$orders = array();
		$dates = array();
		
		$startDate = $start;
		
		while(strtotime($startDate) < time()) {
			$item['Start'] = $startDate;
			$item['End'] = date('Y-m-d 00:00:00', mktime(0, 0, 0, date('m', strtotime($startDate))+1, date('d', strtotime($startDate)), date('Y', strtotime($startDate))));
			
			$dates[] = $item;
					
			$startDate = $item['End'];
		}
	
		foreach($dates as $date) {
			$data = new DataQuery(sprintf("SELECT o.Order_ID, SUM(ol.Quantity) AS Quantity FROM orders o INNER JOIN order_line ol ON ol.Order_ID=o.Order_ID INNER JOIN product p ON p.Product_ID=ol.Product_ID WHERE p.Product_ID=%d AND o.Created_On BETWEEN '%s' AND '%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Quantity<=%d GROUP BY o.Order_ID", $product->ID, $date['Start'], $date['End'], $exclude));
			while($data->Row) {
				$item = array();
				$item['Order_ID'] = $data->Row['Order_ID'];
				$item['Quantity'] = $data->Row['Quantity'];
				
				$orders[strtotime($date['Start']) . ':' . strtotime($date['End'])][] = $item;
				
				$data->Next();
			}
			$data->Disconnect();
		}
	} else {
		$data = new DataQuery(sprintf("SELECT o.Order_ID, SUM(ol.Quantity) AS Quantity FROM orders o INNER JOIN order_line ol ON ol.Order_ID=o.Order_ID INNER JOIN product p ON p.Product_ID=ol.Product_ID WHERE p.Product_ID=%d AND o.Created_On BETWEEN '%s' AND '%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Quantity<=%d GROUP BY o.Order_ID", $product->ID, $start, $end, $exclude));
		while($data->Row) {
			$item = array();
			$item['Order_ID'] = $data->Row['Order_ID'];
			$item['Quantity'] = $data->Row['Quantity'];
			
			$orders[] = $item;
			
			$data->Next();
		}
		$data->Disconnect();
	}
	
	$page = new Page('Sales Report: ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->Display('header');
	?>

	<br />
	<h3><?php echo "#{$product->ID}: {$product->Name}" ?></h3>
	<br />
	
	<?php
	if($isMonthly) {
		?>
		
		<table width="100%" border="0" >
			<tr>
				<td style="border-bottom: 1px solid #aaaaaa;"><strong>Date Range</strong></td>
				<td style="border-bottom: 1px solid #aaaaaa;" align="right"><strong>Quantity Sold</strong></td>
				<td style="border-bottom: 1px solid #aaaaaa;"><strong>Order</strong></td>
			</tr>
			
			<?php
			foreach($orders as $dateRanges=>$figures) {
				$ranges = explode(':', $dateRanges);
				
				foreach($figures as $order) {
					$totalQty += $order['Quantity'];
				}
				?>
			
				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><strong><?php echo cDatetime(date('Y-m-d 00:00:00', $ranges[0]), 'shortdate'); ?> - <?php echo cDatetime(date('Y-m-d 00:00:00', $ranges[1]), 'shortdate'); ?></strong></td>
					<td align="right"><strong><?php echo $totalQty; ?></strong></td>
					<td>&nbsp;</td>
				</tr>
				
				<?php
				$totalQty = 0;
				
				foreach($figures as $order) {
					?>
					
					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td><a href="order_details.php?orderid=<?php echo $order['Order_ID']; ?>"><?php echo $order['Order_ID']; ?></a></td>
					</tr>
				
					<?php
				}
				?>
				
				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
					
				<?php
			}
			?>
			
		</table>
		
		<?php
	} else {
		foreach($orders as $order) {
			$totalQty += $order['Quantity'];
		}
		?>
		
		<table width="100%" border="0">
			<tr>
				<td style="border-bottom: 1px solid #aaaaaa;"><strong>Date Range</strong></td>
				<td style="border-bottom: 1px solid #aaaaaa;" align="right"><strong>Quantity Sold</strong></td>
				<td style="border-bottom: 1px solid #aaaaaa;"><strong>Order</strong></td>
			</tr>
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><strong><?php echo cDatetime($start, 'shortdate'); ?> - <?php echo cDatetime($end, 'shortdate'); ?></strong></td>
				<td align="right"><strong><?php echo $totalQty; ?></strong></td>
				<td>&nbsp;</td>
			</tr>
			
			<?php
			foreach($orders as $order) {
				?>
			
				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td><a href="order_details.php?orderid=<?php echo $order['Order_ID'];?>"><?php echo $order['Order_ID']; ?></a></td>
				</tr>
			
				<?php
			}
			?>
			
		</table>
		
		<?php
	}

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>