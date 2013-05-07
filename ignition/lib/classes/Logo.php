<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Image.php');

class Logo {
	var $ID;
	var $Name;
	var $Image;
	var $ActiveFromDate;
	var $ActiveToDate;
	var $IsDefault;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function __construct($id = null) {
		$this->IsDefault = 'N';
		$this->ActiveFromDate = '0000-00-00 00:00:00';
		$this->ActiveToDate = '0000-00-00 00:00:00';

		$this->Image = new Image();
		$this->Image->OnConflict = 'makeunique';
		$this->Image->SetMinDimensions($GLOBALS['LOGO_IMAGE_MIN_WIDTH'], $GLOBALS['LOGO_IMAGE_MIN_HEIGHT']);
		$this->Image->SetMaxDimensions($GLOBALS['LOGO_IMAGE_MAX_WIDTH'], $GLOBALS['LOGO_IMAGE_MAX_HEIGHT']);
		$this->Image->SetDirectory($GLOBALS['LOGO_IMAGE_DIR_FS']);

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM logo WHERE Logo_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Name = $data->Row['Name'];
			$this->Image->FileName = $data->Row['Image_File_Name'];
			$this->IsDefault = $data->Row['Is_Default'];
			$this->ActiveFromDate = $data->Row['Active_From_Date'];
			$this->ActiveToDate = $data->Row['Active_To_Date'];
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

	function GetActiveLogoID() {
		$data = new DataQuery(sprintf("SELECT Logo_ID FROM logo WHERE Active_From_Date>'0000-00-00 00:00:00' AND Active_From_Date<NOW() AND Active_To_Date>'0000-00-00 00:00:00' AND Active_To_Date>=NOW()"));
		if($data->TotalRows > 0) {
			$results = array();

			while($data->Row) {
				$results[] = $data->Row['Logo_ID'];

				$data->Next();
			}
			$data->Disconnect();

			$this->ID = $results[rand(0, count($results) - 1)];

			return true;
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT Logo_ID FROM logo WHERE (Active_From_Date='0000-00-00 00:00:00' AND Active_To_Date>'0000-00-00 00:00:00' AND Active_To_Date>=NOW()) OR (Active_From_Date>'0000-00-00 00:00:00' AND Active_From_Date<=NOW() AND Active_To_Date='0000-00-00 00:00:00')"));
		if($data->TotalRows > 0) {
			$results = array();

			while($data->Row) {
				$results[] = $data->Row['Logo_ID'];

				$data->Next();
			}
			$data->Disconnect();

			$this->ID = $results[rand(0, count($results) - 1)];

			return true;
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT Logo_ID FROM logo WHERE Active_From_Date='0000-00-00 00:00:00' AND Active_To_Date='0000-00-00 00:00:00'"));
		if($data->TotalRows > 0) {
			$results = array();

			while($data->Row) {
				$results[] = $data->Row['Logo_ID'];

				$data->Next();
			}
			$data->Disconnect();

			$this->ID = $results[rand(0, count($results) - 1)];

			return true;
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT Logo_ID FROM logo WHERE Is_Default='Y'"));
		if($data->TotalRows > 0) {
			$this->ID = $data->Row['Logo_ID'];
			$data->Disconnect();

			return true;
		}
		$data->Disconnect();

		return false;
	}

	function Add($imageField = null) {
		if(!is_null($imageField) && isset($_FILES[$imageField]) && !empty($_FILES[$imageField]['name'])){
			if(!$this->Image->Upload($imageField)){
				return false;
			} else {
				if(!$this->Image->CheckDimensions()){
					$this->Image->Resize();
				}
			}
		}

		if($this->IsDefault == 'Y') {
			$this->ClearDefault();
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("INSERT INTO logo (Name, Active_From_Date, Active_To_Date, Is_Default, Image_File_Name, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', '%s', '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->ActiveFromDate), mysql_real_escape_string($this->ActiveToDate), mysql_real_escape_string($this->IsDefault), mysql_real_escape_string($this->Image->FileName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;

		return true;
	}

	function Update($imageField = null) {
		$oldImage = new Image($this->Image->FileName, $this->Image->Directory);

		if(!is_null($imageField) && isset($_FILES[$imageField]) && !empty($_FILES[$imageField]['name'])){
			if(!$this->Image->Upload($imageField)){
				return false;
			} else {
				if(!$this->Image->CheckDimensions()){
					$this->Image->Resize();
				}

				if($oldImage->FileName != $this->Image->FileName) {
					$oldImage->Delete();
				}
			}
		}

		if($this->IsDefault == 'Y') {
			$this->ClearDefault();
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("UPDATE logo SET Name='%s', Active_From_Date='%s', Active_To_Date='%s', Is_Default='%s', Image_File_Name='%s', Modified_On=NOW(), Modified_By=%d WHERE Logo_ID=%d", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->ActiveFromDate), mysql_real_escape_string($this->ActiveToDate), mysql_real_escape_string($this->IsDefault), mysql_real_escape_string($this->Image->FileName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));

		return true;
	}

	function Delete($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(empty($this->Image->FileName)) {
			$this->Get();
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM logo WHERE Logo_ID=%d", mysql_real_escape_string($this->ID)));

		if(!empty($this->Image->FileName) && $this->Image->Exists()){
			$this->Image->Delete();
		}
	}

	function ClearDefault(){
		new DataQuery(sprintf("UPDATE logo SET Is_Default='N'"));
	}
}