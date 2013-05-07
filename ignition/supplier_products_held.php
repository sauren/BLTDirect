<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$session->Secure(2);
view($session->Supplier->ID);
exit;

function view($supplierId) {
    $page = new Page('Held Products', 'Listing supplier stock held for selected products.');
	$page->Display('header');

	$table = new DataTable('products');
	$table->SetSQL(sprintf("SELECT p.Product_ID, p.Product_Title, sphr.Quantity, sp.Cost, sphr.Quantity*sp.Cost AS Total FROM supplier_product_hold_request AS sphr INNER JOIN product AS p ON p.Product_ID=sphr.ProductID LEFT JOIN supplier_product AS sp ON sp.Product_ID=sphr.ProductID AND sp.Supplier_ID=sphr.SupplierID WHERE sphr.SupplierID=%d", $supplierId));
	$table->AddField('ID', 'Product_ID');
	$table->AddField('Product','Product_Title');
	$table->AddField('Cost','Cost', 'right');
	$table->AddField('Proposed Hold Quantity','Quantity', 'right');
	$table->AddField('Total','Total', 'right');
	$table->SetMaxRows(25);
	$table->SetOrderBy('Product_Title');
	$table->Order = 'ASC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}