<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/chart/libchart.php');

$session->Secure(2);

$user = new User($session->UserID);

$chart1FileName = 'welcome' . rand(0, 9999999);
$chart1Width = 900;
$chart1Height = 300;
$chart1Title = 'Recent activity over the past week';
$chart1Reference = sprintf('temp/charts/chart_%s.png', $chart1FileName);

$chart1 = new LineChart($chart1Width, $chart1Height);

$graphData = array();

$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count, DATE(Created_On) AS Day, HOUR(Created_On) AS Hour FROM customer_session_item GROUP BY Day, Hour ORDER BY Day ASC, Hour ASC"));
while($data->Row) {
	$time = strtotime(sprintf('%s %s%s:00:00', $data->Row['Day'], ($data->Row['Hour'] < 10) ? '0' : '', $data->Row['Hour']));

	$graphData[date('H:00 (jS)', $time)]['Hits'] = $data->Row['Count'];

	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count, DATE(Created_On) AS Day, HOUR(Created_On) AS Hour FROM customer_session GROUP BY Day, Hour ORDER BY Day ASC, Hour ASC"));
while($data->Row) {
	$time = strtotime(sprintf('%s %s%s:00:00', $data->Row['Day'], ($data->Row['Hour'] < 10) ? '0' : '', $data->Row['Hour']));

	$graphData[date('H:00 (jS)', $time)]['Visitors'] = $data->Row['Count'];

	$data->Next();
}
$data->Disconnect();

foreach($graphData as $period=>$graphItem) {
	$chart1->addPoint(new Point($period, array(isset($graphItem['Hits']) ? $graphItem['Hits'] : 0, isset($graphItem['Visitors']) ? $graphItem['Visitors'] : 0)));
}

$chart1->SetTitle($chart1Title);
$chart1->SetLabelY('Connections per hour');
$chart1->ShowText = false;
$chart1->ShowLabels = true;
$chart1->LabelInterval = 6;
$chart1->render($chart1Reference);

$page = new Page('');
$page->DisableTitle();
$page->Display('header');

$backupShow = false;
$backupDays = 0;

$data = new DataQuery(sprintf("SELECT Created_On FROM library_file WHERE SRC LIKE '%%sage%%' AND SRC LIKE '%%.001' ORDER BY Created_On DESC LIMIT 0, 1"));
if($data->TotalRows > 0) {
	$days = (int) date_diff_days($data->Row['Created_On'], date('Y-m-d H:i:s'));

	if($days >= Setting::GetValue('sage_backup_days')) {
		$backupShow = true;
		$backupDays = $days;
	}
} else {
	$backupShow = true;
}
$data->Disconnect();

if($backupShow) {
	$bubble = new Bubble('Sage Backup Warning', ($backupDays > 0) ? sprintf('There has been no Sage backup detected in the last %d days.', $backupDays) : 'There is no detected sage backup.');

	echo $bubble->GetHTML();
	echo '<br />';	
}
?>

<table width="100%">
	<tr>
		<td width="33.3%" align="center">
			<a target="_top" href="http://www.bltdirect.co.uk/ignition/login.php?username=<?php print $user->Username; ?>"><img style="border: 1px solid #eee;" src="images/sites/bltdirectcouk.jpg" /></a><br /><br />
			<strong style="color:#333;">bltdirect.co.uk</strong>
		</td>
		<td width="33.3%" align="center">
			<a target="_top" href="https://www.bltdirect.com/ignition/login.php?username=<?php print $user->Username; ?>"><img style="border: 1px solid #eee;" src="images/sites/bltdirectcom.jpg" /></a><br /><br />
			<strong style="color:#333;">bltdirect.com</strong>
		</td>
		<td width="33.3%" align="center">
			<a target="_top" href="https://www.lightbulbsuk.co.uk/ignition/login.php?username=<?php print $user->Username; ?>"><img style="border: 1px solid #eee;" src="images/sites/lightbulbsuk.jpg" /></a><br /><br />
			<strong style="color:#333;">lightbulbsuk.co.uk</strong>
		</td>
	</tr>
</table>
<br />

<div style="text-align: center; border: 1px solid #eee; margin: 10px;">
	<img src="<?php echo $chart1Reference; ?>" width="<?php print $chart1Width; ?>" height="<?php print $chart1Height; ?>" alt="<?php print $chart1Title; ?>" />
</div>

<?php
$page->Display('footer');

require_once('lib/common/app_footer.php');