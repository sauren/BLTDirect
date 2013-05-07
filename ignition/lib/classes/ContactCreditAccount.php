<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class ContactCreditAccount {
	public $id;
	public $contact;
	public $limit;
	public $startedOn;
	public $endedOn;
	
	public function __construct($id = null) {
		$this->contact = new Contact();
		$this->startedOn = '0000-00-00 00:00:00';
		$this->endedOn = '0000-00-00 00:00:00';

		if(!is_null($id)) {
			$this->get($id);
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		$data = new DataQuery(sprintf("SELECT * FROM contact_credit_account WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
			$this->contact->ID = $this->contactId;

            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO contact_credit_account (contactId, `limit`, startedOn, endedOn) VALUES (%d, %f, '%s', '%s')", mysql_real_escape_string($this->contact->ID), mysql_real_escape_string($this->limit), mysql_real_escape_string($this->startedOn), mysql_real_escape_string($this->endedOn)));

		$this->id = $data->InsertID;
	}
	
	public function update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE contact_credit_account SET contactId=%d, `limit`=%f, startedOn='%s', endedOn='%s' WHERE id=%d", mysql_real_escape_string($this->contact->ID), mysql_real_escape_string($this->limit), mysql_real_escape_string($this->startedOn), mysql_real_escape_string($this->endedOn), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM contact_credit_account WHERE id=%d", mysql_real_escape_string($this->id)));
	}
}