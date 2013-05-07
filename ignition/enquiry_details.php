<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLineDocument.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLineQuote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IFile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Quote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$session->Secure(3);

$enquiry = new Enquiry();

if(!$enquiry->Get($_REQUEST['enquiryid'])) {
	redirect(sprintf("Location: enquiry_search.php"));
}

$enquiry->Type->Get();
$enquiry->Channel->Get();
$enquiry->Customer->Get();
$enquiry->Customer->Contact->Get();
$enquiry->ClosedType->Get();
$enquiry->GetLines();
$enquiry->Received();

if($action == 'requestcatalogue'){
    $enquiry->Customer->Contact->IsCatalogueRequested = 'Y';
    $enquiry->Customer->Contact->Update();
    redirectTo('?enquiryid=' . $enquiry->ID);
}

if($action == 'cancelrequestcatalogue'){
    $enquiry->Customer->Contact->IsCatalogueRequested = 'N';
    $enquiry->Customer->Contact->Update();
    redirectTo('?enquiryid=' . $enquiry->ID);
}

UserRecent::Record(sprintf('[#%d] Enquiry Details (%s)', $enquiry->ID, $enquiry->Customer->Contact->Person->GetFullName()), sprintf('enquiry_details.php?enquiryid=%d', $enquiry->ID));

if($enquiry->Customer->Contact->Parent->ID > 0) {
	$enquiry->Customer->Contact->Parent->Get();
}

$customerName = trim(sprintf('%s %s %s', $enquiry->Customer->Contact->Person->Title, $enquiry->Customer->Contact->Person->Name, $enquiry->Customer->Contact->Person->LastName));
$orgName = $enquiry->Customer->Contact->Person->GetFullName();

