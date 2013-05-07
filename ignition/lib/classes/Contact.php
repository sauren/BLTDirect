<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ContactAccount.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ContactStatus.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CustomerContact.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Customer.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Supplier.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ContactAppointment.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DiscountCustomer.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DiscountBandingBasket.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CustomerProduct.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ContactGroupAssoc.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CampaignContactEvent.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CustomerProductGroup.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CustomerProductGroupItem.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DataQuery.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Image.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Person.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Organisation.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");

class Contact {
	const SOLICIT_MOBILE = 0x01;

	public $ID;
	public $Status;
	public $Parent;
	public $IntegrationReference;
	public $IsIntegrationLocked;
	public $Type;
	public $Person;
	public $Solicitation;
	public $Organisation;
	public $IsActive;
	public $IsTest;
	public $IsTemporary;
	public $IsEmailInvalid;
	public $IsCustomer;
	public $IsSupplier;
	public $IsCreditContact;
	public $IsHighDiscount;
	public $IsProformaAccount;
	public $IsTradeAccount;
	public $TradeImage;
	public $OnMailingList;
	public $GenerateGroups;
	public $NominalCode;
	public $PositionOrders;
	public $PositionTurnover;
	public $IsCatalogueRequested;
	public $CatalogueSentOn;
	public $CreatedOn;
	public $CreatedBy;
	public $ModifiedOn;
	public $ModifiedBy;
	public $HasParent;
	public $AccountManager;

