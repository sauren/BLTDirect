<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLineDocument.php');

$session->Secure(3);

$document = new EnquiryLineDocument();
if(!$document->Get($_REQUEST['documentid'])) {
	echo '<script language="javascript" type="text/javascript">alert(\'An error has occurred.\n\nPlease inform the system administrator that the download is missing.\'); window.close();</script>';
	require_once('lib/common/app_footer.php');
	exit;
}

$filepath = sprintf("%s%s", $GLOBALS['ENQUIRY_DOCUMENT_DIR_FS'], $document->File->FileName);

header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private", false);
header("Content-Transfer-Encoding: binary");
header("Content-Type: application/force-download");
header(sprintf("Content-Length: %s", filesize($filepath)));

$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);

if((is_integer(strpos($userAgent, "msie"))) && (is_integer(strpos($userAgent, "win")))){
	header(sprintf("Content-Disposition: filename=%s", $document->File->FileName));
} else {
	header(sprintf("Content-Disposition: attachment; filename=%s", $document->File->FileName));
}

readfile($filepath);

require_once('lib/common/app_footer.php');
?>