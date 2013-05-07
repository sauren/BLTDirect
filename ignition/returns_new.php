<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'add'){
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'update'){
	$session->Secure(3);
	update();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function view(){

	$page = new Page('New and Open Returns', 'Below is a list of all new returns requests, which require action.');
	$page->Display('header');
	$sql = "SELECT r.*, o.Order_ID, o.Billing_Organisation_Name, rr.Reason_Title,
                o.Billing_Last_Name, o.Billing_First_Name FROM `return` AS r
                INNER JOIN order_line AS ol ON r.Order_Line_ID = ol.Order_Line_ID
                INNER JOIN orders AS o ON o.Order_ID = ol.Order_ID
                INNER JOIN return_reason AS rr ON r.Reason_ID = rr.Reason_ID
                WHERE r.Status LIKE 'Unread'";
	$table = new DataTable("returns");
	$table->SetSQL($sql);
	$table->AddField('RETURN', 'Return_ID', 'left');
	$table->AddField('Requested', 'Requested_On', 'left');
	$table->AddField('Organisation', 'Billing_Organisation_Name', 'left');
	$table->AddField('Name', 'Billing_First_Name', 'left');
	$table->AddField('Surname', 'Billing_Last_Name', 'left');
	$table->AddField('Order Number', 'Order_ID', 'right');
	$table->AddField('Reason', 'Reason_Title');
	$table->AddLink("return_details.php?id=%s",
	"<img src=\"./images/folderopen.gif\" alt=\"Open Return Details\" border=\"0\">",
	"Return_ID");
	$table->AddLink("javascript:confirmRequest('returns_new.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this return?');",
	"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
	"Return_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Requested_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function add(){
}

function update(){
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Return.php');

	if(isset($_REQUEST['id']) && isset($_REQUEST['confirm'])) {
		$return = new ProductReturn($_REQUEST['id']);
		$return->Delete();
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}
?>
