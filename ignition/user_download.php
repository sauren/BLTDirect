<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserDocument.php');

$session->Secure(3);

$document = new UserDocument();

if(!$document->Get($_REQUEST['documentid'])) {
	echo '<script language="javascript" type="text/javascript">alert(\'An error has occurred.\n\nPlease inform the system administrator that the download is missing.\'); window.close();</script>';
	require_once('lib/common/app_footer.php');
	exit;
}

$fileName =  $document->File->FileName;
$filePath = sprintf("%s%s", $GLOBALS['USER_DOCUMENT_DIR_FS'], $fileName);

header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private", false);
header("Content-Transfer-Encoding: binary");
header("Content-Type: application/force-download");
header(sprintf("Content-Length: %s", filesize($filePath)));
header(sprintf("Content-Disposition: attachment; filename=%s", $fileName));

readfile($filePath);

require_once('lib/common/app_footer.php');