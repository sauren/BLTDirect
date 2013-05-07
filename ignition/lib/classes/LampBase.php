<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Image.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductSpecValue.php");

class LampBase {
	var $ID;
	var $SequenceNumber;
	var $Name;
	var $Image;
	var $Value;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function LampBase($id = null) {
		$this->Value = new ProductSpecValue();

		$this->Image = new Image();
		$this->Image->OnConflict = 'makeunique';
		$this->Image->SetMinDimensions($GLOBALS['BASE_IMG_MIN_WIDTH'], $GLOBALS['BASE_IMG_MIN_HEIGHT']);
		$this->Image->SetMaxDimensions($GLOBALS['BASE_IMG_MAX_WIDTH'], $GLOBALS['BASE_IMG_MAX_HEIGHT']);
		$this->Image->SetDirectory($GLOBALS['BASE_IMAGES_DIR_FS']);

		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}


		$data = new DataQuery(sprintf("SELECT * FROM lamp_base WHERE Lamp_Base_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->SequenceNumber = $data->Row['Sequence_Number'];
			$this->Name = $data->Row['Name'];
			$this->Image->FileName = $data->Row['Image'];
			$this->Value->ID = $data->Row['Specification_Value_ID'];
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

	function Add($imageField = null) {
		if(!is_null($imageField) && isset($_FILES[$imageField]) && !empty($_FILES[$imageField]['name'])) {
			if(!$this->Image->Upload($imageField)) {
				return false;
			} else {
				if(!$this->Image->CheckDimensions()){
					$this->Image->Resize();
				}
			}
		}

		$data = new DataQuery(sprintf("INSERT INTO lamp_base (Sequence_Number, Name, Image, Specification_Value_ID, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, '%s', '%s', %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->SequenceNumber), mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Image->FileName), mysql_real_escape_string($this->Value->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
		$this->SequenceNumber = $this->ID;
		$this->Update();
	}

	function Update($imageField = null) {
		$tempImage = new Image($this->Image->FileName, $this->Image->Directory);
		if(!is_numeric($this->ID)){
			return false;
		}

		if(!is_null($imageField) && isset($_FILES[$imageField]) && !empty($_FILES[$imageField]['name'])){
			if(!$this->Image->Upload($imageField)){
				return false;
			} else {
				if(!$this->Image->CheckDimensions()){
					$this->Image->Resize();
				}
				$tempImage->Delete();
			}
		}

		new DataQuery(sprintf("UPDATE lamp_base SET Sequence_Number=%d, Name='%s', Image='%s', Specification_Value_ID=%d, Modified_On=NOW(), Modified_By=%d WHERE Lamp_Base_ID=%d", mysql_real_escape_string($this->SequenceNumber), mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Image->FileName), mysql_real_escape_string($this->Value->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		if(empty($this->Image->FileName)) {
			$this->Get();
		}

		if(!empty($this->Image->FileName) && $this->Image->Exists()) {
			$this->Image->Delete();
		}

		new DataQuery(sprintf("DELETE FROM lamp_base WHERE Lamp_Base_ID=%d", mysql_real_escape_string($this->ID)));
	}
}
?>