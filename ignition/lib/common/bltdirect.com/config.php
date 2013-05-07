<?php
define('DEFAULT_TIMEZONE_IDENTIFIER', 'Europe/London');

date_default_timezone_set(DEFAULT_TIMEZONE_IDENTIFIER);

$GLOBALS['HTTP_SERVER'] = 'http://www.bltdirect.com/';
$GLOBALS['HTTPS_SERVER'] = 'https://www.bltdirect.com/';

$GLOBALS['ANALYTICS_TRACKING'] = 'UA-1618935-2';
$GLOBALS['ANALYTICS_DOMAIN'] = 'bltdirect.com';
$GLOBALS['COOKIE_DOMAIN'] = '.bltdirect.com';
$GLOBALS['USE_SSL'] = true;
$GLOBALS['SSL_PORT'] = 443;
$GLOBALS['VERSION'] = '1.0';
$GLOBALS['IGNITION_ROOT'] = (!$GLOBALS['USE_SSL'])?$GLOBALS['HTTP_SERVER']:$GLOBALS['HTTPS_SERVER'];
$GLOBALS['IGNITION_ROOT'] .= 'ignition/';
$GLOBALS['MAGIC_QUOTES'] = get_magic_quotes_gpc();


$GLOBALS['AZEXIS_SECURE_IPS'] = array(
	"217.41.66.241",
	"81.136.138.157"
);

if (
	$_SERVER['SERVER_PORT'] == $GLOBALS['SSL_PORT']
	&& in_array($_SERVER['REMOTE_ADDR'], $GLOBALS["AZEXIS_SECURE_IPS"])
	&& (isset($_COOKIE['iwantcandy']) && $_COOKIE['iwantcandy'] === 'true')
)
{
	define("DEVELOPER", true);
} else {
	define("DEVELOPER", false);
}


// WS = Define virtual server variables
$GLOBALS['DIR_WS_ROOT'] = '/var/www/vhosts/bltdirect.com/httpdocs/';
$GLOBALS["DIR_WS_ADMIN"] = $GLOBALS['DIR_WS_ROOT'] . 'ignition/';
$GLOBALS['DIR_WS_CACHE'] = $GLOBALS['DIR_WS_ROOT'] . 'cache/';

// FS = Define physical file server variables
$GLOBALS['DIR_FS_DOC_ROOT'] = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '';
$GLOBALS['DIR_FS_BACKUPS'] = '';

// Define database connection variables
//$GLOBALS['DB_TITLE'] = 'BLT Direct';
//$GLOBALS['DB_HOST'] = '127.0.0.1';
//$GLOBALS['DB_NAME'] = 'ell05';
//$GLOBALS['DB_USERNAME'] = 'bltdirect';
//$GLOBALS['DB_PASSWORD'] = '8Rat6Sac';
//$GLOBALS['DB_DOMAIN'][0] = 'bltdirect.com';
$GLOBALS['DB_TITLE'] = 'BLT Direct';
$GLOBALS['DB_HOST'] = '127.0.0.1';
$GLOBALS['DB_NAME'] = 'testbltdirect';
$GLOBALS['DB_USERNAME'] = 'root';
$GLOBALS['DB_PASSWORD'] = '';
$GLOBALS['DB_DOMAIN'][0] = 'localhost/steve/';

/*
	todo: Is syncing to Lightbulbs UK really relevant anymore?
*/
	
/*
$GLOBALS['SYNC_DB_TITLE'][0] = 'Light Bulbs UK';
$GLOBALS['SYNC_DB_HOST'][0] = '217.174.255.15';
$GLOBALS['SYNC_DB_NAME'][0] = 'lightbulbsuk';
$GLOBALS['SYNC_DB_USERNAME'][0] = 'lightbulbsuk';
$GLOBALS['SYNC_DB_PASSWORD'][0] = 'y4WAtuTe';
$GLOBALS['SYNC_DB_DOMAIN'][0] = 'lightbulbsuk.co.uk';
*/

$GLOBALS['JL_DB_TITLE'] = 'Just Lamps Partners';
$GLOBALS['JL_DB_HOST'] = 'jlpartner.justlamps.net';
$GLOBALS['JL_DB_NAME'] = 'jl_partners';
$GLOBALS['JL_DB_USERNAME'] = 'bltdirect';
$GLOBALS['JL_DB_PASSWORD'] = 'owx04k2';

$GLOBALS['ELLWOOD_DB_TITLE'] = 'Ellwood Electrical';
$GLOBALS['ELLWOOD_DB_HOST'] = '127.0.0.1';
$GLOBALS['ELLWOOD_DB_NAME'] = 'ellwood_2';
$GLOBALS['ELLWOOD_DB_USERNAME'] = 'contracting';
$GLOBALS['ELLWOOD_DB_PASSWORD'] = 'T5aHu5eR';

// Email Defaults
$GLOBALS['EMAIL_LIVE'] = true;

$GLOBALS['CACHE_BACKEND'] = 'Memcached';
