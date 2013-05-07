<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ContactCreditAccount.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/htmlMimeMail5.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CustomerContactCollection.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Coupon.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductReview.php");

class Customer {
	var $ID;
	var $Contact;
	var $Username;
	var $UsernameSecondary;
	var $IsSecondaryActive;
	var $Password;
	var $PasswordChangedOn;


	var $PasswordToken;
	var $TokenCreatedOn;



	var $IsActive;
	var $IsAffiliate;
	var $AffiliateCommissionRate;
	var $LastLoginOn;
	var $TotalLogins;
	var $CreditLimit;
	var $CreditPeriod;
	var $CreditRemaining;
	var $IsCreditActive;
	var $ContactMethod;
	var $FirstSaleOn;
	var $LastSaleOn;
	var $SalesRep;
	var $FoundVia;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $AccountType;
	var $Contacts;
	var $Subscriptions;
	var $AvailableDiscountReward;
	var $IsCreditDeactivated;

	var $tempPassword;

	function Customer($id=NULL){
		$this->IsSecondaryActive = 'N';
		$this->Contact = new Contact();
		$this->IsActive = 'Y';
		$this->IsAffiliate = 'N';
		$this->IsCreditActive = 'N';
		$this->IsCreditDeactivated = 'N';
		$this->LastLoginOn = '0000-00-00 00:00:00';
		$this->FirstSaleOn = '0000-00-00 00:00:00';
		$this->LastSaleOn = '0000-00-00 00:00:00';
		$this->PasswordChangedOn = '0000-00-00 00:00:00';
		$this->tempPassword = '';
		$this->PasswordToken = '';
		$this->TokenCreatedOn = '0000-00-00 00:00:00';


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

		$data = new DataQuery(sprintf("select * from customer where Customer_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Contact->ID = $data->Row['Contact_ID'];
			$this->Username = $data->Row['Username'];
			$this->UsernameSecondary = $data->Row['Username_Secondary'];
			$this->IsSecondaryActive = $data->Row['Is_Secondary_Active'];
			$this->Password = $data->Row['Password'];
			$this->IsActive = $data->Row['Is_Active'];
			$this->IsAffiliate = $data->Row['Is_Affiliate'];
			$this->AffiliateCommissionRate = $data->Row['Affiliate_Commission_Rate'];
			$this->LastLoginOn = $data->Row['Last_Login_On'];
			$this->TotalLogins = $data->Row['Total_Logins'];
			$this->CreditLimit = $data->Row['Credit_Limit'];
			$this->CreditPeriod = $data->Row['Credit_Period'];
			$this->IsCreditActive = $data->Row['Is_Credit_Active'];
			$this->ContactMethod = $data->Row['Contact_Method_ID'];
			$this->FirstSaleOn = $data->Row['Sale_First_On'];
			$this->LastSaleOn = $data->Row['Sale_Last_On'];
			$this->SalesRep = $data->Row['Sales_Rep_ID'];
			$this->FoundVia = $data->Row['Found_Via_ID'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$this->Subscriptions = $data->Row['Subscriptions'];
			$this->IsCreditDeactivated = $data->Row['Is_Credit_Deactivated'];
			$this->PasswordChangedOn = $data->Row['PasswordChangedOn'];
			$this->PasswordToken = $data->Row['Password_Token'];
			$this->TokenCreatedOn = $data->Row['Token_Created_On'];




			$data->Disconnect();

			$data = new DataQuery(sprintf("SELECT Coupon_ID FROM coupon WHERE Introduced_By=%d", mysql_real_escape_string($this->ID)));
			if($data->TotalRows > 0) {
				$coupon = new Coupon($data->Row['Coupon_ID']);

				$data2 = new DataQuery(sprintf("SELECT (SUM(SubTotal)/100)*%f AS Discount_Reward FROM orders WHERE Coupon_ID=%d AND Is_Sample='N'", mysql_real_escape_string($coupon->Discount), mysql_real_escape_string($coupon->ID)));
				$this->AvailableDiscountReward = $data2->Row['Discount_Reward'];
				$data2->Disconnect();

				$data2 = new DataQuery(sprintf("SELECT SUM(Discount_Reward) AS Discount_Reward FROM orders WHERE Discount_Reward>0 AND Customer_ID=%d", mysql_real_escape_string($this->ID)));
				$this->AvailableDiscountReward -= $data2->Row['Discount_Reward'];
				$data2->Disconnect();

				$this->AvailableDiscountReward = number_format($this->AvailableDiscountReward, 2, '.', '');
			}
			$data->Disconnect();

			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add($sendMail = true) {
		if(empty($this->Password)) {
			$this->GeneratePassword(false);
		}
		
		$sql = sprintf("INSERT INTO customer
							(Contact_ID, Username, Username_Secondary, Is_Secondary_Active, Password, Is_Active, Is_Affiliate, Affiliate_Commission_Rate, Last_Login_On,
							Total_Logins, Credit_Limit, Credit_Period,
							Is_Credit_Active, Contact_Method_ID,
							Sale_First_On, Sale_Last_On, Sales_Rep_ID, Found_Via_ID,
							Created_On,
							Created_By, Modified_On, Modified_By,Subscriptions,
							Is_Credit_Deactivated, PasswordChangedOn, Password_Token, Token_Created_On)
							VALUES (%d, '%s', '%s', '%s', '%s', '%s', '%s', %f, '%s', %d, %f, %d, '%s', %d,
							'%s', '%s', %d, %d, Now(), %d, Now(), %d,'%s', '%s', '%s', '%s', '%s')",
		mysql_real_escape_string($this->Contact->ID),
		mysql_real_escape_string(stripslashes($this->Username)),
		mysql_real_escape_string(stripslashes($this->UsernameSecondary)),
		mysql_real_escape_string($this->IsSecondaryActive),
		mysql_real_escape_string($this->Password),
		mysql_real_escape_string($this->IsActive),
		mysql_real_escape_string($this->IsAffiliate),
		mysql_real_escape_string($this->AffiliateCommissionRate),
		mysql_real_escape_string($this->LastLoginOn),
		mysql_real_escape_string($this->TotalLogins),
		mysql_real_escape_string($this->CreditLimit),
		mysql_real_escape_string($this->CreditPeriod),
		mysql_real_escape_string($this->IsCreditActive),
		mysql_real_escape_string($this->ContactMethod),
		mysql_real_escape_string($this->FirstSaleOn),
		mysql_real_escape_string($this->LastSaleOn),
		mysql_real_escape_string($this->SalesRep),
		mysql_real_escape_string($this->FoundVia),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->Subscriptions),
		mysql_real_escape_string($this->IsCreditDeactivated),
		mysql_real_escape_string($this->PasswordChangedOn),
		mysql_real_escape_string($this->PasswordToken),
		mysql_real_escape_string($this->TokenCreatedOn));

		$data = new DataQuery($sql);
		$this->ID = $data->InsertID;

		if($sendMail) {
			$this->SendEmail(false);
		}

		return true;
	}

	static function DisableContact($id){

		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE customer SET Is_Active='N' WHERE Contact_ID=%d", mysql_real_escape_string($id)));
	}

	static function UserName($Email, $Customer){
		new DataQuery(sprintf("UPDATE customer SET Username='%s' WHERE Customer_ID=%d", mysql_real_escape_string($Email), mysql_real_escape_string($Customer)));
	}

	static function DeleteContact($id){

		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM customer WHERE Customer_ID=%d", mysql_real_escape_string($id)));
	}

	function Update() {

		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT Is_Credit_Active, Credit_Limit FROM customer WHERE Customer_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			if(($data->Row['Is_Credit_Active'] <> $this->IsCreditActive) || ($data->Row['Credit_Limit'] <> $this->CreditLimit)) {
				$handle = 0;

				if(($data->Row['Is_Credit_Active'] == 'N') && ($this->IsCreditActive == 'Y')) {
					$handle += 1;
				} elseif(($data->Row['Is_Credit_Active'] == 'Y') && ($this->IsCreditActive == 'Y')) {
					if($data->Row['Credit_Limit'] <> $this->CreditLimit) {
						$handle += 1;
						$handle += 2;
					}
				} elseif(($data->Row['Is_Credit_Active'] == 'Y') && ($this->IsCreditActive == 'N')) {
					$handle += 2;
				}

				if($handle & 2) {
					$data2 = new DataQuery(sprintf("SELECT id FROM contact_credit_account WHERE contactId=%d AND endedOn='0000-00-00 00:00:00'", $this->ID));
					while($data2->Row) {
						$account = new ContactCreditAccount($data2->Row['id']);
						$account->endedOn = date('Y-m-d H:i:s');
						$account->update();

						$data2->Next();
					}
					$data2->Disconnect();
				}
				
				if($handle & 1) {
					$account = new ContactCreditAccount();
					$account->contact->ID = $this->ID;
					$account->limit = $this->CreditLimit;
					$account->startedOn = date('Y-m-d H:i:s');
					$account->add();
				}
			}
		}
		$data->Disconnect();

		new DataQuery(sprintf("UPDATE customer SET
							Username='%s', Username_Secondary='%s', Is_Secondary_Active='%s', Password='%s', Is_Active='%s', Is_Affiliate='%s', Affiliate_Commission_Rate=%f,
							Last_Login_On='%s', Total_Logins=%d, Credit_Limit=%f,
							Credit_Period=%d, Is_Credit_Active='%s',
							Contact_Method_ID=%d,
							Sale_First_On='%s', Sale_Last_On='%s', Sales_Rep_ID=%d,
							Found_Via_ID=%d, Modified_On=Now(), Modified_By=%d,
							Subscriptions='%s',
							Is_Credit_Deactivated='%s',
							PasswordChangedOn='%s',
							Password_Token='%s',
							Token_Created_On='%s'
							WHERE Customer_ID=%d",
		mysql_real_escape_string(stripslashes($this->Username)),
		mysql_real_escape_string(stripslashes($this->UsernameSecondary)),
		mysql_real_escape_string($this->IsSecondaryActive),
		mysql_real_escape_string($this->Password),
		mysql_real_escape_string($this->IsActive),
		mysql_real_escape_string($this->IsAffiliate),
		mysql_real_escape_string($this->AffiliateCommissionRate),
		mysql_real_escape_string($this->LastLoginOn),
		mysql_real_escape_string($this->TotalLogins),
		mysql_real_escape_string($this->CreditLimit),
		mysql_real_escape_string($this->CreditPeriod),
		mysql_real_escape_string($this->IsCreditActive),
		mysql_real_escape_string($this->ContactMethod),
		mysql_real_escape_string($this->FirstSaleOn),
		mysql_real_escape_string($this->LastSaleOn),
		mysql_real_escape_string($this->SalesRep),
		mysql_real_escape_string($this->FoundVia),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->Subscriptions),
		mysql_real_escape_string($this->IsCreditDeactivated),
		mysql_real_escape_string($this->PasswordChangedOn),
		mysql_real_escape_string($this->PasswordToken),
		mysql_real_escape_string($this->TokenCreatedOn),
		mysql_real_escape_string($this->ID)));


		if(empty($this->Contact->Person->ID)) $this->Contact->Get();

		$this->Contact->Person->Email = $this->Username;
		$this->Contact->Person->Update();

		return true;
	}

	function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM customer WHERE Customer_ID=%s", mysql_real_escape_string($this->ID)));
		ProductReview::DeleteCustomer($this->ID);
	}

