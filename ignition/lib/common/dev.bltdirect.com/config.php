<?php
define('DEFAULT_TIMEZONE_IDENTIFIER', 'Europe/London');

date_default_timezone_set(DEFAULT_TIMEZONE_IDENTIFIER);

$GLOBALS['HTTP_SERVER'] = 'http://dev.bltdirect.com/';
$GLOBALS['HTTPS_SERVER'] = 'https://dev.bltdirect.com/';

$GLOBALS['ANALYTICS_TRACKING'] = 'UA-36892197-1';
$GLOBALS['ANALYTICS_DOMAIN'] = 'test.bltdirect.com';
$GLOBALS['COOKIE_DOMAIN'] = '.dev.bltdirect.com';
$GLOBALS['USE_SSL'] = false;
$GLOBALS['SSL_PORT'] = 443;
$GLOBALS['VERSION'] = '1.0';
$GLOBALS['IGNITION_ROOT'] = (!$GLOBALS['USE_SSL'])?$GLOBALS['HTTP_SERVER']:$GLOBALS['HTTPS_SERVER'];
$GLOBALS['IGNITION_ROOT'] .= 'ignition/';
$GLOBALS['MAGIC_QUOTES'] = get_magic_quotes_gpc();


define("DEVELOPER", true);


// WS = Define virtual server variables

$GLOBALS['DIR_WS_ROOT'] = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . "/";

$GLOBALS["DIR_WS_ADMIN"] = $GLOBALS['DIR_WS_ROOT'] . 'ignition/';
$GLOBALS['DIR_WS_CACHE'] = $GLOBALS['DIR_WS_ROOT'] . 'cache/';

// FS = Define physical file server variables
$GLOBALS['DIR_FS_DOC_ROOT'] = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '';
$GLOBALS['DIR_FS_BACKUPS'] = '';

// Define database connection variables
//$GLOBALS['DB_TITLE'] = 'BLT Direct';
//$GLOBALS['DB_HOST'] = 'mr-clever';
//$GLOBALS['DB_NAME'] = 'ell05';
//$GLOBALS['DB_USERNAME'] = 'deveus';
//$GLOBALS['DB_PASSWORD'] = '25097001';
//$GLOBALS['DB_DOMAIN'][0] = 'dev.bltdirect.com';
$GLOBALS['DB_TITLE'] = 'BLT Direct';
$GLOBALS['DB_HOST'] = '127.0.0.1';
$GLOBALS['DB_NAME'] = 'testbltdirect';
$GLOBALS['DB_USERNAME'] = 'root';
$GLOBALS['DB_PASSWORD'] = '';
$GLOBALS['DB_DOMAIN'][0] = 'localhost/steve';
/*
$GLOBALS['SYNC_DB_TITLE'][0] = 'Light Bulbs UK';
$GLOBALS['SYNC_DB_HOST'][0] = '127.0.0.1';
$GLOBALS['SYNC_DB_NAME'][0] = 'lightbulbsuk';
$GLOBALS['SYNC_DB_USERNAME'][0] = 'lightbulbs';
$GLOBALS['SYNC_DB_PASSWORD'][0] = 'prU2Ruda';
$GLOBALS['SYNC_DB_DOMAIN'][0] = 'lightbulbsuk.co.uk';
*/
$GLOBALS['JL_DB_TITLE'] = 'Just Lamps Partners';
$GLOBALS['JL_DB_HOST'] = 'jlpartner.justlamps.net';
$GLOBALS['JL_DB_NAME'] = 'jl_partners';
$GLOBALS['JL_DB_USERNAME'] = 'bltdirect';
$GLOBALS['JL_DB_PASSWORD'] = 'owx04k2';

$GLOBALS['ELLWOOD_DB_TITLE'] = 'Ellwood Electrical';
$GLOBALS['ELLWOOD_DB_HOST'] = '127.0.0.1';
$GLOBALS['ELLWOOD_DB_NAME'] = 'contracting_test';
$GLOBALS['ELLWOOD_DB_USERNAME'] = 'testcontracting';
$GLOBALS['ELLWOOD_DB_PASSWORD'] = 'Zet5efr6';

// Email Defaults
$GLOBALS['EMAIL_LIVE'] = false;

$GLOBALS['CACHE_BACKEND'] = 'File';
