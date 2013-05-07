<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IFile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Image.php');

class Brochure {
	public static function GetActiveBrochureID() {
		$data = new DataQuery(sprintf("SELECT Brochure_ID FROM brochure WHERE Active_From_Date>'0000-00-00 00:00:00' AND Active_From_Date<=NOW() AND Active_To_Date>'0000-00-00 00:00:00' AND Active_To_Date>=NOW()"));
		if($data->TotalRows > 0) {
			$results = array();
			
			while($data->Row) {
				$results[] = $data->Row['Brochure_ID'];
				
				$data->Next();
			}
			$data->Disconnect();

			return $results[rand(0, count($results) - 1)];
		}
		$data->Disconnect();
		
		$data = new DataQuery(sprintf("SELECT Brochure_ID FROM brochure WHERE (Active_From_Date='0000-00-00 00:00:00' AND Active_To_Date>'0000-00-00 00:00:00' AND Active_To_Date>=NOW()) OR (Active_From_Date>'0000-00-00 00:00:00' AND Active_From_Date<=NOW() AND Active_To_Date='0000-00-00 00:00:00')"));
		if($data->TotalRows > 0) {
			$results = array();
			
			while($data->Row) {
				$results[] = $data->Row['Brochure_ID'];
				
				$data->Next();
			}
			$data->Disconnect();
			
			return $results[rand(0, count($results) - 1)];
		}
		$data->Disconnect();
		
		$data = new DataQuery(sprintf("SELECT Brochure_ID FROM brochure WHERE Active_From_Date='0000-00-00 00:00:00' AND Active_To_Date='0000-00-00 00:00:00'"));
		if($data->TotalRows > 0) {
			$results = array();
			
			while($data->Row) {
				$results[] = $data->Row['Brochure_ID'];
				
				$data->Next();
			}
			$data->Disconnect();
			
			return $results[rand(0, count($results) - 1)];
		}
		$data->Disconnect();
		
		$data = new DataQuery(sprintf("SELECT Brochure_ID FROM brochure WHERE Is_Default='Y'"));
		if($data->TotalRows > 0) {
			$id = $data->Row['Brochure_ID'];
			$data->Disconnect();
			
			return $id;
		}
		$data->Disconnect();
		
		return false;
	}
	
