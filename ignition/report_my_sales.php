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
                case 'all':         $start = date('Y-m-d H:i:s', 0);
                                    $end = date('Y-m-d H:i:s');
                                    break;

                case 'thisminute':     $start = date('Y-m-d H:i:00');
                                    $end = date('Y-m-d H:i:s');
                                    break;
                case 'thishour':     $start = date('Y-m-d H:00:00');
                                    $end = date('Y-m-d H:i:s');
                                    break;
                case 'thisday':     $start = date('Y-m-d 00:00:00');
                                    $end = date('Y-m-d H:i:s');
                                    break;
                case 'thismonth':     $start = date('Y-m-01 00:00:00');
                                    $end = date('Y-m-d H:i:s');
                                    break;
                case 'thisyear':     $start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")));
                                    $end = date('Y-m-d H:i:s');
                                    break;

                case 'lasthour':     $start = date('Y-m-d H:00:00', mktime(date("H")-1, 0, 0, date("m"), date("d"),  date("Y")));
                                    $end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
                                    break;
                case 'last3hours':     $start = date('Y-m-d H:00:00', mktime(date("H")-3, 0, 0, date("m"), date("d"),  date("Y")));
                                    $end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
                                    break;
                case 'last6hours':     $start = date('Y-m-d H:00:00', mktime(date("H")-6, 0, 0, date("m"), date("d"),  date("Y")));
                                    $end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
                                    break;
                case 'last12hours': $start = date('Y-m-d H:00:00', mktime(date("H")-12, 0, 0, date("m"), date("d"),  date("Y")));
                                    $end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
                                    break;

                case 'lastday':     $start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
                                    $end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
                                    break;
                case 'last2days':     $start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-2, date("Y")));
                                    $end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
                                    break;
                case 'last3days':     $start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));
                                    $end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
                                    break;

                case 'lastmonth':     $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-1, 1,  date("Y")));
                                    $end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
                                    break;
                case 'last3months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-3, 1,  date("Y")));
                                    $end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
                                    break;
                case 'last6months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-6, 1,  date("Y")));
                                    $end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
                                    break;

                case 'lastyear':     $start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-1));
                                    $end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
                                    break;
                case 'last2years':     $start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-2));
                                    $end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
                                    break;
                case 'last3years':     $start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-3));
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

    $page = new Page('My Sales', 'Please choose a start and end date for your report');
    $page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
    $page->Display('header');

    if(!$form->Valid){
        echo $form->GetError();
        echo "<br>";
    }

    $window = new StandardWindow("Report on My Sales.");
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

    $orderTypes = array();
    $orderTypes['W'] = "Website (bltdirect.com)";
    $orderTypes['U'] = "Website (bltdirect.co.uk)";
    $orderTypes['L'] = "Website (lightbulbsuk.co.uk)";
    $orderTypes['M'] = "Mobile";
    $orderTypes['T'] = "Telesales";
    $orderTypes['F'] = "Fax";
    $orderTypes['E'] = "Email";
    $orderTypes['R'] = "Return";
    $orderTypes['B'] = "Broken";
    $orderTypes['N'] = "Not Received";

    $page = new Page('My Sales Report : ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
    $page->Display('header');
    ?>

    <br />
    <h3>Sales Figures</h3>

    <table width="100%" border="0">
        <tr>
            <td style="border-bottom:1px solid #aaaaaa"><strong>Order Type</strong></td>
            <td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Number</strong></td>
            <td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Net</strong></td>
        </tr>

        <?php
        $totals = array();
        $data = new DataQuery(sprintf("SELECT o.Order_Prefix, o.TotalTax, o.Total FROM orders AS o INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID WHERE o.Created_On>='%s' AND o.Created_On<'%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.Owned_By=%d GROUP BY o.Order_ID ORDER BY o.Order_ID ASC", $start, $end, mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

        while($data->Row) {
            if(!isset($totals[$data->Row['Order_Prefix']])) {
                $totals[$data->Row['Order_Prefix']] = array('OrderCount' => 0, 'TotalNet' => 0,);
            }

            $totals[$data->Row['Order_Prefix']]['OrderCount']++;
            $totals[$data->Row['Order_Prefix']]['TotalNet'] += $data->Row['Total'] - $data->Row['TotalTax'];

            $data->Next();
        }
        $data->Disconnect();

        ksort($totals);

        $totalOrders = 0;
        $totalNet = 0;
        
        foreach($totals as $prefix=>$totalData) {
        	$totalOrders += $totalData['OrderCount'];
            $totalNet += $totalData['TotalNet'];
            ?>

            <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
                <td><?php echo $orderTypes[$prefix]; ?></td>
                <td align="right"><?php echo $totalData['OrderCount']; ?></td>
                <td align="right">&pound;<?php echo number_format($totalData['TotalNet'], 2, '.', ','); ?></td>
            </tr>

            <?php
        }
        ?>

        <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
            <td><strong>Total</strong></td>
            <td align="right"><strong><?php echo $totalOrders; ?></strong></td>
            <td align="right"><strong>&pound;<?php echo number_format($totalNet, 2, '.', ','); ?></strong></td>
        </tr>
    </table>

    <br />
    <h3>Orders</h3>

    <table width="100%" border="0">
        <tr>
            <td style="border-bottom:1px solid #aaaaaa"><strong>Order Date</strong></td>
            <td style="border-bottom:1px solid #aaaaaa"><strong>Customer</strong></td>
            <td style="border-bottom:1px solid #aaaaaa"><strong>Order Reference</strong></td>
            <td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Net</strong></td>
        </tr>

        <?php
        $data = new DataQuery(sprintf("SELECT o.Order_Prefix, o.Order_ID, o.TotalTax, o.Total, o.Billing_First_Name, o.Billing_Last_Name, cu.Contact_ID, o.Created_On FROM orders AS o INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID WHERE o.Created_On>='%s' AND o.Created_On<'%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')  AND o.Owned_By=%d GROUP BY o.Order_ID ORDER BY o.Order_ID ASC", $start, $end, mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
        while($data->Row) {
            ?>

            <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
                <td><?php echo cDatetime($data->Row['Created_On'], 'shortdatetime'); ?></td>
                <td><?php echo trim(sprintf('%s %s', $data->Row['Billing_First_Name'], $data->Row['Billing_Last_Name'])); ?></td>
                <td><?php echo sprintf('%s%d', $data->Row['Order_Prefix'], $data->Row['Order_ID']); ?></td>
                <td align="right">&pound;<?php echo number_format($data->Row['Total'] - $data->Row['TotalTax'], 2, '.', ','); ?></td>
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