<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

if($action == 'report') {
	$session->Secure(2);
	report();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$dates = array();

	$start = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y') - 1));
	$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')));

	$tempDate = $start;
	$tempIndex = 0;

	while(true) {
		$tempIndex++;
		$nextDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m') + $tempIndex, 1, date('Y') - 1));

		$dates[] = array('Start' => $tempDate, 'End' => $nextDate);

		$tempDate = $nextDate;

		if(strtotime($tempDate) > strtotime($end)) {
			break;
		}
	}

	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('manager', 'Account Manager', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('manager', '', '');
	$form->AddField('date', 'Date', 'select', '', 'anything', 1, 19);
	$form->AddOption('date', '', '');

	foreach($dates as $date) {
		$form->AddOption('date', $date['Start'], date('Y - F', strtotime($date['Start'])));
	}

	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Person_Name FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID INNER JOIN contact AS c ON c.Account_Manager_ID=u.User_ID GROUP BY u.User_ID ORDER BY Person_Name ASC"));
	while($data->Row) {
		$form->AddOption('manager', $data->Row['User_ID'], $data->Row['Person_Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()) {
			redirect(sprintf("Location: %s?action=report&accountmanagerid=%d&date=%s", $_SERVER['PHP_SELF'], $form->GetValue('manager'), $form->GetValue('date')));
		}
	}

	$page = new Page('Accounts Turnover Report', 'Please choose an account manager for your report');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Accounts Turnover.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select the account manager and date to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('manager'), $form->GetHTML('manager'));
	echo $webForm->AddRow($form->GetLabel('date'), $form->GetHTML('date'));
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

function report() {
	$user = new User($_REQUEST['accountmanagerid']);
	$turnover = array();

	$start = $_REQUEST['date'];
	$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($start)) + 1, 1, date('Y', strtotime($start))));

	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_order SELECT MIN(o.Order_ID) AS Order_ID FROM orders AS o INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID AND c.Account_Manager_ID=%d GROUP BY cu.Customer_ID", mysql_real_escape_string($user->ID), $start, $end));

	$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT o.Order_ID) AS Orders, SUM(o.Total) AS Turnover, SUM(o.Total) / COUNT(DISTINCT o.Order_ID) AS Average_Total FROM orders AS o INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID AND c.Account_Manager_ID=%d LEFT JOIN temp_order AS to1 ON to1.Order_ID=o.Order_ID WHERE to1.Order_ID IS NULL AND o.Order_Prefix='T' AND o.Created_On>='%s' AND o.Created_On<'%s'", mysql_real_escape_string($user->ID), $start, $end));
	$turnover['Recurring'] = $data->Row;
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT o.Order_ID) AS Orders, SUM(o.Total) AS Turnover FROM orders AS o INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID AND c.Account_Manager_ID=%d WHERE o.Order_Prefix='T' AND o.Created_On>='%s' AND o.Created_On<'%s'", mysql_real_escape_string($user->ID), $start, $end));
	$turnover['All'] = $data->Row;
	$data->Disconnect();

	new DataQuery(sprintf("DROP TABLE temp_order"));

	$page = new Page('Accounts Turnover Report: ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->Display('header');
	?>

	<br />
	<h3>Turnover Summary for <?php echo trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)); ?></h3>
	<p>Listing recurring turnover details for this account manager for the selected month.</p>

	<table width="100%" border="0" >
		<tr>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Date</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Orders (All)</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Orders (Recurring)</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa; text-align: right;"><strong>Turnover (All)</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa; text-align: right;"><strong>Turnover (Recurring)</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa; text-align: right;"><strong>Average Order Total</strong></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td><?php echo date('Y - F', strtotime($start)); ?></td>
			<td><?php echo $turnover['All']['Orders']; ?></td>
			<td><?php echo $turnover['Recurring']['Orders']; ?></td>
			<td align="right">&pound;<?php echo number_format($turnover['All']['Turnover'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($turnover['Recurring']['Turnover'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($turnover['Recurring']['Average_Total'], 2, '.', ','); ?></td>
		</tr>
	</table><br />

	<br />
	<h3>Recurring Orders for <?php echo trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)); ?></h3>
	<p>Listing all recurring invoiced orders for this account manager for the selected month.</p>

	<table width="100%" border="0" >
		<tr>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Order Date</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Order ID</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Customer</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa; text-align: right;"><strong>Sub Total</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa; text-align: right;"><strong>Order Total</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa; text-align: right;"><strong># Orders</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa; text-align: right;"><strong>Profit</strong></td>
		</tr>

		<?php
		new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_order SELECT MIN(o.Order_ID) AS Order_ID FROM orders AS o INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID AND c.Account_Manager_ID=%d GROUP BY cu.Customer_ID", mysql_real_escape_string($user->ID)));
		new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_order_collection SELECT o.*, CONCAT_WS(' ', o.Billing_First_Name, o.Billing_Last_Name, IF(LENGTH(o.Billing_Organisation_Name)>0, CONCAT('(', o.Billing_Organisation_Name, ')'), '')) AS Customer_Name, COUNT(DISTINCT o2.Order_ID) AS Total_Orders FROM orders AS o INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID AND c.Account_Manager_ID=%d INNER JOIN orders AS o2 ON o.Customer_ID=o2.Customer_ID LEFT JOIN temp_order AS to1 ON to1.Order_ID=o.Order_ID WHERE to1.Order_ID IS NULL AND o.Order_Prefix='T' AND o.Created_On>='%s' AND o.Created_On<'%s' GROUP BY o.Order_ID", mysql_real_escape_string($user->ID), $start, $end));

		$data = new DataQuery(sprintf("SELECT o.*, SUM((ol.Line_Total - ol.Line_Discount) - (ol.Cost * ol.Quantity)) AS Profit FROM temp_order_collection AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID GROUP BY o.Order_ID"));
		if($data->TotalRows > 0) {
			$totalSub = 0;
			$total = 0;
			$totalProfit = 0;

			while($data->Row) {
				$totalSub += $data->Row['SubTotal'];
				$total += $data->Row['Total'];
				$totalProfit += $data->Row['Profit'];
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $data->Row['Created_On']; ?></td>
					<td><a href="order_details.php?orderid=<?php echo $data->Row['Order_ID']; ?>"><?php echo $data->Row['Order_ID']; ?></a></td>
					<td><?php echo $data->Row['Customer_Name']; ?></td>
					<td align="right">&pound;<?php echo number_format($data->Row['SubTotal'] - $data->Row['TotalDiscount'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($data->Row['Total'], 2, '.', ','); ?></td>
					<td align="right"><?php echo $data->Row['Total_Orders']; ?></td>
					<td align="right">&pound;<?php echo number_format($data->Row['Profit'], 2, '.', ','); ?></td>
				</tr>

				<?php
				$data->Next();
			}
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td align="right"><strong>&pound;<?php echo number_format($totalSub, 2, '.', ','); ?></strong></td>
				<td align="right"><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
				<td>&nbsp;</td>
				<td align="right"><strong>&pound;<?php echo number_format($totalProfit, 2, '.', ','); ?></strong></td>
			</tr>

			<?php
		} else {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td align="center" colspan="6">There are no items available for viewing.</td>
			</tr>

			<?php
		}
		$data->Disconnect();
		?>

	</table><br />

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>