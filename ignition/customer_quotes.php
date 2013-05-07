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

$page = new Page(sprintf('%s Quote History for %s', $tempHeader, $customer->Contact->Person->GetFullName()),
				sprintf('Below is the quote history for %s only.', $customer->Contact->Person->GetFullName()));
$page->Display('header');

$sql = sprintf("SELECT * from quote where Customer_ID=%d", $customer->ID);
$table = new DataTable("quotes");
$table->SetSQL($sql);
$table->AddField('Quote Date', 'Quoted_On', 'left');
$table->AddField('Quote Prefix', 'Quote_Prefix', 'left');
$table->AddField('Quote Number', 'Quote_ID', 'right');
$table->AddField('Quote Total', 'Total', 'right');
$table->AddField('Quote', 'Status', 'right');
$table->AddLink("quote_details.php?quoteid=%s",
						"<img src=\"./images/folderopen.gif\" alt=\"Open Quote Details\" border=\"0\">",
						"Quote_ID");
$table->SetMaxRows(25);
$table->SetOrderBy("Quoted_On");
$table->Order = "DESC";
$table->Finalise();
$table->DisplayTable();
echo "<br>";
$table->DisplayNavigation();

$page->Display('footer');
require_once('lib/common/app_footer.php');