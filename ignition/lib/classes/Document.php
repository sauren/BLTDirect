<?php
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');

class Document
{
	var $ID;
	var $Title;
	var $Body;
	var $ParentID;
	var $Children;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function Document($id = null)
	{
		$this->Children = array();

		if(!is_null($id))
		{
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = null)
	{
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM document WHERE Document_ID=%d", mysql_real_escape_string($this->ID)));

		if($data->TotalRows > 0) {
			$this->Title = $data->Row['Title'];
			$this->Body = $data->Row['Body'];
			$this->ParentID = $data->Row['Parent_ID'];
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

	function GetChildren()
	{
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT ID FROM document WHERE Parent_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$child = new Document();

			if($child->Get($data->Row['ID'])) {
				$this->Children[] = $child;
			}

			$data->Next();
		}

		$data->Disconnect();
		return true;
	}

	function Add()
	{
		$data = new DataQuery(sprintf("INSERT INTO document (Parent_ID, Title, Body, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, '%s', '%s', Now(), %d, Now(), %d)", mysql_real_escape_string($this->ParentID), mysql_real_escape_string(stripslashes($this->Title)), mysql_real_escape_string(stripslashes($this->Body)), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();

		return true;
	}

	function Update()
	{
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("UPDATE document SET Parent_ID=%d, Title='%s', Body='%s', Modified_On=Now(), Modified_By=%d WHERE Document_ID=%d", mysql_real_escape_string($this->ParentID), mysql_real_escape_string(stripslashes($this->Title)), mysql_real_escape_string(stripslashes($this->Body)), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
		$data->Disconnect();

		return true;
	}

	function Remove($id = null)
	{
		if(!is_null($id) && is_numeric($id))
			$this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}

		$this->RemoveChildren($this->ID);

		$data = new DataQuery(sprintf("DELETE FROM document WHERE Document_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();

		return true;
	}

	function RemoveChildren($parentID = null)
	{
		if(!is_null($parentID))
		{
			$data = new DataQuery(sprintf("SELECT Document_ID FROM document WHERE Parent_ID=%d", mysql_real_escape_string($parentID)));

			for($a = 0; $a < $data->TotalRows; $a++)
			{
				$this->RemoveChildren($data->Row['Document_ID']);

				$data2 = new DataQuery(sprintf("DELETE FROM document WHERE Document_ID=%d", mysql_real_escape_string($data->Row['Document_ID'])));
				$data2->Disconnect();

				$data->Next();
			}

			$data->Disconnect();
		}
	}
}
?>