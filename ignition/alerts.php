<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Alert.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

if($action == 'open') {
	$session->Secure(2);
	open();
	exit;
} elseif($action == 'complete') {
	$session->Secure(3);
	complete();
	exit;
} elseif($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function open() {
	$filter = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : '';
	
	$alert = new Alert();
	
	if(isset($_REQUEST['alertid']) && $alert->get($_REQUEST['alertid'])) {
		switch(strtolower($alert->owner)) {
			case 'product':
				redirectTo('product_profile.php?pid=' . $alert->referenceId);
				break;
				
			case 'order':
				redirectTo('order_details.php?orderid=' . $alert->referenceId);
				break;
		}
	}
	
	redirectTo('?action=view&filter=' . $filter);
}

function remove() {
	$filter = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : '';
	
	$alert = new Alert();
	
	if(isset($_REQUEST['alertid'])) {
		$alert->delete($_REQUEST['alertid']);
	}
	
	redirectTo('?action=view&filter=' . $filter);
}

function complete() {
	$filter = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : '';
	
	$alert = new Alert();
	
	if(isset($_REQUEST['alertid']) && $alert->get($_REQUEST['alertid'])) {
		$alert->complete();
	}
	
	redirectTo('?action=view&filter=' . $filter);
}

function view() {
	$filter = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : '';
	
	$page = new Page('Alerts', 'View all alerts.');
	$page->Display('header');

	$table = new DataTable('records');
	
	if(strtolower($filter) == 'order') {
		$table->SetSQL(sprintf("SELECT a.*, p.Product_Title FROM alert AS a LEFT JOIN product AS p ON p.Product_ID=a.referenceId2 WHERE a.isComplete='N'%s", !empty($filter) ? sprintf(' AND a.owner LIKE \'%s\'', $filter) : ''));
	} else {
		$table->SetSQL(sprintf("SELECT * FROM alert WHERE isComplete='N'%s", !empty($filter) ? sprintf(' AND owner LIKE \'%s\'', $filter) : ''));
	}
	
	$table->AddField('ID', 'id', 'left');
	$table->AddField('Type', 'type', 'left');
	$table->AddField('Description', 'description', 'left');
	
	if(strtolower($filter) == 'order') {
		$table->AddField('Product', 'Product_Title', 'left');	
	}
	
	if(empty($filter)) {
		$table->AddField('Owner', 'owner', 'left');
	}
	
	$table->AddLink(sprintf("?action=open&alertid=%%s&filter=%s", $filter), '<img src="images/folderopen.gif" alt="Open" border="0" />', 'id');
	$table->AddLink(sprintf("javascript:confirmRequest('?action=complete&alertid=%%s&filter=%s', 'Are you sure you want to complete this item?');", $filter), '<img src="images/button-tick.gif" alt="Complete" border="0" />', 'id');
	$table->AddLink(sprintf("javascript:confirmRequest('?action=remove&alertid=%%s&filter=%s', 'Are you sure you want to remove this item?');", $filter), '<img src="images/button-cross.gif" alt="Remove" border="0" />', 'id');
	$table->SetMaxRows(25);
	$table->SetOrderBy("id");
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}