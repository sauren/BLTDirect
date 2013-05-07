<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

if($action == 'open') {
	$session->Secure(2);
	open();
	exit;
} elseif($action == 'report') {
	$session->Secure(2);
	view();
	exit;
} else {
	$session->Secure(2);
	start();
	exit;
}

function start() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
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

			redirect(sprintf('Location: %s?action=report&start=%s&end=%s', $_SERVER['PHP_SELF'], $start, $end));
		} else {
			if($form->Validate()) {
				redirect(sprintf('Location: %s?action=report&start=%s&end=%s', $_SERVER['PHP_SELF'], sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2))))))));
			}
		}
	}

	$page = new Page('Returns Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Common Returns.");
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

function view() {
	$page = new Page('Common Returns', 'Listing your commonly returns products.');
	$page->Display('header');

	$start = param('start');
	if(empty($start)){
		$start = date('Y-m-d H:i:s', 0);
	}
	$end = param('end');
	if(empty($end)){
		$end = date('Y-m-d H:i:s');
	}

	$data = new DataQuery(sprintf("select SUM(ol.Quantity) as Total_Sold, SUM(r.Quantity) as Total_Returned
from (
	SELECT *, IF(r.Reason_ID = 2,1,0) AS ReturnsBroken, IF(r.Reason_ID = 3, 1, 0) AS ReturnsFaulty,IF(r.Reason_ID = 4, 1, 0) AS ReturnsIncorrect
	FROM `return` r
	WHERE r.Reason_ID IN (2,3,4) AND r.Created_On BETWEEN '%s' AND '%s'
) r
inner join order_line ol on ol.Order_Line_ID=r.Order_Line_ID
inner join warehouse w on ol.Despatch_From_ID=w.Warehouse_ID and w.Type = 'B'
where ol.Product_ID > 0", $start, $end));
	$totalSold = 0;
	$totalReturned = 0;
	if($data->TotalRows > 0){
		$totalSold = $data->Row['Total_Sold'];
		$totalReturned = $data->Row['Total_Returned'];
	}
	$data->Disconnect();

	echo 'Total Sold : ' . $totalSold . '<br />';
	echo 'Total Returned : ' . $totalReturned . '<br />';
	if($totalSold > 0){
		echo 'Return Percentage : ' . round(($totalReturned / $totalSold) * 100) . '%<br /><br />';
	}

	$table = new DataTable('products');
	$table->SetExtractVars();
	$table->SetSQL(sprintf("select ol.Product_ID, ol.Product_Title, COUNT(r.Return_ID) as Returns, SUM(r.Quantity) as Quantity, SUM(r.ReturnsBroken) as ReturnsBroken, SUM(r.ReturnsFaulty) as ReturnsFaulty, SUM(r.ReturnsIncorrect) as ReturnsIncorrect
from (
	SELECT *, IF(r.Reason_ID = 2,1,0) AS ReturnsBroken, IF(r.Reason_ID = 3, 1, 0) AS ReturnsFaulty,IF(r.Reason_ID = 4, 1, 0) AS ReturnsIncorrect
	FROM `return` r
	WHERE r.Reason_ID IN (2,3,4)
	AND r.Created_On BETWEEN '%s' AND '%s'
) r
inner join order_line ol on ol.Order_Line_ID=r.Order_Line_ID
inner join warehouse w on ol.Despatch_From_ID=w.Warehouse_ID and w.Type = 'B'
where ol.Product_ID > 0 
group by ol.Product_ID", $start, $end));
	$table->AddField('Product ID#', 'Product_ID', 'left');
	$table->AddField('Product Name', 'Product_Title', 'left');
	$table->AddField('Returns Broken', 'ReturnsBroken', 'right');
	$table->AddField('Returns Faulty', 'ReturnsFaulty', 'right');
	$table->AddField('Returns Incorrect', 'ReturnsIncorrect', 'right');
	$table->AddField('Returns', 'Returns', 'right');
	$table->AddField('Quantity', 'Quantity', 'right');
	$table->AddLink('?action=open&productid=%s', '<img src="images/folderopen.gif" alt="Open" border="0" />', 'Product_ID');
	$table->SetMaxRows(25);
	$table->SetOrderBy('Returns');
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function open() {
	$product = new Product();

	if(!isset($_REQUEST['productid']) || !$product->Get($_REQUEST['productid'])) {
		redirectTo('?action=report');
	}

	$page = new Page(sprintf('<a href="?action=report">Common Returns</a> &gt; %s', $product->Name), 'Viewing return reasons for this product.');
	$page->Display('header');

	$table = new DataTable('reasons');
	$table->SetExtractVars();
	$table->SetSQL(sprintf('SELECT r.Return_ID, r.Note, r.Created_On, rr.Reason_Title FROM `return` AS r INNER JOIN return_reason AS rr ON rr.Reason_ID=r.Reason_ID INNER JOIN order_line AS ol ON ol.Order_Line_ID=r.Order_Line_ID INNER JOIN warehouse AS w ON ol.Despatch_From_ID=w.Warehouse_ID AND w.Type=\'B\' WHERE r.Reason_ID IN (2, 3, 4) AND r.Note<>\'\' AND ol.Product_ID=%d AND r.Created_On>ADDDATE(NOW(), INTERVAL -12 MONTH)', $product->ID));
	$table->AddField('Return ID#', 'Return_ID', 'left');
	$table->AddField('Return Date', 'Created_On', 'left');
	$table->AddField('Reason', 'Reason_Title', 'left');
	$table->AddField('Note', 'Note', 'left');
	$table->SetMaxRows(25);
	$table->SetOrderBy('Created_On');
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}