<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
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

function start() {
	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('user', 'User', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('user', '', '');
	
	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Name FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('user', $data->Row['User_ID'], $data->Row['Name']);
		
		$data->Next();	
	}
	$data->Disconnect();
	
	$form->AddField('month', 'Month', 'select', date('m'), 'anything', 1, 11);

	for($i=1; $i<=12; $i++) {          
		$form->AddOption('month', date('m', mktime(0, 0, 0, $i, 1, date('Y'))), date('F', mktime(0, 0, 0, $i, 1, date('Y'))));
	}

	$form->AddField('year', 'Year', 'select', date('Y'), 'anything', 1, 11);

	for($i=2006; $i<=date('Y'); $i++) {
		$form->AddOption('year', $i, $i);
	}
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			redirect(sprintf('Location: ?action=report&user=%d&month=%d&year=%d', $form->GetValue('user'), $form->GetValue('month'), $form->GetValue('year')));
		}
	}

	$page = new Page('Sales Rep Residual Report', 'Please choose a start and end date for your report');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}
	
	$window = new StandardWindow("Select report criteria.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();

	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('user'), $form->GetHTML('user') . $form->GetIcon('user'));
	echo $webForm->AddRow('Period', $form->GetHTML('month') . $form->GetHTML('year') . $form->GetIcon('year'));
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('user', 'User', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('month', 'Month', 'hidden', date('m'), 'anything', 1, 11);
	$form->AddField('year', 'Year', 'hidden', date('Y'), 'anything', 1, 11);
	
	$page = new Page('Sales Rep Residual Report', '');
	$page->Display('header');
	?>
	
	<br />
	<h3>Sales Rep Summary</h3>
	<p>Summary of sales statistics made by this sales rep.</p>
	
	<?php
	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_orders SELECT MIN(Order_ID) AS Order_ID FROM orders WHERE Status<>'Unauthenticated' AND Status<>'Cancelled' AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N' GROUP BY Customer_ID ORDER BY Order_ID"));
	new DataQuery(sprintf("CREATE INDEX Order_ID ON temp_orders (Order_ID)"));
		
	$index = 0;
	
	while($index > -12) {
		$date = strtotime(sprintf('%s-%s-23 00:00:00', $form->GetValue('year'), $form->GetValue('month')));
		$date = mktime(0, 0, 0, date('m', $date) + $index, date('d', $date), date('Y', $date));
		
		$start = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', $date), 23, date('Y', $date)));
		$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', $date) + 1, 23, date('Y', $date)));

		$data = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS Count, SUM(o.Total-o.TotalTax) AS Turnover FROM orders AS o WHERE o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' AND o.Created_On>='%s' AND o.Created_On<'%s' AND o.Owned_By=%d", mysql_real_escape_string($start), mysql_real_escape_string($end), mysql_real_escape_string($form->GetValue('user'))));
		$orderCount = $data->Row['Count'];
		$orderTotal = $data->Row['Turnover'];
		$data->Disconnect();
		$data = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS Count, SUM(o.Total-o.TotalTax) AS Turnover FROM orders AS o LEFT JOIN temp_orders AS tto ON o.Order_ID=tto.Order_ID WHERE tto.Order_ID IS NULL AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' AND o.Created_On>='%s' AND o.Created_On<'%s' AND o.Owned_By=%d", mysql_real_escape_string($start), mysql_real_escape_string($end), mysql_real_escape_string($form->GetValue('user'))));
		$reorderCount = $data->Row['Count'];
		$reorderTotal = $data->Row['Turnover'];
		$data->Disconnect();
		
		$data = new DataQuery(sprintf("SELECT SUM(b.BonusAmount) AS Total FROM bonus AS b WHERE b.StartOn>='%s' AND b.StartOn<'%s' AND b.UserID=%d", mysql_real_escape_string($start), mysql_real_escape_string($end), mysql_real_escape_string($form->GetValue('user'))));
		$bonus = $data->Row['Total'];
		$data->Disconnect();
		?>

		<table width="100%" border="0">
			<tr>
				<td style="border-bottom:1px solid #aaaaaa;"><strong>Item</strong> (<?php echo date('d/m/Y', $date); ?>)</td>
				<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Value</strong></td>
			</tr>
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>Number of Orders Owned</td>
				<td align="right"><?php echo $orderCount; ?></td>
			</tr>
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>Turnover of Orders Owned</td>
				<td align="right">&pound;<?php echo number_format($orderTotal, 2, '.', ','); ?></td>
			</tr>
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>Number of Reorders Owned</td>
				<td align="right"><?php echo $reorderCount; ?></td>
			</tr>
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>Turnover of Reorders Owned</td>
				<td align="right">&pound;<?php echo number_format($reorderTotal, 2, '.', ','); ?></td>
			</tr>
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>Bonus Awarded</td>
				<td align="right">&pound;<?php echo number_format($bonus, 2, '.', ','); ?></td>
			</tr>
		</table>
		<br />
		
		<?php
		$index--;
	}

	new DataQuery(sprintf("DROP TABLE temp_orders"));
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}