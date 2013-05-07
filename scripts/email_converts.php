<?php
ini_set('max_execution_time', '900');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Contact.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/FindReplace.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();
$GLOBALS['SITE_LIVE'] = false;

$contact = new Contact();

$data = new DataQuery(sprintf("SELECT * FROM temp_katie"));
while($data->Row) {
	$contact->Get($data->Row['Contact_ID']);

	$findReplace = new FindReplace();
	$findReplace->Add('/\[BODY\]/', 'Hi, this is Nathan from BLT Direct I have recently taken over your account from Katie, if there is anything I can help you with please feel free to give me a call on 01473 716418.');
	$findReplace->Add('/\[NAME\]/', $contact->Person->GetFullName());

	$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
	$emailBody = '';

	for($i=0; $i < count($stdTmplate); $i++){
		$emailBody .= $findReplace->Execute($stdTmplate[$i]);
	}

	$mail = new htmlMimeMail5();
	$mail->setFrom($GLOBALS['EMAIL_FROM']);
	$mail->setSubject(sprintf("%s Account Information", $GLOBALS['COMPANY']));
	$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
	$mail->setHTML($emailBody);
	$mail->send(array($contact->Person->Email));

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();
?>