<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$session->Secure(2);
start();
exit();

function start(){
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

	if(isset($_REQUEST['confirm'])) {
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

    $page = new Page('Sales Devices Report', 'Please choose a start and end date for your report');
    $page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
    $page->Display('header');

    if(!$form->Valid) {
        echo $form->GetError();
        echo '<br />';
    }

	$window = new StandardWindow("Report on Sales Devices.");
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

function report($start, $end) {
    $page = new Page('Sales Devices Report: ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'));
    $page->Display('header');
    ?>

    <h3>Device Summary</h3>
    <br />

    <table width="100%" border="0" cellpadding="3" cellspacing="0">
        <tr style="background-color: #eee;">
            <td style="border-bottom: 1px solid #ddd;"><strong>Device Platform</strong></td>
            <td style="border-bottom: 1px solid #ddd;"><strong>Number</strong></td>
            <td style="border-bottom: 1px solid #ddd;" align="right"><strong>Net</strong></td>
            <td style="border-bottom: 1px solid #ddd;" align="right"><strong>Tax</strong></td>
            <td style="border-bottom: 1px solid #ddd;" align="right"><strong>Total</strong></td>
        </tr>

        <?php
        $data = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS Count, o.DevicePlatform, SUM(o.Total) AS Total, SUM(o.TotalTax) AS TotalTax FROM orders AS o WHERE o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.DevicePlatform<>'' AND o.DeviceBrowser<>'' GROUP BY o.DevicePlatform ORDER BY o.DevicePlatform ASC, o.DeviceBrowser ASC, o.DeviceVersion ASC", $start, $end));
        while($data->Row) {
            ?>

            <tr>
                <td><?php echo $data->Row['DevicePlatform']; ?></td>
                <td><?php echo $data->Row['Count']; ?></td>
                <td align="right">&pound;<?php echo number_format($data->Row['Total']-$data->Row['TotalTax'], 2, '.', ','); ?></td>
                <td align="right">&pound;<?php echo number_format($data->Row['TotalTax'], 2, '.', ','); ?></td>
                <td align="right">&pound;<?php echo number_format($data->Row['Total'], 2, '.', ','); ?></td>
            </tr>

            <?php
            $data->Next();
        }
        $data->Disconnect();
        ?>

    </table>
    <br />

    <h3>Device Sales</h3>
    <br />

    <table width="100%" border="0" cellpadding="3" cellspacing="0">
        <tr style="background-color: #eee;">
            <td style="border-bottom: 1px solid #ddd;"><strong>Device Platform</strong></td>
            <td style="border-bottom: 1px solid #ddd;"><strong>Device Browser</strong></td>
            <td style="border-bottom: 1px solid #ddd;"><strong>Device Version</strong></td>
            <td style="border-bottom: 1px solid #ddd;"><strong>Number</strong></td>
            <td style="border-bottom: 1px solid #ddd;" align="right"><strong>Net</strong></td>
            <td style="border-bottom: 1px solid #ddd;" align="right"><strong>Tax</strong></td>
            <td style="border-bottom: 1px solid #ddd;" align="right"><strong>Total</strong></td>
        </tr>

        <?php
        $data = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS Count, o.DevicePlatform, o.DeviceBrowser, o.DeviceVersion, SUM(o.Total) AS Total, SUM(o.TotalTax) AS TotalTax FROM orders AS o WHERE o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.DevicePlatform<>'' AND o.DeviceBrowser<>'' GROUP BY o.DevicePlatform, o.DeviceBrowser, o.DeviceVersion ORDER BY o.DevicePlatform ASC, o.DeviceBrowser ASC, o.DeviceVersion ASC", $start, $end));
        while($data->Row) {
            ?>

            <tr>
                <td><?php echo $data->Row['DevicePlatform']; ?></td>
                <td><?php echo $data->Row['DeviceBrowser']; ?></td>
                <td><?php echo $data->Row['DeviceVersion']; ?></td>
                <td><?php echo $data->Row['Count']; ?></td>
                <td align="right">&pound;<?php echo number_format($data->Row['Total']-$data->Row['TotalTax'], 2, '.', ','); ?></td>
                <td align="right">&pound;<?php echo number_format($data->Row['TotalTax'], 2, '.', ','); ?></td>
                <td align="right">&pound;<?php echo number_format($data->Row['Total'], 2, '.', ','); ?></td>
            </tr>

            <?php
            $data->Next();
        }
        $data->Disconnect();
        ?>

    </table>

    <?php
    $page->Display('footer');
    require_once('lib/common/app_footer.php');
}