<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');

$page = new Page('Managed Accounts', 'Below is a list of all managed contact accounts and their account managers.');
$page->Display('header');

$table = new DataTable('contacts');
$table->SetSQL(sprintf("SELECT c.Contact_ID, c.Is_Active, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Contact_Name, CONCAT_WS(' ', p2.Name_First, p2.Name_Last) AS Manager_Name FROM contact AS c LEFT JOIN customer AS cu ON cu.Contact_ID=c.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID INNER JOIN users AS u ON u.User_ID=c.Account_Manager_ID LEFT JOIN person AS p2 ON u.Person_ID=p2.Person_ID WHERE c.Account_Manager_ID>0"));
$table->AddField('ID#', 'Contact_ID', 'left');
$table->AddField('Contact', 'Contact_Name', 'left');
$table->AddField('Manager', 'Manager_Name', 'left');
$table->AddField('Active', 'Is_Active', 'center');
$table->AddLink("contact_profile.php?cid=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"View\" border=\"0\">", "Contact_ID");
$table->SetMaxRows(25);
$table->SetOrderBy('Contact_ID');
$table->Finalise();
$table->DisplayTable();
echo '<br />';
$table->DisplayNavigation();

$page->Display('footer');
require_once('lib/common/app_footer.php');
?>