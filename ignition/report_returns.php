<?php
require_once('lib/common/app_header.php');

if($action == 'debit') {
	$session->Secure(3);
	debit();
	exit();
} elseif($action == 'report') {
	$session->Secure(3);
	report();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function debit() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Debit.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DebitLine.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');

	if(!isset($_SESSION['ReturnsReport'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$debitLines = array();
	$debitTotal = array();
	$debitSuppliers = array();

	if($_SESSION['ReturnsReport']['Supplier'] == 0) {
		$data = new DataQuery(sprintf("SELECT r.Return_ID, rr.Reason_Title, p.Product_Title, p.Product_ID, r.Requested_On, r.Status, r.Quantity, ol.Order_ID, oo.Created_On, o.Org_Name, sp.Cost, s.Supplier_ID
			FROM `return` AS r
			LEFT JOIN return_reason AS rr ON rr.Reason_ID=r.Reason_ID
			INNER JOIN order_line AS ol ON r.Order_Line_ID=ol.Order_Line_ID
			INNER JOIN orders AS oo ON oo.Order_ID=ol.Order_ID
			INNER JOIN product AS p ON ol.Product_ID=p.Product_ID
			LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID
			LEFT JOIN supplier AS s ON s.Supplier_ID=sp.Supplier_ID
			LEFT JOIN contact AS c1 ON c1.Contact_ID=s.Contact_ID
			LEFT JOIN contact AS co ON co.Contact_ID=c1.Parent_Contact_ID
			LEFT JOIN organisation AS o ON o.Org_ID=co.Org_ID
			WHERE r.Requested_On BETWEEN '%s' AND '%s'
			AND oo.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')
			AND sp.Preferred_Supplier='Y' ORDER BY s.Supplier_ID ASC", mysql_real_escape_string($_SESSION['ReturnsReport']['Start']), mysql_real_escape_string($_SESSION['ReturnsReport']['End'])));
	} else {
		$data = new DataQuery(sprintf("SELECT r.Return_ID, rr.Reason_Title, p.Product_Title, p.Product_ID, r.Requested_On, r.Status, r.Quantity, ol.Order_ID, oo.Created_On, o.Org_Name, sp.Cost, s.Supplier_ID
			FROM `return` AS r
			LEFT JOIN return_reason AS rr ON rr.Reason_ID=r.Reason_ID
			INNER JOIN order_line AS ol ON r.Order_Line_ID=ol.Order_Line_ID
			INNER JOIN orders AS oo ON oo.Order_ID=ol.Order_ID
			INNER JOIN product AS p ON ol.Product_ID=p.Product_ID
			LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID
			LEFT JOIN supplier AS s ON s.Supplier_ID=sp.Supplier_ID
			LEFT JOIN contact AS c1 ON c1.Contact_ID=s.Contact_ID
			LEFT JOIN contact AS co ON co.Contact_ID=c1.Parent_Contact_ID
			LEFT JOIN organisation AS o ON o.Org_ID=co.Org_ID
			WHERE r.Requested_On BETWEEN '%s' AND '%s'
			AND oo.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')
			AND s.Supplier_ID=%d
			AND sp.Preferred_Supplier='Y' ORDER BY s.Supplier_ID ASC", mysql_real_escape_string($_SESSION['ReturnsReport']['Start']), mysql_real_escape_string($_SESSION['ReturnsReport']['End']), mysql_real_escape_string($_SESSION['ReturnsReport']['Supplier'])));
	}

	while($data->Row) {
		if(isset($_REQUEST['debit_check_'.$data->Row['Return_ID']]) && ($_REQUEST['debit_check_'.$data->Row['Return_ID']] == 'on')) {
			$line = new DebitLine();
			$line->Description = strip_tags($data->Row['Product_Title']);
			$line->Quantity = $_REQUEST['debit_quantity_'.$data->Row['Return_ID']];
			$line->Product->ID = $data->Row['Product_ID'];
			$line->Cost = $data->Row['Cost'];
			$line->Total = $data->Row['Cost']*$_REQUEST['debit_quantity_'.$data->Row['Return_ID']];
			$line->Reason = $data->Row['Reason_Title'];
			$line->Custom = 'Order Ref: '.$data->Row['Order_ID'].'<br />Order Date: '.cDatetime($data->Row['Created_On'], 'shortdate');

			$debitLines[$data->Row['Supplier_ID']][] = $line;
			$debitTotal[$data->Row['Supplier_ID']] += $line->Total;
			$debitSuppliers[$data->Row['Supplier_ID']] = $data->Row['Org_Name'];
		}

		$data->Next();
	}

	$data->Disconnect();

	foreach($debitLines as $k=>$debitLine) {
		$supplier = new Supplier($k);
		$supplier->Contact->Get();

		$debit = new Debit();
		$debit->Supplier->ID = $supplier->ID;
		$debit->Total = $debitTotal[$k];
		$debit->IsPaid = 'N';
		$debit->IsActive = 'Y';
		$debit->Person = $supplier->Contact->Person;

		$debit->Organisation = $debitSuppliers[$k];
		$debit->Add();

		for($i = 0; $i < count($debitLine); $i++) {
			$debitLine[$i]->DebitID = $debit->ID;
			$debitLine[$i]->Add();
		}
	}

	if(count($debitLines) > 1) {
		redirect(sprintf("Location: debit_awaiting_payment.php"));
	} else {
		redirect(sprintf("Location: debit_awaiting_payment.php?action=open&id=%d", $debit->ID));
	}

	require_once('lib/common/app_footer.php');
}

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
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
	$form->AddField('status', 'Status', 'select', 'all', 'alpha_numeric', 0, 32);
	$form->AddOption('status', 'all', '-- All --');
	$form->AddOption('status', 'resolved', 'Resolved');
	$form->AddOption('status', 'unresolved', 'Unresolved');
	$form->AddField('authorisation', 'Authorisation', 'select', '', 'alpha_numeric', 0, 32, false);
	$form->AddOption('authorisation', '', '-- All --');
	$form->AddOption('authorisation', 'R', 'Return');
	$form->AddOption('authorisation', 'D', 'Despatch');
	$form->AddOption('authorisation', 'N', 'None');
    $form->AddField('reason', 'Reason', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('reason', '0', '-- All --');

    $data = new DataQuery(sprintf("SELECT Reason_ID, Reason_Title FROM return_reason ORDER BY Reason_Title ASC"));
	while($data->Row) {
		$form->AddOption('reason', $data->Row['Reason_ID'], $data->Row['Reason_Title']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('supplier', 'Supplier', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('supplier', '0', '-- All --');

	$data = new DataQuery(sprintf("SELECT s.Supplier_ID, o.Org_Name FROM supplier AS s INNER JOIN contact AS c1 ON c1.Contact_ID=s.Contact_ID INNER JOIN contact AS co ON co.Contact_ID=c1.Parent_Contact_ID INNER JOIN organisation AS o ON o.Org_ID=co.Org_ID GROUP BY o.Org_ID ORDER BY o.Org_Name ASC"));
	while($data->Row) {
		$form->AddOption('supplier', $data->Row['Supplier_ID'], $data->Row['Org_Name']);
		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('warehouse', 'Despatched From', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('warehouse', '0', '-- All --');

	$data = new DataQuery(sprintf("SELECT Warehouse_ID, Warehouse_Name FROM warehouse ORDER BY Warehouse_Name ASC"));
	while($data->Row) {
		$form->AddOption('warehouse', $data->Row['Warehouse_ID'], $data->Row['Warehouse_Name']);
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

			redirect(sprintf('Location: %s?action=report&start=%s&end=%s&supplier=%d&status=%s&authorisation=%s&reason=%s&warehous=%d', $_SERVER['PHP_SELF'], $start, $end, $form->GetValue('supplier'), $form->GetValue('status'), $form->GetValue('authorisation'), $form->GetValue('reason'), $form->GetValue('warehouse')));
		} else {
			if($form->Validate()) {
				redirect(sprintf('Location: %s?action=report&start=%s&end=%s&supplier=%d&status=%s&authorisation=%s&reason=%s&warehous=%d', $_SERVER['PHP_SELF'], sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))), $form->GetValue('supplier'), $form->GetValue('status'), $form->GetValue('authorisation'), $form->GetValue('reason'), $form->GetValue('warehouse')));
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

	$window = new StandardWindow("Report on Returns.");
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
	echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHTML('supplier'));
	echo $webForm->AddRow($form->GetLabel('status'), $form->GetHTML('status'));
	echo $webForm->AddRow($form->GetLabel('authorisation'), $form->GetHTML('authorisation'));
	echo $webForm->AddRow($form->GetLabel('reason'), $form->GetHTML('reason'));
	echo $webForm->AddRow($form->GetLabel('warehouse'), $form->GetHTML('warehouse'));
	echo $webForm->AddRow('&nbsp;', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

    $form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Start Date', 'hidden', '0000-00-00 00:00:00', 'date_ddmmyyy', 1, 19);
	$form->AddField('end', 'End Date', 'hidden', '0000-00-00 00:00:00', 'date_ddmmyyy', 1, 19);
	$form->AddField('status', 'Status', 'hidden', 'all', 'alpha_numeric', 0, 32);
	$form->AddField('authorisation', 'Authorisation', 'hidden', '', 'alpha_numeric', 0, 32, false);
    $form->AddField('reason', 'Reason', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('supplier', 'Supplier', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('warehouse', 'Despatched From', 'hidden', '0', 'numeric_unsigned', 1, 11);

	$page = new Page('Returns Report : ' . cDatetime($form->GetValue('start'), 'longdatetime') . ' to ' . cDatetime($form->GetValue('end'), 'longdatetime'), '');
	$page->Display('header');

	$qryStr = $_SERVER['PHP_SELF'] . "?action=report&start=". $form->GetValue('start') ."&end=".$form->GetValue('end')."&authorisation=".$form->GetValue('authorisation') . "&supplier=". $form->GetValue('supplier') ."&warehouse=".$form->GetValue('warehouse')."&reason=".$form->GetValue('reason')."&order=";
	?>

	<br /><h3>Return Details</h3>
	<p>List of return items logically grouped where appropriate.</p>

	<form action="<?php print $_SERVER['PHP_SELF']; ?>" method="post">
		<input type="hidden" value="debit" name="action" />

		<?php
		$order = 'p.Product_Title';

		if(isset($_REQUEST['order'])) {
			$oRequest = $_REQUEST['order'];

			if($oRequest == 'requested') {
				$order = 'r.Requested_On';
			} elseif($oRequest == 'order') {
				$order = 'ol.Order_ID';
			} elseif($oRequest == 'productname') {
				$order = 'p.Product_Title';
			} elseif($oRequest == 'productid') {
				$order = 'p.Product_ID';
			} elseif($oRequest == 'supplier') {
				$order = 'o.Org_Name';
			} elseif($oRequest == 'warehouse') {
				$order = 'w.Warehouse_Name';
			} elseif($oRequest == 'status') {
				$order = 'r.Status';
			} elseif($oRequest == 'reason') {
				$order = 'rr.Reason_Title';
			} elseif($oRequest == 'quantity') {
				$order = 'r.Quantity';
			} elseif($oRequest == 'cost') {
				$order = 'sp.Cost';
			} elseif($oRequest == 'total') {
				$order = 'Total_Cost';
			} elseif($oRequest == 'authorised') {
				$order = 'r.Authorisation';
			}
		}

		$total = 0;

		if($form->GetValue('status') == 'resolved') {
			$statusStr = " AND r.Status='Resolved'";
		} elseif($form->GetValue('status') == 'unresolved') {
			$statusStr = " AND r.Status<>'Resolved'";
		} else {
			$statusStr = '';
		}

		$supplierStr = ($form->GetValue('supplier') > 0) ? sprintf(' AND s.Supplier_ID=%d ', $form->GetValue('supplier')) : '';
		$authorisationStr = (strlen($form->GetValue('authorisation')) > 0) ? sprintf(" AND r.Authorisation='%s'", $form->GetValue('authorisation')) : '';
		$reasonStr = ($form->GetValue('reason') > 0) ? sprintf(" AND r.Reason_ID=%d", $form->GetValue('reason')) : '';
		$warehouseStr = ($form->GetValue('warehouse') > 0) ? sprintf(' AND w.Warehouse_ID=%d ', $form->GetValue('warehouse')) : '';

		$sql = sprintf("SELECT r.Authorisation, r.Return_ID, (sp.Cost * r.Quantity) AS Total_Cost, rr.Reason_Title, p.Product_Title, p.Product_ID, r.Requested_On, r.Status, r.Quantity, ol.Order_ID, o.Org_Name, sp.Cost, w.Warehouse_Name
								FROM `return` AS r
								LEFT JOIN return_reason AS rr ON rr.Reason_ID=r.Reason_ID
								INNER JOIN order_line AS ol ON r.Order_Line_ID=ol.Order_Line_ID
								LEFT JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID
								INNER JOIN product AS p ON ol.Product_ID=p.Product_ID
								LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID
								LEFT JOIN supplier AS s ON s.Supplier_ID=sp.Supplier_ID
								LEFT JOIN contact AS c1 ON c1.Contact_ID=s.Contact_ID
								LEFT JOIN contact AS co ON co.Contact_ID=c1.Parent_Contact_ID
								LEFT JOIN organisation AS o ON o.Org_ID=co.Org_ID
								WHERE r.Requested_On BETWEEN '%s' AND '%s' %s %s %s %s %s
								AND sp.Preferred_Supplier='Y' ORDER BY %s ASC", mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end')), mysql_real_escape_string($authorisationStr), mysql_real_escape_string($supplierStr), mysql_real_escape_string($statusStr), mysql_real_escape_string($reasonStr), mysql_real_escape_string($warehouseStr), mysql_real_escape_string($order));

		$_SESSION['ReturnsReport'] = array();
		$_SESSION['ReturnsReport']['Start'] = $form->GetValue('start');
		$_SESSION['ReturnsReport']['End'] = $form->GetValue('end');
		$_SESSION['ReturnsReport']['Supplier'] = $form->GetValue('supplier');

		$data = new DataQuery($sql);

		if($data->TotalRows > 0) {
		  		?>

		  	<table width="100%" border="0">
				  <tr>
				  	<td style="border-bottom:1px solid #aaaaaa">&nbsp;</td>
				    <td style="border-bottom:1px solid #aaaaaa"><strong><a href="<?php print $qryStr; ?>requested">Requested</a></strong></td>
				    <td style="border-bottom:1px solid #aaaaaa" align="center"><strong><a href="<?php print $qryStr; ?>order">Order</a></strong></td>
					<td style="border-bottom:1px solid #aaaaaa"><strong><a href="<?php print $qryStr; ?>productname">Product Name</strong></a></td>
					<td style="border-bottom:1px solid #aaaaaa" align="center"><strong><a href="<?php print $qryStr; ?>productid">Quickfind</strong></a></td>
					<td style="border-bottom:1px solid #aaaaaa"><strong><a href="<?php print $qryStr; ?>supplier">Supplier</strong></a></td>
					<td style="border-bottom:1px solid #aaaaaa"><strong><a href="<?php print $qryStr; ?>warehouse">Despatched From</strong></a></td>
					<td style="border-bottom:1px solid #aaaaaa" align="center"><strong><a href="<?php print $qryStr; ?>status">Status</strong></a></td>
					<td style="border-bottom:1px solid #aaaaaa" align="center"><strong><a href="<?php print $qryStr; ?>reason">Reason</strong></a></td>
					<td style="border-bottom:1px solid #aaaaaa" align="center"><strong><a href="<?php print $qryStr; ?>authorised">Authorised</strong></a></td>
					<td style="border-bottom:1px solid #aaaaaa" align="right"><strong><a href="<?php print $qryStr; ?>quantity">Quantity</strong></a></td>
					<td style="border-bottom:1px solid #aaaaaa" align="right"><strong><a href="<?php print $qryStr; ?>cost">Cost Price</strong></a></td>
					<td style="border-bottom:1px solid #aaaaaa" align="right"><strong><a href="<?php print $qryStr; ?>total">Total Cost</strong></a></td>
				</tr>

			<?php
			while ($data->Row){
				$total += $data->Row['Total_Cost'];
				$authorised = ($data->Row['Authorisation'] == 'N') ? 'None' : (($data->Row['Authorisation'] == 'R') ? 'Return' : (($data->Row['Authorisation'] == 'D') ? 'Despatch' : 'Unknown'));
				?>

			  <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><input type="checkbox" name="debit_check_<?php print $data->Row['Return_ID']; ?>" checked="checked" /></td>
			  	<td><?php echo cDatetime($data->Row['Requested_On'], 'shortdate'); ?></td>
				<td align="center"><a href="order_details.php?oid=<?php echo $data->Row['Order_ID']; ?>" target="_blank"><?php echo $data->Row['Order_ID']; ?></a></td>
				<td><?php echo strip_tags($data->Row['Product_Title']); ?></td>
				<td align="center"><a href="product_profile.php?pid=<?php echo $data->Row['Product_ID']; ?>" target="_blank"><?php echo $data->Row['Product_ID']; ?></a></td>
				<td><?php echo $data->Row['Org_Name']; ?></td>
				<td><?php echo $data->Row['Warehouse_Name']; ?></td>
				<td align="center"><?php echo $data->Row['Status']; ?></td>
				<td align="center"><?php echo $data->Row['Reason_Title']; ?></td>
				<td align="center"><?php echo $authorised; ?></td>
				<td align="right">
					<select style="width: 50px;" name="debit_quantity_<?php print $data->Row['Return_ID']; ?>">
						<?php
						for($i = 1; $i <= $data->Row['Quantity']; $i++) {
							if($i == $data->Row['Quantity']) {
								echo sprintf('<option value="%s" selected="selected">%s</option>', $i, $i);
							} else {
								echo sprintf('<option value="%s">%s</option>', $i, $i);
							}
						}
						?>
					</select>
				</td>
				<td align="right"><?php echo "&pound;".number_format($data->Row['Cost'],2,'.',','); ?></td>
				<td align="right"><?php echo "&pound;".number_format($data->Row['Total_Cost'],2,'.',','); ?></td>
			  </tr>

				<?php
				$data->Next();
			}
		  ?>
		   <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td colspan="12" align="right"><strong>Total</strong></td>
				<td align="right"><strong><?php echo "&pound;".number_format($total,2,'.',','); ?></strong></td>
			  </tr>

		  </table><br>

		  <input type="submit" class="btn" value="create debit note" />
	  </form>

	  <?php
		} else {
			echo '<p><strong>No returns to report on.</strong></p>';
		}

		$data->Disconnect();

		$page->Display('footer');
}
?>