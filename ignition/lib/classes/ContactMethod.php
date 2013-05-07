<?php 

require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class ContactMethod {
	
	public $id;
	public $methodName;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;


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

		$data = new DataQuery(sprintf("SELECT * FROM contact_method WHERE Contact_Method_ID =%d", mysql_real_escape_string($this->id)));
			if($data->TotalRows > 0){
				$this->methodName = $data->Row['Method_Name'];
				$this->createdOn = $data->Row['Created_On'];
				$this->createdBy = $data->Row['Created_By'];
				$this->modifiedOn = $data->Row['Modified_On'];
				$this->modifiedBy = $data->Row['Modified_By'];

				$data->Disconnect();
				return true;
			}
			$data->Disconnect();
			return false;
		}

	public function add(){
		
		$data = new DataQuery(sprintf("INSERT INTO contact_method(Method_Name, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->methodName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
	}

	public function update(){

		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("UPDATE contact_method SET Method_Name ='%s', Modified_By =%d, Modified_On =NOW() WHERE Contact_Method_ID = %d", mysql_real_escape_string($this->methodName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null){
		if(!is_null($id)){
			$this->id = $id;
		}
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM contact_method WHERE Contact_Method_ID = %d", mysql_real_escape_string($this->id)));
	}
}

?>