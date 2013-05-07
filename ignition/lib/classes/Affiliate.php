<?php
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/DataQuery.php");
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/Image.php");

class Affiliate {
	var $ID;
	var $Title;
	var $URL;
	var $Description;
	var $Image;

	function Affiliate($id = null) {
		$this->Image = new Image;
		$this->Image->OnConflict = "makeunique";
		$this->Image->SetMinDimensions($GLOBALS['AFFILIATES_IMG_MIN_WIDTH'], $GLOBALS['AFFILIATES_IMG_MIN_HEIGHT']);
		$this->Image->SetMaxDimensions($GLOBALS['AFFILIATES_IMG_MAX_WIDTH'], $GLOBALS['AFFILIATES_IMG_MAX_HEIGHT']);
		$this->Image->SetDirectory($GLOBALS['AFFILIATES_IMAGES_DIR_FS']);

		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
		if(!is_numeric($this->ID)) return false;
	}

	function Get($id = null) {
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)) return false;

		$data = new DataQuery(sprintf("SELECT * FROM affiliate WHERE Affiliate_ID=%d", mysql_real_escape_string($this->ID)));

		if($data->TotalRows > 0) {
			$this->ID = $id;
			$this->Title = $data->Row['Title'];
			$this->URL = $data->Row['URL'];
			$this->Description = $data->Row['Description'];
			$this->Image->FileName = $data->Row['Image'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add($imageField = null)
	{
		if(!is_null($imageField) && isset($_FILES[$imageField]) && !empty($_FILES[$imageField]['name'])){
			if(!$this->Image->Upload($imageField)){
				return false;
			} else {
				if(!$this->Image->CheckDimensions()){
					$this->Image->Resize();
				}
			}
		}

		$data = new DataQuery(sprintf("INSERT INTO affiliate (Title, URL, Description, Image, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', '%s', '%s', Now(), %d, Now(), %d)", 
						mysql_real_escape_string($this->Title), 
						mysql_real_escape_string($this->URL), 
						mysql_real_escape_string($this->Description), 
						mysql_real_escape_string($this->Image->FileName), 
						mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), 
						mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();

		return true;
	}

	function Update($imageField = null)
	{
		if(!is_numeric($this->ID)) return false;
		$oldImage = new Image($this->Image->FileName, $this->Image->Directory);

		if(!is_null($imageField) && isset($_FILES[$imageField]) && !empty($_FILES[$imageField]['name'])){
			if(!$this->Image->Upload($imageField)){
				return false;
			} else {
				if(!$this->Image->CheckDimensions()){
					$this->Image->Resize();
				}

				$oldImage->Delete();
			}
		}

		$data = new DataQuery(sprintf("UPDATE affiliate SET Title='%s', URL='%s', Description='%s', Image='%s', Modified_On=Now(), Modified_By=%d WHERE Affiliate_ID=%d", 
					mysql_real_escape_string($this->Title), 
					mysql_real_escape_string($this->URL), 
					mysql_real_escape_string($this->Description), 
					mysql_real_escape_string($this->Image->FileName), 
					mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), 
					mysql_real_escape_string($this->ID)));
		$data->Disconnect();

		return true;
	}

	function Remove($id = null)
	{
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)) return false;

		$data = new DataQuery(sprintf("DELETE FROM affiliate WHERE Affiliate_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();

		return true;
	}
}
?>