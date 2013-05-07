<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderContact.php');

if($action == 'delete') {
	$session->Secure(3);
	delete();
	exit;
} else if($action == 'contact') {
	$session->Secure(3);
	contact();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function view() {
	$page = new Page('Abandoned Cart', 'Order Count represents a number of orders, which the customer has made on the same day as abandoning this cart.');
	$page->Display('header');
	
	$table = new DataTable('customer_basket');
	/*$table->SetSQL("SELECT cb.*, p.Person_ID, p.Name_First, p.Name_Last, p.Phone_1, p.Email From customer_basket AS cb
LEFT JOIN orders AS o ON o.Customer_ID=cb.Customer_ID AND DATE(o.Created_On)=DATE(cb.Created_On)
LEFT JOIN customer AS c ON c.Customer_ID = cb.Customer_ID
LEFT JOIN contact AS con ON con.Contact_ID = c.Contact_ID
LEFT JOIN person AS p ON p.Person_ID = con.Person_ID
WHERE cb.Prefix='W' AND cb.Customer_ID!=0
AND o.Order_ID is NULL AND cb.Created_On <= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");*/

	$table->SetSQL("SELECT o2.orderCount, cbl.lineCount, o.Order_ID, cb.*, p.Person_ID, p.Name_First, p.Name_Last, p.Phone_1, p.Email  from customer_basket as cb
left join orders as o on cb.Basket_ID=o.Basket_ID and o.Customer_ID=cb.Customer_ID AND DATE(o.Created_On)=DATE(cb.Created_On)
LEFT JOIN (
	select count(*) as orderCount, Customer_ID, Created_On
	from orders
	where Created_On > DATE_SUB(NOW(), INTERVAL 3 DAY)
	group by Customer_ID, DATE(Created_On)
) as o2 ON o2.Customer_ID=cb.Customer_ID AND DATE(o2.Created_On)=DATE(cb.Created_On)
LEFT JOIN (
	select count(*) as lineCount, Basket_ID from customer_basket_line
	group by Basket_ID
) as cbl on cbl.Basket_ID=cb.Basket_ID
LEFT JOIN customer AS c ON c.Customer_ID = cb.Customer_ID
LEFT JOIN contact AS con ON con.Contact_ID = c.Contact_ID
LEFT JOIN person AS p ON p.Person_ID = con.Person_ID
where cb.Prefix='W' and cb.Customer_ID!=0 and
cb.Created_On <= DATE_SUB(NOW(), INTERVAL 1 MINUTE) and o.Order_ID is null and cbl.lineCount>0");
	$table->AddField('Basket Number', 'Basket_ID', 'left');
	$table->AddField('Name', 'Name_First', 'left');
	$table->AddField('Surname', 'Name_Last', 'left');
	$table->AddField('Email', 'Email', 'left');
	$table->AddField('Order Count', 'orderCount', 'Centre');
	$table->AddField('Creation Date', 'Created_On', 'left');	
	$table->AddField('Last Contacted', 'Contacted_On', 'left');	
	$table->AddLink("cart_abandoned.php?action=contact&cartid=%s", "<img src=\"images/icon_clock_1.gif\" alt=\"Contact Made\" border=\"0\" />", "Basket_ID");
	$table->AddLink("cart_details.php?cartid=%s", "<img src=\"images/folderopen.gif\" alt=\"Open Abandoned Cart Details\" border=\"0\" />", "Basket_ID");
	$table->AddLink("cart_abandoned.php?action=delete&cartid=%s", "<img src=\"images/aztector_6.gif\" alt=\"Delete Cart\" border=\"0\" />", "Basket_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Created_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function contact() {
	if(isset($_REQUEST['cartid']) && !empty($_REQUEST['cartid'])) {
		$cid = $_REQUEST['cartid'];
		$cart = new Cart($cid);
		$cart->getByID($cid);
		$cart->ContactMade();
	}

	redirect('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

function delete() {	
	if(isset($_REQUEST['cartid']) && !empty($_REQUEST['cartid'])) {
		$cid = $_REQUEST['cartid'];
		$cart = new Cart($cid);
		$cart->getByID($cid);
		$cart->Delete();
	}
	redirect('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}