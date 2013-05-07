<?php

require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class OrderNoteType {
		
		public $id;
		public $typeName;
		public $isPublic;
		public $createdBy;
		public $createdOn;
		public $modifiedBy;
		public $modifiedOn;

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
		$data = new DataQuery(sprintf("SELECT * FROM order_note_type WHERE Order_Note_Type_ID = %d", mysql_real_escape_string($this->id)));
			
			if($data->TotalRows >0){
			$this->typeName = 'Type_Name';
			$this->isPublic = 'Is_Public';
			$this->createdBy = 'Created_By';
			$this->createdOn = 'Created_On';
			$this->modifiedBy = 'Modified_By';
			$this->modifiedOn = 'Modified_On';

			$this->Disconnect();
			return true;
		}

		$this->Disconnect();
		return false;
	}

	public function add(){
		
		$data = new DataQuery(sprintf("INSERT INTO order_note_type (Type_Name, Is_Public, Created_by, Created_On, Modified_On, Modified_By) VALUES ('%s', %d, '%s', %d ,NOW())", mysql_real_escape_string($this->typeName), mysql_real_escape_string($this->isPublic), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
	}

	public function update(){
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE order_note_type SET Type_Name = '%s', Is_Public = '%s', Modified_By = %d, Modified_On = NOW() WHERE Order_Note_Type_ID = %d", mysql_real_escape_string($this->typeName), mysql_real_escape_string($this->isPublic), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)){
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}
	new DataQuery(sprintf("DELETE FROM order_note_type WHERE Order_Note_Type_ID = %d", mysql_real_escape_string($this->id)));
	}
}
?>