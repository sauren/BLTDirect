<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Address.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/OrganisationType.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/OrganisationIndustry.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Contact.php");

class Organisation{
	var $ID;
	var $Name;
	var $Address;
	var $InvoiceName;
	var $InvoiceAddress;
	var $InvoicePhone;
	var $InvoiceEmail;
	var $UseInvoiceAddress;
	var $Type;
	var $Industry;
	var $Phone1;
	var $Phone1Ext;
	var $Phone2;
	var $Phone2Ext;
	var $Fax;
	var $Email;
	var $Url;
	var $CompanyNo;
	var $TaxNo;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function Organisation($id=NULL){
		$this->Address = new Address();
		$this->InvoiceAddress = new Address();
		$this->UseInvoiceAddress = 'N';
		$this->Type = new OrganisationType();
		$this->Industry = new OrganisationIndustry();

		if(!is_null($id) && is_numeric($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id) && is_numeric($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("select
										o.*,
										a.Address_Line_1, a.Address_Line_2, a.Address_Line_3, a.City, a.Region_ID, a.Region_Name, a.Country_ID, a.Zip,
										c.Country, c.Address_Format_ID, c.ISO_Code_2, c.Allow_Sales,
										af.Address_Format, af.Address_Summary,
										r.Region_Name, r.Region_Code,
                                        a2.Address_Line_1 AS Invoice_Address_Line_1, a2.Address_Line_2 AS Invoice_Address_Line_2, a2.Address_Line_3 AS Invoice_Address_Line_3, a2.City AS Invoice_City, a2.Region_ID AS Invoice_Region_ID, a2.Region_Name AS Invoice_Region_Name, a2.Country_ID AS Invoice_Country_ID, a2.Zip AS Invoice_Zip,
										c2.Country AS Invoice_Country, c2.Address_Format_ID AS Invoice_Address_Format_ID, c2.ISO_Code_2 AS Invoice_ISO_Code_2, c2.Allow_Sales AS Invoice_Allow_Sales,
										af2.Address_Format AS Invoice_Address_Format, af2.Address_Summary AS Invoice_Address_Summary,
										r2.Region_Name AS Invoice_Region_Name, r2.Region_Code AS Invoice_Region_Code,
										oi.Industry_Name,
										ot.Org_Type
										from organisation as o
										left join address as a on o.Address_ID=a.Address_ID
										left join countries as c on a.Country_ID=c.Country_ID
										left join address_format as af on c.Address_Format_ID=af.Address_Format_ID
										left join regions as r on a.Region_ID=r.Region_ID
                                        left join address as a2 on o.Invoice_Address_ID=a2.Address_ID
										left join countries as c2 on a.Country_ID=c2.Country_ID
										left join address_format as af2 on c2.Address_Format_ID=af2.Address_Format_ID
										left join regions as r2 on a.Region_ID=r2.Region_ID
										left join organisation_industry as oi on o.Industry_ID=oi.Industry_ID
										left join organisation_type as ot on o.Org_Type_ID=ot.Org_Type_ID
										where o.Org_ID=%d", mysql_real_escape_string($this->ID)));


		$this->Name = $data->Row['Org_Name'];
		$this->Address->ID = $data->Row['Address_ID'];
		$this->InvoiceName = $data->Row['Invoice_Name'];
		$this->InvoiceAddress->ID = $data->Row['Invoice_Address_ID'];
		$this->UseInvoiceAddress = $data->Row['Use_Invoice_Address'];
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
		$this->Address->Country->AddressFormat->ID = $data->Row['Address_Format_ID'];
		$this->Address->Country->AddressFormat->Long = $data->Row['Address_Format'];
		$this->Address->Country->AddressFormat->Short = $data->Row['Address_Summary'];
		$this->Address->Country->ISOCode2 = $data->Row['ISO_Code_2'];
		$this->Address->Country->AllowSales = $data->Row['Allow_Sales'];
		$this->Address->Zip = $data->Row['Zip'];
        $this->InvoiceAddress->Line1 = $data->Row['Invoice_Address_Line_1'];
		$this->InvoiceAddress->Line2 = $data->Row['Invoice_Address_Line_2'];
		$this->InvoiceAddress->Line3 = $data->Row['Invoice_Address_Line_3'];
		$this->InvoiceAddress->City = $data->Row['Invoice_City'];

		if(empty($data->Row['Invoice_Region_ID'])){
			$this->InvoiceAddress->Region->ID = $data->Row['Invoice_Region_ID'];
			$this->InvoiceAddress->Region->Name = $data->Row['Invoice_Region_Name'];
		} else {
			$this->InvoiceAddress->Region->ID = $data->Row["Region_ID"];
			$this->InvoiceAddress->Region->Name = $data->Row["Region_Name"];
			$this->InvoiceAddress->Region->CountryID = $data->Row["Country_ID"];
			$this->InvoiceAddress->Region->Code = $data->Row["Region_Code"];
		}

		$this->InvoiceAddress->Country->ID = $data->Row['Invoice_Country_ID'];
		$this->InvoiceAddress->Country->Name = $data->Row['Invoice_Country'];
		$this->InvoiceAddress->Country->AddressFormat->ID = $data->Row['Invoice_Address_Format_ID'];
		$this->InvoiceAddress->Country->AddressFormat->Long = $data->Row['Invoice_Address_Format'];
		$this->InvoiceAddress->Country->AddressFormat->Short = $data->Row['Invoice_Address_Summary'];
		$this->InvoiceAddress->Country->ISOCode2 = $data->Row['Invoice_ISO_Code_2'];
		$this->InvoiceAddress->Country->AllowSales = $data->Row['Invoice_Allow_Sales'];
		$this->InvoiceAddress->Zip = $data->Row['Invoice_Zip'];
		$this->InvoicePhone = $data->Row['Invoice_Phone'];
		$this->InvoiceEmail = $data->Row['Invoice_Email'];
		$this->Type->ID = $data->Row['Org_Type_ID'];
		$this->Type->Name = $data->Row['Org_Type'];
		$this->Industry->ID = $data->Row['Industry_ID'];
		$this->Industry->Name = $data->Row['Industry_Name'];
		$this->Phone1 = $data->Row['Phone_1'];
		$this->Phone1Ext = $data->Row['Phone_1_Extension'];
		$this->Phone2 = $data->Row['Phone_2'];
		$this->Phone2Ext = $data->Row['Phone_2_Extension'];
		$this->Fax = $data->Row['Fax'];
		$this->Email = $data->Row['Email'];
		$this->Url = $data->Row['URL'];
		$this->CompanyNo = $data->Row['Company_Number'];
		$this->TaxNo = $data->Row['Tax_Number'];
		$this->CreatedOn = $data->Row['Created_On'];
		$this->CreatedBy = $data->Row['Created_By'];
		$this->ModifiedOn = $data->Row['Modified_On'];
		$this->ModifiedBy = $data->Row['Modified_By'];
		
		$data->Disconnect();
		return true;
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->Get($id);
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$this->Address->Delete();

		new DataQuery(sprintf("delete from organisation where Org_ID=%d", mysql_real_escape_string($this->ID)));

		$data = new DataQuery(sprintf("SELECT Contact_ID FROM contact WHERE Org_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			Contact::DeleteOrganisation($this->ID);
			Contact::ContactParent($data->Row['Contact_ID']);
		}
		$data->Disconnect();

		return true;
	}

	function Add($connection = null){
		$this->Address->Add($connection);
		$this->InvoiceAddress->Add($connection);


		$data = new DataQuery(sprintf("insert into organisation (
										Org_Name,
										Org_Name_Search,
										Org_Type_ID,
										Industry_ID,
										Address_ID,
										Invoice_Name,
										Invoice_Address_ID,
										Invoice_Phone,
										Invoice_Email,
										Use_Invoice_Address,
										Phone_1,
										Phone_1_Extension,
										Phone_2,
										Phone_2_Extension,
										Fax,
										Email,
										URL,
										Company_Number,
										Tax_Number,
										Created_On,
										Created_By,
										Modified_On,
										Modified_By
										) values ('%s', '%s', %d, %d, %d, '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', Now(), %d, Now(), %d)",
										mysql_real_escape_string($this->Name),
										mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Name)),
										mysql_real_escape_string($this->Type->ID),
										mysql_real_escape_string($this->Industry->ID),
										mysql_real_escape_string($this->Address->ID),
										mysql_real_escape_string($this->InvoiceName),
										mysql_real_escape_string($this->InvoiceAddress->ID),
										mysql_real_escape_string($this->InvoicePhone),
										mysql_real_escape_string($this->InvoiceEmail),
										mysql_real_escape_string($this->UseInvoiceAddress),
										mysql_real_escape_string($this->Phone1),
										mysql_real_escape_string($this->Phone1Ext),
										mysql_real_escape_string($this->Phone2),
										mysql_real_escape_string($this->Phone2Ext),
										mysql_real_escape_string($this->Fax),
										mysql_real_escape_string($this->Email),
										mysql_real_escape_string($this->Url),
										mysql_real_escape_string($this->CompanyNo),
										mysql_real_escape_string($this->TaxNo),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])), $connection);
		$this->ID = $data->InsertID;

