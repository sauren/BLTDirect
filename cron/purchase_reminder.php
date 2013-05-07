<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'Purchase Reminder';
$fileName = 'purchase_reminder.php';

## BEGIN SCRIPT
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/EmailQueue.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/FindReplace.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Supplier.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Template.php');

$weeks = 1;

$suppliers = array();

$data = new DataQuery(sprintf('SELECT p.Purchase_ID, p.Supplier_ID, pl.Product_ID, pl.Description, pl.Quantity_Decremental, pl.Cost, pl.Quantity_Decremental*pl.Cost AS Total FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID AND pl.Quantity_Decremental>0 WHERE p.Purchase_Status IN (\'Unfulfilled\', \'Partially Fulfilled\') AND p.Created_On<ADDDATE(NOW(), INTERVAL -%d WEEK) ORDER BY p.Purchase_ID ASC, pl.Description ASC', mysql_real_escape_string($weeks)));
while($data->Row) {
	$key = $data->Row['Supplier_ID'];

	if(!isset($suppliers[$key])) {
		$suppliers[$key] = array();
	}

	$suppliers[$key][] = $data->Row;

	$data->Next();
}
$data->Disconnect();

foreach($suppliers as $supplierId=>$purchases) {
	$supplier = new Supplier();

	if($supplier->Get($supplierId)) {
		$supplier->Contact->Get();

		$purchaseHtml = '<table width="100%" cellspacing="0" cellpadding="5" class="order"><tr><th align="left">Purchase</th><th align="left">Product</th><th align="left">Quickfind</th><th align="right">Quantity</th><th align="right">Cost</th><th align="right">Total</th></tr>';

		foreach($purchases as $purchaseData) {
			$purchaseHtml .= sprintf('<tr><td>%d</td><td>%s</td><td>%d</td><td align="right">%d</td><td align="right">&pound;%s</td><td align="right">&pound;%s</td></tr>', $purchaseData['Purchase_ID'], $purchaseData['Description'], $purchaseData['Product_ID'], $purchaseData['Quantity_Decremental'], number_format(round($purchaseData['Cost'], 2), 2, '.', ','), number_format(round($purchaseData['Total'], 2), 2, '.', ','));

			$log[] = sprintf("Compiling Purchase: %d", $purchaseData['Purchase_ID']);
		}

		$purchaseHtml .= '</table><br />';

		$findReplace = new FindReplace();
		$findReplace->Add('/\[SUPPLIER_ID\]/', $supplier->Contact->ID);
		$findReplace->Add('/\[SUPPLIER_NAME\]/', $supplier->Contact->Person->GetFullName());
		$findReplace->Add('/\[SUPPLIER_EMAIL\]/', $supplier->Contact->Person->Email);

		$findReplace->Add('/\[PURCHASES\]/', $purchaseHtml);

		$template = $findReplace->Execute(Template::GetContent('email_purchase_reminder'));

		$findReplace = new FindReplace();
		$findReplace->Add('/\[BODY\]/', $template);
		$findReplace->Add('/\[NAME\]/', $supplier->Contact->Person->Name);
		$findReplace->Add('/\[SALES\]/', 'Wendy Ellwood<br />customerservices@bltdirect.com');

		$template = $findReplace->Execute(Template::GetContent('email_template_informal'));

		$queue = new EmailQueue();
		$queue->GetModuleID('purchases');
		$queue->Type = 'H';
		$queue->Priority = 'H';
		$queue->Subject = sprintf("%s - Purchase Orders Reminder", $GLOBALS['COMPANY']);
		$queue->Body = $template;
		$queue->ToAddress = $supplier->Contact->Person->Email;
		$queue->FromAddress = 'customerservices@bltdirect.com';
		$queue->Add();

		$log[] = sprintf("Emailing: %s, Purchases: %d", $supplier->Contact->Person->Email, count($purchases));
	}
}
## END SCRIPT

$logHeader[] = sprintf("Script: %s", $script);
$logHeader[] = sprintf("File Name: %s", $fileName);
$logHeader[] = sprintf("Date Executed: %s", date('Y-m-d H:i:s'));
$logHeader[] = sprintf("Execution Time: %s seconds", number_format(microtime(true) - $timing, 4, '.', ''));
$logHeader[] = '';

$log = array_merge($logHeader, $log);

if($mailLog) {
	$mail = new htmlMimeMail5();
	$mail->setFrom('root@bltdirect.com');
	$mail->setSubject(sprintf("Cron [%s] <root@bltdirect.com> php /var/www/vhosts/bltdirect.com/httpdocs/cron/%s", $script, $fileName));
	$mail->setText(implode("\n", $log));
	$mail->send(array('adam@azexis.com'));
}

echo implode("<br />", $log);

$GLOBALS['DBCONNECTION']->Close();