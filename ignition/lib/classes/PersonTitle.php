<?php

	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	class PersonTitle {
	
	public $id;
	public $personTitle;
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
		$data = new DataQuery(sprintf("SELECT * FROM person_title WHERE Person_Title_ID =%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			$this->personTitle = $data->Row['Person_Title_ID'];
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
		$data = new DataQuery(sprintf("INSERT INTO person_title(Person_Title, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->personTitle), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		
		$this->id = $data->InsertID;
	}

	public function update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE person_title SET Person_Title = '%s', Modified_By = %d, Modified_On = NOW() WHERE Person_Title_ID = %d", mysql_real_escape_string($this->personTitle), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null){
		if(!is_null($id)){
			$this->id = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM person_title WHERE Person_Title_ID = %d", mysql_real_escape_string($this->id)));
	}
}


?>