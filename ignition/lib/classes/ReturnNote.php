<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Person.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/htmlMimeMail5.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Return.php');

class ReturnNote{
	var $ID;
	var $ReturnID;
	var $TypeID;
	var $Subject;
	var $Message;
	var $IsPublic;
	var $IsAlert;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function ReturnNote($id=NULL){
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

        $data = new DataQuery(sprintf("SELECT note.*, ot.Type_Name
                                      FROM return_note note
                                      LEFT JOIN return_note_type ot
                                      ON note.Return_Note_Type_ID=ot.Return_Note_Type_ID
                                      WHERE note.Return_Note_ID=%d", mysql_real_escape_string($this->ID)));
		$this->ReturnID = $data->Row['Return_ID'];
		$this->Subject = $data->Row['Type_Name'];
		$this->Message = $data->Row['Return_Note'];
		$this->IsPublic = $data->Row['Is_Public'];
		$this->IsAlert = $data->Row['Is_Alert'];
		$this->CreatedOn = $data->Row['Created_On'];
		$this->CreatedBy = $data->Row['Created_By'];
		$this->ModifiedOn = $data->Row['Modified_On'];
		$this->ModifiedBy = $data->Row['Modified_By'];
		$data->Disconnect();
	}

	function Add(){
        $data = new DataQuery(sprintf("INSERT INTO return_note
                                      (Return_ID, Return_Note_Type_ID,
                                      Return_Note, Is_Public, Is_Alert,
                                      Created_On, Created_By, Modified_On,
                                      Modified_By)
                                      VALUES (%d, %d, '%s', '%s', '%s', Now(),
                                          %d, Now(), %d)",
						mysql_real_escape_string($this->ReturnID),
						mysql_real_escape_string($this->TypeID),
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
        $data = new DataQuery(sprintf("UPDATE return_note SET
                                      Return_ID=%d,
                                      Return_Note_Type_ID=%d,
                                      Return_Note='%s',
                                      Is_Public='%s',
                                      Is_Alert='%s',
                                      Modified_On=Now(),
                                      Modified_By=%d
                                      WHERE Return_Note_ID=%d",
                                mysql_real_escape_string($this->ReturnID),
                                mysql_real_escape_string($this->TypeID),
                                mysql_real_escape_string($this->Message),
                                mysql_real_escape_string($this->IsPublic),
                                mysql_real_escape_string($this->IsAlert),
                                mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
                                mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) $this->ID=$id;
        $data = new DataQuery(sprintf("DELETE FROM return_note
                                      WHERE Return_Note_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function SendToCustomer($customerName, $customerEmail){
        $body = '';
        if($this->TypeID == 1){ // If refused
            $findReplace = new FindReplace;
            $subject = 'Return Refused';
            // Get Order Template
            $findReplace = new FindReplace;
            $findReplace->Add('/\[REFUSE_NOTE\]/', $this->Message);
            $findReplace->Add('/\[RETURN_REF\]/', $this->ID);
            $findReplace->Add('/\[REQUEST_DATE\]/',
                cDatetime($this->RequestedOn, 'longdate'));
            $findReplace->Add('/\[CUSTOMER_NAME\]/', $customerName);
            $findReplace->Add('/\[CUSTOMER_ID\]/', $this->Customer->Contact->ID);
            // Replace Order Template Variables
            $orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_return_refused_template.tpl");
            $orderHtml = "";
            for($i=0; $i < count($orderEmail); $i++){
                $body .= $findReplace->Execute($orderEmail[$i]);
            }

            unset($findReplace);
        } else {
            $orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_orderNoteToCustomer.tpl");
            $body = "";
            for($i=0; $i < count($orderEmail); $i++){
                $body .= $findReplace->Execute($orderEmail[$i]);
            }
            // Get Return Template
            $findReplace = new FindReplace;
            $findReplace->Add('/\[MESSAGE\]/', stripslashes($this->Message));

            // Replace Return Template Variables
            $orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_orderNoteToCustomer.tpl");
            $body = "";
            for($i=0; $i < count($orderEmail); $i++){
                $body .= $findReplace->Execute($orderEmail[$i]);
            }

            unset($findReplace);
        }
		$findReplace = new FindReplace;
		$findReplace->Add('/\[BODY\]/', $body);
		$findReplace->Add('/\[NAME\]/', $customerName);
        $emailBody = "";
        // Get Standard Email Template
        $stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
        for($i=0; $i < count($stdTmplate); $i++){
            $emailBody .= $findReplace->Execute($stdTmplate[$i]);
        }

        $queue = new EmailQueue();
        $queue->GetModuleID('returns');
        $queue->FromAddress = $GLOBALS['EMAIL_SUPPORT'];
		$queue->Subject = sprintf("%s - New Note Added to Return %s", $GLOBALS['COMPANY'], $this->ReturnID);
		$queue->Body = $emailBody;
		$queue->ToAddress = $customerEmail;
		$queue->Add();
	}

	function SendToAdmin($customerName, $customerEmail){
		$this->IsAlert = 'Y';
		$this->Update();

		// Get Return Template
		$findReplace = new FindReplace;
		$findReplace->Add('/\[MESSAGE\]/', $this->Message);
		$findReplace->Add('/\[NAME\]/', $customerName);
		$findReplace->Add('/\[RETURN_ID\]/', $this->ReturnID);

		// Replace Return Template Variables
		$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_orderNoteToCustomer.tpl");
		$body = "";
		for($i=0; $i < count($orderEmail); $i++){
			$body .= $findReplace->Execute($orderEmail[$i]);
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

        $queue = new EmailQueue();
        $queue->GetModuleID('returns');
        $queue->FromAddress = $GLOBALS['EMAIL_SUPPORT'];
		$queue->Subject = sprintf("%s - Customer Note Added to Return %s", $GLOBALS['COMPANY'], $this->ReturnID);
		$queue->Body = $emailBody;
		$queue->ToAddress = $customerEmail;
		$queue->Add();
	}
}