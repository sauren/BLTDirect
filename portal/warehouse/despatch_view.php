<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Despatch.php');

$session->Secure(3);

if(isset($_REQUEST['despatchid'])) {
	$despatch = new Despatch($_REQUEST['despatchid']);
	$despatch->IsIgnition = true;
	$despatch->GetLines();
	$despatch->Order->Get();
}

echo $despatch->GetDocument(($despatch->Order->IsPlainLabel == 'N') ? true : false);