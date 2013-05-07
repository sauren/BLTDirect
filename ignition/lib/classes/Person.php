<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Address.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Greeting.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Customer.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Supplier.php");

class Person{
	var $ID;
	var $Title;
	var $Name;
	var $LastName;
	var $Initial;
	var $DOB;
	var $Address;
	var $Department;
	var $Position;
	var $Division;
	var $Greeting;
	var $Gender;
	var $Phone1;
	var $Phone1Ext;
	var $Phone2;
	var $Phone2Ext;
	var $Fax;
	var $Mobile;
	var $Email;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function Person($id=NULL){
		$this->Address = new Address;
		$this->Greeting = new Greeting;
		$this->DOB = '0000-00-00 00:00:00';

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("select
											p.*,
											a.Address_Line_1, a.Address_Line_2, a.Address_Line_3, a.City, a.Region_ID, a.Region_Name, a.Country_ID, a.Zip,
											c.Country, c.Address_Format_ID, c.ISO_Code_2, c.Allow_Sales,
											r.Region_Name, r.Region_Code,
											g.Greeting_Text
											from person as p
											left join address as a on p.Address_ID=a.Address_ID
											left join countries as c on a.Country_ID=c.Country_ID
											left join address_format as af on c.Address_Format_ID=af.Address_Format_ID
											left join regions as r on a.Region_ID=r.Region_ID
											left join greeting as g on p.Greeting_ID=g.Greeting_ID
											where p.Person_ID=%d", mysql_real_escape_string($this->ID)));

		$this->Title = $data->Row['Name_Title'];
		$this->Name = $data->Row['Name_First'];
		$this->LastName = $data->Row['Name_Last'];
		$this->Initial = $data->Row['Name_Initial'];
		$this->DOB = $data->Row['DOB'];
		$this->Address->ID = $data->Row['Address_ID'];
		$this->Address->Line1 = $data->Row['Address_Line_1'];
		$this->Address->Line2 = $data->Row['Address_Line_2'];
		$this->Address->Line3 = $data->Row['Address_Line_3'];
		$this->Address->City = $data->Row['City'];
		
		if(empty($data->Row['Region_ID'])){
			$this->Address->Region->ID = $data->Row['Region_ID'];
			$this->Address->Region->Name = $data->Row['Region_Name'];
		} else {
			$this->Address->Region->ID = $data->Row["Region_ID"];
			$this->Address->Region->Name = $data->Row["Region_Name"];
			$this->Address->Region->CountryID = $data->Row["Country_ID"];
			$this->Address->Region->Code = $data->Row["Region_Code"];
		}

		$this->Address->Country->ID = $data->Row['Country_ID'];
		$this->Address->Country->Name = $data->Row['Country'];
		$this->Address->Country->AddressFormat->Get($data->Row['Address_Format_ID']);
		$this->Address->Country->ISOCode2 = $data->Row['ISO_Code_2'];
		$this->Address->Country->AllowSales = $data->Row['Allow_Sales'];
		$this->Address->Zip = $data->Row['Zip'];
		$this->Department = $data->Row['Department'];
		$this->Position = $data->Row['Position'];
		$this->Division = $data->Row['Division'];
		$this->Greeting->ID = $data->Row['Greeting_ID'];
		$this->Greeting->Name = $data->Row['Greeting_Text'];
		$this->Gender = $data->Row['Gender'];
		$this->Phone1 = $data->Row['Phone_1'];
		$this->Phone1Ext = $data->Row['Phone_1_Extension'];
		$this->Phone2 = $data->Row['Phone_2'];
		$this->Phone2Ext = $data->Row['Phone_2_Extension'];
		$this->Fax = $data->Row['Fax'];
		$this->Mobile = $data->Row['Mobile'];
		$this->Email = $data->Row['Email'];
		$this->CreatedOn = $data->Row['Created_On'];
		$this->CreatedBy = $data->Row['Created_By'];
		$this->ModifiedOn = $data->Row['Modified_On'];
		$this->ModifiedBy = $data->Row['Modified_By'];
		
		$data->Disconnect();
		return true;
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("delete from person where Person_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();

		if(empty($this->Address->ID)) {
			$this->Get();
		}

		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM organisation WHERE Address_ID=%d", mysql_real_escape_string($this->Address->ID)));
		if($data->Row['Count'] == 0) {
			$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM person WHERE Address_ID=%d", mysql_real_escape_string($this->Address->ID)));
			if($data2->Row['Count'] == 0) {
				$this->Address->Delete();
			}
			$data2->Disconnect();
		}
		$data->Disconnect();

		return true;
	}

	function Add($connection = null){
		$this->Address->Add($connection);

		$data = new DataQuery(sprintf("insert into person (
											Name_Title,
											Name_First,
											Name_First_Search,
											Name_Initial,
											Name_Last,
											Name_Last_Search,
											DOB,
											Address_ID,
											Department,
											Position,
											Division,
											Greeting_ID,
											Gender,
											Phone_1,
											Phone_1_Extension,
											Phone_2,
											Phone_2_Extension,
											Fax,
											Mobile,
											Email,
											Created_On,
											Created_By,
											Modified_On,
											Modified_By)
											VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', %d,
											'%s', '%s', '%s', %d, '%s', '%s', '%s',
											'%s', '%s', '%s', '%s', '%s', Now(),
											%d, Now(), %d)",
		addslashes(stripslashes($this->Title)),
		addslashes(stripslashes($this->Name)),
		mysql_real_escape_string(preg_replace('/[^a-zA-Z\p{L}\.\'\s\&\-\\\\\/\-]/u', '', $this->Name)),
		mysql_real_escape_string($this->Initial),
		addslashes(stripslashes($this->LastName)),
		mysql_real_escape_string(preg_replace('/[^a-zA-Z\p{L}\.\'\s\&\-\\\\\/\-]/u', '', $this->LastName)),
		mysql_real_escape_string($this->DOB),
		mysql_real_escape_string($this->Address->ID),
		addslashes(stripslashes($this->Department)),
		addslashes(stripslashes($this->Position)),
		addslashes(stripslashes($this->Division)),
		mysql_real_escape_string($this->Greeting->ID),
		mysql_real_escape_string($this->Gender),
		addslashes(stripslashes($this->Phone1)),
		addslashes(stripslashes($this->Phone1Ext)),
		addslashes(stripslashes($this->Phone2)),
		addslashes(stripslashes($this->Phone2Ext)),
		addslashes(stripslashes($this->Fax)),
		addslashes(stripslashes($this->Mobile)),
		addslashes(stripslashes($this->Email)),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])), $connection);
		$this->ID = $data->InsertID;
		$data->Disconnect();

		return true;
	}

	function Update(){
		if($this->Address->ID == 0 || empty($this->Address->ID)){
			$this->Address->Add();
		} else {
			$this->Address->Update();
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$sql = sprintf("update person set
											Name_Title='%s',
											Name_First='%s',
											Name_First_Search='%s',
											Name_Initial='%s',
											Name_Last='%s',
											Name_Last_Search='%s',
											DOB='%s',
											Address_ID=%d,
											Department='%s',
											Position='%s',
											Division='%s',
											Greeting_ID=%d,
											Gender='%s',
											Phone_1='%s',
											Phone_1_Extension='%s',
											Phone_2='%s',
											Phone_2_Extension='%s',
											Fax='%s',
											Mobile='%s',
											Email='%s',
											Modified_On=Now(),
											Modified_By=%d
											where Person_ID=%d",
		mysql_real_escape_string(stripslashes($this->Title)),
		mysql_real_escape_string(stripslashes($this->Name)),
		mysql_real_escape_string(preg_replace('/[^a-zA-Z\p{L}\.\'\s\&\-\\\\\/\-]/u', '', $this->Name)),
		mysql_real_escape_string($this->Initial),
		mysql_real_escape_string($this->LastName),
		mysql_real_escape_string(preg_replace('/[^a-zA-Z\p{L}\.\'\s\&\-\\\\\/\-]/u', '', $this->LastName)),
		mysql_real_escape_string($this->DOB),
		mysql_real_escape_string($this->Address->ID),
		mysql_real_escape_string(stripslashes($this->Department)),
		mysql_real_escape_string(stripslashes($this->Position)),
		mysql_real_escape_string(stripslashes($this->Division)),
		mysql_real_escape_string($this->Greeting->ID),
		mysql_real_escape_string($this->Gender),
		mysql_real_escape_string(stripslashes($this->Phone1)),
		mysql_real_escape_string(stripslashes($this->Phone1Ext)),
		mysql_real_escape_string(stripslashes($this->Phone2)),
		mysql_real_escape_string(stripslashes($this->Phone2Ext)),
		mysql_real_escape_string(stripslashes($this->Fax)),
		mysql_real_escape_string(stripslashes($this->Mobile)),
		mysql_real_escape_string(stripslashes($this->Email)),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->ID));
		
		new DataQuery($sql);

		$data = new DataQuery(sprintf("SELECT cu.Customer_ID FROM customer AS cu INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID WHERE c.Person_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			Customer::UserName($this->Email, $data->Row['Customer_ID']);
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT u.User_ID FROM users AS u WHERE u.Person_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			User::UserName($this->Email, $data->Row['User_ID']);
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT s.Supplier_ID FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID WHERE c.Person_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			Supplier::UserName($this->Email, $data->Row['Supplier_ID']);
		}
		$data->Disconnect();

		return true;
	}

	function GetFullName() {
		$tempStr = trim(sprintf("%s %s %s %s", $this->Title, $this->Name, $this->Initial, $this->LastName));
	
		if($this->ID > 0) {
			$data = new DataQuery(sprintf("SELECT o.Org_Name FROM contact AS c INNER JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID INNER JOIN organisation AS o ON c2.Org_ID=o.Org_ID WHERE c.Person_ID=%d", mysql_real_escape_string($this->ID)));
			if($data->TotalRows > 0) {
				$tempStr = $data->Row['Org_Name'];
			}
			$data->Disconnect();
		}

		return $tempStr;
	}

	function GetPhone($sep){
		$phone = "";
		if(!empty($this->Phone1)) $phone .= $this->Phone1 . ((!empty($this->Phone1Ext)) ? ' ('.$this->Phone1Ext.')' : '');
		if(!empty($this->Phone2)) $phone .= $sep . $this->Phone2 . ((!empty($this->Phone2Ext)) ? ' ('.$this->Phone2Ext.')' : '');
		if(!empty($this->Mobile)) $phone .= $sep . $this->Mobile;

		return $phone;
	}

	public function validateContact($id,$location){
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
			$contactID = $this->getContactID();

			$inValid = false;
			$errorNote = array();


			$patternName = "/^[a-zA-Z\p{L}+\.\'\s\&\-\\\\\/\-]+$/u";
			$patternPhone = "/^[0-9\+\(\)\-\s]+$/"; 
			$patternAddress = "/^[a-zA-Z0-9\p{L}+\s&,+\"()\:\\\\\/\-\'\.]+$/u";
			$patternEmail = "/^((\"[^\"\f\n\r\t\v\b]+\")|([\w\!\#\$\%\&\'\*\+\-\~\/\^\`\|\{\}]+(\.[\w\!\#\$\%\&\'\*\+\-\~\/\^\`\|\{\}\.]+)*))@((\[(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))\])|(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))|((([A-Za-z0-9\-_])+\.)+[A-Za-z\-]+))$/";


			$NameError = " must contain %s alphabetic characters. The following characters  /\&,-' are also permitted including spaces.";
			$AddressError = " must contain %s alpha numeric characters including spaces. The following characters  /\&,-+:.()' are also permitted including spaces.";
			$emailError = " Email must be a valid email address.";
			$phoneError = " Phone number must contain numeric characters and spaces only.";
			$anyError = " is required.";


			if(preg_match($patternName ,$this->Name)){
			}else{
				$inValid = true;
			}
			if(preg_match($patternName ,$this->LastName)){
			}else{
				$inValid = true;
			}
			if(preg_match($patternPhone ,$this->Phone1)){
			}else{
				$inValid = true;
			}
			if(preg_match($patternEmail ,$this->Email)){
			}else{
				$inValid = true;
			}
			if(preg_match($patternAddress ,$this->Address->Line1)){
			}else{
				$inValid = true;
			}
			if(preg_match($patternAddress ,$this->Address->Line2)){
			}else{
				$inValid = true;
			}
			if(preg_match($patternAddress ,$this->Address->City)){
			}else{
				$inValid = true;
			}
			
			if($this->Address->Region->ID == 0){
				$inValid = true;
			}
			if($this->Address->Country->ID == 0){
				$inValid = true;
			}


			//Front End
			if($inValid && $location=='F'){
				redirect("Location: profile.php?status=update");
				exit;
			}

			//Telesales
			if($inValid && $location=='T'){
				//redirect("Location: order_shipping.php?status=update&type=p");
				redirect(sprintf("location:contact_profile.php?action=updateind&status=update&cid=%d",$contactID));
				exit;
			}

			//Ignition
			if($inValid && $location=='I'){
				//redirect(sprintf("Location: order_shipping.php?status=update&type=p"));
				redirect(sprintf("location:contact_profile.php?action=updateind&status=update&cid=%d",$contactID));
				exit;
			}

			return true;
		}
	}


	public function getContactID(){

		$data = new DataQuery(sprintf("SELECT c.Contact_ID from contact as c 
		left join person as p on p.Person_ID = c.Person_ID
		where p.Person_ID = %d", mysql_real_escape_string($this->ID)));
		
		$contact = $data->Row['Contact_ID'];

		$data->Disconnect();

		return $contact;
	}


}