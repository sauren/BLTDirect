<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

$customer = new Customer($_REQUEST['customer']);
$customer->Contact->Get();
$tempHeader = "";

if($customer->Contact->HasParent){
	$tempHeader .= sprintf("<a href=\"contact_profile.php?cid=%d\">%s</a> &gt; ", $customer->Contact->Parent->ID, $customer->Contact->Parent->Organisation->Name);
}
$tempHeader .= sprintf("<a href=\"contact_profile.php?cid=%d\">%s %s</a> &gt;", $customer->Contact->ID, $customer->Contact->Person->Name, $customer->Contact->Person->LastName);

$page = new Page(sprintf('%s Order Purchase History for %s', $tempHeader, $customer->Contact->Person->GetFullName()), sprintf('Below is the order purchase history for %s only.', $customer->Contact->Person->GetFullName()));
$page->Display('header');

$table = new DataTable("purchases");
$table->SetSQL(sprintf("SELECT od.*, CONCAT(o.Order_ID, o.Order_Prefix) AS orderReference FROM order_document AS od INNER JOIN orders AS o ON o.Order_ID=od.orderId WHERE o.Customer_ID=%d", $customer->ID));
$table->AddField('ID#', 'id', 'left');
$table->AddField('Date Created', 'createdOn', 'left');
$table->AddField('Type', 'type', 'left');
$table->AddField('Name', 'name', 'left');
$table->AddField('File Name', 'fileName', 'left');
$table->AddField('Order', 'orderReference', 'left');
$table->AddLink("order_documents.php?action=download&documentid=%s", "<img src=\"images/folderopen.gif\" alt=\"Download\" border=\"0\">", "id");
$table->SetMaxRows(25);
$table->SetOrderBy("createdOn");
$table->Order = "DESC";
$table->Finalise();
$table->DisplayTable();
echo "<br>";
$table->DisplayNavigation();

$page->Display('footer');
require_once('lib/common/app_footer.php');