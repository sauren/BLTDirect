<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
view();
exit;

function view() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Pending Action Orders (Tax Free)', 'Below is a list of all orders pending further action.');
	$page->Display('header');

	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('status', 'Status', 'select', '', 'alpha', 0, 10);
	$form->AddOption('status', '', '');
	$form->AddOption('status', 'pending', 'Pending Orders');
	$form->AddOption('status', 'packing', 'Packing Orders');
	$form->AddOption('status', 'partial', 'Partially Despatched Orders');
	$form->AddOption('status', 'backordered', 'Backordered Orders');
	$form->AddField('warehouse', 'Warehouse', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('warehouse', '0', '');

	$data = new DataQuery('SELECT Warehouse_ID, Warehouse_Name FROM warehouse ORDER BY Warehouse_Name ASC');
	while($data->Row){
		$form->AddOption('warehouse', $data->Row['Warehouse_ID'], $data->Row['Warehouse_Name']);

		$data->Next();
	}
	$data->Disconnect();

	$window = new StandardWindow('Filter orders');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('status'),$form->GetHTML('status'));
	echo $webForm->AddRow($form->GetLabel('warehouse'), $form->GetHTML('warehouse'));
	echo $webForm->AddRow('', '<input type="submit" name="filter" value="filter" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	echo '<br />';

	$window = new StandardWindow('Pull data into the supplier control form.');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('', sprintf('<input type="button" name="pull data" value="pull data" class="btn" onclick="window.location.href=\'control_supplier.php?variation=pendingorders&status=%s&warehouse=%d\'">', $form->GetValue('status'), $form->GetValue('warehouse')));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	echo '<br />';

	$sqlSelect = sprintf("SELECT o.Order_ID, o.Is_Notes_Unread, o.Deadline_On, o.Is_Absent_Stock_Profile, o.Created_On, o.Status, o.Total, o.Backordered, CONCAT(o.Order_Prefix, o.Order_ID) AS Order_Number, CONCAT_WS(' ', TRIM(CONCAT_WS(' ', o.Billing_First_Name, o.Billing_Last_Name)), REPLACE(CONCAT('(', o.Billing_Organisation_Name, ')'), '()', '')) AS Billing_Contact, po.Postage_Title, po.Postage_Days, IF(ol.Backorder_Expected_On='0000-00-00 00:00:00', '', ol.Backorder_Expected_On) AS Backorder_Date ");
	$sqlFrom = sprintf("FROM orders AS o LEFT JOIN postage AS po ON o.Postage_ID=po.Postage_ID LEFT JOIN order_line AS ol ON o.Order_ID=ol.Order_ID AND ol.Despatch_ID=0 ");
	$sqlWhere = sprintf("WHERE (o.Status LIKE 'Pending' OR o.Status LIKE 'Partially Despatched' OR o.Status LIKE 'Packing') AND o.Is_Security_Risk='N' AND o.Total>0 AND o.TotalTax=0 AND o.Is_Bidding='N' AND o.Is_Awaiting_Customer='N' ");
	$sqlGroup = sprintf("GROUP BY o.Order_ID");

	if(strlen($form->GetValue('status')) > 0) {
		switch($form->GetValue('status')) {
			case 'pending':
				$sqlWhere .= sprintf("AND o.Status LIKE 'Pending' ");
				break;
			case 'packing':
				$sqlWhere .= sprintf("AND o.Status LIKE 'Packing' ");
				break;
			case 'partial':
				$sqlWhere .= sprintf("AND o.Status LIKE 'Partially Despatched' ");
				break;
			case 'backordered':
				$sqlWhere .= sprintf("AND ol.Line_Status LIKE 'Backordered' ");
				break;
		}
	}

	if($form->GetValue('warehouse') > 0) {
		$sqlWhere .= sprintf("AND ol.Despatch_From_ID=%d ", $form->GetValue('warehouse'));
	}

	$table = new DataTable("orders");
	$table->SetSQL(sprintf('%s%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere, $sqlGroup));
	$table->AddBackgroundCondition('Is_Notes_Unread', 'Y', '==', '#99C5FF', '#77B0EE');
	$table->AddBackgroundCondition(array('Deadline_On', 'Deadline_On'), array(date('Y-m-d H:i:s'), '0000-00-00 00:00:00'), array('<', '>'), '#FFB3B3', '#FF9D9D');
	$table->AddBackgroundCondition('Is_Absent_Stock_Profile', 'Y', '==', '#BB99FF', '#9F77EE');
	$table->AddBackgroundCondition('Postage_Days', '1', '==', '#FFF499', '#EEE177');
	$table->AddField('', 'Postage_Days', 'hidden');
	$table->AddField('', 'Deadline_On', 'hidden');
	$table->AddField('', 'Is_Notes_Unread', 'hidden');
	$table->AddField('', 'Is_Absent_Stock_Profile', 'hidden');
	$table->AddField('Order Date', 'Created_On', 'left');
	$table->AddField('Order Number', 'Order_Number', 'left');
	$table->AddField('Contact', 'Billing_Contact', 'left');
	$table->AddField('Status', 'Status', 'left');
	$table->AddField('Postage', 'Postage_Title', 'left');
	$table->AddField('Total', 'Total', 'right');
	$table->AddField('Backordered', 'Backordered', 'center');
	$table->AddField('Expected', 'Backorder_Date', 'left');
	$table->AddLink("order_details.php?orderid=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open Order\" border=\"0\">", "Order_ID");
	$table->AddLink("javascript:popUrl('order_cancel.php?orderid=%s', 800, 600);", "<img src=\"images/aztector_6.gif\" alt=\"Cancel\" border=\"0\">", "Order_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Created_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}