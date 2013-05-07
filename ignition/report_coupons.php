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

			report($start, $end);
			exit;
		} else {
			
			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))));
				exit;
			}
		}
	}

	$page = new Page('Coupon Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');
	
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Report on Coupons.");
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

	$script = '<script language="javascript" type="text/javascript">
		var toggleData = function(hash) {
			var data = document.getElementById(\'data-\' + hash);
			var image = document.getElementById(\'image-\' + hash);
			
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
		</script>';
	
	$page = new Page('Coupon Report : ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->AddToHead($script);
	$page->Display('header');
	?>
	
	<br />
	<h3>Coupon Sales</h3>
	<p>The number of orders and the total turnover gained from each coupon.</p>
	
	<table width="100%" border="0">
		<tr>
			<th style="border-bottom:1px solid #aaaaaa;" width="1%">&nbsp;</th>
			<th style="border-bottom:1px solid #aaaaaa;" align="left">Coupon Name</th>
			<th style="border-bottom:1px solid #aaaaaa;" align="left">Coupon ID</th>
			<th style="border-bottom:1px solid #aaaaaa;" align="right">Orders</th>
			<th style="border-bottom:1px solid #aaaaaa;" align="right">Turnover</th>
		</tr>

		<?php
		$data = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS OrderCount, c.Coupon_ID, c.Coupon_Title, SUM(Total) AS Total FROM orders AS o INNER JOIN coupon AS c ON o.Coupon_ID=c.Coupon_ID WHERE o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY o.Coupon_ID ORDER BY c.Coupon_Title ASC", $start, $end));
		while($data->Row) {
			$hash = md5($data->Row['Coupon_ID']);
			?>
			
			<tr class="dataRow">
				<td><a href="javascript:toggleData('<?php echo $hash; ?>');"><img src="images/button-plus.gif" id="image-<?php echo $hash; ?>" /></a></td>
				<td><?php echo $data->Row['Coupon_Title']; ?></td>
				<td><a href="discount_coupon_settings.php?coupon=<?php echo $data->Row['Coupon_ID']; ?>"><?php echo $data->Row['Coupon_ID']; ?></a></td>
				<td align="right"><?php echo $data->Row['OrderCount']; ?></td>
				<td align="right">&pound;<?php echo number_format($data->Row['Total'], 2, '.', ','); ?></td>
			</tr>
			<tr style="display: none;" id="data-<?php echo $hash; ?>">
				<td>&nbsp;</td>
				<td colspan="4">
				
					<table width="100%" border="0">
						<tr>
							<th width="10%" style="border-bottom:1px solid #aaaaaa; padding-right: 5px;" align="left">Order Reference</th>
							<th width="30%" style="border-bottom:1px solid #aaaaaa; padding-right: 5px;" align="left">Ordered On</th>
							<th width="30%" style="border-bottom:1px solid #aaaaaa; padding-right: 5px;" align="left">Contact</th>
							<th width="30%" style="border-bottom:1px solid #aaaaaa; padding-right: 5px;" align="right">Total</th>
						</tr>
						
						<?php
						$data2 = new DataQuery(sprintf("SELECT *, CONCAT_WS(' ', Billing_First_Name, Billing_Last_Name, IF(LENGTH(Billing_Organisation_Name)>0, CONCAT('(', Billing_Organisation_Name, ')'), '')) AS Billing_Contact FROM orders WHERE Created_On BETWEEN '%s' AND '%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Coupon_ID=%d", $start, $end, mysql_real_escape_string($data->Row['Coupon_ID'])));
						while($data2->Row) {
							?>
			
							<tr>
								<td><a href="order_details.php?orderid=<?php echo $data2->Row['Order_ID']; ?>"><?php echo $data2->Row['Order_Prefix'] . $data2->Row['Order_ID']; ?></a></td>
								<td><?php echo $data2->Row['Created_On']; ?></td>
								<td><?php echo $data2->Row['Billing_Contact']; ?></td>
								<td align="right">&pound;<?php echo number_format($data2->Row['Total'], 2, '.', ','); ?></td>
							</tr>
							
							<?php
							$data2->Next();
						}
						$data2->Disconnect();
						?>
						
					</table>					
				
				</td>
			</tr>
				
			<?php
			$data->Next();
		}
		$data->Disconnect();
		?>
		
	</table>
	
	<?php
	$page->Display('footer');
}