	var $ID;
	var $Name;
	var $Image;
	var $Image2;
	var $Download;
	var $ActiveFromDate;
	var $ActiveToDate;
	var $IsDefault;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	
	function Brochure($id = null) {
		$this->IsDefault = 'N';
		$this->ActiveFromDate = '0000-00-00 00:00:00';
		$this->ActiveToDate = '0000-00-00 00:00:00';
		
		$this->Image = new Image();
		$this->Image->OnConflict = 'makeunique';
		$this->Image->SetMinDimensions($GLOBALS['BROCHURE_MENU_IMAGE_MIN_WIDTH'], $GLOBALS['BROCHURE_MENU_IMAGE_MIN_HEIGHT']);
		$this->Image->SetMaxDimensions($GLOBALS['BROCHURE_MENU_IMAGE_MAX_WIDTH'], $GLOBALS['BROCHURE_MENU_IMAGE_MAX_HEIGHT']);
		$this->Image->SetDirectory($GLOBALS['BROCHURE_MENU_IMAGE_DIR_FS']);
		
		$this->Image2 = new Image();
		$this->Image2->OnConflict = 'makeunique';
		$this->Image2->SetMinDimensions($GLOBALS['BROCHURE_SPREAD_IMAGE_MIN_WIDTH'], $GLOBALS['BROCHURE_SPREAD_IMAGE_MIN_HEIGHT']);
		$this->Image2->SetMaxDimensions($GLOBALS['BROCHURE_SPREAD_IMAGE_MAX_WIDTH'], $GLOBALS['BROCHURE_SPREAD_IMAGE_MAX_HEIGHT']);
		$this->Image2->SetDirectory($GLOBALS['BROCHURE_SPREAD_IMAGE_DIR_FS']);
		
		$this->Download = new IFile();
		$this->Download->OnConflict = 'makeunique';
		$this->Download->SetDirectory($GLOBALS['BROCHURE_DOWNLOAD_DIR_FS']);
		$this->Download->Extensions = '';
		
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

		$data = new DataQuery(sprintf("SELECT * FROM brochure WHERE Brochure_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Name = $data->Row['Name'];
			$this->Image->FileName = $data->Row['Image_File_Name'];
			$this->Image2->FileName = $data->Row['Image_2_File_Name'];
			$this->Download->FileName = $data->Row['Download_File_Name'];
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
	
	function Add($imageField = null, $imageField2 = null, $downloadField = null) {
		$uploadFailure = false;
		$uploadedImage = false;
		$uploadedImage2 = false;
		
		if(!is_null($imageField) && isset($_FILES[$imageField]) && !empty($_FILES[$imageField]['name'])){
			if(!$this->Image->Upload($imageField)){
				return false;
			} else {
				if(!$this->Image->CheckDimensions()){
					$this->Image->Resize();
				}
				
				$uploadedImage = true;
			}
		}
		
		if(!is_null($imageField2) && isset($_FILES[$imageField2]) && !empty($_FILES[$imageField2]['name'])){
			if(!$this->Image2->Upload($imageField2)){
				return false;
			} else {
				if(!$this->Image2->CheckDimensions()){
					$this->Image2->Resize();
				}
				
				$uploadedImage2 = true;
			}
		}
		
		if(!is_null($downloadField) && isset($_FILES[$downloadField]) && !empty($_FILES[$downloadField]['name'])){
			if(!$this->Download->Upload($downloadField)){
				$uploadFailure = true;
			}
		}
		
		if($uploadFailure) {
			if($uploadedImage) {
				$this->Image->Delete();
			}
			
			if($uploadedImage2) {
				$this->Image2->Delete();
			}
			
			return false;
		}

		if($this->IsDefault == 'Y') {
			$this->ClearDefault();
		}
		
		$data = new DataQuery(sprintf("INSERT INTO brochure (Name, Active_From_Date, Active_To_Date, Is_Default, Image_File_Name, Image_2_File_Name, Download_File_Name, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->ActiveFromDate), mysql_real_escape_string($this->ActiveToDate), mysql_real_escape_string($this->IsDefault), mysql_real_escape_string($this->Image->FileName), mysql_real_escape_string($this->Image2->FileName), mysql_real_escape_string($this->Download->FileName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();
		
		return true;
	}
	
	function Update($imageField = null, $imageField2 = null, $downloadField = null) {
		$oldImage = new Image($this->Image->FileName, $this->Image->Directory);
		$oldImage2 = new Image($this->Image2->FileName, $this->Image2->Directory);
		$oldDownload = new IFile($this->Download->FileName, $this->Download->Directory);
		$uploadFailure = false;
		$uploadedImage = false;
		$uploadedImage2 = false;
		
		if(!is_null($imageField) && isset($_FILES[$imageField]) && !empty($_FILES[$imageField]['name'])){
			if(!$this->Image->Upload($imageField)){
				return false;
			} else {
				if(!$this->Image->CheckDimensions()){
					$this->Image->Resize();
				}
				
				$uploadedImage = true;
			}
		}
		
		if(!is_null($imageField2) && isset($_FILES[$imageField2]) && !empty($_FILES[$imageField2]['name'])){
			if(!$this->Image2->Upload($imageField2)){
				return false;
			} else {
				if(!$this->Image2->CheckDimensions()){
					$this->Image2->Resize();
				}
				
				$uploadedImage2 = true;
			}
		}
		
		if(!is_null($downloadField) && isset($_FILES[$downloadField]) && !empty($_FILES[$downloadField]['name'])){
			if(!$this->Download->Upload($downloadField)){
				$uploadFailure = true;
			}
		}
		
		if($uploadFailure) {
			if($uploadedImage) {
				$this->Image->Delete();
			}
			
			if($uploadedImage2) {
				$this->Image2->Delete();
			}
			
			return false;
		}
		
		if(!is_null($imageField) && isset($_FILES[$imageField]) && !empty($_FILES[$imageField]['name'])){
			if($oldImage->FileName != $this->Image->FileName) {
				$oldImage->Delete();
			}
		}
		
		if(!is_null($imageField2) && isset($_FILES[$imageField2]) && !empty($_FILES[$imageField2]['name'])){
			if($oldImage2->FileName != $this->Image2->FileName) {
				$oldImage2->Delete();
			}
		}
		
		if(!is_null($downloadField) && isset($_FILES[$downloadField]) && !empty($_FILES[$downloadField]['name'])){
			if($oldDownload->FileName != $this->Download->FileName) {
				$oldDownload->Delete();
			}
		}

		if($this->IsDefault == 'Y') {
			$this->ClearDefault();
		}

		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("UPDATE brochure SET Name='%s', Active_From_Date='%s', Active_To_Date='%s', Is_Default='%s', Image_File_Name='%s', Image_2_File_Name='%s', Download_File_Name='%s', Modified_On=NOW(), Modified_By=%d WHERE Brochure_ID=%d", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->ActiveFromDate), mysql_real_escape_string($this->ActiveToDate), mysql_real_escape_string($this->IsDefault), mysql_real_escape_string($this->Image->FileName), mysql_real_escape_string($this->Image2->FileName), mysql_real_escape_string($this->Download->FileName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
		$data->Disconnect();
		
		return true;
	}
	
	function Delete($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}
		
		if(empty($this->Image->FileName) && empty($this->Image2->FileName) && empty($this->Download->FileName)){
			$this->Get();
		}
			
		$data = new DataQuery(sprintf("DELETE FROM brochure WHERE Brochure_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();
		
		if(!empty($this->Image->FileName) && $this->Image->Exists()){
			$this->Image->Delete();
		}
		
		if(!empty($this->Image2->FileName) && $this->Image2->Exists()){
			$this->Image2->Delete();
		}
		
		if(!empty($this->Download->FileName) && $this->Download->Exists()){
			$this->Download->Delete();
		}
	}
	
	function ClearDefault(){
		$data = new DataQuery(sprintf("UPDATE brochure SET Is_Default='N'"));
		$data->Disconnect();
	}
}
?>