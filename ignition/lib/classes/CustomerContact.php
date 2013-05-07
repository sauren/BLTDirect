<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Address.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProductGroup.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class CustomerContact {
	var $ID;
	var $Address;
	var $OrgName;
	var $Name;
	var $Title;
	var $Initial;
	var $LastName;
	var $Customer;

	function __construct($id=NULL) {
		$this->Address = new Address();

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id))$this->ID=$id;
		if(!is_numeric($this->ID)) return false;
		
		$sql = sprintf("select
						cc.*,
						a.Address_Line_1, a.Address_Line_2, a.Address_Line_3, a.City, a.Region_ID, a.Country_ID, a.Zip,
						c.Country, c.Address_Format_ID, c.ISO_Code_2, c.Allow_Sales,
						af.Address_Format, af.Address_Summary,
						r.Region_Name, r.Region_Code
						from customer_contact as cc
						left join address as a on cc.Address_ID=a.Address_ID
						left join countries as c on a.Country_ID=c.Country_ID
						left join address_format as af on c.Address_Format_ID=af.Address_Format_ID
						left join regions as r on a.Region_ID=r.Region_ID
						where cc.Customer_Contact_ID=%d", mysql_real_escape_string($this->ID));

		$data = new DataQuery($sql);

		$this->OrgName = $data->Row['Org_Name'];
		$this->Title = $data->Row['Name_Title'];
		$this->Name = $data->Row['Name_First'];
		$this->Initial = $data->Row['Name_Initial'];
		$this->LastName = $data->Row['Name_Last'];
		$this->Customer = $data->Row['Customer_ID'];
		$this->Address->ID = $data->Row['Address_ID'];
		$this->Address->Line1 = $data->Row['Address_Line_1'];
		$this->Address->Line2 = $data->Row['Address_Line_2'];
		$this->Address->Line3 = $data->Row['Address_Line_3'];
		$this->Address->City = $data->Row['City'];
		$this->Address->Region->ID = $data->Row["Region_ID"];
		$this->Address->Region->Name = $data->Row["Region_Name"];
		$this->Address->Region->CountryID = $data->Row["Country_ID"];
		$this->Address->Region->Code = $data->Row["Region_Code"];
		$this->Address->Country->ID = $data->Row['Country_ID'];
		$this->Address->Country->Name = $data->Row['Country'];
		$this->Address->Country->AddressFormat->ID = $data->Row['Address_Format_ID'];
		$this->Address->Country->AddressFormat->Long = $data->Row['Address_Format'];
		$this->Address->Country->AddressFormat->Short = $data->Row['Address_Summary'];
		$this->Address->Country->ISOCode2 = $data->Row['ISO_Code_2'];
		$this->Address->Country->AllowSales = $data->Row['Allow_Sales'];
		$this->Address->Zip = $data->Row['Zip'];
		$this->CreatedOn = $data->Row['Created_On'];
		$this->CreatedBy = $data->Row['Created_By'];
		$this->ModifiedOn = $data->Row['Modified_On'];
		$this->ModifiedBy = $data->Row['Modified_By'];

		$data->Disconnect();
	}

	function Add(){
		$this->Address->Add();
		$data = new DataQuery(sprintf("insert into customer_contact (Customer_ID, Org_Name, Name_Title, Name_First, Name_Initial, Name_Last, Address_ID) values (
								%d, '%s', '%s', '%s', '%s', '%s', %d)",
								mysql_real_escape_string($this->Customer),
								mysql_real_escape_string($this->OrgName),
								mysql_real_escape_string($this->Title),
								mysql_real_escape_string($this->Name),
								mysql_real_escape_string($this->Initial),
								mysql_real_escape_string($this->LastName),
								mysql_real_escape_string($this->Address->ID)));
		$this->ID = $data->InsertID;
		
		$this->insertProductGroup();
	}

	function Delete($id=NULL){
		if(!is_null($id))$this->ID=$id;
		if(!is_numeric($this->ID)) return false;
		if(empty($this->Address->ID)) $this->Get();
		$this->Address->Delete();
		$data = new DataQuery(sprintf("delete from customer_contact where Customer_Contact_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function Update(){
		$this->Address->Update();
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("update customer_contact set Customer_ID=%d, Org_Name='%s', Name_Title='%s',
								Name_First='%s', Name_Initial='%s', Name_Last='%s', Address_ID=%d
								where Customer_Contact_ID=%d",
								mysql_real_escape_string($this->Customer),
								mysql_real_escape_string($this->OrgName),
								mysql_real_escape_string($this->Title),
								mysql_real_escape_string($this->Name),
								mysql_real_escape_string($this->Initial),
								mysql_real_escape_string($this->LastName),
								mysql_real_escape_string($this->Address->ID),
								mysql_real_escape_string($this->ID)));
								
		$this->insertProductGroup();
	}

	function GetFullName(){
		$tempStr = sprintf("%s %s %s %s%s",
							$this->Title,
							$this->Name,
							$this->Initial,
							$this->LastName,
							!empty($this->OrgName) ? '<br />' . $this->OrgName : '');
		return trim($tempStr);
	}
	
	private function insertProductGroup() {
		$groups = array();
		$key = array();
					
		if(!empty($this->Address->Line1)) {
			$key[] = $this->Address->Line1;
		}
		if(!empty($this->Address->Line2)) {
			$key[] = $this->Address->Line2;
		}
		if(!empty($this->Address->City)) {
			$key[] = $this->Address->City;
		}		
		$key = implode(', ', $key);
		
		$data = new DataQuery(sprintf("SELECT name
FROM customer_product_group
WHERE customerId = %d AND name LIKE '%%%s%%'", mysql_real_escape_string($this->Customer), mysql_real_escape_string($key)));
		if($data->TotalRows <= 0){
			$productGroup = new CustomerProductGroup();
			$productGroup->customer->ID = $this->Customer;
			$productGroup->name = $key;
			$productGroup->add();
		}
		$data->Disconnect();	
	}

	static function DeleteContact($id){
		new DataQuery(sprintf("DELETE FROM customer_contact WHERE Customer_ID=%d", mysql_real_escape_string($id)));
	}

	public function validateCustomerContact($id,$location){
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();

			$inValid = false;
			$errorNote = array();

			$patternName = "/^[a-zA-Z\p{L}+\.\'\s\&\-\\\\\/\-]+$/u";
			$patternAddress = "/^[a-zA-Z0-9\p{L}+\s&,+\"()\:\\\\\/\-\'\.]+$/u";
		

			if(preg_match($patternName ,$this->Name)){
			}else{
				$inValid = true;
			}
			if(preg_match($patternName ,$this->LastName)){
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

			//debug($id,1);

			//Front End
			if($inValid && $location=='F'){
				redirect(sprintf("Location: checkout.php?action=edit&contact=%s&type=contact&status=update",$id));
				exit;
			}

			//Telesales
			if($inValid && $location=='T'){
				redirect(sprintf("Location: order_shipping.php?action=edit&contact=%s&type=contact&status=update&type=s",$id));
				exit;
			}

			//Ignition
			if($inValid && $location=='I'){
				redirect(sprintf("Location: order_shipping.php?action=edit&contact=%s&type=contact&status=update&type=s",$id));
				exit;
			}

			return true;
		}
	}
}