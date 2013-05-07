<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderWarehouseNoteType.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');

class OrderWarehouseNote {
	var $ID;
	var $Type;
	var $Order;
	var $Warehouse;
	var $Note;
	var $IsAlert;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function OrderWarehouseNote($id=NULL){
		$this->Type = new OrderWarehouseNoteType();
		$this->Order = new Order();
		$this->Warehouse = new Warehouse();
		$this->IsAlert = 'N';

		if(!is_null($id)){
			$this->ID=$id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) {
			$this->ID=$id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}


		$data = new DataQuery(sprintf("SELECT * FROM order_warehouse_note WHERE Order_Warehouse_Note_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Type->Get($data->Row['Order_Warehouse_Note_Type_ID']);
			$this->Order->ID = $data->Row['Order_ID'];
			$this->Warehouse->Get($data->Row['Warehouse_ID']);
			$this->Note = $data->Row['Note'];
			$this->IsAlert = $data->Row['Is_Alert'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO order_warehouse_note (Order_Warehouse_Note_Type_ID, Order_ID, Warehouse_ID, Note, Is_Alert, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, %d, %d, '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Type->ID), mysql_real_escape_string($this->Order->ID), mysql_real_escape_string($this->Warehouse->ID), mysql_real_escape_string($this->Note), mysql_real_escape_string($this->IsAlert), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		
		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE order_warehouse_note SET Order_Warehouse_Note_Type_ID=%d, Order_ID=%d, Warehouse_ID=%d, Note='%s', Is_Alert='%s', Modified_On=NOW(), Modified_By=%d WHERE Order_Warehouse_Note_ID=%d", mysql_real_escape_string($this->Type->ID), mysql_real_escape_string($this->Order->ID), mysql_real_escape_string($this->Warehouse->ID), mysql_real_escape_string($this->Note), mysql_real_escape_string($this->IsAlert), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID=$id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM order_warehouse_note WHERE Order_Warehouse_Note_ID=%d", mysql_real_escape_string($this->ID)));
	}
}