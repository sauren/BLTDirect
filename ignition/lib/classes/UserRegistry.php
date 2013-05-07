<?php

	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class UserRegistry {
	
	public $id;
	public $userId;
	public $registryId;

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
		$data = new DataQuery(sprintf("SELECT * FROM user_registry WHERE id =%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			$this->userId = $data->Row['userId'];
			$this->registryId = $data->Row['registryId'];

			$data->Disconnect();
			return true;
		}
		$data->Disconnect();
		return false;
	}

	public function add(){
		$data = new DataQuery(sprintf("INSERT INTO user_registry(userId, registryId) VALUES (%d, %d)", mysql_real_escape_string($this->userId), mysql_real_escape_string($this->registryId)));
		
		$this->id = $data->InsertID;
	}

	public function update(){
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE user_registry SET userId = %d, registryId = %d WHERE id = %d", mysql_real_escape_string($this->userId), mysql_real_escape_string($this->registryId), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null){
		if(!is_null($id)){
			$this->id = $id;
		}
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM user_registry WHERE id = %d", mysql_real_escape_string($this->id)));
	}
}

?>