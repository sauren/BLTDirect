<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
report();
exit();

function report(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$page = new Page('Customer Products Report', '');
	$page->Display('header');

	$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT CustomerID) AS Count FROM customer_location"));
	echo '<strong>Number of customers who have selected location(s): ' . $data->Row['Count'] . '</strong><br />';
	$data->Disconnect();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}