$data = new DataQuery(sprintf("SELECT Enquiry_Type_ID FROM enquiry_type WHERE Enquiry_Type_ID=%d", mysql_real_escape_string($enquiry->Type->ID)));
$enquiry->Type->ID = ($data->TotalRows > 0) ? $data->Row['Enquiry_Type_ID'] : 0;
$data->Disconnect();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('enquiryid', 'Enquiry ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('categorytype', 'Category', 'select', $enquiry->Type->ID, 'numeric_unsigned', 1, 11, false, 'onchange="changeType(this);"');

if($enquiry->Type->ID == 0) {
	$form->AddOption('categorytype', '', '');
}

$form->AddField('owner', 'Owned By', 'select', $enquiry->OwnedBy, 'numeric_unsigned', 1, 11, false, 'onchange="changeOwner(this);"');
$form->AddOption('owner', '0', '');
$form->AddField('template', 'Standard Template', 'select', '', 'anything', 1, 255, false, 'onchange="populateResponse(this);"');
$form->AddOption('template', '0', '');
$form->AddField('message', 'Message', 'textarea', '', 'anything', 1, 16384, false, 'style="width: 100%; font-family: arial, sans-serif;" rows="15"');
$form->AddField('product', 'Product ID', 'text', '', 'numeric_unsigned', 1, 11, false, 'size="8"');
$form->AddField('public', 'Public', 'checkbox', (isset($_REQUEST['public'])) ? $_REQUEST['public'] : (isset($_REQUEST['confirm']) ? 'N' : 'Y'), 'boolean', 1, 1, false);
$form->AddField('customermessage', 'Customer Message', 'checkbox', (isset($_REQUEST['customermessage'])) ? $_REQUEST['customermessage'] : 'N', 'boolean', 1, 1, false);
$form->AddField('pending', 'Pending Action', 'checkbox', (isset($_REQUEST['pending'])) ? $_REQUEST['pending'] : 'N', 'boolean', 1, 1, false);
$form->AddField('sendto', 'Sent To', 'select', (($enquiry->Status == 'Closed') || ($enquiry->IsOrdered == 'Y')) ? '' : $enquiry->IsPendingAction, 'alpha', 0, 1, false, 'onchange="changePending(this);"');
$form->AddField('bigenquiry', 'Big Enquiry', 'checkbox', $enquiry->IsBigEnquiry, 'boolean', 1, 1, false, 'onclick="setBigEnquiry((this.checked) ? \'true\' : \'false\');"');
$form->AddField('tradeenquiry', 'Trade Enquiry', 'checkbox', $enquiry->IsTradeEnquiry, 'boolean', 1, 1, false, 'onclick="setTradeEnquiry((this.checked) ? \'true\' : \'false\');"');
$form->AddField('isordered', 'Is Ordered', 'checkbox', $enquiry->IsOrdered, 'boolean', 1, 1, false, 'onclick="setIsOrdered((this.checked) ? \'true\' : \'false\');"');

if(($enquiry->Status == 'Closed') || ($enquiry->IsOrdered == 'Y')) {
	$form->AddOption('sendto', '', '');
}

$form->AddOption('sendto', 'N', 'Awaiting Response');
$form->AddOption('sendto', 'Y', 'Pending Action');

$additional = array();

$data = new DataQuery(sprintf("SELECT * FROM enquiry_type ORDER BY Name ASC"));
while($data->Row) {
	if(strtolower($data->Row['Name']) == 'other') {
		$additional[$data->Row['Name']] = $data->Row['Enquiry_Type_ID'];
	} else {
		$form->AddOption('categorytype', $data->Row['Enquiry_Type_ID'], $data->Row['Name']);
	}

	$data->Next();
}
$data->Disconnect();

foreach($additional as $type=>$id) {
	$form->AddOption('categorytype', $id, $type);
}

$data = new DataQuery(sprintf("SELECT u.User_ID, p.Name_First, p.Name_Last FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY p.Name_First, p.Name_Last ASC"));
while($data->Row) {
	$form->AddOption('owner', $data->Row['User_ID'], trim(sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last'])));

	$data->Next();
}
$data->Disconnect();

$templateCount = 0;

$data = new DataQuery(sprintf("SELECT Enquiry_Template_ID, Title, Template FROM enquiry_template WHERE Enquiry_Type_ID=0 OR Enquiry_Type_ID=%d ORDER BY Title ASC", mysql_real_escape_string($enquiry->Type->ID)));
while($data->Row) {
	$templateCount++;

	$form->AddOption('template', $data->Row['Enquiry_Template_ID'], $data->Row['Title']);
	$data->Next();
}
$data->Disconnect();

if($enquiry->Status != 'Closed') {
	$form->AddField('reviewperiod', 'Review Period', 'select', '', 'anything', 1, 255, false);
	$form->AddOption('reviewperiod', '', '');

	for($i=0; $i<7; $i++) {
		$timeframe = sprintf('%d Day%s', $i + 1, ($i > 0) ? 's' : '');
		$form->AddOption('reviewperiod', $timeframe, $timeframe);
	}

	for($i=1; $i<3; $i++) {
		$timeframe = sprintf('%d Week%s', $i + 1, ($i > 0) ? 's' : '');
		$form->AddOption('reviewperiod', $timeframe, $timeframe);
	}

	$timeframe = sprintf('1 Month');
	$form->AddOption('reviewperiod', $timeframe, $timeframe);

	for($i=3; $i<7; $i=$i+3) {
		$timeframe = sprintf('%d Month%s', $i, ($i > 0) ? 's' : '');
		$form->AddOption('reviewperiod', $timeframe, $timeframe);
	}

	$form->AddField('reviewtime', 'Review Time', 'select', '', 'anything', 1, 255, false);
	$form->AddOption('reviewtime', '', '');

	for($i=8; $i<19; $i++) {
		$form->AddOption('reviewtime', sprintf('%d:00', $i), sprintf('%d:00', $i));
	}

	$form->AddField('reviewdate', 'Review Date', 'text', ($enquiry->ReviewOn > '0000-00-00 00:00:00') ? date('d/m/Y', strtotime($enquiry->ReviewOn)) : '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
}

$drafts = 0;
$messages = 0;

for($i=0;$i<count($enquiry->Line);$i++) {
	if($enquiry->Line[$i]->IsDraft == 'Y') {
		$drafts++;

		$form->AddField('draft_message_'.$enquiry->Line[$i]->ID, 'Draft Message', 'textarea', $enquiry->Line[$i]->Message, 'anything', 1, 16384, false, 'style="width: 100%; font-family: arial, sans-serif;" rows="15"');
	} else {
		$messages++;
	}
}

if($action == 'removeline') {
	$enquiryLine = new EnquiryLine();
	$enquiryLine->Delete($_REQUEST['enquirylineid']);

	redirect(sprintf("Location: %s?enquiryid=%d", $_SERVER['PHP_SELF'], $enquiry->ID));

} elseif($action == 'removeschedule') {
	$enquiry->ReviewOn = '0000-00-00 00:00:00';
	$enquiry->Update();

	redirect(sprintf("Location: %s?enquiryid=%d", $_SERVER['PHP_SELF'], $enquiry->ID));

} elseif($action == 'removedocument') {
	$enquiryDocument = new EnquiryLineDocument();
	$enquiryDocument->Delete($_REQUEST['enquirydocumentid']);

	redirect(sprintf("Location: %s?enquiryid=%d", $_SERVER['PHP_SELF'], $enquiry->ID));

} elseif($action == 'removequote') {
	$enquiryQuote = new EnquiryLineQuote();
	$enquiryQuote->Delete($_REQUEST['enquiryquoteid']);

	redirect(sprintf("Location: %s?enquiryid=%d", $_SERVER['PHP_SELF'], $enquiry->ID));

} elseif($action == 'removesessionquote') {
	unset($_SESSION['Enquiries'][$enquiry->ID]['Quotes'][$_REQUEST['quoteid']]);

	redirect(sprintf("Location: %s?enquiryid=%d", $_SERVER['PHP_SELF'], $enquiry->ID));

} elseif($action == 'removesessiondocument') {
	$file = new IFile();
	$file->Directory = $GLOBALS['TEMP_ENQUIRY_DOCUMENT_DIR_FS'];
	$file->FileName = $_REQUEST['document'];

	if($file->Delete()) {
		unset($_SESSION['Enquiries'][$enquiry->ID]['Documents'][$_REQUEST['document']]);
	}

	redirect(sprintf("Location: %s?enquiryid=%d", $_SERVER['PHP_SELF'], $enquiry->ID));

} elseif($action == 'setbigenquiry') {
	if(isset($_REQUEST['isbig'])) {
		$enquiry->IsBigEnquiry = ($_REQUEST['isbig'] == 'true') ? 'Y' : 'N';
		$enquiry->Update();
	}

	redirect(sprintf("Location: %s?enquiryid=%d", $_SERVER['PHP_SELF'], $enquiry->ID));

} elseif($action == 'settradeenquiry') {
	if(isset($_REQUEST['istrade'])) {
		$enquiry->IsTradeEnquiry = ($_REQUEST['istrade'] == 'true') ? 'Y' : 'N';
		$enquiry->Update();
	}

	redirect(sprintf("Location: %s?enquiryid=%d", $_SERVER['PHP_SELF'], $enquiry->ID));

} elseif($action == 'setisordered') {
	if(isset($_REQUEST['isordered'])) {
		$enquiry->IsOrdered = ($_REQUEST['isordered'] == 'true') ? 'Y' : 'N';
		$enquiry->Update();
	}

	redirect(sprintf("Location: %s?enquiryid=%d", $_SERVER['PHP_SELF'], $enquiry->ID));

} elseif($action == 'changetype') {
	if(isset($_REQUEST['typeid']) && is_numeric($_REQUEST['typeid']) && ($_REQUEST['typeid'] > 0)) {
		$enquiry->Type->ID = $_REQUEST['typeid'];
		$enquiry->Update();
	}

	redirect(sprintf("Location: %s?enquiryid=%d", $_SERVER['PHP_SELF'], $enquiry->ID));

} elseif($action == 'changepending') {
	if(isset($_REQUEST['pending'])) {
		if($_REQUEST['pending'] == 'Y') {
			if($_REQUEST['pending'] != $enquiry->IsPendingAction) {
				$enquiryType = $_REQUEST['categorytype'];
				$enquiry->SendNotification($enquiryType);
			}
		}

		$enquiry->Status = 'Open';
		$enquiry->IsOrdered = 'N';
		$enquiry->IsPendingAction = $_REQUEST['pending'];
		$enquiry->Update();
	}

	redirect(sprintf("Location: %s?enquiryid=%d", $_SERVER['PHP_SELF'], $enquiry->ID));

} elseif($action == 'changeowner') {
	if(isset($_REQUEST['ownerid']) && is_numeric($_REQUEST['ownerid'])) {
		if($_REQUEST['ownerid'] != $enquiry->OwnedBy) {
			$enquiry->OwnedBy = $_REQUEST['ownerid'];
			$enquiryType = $_REQUEST['categorytype'];
			$enquiry->SendNotification($enquiryType);
		}

		$enquiry->OwnedBy = $_REQUEST['ownerid'];
		$enquiry->Update();
	}

	redirect(sprintf("Location: %s?enquiryid=%d", $_SERVER['PHP_SELF'], $enquiry->ID));
}

if(isset($_REQUEST['review'])) {
	if($form->Validate()) {
		$reviewTimes = array();
		$reviewTimes[0] = 0;

		if(strlen($form->GetValue('reviewtime')) > 0) {
			$reviewTimes = explode(':', $form->GetValue('reviewtime'));
		}

		$reviewDate = date('Y-m-d H:i:s', mktime($reviewTimes[0], 0, 0, date('m'), date('d') + 14, date('Y')));

		if(strlen($form->GetValue('reviewdate')) > 0) {
			$reviewDates = explode('/', $form->GetValue('reviewdate'));
			$reviewDate = date('Y-m-d H:i:s', mktime($reviewTimes[0], 0, 0, $reviewDates[1], $reviewDates[0], $reviewDates[2]));
		} else {
			$reviewDates = explode(' ', $form->GetValue('reviewperiod'));

			switch(strtolower($reviewDates[1])) {
				case 'day':
				case 'days':
					$reviewDate = date('Y-m-d H:i:s', mktime($reviewTimes[0], 0, 0, date('m'), date('d') + $reviewDates[0], date('Y')));
					break;
				case 'week':
				case 'weeks':
					$days = $reviewDates[0] * 7;
					$reviewDate = date('Y-m-d H:i:s', mktime($reviewTimes[0], 0, 0, date('m'), date('d') + $days, date('Y')));
					break;
				case 'month':
				case 'months':
					$reviewDate = date('Y-m-d H:i:s', mktime($reviewTimes[0], 0, 0, date('m') + $reviewDates[0], date('d'), date('Y')));
					break;
			}
		}

		$enquiry->ReviewOn = $reviewDate;
		$enquiry->Update();

		redirect(sprintf("Location: %s?enquiryid=%d", $_SERVER['PHP_SELF'], $enquiry->ID));
	}

} elseif(isset($_REQUEST['close'])) {
	redirect(sprintf("Location: enquiry_close.php?enquiryid=%d", $enquiry->ID));

} elseif(isset($_REQUEST['closing'])) {
	$enquiry->IsRequestingClosure = 'Y';
	$enquiry->IsPendingAction = 'N';
	$enquiry->Update();
	$enquiryType = $_REQUEST['categorytype'];
	$enquiry->SendClosing($enquiryType);

	redirect(sprintf("Location: %s?enquiryid=%d", $_SERVER['PHP_SELF'], $enquiry->ID));

} elseif(isset($_REQUEST['post']) || isset($_REQUEST['draft'])) {
	if(isset($_REQUEST['post'])) {
		$form->InputFields['message']->Required = true;
	}

	if($form->Validate()) {
		$user = new User($session->UserID);
		$ownerFound = false;

		if(!isset($_REQUEST['draft']) || ($_REQUEST['draft'] == 'N')) {
			if($enquiry->OwnedBy == 0) {
				$enquiry->OwnedBy = $session->UserID;
			}
		}

		if($enquiry->OwnedBy > 0) {
			$owner = new User();
			$owner->ID = $enquiry->OwnedBy;

			if($owner->Get()) {
				$ownerFound = true;
			}
		}

		$findReplace = new FindReplace();
		$findReplace->Add('/\[CUSTOMER\]/', sprintf("%s%s %s %s", ($enquiry->Customer->Contact->Parent->ID > 0) ? sprintf('%s<br />', $enquiry->Customer->Contact->Parent->Organisation->Name) : '', $enquiry->Customer->Contact->Person->Title, $enquiry->Customer->Contact->Person->Name, $enquiry->Customer->Contact->Person->LastName));
		$findReplace->Add('/\[TITLE\]/', $enquiry->Customer->Contact->Person->Title);
		$findReplace->Add('/\[FIRSTNAME\]/', $enquiry->Customer->Contact->Person->Name);
		$findReplace->Add('/\[LASTNAME\]/', $enquiry->Customer->Contact->Person->LastName);
		$findReplace->Add('/\[FULLNAME\]/', trim(str_replace("   ", " ", str_replace("  ", " ", sprintf("%s %s %s", $enquiry->Customer->Contact->Person->Title, $enquiry->Customer->Contact->Person->Name, $enquiry->Customer->Contact->Person->LastName)))));
		$findReplace->Add('/\[FAX\]/', $enquiry->Customer->Contact->Person->Fax);
		$findReplace->Add('/\[PHONE\]/', $enquiry->Customer->Contact->Person->Phone1);
		$findReplace->Add('/\[ADDRESS\]/', $enquiry->Customer->Contact->Person->Address->GetLongString());
		$findReplace->Add('/\[USERNAME\]/', sprintf("%s %s", $user->Person->Name, $user->Person->LastName));
		$findReplace->Add('/\[USEREMAIL\]/', $user->Username);
		$findReplace->Add('/\[USERPHONE\]/', sprintf('%s', (strlen(trim($user->Person->Phone1)) > 0) ? $user->Person->Phone1 : $user->Person->Phone2));
		$findReplace->Add('/\[EMAIL\]/', $enquiry->Customer->GetEmail());
		//Do not send out Password via Email - Plus, its impossible to get unencrypted version of password.
		//$findReplace->Add('/\[PASSWORD\]/', $enquiry->Customer->GetPassword());

		if($enquiry->Type->DeveloperKey == 'customerservices') {
			$findReplace->Add('/\[SALES\]/', sprintf('BLT Direct<br />Customer Services: %s<br />customerservices@bltdirect.com', Setting::GetValue('telephone_customer_services')));
		} else {
			if($ownerFound) {
				$findReplace->Add('/\[SALES\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', sprintf("%s %s", $owner->Person->Name, $owner->Person->LastName), $owner->Person->Phone1, $owner->Username));
			} else {
				$findReplace->Add('/\[SALES\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', Setting::GetValue('default_username'), Setting::GetValue('default_userphone'), Setting::GetValue('default_useremail')));
			}
		}

		$message = $findReplace->Execute($form->GetValue('message'));

		$enquiry->IsPendingAction = (isset($_REQUEST['pending']) && ($_REQUEST['pending'] == 'Y')) ? 'Y' : 'N';
		$enquiry->IsRequestingClosure = 'N';
		$enquiry->Update();

		$enquiryLine = new EnquiryLine();
		$enquiryLine->Enquiry->ID = $enquiry->ID;
		$enquiryLine->IsCustomerMessage = (isset($_REQUEST['customermessage']) && ($_REQUEST['customermessage'] == 'Y')) ? $_REQUEST['customermessage'] : 'N';
		$enquiryLine->IsPublic = (isset($_REQUEST['public']) && ($_REQUEST['public'] == 'Y')) ? $_REQUEST['public'] : 'N';
		$enquiryLine->Message = $message;
		$enquiryLine->IsDraft = 'Y';
		$enquiryLine->Add();

		if(isset($_SESSION['Enquiries']) && isset($_SESSION['Enquiries'][$enquiry->ID])) {
			if(isset($_SESSION['Enquiries'][$enquiry->ID]['Quotes'])) {
				foreach($_SESSION['Enquiries'][$enquiry->ID]['Quotes'] as $quoteItem) {
					$enquiryQuote = new EnquiryLineQuote();
					$enquiryQuote->Quote->ID = $quoteItem;
					$enquiryQuote->EnquiryLineID = $enquiryLine->ID;
					$enquiryQuote->Add();

					$enquiryLine->Quotes[] = $enquiryQuote;
				}
			}

			if(isset($_SESSION['Enquiries'][$enquiry->ID]['Documents'])) {
				foreach($_SESSION['Enquiries'][$enquiry->ID]['Documents'] as $documentItem) {
					$enquiryDocument = new EnquiryLineDocument();
					$enquiryDocument->IsPublic = $documentItem['Public'];
					$enquiryDocument->File->SetDirectory($GLOBALS['TEMP_ENQUIRY_DOCUMENT_DIR_FS']);
					$enquiryDocument->File->FileName = $documentItem['FileName'];
					$enquiryDocument->EnquiryLineID = $enquiryLine->ID;

					if(!empty($enquiryDocument->File->FileName) && file_exists($GLOBALS['TEMP_ENQUIRY_DOCUMENT_DIR_FS'].$enquiryDocument->File->FileName)) {
						$destination = new IFile();
						$destination->OnConflict = "makeunique";
						$destination->Extensions = "";
						$destination->SetDirectory($GLOBALS['ENQUIRY_DOCUMENT_DIR_FS']);
						$destination->FileName = $enquiryDocument->File->FileName;
						$destination->SetName($destination->FileName);

						if($destination->Exists()) {
							$destination->CreateUniqueName($enquiryDocument->File->FileName);
						}

						if($enquiryDocument->File->Copy($destination->Directory, $destination->FileName)) {
							$enquiryDocument->File->FileName = $destination->FileName;
							$enquiryDocument->Add();

							$enquiryLine->Documents[] = $enquiryDocument;

							$source = new IFile();
							$source->Directory = $GLOBALS['TEMP_ENQUIRY_DOCUMENT_DIR_FS'];
							$source->FileName = $documentItem['FileName'];
							$source->Delete();
						}
					}
				}
			}

			unset($_SESSION['Enquiries'][$enquiry->ID]);
		}

		if(!isset($_REQUEST['draft']) || ($_REQUEST['draft'] == 'N')) {
			$enquiryLine->IsDraft = 'N';
			$enquiryLine->Update();

			if(($enquiryLine->IsCustomerMessage == 'N') && ($enquiryLine->IsPublic == 'Y')) {
				$enquiryType = $_REQUEST['categorytype'];
				$enquiryLine->SendResponse($enquiryType);

				if($enquiry->OwnedBy == 0) {
					$enquiry->OwnedBy = $GLOBALS['SESSION_USER_ID'];
					$enquiry->Update();
				}
			}

			if($enquiryLine->IsCustomerMessage == 'Y') {
				$enquiry->ReviewOn = '0000-00-00 00:00:00';
				$enquiry->Update();
			}

			if($enquiry->OwnedBy != $GLOBALS['SESSION_USER_ID']) {
				$enquiryType = $_REQUEST['categorytype'];
				$enquiry->SendNotification($enquiryType);
			}
		}

		redirect(sprintf("Location: %s?enquiryid=%d", $_SERVER['PHP_SELF'], $enquiry->ID));
	}
} else {
	foreach($_REQUEST as $key=>$value) {
		$items = explode('-', $key);

		if(count($items) == 2) {
			$enquiryLine = new EnquiryLine();
			if($enquiryLine->Get($items[1])) {

				switch($items[0]) {
					case 'postDraft':
						$form->InputFields['draft_message_'.$enquiryLine->ID]->Required = true;

						if($enquiry->OwnedBy == 0) {
							$enquiry->OwnedBy = $session->UserID;
							$enquiry->Update();
						}

					case 'saveDraft':
						if($form->Validate()) {
							$user = new User($session->UserID);
							$ownerFound = false;

							if($enquiry->OwnedBy > 0) {
								$owner = new User();
								$owner->ID = $enquiry->OwnedBy;

								if($owner->Get()) {
									$ownerFound = true;
								}
							}

							$findReplace = new FindReplace();
							$findReplace->Add('/\[CUSTOMER\]/', sprintf("%s%s %s %s", ($enquiry->Customer->Contact->Parent->ID > 0) ? sprintf('%s<br />', $enquiry->Customer->Contact->Parent->Organisation->Name) : '', $enquiry->Customer->Contact->Person->Title, $enquiry->Customer->Contact->Person->Name, $enquiry->Customer->Contact->Person->LastName));
							$findReplace->Add('/\[TITLE\]/', $enquiry->Customer->Contact->Person->Title);
							$findReplace->Add('/\[FIRSTNAME\]/', $enquiry->Customer->Contact->Person->Name);
							$findReplace->Add('/\[LASTNAME\]/', $enquiry->Customer->Contact->Person->LastName);
							$findReplace->Add('/\[FULLNAME\]/', trim(str_replace("   ", " ", str_replace("  ", " ", sprintf("%s %s %s", $enquiry->Customer->Contact->Person->Title, $enquiry->Customer->Contact->Person->Name, $enquiry->Customer->Contact->Person->LastName)))));
							$findReplace->Add('/\[FAX\]/', $enquiry->Customer->Contact->Person->Fax);
							$findReplace->Add('/\[PHONE\]/', $enquiry->Customer->Contact->Person->Phone1);
							$findReplace->Add('/\[ADDRESS\]/', $enquiry->Customer->Contact->Person->Address->GetLongString());
							$findReplace->Add('/\[USERNAME\]/', sprintf("%s %s", $user->Person->Name, $user->Person->LastName));
							$findReplace->Add('/\[USEREMAIL\]/', $user->Username);
							$findReplace->Add('/\[USERPHONE\]/', sprintf('%s', (strlen(trim($user->Person->Phone1)) > 0) ? $user->Person->Phone1 : $user->Person->Phone2));
							$findReplace->Add('/\[EMAIL\]/', $enquiry->Customer->GetEmail());
							//Do not send out Password via Email - Plus, its impossible to get unencrypted version of password.
							//$findReplace->Add('/\[PASSWORD\]/', $enquiry->Customer->GetPassword());

							if($enquiry->Type->DeveloperKey == 'customerservices') {
								$findReplace->Add('/\[SALES\]/', sprintf('BLT Direct<br />Customer Services: %s<br />customerservices@bltdirect.com', Setting::GetValue('telephone_customer_services')));
							} else {
								if($ownerFound) {
									$findReplace->Add('/\[SALES\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', sprintf("%s %s", $owner->Person->Name, $owner->Person->LastName), $owner->Person->Phone1, $owner->Username));
								} else {
									$findReplace->Add('/\[SALES\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', Setting::GetValue('default_username'), Setting::GetValue('default_userphone'), Setting::GetValue('default_useremail')));
								}
							}

							$message = $findReplace->Execute($form->GetValue('draft_message_'.$enquiryLine->ID));

							switch($items[0]) {
								case 'postDraft':
									$enquiryLine->IsDraft = 'N';
									break;
							}

							$enquiryLine->Message = $message;
							$enquiryLine->Update();

							switch($items[0]) {
								case 'postDraft':
									new DataQuery(sprintf("UPDATE enquiry_line SET Created_On=NOW() WHERE Enquiry_Line_ID=%d", mysql_real_escape_string($enquiryLine->ID)));

									if(($enquiryLine->IsCustomerMessage == 'N') && ($enquiryLine->IsPublic == 'Y')) {
										$enquiryType = $_REQUEST['categorytype'];
										$enquiryLine->SendResponse($enquiryType);
									}

									if($enquiry->OwnedBy != $GLOBALS['SESSION_USER_ID']) {
										$enquiryType = $_REQUEST['categorytype'];
										$enquiry->SendNotification($enquiryType);
									}

									redirect(sprintf("Location: %s?enquiryid=%d", $_SERVER['PHP_SELF'], $enquiry->ID));

									break;
								case 'saveDraft':
									redirect(sprintf("Location: %s?enquiryid=%d", $_SERVER['PHP_SELF'], $enquiry->ID));
									break;
							}
						}

						break;
				}
			}
		}
	}
}

