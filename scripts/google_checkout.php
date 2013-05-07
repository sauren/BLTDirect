<?php
ini_set('max_execution_time', '3000');
ini_set('display_errors','on');

error_fatal();

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'services/google-checkout/classes/GoogleResponse.php');

$cart = null;
$session = null;
$globalTaxCalculator = null;

$data = new DataQuery(sprintf("SELECT Log_ID, Log_Message, Created_On FROM log WHERE Owner LIKE 'GoogleCheckout' AND Created_On BETWEEN '2010-12-20 00:00:00' AND '2010-12-20 10:10:00' AND Type LIKE 'REQUEST' ORDER BY Log_ID ASC"));
while($data->Row) {
	$googleXml = $data->Row['Log_Message'];
	
	if (get_magic_quotes_gpc()) $googleXml = stripslashes($googleXml);

	$googleResponse = new GoogleResponse();

	if($googleResponse->ParseXml($googleXml)){
		switch ($googleResponse->_Root){
			case "new-order-notification":
			case "order-state-change-notification":
			case "charge-amount-notification":
			case "chargeback-amount-notification":
			case "refund-amount-notification":
			case "risk-information-notification":
				echo $data->Row['Log_ID'] . ' - ' . $googleResponse->_Root . '<br />';

				$googleResponse->Execute();
		
				break;
		}
	} else {
		$googleResponse->Error('Unable to Parse Google Response XML.');
	}
	
	$data->Next();
}
$data->Disconnect();