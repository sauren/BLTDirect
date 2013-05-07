<?php
require_once('lib/common/app_header.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

	$customer = new Customer($_REQUEST['customer']);
	$customer->Contact->Get();
	$tempHeader = "";

	if($customer->Contact->HasParent){
		$tempHeader .= sprintf("<a href=\"contact_profile.php?cid=%d\">%s</a> &gt; ", $customer->Contact->Parent->ID, $customer->Contact->Parent->Organisation->Name);
	}
	$tempHeader .= sprintf("<a href=\"contact_profile.php?cid=%d\">%s %s</a> &gt;", $customer->Contact->ID, $customer->Contact->Person->Name, $customer->Contact->Person->LastName);

	$page = new Page(sprintf('%s Products for %s', $tempHeader, $customer->Contact->Person->GetFullName()),
	sprintf('Below is the list of products for %s only.', $customer->Contact->Person->GetFullName()));
	$page->Display('header');

	$sql = sprintf("SELECT cp.Customer_Product_ID, p.SKU, p.Product_ID, p.Product_Title from customer_product as cp inner join product AS p ON p.Product_ID=cp.Product_ID where cp.Customer_ID=%d", $customer->ID);
	$table = new DataTable("orders");
	$table->SetSQL($sql);
	$table->AddField('Quickfind', 'Product_ID', 'left');
	$table->AddField('Product Title', 'Product_Title', 'left');
	$table->AddField('SKU', 'SKU', 'left');
	$table->AddLink("product_profile.php?pid=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open Product Details\" border=\"0\">", "Product_ID");
	$table->AddLink("javascript:confirmRequest('customer_products.php?action=remove&id=%s&customer=".$customer->ID."','Are you sure you want to remove this product from the customers collection? Note: you will NOT lose any product information by performing this operation.');","<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">","Customer_Product_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Product_ID");
	$table->Order = "ASC";
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function remove(){
	if(isset($_REQUEST['id'])) {
		new DataQuery(sprintf("DELETE FROM customer_product WHERE Customer_Product_ID=%d", mysql_real_escape_string($_REQUEST['id'])));
	}

	if(isset($_REQUEST['customer'])) {
		redirect(sprintf("Location: %s?customer=%d", $_SERVER['PHP_SELF'], $_REQUEST['customer']));
	}
}
?>