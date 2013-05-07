<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/LedType.php');

class LedLocation {
	public $id;
	public $type;
	public $name;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;
	
	public function __construct($id = null) {
		$this->type = new LedType();
		
		if(!is_null($id)) {
			$this->get($id);
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM led_location WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
			$this->type->id = $this->typeId;
			
            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
	
	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO led_location (typeId, name, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->type->id), mysql_real_escape_string($this->name), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
	}
	
	public function update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE led_location SET typeId=%d, name='%s', modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->type->id), mysql_real_escape_string($this->name), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM led_location WHERE id=%d", mysql_real_escape_string($this->id)));
	}
}