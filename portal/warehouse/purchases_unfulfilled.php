<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

if($action == 'cancel'){
	$session->Secure(3);
	cancel();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function cancel() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');

	if(isset($_REQUEST['pid'])) {
		$purchase = new Purchase($_REQUEST['pid']);
		$purchase->Cancel();
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page("Unfulfilled Purchases", "Listing all unfulfilled purchases.");
	$page->Display('header');

	$table = new DataTable("purchases");
	$table->SetSQL(sprintf("SELECT * FROM purchase AS p WHERE p.For_Branch>0 AND p.Purchase_Status IN ('Unfulfilled', 'Partially Fulfilled')"));
	$table->AddField('ID#','Purchase_ID');
	$table->AddField('Date Ordered','Purchased_On');
	$table->AddField('Organisation','Supplier_Organisation_Name');
	$table->AddField('First Name','Supplier_First_Name');
	$table->AddField('Last Name','Supplier_Last_Name');
	$table->AddField('Status','Purchase_Status');
	$table->AddField('Custom Reference', 'Custom_Reference_Number');
	$table->AddField('Notes','Order_Note');
	$table->AddLink('purchase_edit.php?pid=%s',"<img src=\"images/folderopen.gif\" alt=\"View\" border=\"0\">",'Purchase_ID');
	$table->AddLink("javascript:confirmRequest('purchases_view.php?action=cancel&pid=%s','Are you sure you want to cancel this item?');","<img src=\"./images/aztector_6.gif\" alt=\"Cancel\" border=\"0\">","Purchase_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy('Purchased_On');
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
}