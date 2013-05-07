<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductImageExampleRequest.php');

$session->Secure(3);

$document = new ProductImageExampleRequest();

if(!$document->Get($_REQUEST['id'])) {
	echo '<script language="javascript" type="text/javascript">alert(\'An error has occurred.\n\nPlease inform the system administrator that the download is missing.\'); window.close();</script>';
	require_once('lib/common/app_footer.php');
	exit;
}

$asset = $document->asset;
$asset->get();

header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private", false);
header("Content-Transfer-Encoding: binary");
header("Content-Type: application/force-download");
header("Content-Length: " . strlen($asset->getData()));
header("Content-Disposition: attachment; filename=" . $asset->name);

echo $asset->getData();

require_once('lib/common/app_footer.php');