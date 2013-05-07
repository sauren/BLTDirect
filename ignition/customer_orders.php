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

$page = new Page(sprintf('%s Order History for %s', $tempHeader, $customer->Contact->Person->GetFullName()), sprintf('Below is the order history for %s only.', $customer->Contact->Person->GetFullName()));
$page->Display('header');

$table = new DataTable("orders");
$table->SetSQL(sprintf("SELECT * from orders where Customer_ID=%d AND Status <> 'Compromised'", $customer->ID));
$table->AddField('Order Date', 'Ordered_On', 'left');
$table->AddField('Order Prefix', 'Order_Prefix', 'left');
$table->AddField('Order Number', 'Order_ID', 'right');
$table->AddField('Order Total', 'Total', 'right');
$table->AddField('Status', 'Status', 'right');
$table->AddLink("order_details.php?orderid=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open Order Details\" border=\"0\">", "Order_ID");
$table->SetMaxRows(25);
$table->SetOrderBy("Ordered_On");
$table->Order = "DESC";
$table->Finalise();
$table->DisplayTable();
echo "<br>";
$table->DisplayNavigation();

$page->Display('footer');
require_once('lib/common/app_footer.php');