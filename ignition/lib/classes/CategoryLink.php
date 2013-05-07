<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class CategoryLink {
	var $ID;
	var $CategoryID;
	var $LinkedID;

	function Category($id=NULL) {
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM product_category_link WHERE Category_Link_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->CategoryID = $data->Row['Category_ID'];
			$this->LinkedID = $data->Row['Linked_Category_ID'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO product_category_link (Category_ID, Linked_Category_ID) VALUES (%d, %d)", mysql_real_escape_string($this->CategoryID), mysql_real_escape_string($this->LinkedID)));

		$this->ID = $data->InsertID;
	}

	function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_category_link WHERE Category_Link_ID=%d", mysql_real_escape_string($this->ID)));
	}
}
?>