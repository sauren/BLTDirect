<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$session->Secure(3);

$name = new DataQuery(sprintf('SELECT p.Name_First,p.Name_Last FROM contact c INNER JOIN person p ON c.Person_ID = p.Person_ID WHERE c.Contact_ID = %d', mysql_real_escape_string($_REQUEST['cid'])));

$page = new Page(sprintf("<a href=contact_profile.php?cid=%d>%s %s</a> &gt Products supplied",$_REQUEST['cid'], $name->Row['Name_First'],$name->Row['Name_Last']),"View the products supplied by this supplier.");
$page->Display('header');

$name->Disconnect();

$table = new DataTable("com");
$table->SetSQL(sprintf('SELECT s.Supplier_Product_ID, s.Cost, p.Product_ID, p.SKU, p.Product_Title FROM product AS p INNER JOIN supplier_product AS s ON s.Product_ID=p.Product_ID AND s.Supplier_ID=%1$d WHERE p.LockedSupplierID=%1$d OR p.DropSupplierID=%1$d', mysql_real_escape_string($_REQUEST['sid'])));
$table->AddField('ID','Product_ID');
$table->AddField('SKU','SKU');
$table->AddField('Product','Product_Title');
$table->AddField('Cost','Cost', 'right');
$table->AddLink('product_profile.php?pid=%s',"<img src=\"images/folderopen.gif\" alt=\"Update\" border=\"0\">", 'Product_ID');
$table->SetMaxRows(25);
$table->SetOrderBy('Product_ID');
$table->Finalise();
$table->DisplayTable();
echo "<br>";
$table->DisplayNavigation();

$page->Display('footer');
require_once('lib/common/app_footer.php');