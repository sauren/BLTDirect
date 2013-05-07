<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/htmlMimeMail5.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierIPAccess.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');

class Supplier {
	var $ID;
	var $Contact;
	var $IP;
	var $Reference;
	var $Username;
	var $Password;
	var $IsActive;
	var $IsDropShipper;
	var $IsStockerOnly;
	var $IsFavourite;
	var $IsBidder;
	var $IsAutoShip;
	var $DropShippperID;
	var $ShowProduct;
	var $FreeShippingMinimumPurchase;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $LastLoginOn;
	var $TotalLogins;
	var $IsDataImported;
	var $IsComparable;

	function Supplier($id=NULL, $connection = null) {
		$this->Contact = new Contact();
		$this->IP = new SupplierIPAccess();
		$this->IsActive = 'Y';
		$this->IsDropShipper = 'Y';
		$this->IsStockerOnly = 'N';
		$this->IsFavourite = 'N';
		$this->IsBidder = 'N';
		$this->IsAutoShip = 'N';
		$this->ShowProduct = 'N';
		$this->IsComparable = 'N';
		$this->LastLoginOn = '0000-00-00 00:00:00';
		$this->IsDataImported = 'N';

		if(!is_null($id)){
			$this->Get($id, $connection);
		}
	}

	function Get($id = NULL, $connection = null) {
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT * FROM supplier WHERE Supplier_ID=%d", mysql_real_escape_string($this->ID)), $connection);
		if($data->TotalRows > 0) {
			$this->Contact->ID = $data->Row['Contact_ID'];
			$this->Reference = $data->Row['Reference'];
			$this->Username = $data->Row['Username'];
			$this->Password = $data->Row['Password'];
			$this->IsActive = $data->Row['Is_Active'];
			$this->IsDropShipper = $data->Row['Is_Drop_Shipper'];
			$this->IsStockerOnly = $data->Row['Is_Stocker_Only'];
			$this->IsFavourite = $data->Row['Is_Favourite'];
			$this->IsBidder = $data->Row['Is_Bidder'];
			$this->IsAutoShip = $data->Row['Is_Auto_Ship'];
			$this->DropShipperID = $data->Row['Drop_Shipper_ID'];
			$this->ShowProduct = $data->Row['Show_Product'];
			$this->FreeShippingMinimumPurchase = $data->Row['Free_Shipping_Minimum_Purchase'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$this->LastLoginOn = $data->Row['Last_Login_On'];
			$this->TotalLogins = $data->Row['Total_Logins'];
			$this->IsDataImported = $data->Row['Is_Data_Imported'];
			$this->IsComparable = $data->Row['Is_Comparable'];

			if(!$this->IP->GetBySupplierID($this->ID)) {
				$this->IP->SupplierID = $this->ID;
				$this->IP->Add();
			}

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

    function GetAddress() {
    	$this->Contact->Get();

		$address = $this->Contact->Person->GetFullName();
		$address .= '<br />';
		$address .= $this->Contact->Person->Address->GetFormatted('<br />');

		return $address;
	}

	function Add($connection = null){
		$sql = sprintf("INSERT INTO supplier(Contact_ID,
												  Reference,
												  Username,
												  Password,
												  Is_Active,
												  Is_Drop_Shipper,
												  Is_Stocker_Only,
												  Is_Favourite,
												  Is_Bidder,
												  Is_Auto_Ship,
												  Drop_Shipper_ID,
												  Show_Product,
												  Free_Shipping_Minimum_Purchase,
												  Created_On,
												  Created_By,
												  Modified_On,
												  Modified_By,
												  Last_Login_On,
												  Total_Logins,
												  Is_Data_Imported,
												  Is_Comparable)
							VALUES (%d, '%s','%s','%s','%s','%s', '%s', '%s', '%s', '%s', %d, '%s', %f, NOW(),%d,NOW(),%d,'%s',%d,'%s','%s')",
		mysql_real_escape_string($this->Contact->ID),
		mysql_real_escape_string($this->Reference),
		mysql_real_escape_string($this->Username),
		mysql_real_escape_string($this->Password),
		mysql_real_escape_string($this->IsActive),
		mysql_real_escape_string($this->IsDropShipper),
		mysql_real_escape_string($this->IsStockerOnly),
		mysql_real_escape_string($this->IsFavourite),
		mysql_real_escape_string($this->IsBidder),
		mysql_real_escape_string($this->IsAutoShip),
		mysql_real_escape_string($this->DropShipperID),
		mysql_real_escape_string($this->ShowProduct),
		mysql_real_escape_string($this->FreeShippingMinimumPurchase),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->LastLoginOn),
		mysql_real_escape_string($this->TotalLogins),
		mysql_real_escape_string($this->IsDataImported),
		mysql_real_escape_string($this->IsComparable));

		$data = new DataQuery($sql, $connection);

		$this->ID = $data->InsertID;

		$this->IP->SupplierID = $insertForm->InsertID;
		$this->IP->Add();

		if(empty($data->Error)) {
			return true;
		}

		return false;
	}

	static function DisableContact($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE supplier SET Is_Active='N' WHERE Contact_ID=%d", mysql_real_escape_string($id)));
	}

	function Update($connection = null){
		if(!is_numeric($this->ID)){
			return false;
		}
		$sql = sprintf("UPDATE supplier SET Reference = '%s',
												Username = '%s',
												Password = '%s',
												Is_Active = '%s',
												Is_Drop_Shipper='%s',
												Is_Stocker_Only='%s',
												Is_Favourite='%s',
												Is_Bidder='%s',
												Is_Auto_Ship='%s',
												Drop_Shipper_ID=%d,
												Show_Product='%s',
												Free_Shipping_Minimum_Purchase=%f,
												Modified_On = Now(),
												Modified_By = %d,
												Last_Login_On = '%s',
												Total_Logins = %d,
												Is_Data_Imported = '%s',
												Is_Comparable = '%s'
							WHERE Supplier_ID = %d",
		mysql_real_escape_string($this->Reference),
		mysql_real_escape_string($this->Username),
		mysql_real_escape_string($this->Password),
		mysql_real_escape_string($this->IsActive),
		mysql_real_escape_string($this->IsDropShipper),
		mysql_real_escape_string($this->IsStockerOnly),
		mysql_real_escape_string($this->IsFavourite),
		mysql_real_escape_string($this->IsBidder),
		mysql_real_escape_string($this->IsAutoShip),
		mysql_real_escape_string($this->DropShipperID),
		mysql_real_escape_string($this->ShowProduct),
		mysql_real_escape_string($this->FreeShippingMinimumPurchase),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->LastLoginOn),
		mysql_real_escape_string($this->TotalLogins),
		mysql_real_escape_string($this->IsDataImported),
		mysql_real_escape_string($this->IsComparable),
		mysql_real_escape_string($this->ID));

		new DataQuery($sql, $connection);

		$this->IP->Update();

		if(empty($this->Contact->Person->ID)) $this->Contact->Get();

		$this->Contact->Person->Email = $this->Username;
		$this->Contact->Person->Update();

		return true;
	}

