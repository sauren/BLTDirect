<?php
require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Despatch.php');

if(param('ref')) {
	$reference = trim(urldecode(param('ref', '')));

	$cypher = new Cipher(base64_decode($reference));

	if(strlen(base64_decode($reference)) >= $cypher->IvSize) {
		$cypher->Data = base64_decode($reference);
		$cypher->Decrypt();

        $reference = unserialize($cypher->Value);

        $despatch = new Despatch();

        if($despatch->Get($reference['Despatch'])) {
			echo $despatch->GetDocument(($despatch->Order->IsPlainLabel == 'N') ? true : false);
			echo '<script langauge="javascript" type="text/javascript">window.self.print();</script>';
        	exit;
		}
	}
}

redirect(sprintf("Location: index.php"));