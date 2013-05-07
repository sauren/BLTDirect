<?php
require_once('lib/common/app_header.php');

$session->Secure(3);

if(empty($_REQUEST['filename']) || !file_exists($GLOBALS['CAMPAIGN_DOCUMENT_DIR_FS'].$_REQUEST['filename'])) {
	echo '<script language="javascript" type="text/javascript">alert(\'An error has occurred.\n\nPlease inform the system administrator that the download is missing.\'); window.close();</script>';
	require_once('lib/common/app_footer.php');
	exit;
}

$filepath = sprintf("%s%s", $GLOBALS['CAMPAIGN_DOCUMENT_DIR_FS'], $_REQUEST['filename']);

header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private", false);
header("Content-Transfer-Encoding: binary");
header("Content-Type: application/force-download");
header(sprintf("Content-Length: %s", filesize($filepath)));
header(sprintf("Content-Disposition: attachment; filename=%s", $_REQUEST['filename']));

readfile($filepath);

require_once('lib/common/app_footer.php');
?>