	function Delete($id = NULL){
		if(!is_null($id)) $this->ID = $id;

		new DataQuery(sprintf("delete from supplier where Supplier_ID = %d",mysql_real_escape_string($this->ID)));
		SupplierProduct::DeleteSupplier($this->ID);
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT * FROM warehouse WHERE `Type`='S' AND Type_Reference_ID = %d",mysql_real_escape_string($this->ID)));

		if($data->TotalRows>0){
			$warehouse = new Warehouse($data->Row['Warehouse_ID']);
			$warehouse->Delete();
		}
	}

	function IsUnique($username=NULL){
		if(!is_null($username)) $this->Username = $username;
		$check = new DataQuery(sprintf("select Supplier_ID from supplier where Username='%s'", mysql_real_escape_string($this->Username)));
		if($check->TotalRows > 0){
			$this->ID = $check->Row['Supplier_ID'];
			$check->Disconnect();
			return false;
		}
		else{
			$check->Disconnect();
			return true;
		}
	}

	function GetPassword(){
		$password = new Cipher($this->Password);
		$password->Decrypt();
		return $password->Value;
	}

	function SetPassword($newPassword){
		$password = new Cipher($newPassword);
		$password->Encrypt();
		$this->Password = $password->Value;
		return true;
	}

	function GetEmail() {
		if(empty($this->Contact->Person->ID)) {
			$this->Contact->Get();
		}

		return $this->Contact->Person->Email;
	}

	function ResendEmail(){
		$this->SendEmail(true);
	}

	function SendEmail($resending = false){
		if(empty($this->Contact->Person->ID)) $this->Contact->Get();
		$template = ($resending)?"lib/templates/email_lostSupplierPassword.tpl":"lib/templates/email_newSupplier.tpl";

		$findReplace = new FindReplace;
		$findReplace->Add('/\[EMAIL\]/',$this->GetEmail());
		$findReplace->Add('/\[PASSWORD\]/',$this->GetPassword());

		$orderEmail = file($GLOBALS["DIR_WS_ADMIN"].$template);
		$orderHtml = "";
		for($i=0; $i < count($orderEmail); $i++){
			$orderHtml .= $findReplace->Execute($orderEmail[$i]);
		}

		unset($findReplace);
		$findReplace =  new FindReplace;
		$findReplace->Add('/\[BODY\]/',$orderHtml);
		$findReplace->Add('/\[NAME\]/', $this->Contact->Person->getFullName());

		$stdTemplate = file($GLOBALS["DIR_WS_ADMIN"]."lib/templates/email/template_standard.tpl");
		$emailBody = "";
		for($i=0; $i<count($stdTemplate); $i++){
			$emailBody .=$findReplace->Execute($stdTemplate[$i]);
		}

		$mail = new htmlMimeMail5();
		$mail->setFrom($GLOBALS['EMAIL_FROM']);
		$mail->setSubject(sprintf("%s Supplier Registration Confirmed", $GLOBALS['COMPANY']));
		$mail->setText('This is an HTML email. IF you only see this text your email client only supports plain text emails.');
		$mail->setHTML($emailBody);
		$mail->send(array($this->GetEmail()));
	}

	function IsEmailUnique($email){
		$checkEmail = new DataQuery(sprintf("SELECT sup.Supplier_ID
												FROM supplier AS sup
												INNER JOIN contact AS con
												ON con.Contact_ID = sup.Contact_ID
												INNER JOIN person AS per
												ON per.Person_ID=con.Person_ID
												WHERE sup.Username='%s' AND sup.Is_Active='Y'", mysql_real_escape_string($email)));
		$checkEmail->Disconnect();
		if($checkEmail->TotalRows >0){
			$this->ID = $checkEmail->Row['Supplier_ID'];
			return false;
		} else {
			return true;
		}
	}

	static function UserName($Email, $SupplierID){
		new DataQuery(sprintf("UPDATE supplier SET Username='%s' WHERE Supplier_ID=%d", mysql_real_escape_string($Email), mysql_real_escape_string($SupplierID)));
	}
}