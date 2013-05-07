<?php 

	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class Registry {
	
	public $id;
	public $scriptName;
	public $scriptDescription;
	public $scriptFile;
	public $createdOn;
	public $createdBy;
	public $modifiedBy;
	public $modifiedOn;

	public function __construct($id = null){
		if(!is_null($id)){
			$this->id = $id;
			$this->get();
		}
	}

	public function get($id = null){
		if(!is_null($id)){
			$this->id = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT * FROM registry WHERE Registry_ID =%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			$this->scriptName = $data->Row['Script_Name'];
			$this->scriptDescription = $data->Row['Script_Description'];
			$this->scriptFile = $data->Row['Script_File'];
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
		$data = new DataQuery(sprintf("INSERT INTO registry(Script_Name, Script_Description, Script_File, Created_By, Created_On, Modified_On, Modified_By) VALUES ('%s', '%s', '%s', %d, NOW(), NOW(), %d)", mysql_real_escape_string($this->scriptName), mysql_real_escape_string($this->scriptDescription), mysql_real_escape_string($this->scriptFile), $GLOBALS['SESSION_USER_ID'], mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		
		$this->id = $data->InsertID;
	}

	public function update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE registry SET Script_Name = '%s', Script_Description = '%s', Script_File = '%s', Modified_By = %d, Modified_On = NOW() WHERE Registry_ID = %d", mysql_real_escape_string($this->scriptName), mysql_real_escape_string($this->scriptDescription), mysql_real_escape_string($this->scriptFile), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null){
		if(!is_null($id)){
			$this->id = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM registry WHERE Registry_ID = %d", mysql_real_escape_string($this->id)));
	}
}

?>