<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$session->Secure(2);
view($session->Supplier->ID);
exit;

function view($supplierId) {
    $page = new Page('Pending Supplier Invoice Queries', 'Listing all pending supplier invoice queries.');
	$page->Display('header');

	$table = new DataTable('queries');
	$table->SetSQL(sprintf("SELECT siq.*, DATE(siq.CreatedOn) AS CreatedDate, IF(siq.InvoiceDate<>'0000-00-00 00:00:00', DATE(siq.InvoiceDate), '') AS InvoiceDate FROM supplier_invoice_query AS siq INNER JOIN supplier AS s ON s.Supplier_ID=siq.SupplierID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE siq.Status LIKE 'Pending' AND siq.SupplierID=%d GROUP BY siq.SupplierInvoiceQueryID", $supplierId));
	$table->AddField('ID', 'SupplierInvoiceQueryID');
	$table->AddField('Created Date','CreatedDate');
	$table->AddField('Status', 'Status', 'left');
	$table->AddField('Invoice Reference','InvoiceReference');
	$table->AddField('Total', 'Total', 'right');
	$table->AddLink('supplier_supplier_invoice_query_details.php?queryid=%s', '<img src="images/folderopen.gif" alt="Open" border="0" />', 'SupplierInvoiceQueryID');
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