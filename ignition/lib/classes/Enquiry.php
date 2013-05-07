<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryType.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryClosedType.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Setting.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Quote.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/EmailQueue.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Channel.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Template.php");

class Enquiry {
	var $ID;
	var $Channel;
	var $Prefix;
	var $Customer;
	var $Type;
	var $ClosedType;
	var $Subject;
	var $Status;
	var $IsPendingAction;
	var $IsRequestingClosure;
	var $IsBigEnquiry;
	var $IsTradeEnquiry;
	var $IsOrdered;
	var $Rating;
	var $RatingComment;
	var $OwnedBy;
	var $Line;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $ClosedOn;
	var $ReviewOn;

	function __construct($id = NULL) {
		$this->Prefix = 'W';
		$this->Type = new EnquiryType();
		$this->ClosedType = new EnquiryClosedType();
		$this->Customer = new Customer();
		$this->Line = array();
		$this->IsPendingAction = 'Y';
		$this->IsRequestingClosure = 'N';
		$this->IsBigEnquiry = 'N';
		$this->IsTradeEnquiry = 'N';
		$this->IsOrdered = 'N';
		$this->ClosedOn = '0000-00-00 00:00:00';
		$this->ReviewOn = '0000-00-00 00:00:00';

        $this->Channel = new Channel();
		$this->Channel->ID = CHANNEL_ID;

		if (!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = NULL) {
		if (!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM enquiry WHERE Enquiry_ID=%d", mysql_real_escape_string($this->ID)));
		if ($data->Row) {
			$this->Channel->ID = $data->Row['Channel_ID'];
			$this->Prefix = $data->Row['Prefix'];
			$this->Type->Get($data->Row['Enquiry_Type_ID']);
			$this->ClosedType->Get($data->Row['Enquiry_Closed_Type_ID']);
			$this->Customer->Get($data->Row['Customer_ID']);
			$this->Subject = stripslashes($data->Row['Subject']);
			$this->Status = $data->Row['Status'];
			$this->IsPendingAction = $data->Row['Is_Pending_Action'];
			$this->IsRequestingClosure = $data->Row['Is_Requesting_Closure'];
			$this->IsBigEnquiry = $data->Row['Is_Big_Enquiry'];
			$this->IsTradeEnquiry = $data->Row['Is_Trade_Enquiry'];
			$this->IsOrdered = $data->Row['Is_Ordered'];
			$this->Rating = $data->Row['Rating'];
			$this->RatingComment = stripslashes($data->Row['Rating_Comment']);
			$this->OwnedBy = $data->Row['Owned_By'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$this->ClosedOn = $data->Row['Closed_On'];
			$this->ReviewOn = $data->Row['Review_On'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function GetLines() {
		$this->Line = array();
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT Enquiry_Line_ID FROM enquiry_line WHERE Enquiry_ID=%d ORDER BY Created_On ASC", mysql_real_escape_string($this->ID)));
		while ($data->Row) {
			$line = new EnquiryLine();

			if ($line->Get($data->Row['Enquiry_Line_ID'])) {
				$this->Line[] = $line;
			}

			$data->Next();
		}
		$data->Disconnect();
	}

	function Add($email = true) {
		$data = new DataQuery(sprintf("INSERT INTO enquiry (Channel_ID, Prefix, Customer_ID, Enquiry_Type_ID, Enquiry_Closed_Type_ID, Subject, Status, Is_Pending_Action, Is_Requesting_Closure, Is_Big_Enquiry, Is_Trade_Enquiry, Is_Ordered, Rating, Rating_Comment, Owned_By, Created_On, Created_By, Modified_On, Modified_By, Closed_On, Review_On) VALUES (%d, '%s', %d, %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', %f, '%s', %d, NOW(), %d, NOW(), %d, '%s', '%s')", mysql_real_escape_string($this->Channel->ID), mysql_real_escape_string($this->Prefix), mysql_real_escape_string($this->Customer->ID), mysql_real_escape_string($this->Type->ID), mysql_real_escape_string($this->ClosedType->ID), mysql_real_escape_string(stripslashes($this->Subject)), mysql_real_escape_string(stripslashes($this->Status)), mysql_real_escape_string($this->IsPendingAction), mysql_real_escape_string($this->IsRequestingClosure), mysql_real_escape_string($this->IsBigEnquiry), mysql_real_escape_string($this->IsTradeEnquiry), mysql_real_escape_string($this->IsOrdered), mysql_real_escape_string($this->Rating), mysql_real_escape_string(stripslashes($this->RatingComment)), mysql_real_escape_string($this->OwnedBy), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ClosedOn), mysql_real_escape_string($this->ReviewOn)));

		$this->ID = $data->InsertID;

		if($email) {
			$this->SendConfirmation();
		}
	}

	static function EnquiryOrdered($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE enquiry SET Is_Ordered='Y' WHERE Customer_ID=%d AND Status NOT LIKE 'Closed'", mysql_real_escape_string($id)));
	}
	
	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE enquiry SET Channel_ID=%d, Prefix='%s', Enquiry_Type_ID=%d, Enquiry_Closed_Type_ID=%d, Subject='%s', Status='%s', Is_Pending_Action='%s', Is_Requesting_Closure='%s', Is_Big_Enquiry='%s', Is_Trade_Enquiry='%s', Is_Ordered='%s', Rating=%f, Rating_Comment='%s', Owned_By=%d, Modified_On=NOW(), Modified_By=%d, Closed_On='%s', Review_On='%s' WHERE Enquiry_ID=%d", mysql_real_escape_string($this->Channel->ID), mysql_real_escape_string($this->Prefix), mysql_real_escape_string($this->Type->ID), mysql_real_escape_string($this->ClosedType->ID), mysql_real_escape_string(stripslashes($this->Subject)), mysql_real_escape_string(stripslashes($this->Status)), mysql_real_escape_string($this->IsPendingAction), mysql_real_escape_string($this->IsRequestingClosure), mysql_real_escape_string($this->IsBigEnquiry), mysql_real_escape_string($this->IsTradeEnquiry), mysql_real_escape_string($this->IsOrdered), mysql_real_escape_string($this->Rating), mysql_real_escape_string(stripslashes($this->RatingComment)), mysql_real_escape_string($this->OwnedBy), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ClosedOn), mysql_real_escape_string($this->ReviewOn), mysql_real_escape_string($this->ID)));
	}

	function Close($closedTypeId = 0) {
		$this->Customer->Get();
		$this->Customer->Contact->Get();

		$this->ClosedOn = date('Y-m-d H:i:s');
		$this->Status = 'Closed';
		$this->ClosedType->ID = $closedTypeId;
		$this->IsRequestingClosure = 'N';
		$this->IsPendingAction = 'N';
		$this->Update();
	}

	function Delete($id = NULL) {
		if (!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM enquiry WHERE Enquiry_ID=%d", mysql_real_escape_string($this->ID)));

		$enquiryLine = new EnquiryLine();

		$data = new DataQuery(sprintf("SELECT Enquiry_Line_ID FROM enquiry_line WHERE Enquiry_ID=%d", mysql_real_escape_string($this->ID)));
		while ($data->Row) {
			$enquiryLine->Delete($data->Row['Enquiry_Line_ID']);

			$data->Next();
		}
		$data->Disconnect();
	}

	function Received() {
		if ($this->Status == 'Unread') {
			$this->Status = 'Open';
			$this->Update();
		}
	}

	function SendConfirmation($enquiryType = null) {
		$this->Type->Get();
		
		if($this->Type->IsPublic == 'Y') {
			if (empty($this->Customer->Contact->ID)) {
				$this->Customer->Get();
				$this->Customer->Contact->Get();
			}

			if (strlen($this->Customer->GetEmail()) > 0) {
				$enquiryHtml = '';
				$findReplace = new FindReplace();
				$findReplace->Add('/\[EMAIL\]/', $this->Customer->GetEmail());
				$findReplace->Add('/\[PASSWORD\]/', $this->Customer->GetPassword());

				$enquiryEmail = file($GLOBALS["DIR_WS_ADMIN"] . 'lib/templates/email_enquiryConfirmation.tpl');
				for ($i = 0; $i < count($enquiryEmail); $i++) {
					$enquiryHtml .= $findReplace->Execute($enquiryEmail[$i]);
				}

				$user = new User($this->OwnedBy);

				$findReplace = new FindReplace();
				$findReplace->Add('/\[BODY\]/', $enquiryHtml);
				$findReplace->Add('/\[NAME\]/', trim(sprintf("%s %s %s %s", $this->Customer->Contact->Person->Title, $this->Customer->Contact->Person->Name, $this->Customer->Contact->Person->Initial, $this->Customer->Contact->Person->LastName)));

				if($this->Type->DeveloperKey == 'customerservices') {
					$findReplace->Add('/\[SALES\]/', 'Customer Services');
				} else {
					if($this->OwnedBy > 0) {
						$findReplace->Add('/\[SALES\]/', sprintf('%s<br />%s<br />%s', trim(sprintf("%s %s", $user->Person->Name, $user->Person->LastName)), (strlen(trim($user->Person->Phone1)) > 0) ? $user->Person->Phone1 : $GLOBALS['COMPANY_PHONE'], !empty($user->SecondaryMailbox) ? $user->SecondaryMailbox : $user->Username));
					} else {
						$findReplace->Add('/\[SALES\]/', 'Customer Services');
					}
				}

				$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_templatePersonal.tpl");
				$emailBody = '';
				for ($i = 0; $i < count($stdTmplate); $i++) {
					$emailBody .= $findReplace->Execute($stdTmplate[$i]);
				}

				$returnPath = explode('@', $GLOBALS['EMAIL_RETURN']);
				$returnPath = (count($returnPath) == 2) ? sprintf('%s.enquiry.%d@%s', $returnPath[0], $this->ID, $returnPath[1]) : $GLOBALS['EMAIL_RETURN'];

				$queue = new EmailQueue();
				$queue->GetModuleID('enquiries');
				$queue->ReturnPath = $returnPath;
				$queue->Subject = sprintf("%s Enquiry Confirmation [#%s]", $GLOBALS['COMPANY'], $this->GetReference());
				$queue->Body = $emailBody;
				$queue->ToAddress = $this->Customer->GetEmail();
				if($enquiryType == CUSTOMER_SERVICES_ENQUIRY){
					$queue->FromAddress = $GLOBALS['EMAIL_FROM'];
				} else{
					$queue->FromAddress = ($user->ID > 0) ? (!empty($user->SecondaryMailbox) ? $user->SecondaryMailbox : $user->Username) : $queue->FromAddress;
				}
				$queue->Priority = 'H';
				$queue->Type = 'H';
				$queue->Add();
			}
		}
	}

	function SendClosed($enquiryType = null) {
		$this->Type->Get();
		
		if($this->Type->IsPublic == 'Y') {
			if(empty($this->Customer->Contact->ID)) {
				$this->Customer->Get();
				$this->Customer->Contact->Get();
			}

			if(strlen($this->Customer->GetEmail()) > 0) {
				$findReplace = new FindReplace();
				$findReplace->Add('/\[PREFIX\]/', $this->GetPrefix());
				$findReplace->Add('/\[ENQUIRYID\]/', $this->ID);

				$enquiryEmail = file($GLOBALS["DIR_WS_ADMIN"] . 'lib/templates/email_enquiryClosed.tpl');
				$enquiryHtml = '';
				for ($i = 0; $i < count($enquiryEmail); $i++) {
					$enquiryHtml .= $findReplace->Execute($enquiryEmail[$i]);
				}

				$user = new User($this->OwnedBy);

				$findReplace = new FindReplace();
				$findReplace->Add('/\[BODY\]/', $enquiryHtml);
				$findReplace->Add('/\[NAME\]/', trim(sprintf("%s %s %s %s", $this->Customer->Contact->Person->Title, $this->Customer->Contact->Person->Name, $this->Customer->Contact->Person->Initial, $this->Customer->Contact->Person->LastName)));

				if($this->Type->DeveloperKey == 'customerservices') {
					$findReplace->Add('/\[SALES\]/', 'Customer Services');
				} else {
					if ($this->OwnedBy > 0) {
						$findReplace->Add('/\[SALES\]/', sprintf('%s<br />%s<br />%s', trim(sprintf("%s %s", $user->Person->Name, $user->Person->LastName)), (strlen(trim($user->Person->Phone1)) > 0) ? $user->Person->Phone1 : $GLOBALS['COMPANY_PHONE'], !empty($user->SecondaryMailbox) ? $user->SecondaryMailbox : $user->Username));
					} else {
						$findReplace->Add('/\[SALES\]/', 'Customer Services');
					}
				}

				$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_templatePersonal.tpl");
				$emailBody = '';
				for ($i = 0; $i < count($stdTmplate); $i++) {
					$emailBody .= $findReplace->Execute($stdTmplate[$i]);
				}
				
				$returnPath = explode('@', $GLOBALS['EMAIL_RETURN']);
				$returnPath = (count($returnPath) == 2) ? sprintf('%s.enquiry.%d@%s', $returnPath[0], $this->ID, $returnPath[1]) : $GLOBALS['EMAIL_RETURN'];

				$queue = new EmailQueue();
				$queue->GetModuleID('enquiries');
				$queue->ReturnPath = $returnPath;
				$queue->Subject = sprintf("%s Enquiry Closed [#%s]", $GLOBALS['COMPANY'], $this->GetReference());
				$queue->Body = $emailBody;
				$queue->ToAddress = $this->Customer->GetEmail();
					if($enquiryType == CUSTOMER_SERVICES_ENQUIRY){
					$queue->FromAddress = $GLOBALS['EMAIL_FROM'];
				} else{
					$queue->FromAddress = ($user->ID > 0) ? (!empty($user->SecondaryMailbox) ? $user->SecondaryMailbox : $user->Username) : $queue->FromAddress;
				}
				$queue->Priority = 'H';
				$queue->Type = 'H';
				$queue->Add();
			}
		}
	}

	function SendClosing($enquiryType = null) {
		$this->Type->Get();
		
		if($this->Type->IsPublic == 'Y') {
			if(empty($this->Customer->Contact->ID)) {
				$this->Customer->Get();
				$this->Customer->Contact->Get();
			}

			if(strlen($this->Customer->GetEmail()) > 0) {
				$findReplace = new FindReplace();
				$findReplace->Add('/\[PREFIX\]/', $this->GetPrefix());
				$findReplace->Add('/\[ENQUIRYID\]/', $this->ID);

				$enquiryEmail = file($GLOBALS["DIR_WS_ADMIN"] . 'lib/templates/email_enquiryClosing.tpl');
				$enquiryHtml = '';
				for ($i = 0; $i < count($enquiryEmail); $i++) {
					$enquiryHtml .= $findReplace->Execute($enquiryEmail[$i]);
				}

				$user = new User($this->OwnedBy);

				$findReplace = new FindReplace();
				$findReplace->Add('/\[BODY\]/', $enquiryHtml);
				$findReplace->Add('/\[NAME\]/', trim(sprintf("%s %s %s %s", $this->Customer->Contact->Person->Title, $this->Customer->Contact->Person->Name, $this->Customer->Contact->Person->Initial, $this->Customer->Contact->Person->LastName)));

				if($this->Type->DeveloperKey == 'customerservices') {
					$findReplace->Add('/\[SALES\]/', 'Customer Services');
				} else {
					if ($this->OwnedBy > 0) {
						$findReplace->Add('/\[SALES\]/', sprintf('%s<br />%s<br />%s', trim(sprintf("%s %s", $user->Person->Name, $user->Person->LastName)), (strlen(trim($user->Person->Phone1)) > 0) ? $user->Person->Phone1 : $GLOBALS['COMPANY_PHONE'], !empty($user->SecondaryMailbox) ? $user->SecondaryMailbox : $user->Username));
					} else {
						$findReplace->Add('/\[SALES\]/', 'Customer Services');
					}
				}

				$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_templatePersonal.tpl");
				$emailBody = '';
				for ($i = 0; $i < count($stdTmplate); $i++) {
					$emailBody .= $findReplace->Execute($stdTmplate[$i]);
				}

				$returnPath = explode('@', $GLOBALS['EMAIL_RETURN']);
				$returnPath = (count($returnPath) == 2) ? sprintf('%s.enquiry.%d@%s', $returnPath[0], $this->ID, $returnPath[1]) : $GLOBALS['EMAIL_RETURN'];

				$queue = new EmailQueue();
				$queue->GetModuleID('enquiries');
				$queue->ReturnPath = $returnPath;
				$queue->Subject = sprintf("%s Enquiry Requesting Closure [#%s]", $GLOBALS['COMPANY'], $this->GetReference());
				$queue->Body = $emailBody;
				$queue->ToAddress = $this->Customer->GetEmail();
					if($enquiryType == CUSTOMER_SERVICES_ENQUIRY){
					$queue->FromAddress = $GLOBALS['EMAIL_FROM'];
				} else{
					$queue->FromAddress = ($user->ID > 0) ? (!empty($user->SecondaryMailbox) ? $user->SecondaryMailbox : $user->Username) : $queue->FromAddress;
				}
				$queue->Priority = 'H';
				$queue->Type = 'H';
				$queue->Add();
			}
		}
	}

	function SendNotification($enquiryType = null) {
		if(Setting::GetValue('enquiry_send_notification') == 'true') {
			if($this->OwnedBy > 0) {
				$user = new User($this->OwnedBy);

				$findReplace = new FindReplace();
				$findReplace->Add('/\[PREFIX\]/', $this->GetPrefix());
				$findReplace->Add('/\[ENQUIRYID\]/', $this->ID);

				$enquiryEmail = file($GLOBALS["DIR_WS_ADMIN"] . 'lib/templates/email_enquiryNotification.tpl');
				$enquiryHtml = '';
				for ($i = 0; $i < count($enquiryEmail); $i++) {
					$enquiryHtml .= $findReplace->Execute($enquiryEmail[$i]);
				}

				$findReplace = new FindReplace();
				$findReplace->Add('/\[BODY\]/', $enquiryHtml);
				$findReplace->Add('/\[NAME\]/', trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)));

				$emailBody = $findReplace->Execute(Template::GetContent('email_template_standard'));

				$queue = new EmailQueue();
				$queue->GetModuleID('enquiries');
				$queue->Body = $emailBody;
				$queue->ToAddress = $user->Person->Email;
					if($enquiryType == CUSTOMER_SERVICES_ENQUIRY){
					$queue->FromAddress = $GLOBALS['EMAIL_FROM'];
				} else{
					$queue->FromAddress = ($user->ID > 0) ? (!empty($user->SecondaryMailbox) ? $user->SecondaryMailbox : $user->Username) : $queue->FromAddress;
				}
				$queue->Priority = 'H';
				$queue->Subject = sprintf("%s Enquiry Notification [#%s]", $GLOBALS['COMPANY'], $this->GetReference());
				$queue->Type = 'H';
				$queue->Add();
			}
		}
	}

	function GetPrefix() {
		return sprintf('E%s', $this->Prefix);
	}

	function GetReference() {
		return sprintf('%s%d', $this->GetPrefix(), $this->ID);
	}
}