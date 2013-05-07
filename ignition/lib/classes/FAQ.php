<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class FAQ {
	public $ID;
	public $Question;
	public $Answer;
	public $Created;
	public $CreatedBy;
	public $Modified;
	public $ModifiedBy;
	
	public function __construct($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}
	
	public function Get($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT * FROM faq where FAQ_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Question = $data->Row['Question'];
			$this->Answer = $data->Row['Answer'];
			$this->Created = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->Modified = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];	
			
			$data->Disconnect();
			return true;
		}
		
		$data->Disconnect();
		return false;
	}
	
	public function Add(){
		$data = new DataQuery(sprintf("INSERT INTO faq (Question, Answer, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', Now(), %d, Now(), %d)", mysql_real_escape_string($this->Question), mysql_real_escape_string($this->Answer), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}
	
	public function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE faq set Question='%s', Answer='%s', Modified_On=Now(), Modified_By=%d where FAQ_ID=%d", mysql_real_escape_string($this->Question), mysql_real_escape_string($this->Answer), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}
	
	public function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}
		
		new DataQuery(sprintf("DELETE FROM faq where FAQ_ID=%d", mysql_real_escape_string($this->ID)));
	}
}