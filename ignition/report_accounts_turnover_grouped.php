<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/chart/libchart.php');

$accountManagers = array();

$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Person_Name FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID INNER JOIN contact_account AS ca ON ca.Account_Manager_ID=u.User_ID GROUP BY u.User_ID ORDER BY Person_Name ASC"));
while($data->Row) {
	$accountManagers[$data->Row['User_ID']] = $data->Row['Person_Name'];

	$data->Next();
}
$data->Disconnect();

$startDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y') - 3));
$endDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')));

$dates = array();

$tempDate = $startDate;

while(($tempTime = strtotime($tempDate)) < ($endTime = strtotime($endDate))) {
	$nextEndDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', $tempTime) + 1, 1, date('Y', $tempTime)));

	$dates[] = array('Start' => $tempDate, 'End' => $nextEndDate);

	$tempDate = $nextEndDate;
}

new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_exclude_order SELECT MIN(o.Order_ID) AS Order_ID FROM orders AS o INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact_account AS ca ON ca.Contact_ID=cu.Contact_ID GROUP BY cu.Customer_ID"));
new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_include_order SELECT ca.Account_Manager_ID, o.Order_ID, o.Total, o.Created_On, SUM((ol.Line_Total - ol.Line_Discount) - (ol.Cost * ol.Quantity)) AS Profit FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact_account AS ca ON ca.Contact_ID=cu.Contact_ID AND ((ca.Start_Account_On='0000-00-00 00:00:00' AND ca.End_Account_On='0000-00-00 00:00:00') OR (ca.Start_Account_On='0000-00-00 00:00:00' AND o.Created_On<ca.End_Account_On) OR (o.Created_On>=ca.Start_Account_On AND ca.End_Account_On='0000-00-00 00:00:00') OR (o.Created_On>=ca.Start_Account_On AND o.Created_On<ca.End_Account_On)) WHERE o.Created_On>='%s' AND o.Created_On<'%s' GROUP BY o.Order_ID", $startDate, $endDate));

new DataQuery(sprintf("ALTER TABLE temp_include_order ADD INDEX Order_ID (Order_ID)"));
new DataQuery(sprintf("ALTER TABLE temp_include_order ADD INDEX Account_Manager_ID (Account_Manager_ID)"));
new DataQuery(sprintf("ALTER TABLE temp_include_order ADD INDEX Created_On (Created_On)"));

$data = new DataQuery(sprintf("SELECT Order_ID FROM temp_exclude_order"));
while($data->Row) {
	new DataQuery(sprintf("DELETE FROM temp_include_order WHERE Order_ID=%d", $data->Row['Order_ID']));

	$data->Next();
}
$data->Disconnect();

$page = new Page('Accounts Turnover Grouped Report', '');
$page->Display('header');

$chartFileName = $GLOBALS['SESSION_USER_ID'].'_'.rand(0, 99999);
$chartWidth = 900;
$chartHeight = 600;
$chartTitle = 'Recurring Turnover';
$chartReference = sprintf('temp/charts/chart_%s.png', $chartFileName);

$chartFileName2 = $GLOBALS['SESSION_USER_ID'].'_'.rand(0, 99999);
$chartWidth2 = 900;
$chartHeight2 = 600;
$chartTitle2 = 'Recurring Profit';
$chartReference2 = sprintf('temp/charts/chart_%s.png', $chartFileName2);

$legend = array();

foreach($accountManagers as $userId=>$accountManager) {
	$legend[] = $accountManager;
}

$chart = new LineChart(900, 600, $legend);
$chart2 = new LineChart(900, 600, $legend);

foreach($dates as $dateItem) {
	$points = array();
	$points2 = array();

	foreach($accountManagers as $userId=>$accountManager) {
		$data = new DataQuery(sprintf("SELECT SUM(Total) AS Turnover, SUM(Profit) AS Profit FROM temp_include_order WHERE Account_Manager_ID=%d AND Created_On>='%s' AND Created_On<'%s'", mysql_real_escape_string($userId), $dateItem['Start'], $dateItem['End']));

		$points[] = $data->Row['Turnover'];
		$points2[] = $data->Row['Profit'];

		$data->Disconnect();
	}

	$chart->addPoint(new Point(date('M Y', strtotime($dateItem['Start'])), $points));
	$chart2->addPoint(new Point(date('M Y', strtotime($dateItem['Start'])), $points2));
}

$chart->SetTitle($chartTitle);
$chart->SetLabelY('Turnover');
$chart->ReduceLabels = false;
$chart->ShowText = false;
$chart->render($chartReference);

$chart2->SetTitle($chartTitle2);
$chart2->SetLabelY('Profit');
$chart2->ReduceLabels = false;
$chart2->ShowText = false;
$chart2->render($chartReference2);
?>

<br />
<h3>Accounts Turnover</h3>
<p>Recurring turnover statistics for each account manager.</p>

<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
	<img src="<?php echo $chartReference; ?>" width="<?php print $chartWidth; ?>" height="<?php print $chartHeight; ?>" alt="<?php print $chartTitle; ?>" />
</div>

<br />

<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
	<img src="<?php echo $chartReference2; ?>" width="<?php print $chartWidth2; ?>" height="<?php print $chartHeight2; ?>" alt="<?php print $chartTitle2; ?>" />
</div>

<script language="javascript">
	window.onload = function() {
		var httpRequest = new HttpRequest();
		httpRequest.post('lib/util/removeChart.php', 'chart=<?php print $chartReference; ?>');

		var httpRequest2 = new HttpRequest();
		httpRequest2.post('lib/util/removeChart.php', 'chart=<?php print $chartReference2; ?>');
	}
</script>

<?php
$page->Display('footer');

require_once('lib/common/app_footer.php');
?>