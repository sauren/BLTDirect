<?php
require_once('../../lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'services/google-checkout/classes/GoogleResponse.php');

error_fatal(0);

$cart = null;
$session = null;
$globalTaxCalculator = null;

## ENTER LOG
############

$googleXml = '';

##############
##### END

if (get_magic_quotes_gpc()) $googleXml = stripslashes($googleXml);

/*
	Process the XML as a new Google Response
*/

$googleResponse = new GoogleResponse();

if($googleResponse->ParseXml($googleXml)){
	## UNCOMMENT TO EXECUTE TEST
	//$googleResponse->Execute();
} else {
	$googleResponse->Error('Unable to Parse Google Response XML.');
}

$GLOBALS['DBCONNECTION']->Close();