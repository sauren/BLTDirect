<?php
require_once('lib/common/app_header.php');

if($action == 'report') {
	$session->Secure(3);
	report();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Ouote Associations', 'Please choose a start and end date for this facility.');
	$year = cDatetime(getDatetime(), 'y');
	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Report Start Date', 'datetime', '0000-00-00 00:00:00', 'datetime', $year-10, $year, true);
	$form->AddField('end', 'Report End Date', 'datetime', '0000-00-00 00:00:00', 'datetime', $year-10, $year, true);
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

			redirect(sprintf("Location: %s?action=report&startdate=%s&enddate=%s", $_SERVER['PHP_SELF'], $start, $end));
		} else {
			if(!isDate($form->GetValue('start'))){
				$form->AddError('Report Start Date is not a real date. Please try again.', 'start');
			}
			if(!isDate($form->GetValue('end'))){
				$form->AddError('Report End Date is not a real date. Please try again.', 'end');
			}
			if($form->Validate()){			
				redirect(sprintf("Location: %s?action=report&startdate=%s&enddate=%s", $_SERVER['PHP_SELF'], $form->GetValue('start'), $form->GetValue('end')));
			}
		}
	}

	$page->Display('header');
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	$window = new StandardWindow("Associate orders to quotes.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
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

function report(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Quote.php');
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('startdate', 'Start Date', 'hidden', '', 'anything', 1, 32);
	$form->AddField('enddate', 'End Date', 'hidden', '', 'anything', 1, 32);
	
	$orders = array();
	
	$data = new DataQuery(sprintf("SELECT o.Order_ID, o.Order_Prefix, o.Created_On AS Order_Created_On, CONCAT_WS(' ', o.Billing_First_Name, o.Billing_Last_Name) AS Order_Billing_Name, o.Billing_Organisation_Name AS Order_Organisation_Name, o.Total AS Order_Total, o.Status AS Order_Status, q.Quote_ID, q.Quote_Prefix, q.Created_On AS Quote_Created_On, q.Total AS Quote_Total FROM orders AS o INNER JOIN quote AS q ON q.Customer_ID=o.Customer_ID AND o.Created_On>q.Created_On WHERE o.Quote_ID=0 AND o.Created_On BETWEEN '%s' AND '%s' AND q.Status LIKE 'Pending' ORDER BY o.Created_On DESC", mysql_real_escape_string($form->GetValue('startdate')), mysql_real_escape_string($form->GetValue('enddate'))));
	while($data->Row) {
		if(!isset($orders[$data->Row['Order_ID']])) {
			$orders[$data->Row['Order_ID']] = array();
			$orders[$data->Row['Order_ID']]['Quotes'] = array();
			$orders[$data->Row['Order_ID']]['Order_ID'] = $data->Row['Order_ID'];
			$orders[$data->Row['Order_ID']]['Order_Prefix'] = $data->Row['Order_Prefix'];
			$orders[$data->Row['Order_ID']]['Order_Billing_Name'] = $data->Row['Order_Billing_Name'];
			$orders[$data->Row['Order_ID']]['Order_Organisation_Name'] = $data->Row['Order_Organisation_Name'];
			$orders[$data->Row['Order_ID']]['Order_Total'] = $data->Row['Order_Total'];
			$orders[$data->Row['Order_ID']]['Order_Status'] = $data->Row['Order_Status'];
			$orders[$data->Row['Order_ID']]['Order_Created_On'] = $data->Row['Order_Created_On'];
			
			$form->AddField('primary_'.$data->Row['Order_ID'], '', 'radio', 0, 'numeric_unsigned', 1, 11, false);
			$form->AddOption('primary_'.$data->Row['Order_ID'], 0, '');
		}
		
		$orders[$data->Row['Order_ID']]['Quotes'][$data->Row['Quote_ID']] = array();
		$orders[$data->Row['Order_ID']]['Quotes'][$data->Row['Quote_ID']]['Quote_ID'] = $data->Row['Quote_ID'];
		$orders[$data->Row['Order_ID']]['Quotes'][$data->Row['Quote_ID']]['Quote_Prefix'] = $data->Row['Quote_Prefix'];
		$orders[$data->Row['Order_ID']]['Quotes'][$data->Row['Quote_ID']]['Quote_Created_On'] = $data->Row['Quote_Created_On'];
		$orders[$data->Row['Order_ID']]['Quotes'][$data->Row['Quote_ID']]['Quote_Total'] = $data->Row['Quote_Total'];
		
		$form->AddOption('primary_'.$data->Row['Order_ID'], $data->Row['Quote_ID'], '');
		
		$data->Next();
	}
	$data->Disconnect();
	
	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()) {
			foreach($orders as $order) {
				if($form->GetValue('primary_'.$order['Order_ID']) > 0) {
					$orderObj = new Order($order['Order_ID']);
					$orderObj->QuoteID = $form->GetValue('primary_'.$order['Order_ID']);
					$orderObj->Update();
					
					$quoteObj = new Quote($form->GetValue('primary_'.$order['Order_ID']));
					$quoteObj->Status = 'Ordered';
					$quoteObj->Update();
				}				
			}
						
			redirect(sprintf("Location: %s?action=report&startdate=%s&enddate=%s", $_SERVER['PHP_SELF'], $form->GetValue('startdate'), $form->GetValue('enddate')));
		}
	}
	
	$page = new Page('Quote Associations: ' . cDatetime($form->GetValue('startdate'), 'longdatetime') . ' to ' . cDatetime($form->GetValue('enddate'), 'longdatetime'), 'Associate unassociated orders with quotes created prior to the order.');
	$page->Display('header');

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('startdate');
	echo $form->GetHTML('enddate');	
	?>
	
	<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
		<thead>
			<tr>
				<th nowrap="nowrap" class="dataHeadOrdered">Order Date</th>
				<th nowrap="nowrap">Organisation</th>
				<th nowrap="nowrap">Name</th>
				<th nowrap="nowrap">Prefix</th>
				<th nowrap="nowrap">Number</th>
				<th nowrap="nowrap">Total</th>
				<th nowrap="nowrap">Status</th>
				<th colspan="1">&nbsp;</th>
			</tr>
		</thead>
		<tbody>

			<?php
			if(count($orders) > 0) {
				foreach($orders as $order) {
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td align="left" class="dataOrdered"><?php echo $order['Order_Created_On']; ?></td>
						<td align="left"><?php echo $order['Order_Organisation_Name']; ?>&nbsp;</td>
						<td align="left"><?php echo $order['Order_Billing_Name']; ?>&nbsp;</td>
						<td align="center"><?php echo $order['Order_Prefix']; ?>&nbsp;</td>
						<td align="left"><?php echo $order['Order_ID']; ?>&nbsp;</td>
						<td align="right">&pound;<?php echo $order['Order_Total']; ?>&nbsp;</td>
						<td align="left"><?php echo $order['Order_Status']; ?>&nbsp;</td>
						<td nowrap align="center" width="16"><a href="order_details.php?orderid=<?php echo $order['Order_ID']; ?>"><img src="./images/folderopen.gif" alt="Open Order" border="0"></a></td>
					</tr>
					<tr>
						<td colspan="8">
						
							<table align="center" cellpadding="4" cellspacing="0" class="DataTable">							
								<tr class="dataRow" style="background-color: #fff;">
									<td colspan="1">&nbsp;</td>
									<td width="40%" style="color: #666;" align="left"><strong>Quoted On</strong></td>
									<td width="15%" style="color: #666;" align="center"><strong>Prefix</strong></td>
									<td width="35%" style="color: #666;" align="left"><strong>Number</strong></td>
									<td width="20%" style="color: #666;" align="right"><strong>Total</strong></td>
									<td colspan="1">&nbsp;</td>
								</tr>								
								<tbody>
								
									<?php
									$index = 2;
									
									foreach($order['Quotes'] as $quote) {
										?>	
										
										<tr class="dataRow" style="background-color: #fff;">
											<td align="left"><?php echo $form->GetHTML('primary_'.$order['Order_ID'], $index); ?></td>
											<td align="left"><?php echo $quote['Quote_Created_On']; ?></td>
											<td align="center"><?php echo $quote['Quote_Prefix']; ?>&nbsp;</td>
											<td align="left"><?php echo $quote['Quote_ID']; ?>&nbsp;</td>
											<td align="right">&pound;<?php echo $quote['Quote_Total']; ?>&nbsp;</td>
											<td nowrap align="center" width="16"><a href="quote_details.php?quoteid=<?php echo $quote['Quote_ID']; ?>"><img src="./images/folderopen.gif" alt="Open Quote" border="0"></a></td>
										</tr>	
					
										<?php
										$index++;
									}
									?>
									
									<tr class="dataRow" style="background-color: #fff;">
										<td align="left" colspan="6"><?php echo $form->GetHTML('primary_'.$order['Order_ID'], 1); ?> No association</td>
									</tr>
								</tbody>
							</table><br />
						
						</td>
					</tr>
					
					<?php
				}
			} else {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td align="left" colspan="8">No Records Found</td>
				</tr>

				<?php
			}
			$data->Disconnect();
			?>

		</tbody>
	</table><br />
	
	<input type="submit" class="btn" name="associate" value="associate" />
	
	<?php
	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>