	function IsUnique($username=NULL){
		if(!is_null($username)) $this->Username = $username;
		$check = new DataQuery(sprintf("SELECT Customer_ID FROM customer
											WHERE Username='%s'",
		mysql_real_escape_string($this->Username)));
		if($check->TotalRows > 0){
			$this->ID = $check->Row['Customer_ID'];
			$check->Disconnect();
			return false;
		} else {
			$check->Disconnect();
			return true;
		}
	}

	function GetEmail() {
		return $this->Username;
	}
	
	function GetInvoiceEmail() {
		$emailAddress = $this->Username;
		
		if(empty($this->Contact->Person->ID)) {
			$this->Contact->Get();
		}
		
		if($this->Contact->Parent->ID > 0) {
			$this->Contact->Parent->Get();
			
			if(!empty($this->Contact->Parent->Organisation->InvoiceEmail)) {
				$emailAddress = $this->Contact->Parent->Organisation->InvoiceEmail;
			}
		}

		return $emailAddress;
	}

	function GetPassword(){
		return $this->Password;
	}

	function SetPassword($newPassword){
		$this->tempPassword = $newPassword;
		$this->Password = sha1($newPassword);
		$this->PasswordChangedOn = date('Y-m-d H:i:s');
	}
	

	function GeneratePassword($passwordChanged = true) {
		$password = new Password(PASSWORD_LENGTH_CUSTOMER);

		$this->SetPassword($password->Value);
		if($passwordChanged){
			$this->PasswordChangedOn = '0000-00-00 00:00:00';
		}
		$this->UpdatePassword();

		return $password->Value;
	}



