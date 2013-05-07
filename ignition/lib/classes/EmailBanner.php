<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Image.php");

class EmailBanner {
	var $ID;
	var $Name;
	var $Image;

	function EmailBanner($id=NULL) {
		$this->Image = new Image;
		$this->Image->OnConflict = 'makeunique';
		$this->Image->SetMinDimensions($GLOBALS['EMAIL_BANNER_IMAGE_MIN_WIDTH'], $GLOBALS['EMAIL_BANNER_IMAGE_MIN_HEIGHT']);
		$this->Image->SetMaxDimensions($GLOBALS['EMAIL_BANNER_IMAGE_MAX_WIDTH'], $GLOBALS['EMAIL_BANNER_IMAGE_MAX_HEIGHT']);
		$this->Image->SetDirectory($GLOBALS['EMAIL_BANNER_IMAGES_DIR_FS']);

		if(!is_null($id)) {
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

		$data = new DataQuery(sprintf("SELECT * FROM email_banner WHERE EmailBannerID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Name = $data->Row['Name'];
			$this->Image->FileName = $data->Row['FileName'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add($imageField = NULL) {
		if(!is_null($imageField) && isset($_FILES[$imageField]) && !empty($_FILES[$imageField]['name'])) {
			if(!$this->Image->Upload($imageField)){
				return false;
			} else {
				if(!$this->Image->CheckDimensions()){
					$this->Image->Resize();
				}
			}
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("INSERT INTO email_banner (Name, FileName) VALUES ('%s', '%s')", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Image->FileName)));

		$this->ID = $data->InsertID;
		
		return true;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE email_banner SET Name='%s', FileName='%s' WHERE EmailBannerID=%d", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Image->FileName), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(empty($this->Image->FileName)) {
			$this->Get();
		}

		if(!is_numeric($this->ID)){
			return false;
		}
		
		new DataQuery(sprintf("DELETE FROM email_banner WHERE EmailBannerID=%d", mysql_real_escape_string($this->ID)));
		new DataQuery(sprintf("UPDATE email_date SET EmailBannerID=0 WHERE EmailBannerID=%d", mysql_real_escape_string($this->ID)));
		
		if(!empty($this->Image->FileName) && $this->Image->Exists()) {
			$this->Image->Delete();
		}
	}
}