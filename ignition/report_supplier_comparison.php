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
	$form->AddField('target', 'Target Suppliers', 'selectmultiple', '0', 'numeric_unsigned', 1, 11, true, 'size="10"');
	$form->AddGroup('target', 'Y', 'Favourite Suppliers');
	$form->AddGroup('target', 'N', 'Standard Suppliers');
	$form->AddField('reference', 'Reference Supplier', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('reference', '0', '');

	$data = new DataQuery(sprintf("SELECT s.Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Supplier_Name, s.Is_Favourite FROM supplier AS s INNER JOIN contact AS c ON s.Contact_ID=c.Contact_ID INNER JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID ORDER BY Supplier_Name ASC"));
	while($data->Row) {
		$form->AddOption('target', $data->Row['Supplier_ID'], $data->Row['Supplier_Name'], $data->Row['Is_Favourite']);
		$form->AddOption('reference', $data->Row['Supplier_ID'], $data->Row['Supplier_Name']);

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

			report($start, $end, $form->GetValue('target'), $form->GetValue('reference'));
			exit;
		} else {

			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))), $form->GetValue('target'), $form->GetValue('reference'));
				exit;
			}
		}
	}

	$page = new Page('Supplier Comparison Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	$window = new StandardWindow("Report on Supplier Comparisons");
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

	echo $window->AddHeader('Select  your target and reference suppliers for this report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('target'), $form->GetHTML('target'));
	echo $webForm->AddRow($form->GetLabel('reference'), $form->GetHTML('reference'));
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

function report($start, $end, $targetId, $referenceId){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');

	$referenceSupplier = new Supplier($referenceId);
	$referenceSupplier->Contact->Get();

	$page = new Page('Supplier Comparison Report : ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->Display('header');

	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_cost SELECT Product_ID, Cost FROM supplier_product WHERE Supplier_ID=%d", mysql_real_escape_string($referenceId)));
	new DataQuery(sprintf("ALTER TABLE temp_cost ADD INDEX Product_ID (Product_ID)"));

	foreach($targetId as $targetItemId) {
		$targetSupplier = new Supplier($targetItemId);
		$targetSupplier->Contact->Get();
		?>

		<br />
		<h3><?php echo $targetSupplier->Contact->Parent->Organisation->Name; ?> -> <?php echo $referenceSupplier->Contact->Parent->Organisation->Name; ?></h3>
		<p>Comparing despatch product costs against the above suppliers for the given period.</p>

		<?php
		echo '<table width="100%" border="0">';

		$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, SUM(ol.Quantity) AS Quantity, ol.Cost AS Target_Cost, tc.Cost AS Reference_Cost FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' AND w.Type_Reference_ID=%d INNER JOIN temp_cost AS tc ON tc.Product_ID=ol.Product_ID AND tc.Cost>0 AND tc.Cost<ol.Cost INNER JOIN product AS p ON p.Product_ID=ol.Product_ID WHERE o.Created_On>='%s' AND o.Created_On<'%s' GROUP BY p.Product_ID, ol.Cost", mysql_real_escape_string($targetItemId), mysql_real_escape_string($start), mysql_real_escape_string($end)));
		if($data->TotalRows > 0) {
			$totalDifference = 0;
			$totalReferenceCost = 0;

			echo '<tr>';
			echo '<td style="border-bottom:1px solid #aaaaaa;"><strong>Product Name</strong></td>';
			echo '<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>';
			echo '<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Target Cost</strong></td>';
			echo '<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Reference Cost</strong></td>';
			echo '<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Difference</strong></td>';
			echo '<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Quantity</strong></td>';
			echo '<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Total Difference</strong></td>';
			echo '<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Total Reference Cost</strong></td>';
			echo '</tr>';

			while($data->Row) {
				$totalDifference += ($data->Row['Target_Cost'] - $data->Row['Reference_Cost']) * $data->Row['Quantity'];
				$totalReferenceCost += $data->Row['Reference_Cost'] * $data->Row['Quantity'];

				echo '<tr class="dataRow" onMouseOver="setClassName(this, \'dataRowOver\');" onMouseOut="setClassName(this, \'dataRow\');">';
				echo sprintf('<td>%s</td>', strip_tags($data->Row['Product_Title']));
				echo sprintf('<td><a href="product_profile.php?pid=%d">%d</a></td>', $data->Row['Product_ID'], $data->Row['Product_ID']);
				echo sprintf('<td align="right">&pound;%s</td>', number_format($data->Row['Target_Cost'], 2, '.', ','));
				echo sprintf('<td align="right">&pound;%s</td>', number_format($data->Row['Reference_Cost'], 2, '.', ','));
				echo sprintf('<td align="right">&pound;%s</td>', number_format($data->Row['Target_Cost'] - $data->Row['Reference_Cost'], 2, '.', ','));
				echo sprintf('<td align="right">%s</td>', $data->Row['Quantity']);
				echo sprintf('<td align="right">&pound;%s</td>', number_format(($data->Row['Target_Cost'] - $data->Row['Reference_Cost']) * $data->Row['Quantity'], 2, '.', ','));
				echo sprintf('<td align="right">&pound;%s</td>', number_format($data->Row['Reference_Cost'] * $data->Row['Quantity'], 2, '.', ','));
				echo '</tr>';

				$data->Next();
			}

			echo '<tr class="dataRow" onMouseOver="setClassName(this, \'dataRowOver\');" onMouseOut="setClassName(this, \'dataRow\');">';
			echo '<td>&nbsp;</td>';
			echo '<td>&nbsp;</td>';
			echo '<td>&nbsp;</td>';
			echo '<td>&nbsp;</td>';
			echo '<td>&nbsp;</td>';
			echo '<td>&nbsp;</td>';
			echo sprintf('<td align="right"><strong>&pound;%s</strong></td>', number_format($totalDifference, 2, '.', ','));
			echo sprintf('<td align="right"><strong>&pound;%s</strong></td>', number_format($totalReferenceCost, 2, '.', ','));
			echo '</tr>';
		} else {
			echo '<tr><td align="center" colspan="2"></td></tr>';
		}
		$data->Disconnect();

		echo '</table>';
	}

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}
?>