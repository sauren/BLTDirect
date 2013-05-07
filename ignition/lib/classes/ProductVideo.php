<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");


class ProductVideo{
	var $ID;
	var $ProductID;
	var $YoutubeURL;
	var $IsActive;
	var $VideoTitle;
	var $Videos;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function ProductVideo($id=NULL){

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT * from product_videos where Product_Video_ID=%d", mysql_real_escape_string($this->ID)));
			if($data->TotalRows > 0) {
				$this->ID = $data->Row['Product_Video_ID'];
				$this->ProductID = $data->Row['Product_ID'];
				$this->IsActive = $data->Row['Is_Active'];
				$this->VideoTitle = $data->Row['Video_Title'];
				$this->YoutubeURL = ($data->Row['Youtube_Url']);
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


	function GetProductVideo($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}
		$this->Videos = array();
		$data = new DataQuery(sprintf("SELECT * from product_videos where Product_ID=%d and Is_Active='Y'order by Modified_On DESC", mysql_real_escape_string($this->ID)));
			
		while($data->Row) {
			$this->Videos[] = $data->Row;
			$data->Next();	
		}

		$data->Disconnect();
		return false;
	}


	function Add()
	{
		$data = new DataQuery(sprintf("insert into product_videos (
			Product_ID,
			Is_Active,
			Video_Title,
			Youtube_Url,
			Created_On,
			Created_By,
			Modified_On,
			Modified_By
			) values (%d,'%s','%s', '%s', Now(), %d, Now(), %d)",
			mysql_real_escape_string($this->ProductID),
			mysql_real_escape_string($this->IsActive),
			mysql_real_escape_string($this->VideoTitle),
			mysql_real_escape_string($this->YoutubeURL),
			mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
			mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;

		return true;
	}

	function Update()
	{
		
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("update product_videos set
			Product_ID=%d,
			Is_Active='%s',
			Video_Title='%s',
			Youtube_Url='%s',
			Modified_On=Now(),
			Modified_By=%d
			where Product_Video_ID=%d",
			mysql_real_escape_string($this->ProductID),
			mysql_real_escape_string($this->IsActive),
			mysql_real_escape_string($this->VideoTitle),
			mysql_real_escape_string($this->YoutubeURL),
			mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
			mysql_real_escape_string($this->ID)));

		return true;

	}

	function Delete($id=NULL) {
		if(!is_null($id)) $this->ID = $id;
		
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("delete from product_videos where Product_video_ID=%d", mysql_real_escape_string($this->ID)));

		$data->Disconnect();
		return true;
	}
}
?>