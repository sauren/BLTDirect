<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

if($action == 'open') {
	$session->Secure(2);
	open();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function open() {
	$product = new Product();

	if(!isset($_REQUEST['productid']) || !$product->Get($_REQUEST['productid'])) {
		redirectTo('?action=view');
	}

	$page = new Page(sprintf('<a href="?action=view">Common Returns</a> &gt; %s', $product->Name), 'Viewing return reasons for this product.');
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

function view() {
	$page = new Page('Common Returns', 'Listing your commonly returns products.');
	$page->Display('header');

	$table = new DataTable('products');
	$table->SetExtractVars();
	$table->SetSQL(sprintf('SELECT ol.Product_ID, ol.Product_Title, COUNT(r.Return_ID) AS Returns, SUM(r.Quantity) AS Quantity, r2.Returns AS ReturnsBroken, r3.Returns AS ReturnsFaulty, r4.Returns AS ReturnsIncorrect FROM `return` AS r INNER JOIN order_line AS ol ON ol.Order_Line_ID=r.Order_Line_ID INNER JOIN warehouse AS w ON ol.Despatch_From_ID=w.Warehouse_ID AND w.Type=\'B\' LEFT JOIN (SELECT ol.Product_ID, COUNT(r.Return_ID) AS Returns FROM `return` AS r INNER JOIN order_line AS ol ON ol.Order_Line_ID=r.Order_Line_ID INNER JOIN warehouse AS w ON ol.Despatch_From_ID=w.Warehouse_ID AND w.Type=\'B\' WHERE r.Reason_ID=2 AND ol.Product_ID>0 AND r.Created_On>ADDDATE(NOW(), INTERVAL -12 MONTH) GROUP BY ol.Product_ID) AS r2 on r2.Product_ID=ol.Product_ID LEFT JOIN (SELECT ol.Product_ID, COUNT(r.Return_ID) AS Returns FROM `return` AS r INNER JOIN order_line AS ol ON ol.Order_Line_ID=r.Order_Line_ID INNER JOIN warehouse AS w ON ol.Despatch_From_ID=w.Warehouse_ID AND w.Type=\'B\' WHERE r.Reason_ID=3 AND ol.Product_ID>0 AND r.Created_On>ADDDATE(NOW(), INTERVAL -12 MONTH) GROUP BY ol.Product_ID) AS r3 on r3.Product_ID=ol.Product_ID LEFT JOIN (SELECT ol.Product_ID, COUNT(r.Return_ID) AS Returns FROM `return` AS r INNER JOIN order_line AS ol ON ol.Order_Line_ID=r.Order_Line_ID INNER JOIN warehouse AS w ON ol.Despatch_From_ID=w.Warehouse_ID AND w.Type=\'B\' WHERE r.Reason_ID=4 AND ol.Product_ID>0 AND r.Created_On>ADDDATE(NOW(), INTERVAL -12 MONTH) GROUP BY ol.Product_ID) AS r4 on r4.Product_ID=ol.Product_ID WHERE r.Reason_ID IN (2, 3, 4) AND ol.Product_ID>0 AND r.Created_On>ADDDATE(NOW(), INTERVAL -12 MONTH) GROUP BY ol.Product_ID'));
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