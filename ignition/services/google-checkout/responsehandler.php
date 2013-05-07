<?php
require_once('../../lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/GoogleCheckout.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'services/google-checkout/classes/GoogleResponse.php');

error_fatal(0);

$cart = null;
$session = null;
$globalTaxCalculator = null;

$googleXml = isset($HTTP_RAW_POST_DATA)?$HTTP_RAW_POST_DATA:file_get_contents("php://input");
if (get_magic_quotes_gpc()) $googleXml = stripslashes($googleXml);

$ignore = false;

$googleResponse = new GoogleResponse();
$googleResponse->Logging = false;

if($googleResponse->ParseXml($googleXml)){
	if($googleResponse->StartSession()){
		if($googleResponse->_Root == "merchant-calculation-callback") {
			$ignore = true;
			$googleResponse->onMerchantCalculationCallback();
		}
	}
} else {
	$googleResponse->Error('Unable to Parse Google Response XML.');
}

if(!$ignore) {
	$checkout = new GoogleCheckout();
	$checkout->Data = $googleXml;
	$checkout->Add();
}

$GLOBALS['DBCONNECTION']->Close();