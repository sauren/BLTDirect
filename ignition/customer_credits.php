<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

$customer = new Customer($_REQUEST['customer']);
$customer->Contact->Get();
$tempHeader = '';

if($customer->Contact->HasParent){
	$tempHeader .= sprintf("<a href=\"contact_profile.php?cid=%d\">%s</a> &gt; ", $customer->Contact->Parent->ID, $customer->Contact->Parent->Organisation->Name);
}
$tempHeader .= sprintf("<a href=\"contact_profile.php?cid=%d\">%s %s</a> &gt;", $customer->Contact->ID, $customer->Contact->Person->Name, $customer->Contact->Person->LastName);

$page = new Page(sprintf('%s Credit History for %s', $tempHeader, $customer->Contact->Person->GetFullName()), sprintf('Below is the credit history for %s only.', $customer->Contact->Person->GetFullName()));
$page->Display('header');

$sql = sprintf("SELECT c.* from credit_note AS c INNER JOIN orders AS o ON o.Order_ID=c.Order_ID where Customer_ID=%d", $customer->ID);
$table = new DataTable("credit");
$table->SetSQL($sql);
$table->AddField('Credit Date', 'Credited_On', 'left');
$table->AddField('Credit Type', 'Credit_Type', 'left');
$table->AddField('Credit Number', 'Credit_Note_ID', 'right');
$table->AddField('Credit Total', 'Total', 'right');
$table->AddField('Status', 'Credit_Status', 'right');
$table->AddLink("credit_note_view.php?cnid=%s",
						"<img src=\"./images/folderopen.gif\" alt=\"Open Order Details\" border=\"0\">",
						"Credit_Note_ID");
$table->SetMaxRows(25);
$table->SetOrderBy("Ordered_On");
$table->Order = "DESC";
$table->Finalise();
$table->DisplayTable();
echo "<br>";
$table->DisplayNavigation();

$page->Display('footer');
require_once('lib/common/app_footer.php');