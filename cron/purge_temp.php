<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'Purge Temporary Files';
$fileName = 'purge_temp.php';

## BEGIN SCRIPT
$dir = $GLOBALS["DIR_WS_ADMIN"] . 'temp/';

function purgeDirectory($dir) {
	$log = array();

	if($handle = opendir($dir)) {
	    while(false !== ($file = readdir($handle))) {
	        if(($file != '.') && ($file != '..') && (substr($file, 0, 1) != '.')) {
	            if(is_dir($dir.$file)) {
	            	$log = array_merge(purgeDirectory(sprintf('%s%s/', $dir, $file)), $log);
	            } else {
	            	$log[] = sprintf("Purged: %s%s", $dir, $file);

	            	unlink($dir.$file);
	            }
	        }
	    }

	    closedir($handle);
	}

	return $log;
}

$log = array_merge(purgeDirectory($dir), $log);
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
?>