<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$session->Secure(2);
view($session->Supplier->ID);
exit;

function view($supplierId) {
    $page = new Page('Completed Debits', 'Listing all completed supplier debits.');
	$page->Display('header');

	$table = new DataTable('debits');
	$table->SetSQL(sprintf("SELECT *, DATE(Created_On) AS CreatedDate FROM debit WHERE Is_Paid='Y' AND Supplier_ID=%d", $supplierId));
	$table->AddField('ID', 'Debit_ID');
	$table->AddField('Created Date','CreatedDate');
	$table->AddField('Total','Debit_Total', 'right');
	$table->AddLink('supplier_debit_details.php?debitid=%s', '<img src="images/folderopen.gif" alt="Open" border="0" />', 'Debit_ID');
	$table->SetMaxRows(25);
	$table->SetOrderBy('CreatedDate');
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}