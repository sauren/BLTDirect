<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CatalogueSectionCategorySpecification.php');

class CatalogueSectionCategory {
	var $ID;
	var $CatalogueSectionID;
	var $CategoryID;
	var $CategoryCatalogueImageID;
	var $Title;
	var $Description;
	var $SequenceNumber;
	var $SortMethod;
	var $SortSpecificationGroupID;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function CatalogueSectionCategory($id = null) {
		$this->Method = 'Code';

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

		$data = new DataQuery(sprintf("SELECT * FROM catalogue_section_category WHERE Catalogue_Section_Category_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->CatalogueSectionID = $data->Row['Catalogue_Section_ID'];
			$this->CategoryID = $data->Row['Category_ID'];
			$this->CategoryCatalogueImageID = $data->Row['Category_Catalogue_Image_ID'];
			$this->Title = $data->Row['Title'];
			$this->Description = $data->Row['Description'];
			$this->SequenceNumber = $data->Row['Sequence_Number'];
			$this->SortMethod = $data->Row['Sort_Method'];
			$this->SortSpecificationGroupID = $data->Row['Sort_Specification_Group_ID'];
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
		$data = new DataQuery(sprintf("INSERT INTO catalogue_section_category (Catalogue_Section_ID, Category_ID, Category_Catalogue_Image_ID, Title, Description, Sequence_Number, Sort_Method, Sort_Specification_Group_ID, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, %d, %d, '%s', '%s', %d, '%s', %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->CatalogueSectionID), mysql_real_escape_string($this->CategoryID), mysql_real_escape_string($this->CategoryCatalogueImageID), mysql_real_escape_string($this->Title), mysql_real_escape_string($this->Description), mysql_real_escape_string($this->SequenceNumber), mysql_real_escape_string($this->SortMethod), mysql_real_escape_string($this->SortSpecificationGroupID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
		$this->SequenceNumber = $this->ID;
		$this->Update();
	}

	function Update() {

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE catalogue_section_category SET Catalogue_Section_ID=%d, Category_ID=%d, Category_Catalogue_Image_ID=%d, Title='%s', Description='%s', Sequence_Number=%d, Sort_Method='%s', Sort_Specification_Group_ID=%d, Modified_On=NOW(), Modified_By=%d WHERE Catalogue_Section_Category_ID=%d", $this->CatalogueSectionID, $this->CategoryID, $this->CategoryCatalogueImageID, mysql_real_escape_string($this->Title), mysql_real_escape_string($this->Description), $this->SequenceNumber, $this->SortMethod, $this->SortSpecificationGroupID, $GLOBALS['SESSION_USER_ID'], mysql_real_escape_string($this->ID)));
	}

	function Delete($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM catalogue_section_category WHERE Catalogue_Section_Category_ID=%d", mysql_real_escape_string($this->ID)));
		CatalogueSectionCategorySpecification::DeleteCatalogueSection($this->ID);
	}
}
?>