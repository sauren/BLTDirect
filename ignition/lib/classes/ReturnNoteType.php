<?php

require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class ReturnNoteType {
	
	public $id;
	public $isPublic;
	public $typeName;
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
		$data = new DataQuery(sprintf("SELECT * FROM return_note_type WHERE Return_Note_Type_ID =%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			$this->isPublic = $data->Row['Is_Public'];
			$this->typeName = $data->Row['Type_Name'];
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
		$data = new DataQuery(sprintf("INSERT INTO return_note_type(Type_Name, Is_Public, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->typeName), mysql_real_escape_string($this->isPublic), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		
		$this->id = $data->InsertID;
	}

	public function update(){

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE return_note_type SET Type_Name = '%s', Is_Public = '%s', Modified_By = %d, Modified_On = NOW() WHERE Return_Note_Type_ID = %d", mysql_real_escape_string($this->typeName), mysql_real_escape_string($this->isPublic), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null){
		if(!is_null($id)){
			$this->id = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM return_note_type WHERE Return_Note_Type_ID = %d", mysql_real_escape_string($this->id)));
	}
}

?>