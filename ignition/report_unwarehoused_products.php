<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$page = new Page("Unwarehoused Products Report","Here you can see the products that are not stored in any warehouses");
$page->Display('header');

$sql = "SELECT p.* FROM product p LEFT JOIN warehouse_stock s ON p.Product_ID = s.Product_ID where s.Stock_ID IS NULL
		group by p.Product_ID";
$table = new DataTable("com");
$table->SetSQL($sql);
$table->AddField('ID','Product_ID','right');
$table->AddField('SKU','SKU');
$table->AddField('Product Title','Produc_Title');
$table->AddLink('warehouse_stock_view.php?pid=%d',"<img src=\"./images/icon_edit_1.gif\" alt=\"view the warehouses for this product\" border=\"0\">",'Product_ID');
$table->SetMaxRows(25);
$table->Finalise();
$table->DisplayTable();
echo "<br>";
$table->DisplayNavigation();
echo "<br>";
echo '<input type = "button" type="submit" value="Add a new branch" class = "btn" onclick="window.location.href=\'./branch_add.php\'">';
$page->Display('footer');