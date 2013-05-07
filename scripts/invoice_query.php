<?php
ini_set('max_execution_time', '3000');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$data = new DataQuery(sprintf("SELECT SupplierInvoiceQueryID, Description FROM supplier_invoice_query ORDER BY Description ASC"));
while($data->Row) {
	if(preg_match('/ from Invoice \'([0-9]*)\' on ([0-9\/]+)\./', $data->Row['Description'], $matches)) {
		echo $data->Row['Description'] . '<br />';
		echo str_replace($matches[0], '.', $data->Row['Description']) . '<br />';
		echo '<br />';
		
		new DataQuery(sprintf("UPDATE supplier_invoice_query SET InvoiceReference='%s', InvoiceDate='%s', Description='%s' WHERE SupplierInvoiceQueryID=%d", $matches[1], sprintf('%s-%s-%s 00:00:00', substr($matches[2], 6, 4), substr($matches[2], 3, 2), substr($matches[2], 0, 2)), mysql_real_escape_string(str_replace($matches[0], '.', $data->Row['Description'])), $data->Row['SupplierInvoiceQueryID']));
		
	} elseif(preg_match('/ [(]Invoice \'([0-9]*)\' on ([0-9\/]+)[)]\./', $data->Row['Description'], $matches)) {
		echo $data->Row['Description'] . '<br />';
		echo str_replace($matches[0], '.', $data->Row['Description']) . '<br />';
		echo '<br />';
		
		new DataQuery(sprintf("UPDATE supplier_invoice_query SET InvoiceReference='%s', InvoiceDate='%s', Description='%s' WHERE SupplierInvoiceQueryID=%d", $matches[1], sprintf('%s-%s-%s 00:00:00', substr($matches[2], 6, 4), substr($matches[2], 3, 2), substr($matches[2], 0, 2)), mysql_real_escape_string(str_replace($matches[0], '.', $data->Row['Description'])), $data->Row['SupplierInvoiceQueryID']));
	}

	$data->Next();
}
$data->Disconnect();