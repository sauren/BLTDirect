<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Asset.php');

$session->Secure(3);

$asset = new Asset();

if(!isset($_REQUEST['hash']) || !$asset->getByHash($_REQUEST['hash'])) {
	header("HTTP/1.1 404 Not Found");
} else {
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private", false);
	header("Content-Transfer-Encoding: binary");
	header("Content-Type: application/force-download");
	header("Content-Length: " . strlen($asset->getData()));
	header("Content-Disposition: attachment; filename=" . $asset->name);

	echo $asset->getData();
}