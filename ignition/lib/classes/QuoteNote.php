<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Person.php");
	require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/htmlMimeMail5.php");
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Quote.php');

	class QuoteNote{
		var $ID;
		var $QuoteID;
		var $TypeID;
		var $Subject;
		var $Message;
		var $IsPublic;
		var $IsAlert;
		var $CreatedOn;
		var $CreatedBy;
		var $ModifiedOn;
		var $ModifiedBy;

		function QuoteNote($id=NULL){
			$this->IsPublic = 'Y';
			$this->IsAlert = 'N';
			if(!is_null($id)){
				$this->ID=$id;
				$this->Get();
			}
		}

		function Get($id=NULL){
			if(!is_null($id)) $this->ID=$id;
			if(!is_numeric($this->ID)){
				return false;
			}
			$data = new DataQuery(sprintf("select * from quote_note where Quote_Note_ID=%d", mysql_real_escape_string($this->ID)));
			$this->QuoteID = $data->Row['Quote_ID'];
			$this->Message = $data->Row['Quote_Note'];
			$this->IsPublic = $data->Row['Is_Public'];
			$this->IsAlert = $data->Row['Is_Alert'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$data->Disconnect();
		}

		function Add(){
			$data = new DataQuery(sprintf("insert into quote_note (Quote_ID, Quote_Note, Is_Public, Is_Alert, Created_On, Created_By, Modified_On, Modified_By) values (%d, '%s', '%s', '%s', Now(), %d, Now(), %d)",
							mysql_real_escape_string($this->QuoteID),
							mysql_real_escape_string($this->Message),
							mysql_real_escape_string($this->IsPublic),
							mysql_real_escape_string($this->IsAlert),
							mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
							mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
			$this->ID = $data->InsertID;
		}

		function Update(){
			if(!is_numeric($this->ID)){
				return false;
			}
			$data = new DataQuery(sprintf("update quote_note set Quote_ID=%d, Quote_Note='%s', Is_Public='%s', Is_Alert='%s', Modified_On=Now(), Modified_By=%d where Quote_Note_ID=%d",
							mysql_real_escape_string($this->QuoteID),
							mysql_real_escape_string($this->Message),
							mysql_real_escape_string($this->IsPublic),
							mysql_real_escape_string($this->IsAlert),
							mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
							mysql_real_escape_string($this->ID)));
		}

		function Delete($id=NULL){
			if(!is_null($id)) $this->ID=$id;
			if(!is_numeric($this->ID)){
			return false;
		}
			$data = new DataQuery(sprintf("delete from quote_note where Quote_Note_ID=%d", mysql_real_escape_string($this->ID)));
		}

		function SendToCustomer($customerName, $customerEmail){
			// Get Quote Template
			$findReplace = new FindReplace;
			$findReplace->Add('/\[MESSAGE\]/', stripslashes($this->Message));

			// Replace Quote Template Variables
			$quoteEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_quoteNoteToCustomer.tpl");
			$body = "";
			for($i=0; $i < count($quoteEmail); $i++){
				$body .= $findReplace->Execute($quoteEmail[$i]);
			}

			unset($findReplace);
			$findReplace = new FindReplace;
			$findReplace->Add('/\[BODY\]/', $body);
			$findReplace->Add('/\[NAME\]/', $customerName);
			// Get Standard Email Template
			$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
			$emailBody = "";
			for($i=0; $i < count($stdTmplate); $i++){
				$emailBody .= $findReplace->Execute($stdTmplate[$i]);
			}

			// Ok, we have the email body now lets email it.
			$mail = new htmlMimeMail5();
			$mail->setFrom($GLOBALS['EMAIL_SUPPORT']);
			$mail->setSubject(sprintf("%s - New Note Added to Quote %s",
									$GLOBALS['COMPANY'],
									$this->QuoteID));
			$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
			$mail->setHTML($emailBody);
			$mail->send(array($customerEmail));
		}

		function SendToAdmin($customerName, $customerEmail){
			$this->IsAlert = 'Y';
			$this->Update();

			// Get Quote Template
			$findReplace = new FindReplace;
			$findReplace->Add('/\[MESSAGE\]/', $this->Message);
			$findReplace->Add('/\[NAME\]/', $customerName);
			$findReplace->Add('/\[QUOTEID\]/', $this->QuoteID);

			// Replace Quote Template Variables
			$quoteEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_quoteNoteToCustomer.tpl");
			$body = "";
			for($i=0; $i < count($quoteEmail); $i++){
				$body .= $findReplace->Execute($quoteEmail[$i]);
			}

			unset($findReplace);
			$findReplace = new FindReplace;
			$findReplace->Add('/\[BODY\]/', $body);
			$findReplace->Add('/\[NAME\]/', $customerName);
			// Get Standard Email Template
			$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
			$emailBody = "";
			for($i=0; $i < count($stdTmplate); $i++){
				$emailBody .= $findReplace->Execute($stdTmplate[$i]);
			}

			// Ok, we have the email body now lets email it.
			$mail = new htmlMimeMail5();
			$mail->setFrom($customerEmail);
			$mail->setSubject(sprintf("%s - Customer Note Added to Quote %s",
									$GLOBALS['COMPANY'],
									$this->QuoteID));
			$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
			$mail->setHTML($emailBody);
			$mail->send(array($GLOBALS['EMAIL_SUPPORT']));
		}
	}
?>