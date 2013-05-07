<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$session->Secure(3);

$order = new Order($_REQUEST['oid']);

$page = new Page('Order Credit Notes','You have issued the following Credit Notes for this order.');
$page->Display('header');

echo sprintf('<p><a href="order_details.php?orderid=%d">Back to Order Details</a></p>', $order->ID);

$table = new DataTable('notes');
$table->SetSQL(sprintf("select * from credit_note where Order_ID=%d", $order->ID));
$table->AddField('ID#', 'Credit_Note_ID', 'right');
$table->AddField('Type', 'Credit_Type', 'left');
$table->AddField('Status', 'Credit_Status', 'left');
$table->AddField('Total', 'Total', 'right');
$table->AddField('Issue Date', 'Credited_On', 'right');
$table->AddLink("credit_note.php?cnid=%s", "<img src=\"./images/folderopen.gif\" alt=\"Update Settings\" border=\"0\">", "Credit_Note_ID");
$table->SetMaxRows(25);
$table->SetOrderBy("Credit_Note_ID");
$table->Order= "desc";
$table->Finalise();
$table->DisplayTable();
echo '<br />';
$table->DisplayNavigation();

$page->Display('footer');
require_once('lib/common/app_footer.php');