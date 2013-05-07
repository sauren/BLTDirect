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

			report($start, $end, $form->GetValue('parent'), ($form->GetValue('subfolders') =='Y') ? true : false);
			exit;
		} else {
			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))),$form->GetValue('parent'),($form->GetValue('subfolders') =='Y')?true:false);
				exit;
			}
		}
	}

	$page = new Page('Returns Telesales Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Returns Telesales.");
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

function report($start, $end){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$accountManager = array();

	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Account_Manager FROM `return` AS r INNER JOIN order_line AS ol ON ol.Order_Line_ID=r.Order_Line_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID INNER JOIN users AS u ON u.User_ID=o.Created_By INNER JOIN person AS p ON p.Person_ID=u.Person_ID WHERE o.Order_Prefix='T' AND o.Created_On>='%s' AND o.Created_On<'%s' AND r.Status LIKE 'Resolved' GROUP BY o.Created_By", mysql_real_escape_string($start), mysql_real_escape_string($end)));
	while($data->Row) {
		$accountManager[$data->Row['User_ID']] = $data->Row['Account_Manager'];

		$data->Next();
	}
	$data->Disconnect();

	$page = new Page('Returns Telesales Report: ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->Display('header');

	foreach($accountManager as $accountManagerId=>$accountManagerName) {
		?>

		<br />
		<h3><?php echo $accountManagerName; ?>'s Returned Orders</h3>
		<p>Listing all returned telesales orders between the given dates for this account manager.</p>

		<table width="100%" border="0">
			<tr>
				<td align="left" style="border-bottom: 1px solid #aaaaaa;" width="10%"><strong>Order ID</strong></td>
				<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Return Note</strong></td>
				<td align="right" style="border-bottom: 1px solid #aaaaaa;" width="15%"><strong>Total Ordered (&pound;)</strong></td>
				<td align="right" style="border-bottom: 1px solid #aaaaaa;" width="15%"><strong>Total Returned (&pound;)</strong></td>
				<td align="right" style="border-bottom: 1px solid #aaaaaa;" width="15%"><strong>Total Credited (&pound;)</strong></td>
				<td align="right" style="border-bottom: 1px solid #aaaaaa;" width="15%"><strong>Total Despatched (&pound;)</strong></td>
			</tr>

			<?php
			$totalOrder = 0;
			$totalReturn = 0;
			$totalCredit = 0;
			$totalDespatch = 0;

			$data = new DataQuery(sprintf("SELECT o.Order_ID, r.Return_ID, r.Note, o.Total-o.TotalTax AS Order_Total, r.Quantity*ol.Price AS Return_Total, cn.Total-cn.TotalTax AS Credit_Total, SUM(ol2.Price * ol2.Quantity) AS Despatch_Total FROM `return` AS r INNER JOIN order_line AS ol ON ol.Order_Line_ID=r.Order_Line_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID LEFT JOIN credit_note AS cn ON cn.Order_ID=o.Order_ID LEFT JOIN orders AS o2 ON o2.Return_ID=r.Return_ID LEFT JOIN order_line AS ol2 ON ol2.Order_ID=o2.Order_ID WHERE o.Order_Prefix='T' AND o.Created_On>='%s' AND o.Created_On<'%s' AND o.Created_By=%d AND r.Status LIKE 'Resolved' GROUP BY o.Order_ID", mysql_real_escape_string($start), mysql_real_escape_string($end), mysql_real_escape_string($accountManagerId)));
			while($data->Row) {
				$totalOrder += $data->Row['Order_Total'];
				$totalReturn += $data->Row['Return_Total'];
				$totalCredit += $data->Row['Credit_Total'];
				$totalDespatch += $data->Row['Despatch_Total'];
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><a href="order_details.php?orderid=<?php echo $data->Row['Order_ID']; ?>"><?php echo $data->Row['Order_ID']; ?></a></td>
					<td><?php echo $data->Row['Note']; ?></td>
					<td align="right"><?php echo number_format($data->Row['Order_Total'], 2, '.', ','); ?></td>
					<td align="right"><?php echo number_format($data->Row['Return_Total'], 2, '.', ','); ?></td>
					<td align="right"><?php echo number_format($data->Row['Credit_Total'], 2, '.', ','); ?></td>
					<td align="right"><?php echo number_format($data->Row['Despatch_Total'], 2, '.', ','); ?></td>
				</tr>

				<?php
				$data2 = new DataQuery(sprintf("SELECT rl.Quantity, p.Product_ID, p.Product_Title FROM return_line AS rl INNER JOIN product as p ON rl.Product_ID=p.Product_ID WHERE rl.Return_ID=%d", $data->Row['Return_ID']));
				if($data2->TotalRows > 0) {
					?>

					<tr>
						<td>&nbsp;</td>
						<td colspan="5">

							<table width="100%" border="0" class="orderDetails">
								<tr>
									<th style="text-align: left;" width="10%">Qty</th>
									<th style="text-align: left;">Received Product</th>
									<th style="text-align: left;" width="10%">Quickfind</th>
								</tr>

								<?php
								while($data2->Row) {
									?>

									<tr>
										<td><?php echo $data2->Row['Quantity']; ?></td>
										<td><?php echo strip_tags($data2->Row['Product_Title']); ?></td>
										<td><?php echo $data2->Row['Product_ID']; ?></td>
									</tr>

									<?php
									$data2->Next();
								}
								?>

							</table>

						</td>
					</tr>

					<?php
				}
				$data2->Disconnect();

				$data2 = new DataQuery(sprintf("SELECT rl.Quantity, p.Product_ID, p.Product_Title FROM return_line_despatch AS rl INNER JOIN product as p ON rl.Product_ID=p.Product_ID WHERE rl.Return_ID=%d", $data->Row['Return_ID']));
				if($data2->TotalRows > 0) {
					?>

					<tr>
						<td>&nbsp;</td>
						<td colspan="5">

							<table width="100%" border="0" class="orderDetails">
								<tr>
									<th style="text-align: left;" width="10%">Qty</th>
									<th style="text-align: left;">Despatched Product</th>
									<th style="text-align: left;" width="10%">Quickfind</th>
								</tr>

								<?php
								while($data2->Row) {
									?>

									<tr>
										<td><?php echo $data2->Row['Quantity']; ?></td>
										<td><?php echo strip_tags($data2->Row['Product_Title']); ?></td>
										<td><?php echo $data2->Row['Product_ID']; ?></td>
									</tr>

									<?php
									$data2->Next();
								}
								?>

							</table>

						</td>
					</tr>

					<?php
				}
				$data2->Disconnect();

				$data->Next();
			}
			$data->Disconnect();
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td align="right"><strong><?php echo number_format($totalOrder, 2, '.', ','); ?><strong></td>
				<td align="right"><strong><?php echo number_format($totalReturn, 2, '.', ','); ?><strong></td>
				<td align="right"><strong><?php echo number_format($totalCredit, 2, '.', ','); ?><strong></td>
				<td align="right"><strong><?php echo number_format($totalDespatch, 2, '.', ','); ?><strong></td>
			</tr>
		</table>

		<?php
	}

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>