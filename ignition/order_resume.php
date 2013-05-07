<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');

if($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'resume') {
	$session->Secure(3);
	resume();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	if(isset($_REQUEST['id'])) {
		new DataQuery(sprintf("DELETE FROM customer_basket WHERE Basket_ID=%d", mysql_real_escape_string($_REQUEST['id'])));
		new DataQuery(sprintf("DELETE FROM customer_basket_line WHERE Basket_ID=%d", mysql_real_escape_string($_REQUEST['id'])));
		new DataQuery(sprintf("DELETE FROM customer_basket_shipping WHERE CustomerBasketID=%d", mysql_real_escape_string($_REQUEST['id'])));
	}

	redirect('Location: ?action=view');
}

function resume() {
	if(isset($_REQUEST['id'])) {
		$data = new DataQuery(sprintf("SELECT CSID FROM customer_basket WHERE Basket_ID=%d", mysql_real_escape_string($_REQUEST['id'])));
		if($data->TotalRows > 0) {
			session_destroy();
			session_id($data->Row['CSID']);
			session_start();

			redirect('Location: order_cart.php');
		}
		$data->Disconnect();
	}

	redirect('Location: ?action=view');
}

function view() {
	global $session;

	$page = new Page('Resume Order', 'Listing all customer shopping carts available for resuming.');
	$page->Display('header');

	$table = new DataTable('records');
	$table->SetSQL(sprintf("SELECT cb.Basket_ID, cb.CSID, cb.Created_On, CONCAT_WS(' ', p1.Name_First, p1.Name_Last) AS Customer, CONCAT_WS(' ', p2.Name_First, p2.Name_Last) AS User FROM customer_basket AS cb INNER JOIN customer_basket_line AS cbl ON cbl.Basket_ID=cb.Basket_ID INNER JOIN sessions AS s ON cb.CSID=s.Session_ID INNER JOIN customer AS c ON c.Customer_ID=cb.Customer_ID INNER JOIN contact AS co ON co.Contact_ID=c.Contact_ID INNER JOIN person AS p1 ON p1.Person_ID=co.Person_ID INNER JOIN users AS u ON u.User_ID=s.User_ID INNER JOIN person AS p2 ON p2.Person_ID=u.Person_ID WHERE cb.Customer_ID>0"));
	$table->AddBackgroundCondition('CSID', $session->ID, '==', '#FEFDB2', '#FEFC6B');
	$table->AddField('', 'CSID', 'hidden');
	$table->AddField('Basket ID', 'Basket_ID', 'left');
	$table->AddField('Created Date', 'Created_On', 'left');
	$table->AddField('Customer', 'Customer', 'left');
	$table->AddField('User', 'User', 'left');
	$table->AddLink('?action=resume&id=%s', '<img src="images/folderopen.gif" alt="Resume" border="0" />', 'Basket_ID');
	$table->AddLink('javascript:confirmRequest(\'?action=remove&id=%s\', \'Are you sure you want to remove this item?\');', '<img src="images/aztector_6.gif" alt="Remove" border="0" />', 'Basket_ID');
	$table->SetMaxRows(25);
	$table->SetOrderBy('Basket_ID');
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}