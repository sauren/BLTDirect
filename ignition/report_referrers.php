<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Referrer Report', 'Please choose a start and end date for your report');
	$year = cDatetime(getDatetime(), 'y');
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

			report($start, $end, $form->GetValue('parent'));
			exit;
		} else {
			
			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))));
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
	$window = new StandardWindow("Report on Referrers.");
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Referrer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Country.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Region.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

	class ReferrerInfo{
		var $Number;
		var $Total;
		var $SearchString;
		var $Sessions;

		function ReferrerInfo(){
			$this->Number = 0;
			$this->Total = 0;
			$this->Sessions = 0;
			$this->SearchString = array();
		}
	}

	$orderTypes = array();
	$orderTypes['W'] = "Website (bltdirect.com)";
	$orderTypes['U'] = "Website (bltdirect.co.uk)";
	$orderTypes['L'] = "Website (lightbulbsuk.co.uk)";
	$orderTypes['M'] = "Mobile";
	$orderTypes['T'] = "Telesales";
	$orderTypes['F'] = "Fax";
	$orderTypes['E'] = "Email";

	$referrersArray = array();

	$page = new Page('Referrer Report : ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->Display('header');

	$referrers = new DataQuery(sprintf("select count(Order_ID) as Count, Referrer, sum(SubTotal) as Total from orders where Order_Prefix='W' and Created_On between '%s' and '%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') group by Referrer order by Count desc", mysql_real_escape_string($start), mysql_real_escape_string($end)));

	while($referrers->Row){
		if(empty($referrers->Row['Referrer'])) {
			$key = 'Unknown';
			$string = '';
		} else {
			// get key
			// get search string
			$referrer = new Referrer($referrers->Row['Referrer']);
			$key = $referrer->Domain;
			$string = $referrer->SearchString;
		}

		$string = (empty($string))?'None':$string;

		if(!array_key_exists($key, $referrersArray)){
			$referrersArray[$key] = new ReferrerInfo;
		}
		$referrersArray[$key]->Number += $referrers->Row['Count'];
		$referrersArray[$key]->Total += $referrers->Row['Total'];

		if(!array_key_exists($string, $referrersArray[$key]->SearchString)){
			$referrersArray[$key]->SearchString[$string] = new ReferrerInfo;
		}
		$referrersArray[$key]->SearchString[$string]->Number += $referrers->Row['Count'];
		$referrersArray[$key]->SearchString[$string]->Total += $referrers->Row['Total'];

		$referrers->Next();
	}
	$referrers->Disconnect();

	// Compile Session information into referrers
	$sessions = new DataQuery(sprintf("select count(Session_ID) as Count, Referrer from customer_session where Created_On between '%s' and '%s' group by Referrer order by Count desc", mysql_real_escape_string($start), mysql_real_escape_string($end)));

	while($sessions->Row){
		if(empty($sessions->Row['Referrer'])) {
			$key = 'Unknown';
			$string = '';
		} else {
			// get key
			// get search string
			$session = new Referrer($sessions->Row['Referrer']);
			$key = $session->Domain;
			$string = $session->SearchString;
		}

		$string = (empty($string))?'None':$string;

		if(array_key_exists($key, $referrersArray)){

			$referrersArray[$key]->Sessions += $sessions->Row['Count'];

			if(array_key_exists($string, $referrersArray[$key]->SearchString)){
				$referrersArray[$key]->SearchString[$string]->Sessions += $sessions->Row['Count'];
			}
		}
		$sessions->Next();
	}
	$sessions->Disconnect();
	?>
	
	<br />
	<h3>Referrer Stats</h3>
	<p>Where are your sales coming from?
	The referrer statistics below are gathered by Ignition automatically on website orders only. These statistics are not 100% reliable, but do offer a good indication of where your sales are coming from.</p>

	<table width="100%" border="0" >
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Referrer</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Orders</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Sessions</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Convertion Rate</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Turnover</strong></td>
	  </tr>
	  <?php
	  foreach($referrersArray as $key=>$value){
	  	if (empty($value->Sessions)) $value->Sessions = 1;
	  ?>
	  <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td><strong><?php echo $key; ?></strong></td>
		<td align="right"><strong><?php echo $value->Number; ?></strong></td>
		<td align="right"><strong><?php echo $value->Sessions; ?></strong></td>
		<td align="right"><strong><?php echo round(($value->Number/$value->Sessions)*100, 2) . '%'; ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($value->Total, 2, '.', ','); ?></strong></td>
	  </tr>
	  <?php
	  foreach($value->SearchString as $stringKey => $stringValue){
	  	if (empty($stringValue->Sessions)) $stringValue->Sessions = 1;
	  ?>
			  <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>&nbsp; &nbsp; <a href="stat_terms.php?term=<?php echo urlencode(trim($stringKey)); ?>&start=<?php echo urlencode($start); ?>&end=<?php echo urlencode($end); ?>"><?php echo $stringKey; ?></a></td>
				<td align="right"><?php echo $stringValue->Number; ?></td>
				<td align="right"><?php echo $stringValue->Sessions; ?></td>
				<td align="right"><?php echo round(($stringValue->Number/$stringValue->Sessions)*100, 2) . '%'; ?></td>
				<td align="right">&pound;<?php echo number_format($stringValue->Total, 2, '.', ','); ?></td>
			  </tr>
	  <?php
	  }
	  echo "<tr><td colspan=\"3\">&nbsp;</td></tr>";
	  }
	  ?>

	</table>
	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>