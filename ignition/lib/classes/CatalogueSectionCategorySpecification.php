<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class CatalogueSectionCategorySpecification {
	var $ID;
	var $CatalogueSectionCategoryID;
	var $SpecificationGroupID;
	var $SequenceNumber;

	function CatalogueSectionCategorySpecification($id = null) {
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

		$data = new DataQuery(sprintf("SELECT * FROM catalogue_section_category_specification WHERE Catalogue_Section_Category_Specification_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->CatalogueSectionCategoryID = $data->Row['Catalogue_Section_Category_ID'];
			$this->SpecificationGroupID = $data->Row['Specification_Group_ID'];
			$this->SequenceNumber = $data->Row['Sequence_Number'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO catalogue_section_category_specification (Catalogue_Section_Category_ID, Specification_Group_ID, Sequence_Number) VALUES (%d, %d, %d)", mysql_real_escape_string($this->CatalogueSectionCategoryID), mysql_real_escape_string($this->SpecificationGroupID), mysql_real_escape_string($this->SequenceNumber)));

		$this->ID = $data->InsertID;
		$this->SequenceNumber = $this->ID;
		$this->Update();
	}

	function Update() {

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE catalogue_section_category_specification SET Catalogue_Section_Category_ID=%d, Specification_Group_ID=%d, Sequence_Number=%d WHERE Catalogue_Section_Category_Specification_ID=%d", mysql_real_escape_string($this->CatalogueSectionCategoryID), mysql_real_escape_string($this->SpecificationGroupID), mysql_real_escape_string($this->SequenceNumber), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM catalogue_section_category_specification WHERE Catalogue_Section_Category_Specification_ID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeleteCatalogueSection($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM catalogue_section_category_specification WHERE Catalogue_Section_Category_ID=%d", mysql_real_escape_string($id)));
	}
}
?>