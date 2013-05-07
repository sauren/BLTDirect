<?php 
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class LbukLinked {	
	public $id;
	public $localId;
	public $remoteId;

	public function __construct($id = null) {
		if(!is_null($id)){
			$this->id = $id;
			$this->get();
		}
	}

	public function get($id = null){
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)) {
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT * FROM lbuk_linked WHERE Access_ID =%d", $this->id));
		if($data->TotalRows > 0) {
			$this->localId = $data->Row['localId'];
			$this->remoteId = $data->Row['remoteId'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function add(){
		$data = new DataQuery(sprintf("INSERT INTO lbuk_linked (localId, remoteId) VALUES (%d, %d)", mysql_real_escape_string($this->localId), mysql_real_escape_string($this->remoteId)));
		
		$this->id = $data->InsertID;
	}

	public function update(){
		if(!is_numeric($this->id)) {
			return false;
		}
		
		new DataQuery(sprintf("UPDATE lbuk_linked SET localId=%d, remoteId=%d WHERE id=%d", mysql_real_escape_string($this->localId), mysql_real_escape_string($this->remoteId), $this->id));
	}

	public function delete($id = null){
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)) {
			return false;
		}
		
		new DataQuery(sprintf("DELETE FROM lbuk_linked WHERE id=%d", $this->id));
	}

	public function deleteByRemoteId($remoteId = null) {
		if(!is_null($remoteId)) {
			$this->remoteId = $remoteId;
		}

		if(!is_numeric($this->remoteId)) {
			return false;
		}
		
		new DataQuery(sprintf("DELETE FROM lbuk_linked WHERE remoteId=%d", $this->remoteId));
	}
}