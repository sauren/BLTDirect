<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$session->Secure(2);
view($session->Supplier->ID);
exit;

function view($supplierId) {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 1, 11);
	$form->AddField('orderid', 'Order ID', 'text', '', 'numeric_unsigned', 1, 11);

	$sqlSelect = '';
	$sqlFrom = '';
	$sqlWhere = '';
	$sqlGroup = '';

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$sqlSelect = sprintf("SELECT o.*, p.Postage_Title, p.Postage_Days ");
			$sqlFrom = sprintf("FROM orders AS o INNER JOIN postage AS p ON o.Postage_ID=p.Postage_ID INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' AND w.Type_Reference_ID=%d ", $supplierId);
			$sqlWhere = sprintf("WHERE o.Status IN ('Packing', 'Partially Despatched', 'Despatched') ");
			$sqlGroup = sprintf("GROUP BY o.Order_ID");

			if(strlen($form->GetValue('orderid')) > 0) {
				$sqlWhere .= sprintf("AND o.Order_ID=%d ", mysql_real_escape_string($form->GetValue('orderid')));
			}
		}
	}

	$page = new Page('Search Orders', 'Listing all available orders.');
	$page->Display('header');

	$window = new StandardWindow('Search orders');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('orderid'), $form->GetHTML('orderid').'<input type="submit" name="search" value="search" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	if(strlen(sprintf('%s%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere, $sqlGroup)) > 0) {
		echo '<br />';

		$table = new DataTable('orders');
		$table->SetSQL(sprintf('%s%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere, $sqlGroup));
		$table->AddField('ID', 'Order_ID');
		$table->AddField('Created Date','Created_On');
		$table->AddField('Status', 'Status', 'left');
		$table->AddField('Postage Details', 'Postage_Title', 'left');
		$table->AddField('Prefix', 'Order_Prefix', 'center');
		$table->AddLink('supplier_order_details.php?orderid=%s', '<img src="images/folderopen.gif" alt="Open" border="0" />', 'Order_ID');
		$table->SetMaxRows(25);
		$table->SetOrderBy('Created_On');
		$table->Order = 'DESC';
		$table->Finalise();
		$table->DisplayTable();
		echo '<br />';
		$table->DisplayNavigation();
	}

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}