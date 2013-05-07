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

$page = new Page(sprintf('%s Return History for %s', $tempHeader, $customer->Contact->Person->GetFullName()),
				sprintf('Below is the return history for %s only.', $customer->Contact->Person->GetFullName()));
$page->Display('header');

$sql = sprintf("SELECT * from `return` where Customer_ID=%d", $customer->ID);
$table = new DataTable("quotes");
$table->SetSQL($sql);
$table->AddField('Return', 'Return_ID', 'right');
$table->AddField('Request Date', 'Requested_On', 'left');
$table->AddField('Status', 'Status', 'right');
$table->AddLink("return_details.php?id=%s",
						"<img src=\"./images/folderopen.gif\" alt=\"Open Return Details\" border=\"0\">",
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