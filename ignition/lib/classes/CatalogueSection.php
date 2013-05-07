<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CatalogueSectionCategory.php');

class CatalogueSection {
	var $ID;
	var $CatalogueID;
	var $Title;
	var $Description;
	var $SequenceNumber;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function CatalogueSection($id = null) {
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


		$data = new DataQuery(sprintf("SELECT * FROM catalogue_section WHERE Catalogue_Section_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->CatalogueID = $data->Row['Catalogue_ID'];
			$this->Title = $data->Row['Title'];
			$this->Description = $data->Row['Description'];
			$this->SequenceNumber = $data->Row['Sequence_Number'];
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

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO catalogue_section (Catalogue_ID, Title, Description, Sequence_Number, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, '%s', '%s', %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->CatalogueID), mysql_real_escape_string($this->Title), mysql_real_escape_string($this->Description), mysql_real_escape_string($this->SequenceNumber), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;

		$this->SequenceNumber = $this->ID;
		$this->Update();
	}

	function Update() {

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("UPDATE catalogue_section SET Catalogue_ID=%d, Title='%s', Description='%s', Sequence_Number=%d, Modified_On=NOW(), Modified_By=%d WHERE Catalogue_Section_ID=%d", mysql_real_escape_string($this->CatalogueID), mysql_real_escape_string($this->Title), mysql_real_escape_string($this->Description), mysql_real_escape_string($this->SequenceNumber), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM catalogue_section WHERE Catalogue_Section_ID=%d", mysql_real_escape_string($this->ID)));

		$category = new CatalogueSectionCategory();

		$data = new DataQuery(sprintf("SELECT Catalogue_Section_Category_ID FROM catalogue_section_category WHERE Catalogue_Section_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$category->Delete($data->Row['Catalogue_Section_Category_ID']);

			$data->Next();
		}
		$data->Disconnect();
	}
}
?>