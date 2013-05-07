<?php 

require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class AccessLevel {
	
	public $id;
	public $accessLevel;
	public $createdOn;
	public $createdBy;
	public $modifiedBy;
	public $modifiedOn;

	public function __construct($id = null){
		if(!is_null($id)){
			$this->id = $id;
			$this->get();
		}
		if(!is_numeric($this->ID)){
			return false;
		}
	}

	public function get($id = null){
		if(!is_null($id)){ $this->id = $id; }
		if(!is_numeric($this->id)) return false;
		
		$data = new DataQuery(sprintf("SELECT * FROM access_levels WHERE Access_ID =%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			$this->accessLevel = $data->Row['Access_Level'];
			$this->createdOn = $data->Row['Created_On'];
			$this->createdBy = $data->Row['Created_By'];
			$this->modifiedOn = $data->Row['Modified_On'];
			$this->modifiedBy = $data->Row['Modified_By'];

			$data->Disconnect();
			return true;
		}
		$data->Disconnect();
		return false;
	}

	public function add(){
		$data = new DataQuery(sprintf("INSERT INTO access_levels(Access_Level, Created_On, Created_By, Modified_By, Modified_On) VALUES ('%s', NOW(), %d, %d, NOW())", 
					mysql_real_escape_string($this->accessLevel), 
					mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), 
					mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		
		$this->id = $data->InsertID;
	}

	public function update(){
		if(!is_numeric($this->id)) return false;
		new DataQuery(sprintf("UPDATE access_levels SET Access_Level = '%s', Modified_By = %d, Modified_On = NOW() WHERE Access_ID = %d", 
				mysql_real_escape_string($this->accessLevel)), 
				mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), 
				mysql_real_escape_string($this->id));
	}

	public function delete($id = null){
		if(!is_null($id)){ $this->id = $id; }
		if(!is_numeric($this->id)) return false;
		new DataQuery(sprintf("DELETE FROM access_levels WHERE Access_ID = %d", mysql_real_escape_string($this->id)));
	}
}