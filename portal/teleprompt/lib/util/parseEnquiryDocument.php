<?php
require_once('../../../../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Document.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/FindReplace.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/User.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Enquiry.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Setting.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Customer.php');

$document = new Document();
if(!$document->Get($_REQUEST['id'])) {
	header("HTTP/1.0 400 Bad Request");
} else {
	$findReplace = new FindReplace();

	if(isset($_REQUEST['userid'])) {
		$user = new User($_REQUEST['userid']);

		$findReplace->Add('/\[USERNAME\]/', sprintf("%s %s", $user->Person->Name, $user->Person->LastName));
		$findReplace->Add('/\[USEREMAIL\]/', $user->Username);
		$findReplace->Add('/\[USERPHONE\]/', sprintf('%s', (strlen(trim($user->Person->Phone1)) > 0) ? $user->Person->Phone1 : $user->Person->Phone2));
	}

	$ownerFound = false;

	if(isset($_REQUEST['enquiryid'])) {
		$enquiry = new Enquiry($_REQUEST['enquiryid']);

		if($enquiry->OwnedBy > 0) {
			$owner = new User();
			$owner->ID = $enquiry->OwnedBy;

			if($owner->Get()) {
				$ownerFound = true;
				$findReplace->Add('/\[SALES\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', sprintf("%s %s", $owner->Person->Name, $owner->Person->LastName), $owner->Person->Phone1, $owner->Username));
			}
		}
	}

	if(!$ownerFound) {
		$findReplace->Add('/\[SALES\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', Setting::GetValue('default_username'), Setting::GetValue('default_userphone'), Setting::GetValue('default_useremail')));
	}

	if(isset($_REQUEST['customerid'])) {
		$customer = new Customer($_REQUEST['customerid']);
		$customer->Contact->Get();

		$findReplace->Add('/\[CUSTOMER\]/', sprintf("%s%s %s %s", ($customer->Contact->Parent->ID > 0) ? sprintf('%s<br />', $customer->Contact->Parent->Organisation->Name) : '', $customer->Contact->Person->Title, $customer->Contact->Person->Name, $customer->Contact->Person->LastName));
		$findReplace->Add('/\[TITLE\]/', $customer->Contact->Person->Title);
		$findReplace->Add('/\[FIRSTNAME\]/', $customer->Contact->Person->Name);
		$findReplace->Add('/\[LASTNAME\]/', $customer->Contact->Person->LastName);
		$findReplace->Add('/\[FULLNAME\]/', trim(str_replace("   ", " ", str_replace("  ", " ", sprintf("%s %s %s", $customer->Contact->Person->Title, $customer->Contact->Person->Name, $customer->Contact->Person->LastName)))));
		$findReplace->Add('/\[FAX\]/', $customer->Contact->Person->Fax);
		$findReplace->Add('/\[PHONE\]/', $customer->Contact->Person->Phone1);
		$findReplace->Add('/\[ADDRESS\]/', $customer->Contact->Person->Address->GetLongString());
		$findReplace->Add('/\[EMAIL\]/', $customer->GetEmail());
		//Do not send out Password via Email - Plus, its impossible to get unencrypted version of password.
		//$findReplace->Add('/\[PASSWORD\]/', $customer->GetPassword());
	}

	echo $findReplace->Execute($document->Body);
}

$GLOBALS['DBCONNECTION']->Close();
?>