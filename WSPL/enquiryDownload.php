<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLineDocument.php');

$session->Secure(3);

$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM enquiry AS e INNER JOIN customer AS c ON c.Customer_ID=e.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN enquiry_line AS el ON el.Enquiry_ID=e.Enquiry_ID INNER JOIN enquiry_line_document AS eld ON eld.Enquiry_Line_ID=el.Enquiry_Line_ID WHERE ((n.Parent_Contact_ID>0 AND n.Parent_Contact_ID=%d) OR (n.Parent_Contact_ID=0 AND n.Contact_ID=%d)) AND eld.Enquiry_Line_Document_ID=%d", $session->Customer->Contact->Parent->ID, $session->Customer->Contact->ID, id_param('documentid')));
if($data->Row['Counter'] == 0) {
	redirect(sprintf("Location: enquiries.php"));
}
$data->Disconnect();

$document = new EnquiryLineDocument();
if(id_param('documentid') && !$document->Get(id_param('documentid'))) {
	echo '<script type="text/javascript">alert(\'An error has occurred.\n\nPlease inform the system administrator that the download is missing.\'); window.close();</script>';
	require_once('../lib/common/appFooter.php');
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

require_once('../lib/common/appFooter.php');
?>