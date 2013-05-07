<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class ReportExtract {
	public $id;
	public $name;
	public $query;

	public function __construct($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
			$this->get();
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM report_extract WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			$this->name = $data->Row['name'];
			$this->query = $data->Row['query'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO report_extract (name, query) VALUES ('%s', '%s')", mysql_real_escape_string($this->name), mysql_real_escape_string($this->query)));

		$this->id = $data->InsertID;
	}

	public function update() {
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE report_extract SET name='%s', query='%s' WHERE id=%d", mysql_real_escape_string($this->name), mysql_real_escape_string($this->query), mysql_real_escape_string($this->id)));
	}

	public function delete($id=NULL) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM report_extract WHERE id=%d", mysql_real_escape_string($this->id)));
	}
}