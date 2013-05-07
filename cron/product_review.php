<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Cron.php');

$cron = new Cron();
$cron->scriptName = 'Product Review Request';
$cron->scriptFileName = 'product_review.php';
$cron->mailLogLevel = Cron::LOG_LEVEL_WARNING;

## BEGIN SCRIPT
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Customer.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/EmailQueue.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/FindReplace.php');

new DataQuery("SET @start = DATE(ADDDATE(NOW(), INTERVAL -1 MONTH))");
new DataQuery("SET @end = DATE(ADDDATE(ADDDATE(NOW(), INTERVAL -1 MONTH), INTERVAL 1 DAY))");

$data = new DataQuery(sprintf("SELECT Order_ID, Customer_ID FROM orders WHERE Order_Prefix IN ('W', 'U', 'L', 'M') AND Status LIKE 'Despatched' AND Created_On>=@start AND Created_On<@end"));
while($data->Row) {
	$products = array();
	
	$data2 = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title FROM order_line AS ol INNER JOIN product AS p ON p.Product_ID=ol.Product_ID WHERE ol.Order_ID=%d AND ol.Despatch_ID>0 AND p.Is_Automatic_Review='Y' GROUP BY p.Product_ID", $data->Row['Order_ID']));
	while($data2->Row) {
		$products[$data2->Row['Product_ID']] = strip_tags($data2->Row['Product_Title']); 
		
		$data2->Next();	
	}
	$data2->Disconnect();
	
	if(count($products) > 0) {
		$customer = new Customer($data->Row['Customer_ID']);
		$customer->Contact->Get();
		
		$productsHtml = '<table width="100%" cellspacing="0" cellpadding="5" class="order"><tr><th align="left" style="border-bottom:1px solid #FA8F00;">Quickfind</th><th align="left" style="border-bottom:1px solid #FA8F00;">Product</th></tr>';
		
		foreach($products as $productId=>$productTitle) {
			$productsHtml .= sprintf('<tr><td>%d</td><td><a href="%sproductReview.php?pid=%d&tab=reviews">%s</a></td></tr>', $productId, $GLOBALS['HTTP_SERVER'], $productId, $productTitle);
		}
		
		$productsHtml .= '</table>';
		
		$findReplace = new FindReplace();
		$findReplace->Add('/\[PRODUCTS\]/', $productsHtml);

		$customTemplate = file($GLOBALS["DIR_WS_ADMIN"].'lib/templates/email/product_review.tpl');
		$customHtml = '';
		
		for($i=0; $i < count($customTemplate); $i++){
			$customHtml .= $findReplace->Execute($customTemplate[$i]);
		}

		$findReplace =  new FindReplace();
		$findReplace->Add('/\[BODY\]/', $customHtml);
		$findReplace->Add('/\[NAME\]/', $customer->Contact->Person->GetFullName());

		$standardTemplate = file($GLOBALS["DIR_WS_ADMIN"].'lib/templates/email/template_standard.tpl');
		$standardHtml = '';
		
		for($i=0; $i<count($standardTemplate); $i++){
			$standardHtml .= $findReplace->Execute($standardTemplate[$i]);
		}
		
		$queue = new EmailQueue();
		$queue->GetModuleID('products');
		$queue->Subject = sprintf("%s - Product Review Request", $GLOBALS['COMPANY']);
		$queue->Body = $standardHtml;
		$queue->ToAddress = $customer->Contact->Person->Email;
		$queue->Priority = 'H';
		$queue->Type = 'H';
		$queue->Add();
		
		$cron->log(sprintf('Emailing review request: %s.', $customer->Contact->Person->Email), Cron::LOG_LEVEL_INFO);
	}
		
	$data->Next();
}
$data->Disconnect();
## END SCRIPT

$cron->execute();
$cron->output();

$GLOBALS['DBCONNECTION']->Close();