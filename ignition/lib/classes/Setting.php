<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class Setting {
	private static $properties;
	
	public static function GetValue($property = null) {
		if(!is_null($property)) {
			if(is_null(self::$properties)) {
				self::$properties = array();	
				
				$data = new DataQuery("SELECT Property, Value FROM settings");
				while($data->Row) {
					self::$properties[strtolower($data->Row['Property'])] = $data->Row['Value'];
					
					$data->Next();
				}
				$data->Disconnect();
			}
			
			$property = strtolower($property);
			
			if(isset(self::$properties[$property])) {
				return self::$properties[$property];
			}
		}
		
		return null;
	}
	
	public $ID;
	public $Property;
	public $Value;
	public $Description;
	public $Created;
	public $CreatedBy;
	public $Modified;
	public $ModifiedBy;
	public $Type;

	public function __construct($id=NULL){
		$this->Type = 'string';

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	public function Get($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM settings WHERE Setting_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Property = $data->Row['Property'];
			$this->Value = $data->Row['Value'];
			$this->Description = $data->Row['Description'];
			$this->Created = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->Modified = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$this->Type = $data->Row['Type'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
	
	public function GetByProperty($property=NULL) {
		if(!is_null($property)) {
			$this->Property = $property;
		}
		
		$data = new DataQuery(sprintf("SELECT Setting_ID FROM settings WHERE Property LIKE '%s'", strtolower(addslashes($this->Property))));
		if($data->TotalRows > 0) {
			$return = $this->Get($data->Row['Setting_ID']);
			
			$data->Disconnect();
			return $return;
		}
		$data->Disconnect();

		return false;
	}

	public function Add(){
		$data = new DataQuery(sprintf("INSERT INTO settings (Type, Property, Value, Description, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', '%s', '%s', Now(), %d, Now(), %d)", mysql_real_escape_string($this->Type), mysql_real_escape_string($this->Property), mysql_real_escape_string($this->Value), mysql_real_escape_string($this->Description), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		
		$this->ID = $data->InsertID;
	}

	public function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE settings SET Type='%s', Value='%s', Modified_On=Now(), Modified_By=%d WHERE Setting_ID=%d", mysql_real_escape_string($this->Type), mysql_real_escape_string($this->Value), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM settings WHERE Setting_ID=%d", mysql_real_escape_string($this->ID)));
	}
}