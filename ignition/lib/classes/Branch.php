<?php 
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Address.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrganisationType.php');
	
	class Branch{
		var $ID;
		var $Name;
		var $HQ;
		var $Address;
		var $Tax;
		var $Org;
		var $PublicContact;
		var $Support;
		var $Sales;
		var $Phone1;
		var $Phone1Ext;
		var $Phone2;
		var $Phone2Ext;
		var $Email;
		var $Fax;
		var $Company;
		
		function Branch($id = NULL){
			$this->Address = new Address;
			$this->Org = new OrganisationType;
			if(!is_null($id)){
				$this->ID = $id;
				$this->Get();
			}
		}
		
		function Get($id = null){
			if(!is_null($id)) $this->ID = $id;
			/*if($this->ID == 0){
				$data = new DataQuery("SELECT * FROM branch WHERE Is_Hq = 'Y'");
				$this->ID = $data->Row['Branch_ID'];
				$data->Disconnect();
			}*/

			if(!is_numeric($this->ID)){
				return false;
			}


			$data = new DataQuery(sprintf("SELECT * FROM branch WHERE Branch_ID = %d",mysql_real_escape_string($this->ID)));
			$this->Name = $data->Row['Branch_Name'];
			$this->HQ = $data->Row['Is_HQ'];
			$this->Address->ID = $data->Row['Address_ID'];
			$this->Tax = $data->Row['Tax_Number'];
			$this->Org->ID = $data->Row['Org_Type_ID'];
			$this->Phone1 = $data->Row['Phone_1'];
			$this->Phone1Ext = $data->Row['Phone_1_Ext'];
			$this->Phone2 = $data->Row['Phone_2'];
			$this->Phone2Ext = $data->Row['Phone_2_Ext'];
			$this->Fax = $data->Row['Fax'];
			$this->Email = $data->Row['Email'];
			$this->PublicContact = $data->Row['Can_Public_Contact'];
			$this->Sales = $data->Row['Is_Sales'];
			$this->Support = $data->Row['Is_Support'];
			$this->Company = $data->Row['Company_Number'];
			$data->Disconnect();
			$this->Address->Get();
			$this->Org->Get();
			return true;
		}
		
		function Add(){
			if(!empty($this->Address->Line1)) $this->Address->Add();
			if($this->HQ == 'Y') $this->ReplaceHQ();
			$sql = sprintf("INSERT INTO branch (Branch_Name,
												Is_HQ,
												Address_ID,
												Phone_1,
												Phone_1_Ext,
												Phone_2,
												Phone_2_Ext,
												Fax,
												Email,
												Created_On,
												Created_By,
												Modified_On,
												Modified_By,
												Tax_Number,
												Company_Number,
												Org_Type_ID,
												Can_Public_Contact,
												Is_Support,
												Is_Sales)
							VALUES ('%s','%s',%d,'%s','%s','%s','%s','%s','%s',Now(),%d,Now(),%d,'%s','%s',%d,'%s','%s','%s')",
												mysql_real_escape_string($this->Name),
												mysql_real_escape_string($this->HQ),
												mysql_real_escape_string($this->Address->ID),
												mysql_real_escape_string($this->Phone1),
												mysql_real_escape_string($this->Phone1Ext),
												mysql_real_escape_string($this->Phone2),
												mysql_real_escape_string($this->Phone2Ext),
												mysql_real_escape_string($this->Fax),
												mysql_real_escape_string($this->Email),
												mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
												mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
												mysql_real_escape_string($this->Tax),
												mysql_real_escape_string($this->Company),
												mysql_real_escape_string($this->Org->ID),
												mysql_real_escape_string($this->PublicContact),
												mysql_real_escape_string($this->Support),
												mysql_real_escape_string($this->Sales)
												);
				$data = new DataQuery($sql);
				$this->ID = $data->InsertID;
				$data->Disconnect();
				return true;
				}
				
		function Update(){
			if($this->HQ == 'Y') $this->ReplaceHQ();
			if(!empty($this->Address->Line1)){
				if($this->Address->ID == 0 || empty($this->Address->ID)){
					$this->Address->Add();
				} else {
					$this->Address->Update();
				}
			}
			if(!is_numeric($this->ID)){
				return false;
			}

			$sql = sprintf("UPDATE branch SET	Branch_Name='%s',
												Is_HQ= '%s',
												Address_ID = %d,
												Phone_1 = '%s',
												Phone_1_Ext = '%s',
												Phone_2= '%s',
												Phone_2_Ext= '%s',
												Fax= '%s',
												Email= '%s',
												Modified_On=Now(),
												Modified_By=%d,
												Tax_Number='%s',
												Company_Number='%s',
												Org_Type_ID=%d,
												Can_Public_Contact='%s',
												Is_Support='%s',
												Is_Sales='%s'
												WHERE Branch_ID = %d",
												mysql_real_escape_string($this->Name),
												mysql_real_escape_string($this->HQ),
												mysql_real_escape_string($this->Address->ID),
												mysql_real_escape_string($this->Phone1),
												mysql_real_escape_string($this->Phone1Ext),
												mysql_real_escape_string($this->Phone2),
												mysql_real_escape_string($this->Phone2Ext),
												mysql_real_escape_string($this->Fax),
												mysql_real_escape_string($this->Email),
												mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
												mysql_real_escape_string($this->Tax),
												mysql_real_escape_string($this->Company),
												mysql_real_escape_string($this->Org->ID),
												mysql_real_escape_string($this->PublicContact),
												mysql_real_escape_string($this->Support),
												mysql_real_escape_string($this->Sales),
												mysql_real_escape_string($this->ID));
			$data = new DataQuery($sql);
			$data->Disconnect();
			return true;
		}
		
		function  Delete($id=NULL){
			require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');
			if(!is_null($id) && is_numeric($id)) $this->ID = $id;
			$data = new DataQuery(sprintf("DELETE FROM branch WHERE Branch_ID = %d",mysql_real_escape_string($this->ID)));
			$data = new DataQuery(sprintf("SELECT * FROM warehouse WHERE Type_Reference_ID = %d AND `Type`='B'",mysql_real_escape_string($this->ID)));
			if($data->TotalRows>0){
				$warehouse = new Warehouse($data->Row['Warehouse_ID']);
				$warehouse->Delete();
			}
			return true;
		}
		
		function ReplaceHQ(){
			$data = new DataQuery("UPDATE branch SET Is_HQ='N'");
			$data->Disconnect();
			unset($data);
		}
}
?>