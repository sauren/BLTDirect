<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF'], 'get');
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

	$page = new Page('Ansaback/Building Design Report', 'Please choose a start and end date for your report.');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Report on Ansaback/Building Design.");
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

	$orders = array();
	$orders['AnsaBack'] = array('Enquiries' => array(), 'Orders' => array());
	$orders['BuildingDesign'] = array('Enquiries' => array(), 'Orders' => array());

	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_enquiry_order SELECT MIN(o.Order_ID) AS Order_ID FROM enquiry AS e INNER JOIN orders AS o ON o.Customer_ID=e.Customer_ID AND o.Created_On>e.Created_On WHERE e.Created_On>='%s' AND e.Created_On<='%s' AND e.Created_By=27 AND o.Created_By<>27 GROUP BY e.Customer_ID", $start, $end));
	new DataQuery(sprintf("ALTER TABLE temp_enquiry_order ADD INDEX Order_ID (Order_ID)"));

	$data = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS Count, SUM(o.SubTotal) AS SubTotal, SUM(o.TotalDiscount) AS TotalDiscount, SUM(o.TotalShipping) AS TotalShipping, SUM(o.TotalTax) AS TotalTax, SUM(o.Total) AS Total FROM orders AS o INNER JOIN temp_enquiry_order AS teo ON o.Order_ID=teo.Order_ID"));
	$orders['AnsaBack']['Enquiries'] = $data->Row;
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS Count, SUM(o.SubTotal) AS SubTotal, SUM(o.TotalDiscount) AS TotalDiscount, SUM(o.TotalShipping) AS TotalShipping, SUM(o.TotalTax) AS TotalTax, SUM(o.Total) AS Total FROM orders AS o WHERE o.Created_By=27 AND o.Created_On>='%s' AND o.Created_On<='%s'", $start, $end));
	$orders['AnsaBack']['Orders'] = $data->Row;
	$data->Disconnect();

	new DataQuery(sprintf("DROP TABLE temp_enquiry_order"));

	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_enquiry_order SELECT MIN(o.Order_ID) AS Order_ID FROM enquiry AS e INNER JOIN orders AS o ON o.Customer_ID=e.Customer_ID AND o.Created_On>e.Created_On WHERE e.Created_On>='%s' AND e.Created_On<='%s' AND e.Created_By=30 AND o.Created_By<>30 GROUP BY e.Customer_ID", $start, $end));
	new DataQuery(sprintf("ALTER TABLE temp_enquiry_order ADD INDEX Order_ID (Order_ID)"));

	$data = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS Count, SUM(o.SubTotal) AS SubTotal, SUM(o.TotalDiscount) AS TotalDiscount, SUM(o.TotalShipping) AS TotalShipping, SUM(o.TotalTax) AS TotalTax, SUM(o.Total) AS Total FROM orders AS o INNER JOIN temp_enquiry_order AS teo ON o.Order_ID=teo.Order_ID"));
	$orders['BuildingDesign']['Enquiries'] = $data->Row;
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS Count, SUM(o.SubTotal) AS SubTotal, SUM(o.TotalDiscount) AS TotalDiscount, SUM(o.TotalShipping) AS TotalShipping, SUM(o.TotalTax) AS TotalTax, SUM(o.Total) AS Total FROM orders AS o WHERE o.Created_By=30 AND o.Created_On>='%s' AND o.Created_On<='%s'", $start, $end));
	$orders['BuildingDesign']['Orders'] = $data->Row;
	$data->Disconnect();

	new DataQuery(sprintf("DROP TABLE temp_enquiry_order"));

	$page = new Page('Ansaback/Building Design Report: ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->Display('header');
	?>

	<br />
	<h3>Orders From Enquiries</h3>
	<p>Orders placed after an Ansaback/Building Design enquiry between the given period not placed by Ansaback/Building Design.</p>

	<table width="100%" border="0" >
		<tr>
			<td align="left" style="border-bottom:1px solid #aaaaaa"><strong>Source</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Number</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Sub Total</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Discounts</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Net</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Shipping</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Tax</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Gross</strong></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td align="left">AnsaBack</td>
			<td align="right"><?php echo $orders['AnsaBack']['Enquiries']['Count']; ?></td>
			<td align="right">&pound;<?php echo number_format($orders['AnsaBack']['Enquiries']['SubTotal'], 2, '.', ','); ?></td>
			<td align="right">-&pound;<?php echo number_format($orders['AnsaBack']['Enquiries']['TotalDiscount'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format(($orders['AnsaBack']['Enquiries']['SubTotal']-$orders['AnsaBack']['Enquiries']['TotalDiscount']), 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($orders['AnsaBack']['Enquiries']['TotalShipping'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($orders['AnsaBack']['Enquiries']['TotalTax'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($orders['AnsaBack']['Enquiries']['Total'], 2, '.', ','); ?></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td align="left">Building Design</td>
			<td align="right"><?php echo $orders['BuildingDesign']['Enquiries']['Count']; ?></td>
			<td align="right">&pound;<?php echo number_format($orders['BuildingDesign']['Enquiries']['SubTotal'], 2, '.', ','); ?></td>
			<td align="right">-&pound;<?php echo number_format($orders['BuildingDesign']['Enquiries']['TotalDiscount'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format(($orders['BuildingDesign']['Enquiries']['SubTotal']-$orders['BuildingDesign']['Enquiries']['TotalDiscount']), 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($orders['BuildingDesign']['Enquiries']['TotalShipping'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($orders['BuildingDesign']['Enquiries']['TotalTax'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($orders['BuildingDesign']['Enquiries']['Total'], 2, '.', ','); ?></td>
		</tr>
	</table>
	<br />

	<br />
	<h3>Direct Orders</h3>
	<p>Orders placed directly by Ansaback/Building Design between the given period.</p>

	<table width="100%" border="0" >
		<tr>
			<td align="left" style="border-bottom:1px solid #aaaaaa"><strong>Source</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Number</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Sub Total</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Discounts</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Net</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Shipping</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Tax</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Gross</strong></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td align="left">AnsaBack</td>
			<td align="right"><?php echo $orders['AnsaBack']['Enquiries']['Count']; ?></td>
			<td align="right">&pound;<?php echo number_format($orders['AnsaBack']['Orders']['SubTotal'], 2, '.', ','); ?></td>
			<td align="right">-&pound;<?php echo number_format($orders['AnsaBack']['Orders']['TotalDiscount'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format(($orders['AnsaBack']['Orders']['SubTotal']-$orders['AnsaBack']['Orders']['TotalDiscount']), 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($orders['AnsaBack']['Orders']['TotalShipping'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($orders['AnsaBack']['Orders']['TotalTax'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($orders['AnsaBack']['Orders']['Total'], 2, '.', ','); ?></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td align="left">Building Design</td>
			<td align="right"><?php echo $orders['BuildingDesign']['Enquiries']['Count']; ?></td>
			<td align="right">&pound;<?php echo number_format($orders['BuildingDesign']['Orders']['SubTotal'], 2, '.', ','); ?></td>
			<td align="right">-&pound;<?php echo number_format($orders['BuildingDesign']['Orders']['TotalDiscount'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format(($orders['BuildingDesign']['Orders']['SubTotal']-$orders['BuildingDesign']['Orders']['TotalDiscount']), 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($orders['BuildingDesign']['Orders']['TotalShipping'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($orders['BuildingDesign']['Orders']['TotalTax'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($orders['BuildingDesign']['Orders']['Total'], 2, '.', ','); ?></td>
		</tr>
	</table>
	<br />

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>