<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Coupon.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');

class CouponContact {
	var $ID;
	var $Coupon;
	var $EmailAddress;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function CouponContact($id=NULL){
		$this->Coupon = new Coupon();

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

		$data = new DataQuery(sprintf("SELECT * FROM coupon_contact WHERE Coupon_Contact_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Coupon->ID = $data->Row['Coupon_ID'];
			$this->EmailAddress = $data->Row['Email_Address'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO coupon_contact (Coupon_ID, Email_Address, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, '%s', Now(), %d, Now(), %d)", mysql_real_escape_string($this->Coupon->ID), mysql_real_escape_string($this->EmailAddress), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("UPDATE coupon_contact SET Modified_On=Now(), Modified_By=%d WHERE Coupon_Contact_ID=%d", mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("DELETE FROM coupon_contact WHERE Coupon_Contact_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}

	function SendCoupon($email, $name) {
		$data = new DataQuery(sprintf("SELECT Coupon_ID, Coupon_Ref, Discount_Amount FROM coupon WHERE Coupon_Title LIKE 'Introduction Coupon'"));
		if($data->TotalRows > 0) {
			$findReplace = new FindReplace();
			$findReplace->Add('/\[DISCOUNT\]/', $data->Row['Discount_Amount']);
			$findReplace->Add('/\[COUPON\]/', $data->Row['Coupon_Ref']);

			// Replace Quote Template Variables
			$quoteEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_couponOrder.tpl");
			$quoteHtml = "";
			for($i=0; $i < count($quoteEmail); $i++){
				$quoteHtml .= $findReplace->Execute($quoteEmail[$i]);
			}

			unset($findReplace);
			$findReplace = new FindReplace;
			$findReplace->Add('/\[BODY\]/', $quoteHtml);
			$findReplace->Add('/\[NAME\]/', (strlen($name) == 0) ? 'Sir/Madam' : $name);
			// Get Standard Email Template
			$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
			$emailBody = "";
			for($i=0; $i < count($stdTmplate); $i++){
				$emailBody .= $findReplace->Execute($stdTmplate[$i]);
			}

			// Ok, we have the email body now lets email it.
			$mail = new htmlMimeMail5();
			$mail->setFrom($GLOBALS['EMAIL_FROM']);
			$mail->setSubject(sprintf("Introduction Coupon from %s", $GLOBALS['COMPANY']));
			$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
			$mail->setHTML($emailBody);
			$mail->send(array($email));

			$this->Coupon->ID = $data->Row['Coupon_ID'];
			$this->EmailAddress = $email;
			$this->Add();
		}
		$data->Disconnect();
	}
}
?>