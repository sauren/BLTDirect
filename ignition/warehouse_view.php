<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');

if($action == remove){
	$session->Secure(3);
	if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true') && isset($_REQUEST['wid'])){
		$warehouse = new Warehouse($_REQUEST['wid']);
		$warehouse->Delete();
		
		unset($data);
	}
	redirect("Location: warehouse_view.php");
	exit();
}
else{
	$session->Secure(2);
	view();
	exit();
}

function view(){
	$page = new Page("Warehouses","Here you can add or remove Warehouses belonging to the company and view the products held by that warehouse");
	$page->Display('header');
	$sql = "SELECT * FROM warehouse";
	$table = new DataTable("com");
	$table->SetSQL($sql);
	$table->AddField('ID','Warehouse_ID','right');
	$table->AddField('Name','Warehouse_Name');
	$table->AddField('Next Day Tracking Required','Is_Next_Day_Tracking_Required', 'center');
	$table->AddLink('warehouse_stock_view.php?wid=%d',"<img src=\"./images/folderopen.gif\" alt=\"Edit the stock of this warehouse\" border=\"0\">",'Warehouse_ID');
	$table->AddLink('warehouse_edit.php?wid=%d',"<img src=\"./images/icon_edit_1.gif\" alt=\"Edit the details of this warehouse\" border=\"0\">",'Warehouse_ID');
	$table->AddLink("javascript:confirmRequest('warehouse_view.php?action=remove&confirm=true&wid=%d','Are you sure you want to remove this warehouse?');","<img src=\"./images/aztector_6.gif\" alt=\"Remove this warehouse\" border=\"0\">","Warehouse_ID");
	$table->SetMaxRows(25);
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type = "button" type="submit" value="Add a new warehouse" class = "btn" onclick="window.location.href=\'./warehouse_add.php\'">';
	$page->Display('footer');
}
?>