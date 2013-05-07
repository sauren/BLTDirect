<?php
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class Cache {
	public $id;
	public $property;
	public $data;
	public $createdOn;

	public function __construct($id = NULL) {
		if (!is_null($id)) {
			$this->id = $id;
			$this->get();
		}
	}

	public function setProperty($property) {
		$this->property = strtolower($property);
	}
	
	public function setData($data) {
		$this->data = base64_encode(serialize($data));
	}
	
	public function get($id = NULL) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM cache WHERE CacheID=%d ", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			$this->property = $data->Row["Property"];
			$this->data = unserialize(base64_decode($data->Row["Data"]));
			$this->createdOn = $data->Row["CreatedOn"];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function getByProperty($property=NULL) {
		if(!is_null($property)) {
			$data = new DataQuery(sprintf("SELECT CacheID FROM cache WHERE Property LIKE '%s' ORDER BY CreatedOn DESC LIMIT 0, 1", strtolower(mysql_real_escape_string($property))));
			if($data->TotalRows > 0) {
				$return = $this->get($data->Row['CacheID']);

				$data->Disconnect();
				return $return;
			}
			$data->Disconnect();
		}

		return false;
	}
	
	public static function getData($property=NULL) {
		$returnValue = null;

		if(!is_null($property)) {
			$data = new DataQuery(sprintf("SELECT Data FROM cache WHERE Property LIKE '%s' ORDER BY CreatedOn DESC LIMIT 0, 1", strtolower(mysql_real_escape_string($property))));
			if($data->TotalRows > 0) {
				$returnValue = unserialize(base64_decode($data->Row["Data"]));;
			}
			$data->Disconnect();
		}

		return $returnValue;
	}

	public function add() {

		if(!is_numeric($this->id)){
			return false;
		}

		$data = new DataQuery(sprintf("INSERT INTO cache (Property, Data, CreatedOn) VALUES ('%s', '%s', NOW())", mysql_real_escape_string($this->property), mysql_real_escape_string($this->data)));
		
		$this->id = $data->InsertID;
	}

	public function update() {
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE cache SET Property='%s', Data='%s' WHERE CacheID=%d", mysql_real_escape_string($this->property), mysql_real_escape_string($this->data),  mysql_real_escape_string($this->id)));
	}

	public function delete($id = NULL) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM cache WHERE CacheID=%d",  mysql_real_escape_string($this->id)));
	}
}