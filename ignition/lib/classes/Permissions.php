<?php 

require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class Permissions {
	
	public $id;
	public $permission;

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
		$data = new DataQuery(sprintf("SELECT * FROM permissions WHERE Permission_ID =%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			$this->permission = $data->Row['Permission'];

			$data->Disconnect();
			return true;
		}
		$data->Disconnect();
		return false;
	}

	public function add(){
		$data = new DataQuery(sprintf("INSERT INTO permissions(Permission) VALUES ('%s')", mysql_real_escape_string($this->permission)));
		
		$this->id = $data->InsertID;
	}

	public function update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE permissions SET Permission = '%s' WHERE Permission_ID = %d", mysql_real_escape_string($this->permission), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null){
		if(!is_null($id)){
			$this->id = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM permissions WHERE Permission_ID = %d", mysql_real_escape_string($this->id)));
	}
}