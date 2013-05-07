<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Category.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'Sitemap Cache';
$fileName = 'cache_sitemap.php';

## BEGIN SCRIPT
$cacheFile = 'sitemap.dat';

function listCategories($catId = 0){
	global $log;

	$txt = "";
	$subCategory = new Category();

	$data = new DataQuery(sprintf("SELECT Category_ID, Category_Title, Meta_Title FROM product_categories WHERE Is_Active='Y' AND Category_Parent_ID=%d", mysql_real_escape_string($catId)));
	if($data->TotalRows > 0){
		$txt .= "<ul>\n";

		while($data->Row){
			$subCategory->ID = $data->Row['Category_ID'];
			$subCategory->Name = $data->Row['Category_Title'];
			$subCategory->MetaTitle = $data->Row['Meta_Title'];

			$url = $subCategory->GetUrl();

			$log[] = sprintf("Caching Category: %s [#%d]", $data->Row['Category_Title'], $data->Row['Category_ID']);

			$txt .= sprintf("<li><a href=\"%s\" title=\"%s\">%s</a></li>\n", $url, $data->Row['Meta_Title'], $data->Row['Category_Title']);
			$txt .= listCategories($data->Row['Category_ID']);

			$data->Next();
		}

		$txt .= "</ul>\n";
	}
	$data->Disconnect();

	return $txt;
}

$fh = fopen($GLOBALS['DIR_WS_CACHE'].$cacheFile, 'w');
fwrite($fh, listCategories());
fclose($fh);
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