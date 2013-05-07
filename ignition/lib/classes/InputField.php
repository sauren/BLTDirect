<?php
class InputField{
	var $ID;
	var $Label;
	var $HtmlType;
	var $Value;
	var $ValidationType;
	var $MinLength;
	var $MaxLength;
	var $Required;
	var $Attributes;
	var $Options;
	var $Groups;
	var $Valid;
	var $FieldNum;
	var $Default;

	function InputField($id, $label, $htmlType, $value, $validationType, $minLength=2, $maxLength=128, $required=true, $attributes=""){
		$this->ID = $id;
		$this->Label = $label;
		$this->HtmlType = $htmlType;
		$this->Default = ((strtolower($htmlType) == 'selectmultiple') || (strtolower($htmlType) == 'hidden-array')) ? (is_array($value) ? $value : array($value)) : trim($value);
		$this->ValidationType = $validationType;
		$this->MinLength = $minLength;
		$this->MaxLength = $maxLength;
		$this->Required = $required;
		$this->Attributes = $attributes;
		$this->Valid = NULL;
		$this->Options = array();
		$this->Groups = array();

		if(isset($_REQUEST[$this->ID])){
			if((strtolower($htmlType) == 'selectmultiple') || (strtolower($htmlType) == 'hidden-array')) {
				$this->Value = is_array($_REQUEST[$this->ID]) ? $_REQUEST[$this->ID] : array($_REQUEST[$this->ID]);
			} else {
				if(get_magic_quotes_gpc() == 1){
					$_REQUEST[$this->ID] = stripslashes($_REQUEST[$this->ID]);
				}

				$this->Value = trim($_REQUEST[$this->ID]);
			}
		} else {
			if((strtolower($htmlType) == 'selectmultiple') || (strtolower($htmlType) == 'hidden-array')) {
				$this->Value = $this->Default;
			} else {
				$this->Value = trim($this->Default);
			}
		}

		if (strtolower($this->HtmlType) == 'checkbox'){
			if(!isset($_REQUEST[$this->ID]) && isset($_REQUEST['confirm'])){
				$this->Value = 'N';
			}
		}
	}
}