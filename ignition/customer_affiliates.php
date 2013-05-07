<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$page = new Page('My Affiliates', 'Manage your customer affiliates here.');
$page->Display('header');

$table = new DataTable("affiliates");
$table->SetSQL(sprintf("SELECT c.Contact_ID, p.Name_First, p.Name_Last, cu.Customer_ID, cu.Is_Active, o.Org_Name FROM customer AS cu INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE cu.Is_Affiliate='Y'"));
$table->AddField('ID#', 'Contact_ID', 'left');
$table->AddField('Organisation', 'Org_Name', 'left');
$table->AddField('First Name', 'Name_First', 'left');
$table->AddField('Last Name', 'Name_Last', 'left');
$table->AddField('Active', 'Is_Active', 'center');
$table->AddLink("customer_affiliate.php?customer=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"View Affiliate Information\" border=\"0\">", "Customer_ID");
$table->AddLink("contact_profile.php?cid=%s", "<img src=\"./images/folderopen.gif\" alt=\"View Contact Details\" border=\"0\">", "Contact_ID");
$table->SetMaxRows(25);
$table->SetOrderBy("Contact_ID");
$table->Finalise();
$table->DisplayTable();
echo "<br>";
$table->DisplayNavigation();

$page->Display('footer');
require_once('lib/common/app_footer.php');