<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpec.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValue.php');

class ProductSpecGroup {
	public static function regenerateRanges() {
		$data = new DataQuery(sprintf("SELECT Group_ID, Reference FROM product_specification_group WHERE Reference LIKE '%%range' AND Data_Type='numeric'"));
		while($data->Row) {
			$values = array();
			
			$data2 = new DataQuery(sprintf("SELECT ps.Specification_ID FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON psv.Value_ID=ps.Value_ID WHERE psv.Group_ID=%d", mysql_real_escape_string($data->Row['Group_ID'])));
			while($data2->Row) {
				$values[] = $data2->Row['Specification_ID'];
				
				$data2->Next();	
			}
			$data2->Disconnect();
			
			if(!empty($values)) {
				new DataQuery(sprintf("DELETE FROM product_specification WHERE Specification_ID IN (%s)", mysql_real_escape_string(implode(', ', $values))));
			}
			
			$values = array();
			
			$data2 = new DataQuery(sprintf("SELECT Value_ID, Value FROM product_specification_value WHERE Group_ID=%d", mysql_real_escape_string($data->Row['Group_ID'])));
			while($data2->Row) {
				$values[] = $data2->Row;
				
				$data2->Next();	
			}
			$data2->Disconnect();
			
			$ranges = array();
			$exceeds = array();
			
			foreach($values as $value) {
				if(stristr($value['Value'], '-')) {
					$parts = explode('-', $value['Value']);

					if(count($parts) >= 2) {
						$ranges[$value['Value_ID']] = array($parts[0], $parts[1]);
					}
				} elseif(stristr($value['Value'], '+')) {
					$exceeds[$value['Value_ID']] = array(trim(preg_replace('/[^0-9\.]/', '', $value['Value'])));
				}
			}
			
			$subName = trim(str_ireplace('range', '', $data->Row['Reference']));
			
			$data2 = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Reference LIKE '%s'", mysql_real_escape_string($subName)));
			if($data2->TotalRows > 0) {
				$data3 = new DataQuery(sprintf("SELECT Product_ID, Value FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON psv.Value_ID=ps.Value_ID WHERE psv.Group_ID=%d", mysql_real_escape_string($data2->Row['Group_ID'])));
				while($data3->Row) {
					if(!empty($data3->Row['Value'])) {
						$value = trim(preg_replace('/[^0-9\s\.]/', ' ', $data3->Row['Value']));
						$parts = explode(' ', $value);
						
						if(!empty($parts)) {
							$value = $parts[0];
							$found = false;
							
							if(!$found) {
								foreach($ranges as $valueId=>$valueData) {
									if(($value >= $valueData[0]) && ($value <= $valueData[1])) {
										$spec = new ProductSpec();
										$spec->Value->ID = $valueId;
										$spec->Product->ID = $data3->Row['Product_ID'];
										$spec->Add();
										
										$found = true;								
										break;	
									}
								}
							}
							
							if(!$found) {
								foreach($exceeds as $valueId=>$valueData) {
									if($value >= $valueData[0]) {
										$spec = new ProductSpec();
										$spec->Value->ID = $valueId;
										$spec->Product->ID = $data3->Row['Product_ID'];
										$spec->Add();
										
										$found = true;
										break;	
									}
								}
							}
						}
					}

					$data3->Next();
				}
				$data3->Disconnect();
			}
			$data2->Disconnect();
		
			$data->Next();	
		}
		$data->Disconnect();
	}	

	public $ID;
	public $ParentID;
	public $Name;
	public $Reference;
	public $SequenceNumber;
	public $IsFilterable;
	public $IsVisible;
	public $IsHidden;
	public $Units;
	public $DataType;

	public function __construct($id=NULL){
		$this->IsFilterable = 'N';
		$this->IsVisible = 'Y';
		$this->IsHidden = 'N';
		$this->SequenceNumber = 1000;
		$this->DataType = 'string';

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	public function Get($id=NULL, $connection = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM product_specification_group WHERE Group_ID=%d", mysql_real_escape_string($this->ID)), $connection);
		if($data->TotalRows > 0) {
			$this->ParentID = $data->Row['Parent_ID'];
			$this->Name = $data->Row['Name'];
			$this->Reference = $data->Row['Reference'];
			$this->SequenceNumber = $data->Row['Sequence_Number'];
			$this->IsFilterable = $data->Row['Is_Filterable'];
			$this->IsVisible = $data->Row['Is_Visible'];
			$this->IsHidden = $data->Row['Is_Hidden'];
			$this->Units = $data->Row['Units'];
			$this->DataType = $data->Row['Data_Type'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function Add($connection = null){
		$data = new DataQuery(sprintf("INSERT INTO product_specification_group (Parent_ID, Name, Reference, Sequence_Number, Is_Filterable, Is_Visible, Is_Hidden, Units, Data_Type) VALUES (%d, '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s')", mysql_real_escape_string($this->ParentID), mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Reference), mysql_real_escape_string($this->SequenceNumber), mysql_real_escape_string($this->IsFilterable), mysql_real_escape_string($this->IsVisible), mysql_real_escape_string($this->IsHidden), mysql_real_escape_string($this->Units), mysql_real_escape_string($this->DataType)), $connection);

		$this->ID = $data->InsertID;
	}

	public function Update($connection = null){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE product_specification_group SET Parent_ID=%d, Name='%s', Reference='%s', Sequence_Number=%d, Is_Filterable='%s', Is_Visible='%s', Is_Hidden='%s', Units='%s', Data_Type='%s' WHERE Group_ID=%d", mysql_real_escape_string($this->ParentID), mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Reference), mysql_real_escape_string($this->SequenceNumber), mysql_real_escape_string($this->IsFilterable), mysql_real_escape_string($this->IsVisible), mysql_real_escape_string($this->IsHidden), mysql_real_escape_string($this->Units), mysql_real_escape_string($this->DataType), mysql_real_escape_string($this->ID)), $connection);
	}

	public function Delete($id=NULL, $connection = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_specification_group WHERE Group_ID=%d", mysql_real_escape_string($this->ID)), $connection);

		$value = new ProductSpec();
		$value->ID = $this->ID;
		$value->Delete();
		$value->Disconnect();

		$data = new DataQuery(sprintf("SELECT Value_ID FROM product_specification_value WHERE Group_ID=%d", mysql_real_escape_string($this->ID)), $connection);
		while($data->Row) {
			$value->Delete($data->Row['Value_ID'], $connection);

			$data->Next();
		}
		$data->Disconnect();

		new DataQuery(sprintf("UPDATE product_specification_group SET Parent_ID=0 WHERE Parent_ID=%d", mysql_real_escape_string($this->ID)), $connection);
	}
}