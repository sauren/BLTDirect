<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Branch.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Country.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Password.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Person.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PublicHoliday.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Template.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserIPAccess.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserHoliday.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserAccess.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PublicHoliday.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Timesheet.php');

class User {
	public static function UserHasAccess($userId, $level) {
		if(!is_numeric($userId)){
			return false;
		}
		$data = new DataQuery(sprintf("select userAccessId, accessId from user_access where userId = %d", mysql_real_escape_string($userId)));
		$accessCache[$data->Row['accessId']] = $data->Row['userAccessId'];
		
		return isset($accessCache[$level]);
	}

	var $ID;
	var $IP;
	var $Person;
	var $Username;
	var $Password;
	var $PasswordMailbox;
	var $Country;
	var $IsActive;
	var $IsPacker;
	var $IsPayroll;
	var $IsCasualWorker;
	var $Hours;
	var $RequireTimesheetDescription;
	var $ShowSessions;
	var $CanBypassWorkTasks;
	var $IsLocked;
	var $FailedLogins;
	var $PasswordChangedOn;
	var $SecretQuestion;
	var $SecretAnswer;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $SecondaryMailbox;
	var $SecondaryMailboxPassword;
	var $Branch;
	
	var $AccessCache = array();

	function __construct($id=NULL) {
		$this->Branch = new Branch();
		$this->Person = new Person();
		$this->IP = new UserIPAccess();
		$this->IsPacker = 'N';
		$this->IsPayroll = 'N';
		$this->RequireTimesheetDescription = 'N';
		$this->ShowSessions = 'N';
		$this->CanBypassWorkTasks = 'N';
		$this->IsCasualWorker = 'N';
		$this->IsLocked = 'N';
		$this->PasswordChangedOn = '0000-00-00 00:00:00';

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function GetFromSession($sessionID) {
		$sql = sprintf("FROM sessions
						INNER JOIN users
						ON users.User_ID=sessions.User_ID
						WHERE sessions.Session_ID='%s'", mysql_real_escape_string($sessionID));
						
		return $this->GetUserSQL($sql);
	}

	function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		
		return $this->GetUserSQL(sprintf("FROM users WHERE users.User_ID = %d", mysql_real_escape_string($this->ID)));
	}

	function GetByUsername($username = null) {
		if(!is_null($username)) {
			$this->Username = $username;
		}
		
		return $this->GetUserSQL(sprintf("FROM users WHERE users.User_Name LIKE '%s'", mysql_real_escape_string($this->Username)));
	}

	function GetAllUsers(){
		$i = 0;
		$userDetails = array();
		$data = new DataQuery(sprintf("SELECT u.*, p.Name_Title, p.Name_First, p.Name_Last
							from users u
							left join person as p on u.Person_ID = p.Person_ID"));
		while($data->Row) {
				$userDetails[$i]["User_ID"] = $data->Row['User_ID'];
				$userDetails[$i]["Person_ID"] = $data->Row['Person_ID'];
				$userDetails[$i]["First_Name"]= $data->Row['Name_First'];
				$userDetails[$i]["Last_Name"] = $data->Row['Name_Last'];

				$i++;
			$data->Next();	
		}
		$data->Disconnect();
		return $userDetails;
	}

	function GetUserSQL($tempsql) {
		$user = new DataQuery("SELECT users.* " . $tempsql);
		if($user->TotalRows > 0){
			$this->Username = $user->Row["User_Name"];
			$this->Password = $user->Row["User_Password"];
			$this->PasswordMailbox = $user->Row["Password_Mailbox"];
			$this->ID = $user->Row["User_ID"];
			$this->IsActive = $user->Row["Is_Active"];
			$this->Person->Get($user->Row["Person_ID"]);
			$this->IsPacker = $user->Row["Is_Packer"];
			$this->IsPayroll = $user->Row["Is_Payroll"];
			$this->IsCasualWorker = $user->Row["Is_Casual_Worker"];
			$this->Hours = $user->Row['Hours'];
			$this->RequireTimesheetDescription = $user->Row['RequireTimesheetDescription'];
			$this->ShowSessions = $user->Row['ShowSessions'];
			$this->CanBypassWorkTasks = $user->Row['CanBypassWorkTasks'];
			$this->IsLocked = $user->Row["IsLocked"];
			$this->FailedLogins = $user->Row["FailedLogins"];
			$this->PasswordChangedOn = $user->Row['PasswordChangedOn'];
			$this->SecretQuestion = $user->Row["SecretQuestion"];
			$this->SecretAnswer = $user->Row["SecretAnswer"];
			$this->CreatedOn = $user->Row["Created_On"];
			$this->CreatedBy = $user->Row["Created_By"];
			$this->ModifiedOn = $user->Row["Modified_On"];
			$this->ModifiedBy = $user->Row["Modified_By"];
			$this->SecondaryMailbox = $user->Row["Secondary_Mailbox"];
			$this->SecondaryMailboxPassword = $user->Row["Secondary_Mailbox_Password"];
			$this->Country = new Country($user->Row["Country_ID"]);
			$this->Branch->Get($user->Row["Branch_ID"]);

			if(!$this->IP->GetByUserID($this->ID)) {
				$this->IP->UserID = $this->ID;
				$this->IP->Add();
			}

			$user->Disconnect();
			return true;
		}

		$user->Disconnect();
		return false;
	}

	function Remove($userID) {
		if(!is_numeric($userID)){
			return false;
		}
		if(!is_numeric($userID)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM users WHERE User_ID=%d", mysql_real_escape_string($userID)));
		UserIPAccess::DeleteUser($userID);
	}

	function Update() {
		if($this->FailedLogins >= Setting::GetValue('user_failed_logins')) {
			$this->IsLocked = 'Y';
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("update users set Is_Active='%s', Person_ID=%d, User_Name='%s', User_Password='%s', Password_Mailbox='%s', Is_Packer='%s', Is_Payroll='%s', Is_Casual_Worker='%s', Hours=%f, RequireTimesheetDescription='%s', ShowSessions='%s', CanBypassWorkTasks='%s', IsLocked='%s', FailedLogins=%d, PasswordChangedOn='%s', SecretQuestion='%s', SecretAnswer='%s', Modified_On=NOW(), Modified_By=%d, Secondary_Mailbox='%s', Secondary_Mailbox_Password='%s', Branch_ID=%d WHERE User_ID=%d",
												mysql_real_escape_string($this->IsActive),
												mysql_real_escape_string($this->Person->ID),
												mysql_real_escape_string($this->Username),
												mysql_real_escape_string($this->Password),
												mysql_real_escape_string($this->PasswordMailbox),
												mysql_real_escape_string($this->IsPacker),
												mysql_real_escape_string($this->IsPayroll),
												mysql_real_escape_string($this->IsCasualWorker),
												mysql_real_escape_string($this->Hours),
												mysql_real_escape_string($this->RequireTimesheetDescription),
												mysql_real_escape_string($this->ShowSessions),
												mysql_real_escape_string($this->CanBypassWorkTasks),
												mysql_real_escape_string($this->IsLocked),
												mysql_real_escape_string($this->FailedLogins),
												mysql_real_escape_string($this->PasswordChangedOn),
												mysql_real_escape_string($this->SecretQuestion),
												mysql_real_escape_string($this->SecretAnswer),
												mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
												mysql_real_escape_string($this->SecondaryMailbox),
												mysql_real_escape_string($this->SecondaryMailboxPassword),
												mysql_real_escape_string($this->Branch->ID),
												mysql_real_escape_string($this->ID)));

		$this->IP->Update();

		$this->Person->Email = $this->Username;
		$this->Person->Update();

		return true;
	}
	
	function SetAccessLevels($levels) {
		foreach ($levels as $accessId=>$level) {
			if ($level == 'Y') {
				DataQuery::Upsert("user_access", array("accessId"=>$accessId, "userId"=>$this->ID));
			} else {
				UserAccess::DeleteUser($accessId, $this->ID);
			}
		}
	}
	
	function HasAccess($level) {
		if (!$this->AccessCache) {
			$data = new DataQuery(sprintf("select userAccessId, accessId from user_access where userId = %d", mysql_real_escape_string($this->ID)));
			for (; $data->Row; $data->Next()) {
				$this->AccessCache[$data->Row['accessId']] = $data->Row['userAccessId'];
			}
		}
		
		return isset($this->AccessCache[$level]);
	}

	function GetAccess(){
		$i = 0;
		$userAccess = array();
		$data = new DataQuery(sprintf("SELECT userAccessId, accessId from user_access where userId = %d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
				$userAccess[$i]["AccessId"] = $data->Row['accessId'];
				$i++;
			$data->Next();	
		}
		$data->Disconnect();
		return $userAccess;
	}

	function SetPassword($newPassword){
		$this->Password = md5($newPassword);
		$this->PasswordChangedOn = date('Y-m-d H:i:s');
	}

    function GetMailboxPassword() {
		$password = new Cipher($this->PasswordMailbox);
		$password->Decrypt();

		return $password->Value;
	}

	function SetMailboxPassword($newPassword){
		$password = new Cipher($newPassword);
		$password->Encrypt();

		$this->PasswordMailbox = $password->Value;
	}
	
	function GetSecondaryMailboxPassword() {
		$password = new Cipher($this->SecondaryMailboxPassword);
		$password->Decrypt();

		return $password->Value;
	}

	function SetSecondaryMailboxPassword($newPassword){
		$password = new Cipher($newPassword);
		$password->Encrypt();

		$this->SecondaryMailboxPassword = $password->Value;
	}

	function Add() {
		$insertForm = new DataQuery(sprintf("insert into users (Is_Active, Person_ID, User_Name, User_Password, Password_Mailbox, Is_Packer, Is_Payroll, Is_Casual_Worker, Hours, RequireTimesheetDescription, ShowSessions, CanBypassWorkTasks, IsLocked, FailedLogins, PasswordChangedOn, SecretQuestion, SecretAnswer, Created_On, Created_By, Secondary_Mailbox, Secondary_Mailbox_Password, Branch_ID) values ('%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', %f, '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', NOW(), %d, '%s', '%s', %d)",
												mysql_real_escape_string($this->IsActive),
												mysql_real_escape_string($this->Person->ID),
												mysql_real_escape_string($this->Username),
												mysql_real_escape_string($this->Password),
												mysql_real_escape_string($this->PasswordMailbox),
												mysql_real_escape_string($this->IsPacker),
												mysql_real_escape_string($this->IsPayroll),
												mysql_real_escape_string($this->IsCasualWorker),
												mysql_real_escape_string($this->Hours),
												mysql_real_escape_string($this->RequireTimesheetDescription),
												mysql_real_escape_string($this->ShowSessions),
												mysql_real_escape_string($this->CanBypassWorkTasks),
												mysql_real_escape_string($this->IsLocked),
												mysql_real_escape_string($this->FailedLogins),
												mysql_real_escape_string($this->PasswordChangedOn),
												mysql_real_escape_string($this->SecretQuestion),
												mysql_real_escape_string($this->SecretAnswer),
												mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
												mysql_real_escape_string($this->SecondaryMailbox),
												mysql_real_escape_string($this->SecondaryMailboxPassword),
												mysql_real_escape_string($this->Branch->ID)));

		$this->IP->UserID = $insertForm->InsertID;
		$this->IP->Add();
		
		$this->ID = $insertForm->InsertID;

		return $insertForm->InsertID;
	}

	function IsUnique(){
		$check = new DataQuery(sprintf("select User_ID from users where User_Name='%s'", mysql_real_escape_string($this->Username)));
		if($check->TotalRows > 0){
			$check->Disconnect();
			return false;
		} else {
			$check->Disconnect();
			return true;
		}
	}
	
	function Recalculate() {
		if(!is_numeric($this->ID)){
			return false;
		}
		if($this->IsCasualWorker == 'Y') {
			new DataQuery(sprintf("DELETE FROM timesheet WHERE Public_Holiday_ID>0 AND User_ID=%d", mysql_real_escape_string($this->ID)));
		} else {
	        $publicHoliday = new PublicHoliday();

	        $data = new DataQuery(sprintf("SELECT Public_Holiday_ID FROM public_holiday WHERE Holiday_Date>=NOW()"));
			while($data->Row) {
				$publicHoliday->Get($data->Row['Public_Holiday_ID']);
				$publicHoliday->Recalculate($this->ID);

				$data->Next();
			}
			$data->Disconnect();

	        $userHoliday = new UserHoliday();

			$data = new DataQuery(sprintf("SELECT User_Holiday_ID FROM user_holiday WHERE User_ID=%d AND Start_Date>=NOW()", mysql_real_escape_string($this->ID)));
			while($data->Row) {
				$userHoliday->Get($data->Row['User_Holiday_ID']);
				$userHoliday->Recalculate();

				$data->Next();
			}
			$data->Disconnect();
		}
	}

	function getPermissions(){
		$permissions = array();
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT reg.Script_File FROM registry_permissions rp inner join registry as reg on rp.Registry_ID=reg.Registry_ID INNER JOIN user_access AS ua ON ua.accessId=rp.Access_ID where ua.userId=%d AND rp.Permission_ID>1", mysql_real_escape_string($this->ID)));
		while($data->Row){
			$permissions[] = $data->Row['Script_File'];
			
			$data->Next();
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT r.Script_File FROM user_registry AS ur INNER JOIN registry AS r ON r.Registry_ID=ur.registryId WHERE ur.userId=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			if(!in_array($data->Row['Script_File'], $permissions)) {
				$permissions[] = $data->Row['Script_File'];
			}
			
			$data->Next();
		}
		$data->Disconnect();

		return $permissions;
	}


	static function UserName($Email, $UserID){
		if(!is_numeric($UserID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE users SET User_Name='%s' WHERE User_ID=%d", mysql_real_escape_string($Email), mysql_real_escape_string($UserID)));
	}

	public function IsPasswordOld() {
		return (strtotime($this->PasswordChangedOn) < mktime(0, 0, 0, date('m')-Setting::GetValue('user_password_refresh_months'), date('d'), date('Y'))) ? true : false;
	}

	public function RegeneratePassword() {
		$password = new Password(PASSWORD_LENGTH_USER);

		$this->SetPassword($password->Value);
		$this->IsLocked = 'N';
		$this->FailedLogins = 0;
		$this->Update();

		$findReplace = new FindReplace();
		$findReplace->Add('/\[EMAIL\]/', $this->Username);
		$findReplace->Add('/\[PASSWORD\]/', $password->Value);

		$templateHtml = $findReplace->Execute(Template::GetContent('email_user_regenerated'));

		$findReplace = new FindReplace();
		$findReplace->Add('/\[BODY\]/', $templateHtml);
		$findReplace->Add('/\[NAME\]/', sprintf('%s %s', $this->Person->Name, $this->Person->LastName));

		$templateHtml = $findReplace->Execute(Template::GetContent('email_template_standard'));

		$mail = new htmlMimeMail5();
		$mail->setFrom($GLOBALS['EMAIL_FROM']);
		$mail->setSubject(sprintf('%s - Password Regenerated', $GLOBALS['COMPANY']));
		$mail->setText('This is an HTML email. If you only see this text your email client only supports plain text emails.');
		$mail->setHTML($templateHtml);
		$mail->send(array($this->Person->Email));
	}
}