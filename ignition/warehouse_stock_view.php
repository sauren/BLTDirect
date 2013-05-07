<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseStock.php');

if($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} else{
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	if(isset($_REQUEST['sid'])) {
		$stock = new WarehouseStock($_REQUEST['sid']);

		if($stock->IsWrittenOff != 'Y' && $stock->WrittenOffBy > 0){
			$stock->Delete($_REQUEST['sid']);

		} else {
			echo 'This stock has been written off, and cannot be deleted.<br />';
			echo '<input type="button" type="submit" value="return" class="btn" onclick="window.location.href=\'warehouse_stock_view.php?pid='.$_REQUEST['pid'].'\'">';
			exit;
		}
	}
	
	redirect(sprintf("Location: warehouse_stock_view.php?pid=%d",$_REQUEST['pid']));
}

function view(){
	$data = new DataQuery(sprintf("SELECT * FROM product WHERE Product_ID = %d",mysql_real_escape_string($_REQUEST['pid'])));
	
	$page = new Page(sprintf("<a href='product_profile.php?pid=%d'> %s </a> &gt; Warehouse Stock",$_REQUEST['pid'],strip_tags($data->Row['Product_Title'])),"Stock control is needed for the branches, here you can edit the information about the stock");
	$page->Display('header');
	
	$table = new DataTable("com");
	$table->SetSQL(sprintf("SELECT w.Warehouse_Name, ws.*, m.Manufacturer_Name FROM warehouse_stock AS ws INNER JOIN warehouse w on ws.Warehouse_ID=w.Warehouse_ID LEFT JOIN manufacturer AS m ON m.Manufacturer_ID=ws.Manufacturer_ID WHERE ws.Product_ID=%d", mysql_real_escape_string($_REQUEST['pid'])));
	$table->AddField('Stock ID','Stock_ID');
	$table->AddField('Warehouse','Warehouse_Name');
	$table->AddField('Manufacturer','Manufacturer_Name');
	$table->AddField('Location','Shelf_Location');
	$table->AddField('Quantity','Quantity_In_Stock','right');
	$table->AddField('Cost','Cost','right');
	$table->AddField('Archived','Is_Archived','center');
	$table->AddField('Written Off','Is_Writtenoff','center');
	$table->AddLink('warehouse_stock_writeoff.php?sid=%s',"<img src=\"./images/icon_cross_4.gif\" alt=\"Update\" border=\"0\">",'Stock_ID');
	$table->AddLink('warehouse_stock_edit.php?sid=%s',"<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">",'Stock_ID');
	$table->AddLink("javascript:confirmRequest('warehouse_stock_view.php?action=remove&confirm=true&sid=%s','Are you sure you want to remove this item?');","<img src=\"./images/aztector_6.gif\" alt=\"Remove this stock from the warehouse\" border=\"0\">","Stock_ID");
	$table->SetOrderBy('Stock_ID');
	$table->SetMaxRows(25);
	$table->Finalise();
	$table->DisplayTable();

	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo '<input type = "button" type="submit" value="add stock" class = "btn" onclick="window.location.href=\'warehouse_stock_add.php?pid='.$_REQUEST['pid'].'\'">';
	
	echo '<br /><br />';
	
	echo '<h3>Purchases</h3>';
	echo '<p>Listing outstanding purchases which contain incoming quantities for this product.</p>';
	
	$table = new DataTable("purchases");
	$table->SetExtractVars(array('pid'));
	$table->SetExtractVarsLink(array('pid'));
	$table->SetSQL(sprintf("SELECT p.*, SUM(pl.Quantity_Decremental) AS Quantity_Incoming, pl.Cost FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID INNER JOIN users u ON u.Branch_ID=p.For_Branch WHERE u.User_ID=%d AND pl.Quantity_Decremental>0 AND pl.Product_ID=%d GROUP BY p.Purchase_ID", mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($_REQUEST['pid'])));
	$table->AddField('Purchase ID','Purchase_ID');
	$table->AddField('Date Ordered','Purchased_On');
	$table->AddField('Organisation','Supplier_Organisation_Name');
	$table->AddField('First Name','Supplier_First_Name');
	$table->AddField('Last Name','Supplier_Last_Name');
	$table->AddField('Status','Purchase_Status');
	$table->AddField('Quantity Incoming','Quantity_Incoming', 'right');
	$table->AddField('Cost','Cost', 'right');
	$table->AddLink('purchase_edit.php?pid=%s',"<img src=\"./images/icon_edit_1.gif\" alt=\"Update the purchase settings\" border=\"0\">",'Purchase_ID');
	$table->SetMaxRows(25);
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
	
	$page->Display('footer');
}