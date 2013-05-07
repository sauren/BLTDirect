<?php
class ProFormaShipping {
	public $ID;
	public $ProFormaID;
	public $Weight;
	public $Quantity;
	public $Charge;

	public function __construct($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	public function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM proforma_shipping WHERE ProFormaShippingID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->ProFormaID = $data->Row['ProFormaID'];
			$this->Weight = $data->Row['Weight'];
			$this->Quantity = $data->Row['Quantity'];
			$this->Charge = $data->Row['Charge'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function Add() {
		$data = new DataQuery(sprintf("INSERT INTO proforma_shipping (ProFormaID, Weight, Quantity, Charge) VALUES (%d, %f, %d, %f)", mysql_real_escape_string($this->ProFormaID), mysql_real_escape_string($this->Weight), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->Charge)));

		$this->ID = $data->InsertID;
	}

	public function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE proforma_shipping SET Weight=%f, Quantity=%d, Charge=%f WHERE ProFormaShippingID=%d", mysql_real_escape_string($this->Weight), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->Charge), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM proforma_shipping WHERE ProFormaShippingID=%d", mysql_real_escape_string($this->ID)));
	}
}
?>