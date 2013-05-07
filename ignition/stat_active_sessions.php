<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

$sessions = array();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'filter', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('start', 'Start', 'hidden', '0000-00-00 00:00:00', 'anything', 19, 19);
$form->AddField('end', 'End', 'hidden', '0000-00-00 00:00:00', 'anything', 19, 19);
$form->AddField('customer', 'Customer', 'hidden', '0', 'numeric_unsigned', 1, 11);
$form->AddField('bots', 'Bots', 'hidden', '', 'alpha', 0, 1);
$form->AddField('agent', 'User Agent', 'hidden', '0', 'numeric_unsigned', 1, 11);
$form->AddField('order', 'Sort Order', 'hidden', 'Page_Requests', 'anything', 0, 255);

$page = new Page('Sessions', sprintf('Statistics for sessions.'));
$page->Display('header');

$sqlFrom = sprintf("FROM customer_session AS cs LEFT JOIN customer_session_item AS csi ON csi.Session_ID=cs.Session_ID ");
$sqlWhere = sprintf("WHERE 0=0 ");

if(($form->GetValue('start') != '0000-00-00 00:00:00') && ($form->GetValue('end') != '0000-00-00 00:00:00')) {
	$sqlWhere .= sprintf(" AND cs.Created_On>='%s' AND cs.Created_On<'%s' ", mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end')));
} elseif($form->GetValue('start') != '0000-00-00 00:00:00') {
	$sqlWhere .= sprintf(" AND cs.Created_On>='%s' ", mysql_real_escape_string($form->GetValue('start')));
} elseif($form->GetValue('end') != '0000-00-00 00:00:00') {
	$sqlWhere .= sprintf(" AND cs.Created_On<'%s' ", mysql_real_escape_string($form->GetValue('end')));
}

if($form->GetValue('agent') > 0) {
	$sqlWhere .= sprintf("AND cs.User_Agent_ID=%d ", mysql_real_escape_string($form->GetValue('agent')));
}

if($form->GetValue('customer') > 0) {
	$sqlFrom .= sprintf("INNER JOIN customer_session_item AS csi2 ON csi2.Session_ID=csi.Session_ID AND csi2.Customer_ID=%d ", mysql_real_escape_string($form->GetValue('customer')));
}

if($form->GetValue('bots') == 'Y') {
	$sqlFrom .= sprintf("INNER JOIN user_agent AS ua ON ua.User_Agent_ID=cs.User_Agent_ID AND ua.Is_Bot='%s' ", mysql_real_escape_string($form->GetValue('bots')));
} elseif($form->GetValue('bots') == 'N') {
	$sqlFrom .= sprintf("LEFT JOIN user_agent AS ua ON ua.User_Agent_ID=cs.User_Agent_ID ");
	$sqlWhere .= sprintf("AND (cs.User_Agent_ID=0 OR (cs.User_Agent_ID>0 AND ua.Is_Bot='%s')) ", mysql_real_escape_string($form->GetValue('bots')));
}

$data = new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_session SELECT COUNT(DISTINCT csi.Session_Item_ID) AS Page_Requests, cs.Session_ID, cs.Created_On, cs.User_Agent_ID %s %s GROUP BY cs.Session_ID", $sqlFrom, $sqlWhere));
$data->Disconnect();

$table = new DataTable('sessions');
$table->SetSQL(sprintf("SELECT ts.Session_ID, ts.Created_On, ts.Page_Requests, ua.String AS User_Agent, ua.Is_Bot FROM temp_session AS ts LEFT JOIN user_agent AS ua ON ua.User_Agent_ID=ts.User_Agent_ID"));
$table->SetTotalRowSQL(sprintf("SELECT COUNT(*) AS TotalRows FROM temp_session"));
$table->AddBackgroundCondition('Is_Bot', 'Y', '==', '#FFF499', '#EEE177');
$table->AddField('Is Bot', 'Is_Bot', 'hidden');
$table->AddField("Session #", "Session_ID");
$table->AddField("Session Date", "Created_On");
$table->AddField("Page Requests", "Page_Requests");
$table->AddField("User Agent", "User_Agent");
$table->AddLink("stat_session_details.php?id=%s", '<img src="images/icon_search_1.gif" alt="View Session Details" border="0" / >', 'Session_ID', true, true);
$table->SetMaxRows(25);
$table->SetOrderBy($form->GetValue('order'));
$table->Order = 'DESC';
$table->Finalise();
$table->DisplayTable();
echo '<br />';
$table->DisplayNavigation();

require_once('lib/common/app_footer.php');
?>