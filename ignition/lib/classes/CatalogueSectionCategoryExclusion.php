<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class CatalogueSectionCategoryExclusion {
	var $ID;
	var $CatalogueSectionCategoryID;
	var $CategoryID;

	function CatalogueSectionCategoryExclusion($id = null) {
		if(!is_null($id)) {
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

		$data = new DataQuery(sprintf("SELECT * FROM catalogue_section_category_exclusion WHERE Catalogue_Section_Category_Exclusion_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->CatalogueSectionCategoryID = $data->Row['Catalogue_Section_Category_ID'];
			$this->CategoryID = $data->Row['Category_ID'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO catalogue_section_category_exclusion (Catalogue_Section_Category_ID, Category_ID) VALUES (%d, %d)", mysql_real_escape_string($this->CatalogueSectionCategoryID), mysql_real_escape_string($this->CategoryID)));

		$this->ID = $data->InsertID;
	}

	function Update() {


		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE catalogue_section_category_exclusion SET Catalogue_Section_Category_ID=%d, Category_ID=%d WHERE Catalogue_Section_Category_Exclusion_ID=%d", mysql_real_escape_string($this->CatalogueSectionCategoryID), mysql_real_escape_string($this->CategoryID), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}


if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM catalogue_section_category_exclusion WHERE Catalogue_Section_Category_Exclusion_ID=%d", mysql_real_escape_string($this->ID)));
	}
}
?>