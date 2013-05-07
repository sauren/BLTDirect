<?php
require_once('lib/common/app_header.php');

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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('range', 'Date Range', 'select', 'none', 'alpha_numeric', 0, 32);
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
	$form->AddField('user', 'User', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('user', '', '');

	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT(p.Name_First, ' ', p.Name_Last) AS Name FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('user', $data->Row['User_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if(($form->GetValue('range') != 'none') && (strlen($form->GetValue('range')) > 1)) {

			$form->InputFields['start']->Required = false;
			$form->InputFields['end']->Required = false;

			if($form->Validate()) {
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

				redirect(sprintf('Location: ?action=report&start=%s&end=%s&user=%d', $start, $end, $form->GetValue('user')));
			}
		} else {
			if($form->Validate()) {
				redirect(sprintf('Location: ?action=report&start=%s&end=%s&user=%d', sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))), $form->GetValue('user')));
			}
		}
	}

	$page = new Page('Orders Demo Products Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Orders Demo Products.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select a sales rep for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('user'), $form->GetHTML('user'));
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
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

    $form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Start Date', 'hidden', '0000-00-00 00:00:00', 'anything', 1, 19);
	$form->AddField('end', 'End Date', 'hidden', '0000-00-00 00:00:00', 'anything', 1, 19);
	$form->AddField('user', 'Sales Rep', 'hidden', '0', 'numeric_unsigned', 1, 11);

	$page = new Page('Orders Demo Products Report: ' . cDatetime($form->GetValue('start'), 'longdatetime') . ' to ' . cDatetime($form->GetValue('end'), 'longdatetime'), '');
	$page->Display('header');
	?>

	<br />
	<h3>Ordered Demo Products</h3>
	<p>Listing demo products ordered by the selected user for the chosen period.</p>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Order Ref</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Order Date</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Product ID</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Product</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;" align="right"><strong>Quantity</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;" align="right"><strong>Total</strong></td>
		</tr>

		<?php
		$data = new DataQuery(sprintf("SELECT o.Order_Prefix, o.Order_ID, o.Created_On, p.Product_ID, p.Product_Title, ol.Quantity, ol.Line_Total-ol.Line_Discount AS Total FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN product AS p ON p.Product_ID=ol.Product_ID AND p.Is_Demo_Product='Y' WHERE o.Created_By=%d AND o.Created_On>'%s' AND o.Created_On<'%s'", $form->GetValue('user'), mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
		if($data->TotalRows > 0) {
			while($data->Row) {
				?>

				<tr>
					<td><a href="order_details.php?orderid=<?php echo $data->Row['Order_ID']; ?>"><?php echo $data->Row['Order_Prefix'].$data->Row['Order_ID']; ?></a></td>
					<td><?php echo $data->Row['Created_On']; ?></td>
					<td><a href="product_profile.php?pid=<?php echo $data->Row['Product_ID']; ?>"><?php echo $data->Row['Product_ID']; ?></a></td>
					<td><?php echo strip_tags($data->Row['Product_Title']); ?></td>
					<td align="right"><?php echo $data->Row['Quantity']; ?></td>
					<td align="right">&pound;<?php echo number_format(round($data->Row['Total'], 2), 2, '.', ','); ?></td>
				</tr>

				<?php
				$data->Next();
			}
		} else {
			?>

            <tr>
				<td colspan="6" align="center">There are no items available for viewing.</td>
			</tr>

			<?php
		}
		$data->Disconnect();
		?>

	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}