		return true;
	}

	function Update(){
		if($this->Address->ID == 0 || empty($this->Address->ID)){
			$this->Address->Add();
		} else {
			$this->Address->Update();
		}

        if($this->InvoiceAddress->ID == 0 || empty($this->InvoiceAddress->ID)){
			$this->InvoiceAddress->Add();
		} else {
			$this->InvoiceAddress->Update();
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("UPDATE organisation SET
										Org_Name='%s',
										Org_Name_Search='%s',
										Org_Type_ID=%d,
										Industry_ID=%d,
										Address_ID=%d,
										Invoice_Name='%s',
										Invoice_Address_ID=%d,
										Invoice_Phone='%s',
										Invoice_Email='%s',
										Use_Invoice_Address='%s',
										Phone_1='%s',
										Phone_1_Extension='%s',
										Phone_2='%s',
										Phone_2_Extension='%s',
										Fax='%s',
										Email='%s',
										URL='%s',
										Company_Number='%s',
										Tax_Number='%s',
										Modified_On=Now(),
										Modified_By=%d
										where Org_ID=%d",
										mysql_real_escape_string($this->Name),
										mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Name)),
										mysql_real_escape_string($this->Type->ID),
										mysql_real_escape_string($this->Industry->ID),
										mysql_real_escape_string($this->Address->ID),
										mysql_real_escape_string($this->InvoiceName),
										mysql_real_escape_string($this->InvoiceAddress->ID),
										mysql_real_escape_string($this->InvoicePhone),
										mysql_real_escape_string($this->InvoiceEmail),
										mysql_real_escape_string($this->UseInvoiceAddress),
										mysql_real_escape_string($this->Phone1),
										mysql_real_escape_string($this->Phone1Ext),
										mysql_real_escape_string($this->Phone2),
										mysql_real_escape_string($this->Phone2Ext),
										mysql_real_escape_string($this->Fax),
										mysql_real_escape_string($this->Email),
										mysql_real_escape_string($this->Url),
										mysql_real_escape_string($this->CompanyNo),
										mysql_real_escape_string($this->TaxNo),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($this->ID)));
		return true;
	}

	function GetInvoiceAddress() {
		return ($this->UseInvoiceAddress == 'Y') ? $this->InvoiceAddress : $this->Address;
	}
}