	function UpdatePassword() {
		if(!is_null($this->ID) || $this->ID > 0){
			new DataQuery(sprintf("UPDATE customer SET Password='%s', PasswordChangedOn='%s' WHERE Customer_ID=%d",$this->Password,$this->PasswordChangedOn, $this->ID));
		}
	}

	function IsPasswordOld() {
		if(Setting::GetValue('customer_password_refresh_months') != ''){
			return (strtotime($this->PasswordChangedOn) < mktime(0, 0, 0, date('m')-Setting::GetValue('customer_password_refresh_months'), date('d'), date('Y'))) ? true : false;
		} else {
			return false;
		}
	}


	function GenerateToken($cid = null){
		if(isset($cid) && !empty($cid)){
			$id = $cid;
			$token = new Password(PASSWORD_TOKEN_LENGTH);

			$this->newToken = $token->Value;
			$this->TokenCreatedOn = date('Y-m-d H:i:s');

			new DataQuery(sprintf("UPDATE customer SET Password_Token='%s', Token_Created_On='%s' WHERE Customer_ID=%d",$this->newToken, $this->TokenCreatedOn, $id));

			return $this->passwordToken;
			exit;

		}else{
			return false;
			exit;
		}
	}

	function ResetToken(){
		$this->PasswordToken = '';
        $this->TokenCreatedOn = '0000-00-00 00:00:00';
        $this->Update();
        return true;
	}

