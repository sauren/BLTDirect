<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Referrer.php');

$session->Secure(3);

$duration = '-30 day';
if(isset($_REQUEST['duration'])) $duration = $_REQUEST['duration'];

$sessions = array();
$sessions['total'] = 0;
$sessions['google'] = 0;
$sessions['google-ppc'] = 0;
$sessions['yahoo'] = 0;
$sessions['yahoo-ppc'] = 0;
$sessions['msn'] = 0;
$sessions['msn-ppc'] = 0;

$orders = array();
$orders['total'] = 0;
$orders['google'] = 0;
$orders['google-ppc'] = 0;
$orders['yahoo'] = 0;
$orders['yahoo-ppc'] = 0;
$orders['msn'] = 0;
$orders['msn-ppc'] = 0;

// get oders
$data = new DataQuery("SELECT count(Order_ID) as Count, Referrer from orders where Created_On >= ADDDATE(now(), interval {$duration}) AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') and (Referrer like '%google%' or Referrer like '%yahoo%' or Referrer like '%msn%') group by Referrer");
while($data->Row){
	if(stristr($data->Row['Referrer'], 'google')){
		if(stristr($data->Row['Referrer'], 'ppc')){
			$orders['google-ppc'] += $data->Row['Count'];
		} else {
			$orders['google'] += $data->Row['Count'];
		}
	} elseif (stristr($data->Row['Referrer'], 'yahoo')){
		if(stristr($data->Row['Referrer'], 'ppc')){
			$orders['yahoo-ppc'] += $data->Row['Count'];
		} else {
			$orders['yahoo'] += $data->Row['Count'];
		}
	} elseif (stristr($data->Row['Referrer'], 'msn')){
		if(stristr($data->Row['Referrer'], 'ppc')){
			$orders['msn-ppc'] += $data->Row['Count'];
		} else {
			$orders['msn'] += $data->Row['Count'];
		}
	}
	$data->Next();
}
$data->Disconnect();

// get sessions
$data = new DataQuery("SELECT count(Session_ID) as Count, Referrer from customer_session where Created_On >= ADDDATE(now(), interval {$duration}) and (Referrer like '%google%' or Referrer like '%yahoo%' or Referrer like '%msn%') group by Referrer");
while($data->Row){
	if(stristr($data->Row['Referrer'], 'google')){
		if(stristr($data->Row['Referrer'], 'ppc')){
			$sessions['google-ppc'] += $data->Row['Count'];
		} else {
			$sessions['google'] += $data->Row['Count'];
		}
	} elseif (stristr($data->Row['Referrer'], 'yahoo')){
		if(stristr($data->Row['Referrer'], 'ppc')){
			$sessions['yahoo-ppc'] += $data->Row['Count'];
		} else {
			$sessions['yahoo'] += $data->Row['Count'];
		}
	} elseif (stristr($data->Row['Referrer'], 'msn')){
		if(stristr($data->Row['Referrer'], 'ppc')){
			$sessions['msn-ppc'] += $data->Row['Count'];
		} else {
			$sessions['msn'] += $data->Row['Count'];
		}
	}
	$data->Next();
}
$data->Disconnect();

$data = new DataQuery("SELECT count(Session_ID) as Count from customer_session where Created_On >= ADDDATE(now(), interval {$duration})");
$sessions['total'] = $data->Row['Count'];
$data->Disconnect();

$data = new DataQuery("SELECT count(Order_ID) as Count from orders where Created_On >= ADDDATE(now(), interval {$duration})");
$orders['total'] = $data->Row['Count'];
$data->Disconnect();

$page = new Page('Referrer Overview : ', '');
$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');

$block = ' background-color:#eeeeee; border:1px solid #aaaaaa;';
?>
<table width="100%" border="0" cellspacing="0" cellpadding="4">
    <tr>
        <td rowspan="8" valign="top">
		<p> <a href="report_overview.php?duration=-30 day" style="display:block; padding:5px; <?php if($duration == '-30 day') echo $block; ?>">30 Days</a></p>
        <p> <a href="report_overview.php?duration=-6 month" style="display:block; padding:5px; <?php if($duration == '-6 month') echo $block; ?>">6 Months</a></p>
        <p><a href="report_overview.php?duration=-1 year" style="display:block; padding:5px; <?php if($duration == '-1 year') echo $block; ?>">1 Year </a></p>
        </td>
        <td rowspan="8">&nbsp;</td>
        <td><strong>Description</strong></td>
        <td align="right"><strong>Sessions</strong></td>
        <td align="right"><strong>Orders</strong></td>
        <td align="right"><strong>Convertion Rate (%) </strong></td>
    </tr>
    <tr style="background-color:#eeeeee;">
        <td>Google</td>
        <td align="right"><?php echo $sessions['google']; ?></td>
        <td align="right"><?php echo $orders['google']; ?></td>
        <td align="right"><?php echo ($sessions['google']>0)?round(($orders['google']/$sessions['google'])*100, 2):0; ?>%</td>
    </tr>
    <tr>
        <td>Google PPC </td>
        <td align="right"><?php echo $sessions['google-ppc']; ?></td>
        <td align="right"><?php echo $orders['google-ppc']; ?></td>
        <td align="right"><?php echo ($sessions['google-ppc']>0)?round(($orders['google-ppc']/$sessions['google-ppc'])*100, 2):0; ?>%</td>
    </tr>
    <tr style="background-color:#eeeeee;">
        <td>Yahoo</td>
        <td align="right"><?php echo $sessions['yahoo']; ?></td>
        <td align="right"><?php echo $orders['yahoo']; ?></td>
        <td align="right"><?php echo ($sessions['yahoo']>0)?round(($orders['yahoo']/$sessions['yahoo'])*100, 2):0; ?>%</td>
    </tr>
    <tr>
        <td>Yahoo PPC </td>
        <td align="right"><?php echo $sessions['yahoo-ppc']; ?></td>
        <td align="right"><?php echo $orders['yahoo-ppc']; ?></td>
        <td align="right"><?php echo ($sessions['yahoo-ppc']>0)?round(($orders['yahoo-ppc']/$sessions['yahoo-ppc'])*100, 2):0; ?>%</td>
    </tr>
    <tr style="background-color:#eeeeee;">
        <td>MSN</td>
        <td align="right"><?php echo $sessions['msn']; ?></td>
        <td align="right"><?php echo $orders['msn']; ?></td>
        <td align="right"><?php echo ($sessions['msn']>0)?round(($orders['msn']/$sessions['msn'])*100, 2):0; ?>%</td>
    </tr>
    <tr>
        <td>MSN PPPC</td>
        <td align="right"><?php echo $sessions['msn-ppc']; ?></td>
        <td align="right"><?php echo $orders['msn-ppc']; ?></td>
        <td align="right"><?php echo ($sessions['msn-ppc']>0)?round(($orders['msn-ppc']/$sessions['msn-ppc'])*100, 2):0; ?>%</td>
    </tr>
    <tr style="background-color:#eeeeee;">
        <td>Total</td>
        <td align="right"><?php echo $sessions['total']; ?></td>
        <td align="right"><?php echo $orders['total']; ?></td>
        <td align="right"><?php echo ($sessions['total']>0)?round(($orders['total']/$sessions['total'])*100, 2):0; ?>%</td>
    </tr>
</table>
<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
?>