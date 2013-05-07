<?php
require_once('ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');

$pathInfo = $_SERVER['REQUEST_URI'];
//echo $pathInfo;
if(($pos = stripos($pathInfo, '?')) !== false) {
	$pathInfo = substr($pathInfo, 0, $pos);
}

$pathInfo = (substr($pathInfo, strlen($pathInfo) - 1) == '/') ? substr($pathInfo, 0, strlen($pathInfo) - 1) : $pathInfo;
//echo $pathInfo;
$dataUrlAlias = new DataQuery(sprintf("SELECT Type, Reference_ID FROM url_alias WHERE Alias='%s' LIMIT 0, 1", mysql_real_escape_string($pathInfo)));
if($dataUrlAlias->TotalRows > 0) {
	$items = explode('&', $_SERVER['QUERY_STRING']);

	for($i=0; $i<count($items); $i++) {
		$parts = explode('=', $items[$i]);

		if(count($parts) == 2) {
			$_REQUEST[$parts[0]] = $parts[1];
		}
	}

	unset($items);
	unset($parts);

	switch(strtolower(trim($dataUrlAlias->Row['Type']))) {
		case 'product':
			$_REQUEST['pid'] = $dataUrlAlias->Row['Reference_ID'];

			header('HTTP/1.1 200 OK');
			require_once('product.php');
			break;

		case 'category':
			$_REQUEST['cat'] = $dataUrlAlias->Row['Reference_ID'];

			header('HTTP/1.1 200 OK');
			require_once('products.php');
			break;
	}

	exit;
}
$dataUrlAlias->Disconnect();

$pathItems = explode('/', substr($pathInfo, 1));

$dataLanding = new DataQuery(sprintf("SELECT id FROM product_landing WHERE name LIKE '%s' LIMIT 0, 1", mysql_real_escape_string(str_replace('-', ' ', $pathItems[0]))));
if($dataLanding->TotalRows > 0) {
	$_REQUEST['landingid'] = $dataLanding->Row['id'];
	$_REQUEST['specid'] = isset($pathItems[1]) ? $pathItems[1] : 0;
	
	$redirectItems = explode('&', isset($_SERVER['REDIRECT_QUERY_STRING']) ? $_SERVER['REDIRECT_QUERY_STRING'] : '');
	
	foreach($redirectItems as $redirectItem) {
		$items = explode('=', $redirectItem);

		if(count($items) >= 2) {
			$_REQUEST[$items[0]] = $items[1];
		}
	}
	
	header('HTTP/1.1 200 OK');
	require_once('productLanding.php');

	exit;
}
$dataLanding->Disconnect();

$exclude = array();
$fileNotFound = false;

array_push($exclude, 'jpeg');
array_push($exclude, 'jpg');
array_push($exclude, 'gif');
array_push($exclude, 'png');
array_push($exclude, 'ico');
array_push($exclude, 'css');
array_push($exclude, 'js');

if(stristr($pathInfo, '/track.gif')) {
	$redirected = array();
	$request = explode('&', isset($_SERVER['REDIRECT_QUERY_STRING']) ? $_SERVER['REDIRECT_QUERY_STRING'] : '');

	foreach($request as $requestItem) {
		$parts = explode('=', $requestItem);

		$redirected[$parts[0]] = isset($parts[1]) ? $parts[1] : '';
	}

	if(isset($redirected['entity'])) {
		$entity = trim(urldecode($redirected['entity']));

		$cypher = new Cipher(base64_decode($entity));
		$cypher->Decrypt();

		$entity = @unserialize($cypher->Value);

		if(isset($entity['Type'])) {
			switch($entity['Type']) {
				case 'Campaign':

					if(is_numeric($entity['CampaignEvent']) && is_numeric($entity['CampaignContact'])){
						new DataQuery(sprintf("UPDATE campaign_contact_event SET Is_Email_Viewed='Y' WHERE Campaign_Event_ID=%d AND Campaign_Contact_ID=%d", mysql_real_escape_string($entity['CampaignEvent']), mysql_real_escape_string($entity['CampaignContact'])));
					}

					break;
			}
		}
	}
	
	header('Content-Type: image/gif');

	echo file_get_contents($GLOBALS["DIR_WS_ROOT"] . '/images/track.gif');
	exit;
}

if(strlen($pathInfo) >= 4) {
	$fileExt = strrev(substr(strrev($pathInfo), 0, 4));

	if(substr($fileExt, 0, 1) == '.') {
		foreach($exclude as $fileType) {
			if($fileType == substr($fileExt, 1, 3)) {
				$fileNotFound = true;
			}
		}
	}
}

$notFound = 'page';
if($fileNotFound){
	$notFound = 'file';
}

$ourFault = false;
if(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $GLOBALS['HTTP_SERVER']) !== false) {
	$ourFault = true;
}

unset($exclude);
unset($dataUrlAlias);

if(stristr($pathInfo, '/login')) {
	require_once('gateway.php');
} elseif(stristr($pathInfo, '/introduce-a-friend')) {
	require_once('introduce_a_friend.php');
} else {
	require_once('notfound.php');
}


if($ourFault) {
	$mailBody = sprintf("The following details are regarding a Broken Link to a %s from %s \n\rPATH INFO: %s\nHTTP REFERER: %s", ucfirst($notFound), $GLOBALS['COMPANY'], $pathInfo, $_SERVER['HTTP_REFERER']);
	
	$mail = new htmlMimeMail5();
	$mail->setFrom($GLOBALS['EMAIL_FROM']);
	$mail->setSubject(sprintf("Broken Link: %s", $pathInfo));
	$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
	$mail->setHTML($mailBody);
	//$mail->send(array($GLOBALS['EMAIL_TECHNICAL']));
}
exit;
