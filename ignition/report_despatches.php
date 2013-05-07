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

	$page = new Page('Despatch Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Report on Despatches.");
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

	$totalConsignments = 0;
	$totalBoxes = 0;
	$totalWeight = 0;

	$page = new Page('Despatch Report : ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->Display('header');
	?>

	<br />
	<h3>Standard Orders</h3>
	<p>Listing despatch statistics on all orders other than R types.</p>

	<?php
	$despatch1 = new DataQuery(sprintf("select count(d.Despatch_ID) as numDespatches, c.Courier_Name, sum(d.Boxes) as Boxes, sum(d.Weight) as Weight from despatch as d INNER JOIN orders AS o ON o.Order_ID=d.Order_ID left join courier as c on d.Courier_ID=c.Courier_ID where d.Despatched_On between '%s' and '%s' group by d.Courier_ID order by numDespatches desc", $start, $end));
	?>

	<br />
	<table width="100%" border="0">
	  <tr>
	    <td style="border-bottom:1px solid #aaaaaa;"><strong>Courier Results</strong></td>
	    <td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Consignments </strong></td>
	    <td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Boxes</strong></td>
	    <td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Weight</strong></td>
	    </tr>
	  <?php
	  	while($despatch1->Row){
			$totalConsignments += $despatch1->Row['numDespatches'];
			$totalBoxes += $despatch1->Row['Boxes'];
			$totalWeight += $despatch1->Row['Weight'];
	  ?>
			  <tr>
				<td><?php echo $despatch1->Row['Courier_Name']; ?></td>
				<td align="right"><?php echo $despatch1->Row['numDespatches']; ?></td>
				<td align="right"><?php echo $despatch1->Row['Boxes']; ?></td>
				<td align="right"><?php echo number_format($despatch1->Row['Weight'], 2, '.', ','); ?>Kg</td>
			  </tr>
	  <?php
	  		$despatch1->Next();
		}
		$despatch1->Disconnect();
	  ?>
	  <tr>
				<td><strong>Totals</strong></td>
				<td align="right"><strong><?php echo $totalConsignments; ?></strong></td>
				<td align="right"><strong><?php echo $totalBoxes; ?></strong></td>
				<td align="right"><strong><?php echo number_format($totalWeight, 2, '.', ','); ?>Kg</strong></td>
		  </tr>
	</table>
	<p>&nbsp;</p>
	<table width="100%" border="0">
	  <tr>
	    <td style="border-bottom:1px solid #aaaaaa;"><strong>Geographical Results</strong></td>
	    <td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Consignments </strong></td>
	    <td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Boxes</strong></td>
	    <td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Weight</strong></td>
	  </tr>
	  <?php
	  	$byCountry = new DataQuery(sprintf("select count(d.Despatch_ID) as numDespatches, d.Despatch_Country, sum(d.Boxes) as Boxes, sum(d.Weight) as Weight from despatch AS d INNER JOIN orders AS o ON o.Order_ID=d.Order_ID WHERE d.Despatched_On between '%s' and '%s' group by d.Despatch_Country order by numDespatches desc", $start, $end));
	  	while($byCountry->Row){
	  ?>
			  <tr>
				<td><strong><?php echo $byCountry->Row['Despatch_Country']; ?></strong></td>
				<td align="right"><strong><?php echo $byCountry->Row['numDespatches']; ?></strong></td>
				<td align="right"><strong><?php echo $byCountry->Row['Boxes']; ?></strong></td>
				<td align="right"><strong><?php echo number_format($byCountry->Row['Weight'], 2, '.', ','); ?>Kg</strong></td>
			  </tr>
			  <?php
			  	$byRegion = new DataQuery(sprintf("select count(d.Despatch_ID) as numDespatches, d.Despatch_Region, sum(d.Boxes) as Boxes, sum(d.Weight) as Weight from despatch AS d INNER JOIN orders AS o ON o.Order_ID=d.Order_ID WHERE d.Despatch_Country='%s' and d.Despatched_On between '%s' and '%s' group by d.Despatch_Region", $byCountry->Row['Despatch_Country'], $start, $end));
				while($byRegion->Row){
			  ?>
			  	<tr>
				<td><?php echo $byRegion->Row['Despatch_Region']; ?></td>
				<td align="right"><?php echo $byRegion->Row['numDespatches']; ?></td>
				<td align="right"><?php echo $byRegion->Row['Boxes']; ?></td>
				<td align="right"><?php echo number_format($byRegion->Row['Weight'], 2, '.', ','); ?>Kg</td>
			  </tr>
			  <?php
					$byRegion->Next();
				}
				$byRegion->Disconnect();
			  ?>
			  <tr><td colspan="4">&nbsp;</td></tr>
	  <?php
	  		$byCountry->Next();
		}
		$byCountry->Disconnect();
	  $despatch1 = new DataQuery(sprintf("SELECT count(DISTINCT d.Despatch_ID) as numDespatches, w.Warehouse_Name, sum(d.Boxes) as Boxes, sum(d.Weight) as Weight FROM despatch d
	  										INNER JOIN orders AS o ON o.Order_ID=d.Order_ID
											INNER JOIN order_line ol ON ol.Despatch_ID = d.Despatch_ID
											INNER JOIN warehouse w ON ol.Despatch_From_ID = w.Warehouse_ID
											where d.Despatched_On between '%s' and '%s' GROUP BY Warehouse_ID", $start, $end));

	?>
	</table>
		<table width="100%" border="0">
	  <tr>
	    <td style="border-bottom:1px solid #aaaaaa;"><strong>Warhouse Results</strong></td>
	    <td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Boxes </strong></td>
	    <td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Consignments</strong></td>
	    <td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Weight</strong></td>
	    </tr>
	  <?php
	  $totalConsignments = 0;
			$totalBoxes = 0;
			$totalWeight = 0;
	  	while($despatch1->Row){
			$totalConsignments += $despatch1->Row['numDespatches'];
			$totalBoxes += $despatch1->Row['Boxes'];
			$totalWeight += $despatch1->Row['Weight'];
	  ?>
			  <tr>
				<td><?php echo $despatch1->Row['Warehouse_Name']; ?></td>
				<td align="right"><?php echo $despatch1->Row['numDespatches']; ?></td>
				<td align="right"><?php echo $despatch1->Row['Boxes']; ?></td>
				<td align="right"><?php echo number_format($despatch1->Row['Weight'], 2, '.', ','); ?>Kg</td>
			  </tr>
	  <?php
	  		$despatch1->Next();
		}
		$despatch1->Disconnect();
	  ?>
	  <tr>
				<td><strong>Totals</strong></td>
				<td align="right"><strong><?php echo $totalConsignments; ?></strong></td>
				<td align="right"><strong><?php echo $totalBoxes; ?></strong></td>
				<td align="right"><strong><?php echo number_format($totalWeight, 2, '.', ','); ?>Kg</strong></td>
		  </tr>
	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>