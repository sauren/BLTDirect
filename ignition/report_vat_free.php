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
	$form->AddField('geozone', 'Geozone', 'select', 'all', 'alpha_numeric', 1, 11);
	$form->AddOption('geozone', 'all', '-- All --');
	$form->AddOption('geozone', 'ec', 'EC Sales');
	$form->AddOption('geozone', 'uk', 'United Kingdom');
	$form->AddOption('geozone', 'row', 'Rest Of World');

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

			report($start, $end, $form->GetValue('geozone'));
			exit;
		} else {

			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))), $form->GetValue('geozone'));
				exit;
			}
		}
	}

	$page = new Page('VAT Free Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');
	
	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on VAT Free Orders.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select a geozone for this report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('geozone'), $form->GetHTML('geozone'));
	echo $webForm->Close();
	echo $window->CloseContent();

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

function report($start, $end, $geozone) {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Referrer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Country.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Region.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

	$page = new Page('VAT Free Report: ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	$geozoneStr = '';

	if($geozone == 'ec') {
		$geozoneStr .= "AND (";

		$data = new DataQuery(sprintf("SELECT Country_ID FROM geozone_assoc WHERE Geozone_ID=26 AND Country_ID<>222"));
		while($data->Row) {
			if(!isset($exclusions[$data->Row['Country_ID']])) {
				$geozoneStr .= sprintf("(o.Billing_Country_ID=%d OR o.Shipping_Country_ID=%d) OR ", $data->Row['Country_ID'], $data->Row['Country_ID']);
			}

			$data->Next();
		}
		$data->Disconnect();

		$geozoneStr = substr($geozoneStr, 0, -4) . ")";

	} elseif($geozone == 'uk') {
		$geozoneStr .= "AND (";

		$data = new DataQuery(sprintf("SELECT Country_ID FROM geozone_assoc WHERE Geozone_ID=22"));
		while($data->Row) {
			$geozoneStr .= sprintf("(o.Billing_Country_ID=%d OR o.Shipping_Country_ID=%d) OR ", $data->Row['Country_ID'], $data->Row['Country_ID']);

			$data->Next();
		}
		$data->Disconnect();

		$geozoneStr = substr($geozoneStr, 0, -4) . ")";

	} elseif($geozone == 'row') {
		$geozoneStr .= "AND (";

		$data = new DataQuery(sprintf("SELECT Country_ID FROM geozone_assoc WHERE Geozone_ID=22 OR Geozone_ID=26"));
		while($data->Row) {
			$geozoneStr .= sprintf("o.Billing_Country_ID<>%d AND o.Shipping_Country_ID<>%d AND ", $data->Row['Country_ID'], $data->Row['Country_ID']);

			$data->Next();
		}
		$data->Disconnect();

		$geozoneStr = substr($geozoneStr, 0, -5) . ")";
	}
	?>

	<br />
	<h3>VAT Free Orders</h3>
	<p>Listing all orders with 0% tax rate or tax exemption codes.</p>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Order Date</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Customer</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Organisation</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Order ID</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="center"><strong>Export Proof</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Tax Exemption Code</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Net Total</strong></td>
		</tr>

		<?php
		$totalNet = 0;

		$data = new DataQuery(sprintf("SELECT o.*, MIN(i2.Created_On) AS Invoiced_On, cu.Contact_ID, CONCAT_WS(' ', o.Billing_Title, o.Billing_First_Name, o.Billing_Initial, o.Billing_Last_Name) AS Customer, od.count AS Proof_Count FROM orders AS o INNER JOIN invoice AS i ON i.Order_ID=o.Order_ID INNER JOIN invoice AS i2 ON i2.Order_ID=o.Order_ID INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID LEFT JOIN geozone_assoc AS ga ON o.Shipping_Country_ID=ga.Country_ID AND (ga.Region_ID=0 OR ga.Region_ID=o.Shipping_Region_ID) LEFT JOIN tax AS t ON ga.Geozone_ID=t.Geozone_ID AND t.Tax_Rate=0 LEFT JOIN (SELECT orderId, COUNT(*) AS count FROM order_document WHERE type LIKE 'Export Proof' GROUP BY orderId) AS od ON od.orderId=o.Order_ID WHERE o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' AND o.Status LIKE 'Despatched' AND ((o.TaxExemptCode<>'' AND o.TotalTax=0) OR t.Tax_ID IS NOT NULL) AND i.Created_On BETWEEN '%s' AND '%s' %s GROUP BY o.Order_ID HAVING Invoiced_On BETWEEN '%s' AND '%s' ORDER BY o.Created_On ASC", mysql_real_escape_string($start), mysql_real_escape_string($end), mysql_real_escape_string($geozoneStr), mysql_real_escape_string($start), mysql_real_escape_string($end)));
		while($data->Row) {
			$totalNet += $data->Row['SubTotal'] + $data->Row['TotalShipping'];
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><?php print $data->Row['Created_On']; ?></td>
				<td><a href="contact_profile.php?cid=<?php echo $data->Row['Contact_ID']; ?>" target="_blank"><?php echo $data->Row['Customer']; ?></a></td>
				<td><?php echo $data->Row['Billing_Organisation_Name']; ?></td>
				<td><a href="order_details.php?orderid=<?php echo $data->Row['Order_ID']; ?>" target="_blank"><?php echo $data->Row['Order_ID']; ?></a></td>
				<td align="center"><?php echo number_format($data->Row['Proof_Count'], 0); ?></td>
				<td><?php echo $data->Row['TaxExemptCode']; ?></td>
				<td align="right">&pound;<?php echo number_format($data->Row['SubTotal'] - $data->Row['TotalDiscount'] + $data->Row['TotalShipping'], 2, '.', ','); ?></td>
			</tr>

			<?php
			$data->Next();
		}
		$data->Disconnect();
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td align="right"><strong>&pound;<?php echo number_format($totalNet, 2, '.', ','); ?></strong></td>
		</tr>
	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}