<?php
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

class SearchSubstitute {
	public $id;
	public $term;
	public $replacement;

	public function __construct($id = null) {
		if(!is_null($id)) {
			$this->get($id);
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		$data = new DataQuery(sprintf("SELECT * FROM search_substitute WHERE id=%d", mysql_real_escape_string($this->id)));
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
		$data = new DataQuery(sprintf("INSERT INTO search_substitute (term, replacement) VALUES ('%s', '%s')", mysql_real_escape_string($this->term), mysql_real_escape_string($this->replacement)));

		$this->id = $data->InsertID;
	}

	public function update() {
		new DataQuery(sprintf("UPDATE search_substitute SET term='%s', replacement='%s' WHERE id=%d", mysql_real_escape_string($this->term), mysql_real_escape_string($this->replacement), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		new DataQuery(sprintf("DELETE FROM search_substitute WHERE id=%d", mysql_real_escape_string($this->id)));
	}
}