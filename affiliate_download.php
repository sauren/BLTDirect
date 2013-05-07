<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/AffiliatePDF.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Document.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');

$session->Secure();

$documentType = strtolower(param('document',''));

if(!empty($documentType)) {
	$html = '';

	switch($documentType) {
		case 'leaflet':
			$document = new Document(14);

			$html = $document->Body;
			break;

		case 'website':
			$document = new Document(13);

			$html = $document->Body;
			break;
	}

	$findReplace = new FindReplace();
	$findReplace->Add('/\[CUSTOMER\]/', sprintf("%s%s %s %s<br />%s", ($session->Customer->Contact->Parent->ID > 0) ? sprintf('%s<br />', $session->Customer->Contact->Parent->Organisation->Name) : '', $session->Customer->Contact->Person->Title, $session->Customer->Contact->Person->Name, $session->Customer->Contact->Person->LastName, $session->Customer->Contact->Person->Address->GetLongString()));

	$html = $findReplace->Execute($html);

	$pdf = new AffiliatePDF();
	$pdf->AddPage();
	$pdf->WriteHTML(sprintf("&#012;%s", $html));

	$fileName = $pdf->Output(sprintf('affiliate_%s_%d.pdf', $documentType, $session->Customer->ID), 'F', false, $GLOBALS['AFFILIATE_DOCUMENT_DIR_FS']);
	$filePath = sprintf("%s%s", $GLOBALS['AFFILIATE_DOCUMENT_DIR_FS'], $fileName);

	header(sprintf("Content-Disposition: attachment; filename=%s", $fileName));
	readfile($filePath);
} else {
	echo '<script type="text/javascript">window.close();</script>';
}

require_once('lib/common/app_footer.php');
?>