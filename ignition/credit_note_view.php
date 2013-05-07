<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CreditNote.php');

$session->Secure(3);

$creditNote = new CreditNote($_REQUEST['cnid']);

echo $creditNote->GetDocument();