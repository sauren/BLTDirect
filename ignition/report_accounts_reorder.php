<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/chart/libchart.php');

$accountManagers = array();

$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Person_Name FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID INNER JOIN contact AS c ON c.Account_Manager_ID=u.User_ID WHERE u.User_ID=8 OR u.User_ID=5 OR u.User_ID=20 OR u.User_ID=31 GROUP BY u.User_ID ORDER BY Person_Name ASC"));
while($data->Row) {
	$accountManagers[$data->Row['User_ID']] = $data->Row['Person_Name'];

	$data->Next();
}
$data->Disconnect();

$startDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y') - 1));
$endDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')));

$dates = array();

$tempDate = $startDate;

while(($tempTime = strtotime($tempDate)) < ($endTime = strtotime($endDate))) {
	$nextEndDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', $tempTime) + 1, 1, date('Y', $tempTime)));

	$dates[] = array('Start' => $tempDate, 'End' => $nextEndDate);

	$tempDate = $nextEndDate;
}

new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_exclude_order SELECT c.Account_Manager_ID, MIN(o.Order_ID) AS Order_ID FROM orders AS o INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID WHERE c.Account_Manager_ID>0 GROUP BY cu.Customer_ID"));
new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_include_order SELECT c.Account_Manager_ID, o.Order_ID, o.Customer_ID, o.Total, o.Created_On, SUM((ol.Line_Total - ol.Line_Discount) - (ol.Cost * ol.Quantity)) AS Profit FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID WHERE c.Account_Manager_ID>0 AND o.Created_On>='%s' AND o.Created_On<'%s' GROUP BY o.Order_ID", $startDate, $endDate));
new DataQuery(sprintf("ALTER TABLE temp_include_order ADD PRIMARY KEY (Order_ID)"));
new DataQuery(sprintf("ALTER TABLE temp_include_order ADD INDEX Account_Manager_ID (Account_Manager_ID)"));
new DataQuery(sprintf("ALTER TABLE temp_include_order ADD INDEX Created_On (Created_On)"));

$data = new DataQuery(sprintf("SELECT Order_ID FROM temp_exclude_order"));
while($data->Row) {
	new DataQuery(sprintf("DELETE FROM temp_include_order WHERE Order_ID=%d", $data->Row['Order_ID']));

	$data->Next();
}
$data->Disconnect();

$page = new Page('Accounts Reorder Report', '');
$page->Display('header');

$chartFileName = $GLOBALS['SESSION_USER_ID'].'_'.rand(0, 99999);
$chartWidth = 900;
$chartHeight = 600;
$chartTitle = 'Reorder Percentage';
$chartReference = sprintf('temp/charts/chart_%s.png', $chartFileName);

$legend = array();

foreach($accountManagers as $userId=>$accountManager) {
	$legend[] = $accountManager;
}

$chart = new LineChart(900, 600, $legend);

foreach($dates as $dateItem) {
	$points = array();

	foreach($accountManagers as $userId=>$accountManager) {
		$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT c.Contact_ID) AS Count FROM contact AS c INNER JOIN customer AS cu ON cu.Contact_ID=c.Contact_ID INNER JOIN orders AS o ON o.Customer_ID=cu.Customer_ID WHERE c.Account_Manager_ID=%d GROUP BY c.Contact_ID", mysql_real_escape_string($userId)));
		$accounts = $data->Row['Count'];
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT Customer_ID) AS Count FROM temp_include_order WHERE Account_Manager_ID=%d AND Created_On>='%s' AND Created_On<'%s'", mysql_real_escape_string($userId), $dateItem['Start'], $dateItem['End']));
		$reorderFrequency = $data->Row['Count'];
		$data->Disconnect();

		$points[] = round(($reorderFrequency / $accounts) * 100);
	}

	$chart->addPoint(new Point(date('M Y', strtotime($dateItem['Start'])), $points));
}

$chart->SetTitle($chartTitle);
$chart->SetLabelY('Percentage');
$chart->ReduceLabels = false;
$chart->ShowText = false;
$chart->render($chartReference);
?>

<br />
<h3>Accounts Reorder</h3>
<p>Identifying the percentage reorder for each account manager.</p>

<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
	<img src="<?php echo $chartReference; ?>" width="<?php print $chartWidth; ?>" height="<?php print $chartHeight; ?>" alt="<?php print $chartTitle; ?>" />
</div>

<script language="javascript">
	window.onload = function() {
		var httpRequest = new HttpRequest();
		httpRequest.post('lib/util/removeChart.php', 'chart=<?php print $chartReference; ?>');
	}
</script>

<?php
$page->Display('footer');

require_once('lib/common/app_footer.php');
?>