<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReturnAuto.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'convert'){
	$session->Secure(3);
	convert();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	if(isset($_REQUEST['id'])) {
		$return = new ReturnAuto($_REQUEST['id']);
		$return->delete();
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function convert(){
	if(isset($_REQUEST['id'])) {
		$return = new ReturnAuto($_REQUEST['id']);
		
		$orderId = $return->convert();

		redirect(sprintf("Location: order_details.php?orderid=%d", $orderId));
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function view(){
	$page = new Page('Returns Auto', 'Listing returns which are applicable for automatic re-ordering.');
	$page->Display('header');
	
	$table = new DataTable('returns');
	$table->SetSQL('SELECT ra.*, p.Product_Title, CONCAT_WS(\' \', o.Billing_First_Name, o.Billing_Last_Name, CONCAT(\'(\', o.Billing_Organisation_Name, \')\')) AS billingContact FROM return_auto AS ra INNER JOIN product AS p ON p.Product_ID=ra.productId INNER JOIN orders AS o ON o.Order_ID=ra.orderId');
	$table->AddField('ID', 'id', 'left');
	$table->AddField('Date', 'createdOn', 'left');
	$table->AddField('Billing Contact', 'billingContact', 'left');
	$table->AddField('Order Number', 'orderId', 'left');
	$table->AddField('Product', 'Product_Title', 'left');
	$table->AddLink("order_details.php?orderid=%s", "<img src=\"images/folderopen.gif\" alt=\"Open\" border=\"0\">", "orderId");
	$table->AddLink("javascript:confirmRequest('?action=convert&id=%s','Are you sure you want to convert this item?');", "<img src=\"images/button-tick.gif\" alt=\"Convert\" border=\"0\">", "id");
	$table->AddLink("javascript:confirmRequest('?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/button-cross.gif\" alt=\"Remove\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("createdOn");
	$table->Order = 'ASC';
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}