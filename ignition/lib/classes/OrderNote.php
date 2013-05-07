<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Person.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/htmlMimeMail5.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');

class OrderNote{
	var $ID;
	var $OrderID;
	var $TypeID;
	var $Subject;
	var $Message;
	var $IsPublic;
	var $IsAlert;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function OrderNote($id=NULL){
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

		$data = new DataQuery(sprintf("select note.*, ot.Type_Name from order_note as note left join order_note_type as ot on note.Order_Note_Type_ID=ot.Order_Note_Type_ID where note.Order_Note_ID=%d", mysql_real_escape_string($this->ID)));
		$this->OrderID = $data->Row['Order_ID'];
		$this->Subject = $data->Row['Type_Name'];
		$this->Message = $data->Row['Order_Note'];
		$this->IsPublic = $data->Row['Is_Public'];
		$this->IsAlert = $data->Row['Is_Alert'];
		$this->CreatedOn = $data->Row['Created_On'];
		$this->CreatedBy = $data->Row['Created_By'];
		$this->ModifiedOn = $data->Row['Modified_On'];
		$this->ModifiedBy = $data->Row['Modified_By'];
		$data->Disconnect();
	}

	function Add(){
		$data = new DataQuery(sprintf("insert into order_note (Order_ID, Order_Note_Type_ID, Order_Note, Is_Public, Is_Alert, Created_On, Created_By, Modified_On, Modified_By) values (%d, %d, '%s', '%s', '%s', Now(), %d, Now(), %d)",
						mysql_real_escape_string($this->OrderID),
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
		$data = new DataQuery(sprintf("update order_note set Order_ID=%d, Order_Note_Type_ID=%d, Order_Note='%s', Is_Public='%s', Is_Alert='%s', Modified_On=Now(), Modified_By=%d where Order_Note_ID=%d",
						mysql_real_escape_string($this->OrderID),
						mysql_real_escape_string($this->TypeID),
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
		new DataQuery(sprintf("delete from order_note where Order_Note_ID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeleteOrder($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("delete from order_note where Order_ID=%d", mysql_real_escape_string($id)));
	}

	function SendToCustomer($customerName, $customerEmail){
		$findReplace = new FindReplace;
		$findReplace->Add('/\[MESSAGE\]/', stripslashes($this->Message));

		$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_orderNoteToCustomer.tpl");
		$body = "";
		for($i=0; $i < count($orderEmail); $i++){
			$body .= $findReplace->Execute($orderEmail[$i]);
		}

		$findReplace = new FindReplace;
		$findReplace->Add('/\[BODY\]/', $body);
		$findReplace->Add('/\[NAME\]/', $customerName);

		$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
		$emailBody = "";
		for($i=0; $i < count($stdTmplate); $i++){
			$emailBody .= $findReplace->Execute($stdTmplate[$i]);
		}

		$mail = new htmlMimeMail5();
		$mail->setFrom($GLOBALS['EMAIL_SUPPORT']);
		$mail->setSubject(sprintf("%s - New Note Added to Order %s",
								$GLOBALS['COMPANY'],
								$this->OrderID));
		$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
		$mail->setHTML($emailBody);
		$mail->send(array($customerEmail));
	}

	function SendToAdmin($customerName, $customerEmail){
		$this->IsAlert = 'Y';
		$this->Update();

		$findReplace = new FindReplace;
		$findReplace->Add('/\[MESSAGE\]/', $this->Message);
		$findReplace->Add('/\[NAME\]/', $customerName);
		$findReplace->Add('/\[ORDERID\]/', $this->OrderID);

		$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_orderNoteToCustomer.tpl");
		$body = "";
		for($i=0; $i < count($orderEmail); $i++){
			$body .= $findReplace->Execute($orderEmail[$i]);
		}

		$findReplace = new FindReplace;
		$findReplace->Add('/\[BODY\]/', $body);
		$findReplace->Add('/\[NAME\]/', $customerName);

		$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
		$emailBody = "";
		for($i=0; $i < count($stdTmplate); $i++){
			$emailBody .= $findReplace->Execute($stdTmplate[$i]);
		}

		$mail = new htmlMimeMail5();
		$mail->setFrom($customerEmail);
		$mail->setSubject(sprintf("%s - Customer Note Added to Order %s",
								$GLOBALS['COMPANY'],
								$this->OrderID));
		$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
		$mail->setHTML($emailBody);
		$mail->send(array($GLOBALS['EMAIL_SUPPORT']));
	}
}
?>