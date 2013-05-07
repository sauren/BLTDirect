<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Group Spend Report', 'Please choose a start and end date for your report');
	$year = cDatetime(getDatetime(), 'y');
	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('group', 'Contact group', 'select', 'none', 'numeric_unsigned', 1, 11);
	$form->AddOption('group', '', '-- Select --');

	$data = new DataQuery(sprintf("SELECT * FROM contact_group ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('group', $data->Row['Contact_Group_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

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

			report($form->GetValue('group'));
			exit;
		} else {
			if($form->Validate()){
				report($form->GetValue('group'));
				exit;
			}
		}
	}

	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Report on Group Spending.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();

	echo $window->AddHeader('Select a contact group for reporting on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('group'), $form->GetHTML('group'));
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

function report($group){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/chart/libchart.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactGroup.php');

	$months = 24;

	$contactGroup = new ContactGroup($group);

	$page = new Page('Group Spend Report', '');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');

	$chartFileName = $GLOBALS['SESSION_USER_ID'].'_'.rand(0, 99999);
	$chartWidth = 900;
	$chartHeight = 600;
	$chartTitle = sprintf('%s Spending Trends', ucfirst($contactGroup->Name));
	$chartReference = sprintf('temp/charts/chart_%s.png', $chartFileName);
	$chartlegend = array('Total Spending');

	$chart = new LineChart($chartWidth, $chartHeight, $chartlegend);

	for($i = $months-1; $i >= 0; $i--) {
		$points = array();

		$start = date('Y-m-00 00:00:00', mktime(0, 0, 0, date('m') + 1 - $i, 0, date('Y')));
		$end = date('Y-m-00 00:00:00', mktime(0, 0, 0, date('m') + 2 - $i, 0, date('Y')));

		$data = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS Order_Count, SUM(o.SubTotal-o.TotalDiscount) AS Order_Total FROM contact_group_assoc AS g INNER JOIN customer AS cu ON cu.Contact_ID=g.Contact_ID INNER JOIN orders AS o ON o.Customer_ID=cu.Customer_ID WHERE g.Contact_Group_ID=%d AND o.Created_On BETWEEN '%s' AND '%s' AND Status<>'Cancelled'", mysql_real_escape_string($group), $start, $end));
		if($data->TotalRows > 0) {
			$points[] = $data->Row['Order_Total'];
		} else {
			$points[] = 0;
		}
		$data->Disconnect();

		$chart->addPoint(new Point(cDatetime($end, 'shortdate'), $points));
	}

	$chart->SetTitle($chartTitle);
	$chart->SetLabelY('Order Total');
	$chart->render($chartReference);
	?>
	<br />
	<h3><?php print ucfirst($contactGroup->Name); ?> Spending</h3>
	<p>Spending statistics for the specified contact group over a number of months.</p>

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
}

require_once('lib/common/app_header.php');
?>