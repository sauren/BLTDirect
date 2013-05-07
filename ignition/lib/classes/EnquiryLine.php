<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Enquiry.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/EnquiryLineDocument.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/EnquiryLineQuote.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/EmailQueue.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Template.php");

class EnquiryLine {
	var $ID;
	var $Enquiry;
	var $Message;
	var $IsCustomerMessage;
	var $IsPublic;
	var $IsDraft;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $Documents;
	var $Quotes;
	var $EmailAddress;

	function __construct($id=NULL){
		$this->Enquiry = new Enquiry();
		$this->IsCustomerMessage = 'N';
		$this->IsPublic = 'Y';
		$this->IsDraft = 'N';
		$this->Documents = array();
		$this->Quotes = array();

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}


		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM enquiry_line WHERE Enquiry_Line_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->Row) {
			$this->Enquiry->ID = $data->Row['Enquiry_ID'];
			$this->Message = stripslashes($data->Row['Message']);
			$this->IsCustomerMessage = $data->Row['Is_Customer_Message'];
			$this->IsPublic = $data->Row['Is_Public'];
			$this->IsDraft = $data->Row['Is_Draft'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];

			$this->Documents = array();

			$data2 = new DataQuery(sprintf("SELECT Enquiry_Line_Document_ID FROM enquiry_line_document WHERE Enquiry_Line_ID=%d", mysql_real_escape_string($this->ID)));
			while($data2->Row) {
				$this->Documents[] = new EnquiryLineDocument($data2->Row['Enquiry_Line_Document_ID']);

				$data2->Next();
			}
			$data2->Disconnect();

			$this->Quotes = array();

			$data2 = new DataQuery(sprintf("SELECT Enquiry_Line_Quote_ID FROM enquiry_line_quote WHERE Enquiry_Line_ID=%d", mysql_real_escape_string($this->ID)));
			while($data2->Row) {
				$this->Quotes[] = new EnquiryLineQuote($data2->Row['Enquiry_Line_Quote_ID']);

				$data2->Next();
			}
			$data2->Disconnect();

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO enquiry_line (Enquiry_ID, Message, Is_Customer_Message, Is_Public, Is_Draft, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, '%s', '%s', '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Enquiry->ID), mysql_real_escape_string(stripslashes($this->Message)), mysql_real_escape_string($this->IsCustomerMessage), mysql_real_escape_string($this->IsPublic), mysql_real_escape_string($this->IsDraft), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;

		if($this->IsDraft == 'N') {
			if(($this->IsCustomerMessage == 'N') && ($this->IsPublic == 'Y')) {
				$this->SendResponse();
			}

			if(empty($this->Enquiry->Customer->ID)) {
				$this->Enquiry->Get();
			}

			if($this->Enquiry->OwnedBy != $GLOBALS['SESSION_USER_ID']) {
				$this->Enquiry->SendNotification();
			}

			if($this->IsCustomerMessage == 'Y') {
				$this->Enquiry->ReviewOn = '0000-00-00 00:00:00';
				$this->Enquiry->Update();
			}
		}
	}

	function Update(){

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE enquiry_line SET Message='%s', Is_Customer_Message='%s', Is_Public='%s', Is_Draft='%s', Modified_On=NOW(), Modified_By=%d WHERE Enquiry_Line_ID=%d", mysql_real_escape_string(stripslashes($this->Message)), mysql_real_escape_string($this->IsCustomerMessage), mysql_real_escape_string($this->IsPublic), mysql_real_escape_string($this->IsDraft), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}


		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM enquiry_line WHERE Enquiry_Line_ID=%d", mysql_real_escape_string($this->ID)));

		$enquiryLineQuote = new EnquiryLineQuote();

		$data = new DataQuery(sprintf("SELECT Enquiry_Line_Quote_ID FROM enquiry_line_quote WHERE Enquiry_Line_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$enquiryLineQuote->Delete($data->Row['Enquiry_Line_Quote_ID']);

			$data->Next();
		}
		$data->Disconnect();

		$enquiryLineDocument = new EnquiryLineDocument();

		$data = new DataQuery(sprintf("SELECT Enquiry_Line_Document_ID FROM enquiry_line_document WHERE Enquiry_Line_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$enquiryLineDocument->Delete($data->Row['Enquiry_Line_Document_ID']);

			$data->Next();
		}
		$data->Disconnect();
	}

	function SendResponse($enquiryType = null) {
		if(empty($this->Enquiry->Customer->ID)) {
			$this->Enquiry->Get();
			$this->Enquiry->Customer->Get();
			$this->Enquiry->Customer->Contact->Get();
		}

		$this->Enquiry->Type->Get();
		
		if($this->Enquiry->Type->IsPublic == 'Y') {
			if(strlen($this->Enquiry->Customer->GetEmail()) > 0) {
				$this->Get();

				$findReplace = new FindReplace();
				$findReplace->Add('/\[ENQUIRYID\]/', $this->Enquiry->ID);
				$findReplace->Add('/\[MESSAGE\]/', stripslashes($this->Message));

				$enquiryHtml = $findReplace->Execute(Template::GetContent('email_enquiry_response'));

				$user = new User($this->Enquiry->OwnedBy);

				$findReplace = new FindReplace();
				$findReplace->Add('/\[BODY\]/', $enquiryHtml);
				$findReplace->Add('/\[NAME\]/', trim(sprintf("%s %s %s %s", $this->Enquiry->Customer->Contact->Person->Title, $this->Enquiry->Customer->Contact->Person->Name, $this->Enquiry->Customer->Contact->Person->Initial, $this->Enquiry->Customer->Contact->Person->LastName)));

				if($this->Enquiry->Type->DeveloperKey == 'customerservices') {
					$findReplace->Add('/\[SALES\]/', 'Customer Services');
				} else {
					if($this->Enquiry->OwnedBy > 0) {
						$findReplace->Add('/\[SALES\]/', sprintf('%s<br />%s<br />%s', trim(sprintf("%s %s", $user->Person->Name, $user->Person->LastName)), (strlen(trim($user->Person->Phone1)) > 0) ? $user->Person->Phone1 : $GLOBALS['COMPANY_PHONE'], !empty($user->SecondaryMailbox) ? $user->SecondaryMailbox : $user->Username));
					} else {
						$findReplace->Add('/\[SALES\]/', 'Customer Services');
					}
				}
				
				$emailBody = $findReplace->Execute(Template::GetContent('email_template_enquiry'));

				$returnPath = explode('@', $GLOBALS['EMAIL_RETURN']);
				$returnPath = (count($returnPath) == 2) ? sprintf('%s.enquiry-line.%d@%s', $returnPath[0], $this->ID, $returnPath[1]) : $GLOBALS['EMAIL_RETURN'];

				$queue = new EmailQueue();
				$queue->GetModuleID('enquiries');
				$queue->ReturnPath = $returnPath;
				$queue->Subject = sprintf("%s Enquiry Response [#%s]", $GLOBALS['COMPANY'], $this->Enquiry->GetReference());
				$queue->Body = $emailBody;
				$queue->ToAddress = !empty($this->EmailAddress) ? $this->EmailAddress : $this->Enquiry->Customer->GetEmail();
				if($enquiryType == CUSTOMER_SERVICES_ENQUIRY){
					$queue->FromAddress = $GLOBALS['EMAIL_FROM'];
				} else{
					$queue->FromAddress = ($user->ID > 0) ? (!empty($user->SecondaryMailbox) ? $user->SecondaryMailbox : $user->Username) : $queue->FromAddress;
				}
				$queue->Priority = 'H';
				$queue->Type = 'H';
				$queue->Add();

				for($i=0; $i<count($this->Documents); $i++) {
					if($this->Documents[$i]->IsPublic == 'Y') {
						$queue->AddAttachment($GLOBALS['ENQUIRY_DOCUMENT_DIR_FS'].$this->Documents[$i]->File->FileName, $GLOBALS['ENQUIRY_DOCUMENT_DIR_WS'].$this->Documents[$i]->File->FileName);
					}
				}

				for($i=0; $i<count($this->Quotes); $i++) {
					for($j=0; $j<count($this->Quotes[$i]->Documents); $j++) {
						$this->Quotes[$i]->Documents[$j]->QuoteDocument->Get();

						if($this->Quotes[$i]->Documents[$j]->QuoteDocument->IsPublic == 'Y') {
							$queue->AddAttachment($GLOBALS['QUOTE_DOCUMENT_DIR_FS'].$this->Quotes[$i]->Documents[$j]->QuoteDocument->File->FileName, $GLOBALS['QUOTE_DOCUMENT_DIR_WS'].$this->Quotes[$i]->Documents[$j]->QuoteDocument->File->FileName);
						}
					}
				}
			}
		}
	}
}