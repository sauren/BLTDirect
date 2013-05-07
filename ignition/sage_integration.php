<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IntegrationSage.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Template.php');

if($action == 'logs') {
	$session->Secure(2);
	logs();
	exit;
} elseif($action == 'print') {
	$session->Secure(2);
	printLogs();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function printLogs() {
	$integration = new IntegrationSage();
	
	if(!isset($_REQUEST['id']) || !$integration->Get($_REQUEST['id'])) {
		echo '<script language="javascript" type="text/javascript">window.close();</script>';
	}
	
	$html = '<table width="100%" cellspacing="0" cellpadding="5" class="order"><tr><th align="left" style="border-bottom: 1px solid #000;">ID</th><th align="left" style="border-bottom: 1px solid #000;">Type</th><th align="left" style="border-bottom: 1px solid #000;">Account Reference</th><th align="left" style="border-bottom: 1px solid #000;">Contact Name</th><th align="right" style="border-bottom: 1px solid #000;">Amount</th></tr>';
	
	$data = new DataQuery(sprintf("SELECT * FROM integration_sage_log WHERE integrationSageId=%d", mysql_real_escape_string($integration->ID)));
	while($data->Row) {
		$html .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td align="right">&pound;%s</td></tr>', $data->Row['id'], $data->Row['type'], $data->Row['accountReference'], $data->Row['contactName'], $data->Row['amount']);
		
		$data->Next();	
	}
	$data->Disconnect();
	
	$html .= '</table>';
	
	$findReplace = new FindReplace();
	$findReplace->Add('/\[INTEGRATION_DATE\]/', cDatetime($integration->CreatedOn, 'shortdate'));
	$findReplace->Add('/\[INTEGRATION_LOGS\]/', $html);

	echo $findReplace->Execute(Template::GetContent('print_integration_sage_logs'));
}

function logs() {
	$integration = new IntegrationSage();
	
	if(!isset($_REQUEST['id']) || !$integration->Get($_REQUEST['id'])) {
		redirectTo('?action=view');
	}
	
	$print = array();
	
	$data = new DataQuery(sprintf("SELECT * FROM integration_sage_log WHERE integrationSageId=%d", mysql_real_escape_string($integration->ID)));
	while($data->Row) {
		switch($data->Row['type']) {
			case 'Credit':
				$print[sprintf('ordercredit-%d', $data->Row['referenceId'])] = $data->Row['referenceId'];
				break;
				
			case 'Invoice':
				$print[sprintf('orderinvoice-%d', $data->Row['referenceId'])] = $data->Row['referenceId'];
				break;	
		}
		
		$data->Next();	
	}
	$data->Disconnect();
	
	$md5 = md5(serialize($print));
	
	$_SESSION['SageExport'][$md5] = $print;
	
	$page = new Page('<a href="?action=view">Sage Integration</a> &gt; Logs', 'Logs for this sage integration.');
	$page->Display('header');

	$table = new DataTable('integrations');
	$table->SetExtractVars();
	$table->SetSQL(sprintf("SELECT * FROM integration_sage_log WHERE integrationSageId=%d", mysql_real_escape_string($integration->ID)));
	$table->AddField("ID", "id", "left");
	$table->AddField("Type", "type", "left");
	$table->AddField("Account Reference", "accountReference", "left");
	$table->AddField("Contact Name", "contactName", "left");
	$table->AddField("Amount", "amount", "right");
	$table->SetMaxRows(25);
	$table->SetOrderBy("id");
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
	
	echo '<br />';
	echo sprintf('<input type="button" name="print" value="print logs" class="btn" onclick="popUrl(\'?action=print&id=%d\', 800, 600);" />', $integration->ID);
	echo sprintf('<input type="button" name="print" value="print invoices" class="btn" onclick="popUrl(\'sage_export_print.php?documents=%s\', 800, 600);" />', $md5);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	$page = new Page('Sage Integration', 'Below is a list of recent integrations.');
	$page->Display('header');

	$table = new DataTable('integrations');
	$table->SetSQL("SELECT i.*, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Name, COUNT(isl.id) AS Logs FROM integration_sage AS i LEFT JOIN users AS u ON u.User_ID=i.Created_By LEFT JOIN person AS p ON p.Person_ID=u.Person_ID LEFT JOIN integration_sage_log AS isl ON isl.integrationSageId=i.Integration_Sage_ID GROUP BY i.Integration_Sage_ID");
	$table->AddField("", "Logs", "hidden");
	$table->AddField("ID", "Integration_Sage_ID", "left");
	$table->AddField("Created Date", "Created_On", "left");
	$table->AddField("Created By", "Name", "left");
	$table->AddField("Type", "Type", "left");
	$table->AddLink("?action=logs&id=%s", "<img src=\"images/folderopen.gif\" alt=\"Logs\" border=\"0\">", "Integration_Sage_ID", true, false, array('Logs', '>', 0));
	$table->SetMaxRows(25);
	$table->SetOrderBy("Created_On");
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}