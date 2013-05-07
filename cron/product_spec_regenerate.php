<?php
ini_set('max_execution_time', '1800');
ini_set('memory_limit', '512M');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Cron.php');

$cron = new Cron();
$cron->scriptName = 'Product Specification Regenerate';
$cron->scriptFileName = 'product_spec_regenerate.php';
$cron->mailLogLevel = Cron::LOG_LEVEL_WARNING;

## BEGIN SCRIPT
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecGroup.php');

ProductSpecGroup::regenerateRanges();
## END SCRIPT

$cron->execute();
$cron->output();

$GLOBALS['DBCONNECTION']->Close();