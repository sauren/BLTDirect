<?php 

require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class CustomerFoundVia {
	
	public $id;
	public $foundVia;
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

		$data = new DataQuery(sprintf("SELECT * FROM customer_found_via WHERE Found_Via_ID = %d", mysql_real_escape_string($this->id)));
			if($data->TotalRows > 0){
			
			$this->foundVia = $data->Row['Found_Via'];
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
		
		$data = new DataQuery(sprintf("INSERT INTO customer_found_via (Found_Via, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', NOW(), %d)", mysql_real_escape_string($this->foundVia), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
	}

	public function update(){
		new DataQuery(sprintf("UPDATE customer_found_via SET Found_Via = '%s', Modified_By = %d, Modified_On = NOW()", mysql_real_escape_string($this->foundVia), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
	}

	public function delete($id = null){
		if(!is_null($id)){
			$this->id = $id;
		}
		new DataQuery(sprintf("DELETE FROM Customer_Found_Via WHERE Found_Via_ID = %d", mysql_real_escape_string($this->id)));
	}
}

?>