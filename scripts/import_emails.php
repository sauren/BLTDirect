<?php
ini_set('max_execution_time', '3600');
ini_set('display_errors','on');
ini_set('memory_limit', '1024M');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CsvImport.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();
$GLOBALS['SITE_LIVE'] = false;
$newEmails = array();
$oldEmails = array();

$csv = new CsvImport('data/emails.csv', '"', ',');
$csv->HasFieldNames = true;

$form = new Form($_SERVER['PHP_SELF']);

if($csv->Open()) {
	while($csv->Data) {
		if(isset($csv->Data[0])) {
			$email = trim(strtolower($csv->Data[0]));

			if(preg_match('/'.$form->RegularExp['email'].'/', $email)) {
				$newEmails[$email] = $email;
			}
		}

		$csv->Next();
	}

	$csv->Close();
}

$data = new DataQuery(sprintf("SELECT p.Email FROM contact AS c INNER JOIN person AS p ON c.Person_ID=p.Person_ID"));
while($data->Row) {
	$email = trim(strtolower($data->Row['Email']));

	$oldEmails[$email] = $email;

	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT o.Email FROM contact AS c INNER JOIN organisation AS o ON c.Org_ID=o.Org_ID"));
while($data->Row) {
	$email = trim(strtolower($data->Row['Email']));

	$oldEmails[$email] = $email;

	$data->Next();
}
$data->Disconnect();

sort($oldEmails);

foreach($oldEmails as $emailItem) {
	if(isset($newEmails[$emailItem])) {
		unset($newEmails[$emailItem]);
	}
}

$suffix = array('\.org\..{2}$',
				'\.gov\..{2}$',
				'\.sch\..{2}$',
				'\.nhs\..{2}$',
				'\.mod\..{2}$',
				'\.plc\..{2}$',
				'\.ltd\..{2}$',
				'\.com\..{2}$',
				'\.gbr\..{2}$',
				'\.net\..{2}$',
				'\.glo\..{2}$',
				'\.iol\..{2}$',
				'\.co\..{2}$',
				'\.ac\..{2}$',
				'\.tm\..{2}$',
				'\.me\..{2}$',
				'\.org$',
				'\.edu$',
				'\.com$',
				'\.info$',
				'\.aero$',
				'\.coop$',
				'\.name$',
				'\.net$',
				'\.biz$',
				'\.int$',
				'\.gov$',
				'\..{2}$');

$resolve = array();
$organisations = array();

foreach($newEmails as $emailItem) {
	if($pos = stripos($emailItem, '@')) {
		$emailTrimmed = substr($emailItem, $pos + 1);
		$match = false;

		foreach($suffix as $suffixItem) {
			if(preg_match(sprintf('/(.*)(%s)$/', $suffixItem), $emailTrimmed, $matches))  {
				$orgName = $matches[1];
				$orgName = preg_replace(array('/_/', '/\./', '/-/'), ' ', $orgName);
				$orgName = trim(ucwords($orgName));

				$name = substr($emailItem, 0, $pos);
				$name = preg_replace(array('/_/', '/\./', '/-/'), ' ', $name);
				$name = trim(ucwords($name));

				if(!isset($organisations[$orgName])) {
					$organisations[$orgName] = array(	'Name' => $orgName,
														'Email' => $emailItem,
														'Contacts' => array());
				}

				$organisations[$orgName]['Contacts'][] = array(	'Name' => $name,
																'Email' => $emailItem);

				$match = true;
				break;
			}
		}

		if(!$match) {
			if($pos = stripos($emailTrimmed, '.')) {
				$resolve[$emailTrimmed] = $emailTrimmed;
			}
		}
	}
}

foreach($organisations as $orgItem) {
	$parent = new Contact();
	$parent->Type = 'O';
	$parent->Organisation->Name = $orgItem['Name'];
	$parent->Organisation->Email = $orgItem['Email'];
	$parent->IsTemporary = 'Y';
	$parent->Add();

	foreach($orgItem['Contacts'] as $contactItem) {
		$contact = new Contact();
		$contact->Type = 'I';
		$contact->Person->Name = $contactItem['Name'];
		$contact->Person->Email  = $contactItem['Email'];
		$contact->IsTemporary = 'Y';
		$contact->Add();

		$contact->Parent->ID = $parent->ID;
		$contact->Update();
	}
}

/*echo '<pre>';
print_r($resolve);
echo '</pre>';*/

$GLOBALS['DBCONNECTION']->Close();
?>