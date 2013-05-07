<?php

define('SYSTEM_DIR', str_replace('/classes', '/', str_replace('\\', '/', dirname(__FILE__))));

set_include_path(get_include_path() . PATH_SEPARATOR . dirname(dirname(dirname(__FILE__))) . '/packages');

require_once(SYSTEM_DIR . 'common/config.php');
require_once(SYSTEM_DIR . 'common/generic.php');
require_once(SYSTEM_DIR . 'classes/Application.php');
require_once(SYSTEM_DIR . 'classes/CacheFile.php');
require_once(SYSTEM_DIR . 'classes/DataQuery.php');
require_once(SYSTEM_DIR . 'classes/BlacklistIPAddress.php');
require_once(SYSTEM_DIR . 'classes/BlacklistUserAgent.php');
require_once(SYSTEM_DIR . 'classes/Channel.php');
require_once(SYSTEM_DIR . 'classes/MySQLConnection.php');
require_once(SYSTEM_DIR . 'classes/Website.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/common/error_handler.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'packages/Zend/Cache.php');

Application::start();

ini_set('max_execution_time', (ini_get('max_execution_time') <= 60) ? 60 : ini_get('max_execution_time'));

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

if(isset($_SERVER['SERVER_NAME'])) {
	$blacklistIPAddress = new BlacklistIPAddress();
	$blacklistIPAddress->Validate();

	$blacklistUserAgent = new BlacklistUserAgent();
	$blacklistUserAgent->Validate();

	$channel = new Channel();
//
//	if(!$channel->GetByDomain($_SERVER['SERVER_NAME'])) {
//		echo sprintf('<strong>Application Exception</strong><br /> The location \'%s\' is not controlled by this system.', $_SERVER['SERVER_NAME']);
//		exit;
//	//} elseif(!stristr($channel->Domain, $_SERVER['SERVER_NAME'])) {
//		//redirect(sprintf("Location: http://%s", $channel->Domain));
//	}
}

define('CHANNEL_ID', isset($_SERVER['SERVER_NAME']) ? $channel->ID : 1);

unset($channel);
unset($blacklistIPAddress);
unset($blacklistUserAgent);