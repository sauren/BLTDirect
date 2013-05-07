<?php
require_once('../classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/CartTemplate.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/FindReplace.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Setting.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Person.php');

$template = new CartTemplate();
if(!$template->Get($_REQUEST['id'])) {
	header("HTTP/1.0 400 Bad Request");
} else {
	$findReplace = new FindReplace();

	if(isset($_REQUEST['personid'])) {
		$person = new Person($_REQUEST['personid']);
		$person->Get();

		$findReplace->Add('/\[TITLE]/', $person->Title);
		$findReplace->Add('/\[FIRSTNAME]/', $person->Name);
		$findReplace->Add('/\[LASTNAME]/', $person->LastName);
		$findReplace->Add('/\[FULLNAME]/', $person->GetFullName());

		$findReplace->Add('/\[FAX\]/', $person->Fax);
		$findReplace->Add('/\[PHONE\]/', $person->Phone1);
		$findReplace->Add('/\[ADDRESS\]/', $person->Address->GetLongString());
		$findReplace->Add('/\[EMAIL\]/', $person->Email);
	}

	echo $findReplace->Execute($template->Template);
}

$GLOBALS['DBCONNECTION']->Close();
?>