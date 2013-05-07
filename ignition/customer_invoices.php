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

$page = new Page(sprintf('%s Invoice History for %s', $tempHeader, $customer->Contact->Person->GetFullName()),
				sprintf('Below is the invoice history for %s only.', $customer->Contact->Person->GetFullName()));
$page->Display('header');

$sql = sprintf("SELECT * from invoice where Customer_ID=%d", $customer->ID);
$table = new DataTable("invoices");
$table->SetSQL($sql);
$table->AddField('Invoice Date', 'Created_On', 'left');
$table->AddField('Invoice Due', 'Invoice_Due_On', 'left');
$table->AddField('Invoice Number', 'Invoice_ID', 'right');
$table->AddField('Invoice Total', 'Invoice_Total', 'right');
$table->AddLink("invoice_view.php?invoiceid=%s",
						"<img src=\"./images/folderopen.gif\" alt=\"Open Invoice Details\" border=\"0\">",
						"Invoice_ID");
$table->SetMaxRows(25);
$table->SetOrderBy("Created_On");
$table->Order = "DESC";
$table->Finalise();
$table->DisplayTable();
echo "<br>";
$table->DisplayNavigation();

$page->Display('footer');
require_once('lib/common/app_footer.php');