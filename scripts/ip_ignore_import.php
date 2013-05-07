<?php
require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IpIgnore.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

new DataQuery(sprintf("TRUNCATE TABLE ip_ignore"));

$xml = '';

$fh = fopen('http://www.mcafeesecure.com/help/ScanIps.rss', 'r');

if($fh) {
	while(!feof($fh)) {
		$xml .= fgets($fh, 1024);
	}
	
	fclose($fh);
}

$doc = new DOMDocument();
$doc->loadXML(trim($xml));

$ips = $doc->getElementsByTagName('title');

foreach ($ips as $ip) {
	if(preg_match('/^[0-9.]+$/', $ip->nodeValue)) {
		$ipObj = new IpIgnore();
		$ipObj->ip = ip2long($ip->nodeValue);
		$ipObj->add();
	}
}

$GLOBALS['DBCONNECTION']->Close();