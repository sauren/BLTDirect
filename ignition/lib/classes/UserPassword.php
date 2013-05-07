<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');

class UserPassword {
	public $id;
	public $userId;
	public $password;
	public $createdOn;
	
	public function __construct($id = null) {
		if(!is_null($id)) {
			$this->get($id);
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}
		if(!is_numeric($this->id)) return false;

		$data = new DataQuery(sprintf("SELECT * FROM users_password WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
		
            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO users_password (userId, password, createdOn) VALUES (%d, '%s', NOW())", mysql_real_escape_string($this->userId), mysql_real_escape_string($this->password)));

		$this->id = $data->InsertID;
	}
	
	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}
		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM users_password WHERE id=%d", mysql_real_escape_string($this->id)));
	}

	public function isUsed($encryptedPassword) {
		$data = new DataQuery(sprintf("SELECT password FROM users_password WHERE userId=%d ORDER BY id DESC LIMIT 0, %d", mysql_real_escape_string($this->userId), Setting::GetValue('user_password_prevent_reuse')));
		while($data->Row) {
			if($data->Row['password'] == trim($encryptedPassword)) {
				return true;
			}

			$data->Next();
		}
		$data->Disconnect();

		return false;	
	}
}

