<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class CustomerContactCollection {
	var $Customer;
	var $Line;
	
	function CustomerContactCollection($customer=NULL){
		$this->Customer = $customer->ID;
		$this->Line = array();
		if(!is_null($customer)) $this->Get();
	}
	
	function Get($customer=NULL){
		if(!is_null($customer)) $this->Customer = $customer->ID;
		
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
						where cc.Customer_ID=%d", mysql_real_escape_string($this->Customer));
						
		$data = new DataQuery($sql);
		while($data->Row){
			$line = new CustomerContact;
			$line->ID = $data->Row['Customer_Contact_ID'];
			$line->OrgName = $data->Row['Org_Name'];
			$line->Title = $data->Row['Name_Title'];
			$line->Name = $data->Row['Name_First'];
			$line->Initial = $data->Row['Name_Initial'];
			$line->LastName = $data->Row['Name_Last'];
			$line->Customer = $data->Row['Customer_ID'];
			$line->Address->ID = $data->Row['Address_ID'];
			$line->Address->Line1 = $data->Row['Address_Line_1'];
			$line->Address->Line2 = $data->Row['Address_Line_2'];
			$line->Address->Line3 = $data->Row['Address_Line_3'];
			$line->Address->City = $data->Row['City'];
			$line->Address->Region->ID = $data->Row["Region_ID"];
			$line->Address->Region->Name = $data->Row["Region_Name"];
			$line->Address->Region->CountryID = $data->Row["Country_ID"];
			$line->Address->Region->Code = $data->Row["Region_Code"];
			$line->Address->Country->ID = $data->Row['Country_ID'];
			$line->Address->Country->Name = $data->Row['Country'];
			$line->Address->Country->AddressFormat->ID = $data->Row['Address_Format_ID'];
			$line->Address->Country->AddressFormat->Long = $data->Row['Address_Format'];
			$line->Address->Country->AddressFormat->Short = $data->Row['Address_Summary'];
			$line->Address->Country->ISOCode2 = $data->Row['ISO_Code_2'];
			$line->Address->Country->AllowSales = $data->Row['Allow_Sales'];
			$line->Address->Zip = $data->Row['Zip'];
			$line->CreatedOn = $data->Row['Created_On'];
			$line->CreatedBy = $data->Row['Created_By'];
			$line->ModifiedOn = $data->Row['Modified_On'];
			$line->ModifiedBy = $data->Row['Modified_By'];
			
			$this->Line[] = $line;

			$data->Next();
		}
		$data->Disconnect();
	}
}