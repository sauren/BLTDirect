<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

$session->Secure(2);

$contact = new Contact($_REQUEST['ocid']);

$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s</a> &gt; Order Purchase History for %s contacts', $contact->ID, $contact->Organisation->Name, $contact->Organisation->Name), sprintf('Below is the order purchase history for all contacts of %s.', $contact->Organisation->Name));
$page->Display('header');

$table = new DataTable("purchases");
$table->SetSQL(sprintf("SELECT od.*, CONCAT(o.Order_ID, o.Order_Prefix) AS orderReference, CONCAT_WS(' ', p2.Name_First, p2.Name_Last) AS name FROM order_document AS od INNER JOIN orders AS o ON o.Order_ID=od.orderId INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN person AS p2 ON p2.Person_ID=n.Person_ID WHERE n.Parent_Contact_ID=%d", $contact->ID));
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
echo "<br>";

$page->Display('footer');
require_once('lib/common/app_footer.php');