<?php

require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class UserAccess {
	
	public $id;
	public $accessId;
	public $userId;

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
		if(!is_numeric($this->id)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT * FROM user_access WHERE userAccessId =%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			$this->accessId = $data->Row['accessId'];
			$this->userId = $data->Row['userId'];

			$data->Disconnect();
			return true;
		}
		$data->Disconnect();
		return false;
	}

	public function add(){
		$data = new DataQuery(sprintf("INSERT INTO user_access(accessId, userId) VALUES (%d, %d)", mysql_real_escape_string($this->accessId), mysql_real_escape_string($this->userId)));

		$this->id = $data->InsertID;
	}

	public function update(){
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE user_access SET accessId = %d, userId = %d WHERE userAccessId = %d", mysql_real_escape_string($this->accessId), mysql_real_escape_string($this->userId), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null){
		if(!is_null($id)){
			$this->id = $id;
		}
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM user_access WHERE userAccessId = %d", mysql_real_escape_string($this->id)));
	}

	static function DeleteUser($access, $id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("delete from user_access where accessId = %d and userId = %d", mysql_real_escape_string($access), mysql_real_escape_string($id)));
	}
}

?>