$script = '<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>';
$script .= '<script language="javascript" type="text/javascript" src="js/scw.js"></script>';
$script .= '<script language="javascript" type="text/javascript" src="lib/spellcheck/spellChecker.js"></script>';

$script .= sprintf('<script language="javascript" type="text/javascript">
	var parseResponseHandler = function(response) {
		tinyMCE.execInstanceCommand(\'mceFocus\', false, \'message\');
		tinyMCE.activeEditor.setContent(response);
	}

	var parseRequest = new HttpRequest();
	parseRequest.setCaching(false);
	parseRequest.setHandlerResponse(parseResponseHandler);

	var parseDocument = function(id) {
		parseRequest.abort();
		parseRequest.get(\'lib/util/parseEnquiryDocument.php?id=\' + id + \'&customerid=%d&enquiryid=%d&userid=%d\');
	}

	var parseTemplate = function(id) {
		parseRequest.abort();
		parseRequest.get(\'lib/util/parseEnquiryTemplate.php?id=\' + id + \'&customerid=%d&enquiryid=%d&userid=%d\');
	}
	</script>', $enquiry->Customer->ID, $enquiry->ID, $session->UserID, $enquiry->Customer->ID, $enquiry->ID, $session->UserID);

$script .= sprintf('<script language="javascript" type="text/javascript">
	var templateResponseHandler = function(details) {
		var items = details.split("{br}\n");

		parseTemplate(items[0]);
	}

	var templateRequest = new HttpRequest();
	templateRequest.setCaching(false);
	templateRequest.setHandlerResponse(templateResponseHandler);

	var populateResponse = function(obj) {
		if(obj.value == 0) {
			tinyMCE.execInstanceCommand(\'mceFocus\', false, \'message\');
			tinyMCE.activeEditor.setContent(\'\');
		} else {
			templateRequest.abort();
			templateRequest.get(\'lib/util/getEnquiryTemplate.php?id=\' + obj.value);
		}
	}
	</script>');

$script .= sprintf('<script language="javascript" type="text/javascript">
	var documentResponseHandler = function(details) {
		var items = details.split("{br}\n");

		parseDocument(items[0]);
	}

	var documentRequest = new HttpRequest();
	documentRequest.setCaching(false);
	documentRequest.setHandlerResponse(documentResponseHandler);

	var foundDocument = function(id, title) {
		documentRequest.abort();
		documentRequest.get(\'lib/util/getDocument.php?id=\' + id);
	}
	</script>');

$script .= sprintf('<script language="javascript" type="text/javascript">
	var productResponseHandler = function(details) {
		var items = details.split("{br}\n");
		var text = \'<a href="http://www.bltdirect.com/product.php?pid=\' + items[0] + \'">\' + items[1] + \'</a>\';
		
		tinyMCE.execInstanceCommand(\'mceFocus\', false, \'message\');
		tinyMCE.activeEditor.execCommand("mceInsertRawHTML", false, text);

		var e = document.getElementById(\'product\');
		if(e) {
			e.value = \'\';
		}
	}

	var productResponseError = function() {
		alert(\'The Product ID could not be found.\');
	}

	var productRequest = new HttpRequest();
	productRequest.setCaching(false);
	productRequest.setHandlerResponse(productResponseHandler);
	productRequest.setHandlerError(productResponseError);

	var insertAtCursor = function() {
		var e = document.getElementById(\'product\');

		if(e) {
			if(e.value.length == 0) {
				alert(\'Product ID requies a value.\');
			} else {
				productRequest.get(\'lib/util/getProduct.php?id=\' + e.value);
			}
		}
	}
	</script>');

$script .= sprintf('<script language="javascript" type="text/javascript">
	var foundProduct = function(pid) {
		var e = document.getElementById(\'product\');
		if(e) {
			e.value = pid;
		}
	}
	</script>');

$script .= sprintf('<script language="javascript" type="text/javascript">
	var changeType = function(obj) {
		window.self.location.href = \'%s?&action=changetype&typeid=\' + obj.value + \'&enquiryid=%d\';
	}
	</script>', $_SERVER['PHP_SELF'], $enquiry->ID);

$script .= sprintf('<script language="javascript" type="text/javascript">
	var changePending = function(obj) {
		window.self.location.href = \'%s?&action=changepending&pending=\' + obj.value + \'&enquiryid=%d\';
	}
	</script>', $_SERVER['PHP_SELF'], $enquiry->ID);

$script .= sprintf('<script language="javascript" type="text/javascript">
	var changeOwner = function(obj) {
		window.self.location.href = \'%s?&action=changeowner&ownerid=\' + obj.value + \'&enquiryid=%d\';
	}
	</script>', $_SERVER['PHP_SELF'], $enquiry->ID);

$script .= sprintf('<script language="javascript" type="text/javascript">
	var setBigEnquiry = function(isBig) {
		window.self.location.href = \'%s?&action=setbigenquiry&isbig=\' + isBig + \'&enquiryid=%d\';
	}
	</script>', $_SERVER['PHP_SELF'], $enquiry->ID);

$script .= sprintf('<script language="javascript" type="text/javascript">
	var setTradeEnquiry = function(isTrade) {
		window.self.location.href = \'%s?&action=settradeenquiry&istrade=\' + isTrade + \'&enquiryid=%d\';
	}
	</script>', $_SERVER['PHP_SELF'], $enquiry->ID);

$script .= sprintf('<script language="javascript" type="text/javascript">
	var setIsOrdered = function(isOrdered) {
		window.self.location.href = \'%s?&action=setisordered&isordered=\' + isOrdered + \'&enquiryid=%d\';
	}
	</script>', $_SERVER['PHP_SELF'], $enquiry->ID);

$script .= sprintf('<script language="javascript" type="text/javascript">
	function openSpellChecker() {
		var speller = new spellChecker();
		speller.checkTextAreas();
	}
	</script>');

$page = new Page(sprintf('%s%d Enquiry Details for %s', $enquiry->GetPrefix(), $enquiry->ID, $customerName), 'Listing all posts between staff and customer recipients for this enquiry.');
$page->AddToHead('<link rel="stylesheet" type="text/css" href="css/m_enquiries.css" />');
$page->AddToHead($script);
$page->SetEditor(true);
$page->Display('header');

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('enquiryid');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}
?>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td valign="top">

			<?php
			if($messages > 0) {
				?>

				<table width="100%">
					<tr>
						<td class="enquiryBlock">
							<p><span class="pageSubTitle">Enquiry Messages</span><br /><span class="pageDescription">Messages of this enquiry are listed below.</span></p>

							<?php
							$user = new User();

							for($i=0;$i<count($enquiry->Line);$i++) {
								if($enquiry->Line[$i]->IsDraft == 'N') {
									if($enquiry->Line[$i]->IsCustomerMessage == 'Y') {
										$author = sprintf('%s%s', $customerName, ($enquiry->Customer->Contact->Parent->ID > 0) ? sprintf(' @ %s', $orgName) : '');
									} else {
										$user->ID = $enquiry->Line[$i]->CreatedBy;
										$user->Get();

										$author = sprintf('%s @ %s', trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)), $GLOBALS['COMPANY']);
									}
									?>

									<div style="background-color: <?php echo ($enquiry->Line[$i]->IsPublic == 'Y') ? '#fff' : '#eee'; ?>; padding: 10px;">
									 	<div style="float: right;">
									 		<?php
									 		if($enquiry->Line[$i]->CreatedBy > 0) {
									 			$user->ID = $enquiry->Line[$i]->CreatedBy;
									 			$user->Get();

									 			echo sprintf('<span class="enquiryLightText"><em>Posted By: %s</em></span><br />', trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)));
									 		} else {
									 			echo sprintf('<span class="enquiryLightText"><em>Posted By: %s</em></span><br />', $customerName);
									 		}
									 		?>
									 	</div>
									 	<p>
									 		<strong><?php print $author; ?> said:</strong><br />
									 		<em><?php print $enquiry->Line[$i]->CreatedOn; ?></em>
										</p>
										<div style="clear: both;"></div>

									 	<?php
									 	echo sprintf('<p>%s</p>', nl2br($enquiry->Line[$i]->Message));

									 	if(count($enquiry->Line[$i]->Quotes) > 0) {
									 		echo '<p><strong><em>Attached Quotes:</em></strong></p>';
									 		echo '<ul>';

									 		foreach($enquiry->Line[$i]->Quotes as $quote) {
									 			$quote->Quote->Get();
									 			echo sprintf('<li><a href="quote_details.php?quoteid=%d">%s%s</a> (%s)</li>', $quote->Quote->ID, $quote->Quote->Prefix, $quote->Quote->ID, cDatetime($quote->Quote->CreatedOn, 'shortdatetime'));
									 		}

									 		echo '</ul>';
									 		echo '<br />';
									 	}

									 	if(count($enquiry->Line[$i]->Documents) > 0) {
									 		$lines = array();

									 		foreach($enquiry->Line[$i]->Documents as $document) {
									 			if(!empty($document->File->FileName) && file_exists($GLOBALS['ENQUIRY_DOCUMENT_DIR_FS'].$document->File->FileName)) {
									 				$lines[] = sprintf('<div style="padding: 0 0 0 20px;"><a %s href="enquiry_download.php?documentid=%d" target="_blank">%s</a> (%s bytes)</div>', ($document->IsPublic == 'N') ? 'class="enquiryHiddenDocument"' : '', $document->ID, $document->File->FileName, number_format(filesize($GLOBALS['ENQUIRY_DOCUMENT_DIR_FS'].$document->File->FileName), 0, '.', ','));
									 			} else {
									 				$data = new DataQuery(sprintf("DELETE FROM enquiry_line_document WHERE Enquiry_Line_Document_ID=%d", mysql_real_escape_string($document->ID)));
									 				$data->Disconnect();
									 			}
									 		}

									 		if(count($lines) > 0) {
									 			echo '<p><strong><em>Attached Documents:</em></strong></p>';

									 			foreach($lines as $line) {
									 				echo $line;
									 			}

									 			echo '<br />';
									 		}
									 	}
									 	?>

									 </div>
									 <br />

									 <?php
								}
							}
							?>

						</td>
					</tr>
				</table><br />

				<?php
			}

			if($drafts > 0) {
				?>

				<table width="100%">
					<tr>
						<td class="enquiryBlock">
							<p><span class="pageSubTitle">Enquiry Drafts</span><br /><span class="pageDescription">Drafted messages of this enquiry are listed below.</span></p>

							<?php
							$user = new User();

							for($i=0;$i<count($enquiry->Line);$i++) {
								if($enquiry->Line[$i]->IsDraft == 'Y') {
									if($enquiry->Line[$i]->IsCustomerMessage == 'Y') {
										$author = sprintf('%s%s', $customerName, ($enquiry->Customer->Contact->Parent->ID > 0) ? sprintf(' @ %s', $orgName) : '');
									} else {
										$user->ID = $enquiry->Line[$i]->CreatedBy;
										$user->Get();

										$author = sprintf('%s @ %s', trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)), $GLOBALS['COMPANY']);
									}
									?>

									<table width="100%">
										<tr>
											<td>
												 <div style="background-color: <?php echo ($enquiry->Line[$i]->IsPublic == 'Y') ? '#fff' : '#eee'; ?>; padding: 10px;">
												 	<div style="float: right;">
												 		<?php
												 		$user->ID = $enquiry->Line[$i]->ModifiedBy;
												 		$user->Get();

												 		echo sprintf('<span class="enquiryLightText"><em>Last Modified By: %s</em></span>&nbsp;', trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)));
													 	?>

														<a href="javascript:confirmRequest('enquiry_details.php?action=removeline&enquiryid=<?php echo $enquiry->ID; ?>&enquirylineid=<?php echo $enquiry->Line[$i]->ID; ?>', 'Are you sure you wish to remove this draft?');"><img border="0" src="images/icon_cross_3.gif" alt="Remove Draft" align="absmiddle" /></a>
												 	</div>
												 	<p>
												 		<strong><?php print $author; ?> drafted:</strong><br />
												 		<em><?php print $enquiry->Line[$i]->CreatedOn; ?></em>
												 	</p>
												 	<div style="clear: both;"></div>

												 	<?php
												 	echo sprintf('<fieldset style="border: none; padding: 0;">%s</fieldset><br />', $form->GetHTML('draft_message_'.$enquiry->Line[$i]->ID));
												 	?>

													<input type="submit" name="postDraft-<?php echo $enquiry->Line[$i]->ID; ?>" value="post draft" class="btn" />
													<input type="submit" name="saveDraft-<?php echo $enquiry->Line[$i]->ID; ?>" value="save draft" class="btn" />
												 	<br /><br />

												 	<?php
												 	if(count($enquiry->Line[$i]->Quotes) > 0) {
												 		echo '<p><strong><em>Attached Quotes:</em></strong></p>';

												 		foreach($enquiry->Line[$i]->Quotes as $quote) {
												 			$quote->Quote->Get();

												 			echo sprintf('<div style="padding: 0 0 0 20px;"><a href="javascript:confirmRequest(\'enquiry_details.php?action=removequote&enquiryid=%d&enquiryquoteid=%d\', \'Are you sure you wish to remove this quote?\');"><img border="0" src="images/icon_cross_3.gif" alt="Remove Quote" align="absmiddle" /></a> <a href="quote_details.php?quoteid=%d">%s%s</a> (%s)</div>', $enquiry->ID, $quote->ID, $quote->Quote->ID, $quote->Quote->Prefix, $quote->Quote->ID, cDatetime($quote->Quote->CreatedOn, 'shortdatetime'));
												 		}

												 		echo '<br />';
												 	}

												 	if(count($enquiry->Line[$i]->Documents) > 0) {
												 		$lines = array();

												 		foreach($enquiry->Line[$i]->Documents as $document) {
												 			if(!empty($document->File->FileName) && file_exists($GLOBALS['ENQUIRY_DOCUMENT_DIR_FS'].$document->File->FileName)) {
												 				$lines[] = sprintf('<div style="padding: 0 0 0 20px;"><a href="javascript:confirmRequest(\'enquiry_details.php?action=removedocument&enquiryid=%d&enquirydocumentid=%d\', \'Are you sure you wish to remove this document?\');"><img border="0" src="images/icon_cross_3.gif" alt="Remove Document" align="absmiddle" /></a> <a %s href="enquiry_download.php?documentid=%d" target="_blank">%s</a> (%s bytes)</div>', $enquiry->ID, $document->ID, ($document->IsPublic == 'N') ? 'class="enquiryHiddenDocument"' : '', $document->ID, $document->File->FileName, number_format(filesize($GLOBALS['ENQUIRY_DOCUMENT_DIR_FS'].$document->File->FileName), 0, '.', ','));
												 			} else {
												 				$data = new DataQuery(sprintf("DELETE FROM enquiry_line_document WHERE Enquiry_Line_Document_ID=%d", mysql_real_escape_string($document->ID)));
												 				$data->Disconnect();
												 			}
												 		}

												 		if(count($lines) > 0) {
												 			echo '<p><strong><em>Attached Documents:</em></strong></p>';

												 			foreach($lines as $line) {
												 				echo $line;
												 			}

												 			echo '<br />';
												 		}
												 	}
												 	?>

												 	<hr style="background-color: <?php echo ($enquiry->Line[$i]->IsPublic == 'N') ? '#fff' : '#eee'; ?>; color: <?php echo ($enquiry->Line[$i]->IsPublic == 'N') ? '#fff' : '#eee'; ?>; height: 1px;" />
												 	<div style="text-align: right;">
														<p style="padding: 0; margin: 0;"><a href="enquiry_add_product_downloads.php?enquirylineid=<?php echo $enquiry->Line[$i]->ID; ?>">Attach Product Downloads</a> | <a href="enquiry_add_quote.php?enquirylineid=<?php echo $enquiry->Line[$i]->ID; ?>">Attach Quote</a> | <a href="enquiry_add_document.php?enquirylineid=<?php echo $enquiry->Line[$i]->ID; ?>">Attach Document</a></p>
													</div>
												 </div>
											</td>
										</tr>
									</table>

									<br />

									<?php
								}
							}
							?>

						</td>
					</tr>
				</table><br />

				<?php
			}

			if($enquiry->Status != 'Closed') {
				?>

				<table width="100%">
					<tr>
						<td class="enquiryBlock">
							<p><span class="pageSubTitle">New Message</span><br /><span class="pageDescription">Use the following form to submit a response to this enquiry. Public responses will be e-mailed to the customer.</span></p>

							<?php
							if($templateCount > 0) {
								echo sprintf('<strong>%s</strong><br />%s<br /><br />', $form->GetLabel('template'), $form->GetHTML('template'));
							}

							echo sprintf('<strong>%s</strong> <a href="javascript:popUrl(\'popFindDocument.php?callback=foundDocument\', 650, 500);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a><br /><fieldset style="border: none; padding: 0;">%s</fieldset><br />', $form->GetLabel('message'), $form->GetHTML('message'));
							?>

							<table border="0" cellpadding="0" cellspacing="0">
								<tr>
									<td valign="top">
										<strong><?php echo $form->GetLabel('product'); ?></strong> <a href="javascript:popUrl('popFindProduct.php?callback=foundProduct', 650, 500);"><img src="images/icon_search_1.gif" alt="Add product" width="16" height="16" align="absmiddle" border="0" /></a><br />
										<?php echo $form->GetHTML('product'); ?> <a href="javascript:insertAtCursor();"><img src="images/icon_edit_1.gif" alt="Add product" width="16" height="16" align="absmiddle" border="0" /></a>
									</td>
								</tr>
							</table><br />

							<?php echo $form->GetHTML('public'); ?> <strong><?php echo $form->GetLabel('public'); ?></strong> (Check this box if the customer is allowed to view this message)<br /><br />
							<?php echo $form->GetHTML('customermessage'); ?> <strong><?php echo $form->GetLabel('customermessage'); ?></strong> (Check this box to post this message as if authored by the customer)<br /><br />
							<?php echo $form->GetHTML('pending'); ?> <strong><?php echo $form->GetLabel('pending'); ?></strong> (Check this box if this enquiry requires further action)<br /><br />

							<input type="submit" name="post" value="post message" class="btn" />
							<input type="submit" name="draft" value="save draft" class="btn" />
							<input type="button" name="spellcheck" value="check spelling" class="btn" onclick="openSpellChecker();" />
							<br /><br />

							<?php
							if(isset($_SESSION['Enquiries']) && isset($_SESSION['Enquiries'][$enquiry->ID])) {
								if(isset($_SESSION['Enquiries'][$enquiry->ID]['Quotes']) && (count($_SESSION['Enquiries'][$enquiry->ID]['Quotes']) > 0)) {
									echo '<p><strong><em>Attached Quotes:</em></strong></p>';

									$quote = new Quote();

									foreach($_SESSION['Enquiries'][$enquiry->ID]['Quotes'] as $quoteItem) {
										$quote->Get($quoteItem);
										echo sprintf('<div style="padding: 0 0 0 20px;"><a href="javascript:confirmRequest(\'enquiry_details.php?action=removesessionquote&enquiryid=%d&quoteid=%d\', \'Are you sure you wish to remove this quote?\');"><img border="0" src="images/icon_cross_3.gif" alt="Remove Quote" align="absmiddle" /></a> <a href="quote_details.php?quoteid=%d">%s%s</a> (%s)</div>', $enquiry->ID, $quote->ID, $quote->ID, $quote->Prefix, $quote->ID, cDatetime($quote->CreatedOn, 'shortdatetime'));

										if((count($sessionQuoteDocuments) > 0) && isset($sessionQuoteDocuments[$quoteItem])) {
											foreach($sessionQuoteDocuments[$quoteItem] as $documentItem) {
												echo sprintf('<div style="padding: 0 0 0 40px;">%s <a %s href="quote_download.php?documentid=%d" target="_blank">%s</a> (%s bytes)</div>', $form->GetHTML(sprintf('session_quote_%d_document_%d', $quoteItem, $documentItem['Quote_Document_ID'])), ($documentItem['Is_Public'] == 'N') ? 'class="enquiryHiddenDocument"' : '', $documentItem['Quote_Document_ID'], $documentItem['File_Name'], number_format(filesize($GLOBALS['QUOTE_DOCUMENT_DIR_FS'].$documentItem['File_Name']), 0, '.', ','));
											}
										}
									}

									echo '<br />';
								}

								if(isset($_SESSION['Enquiries'][$enquiry->ID]['Documents']) && (count($_SESSION['Enquiries'][$enquiry->ID]['Documents']) > 0)) {
									$lines = array();

									foreach($_SESSION['Enquiries'][$enquiry->ID]['Documents'] as $key=>$documentItem) {
										if(!empty($documentItem['FileName']) && file_exists($GLOBALS['TEMP_ENQUIRY_DOCUMENT_DIR_FS'].$documentItem['FileName'])) {
											$lines[] = sprintf('<div style="padding: 0 0 0 20px;"><a href="javascript:confirmRequest(\'enquiry_details.php?action=removesessiondocument&enquiryid=%d&document=%s\', \'Are you sure you wish to remove this document?\');"><img border="0" src="images/icon_cross_3.gif" alt="Remove Document" align="absmiddle" /></a> <a %s href="%s" target="_blank">%s</a> (%s bytes)</div>', $enquiry->ID, $documentItem['FileName'], ($documentItem['Public'] == 'N') ? 'class="enquiryHiddenDocument"' : '', $GLOBALS['TEMP_ENQUIRY_DOCUMENT_DIR_WS'].$documentItem['FileName'], $documentItem['FileName'], number_format(filesize($GLOBALS['TEMP_ENQUIRY_DOCUMENT_DIR_FS'].$documentItem['FileName']), 0, '.', ','));
										} else {
											unset($_SESSION['Enquiries'][$enquiry->ID]['Documents'][$key]);
										}
									}

									if(count($lines) > 0) {
										echo '<p><strong><em>Attached Documents:</em></strong></p>';

										foreach($lines as $line) {
											echo $line;
										}

										echo '<br />';
									}
								}
							}
						 	?>

							<hr style="background-color: #eee; color: #eee; height: 1px;" />
						 	<div style="text-align: right;">
								<p style="padding: 0; margin: 0;"><a href="enquiry_add_product_downloads.php?enquiryid=<?php echo $enquiry->ID; ?>">Attach Product Downloads</a> | <a href="enquiry_add_quote.php?enquiryid=<?php echo $enquiry->ID; ?>">Attach Quote</a> | <a href="enquiry_add_document.php?enquiryid=<?php echo $enquiry->ID; ?>">Attach Document</a></p>
							</div>

						</td>
					</tr>
				</table>

				<?php
			}
			?>

		</td>
		<td width="15"></td>
		<td valign="top" width="300">
			<?php
			$status = $enquiry->Status;

			if($enquiry->Status != 'Closed') {
				if($enquiry->IsRequestingClosure == 'Y') {
					$status .= ' (Request Closing)';
				} elseif($enquiry->IsPendingAction == 'N') {
					$status .= ' (Awaiting Response)';
				}
			}
			?>

			<div style="background-color: #f6f6f6; padding: 10px;">
				<p><span class="pageSubTitle">Enquiry Info</span><br /><span class="pageDescription">Change the type of this enquiry here.</span></p>

				<table cellpadding="0" cellspacing="0" border="0" class="enquiryForm">
					<tr>
						<td><p><strong>Channel:</strong></p></td>
						<td><p><?php echo $enquiry->Channel->Name; ?></p></td>
					</tr>
					<tr>
						<td><p><strong>Subject:</strong></p></td>
						<td><p><?php print ucfirst($enquiry->Subject); ?></p></td>
					</tr>
					<tr>
						<td><p><strong>Reference:</strong></p></td>
						<td><p><?php print $enquiry->GetPrefix().$enquiry->ID; ?></p></td>
					</tr>
					<tr>
						<td><p><strong>Status:</strong></p></td>
						<td>
							<?php
							if(($enquiry->Status == 'Closed') && ($enquiry->ClosedType->ID > 0)) {
								echo sprintf('<p>%s (%s)</p>', $enquiry->Status, $enquiry->ClosedType->Name);
							} else {
								echo sprintf('<p>%s</p>', $status);
							}
							?>
						</td>
					</tr>
					<tr>
						<td><p><strong>Send To:</strong></p></td>
						<td><p><?php print $form->GetHTML('sendto'); ?></p></td>
					</tr>
					<tr>
						<td><p><strong>Category:</strong></p></td>
						<td><p><?php print $form->GetHTML('categorytype'); ?></p></td>
					</tr>
					<tr>
						<td><p><strong>Owned By:</strong></p></td>
						<td><p><?php print $form->GetHTML('owner'); ?></p></td>
					</tr>
					<tr>
						<td><p><strong>Big Enquiry:</strong></p></td>
						<td><p><?php print $form->GetHTML('bigenquiry'); ?></p></td>
					</tr>
					<tr>
						<td><p><strong>Trade Enquiry:</strong></p></td>
						<td><p><?php print $form->GetHTML('tradeenquiry'); ?></p></td>
					</tr>
					<tr>
						<td><p><strong>Is Ordered:</strong></p></td>
						<td><p><?php print $form->GetHTML('isordered'); ?></p></td>
					</tr>

					<?php
					if($enquiry->Rating > 0) {
						$rating = number_format($enquiry->Rating, 0, '', '');
						$ratingImg = '';

						for($i=0;$i<$rating;$i++) {
							$ratingImg .= sprintf('<img src="images/enquiry_rating_on.gif" align="absmiddle" height="15" width="16" alt="%d out of 5" />', $rating);
						}
						for($i=$rating;$i<5;$i++) {
							$ratingImg .= sprintf('<img src="images/enquiry_rating_off.gif" align="absmiddle" height="15" width="16" alt="%d out of 5" />', $rating);
						}
						?>

						<tr>
							<td><p><strong>Rating:</strong></p></td>
							<td><p><?php print $ratingImg; ?></p></td>
						</tr>

						<tr>
							<td><p><strong>Customer Comment:</strong></p></td>
							<td><p><?php print nl2br($enquiry->RatingComment); ?></p></td>
						</tr>

						<?php
					}
					?>
				</table>

				<?php
				if($enquiry->Status != 'Closed') {
					if($enquiry->IsRequestingClosure == 'N') {
						echo '<input type="submit" name="closing" value="request close" class="btn" />&nbsp;';
					}

					echo '<input type="submit" name="close" value="close enquiry" class="btn" />';
				}
				?>

			</div>
			<br />

			<?php
			$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM enquiry WHERE Customer_ID=%d", mysql_real_escape_string($enquiry->Customer->ID)));
			$enquiryCount = $data->Row['Count'];
			$data->Disconnect();
			?>

			<div style="background-color: #f6f6f6; padding: 10px;">
				<p><span class="pageSubTitle">Customer Info</span><br /><span class="pageDescription">Contact details for this customer.</span></p>

				<table cellpadding="0" cellspacing="0" border="0" class="enquiryForm">
					<tr>
						<td><p><strong>Customer:</strong></p></td>
						<td><p><a href="contact_profile.php?cid=<?php print $enquiry->Customer->Contact->ID; ?>"><?php print $customerName; ?></a></p></td>
					</tr>
					<tr>
						<td><p><strong>Phone:</strong></p></td>
						<td><p><?php print $enquiry->Customer->Contact->Person->Phone1; ?></p></td>
					</tr>
					<tr>
						<td><p><strong>Email:</strong></p></td>
						<td><p><?php print $enquiry->Customer->GetEmail(); ?></p></td>
					</tr>
					<tr>
						<td><p><strong>Enquiries:</strong></p></td>
						<td><p><?php print $enquiryCount; ?></p></td>
					</tr>
					<tr>
						<td><p><strong>Catalogue Sent:</strong></p></td>
						<td><p><?php
						if($enquiry->Customer->Contact->CatalogueSentOn != '0000-00-00 00:00:00'){
						    echo "Catalogue sent on " . cDatetime($enquiry->Customer->Contact->CatalogueSentOn, 'shortdate');
						    if($enquiry->Customer->Contact->IsCatalogueRequested == "Y"){
							echo " (New One Requested - <span>". sprintf('<a href="?action=cancelrequestcatalogue&enquiryid=%1$d">Cancel Request</a>', $enquiry->ID) . "</span>)";
						    }else{
							echo sprintf(' - <span><a href="?action=requestcatalogue&enquiryid=%1$d">Request New Catalogue</a>', $enquiry->ID) . "</span>";
						    }
						}else if($enquiry->Customer->Contact->IsCatalogueRequested == "Y"){
						    echo "Never sent but requested &nbsp;<span> " . sprintf('<a href="?action=cancelrequestcatalogue&enquiryid=%1$d">Cancel Request</a>', $enquiry->ID) . "</span>";
						}else{

						    echo '<em>&lt;Never&gt;</em>&nbsp;<span>' . sprintf('<a href="?action=requestcatalogue&enquiryid=%1$d">Request Catalogue</a>', $enquiry->ID) . "</span>";
						}
						?></p></td>
					</tr>
				</table>

			</div>
			<br />

			<?php
			if($enquiry->Status != 'Closed') {
				$enquiries = array();

				$data = new DataQuery(sprintf("SELECT e.Enquiry_ID, e.Prefix, et.Name AS Type FROM enquiry AS e INNER JOIN enquiry_type AS et ON e.Enquiry_Type_ID=et.Enquiry_Type_ID WHERE e.Customer_ID=%d AND e.Enquiry_ID<>%d AND (e.Status LIKE 'Unread' OR e.Status LIKE 'Open')", mysql_real_escape_string($enquiry->Customer->ID), mysql_real_escape_string($enquiry->ID)));
				while($data->Row) {
					$enquiries[] = $data->Row;

					$data->Next();
				}
				$data->Disconnect();

				if(count($enquiries) > 0) {
					?>

					<div style="background-color: #f6f6f6; padding: 10px;">
						<p><span class="pageSubTitle">Customer Enquiries</span><br /><span class="pageDescription">List of all other open enquiries for this customer.</span></p>

						<ul>
							<?php
							foreach($enquiries as $enquiryItem) {
								echo sprintf('<li><a href="enquiry_details.php?enquiryid=%d">%s%d</a> (%s)</li>', $enquiryItem['Enquiry_ID'], $enquiryItem['Prefix'], $enquiryItem['Enquiry_ID'], $enquiryItem['Type']);
							}
							?>
						</ul>
					</div>
					<br />

					<?php
				}
				?>

				<div style="background-color: #f6f6f6; padding: 10px;">
					<p><span class="pageSubTitle">Review Info</span><br /><span class="pageDescription">Mark this enquiry for reviewing. Selecting a review date will take preference over review period.</span></p>

					<table cellpadding="0" cellspacing="0" border="0" class="enquiryForm">
						<?php
						if($enquiry->ReviewOn > '0000-00-00 00:00:00') {
							?>

							<tr>
								<td><p><strong>Scheduled:</strong></p></td>
								<td><p><?php echo cDatetime($enquiry->ReviewOn, 'shortdatetime'); ?> <a href="javascript:confirmRequest('enquiry_details.php?action=removeschedule&enquiryid=<?php echo $enquiry->ID; ?>', 'Are you sure you wish to remove this schedule?');"><img border="0" src="images/icon_cross_3.gif" alt="Remove Schedule" align="absmiddle" /></a></p></td>
							</tr>
							<tr>
								<td colspan="2"><hr style="background-color: #eee; color: #eee; height: 1px;" /><br /></td>
							</tr>

							<?php
						}
						?>

						<tr>
							<td><p><strong>Set Period:</strong></p></td>
							<td><p><?php print $form->GetHTML('reviewperiod'); ?></p></td>
						</tr>
						<tr>
							<td><p><strong>Set Date:</strong></p></td>
							<td><p><?php print $form->GetHTML('reviewdate'); ?></p></td>
						</tr>
						<tr>
							<td colspan="2"><hr style="background-color: #eee; color: #eee; height: 1px;" /><br /></td>
						</tr>
						<tr>
							<td><p><strong>Set Time:</strong></p></td>
							<td><p><?php print $form->GetHTML('reviewtime'); ?></p></td>
						</tr>
					</table>

					<input type="submit" class="btn" name="review" value="review enquiry" />
				</div>
				<br />

				<?php
			}

			$start = $enquiry->CreatedOn;
			$end = $enquiry->ClosedOn;

			if($enquiry->Status != 'Closed') {
				$end = date('Y-m-d H:i:s');
			}

			$orders = array();
			$quotes = array();

			if($enquiry->Customer->Contact->Parent->ID > 0) {
				$data = new DataQuery(sprintf("SELECT o.* FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE n.Parent_Contact_ID=%d AND o.Created_On BETWEEN '%s' AND '%s'", mysql_real_escape_string($enquiry->Customer->Contact->Parent->ID), mysql_real_escape_string($start), mysql_real_escape_string($end)));
				if($data->TotalRows > 0) {
					while($data->Row) {
						$orders[] = $data->Row;

						$data->Next();
					}
				}
				$data->Disconnect();
			} else {
				$data = new DataQuery(sprintf("SELECT * FROM orders WHERE Customer_ID=%d AND Created_On BETWEEN '%s' AND '%s'",mysql_real_escape_string ($enquiry->Customer->ID), mysql_real_escape_string($start), mysql_real_escape_string($end)));
				if($data->TotalRows > 0) {
					while($data->Row) {
						$orders[] = $data->Row;

						$data->Next();
					}
				}
				$data->Disconnect();
			}

			if($enquiry->Customer->Contact->Parent->ID > 0) {
				$data = new DataQuery(sprintf("SELECT q.* FROM quote AS q INNER JOIN customer AS c ON c.Customer_ID=q.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE n.Parent_Contact_ID=%d AND q.Created_On BETWEEN '%s' AND '%s'", mysql_real_escape_string($enquiry->Customer->Contact->Parent->ID), mysql_real_escape_string($start), mysql_real_escape_string($end)));
				if($data->TotalRows > 0) {
					while($data->Row) {
						$quotes[] = $data->Row;

						$data->Next();
					}
				}
				$data->Disconnect();
			} else {
				$data = new DataQuery(sprintf("SELECT * FROM quote WHERE Customer_ID=%d AND Created_On BETWEEN '%s' AND '%s'", mysql_real_escape_string($enquiry->Customer->ID), mysql_real_escape_string($start), mysql_real_escape_string($end)));
				if($data->TotalRows > 0) {
					while($data->Row) {
						$quotes[] = $data->Row;

						$data->Next();
					}
				}
				$data->Disconnect();
			}

			if(count($orders) > 0) {
				?>

				<div style="background-color: #f6f6f6; padding: 10px;">
					<p><span class="pageSubTitle">Orders Processed</span><br /><span class="pageDescription">Listing orders processed for this customer during the period of this enquiry.</span></p>
					<ul>
						<?php
						foreach($orders as $orderItem) {
							echo sprintf('<li><a href="order_details.php?orderid=%d">%s%d</a> (%s)</li>', $orderItem['Order_ID'], $orderItem['Order_Prefix'], $orderItem['Order_ID'], cDatetime($orderItem['Created_On'], 'shortdatetime'));
						}
						?>
					</ul>
				</div><br />

				<?php
			}

			if(count($quotes) > 0) {
				?>

				<div style="background-color: #f6f6f6; padding: 10px;">
					<p><span class="pageSubTitle">Quotes Processed</span><br /><span class="pageDescription">Listing quotes processed for this customer during the period of this enquiry.</span></p>
					<ul>
						<?php
						foreach($quotes as $quoteItem) {
							echo sprintf('<li><a href="quote_details.php?quoteid=%d">%s%d</a> (%s)</li>', $quoteItem['Quote_ID'], $quoteItem['Quote_Prefix'], $quoteItem['Quote_ID'], cDatetime($quoteItem['Created_On'], 'shortdatetime'));
						}
						?>
					</ul>
				</div><br />

				<?php
			}
			?>

			<div style="background-color: #f6f6f6; padding: 10px;">
				<p><span class="pageSubTitle">Quick Links</span><br /><span class="pageDescription">Use these quick links to swiftly navigate to areas of this customers profile.</span></p>
				<p>
					<img src="images/enquiry_link.gif" alt="" width="16" height="16" align="absmiddle" /> <a href="<?php echo sprintf('enquiry_create.php?action=find&cuid=%d', $enquiry->Customer->ID); ?>">Create New Enquiry</a><br />
					<img src="images/enquiry_link.gif" alt="" width="16" height="16" align="absmiddle" /> <a href="<?php echo sprintf('order_create.php?action=find&cuid=%d', $enquiry->Customer->ID); ?>">Create New Order/Quote</a><br />
					<img src="images/enquiry_link.gif" alt="" width="16" height="16" align="absmiddle" /> <a href="<?php echo sprintf('return_add.php?cid=%d', $enquiry->Customer->Contact->ID); ?>">Create New Return</a><br />

					<?php
					$data = new DataQuery(sprintf("select count(*) as Counter from orders where Customer_ID=%d", mysql_real_escape_string($enquiry->Customer->ID)));
					$orderCount = $data->Row['Counter'];
					$data->Disconnect();

					$data = new DataQuery(sprintf("select count(*) as Counter from quote where Customer_ID=%d", mysql_real_escape_string($enquiry->Customer->ID)));
					$quoteCount = $data->Row['Counter'];
					$data->Disconnect();

					$data = new DataQuery(sprintf("select count(*) as Counter from invoice where Customer_ID=%d", mysql_real_escape_string($enquiry->Customer->ID)));
					$invoiceCount = $data->Row['Counter'];
					$data->Disconnect();

					$data = new DataQuery(sprintf("select count(*) as Counter from `return` where Customer_ID=%d", mysql_real_escape_string($enquiry->Customer->ID)));
					$returnCount = $data->Row['Counter'];
					$data->Disconnect();

					$data = new DataQuery(sprintf("select count(*) as Counter from credit_note as c INNER JOIN orders AS o ON o.Order_ID=c.Order_ID where o.Customer_ID=%d", mysql_real_escape_string($enquiry->Customer->ID)));
					$creditCount = $data->Row['Counter'];
					$data->Disconnect();

					$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM campaign_contact WHERE Contact_ID=%d", mysql_real_escape_string($enquiry->Customer->Contact->ID)));
					$campaignCount = $data->Row['Counter'];
					$data->Disconnect();

					$data = new DataQuery(sprintf("select count(*) as Counter from enquiry where Customer_ID=%d", mysql_real_escape_string($enquiry->Customer->ID)));
					$enquiryCount = $data->Row['Counter'];
					$data->Disconnect();
					?>

					<img src="images/enquiry_link.gif" alt="" width="16" height="16" align="absmiddle" /> <a href="<?php echo sprintf('customer_orders.php?customer=%d', $enquiry->Customer->ID); ?>">View Order History (<?php echo $orderCount; ?>)</a><br />
					<img src="images/enquiry_link.gif" alt="" width="16" height="16" align="absmiddle" /> <a href="<?php echo sprintf('customer_quotes.php?customer=%d', $enquiry->Customer->ID); ?>">View Quote History (<?php echo $quoteCount; ?>)</a><br />
					<img src="images/enquiry_link.gif" alt="" width="16" height="16" align="absmiddle" /> <a href="<?php echo sprintf('customer_invoices.php?customer=%d', $enquiry->Customer->ID); ?>">View Invoice History (<?php echo $invoiceCount; ?>)</a><br />
					<img src="images/enquiry_link.gif" alt="" width="16" height="16" align="absmiddle" /> <a href="<?php echo sprintf('customer_returns.php?customer=%d', $enquiry->Customer->ID); ?>">View Return History (<?php echo $returnCount; ?>)</a><br />
					<img src="images/enquiry_link.gif" alt="" width="16" height="16" align="absmiddle" /> <a href="<?php echo sprintf('customer_credits.php?customer=%d', $enquiry->Customer->ID); ?>">View Credit History (<?php echo $creditCount; ?>)</a><br />
					<img src="images/enquiry_link.gif" alt="" width="16" height="16" align="absmiddle" /> <a href="<?php echo sprintf('contact_campaigns.php?cid=%d', $enquiry->Customer->Contact->ID); ?>">View Campaigns (<?php echo $campaignCount; ?>)</a><br />
					<img src="images/enquiry_link.gif" alt="" width="16" height="16" align="absmiddle" /> <a href="<?php echo sprintf('customer_enquiries.php?customer=%d', $enquiry->Customer->ID); ?>">View Enquiries (<?php echo $enquiryCount; ?>)</a><br />

				</p>

			</div>
			<br />

		</td>
	</tr>
</table>

<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');
?>