	public function __construct($id=NULL) {
		$this->Status = new ContactStatus();
		$this->IsIntegrationLocked = 'N';
		$this->IsActive = 'Y';
		$this->IsTest = 'N';
		$this->IsTemporary = 'N';
		$this->IsEmailInvalid = 'N';
		$this->IsCustomer = 'N';
		$this->IsSupplier = 'N';
		$this->IsCreditContact = 'N';
		$this->IsHighDiscount = 'N';
		$this->IsProformaAccount = 'N';
		$this->IsTradeAccount = 'N';
		$this->Person = new Person();
		$this->Organisation = new Organisation();
		$this->HasParent = false;
		$this->OnMailingList = 'H';
		$this->GenerateGroups = 'N';
		$this->IsCatalogueRequested = 'N';
		$this->CatalogueSentOn = '0000-00-00 00:00:00';
		$this->AccountManager = new User();
		
		$this->TradeImage = new Image();
		$this->TradeImage->OnConflict = 'makeunique';
		$this->TradeImage->SetMinDimensions($GLOBALS['TRADE_IMG_MIN_WIDTH'], $GLOBALS['TRADE_IMG_MIN_HEIGHT']);
		$this->TradeImage->SetMaxDimensions($GLOBALS['TRADE_IMG_MAX_WIDTH'], $GLOBALS['TRADE_IMG_MAX_HEIGHT']);
		$this->TradeImage->SetDirectory($GLOBALS['TRADE_IMAGES_DIR_FS']);
		
		$this->SetSolicitation(self::SOLICIT_MOBILE);

		if(!is_null($id) && is_numeric($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	public function Get($id=NULL) {
		if(!is_null($id) && is_numeric($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM contact WHERE Contact_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			if(!empty($data->Row['Parent_Contact_ID'])) {
				$this->Parent = new Contact();
			}

			$this->Parent->ID = $data->Row['Parent_Contact_ID'];
			$this->Status->ID = $data->Row['Contact_Status_ID'];
			$this->IntegrationReference = $data->Row['Integration_Reference'];
			$this->IsIntegrationLocked = $data->Row['Is_Integration_Locked'];
			$this->Type = $data->Row['Contact_Type'];
			$this->Person->ID = $data->Row['Person_ID'];
			$this->Solicitation = $data->Row['Solicitation'];
			$this->Organisation->ID = $data->Row['Org_ID'];
			$this->IsActive = $data->Row['Is_Active'];
			$this->IsTest = $data->Row['Is_Test'];
			$this->IsTemporary = $data->Row['Is_Temporary'];
			$this->IsEmailInvalid = $data->Row['Is_Email_Invalid'];
			$this->IsCustomer = $data->Row['Is_Customer'];
			$this->IsSupplier = $data->Row['Is_Supplier'];
			$this->IsCreditContact = $data->Row['Is_Credit_Contact'];
			$this->IsHighDiscount = $data->Row['Is_High_Discount'];
			$this->IsProformaAccount = $data->Row['IsProformaAccount'];
			$this->IsTradeAccount = $data->Row['IsTradeAccount'];
			$this->TradeImage->FileName = $data->Row['TradeImage'];
			$this->NominalCode = $data->Row['Nominal_Code'];
			$this->PositionOrders = $data->Row['Position_Orders'];
			$this->PositionTurnover = $data->Row['Position_Turnover'];
			$this->IsCatalogueRequested = $data->Row['Is_Catalogue_Requested'];
			$this->CatalogueSentOn = $data->Row['Catalogue_Sent_On'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$this->OnMailingList = $data->Row['On_Mailing_List'];
			$this->GenerateGroups = $data->Row['Generate_Groups'];			
			$this->AccountManager->ID = $data->Row['Account_Manager_ID'];

			switch($this->Type){
				case 'I':
					$this->Person->Get();
					break;
				case 'O':
					$this->Organisation->Get();
					break;
			}

			if(!empty($this->Parent->ID)){
				$this->HasParent = $this->Parent->Get();
			}

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	static function DisableContact($id){


		if(!is_numeric($id)){
			return false;
		}

		new DataQuery(sprintf("UPDATE contact SET Is_Active='N' WHERE Contact_ID=%d", mysql_real_escape_string($id)));
	}

	static function ContactParent($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE contact SET Parent_Contact_ID=0 WHERE Parent_Contact_ID=%d", mysql_real_escape_string($id)));
	}

	static function RemoveContactsOrganistation($id){

		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE contact SET Parent_Contact_ID=0 WHERE Contact_ID=%d", mysql_real_escape_string($id)));
	}


	static function DeleteOrganisation($id){

		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM contact WHERE Org_ID=%d", mysql_real_escape_string($id)));
	}

	function Delete($id=NULL, $forceDelete = false) {
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}

		if(empty($this->TradeImage->FileName)) {
			$this->Get();
		}


		if(!is_numeric($this->ID)){
			return false;
		}
		
		/*
		make sure that this contact is not being used
		1. deactivate only if used by customer, supplier, or user files.
		2. destroy if not used
		*/
		if(!$forceDelete && ($this->IsCustomer == 'Y' || $this->IsSupplier=='Y')) {
			self::DisableContact($this->ID);
			Customer::DisableContact($this->ID);
			Supplier::DisableContact($this->ID);

			switch($this->Type){
				case 'O':
					$this->DeleteChildren($forceDelete);
					break;
			}
		} else {
			new DataQuery(sprintf("DELETE FROM contact WHERE Contact_ID=%d", mysql_real_escape_string($this->ID)));
			$data = new ContactAppointment();
			$data->ID = $this->ID;
			$data->Delete();

			if($forceDelete) {
				$data = new DataQuery(sprintf("SELECT Customer_ID FROM customer WHERE Contact_ID=%d", mysql_real_escape_string($this->ID)));
				if($data->TotalRows > 0) {
				CustomerContact::DeleteContact($data->Row['Customer_ID']);
				Customer::DeleteContact($data->Row['Customer_ID']);
				DiscountCustomer::DeleteContact($data->Row['Customer_ID']);
				DiscountBandingBasket::DeleteContact($data->Row['Customer_ID']);
				CustomerProduct::DeleteContact($data->Row['Customer_ID']);
				}
				$data->Disconnect();
				ContactGroupAssoc::DeleteContact($this->ID);
				CampaignContact::DeleteContact($this->ID);
			}

			switch($this->Type){
				case 'I':
					$this->Person->Delete();
					break;
				case 'O':
					$this->Organisation->Delete();
					$this->DeleteChildren($forceDelete);
					break;
			}
		}
		
		// TODO: if this image ever becomes shared between contacts check to ensure its not being used by others before deletion.
		if(!empty($this->TradeImage->FileName) && $this->TradeImage->Exists()) {
			$this->TradeImage->Delete();
		}

		return true;
	}

	function Add($connection = null, $alwaysAddParent = false){
		if($this->Type == 'I'){
			if(is_object($this->Parent)) {
				if(($this->Parent->Type == 'O') && ((!$alwaysAddParent && empty($this->Parent->ID)) || ($alwaysAddParent))) {
					$this->Parent->Add($connection, $alwaysAddParent);
				}
			}
			$this->Person->Add($connection);
		} elseif($this->Type == 'O'){
			$this->Organisation->Add($connection);
		}

		$data = new DataQuery(sprintf("INSERT INTO contact (Contact_Status_ID,
											Parent_Contact_ID,
											Integration_Reference,
											Is_Integration_Locked,
											Contact_Type,
											Person_ID,
											Solicitation,
											Org_ID,
											Is_Active,
											Is_Test,
											Is_Temporary,
											Is_Email_Invalid,
											Is_Customer,
											Is_Supplier,
											Is_Credit_Contact,
											Is_High_Discount,
											IsProformaAccount,
											IsTradeAccount,
											TradeImage,
											On_Mailing_List,
											Generate_Groups,
											Nominal_Code,
											Position_Orders,
											Position_Turnover,
											Is_Catalogue_Requested,
											Catalogue_Sent_On,
											Created_On,
											Created_By,
											Modified_On,
											Modified_By,
											Account_Manager_ID
										    ) VALUES (%d, %d, '%s', '%s', '%s', %d, %d, %d, '%s', '%s',
											'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', NOW(),
										    %d, NOW(), %d, %d)",
		mysql_real_escape_string($this->Status->ID),
		is_object($this->Parent) ? $this->Parent->ID : 0,
		mysql_real_escape_string($this->IntegrationReference),
		mysql_real_escape_string($this->IsIntegrationLocked),
		mysql_real_escape_string($this->Type),
		mysql_real_escape_string($this->Person->ID),
		mysql_real_escape_string($this->Solicitation),
		mysql_real_escape_string($this->Organisation->ID),
		mysql_real_escape_string($this->IsActive),
		mysql_real_escape_string($this->IsTest),
		mysql_real_escape_string($this->IsTemporary),
		mysql_real_escape_string($this->IsEmailInvalid),
		mysql_real_escape_string($this->IsCustomer),
		mysql_real_escape_string($this->IsSupplier),
		mysql_real_escape_string($this->IsCreditContact),
		mysql_real_escape_string($this->IsHighDiscount),
		mysql_real_escape_string($this->IsProformaAccount),
		mysql_real_escape_string($this->IsTradeAccount),
		mysql_real_escape_string($this->TradeImage->FileName),
		mysql_real_escape_string($this->OnMailingList),
		mysql_real_escape_string($this->GenerateGroups),
		mysql_real_escape_string($this->NominalCode),
		mysql_real_escape_string($this->PositionOrders),
		mysql_real_escape_string($this->PositionTurnover),
		mysql_real_escape_string($this->IsCatalogueRequested),
		mysql_real_escape_string($this->CatalogueSentOn),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->AccountManager->ID), $connection));

		$this->ID = $data->InsertID;

		return true;
	}

	function Update(){
		if($this->Type == 'I'){
			$this->Person->Update();
		} elseif($this->Type == 'O'){
			$this->Organisation->Update();
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT Account_Manager_ID FROM contact WHERE Contact_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$oldAccountManagerId = $data->Row['Account_Manager_ID'];
			$newAccountManagerId = $this->AccountManager->ID;

			if($oldAccountManagerId <> $newAccountManagerId) {
				$handle = 0;

				if(($oldAccountManagerId == 0) && ($newAccountManagerId > 0)) {
					$handle += 1;
				} elseif(($oldAccountManagerId > 0) && ($newAccountManagerId > 0)) {
					$handle += 1;
					$handle += 2;
				} elseif(($oldAccountManagerId > 0) && ($newAccountManagerId == 0)) {
					$handle += 2;
				}

				if($handle & 1) {
					$account = new ContactAccount();
					$account->ContactID = $this->ID;
					$account->AccountManagerID = $this->AccountManager->ID;
					$account->StartAccountOn = date('Y-m-d H:i:s');
					$account->Add();
				}

				if($handle & 2) {
					$data2 = new DataQuery(sprintf("SELECT Contact_Account_ID FROM contact_account WHERE Contact_ID=%d AND Account_Manager_ID=%d AND End_Account_On='0000-00-00 00:00:00'", mysql_real_escape_string($this->ID), mysql_real_escape_string($oldAccountManagerId)));
					while($data2->Row) {
						$account = new ContactAccount($data2->Row['Contact_Account_ID']);
						$account->EndAccountOn = date('Y-m-d H:i:s');
						$account->Update();

						$data2->Next();
					}
					$data2->Disconnect();
				}
			}
		}
		$data->Disconnect();
		
		if($this->GenerateGroups == 'Y') {
			$data = new DataQuery(sprintf("SELECT Generate_Groups FROM contact WHERE Contact_ID=%d", mysql_real_escape_string($this->ID)));
			if($data->TotalRows > 0) {
				if($data->Row['Generate_Groups'] != $this->GenerateGroups) {
					$groups = array();
					
					$data = new DataQuery(sprintf("SELECT a.Address_Line_1, a.Address_Line_2, a.City FROM customer_contact AS cc INNER JOIN customer AS cu ON cu.Customer_ID=cc.Customer_ID INNER JOIN address AS a ON a.Address_ID=cc.Address_ID WHERE cu.Contact_ID=%d", mysql_real_escape_string($this->ID)));
					while($data->Row) {
						$key = array();
						
						if(!empty($data->Row['Address_Line_1'])) {
							$key[] = $data->Row['Address_Line_1'];
						}
						
						if(!empty($data->Row['Address_Line_2'])) {
							$key[] = $data->Row['Address_Line_2'];
						}
						
						if(!empty($data->Row['City'])) {
							$key[] = $data->Row['City'];
						}
						
						$key = implode(', ', $key);
						
						$groups[$key] = $key;
					
						$data->Next();
					}
					$data->Disconnect();
					
					$data = new DataQuery(sprintf("SELECT cpg.name FROM customer_product_group AS cpg INNER JOIN customer AS c ON c.Customer_ID=cpg.customerId WHERE c.Contact_ID=%d", mysql_real_escape_string($this->ID)));
					while($data->Row) {
						if(isset($groups[strtolower($data->Row['name'])])) {
							unset($groups[strtolower($data->Row['name'])]);
						}						
						
						$data->Next();
					}
					$data->Disconnect();
					
					$data = new DataQuery(sprintf("SELECT Customer_ID FROM customer WHERE Contact_ID=%d", mysql_real_escape_string($this->ID)));
					if($data->TotalRows > 0) {
						foreach($groups as $group) {
							$productGroup = new CustomerProductGroup();
							$productGroup->customer->ID = $data->Row['Customer_ID'];
							$productGroup->name = $group;
							$productGroup->add();
						}
						
						$groups = array();
						
						$data2 = new DataQuery(sprintf("SELECT cpg.id, cpg.name FROM customer_product_group AS cpg INNER JOIN customer AS c ON c.Customer_ID=cpg.customerId WHERE c.Contact_ID=%d", mysql_real_escape_string($this->ID)));
						while($data2->Row) {
							$groups[strtolower($data2->Row['name'])] = $data2->Row;
							
							$data2->Next();
						}
						$data2->Disconnect();
							
						$data2 = new DataQuery(sprintf("SELECT o.Shipping_Address_1, o.Shipping_Address_2, o.Shipping_City, ol.Product_ID, COUNT(*) FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE ol.Product_ID>0 AND o.Customer_ID=%d GROUP BY ol.Product_ID, o.Shipping_Address_1, o.Shipping_Address_2, o.Shipping_City", mysql_real_escape_string($data->Row['Customer_ID'])));
						while($data2->Row > 0) {
							$key = array();
						
							if(!empty($data2->Row['Shipping_Address_1'])) {
								$key[] = $data2->Row['Shipping_Address_1'];
							}
							
							if(!empty($data2->Row['Shipping_Address_2'])) {
								$key[] = $data2->Row['Shipping_Address_2'];
							}
							
							if(!empty($data2->Row['Shipping_City'])) {
								$key[] = $data2->Row['Shipping_City'];
							}
							
							$key = implode(', ', $key);
							
							if(isset($groups[strtolower($key)])) {
								$data3 = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM customer_product_group_item WHERE groupId=%d AND productId=%d", mysql_real_escape_string($groups[strtolower($key)]['id']), mysql_real_escape_string($data2->Row['Product_ID'])));
								if($data3->Row['Count'] == 0) {
									$groupItem = new CustomerProductGroupItem();
									$groupItem->group->id = $groups[strtolower($key)]['id'];
									$groupItem->product->ID = $data2->Row['Product_ID'];
									$groupItem->add();
								}
								$data3->Disconnect();
							}
						
							$data2->Next();
						}
						$data2->Disconnect();
					}
					$data->Disconnect();
				}
			}		
			$data->Disconnect();
		}
			
		$sql = sprintf("UPDATE contact SET Contact_Status_ID=%d,
											Parent_Contact_ID=%d,
											Integration_Reference='%s',
											Is_Integration_Locked='%s',
											Contact_Type='%s',
											Person_ID=%d,
											Solicitation=%d,
											Org_ID=%d,
											Is_Active='%s',
											Is_Test='%s',
											Is_Temporary='%s',
											Is_Email_Invalid='%s',
											Is_Customer='%s',
											Is_Supplier='%s',
											Is_Credit_Contact='%s',
											Is_High_Discount='%s',
											IsProformaAccount='%s',
											IsTradeAccount='%s',
											TradeImage='%s',
											On_Mailing_List='%s',
											Generate_Groups='%s',
											Nominal_Code='%s',
											Position_Orders=%d,
											Position_Turnover=%d,
											Is_Catalogue_Requested='%s',
											Catalogue_Sent_On='%s',
											Modified_On=NOW(),
											Modified_By=%d,
											Account_Manager_ID=%d
											WHERE Contact_ID=%d",
		mysql_real_escape_string($this->Status->ID),
		mysql_real_escape_string($this->Parent->ID),
		mysql_real_escape_string($this->IntegrationReference),
		mysql_real_escape_string($this->IsIntegrationLocked),
		mysql_real_escape_string($this->Type),
		mysql_real_escape_string($this->Person->ID),
		mysql_real_escape_string($this->Solicitation),
		mysql_real_escape_string($this->Organisation->ID),
		mysql_real_escape_string($this->IsActive),
		mysql_real_escape_string($this->IsTest),
		mysql_real_escape_string($this->IsTemporary),
		mysql_real_escape_string($this->IsEmailInvalid),
		mysql_real_escape_string($this->IsCustomer),
		mysql_real_escape_string($this->IsSupplier),
		mysql_real_escape_string($this->IsCreditContact),
		mysql_real_escape_string($this->IsHighDiscount),
		mysql_real_escape_string($this->IsProformaAccount),
		mysql_real_escape_string($this->IsTradeAccount),
		mysql_real_escape_string($this->TradeImage->FileName),
		mysql_real_escape_string($this->OnMailingList),
		mysql_real_escape_string($this->GenerateGroups),
		mysql_real_escape_string($this->NominalCode),
		mysql_real_escape_string($this->PositionOrders),
		mysql_real_escape_string($this->PositionTurnover),
		mysql_real_escape_string($this->IsCatalogueRequested),
		mysql_real_escape_string($this->CatalogueSentOn),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->AccountManager->ID),
		mysql_real_escape_string($this->ID));

		new DataQuery($sql);
				
		$data = new DataQuery(sprintf("SELECT Contact_ID FROM contact WHERE Parent_Contact_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$contact = new Contact($data->Row['Contact_ID']);
			$contact->AccountManager->ID = $this->AccountManager->ID;
			$contact->IsHighDiscount = $this->IsHighDiscount;
			$contact->IsProformaAccount = $this->IsProformaAccount;
			$contact->IsTradeAccount = $this->IsTradeAccount;
			$contact->GenerateGroups = $this->GenerateGroups;
			$contact->Update();

			$data->Next();
		}
		$data->Disconnect();
	}
	
	function UpdateTradeImage($imageField = null) {
		$oldImage = new Image($this->TradeImage->FileName, $this->TradeImage->Directory);

		if(!is_null($imageField) && isset($_FILES[$imageField]) && !empty($_FILES[$imageField]['name'])) {
			if(!$this->TradeImage->Upload($imageField)) {
				return false;
			} else {
				if(!$this->TradeImage->CheckDimensions()) {
					$this->TradeImage->Resize();
				}
				
				$oldImage->Delete();
			}
		}
		
		return true;
	}

	function UpdateAccountManager() {
		if($this->Parent->ID > 0) {
			$this->Parent->Get();
			$this->Parent->AccountManager->ID = $this->AccountManager->ID;
			$this->Parent->Update();
		}
	}
	
	function DeleteChildren($forceDelete = false) {
		$data = new DataQuery(sprintf("SELECT Contact_ID FROM contact WHERE Parent_Contact_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row){
			$contact = new Contact($data->Row['Contact_ID'], $forceDelete);
			$contact->Delete();

			$data->Next();
		}

		$data->Disconnect();

		return true;
	}

	function MakeCustomer(){
		$this->IsCustomer = 'Y';
		$this->Update();
		if($this->Parent->ID != 0 || !empty($this->Parent->ID)){
			$this->Parent->MakeCustomer();
		}
	}

	function MakeSupplier(){
		$this->IsSupplier = 'Y';
		$this->Update();
		if($this->Parent->ID != 0 || !empty($this->Parent->ID)){
			$this->Parent->MakeSupplier();
		}
	}

	function Exists($useZip=true){
		if(strtoupper($this->Type) == 'O'){
			$org = new DataQuery(sprintf("SELECT Org_ID
										FROM organisation AS org
										LEFT JOIN address AS ad ON
										ad.Address_ID=org.Address_ID
										WHERE org.Org_Name='%s' AND ad.Zip='%s'",
			mysql_real_escape_string($this->Organisation->Name),
			mysql_real_escape_string($this->Organisation->Address->Zip)));
			if($org->TotalRows > 0){
				mysql_real_escape_string($this->Organisation->ID = $org->Row['Org_ID']);
				return true;
			}
		} elseif (strtoupper($this->Type) == 'I'){
			if($useZip){
				$ind = new DataQuery(sprintf("SELECT Person_IDmysql_real_escape_string(
												FROM person AS p
												LEFT JOIN address AS ad ON
												p.Address_ID=ad.Address_ID
												WHERE p.Name_First='%s'
												AND p.Name_Last='%s' AND ad.Zip='%s'",
				mysql_real_escape_string($this->Person->Name),
				mysql_real_escape_string($this->Person->LastName),
				mysql_real_escape_string($this->Person->Address->Zip)));
			} else {
				$ind = new DataQuery(sprintf("SELECT Person_ID
										FROM person AS p
										LEFT JOIN address AS ad ON
										p.Address_ID=ad.Address_ID
										WHERE p.Name_First='%s'
										AND p.Name_Last='%s'",
				mysql_real_escape_string($this->Person->Name),
				mysql_real_escape_string($this->Person->LastName)));
			}
			if($ind->TotalRows > 0){
				$this->Person->ID = $ind->Row['Person_ID'];
				return true;
			}
		}

		return false;
	}
	
	public function IsSolicited($solicit) {
		return $this->Solicitation & $solicit;
	}
	
	public function SetSolicitation($solicit) {
		if(!$this->IsSolicited($solicit)) {
			$this->Solicitation += $solicit;
		}
	}
	
	public function SendSms($message) {
		if($this->IsSolicited(self::SOLICIT_MOBILE)) {
			if(!empty($this->Person->Mobile)) {
				$smsProcessor = new SMSProcessor();
				
				if($smsProcessor->GetCredits()) {
					if($smsProcessor->Response['Response'][0] > 0) {
						$smsProcessor->DestinationNumber = $this->Person->Mobile;
						$smsProcessor->SourceNumber = $GLOBALS['SMS_DESTINATION_NUMBER'];
						$smsProcessor->Message = $message . ' Reply STOP to stop receiving texts from us.';
						$smsProcessor->SendSMS();
					}
				}
			}
		}
	}
}