<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$session->Secure(2);
view($session->Supplier->ID);
exit;

function view($supplierId) {
    $page = new Page('Completed Price Enquiries', 'Listing all completed price enquiries.');
	$page->Display('header');

	$table = new DataTable('debits');
	$table->SetSQL(sprintf("SELECT pe.*, DATE(pe.Created_On) AS CreatedDate, pes.Is_Complete, IF(pes.Position>0, pes.Position, '') AS Position_Text FROM price_enquiry AS pe INNER JOIN price_enquiry_supplier AS pes ON pes.Price_Enquiry_ID=pe.Price_Enquiry_ID WHERE pe.Status LIKE 'Completed' AND pes.Supplier_ID=%d", $supplierId));
	$table->AddField('ID', 'Price_Enquiry_ID');
	$table->AddField('Created Date','CreatedDate');
	$table->AddField('Status', 'Status', 'left');
	$table->AddField('Position', 'Position_Text', 'center');
	$table->AddField('Is Priced', 'Is_Complete', 'center');
	$table->AddLink('supplier_price_enquiry_details.php?id=%s', '<img src="images/folderopen.gif" alt="Open" border="0" />', 'Price_Enquiry_ID');
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