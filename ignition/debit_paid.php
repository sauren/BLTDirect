<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseStock.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

if($action == 'remove') {
	$session->Secure(3);
	remove();
} elseif($action == 'open') {
	$session->Secure(2);
	open();
} else {
	$session->Secure(2);
	view();
}

function open(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Debit.php');

	$debit = new Debit($_REQUEST['id']);
	$html = $debit->GetDocument();
	$html .= '<br><br><br>';

	echo $html;
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Debit.php');

	if(isset($_REQUEST['id']) && isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		$debit = new Debit();
		$debit->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page("Completed Debits","Here you can view debits paid.");
	$page->Display('header');

	$table = new DataTable("com");
	$table->SetSQL("SELECT *, CONCAT(Prefix, Debit_ID) AS Debit_Reference FROM debit WHERE Is_Paid='Y'");
	$table->AddField('Date Debited','Created_On');
	$table->AddField('Reference', 'Debit_Reference');
	$table->AddField('Organisation','Debit_Organisation');
	$table->AddField('First Name','Debit_First_Name');
	$table->AddField('Last Name','Debit_Last_Name');
	$table->AddField('Amount Due','Debit_Total');
	$table->AddLink('debit_paid.php?action=open&id=%s',"<img src=\"./images/folderopen.gif\" alt=\"Open this debit note\" border=\"0\">",'Debit_ID');
	$table->AddLink("javascript:confirmRequest('debit_paid.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this debit note?');","<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">","Debit_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy('Created_On');
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
}

require_once('lib/common/app_footer.php');