	function ValidateToken($token, $valid){
		$resetPassword = false;
		if(isset($valid) && !empty($valid)){
			if($valid){
				$resetPassword = true;
			}
			return $resetPassword;
		} else {
			$tokenValidation = false;
			$tokenDateValidation = false;
			$tokenCheck = $this->PasswordToken;
	        $tokenDate = $this->TokenCreatedOn;

	        if($token == $tokenCheck){
	            $tokenValidation = true;
	        }

	        if(strtotime($tokenDate) >= strtotime('-1 day')){
	            $tokenDateValidation = true;
	        }

	        if($tokenValidation && $tokenDateValidation){
	            $resetPassword = true;
	        }
	        return $resetPassword;
		}
	}

	
	function ResetPasswordEmail(){
		if(empty($this->Contact->Person->ID)) $this->Contact->Get();
		if(strlen($this->GetEmail()) > 0) {
			
			$this->newToken = $this->PasswordToken;

			/** if token empty, generate token**/
			if(empty($this->PasswordToken) || is_null($this->PasswordToken)){
				$this->GenerateToken($this->ID);
			}

			/** check if token is old and reset if true **/
			if(strtotime($this->TokenCreatedOn) <= strtotime('-1 day')){
            	$this->GenerateToken($this->ID);
        	}

			$template = "lib/templates/email_changePassword.tpl";
			// Get Order Template

			$findReplace = new FindReplace;
			$findReplace->Add('/\[EMAIL\]/', $this->GetEmail());
			$findReplace->Add('/\[ID\]/', $this->ID);
			$findReplace->Add('/\[TOKEN\]/', $this->newToken);

			// Replace Order Template Variables
			$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . $template);
			$orderHtml = "";
			for($i=0; $i < count($orderEmail); $i++){
				$orderHtml .= $findReplace->Execute($orderEmail[$i]);
			}

			$findReplace = new FindReplace();
			$findReplace->Add('/\[BODY\]/', $orderHtml);
			$findReplace->Add('/\[NAME\]/', $this->Contact->Person->GetFullName());
			// Get Standard Email Template
			$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
			$emailBody = "";
			for($i=0; $i < count($stdTmplate); $i++){
				$emailBody .= $findReplace->Execute($stdTmplate[$i]);
			}

			$mail = new htmlMimeMail5();
			$mail->setFrom($GLOBALS['EMAIL_FROM']);
			$mail->setSubject(sprintf("%s Password Assistance", $GLOBALS['COMPANY']));
			$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
			$mail->setHTML($emailBody);

			$mail->send(array($this->GetEmail()));
		}
	}

	function ResendEmail(){
		$this->SendEmail(true);
	}

	function SendEmail($resending=false){
		if(empty($this->Contact->Person->ID)) $this->Contact->Get();
		if(strlen($this->GetEmail()) > 0) {
			if($resending){
				$this->GeneratePassword();
			}

			$template = ($resending)?"lib/templates/email_lostPassword.tpl":"lib/templates/email_newUser.tpl";
			// Get Order Template
			$findReplace = new FindReplace;
			$findReplace->Add('/\[EMAIL\]/', $this->GetEmail());
			
			$findReplace->Add('/\[PASSWORD\]/', $this->tempPassword);

			// Replace Order Template Variables
			$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . $template);
			$orderHtml = "";
			for($i=0; $i < count($orderEmail); $i++){
				$orderHtml .= $findReplace->Execute($orderEmail[$i]);
			}

			$findReplace = new FindReplace();
			$findReplace->Add('/\[BODY\]/', $orderHtml);
			$findReplace->Add('/\[NAME\]/', $this->Contact->Person->GetFullName());
			// Get Standard Email Template
			$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
			$emailBody = "";
			for($i=0; $i < count($stdTmplate); $i++){
				$emailBody .= $findReplace->Execute($stdTmplate[$i]);
			}

			$mail = new htmlMimeMail5();
			$mail->setFrom($GLOBALS['EMAIL_FROM']);
			$mail->setSubject(sprintf("%s Registration Confirmation", $GLOBALS['COMPANY']));
			$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
			$mail->setHTML($emailBody);
			$mail->send(array($this->GetEmail()));
		}
	}

	function IsEmailUnique($email){
		$checkEmail = new DataQuery(sprintf("SELECT cus.Customer_ID FROM customer AS cus WHERE cus.Username='%s' AND cus.Is_Active='Y'", mysql_real_escape_string($email)));
		$checkEmail->Disconnect();

		if($checkEmail->TotalRows >0){
			$this->ID = $checkEmail->Row['Customer_ID'];
			return false;
		} else {
			return true;
		}
	}

	function GetAccountType(){
		if($this->Contact->HasParent){
			return "Business";
		} else {
			return "Home";
		}
	}

	function GetContacts(){
		$this->Contacts = new CustomerContactCollection($this);
	}

	function GetRemaingAllowance($orderID = null){
		$today = getDatetime();
		$thisMonth = cDatetime($today, 'm');
		$thisYear = cDatetime($today, 'y');
		$nextMonth = $thisMonth + 1;
		$nextYear = $thisYear;
		if($nextMonth == 13){
			$nextMonth = 1;
			++$nextYear;
		}
		$thisMonth = ($thisMonth<10)?'0'.$thisMonth:$thisMonth;
		$nextMonth = ($nextMonth<10)?'0'.$nextMonth:$nextMonth;

		$startDate = sprintf('%s-%s-01 00:00:00', $thisYear, $thisMonth);
		$endDate = sprintf('%s-%s-01 00:00:00', $nextYear, $nextMonth);

		if(!is_numeric($this->ID)){
			return false;
		}
		// set sql
		if(is_null($orderID)) {
			$sql = sprintf("SELECT SUM(o.Total-o.Discount_Reward) AS Total FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID WHERE o.Customer_ID=%d AND o.Ordered_On BETWEEN '%s' AND '%s' AND o.Is_Sample='N' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND pm.Reference LIKE 'credit'", mysql_real_escape_string($this->ID), mysql_real_escape_string($startDate), mysql_real_escape_string($endDate));

			$sql = sprintf("SELECT SUM(o.Total-o.Discount_Reward) AS Total FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID WHERE o.Customer_ID=%d AND o.Ordered_On BETWEEN '%s' AND '%s' AND o.Is_Sample='N' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND pm.Reference LIKE 'credit'", $this->ID, $startDate, $endDate);
		} else {
			$sql = sprintf("SELECT SUM(o.Total-o.Discount_Reward) AS Total FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID WHERE o.Customer_ID=%d AND o.Ordered_On BETWEEN '%s' AND '%s' AND o.Is_Sample='N' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND pm.Reference LIKE 'credit' AND o.Order_ID<>%d", mysql_real_escape_string($this->ID), mysql_real_escape_string($startDate), mysql_real_escape_string($endDate), mysql_real_escape_string($orderID));
		}

		$data = new DataQuery($sql);
		$orderTotal = $data->Row['Total'];
		$data->Disconnect();

		$remaining = $this->CreditLimit - $orderTotal;

		if($remaining < 0){
			$remaining = 0;
		}
		$this->CreditRemaining = $remaining;
		return $remaining;
	}

	function FindByEmail($email){
		$sql = sprintf("SELECT cu.* FROM person as p
							inner join contact as c on p.Person_ID=c.Person_ID
							inner join customer as cu on c.Contact_ID=cu.Contact_ID
							where Email='%s' and c.Is_Active='Y'", mysql_real_escape_string($email));
		$returnValue = false;
		// we want to get the person ID
		$data = new DataQuery($sql);
		if($data->TotalRows > 0){
			$this->ID = $data->Row['Customer_ID'];
			$this->Contact->ID = $data->Row['Contact_ID'];
			$this->Username = $data->Row['Username'];
			$this->Password = $data->Row['Password'];
			$this->IsActive = $data->Row['Is_Active'];
			$this->IsAffiliate = $data->Row['Is_Affiliate'];
			$this->LastLoginOn = $data->Row['Last_Login_On'];
			$this->TotalLogins = $data->Row['Total_Logins'];
			$this->CreditLimit = $data->Row['Credit_Limit'];
			$this->CreditPeriod = $data->Row['Credit_Period'];
			$this->IsCreditActive = $data->Row['Is_Credit_Active'];
			$this->ContactMethod = $data->Row['Contact_Method_ID'];
			$this->FirstSaleOn = $data->Row['Sale_First_On'];
			$this->LastSaleOn = $data->Row['Sale_Last_On'];
			$this->SalesRep = $data->Row['Sales_Rep_ID'];
			$this->FoundVia = $data->Row['Found_Via_ID'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$this->Subscriptions = $data->Row['Subscriptions'];
			$this->PasswordChangedOn = $data->Row['PasswordChangedOn'];

			$returnValue = true;
		}
		$data->Disconnect();

		return $returnValue;
	}

	function Redirect(){
		if($_SERVER['PHP_SELF'] != '/changePassword.php'){
			redirect(sprintf("Location: %schangePassword.php?direct=%s&imodsid=%s", ($GLOBALS['USE_SSL']) ? $GLOBALS['HTTPS_SERVER'] : $GLOBALS['HTTP_SERVER'], $_SERVER['PHP_SELF'], base64_encode(session_id())));
		}
	}
}