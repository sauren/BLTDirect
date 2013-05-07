<?php
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/InputField.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Bubble.php');

class Form {
	var $TabIndex;
	var $Action;
	var $Method;
	var $ID;
	var $InputFields;
	var $Valid;
	var $Errors;
	var $Icons;
	var $OnSubmitFunction;
	var $ShowIcon;
	var $DisableAutocomplete;

	var $RegularExp = array (
		'username' => '^((\"[^\"\f\n\r\t\v\b]+\")|([\w\!\#\$\%\&\'\*\+\-\~\/\^\`\|\{\}]+(\.[\w\!\#\$\%\&\'\*\+\-\~\/\^\`\|\{\}]+)*))@((\[(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))\])|(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))|((([A-Za-z0-9\-])+\.)+[A-Za-z\-]+))$',
		'password' => '^[0-9a-zA-Z\@\*\#\%%\.!_\-\s]{%d,%d}$',
		'telephone' => '^[0-9\+\(\)\-\.\-\s]{5,25}$',
		'alpha' => '(^[a-zA-Z]{%d,%d}$)',
		'alpha_numeric'	=> '(^[0-9a-zA-Z_\-\s\&]{%d,%d}$)',
		'numeric_unsigned' => '^\d{%d,%d}$',
		'numeric_signed' => '^(\-|\+)?\d{%d,%d}$',
		'float' => '^(\-)?((\d*)(\.)?(\d*)){%d,%d}$',
		'link' => '^(http|ftp|https):\/\/[\w\-\_]+(\.[\w\-\_]+)+([\w\-\.,@\?\^\=\%\&:\/\~\+\#]*[\w\-\@\?\^\=\%\&\/\~\+\#])?$',
		'link_relative' => '^([\w\-\.,@\?\^\=\%\&:\/\~\+\#\s]*[\w\-\@\?\^\=\%\&\/\~\+\#])?$',
		'email' => '^((\"[^\"\f\n\r\t\v\b]+\")|([\w\!\#\$\%\&\'\*\+\-\~\/\^\`\|\{\}]+(\.[\w\!\#\$\%\&\'\*\+\-\~\/\^\`\|\{\}\.]+)*))@((\[(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))\])|(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))|((([A-Za-z0-9\-_])+\.)+[A-Za-z\-]+))$',
		'date_mmddyyy' => '((^0?[1-9]|^1[0-2])\/(0?[1-9]|[1-2][0-9]|3[0-1])\/(19|20)[0-9][0-9])$',
		'date_ddmmyyy' => '((^0?[1-9]|^[1-2][0-9]|^3[0-1])\/(0?[1-9]|1[0-2])\/(19|20)[0-9][0-9])$',
		'date_hhii' => '^((([0-1][0-9])|(2[0-3])):([0-5][0-9])){%d,%d}$',
		'paragraph' => '^[\w\W]{%d,%d}$',
	    'isbn' => '^ISBN\x20(?=.{13}$)\d{1,5}([- ])\d{1,7}\1\d{1,6}\1(\d|X)$',
		'postal_uk' => '^((([A-PR-UWYZ])([0-9][0-9A-HJKS-UW]?))|(([A-PR-UWYZ][A-HK-Y])([0-9][0-9ABEHMNPRV-Y]?))\s{0,2}(([0-9])([ABD-HJLNP-UW-Z])([ABD-HJLNP-UW-Z])))|(((GI)(R))\s{0,2}((0)(A)(A)))$',
		'postal_us' => '^\d{5}-\d{4}|\d{5}|[A-Z]\d[A-Z] \d[A-Z]\d$',
		'boolean' => '^(true|false|Y|N|y|n|1|0|Yes|No|TRUE|FALSE|True|False)$',
		'file' => '',
		'anything'=>'',
		
		'name' => '(^[a-zA-Z\p{L}\.\'\s\&\-\\\\\/\-]{%d,%d}$)',
		'address' => '(^[a-zA-Z0-9\p{L}\s&,\.\'\+\"()\:\\\\\/\-]{%d,%d}$)',
		'postcode' => '(^[0-9a-zA-Z\s]+$)',

		'time' => '^(([0-1][0-9]|2[0-3])\:([0-5][0-9]))$');

	var $ExpErrors = array(
		'username' => '%s is not a valid username. Must be a valid email address.',
		'password' => '%s is not a valid password. Must contain %s alpha numeric characters. Pass phrases are permitted in addition to @*#%%.!_- and space characters',
		'telephone' => '%s must contain numeric characters and spaces only.',
		'alpha' => '%s must contain %s alphabetic characters with no spaces.',
		'alpha_numeric'	=> '%s must contain %s alpha numeric characters.',
		'numeric_unsigned' => '%s must contain %s numeric characters.',
		'numeric_signed' => '%s must contain %s numeric characters.',
		'float' => '%s must be a number.',
		'link' => '%s must be a valid http, https or ftp address.',
		'link_relative' => '%s must be a valid relative link.',
		'email' => '%s must be a valid email address',
		'date_mmddyyy' => '%s must be in the format mm/dd/yyyy',
		'date_ddmmyyy' => '%s must be in the format dd/mm/yyyy',
		'date_hhii' => '%s must be in the format hh:mm',
		'paragraph' => '%s requires %s characters.',
	    'isbn' => '%s is not a valid ISBN number.',
		'postal_uk' => '%s is not a valid UK post code.',
		'postal_us' => '%s is not a valid US zip code.',
		'boolean' => '%s must be a Yes or No answer.',
		'file' => '%s does not exist.',
		'anything' => '%s is required.',
		'postcode' => '%s is not a valid. If your country does not have a postal code please enter 0.',
		
		'name'	=> "%s must only contain %s alphabetic characters. The following characters  /\&.-' are also permitted including spaces. ",
		'address'	=> "%s must only contain %s alpha numeric characters including spaces. The following characters  /\&,.-+:()' are also permitted including spaces. ",


		'time' => '%s is not a valid time.');

	var $HTMLArray = array("input" => "<input type=\"%s\" name=\"%s\" value=\"%s\" id=\"%s\" %s />\n",
						   "inputbasic" => "<input type=\"%s\" name=\"%s\" value=\"%s\" %s />\n",
						   "textarea" => "<textarea name=\"%s\" id=\"%s\" %s>%s</textarea>\n",
						   "select" => "<select name=\"%s\" id=\"%s\" %s>%s</select>\n",
						   "selectmultiple" => "<select name=\"%s[]\" id=\"%s\" multiple=\"multiple\" %s>%s</select>\n",
						   "option" => "<option value=\"%s\" %s>%s</option>\n");

	function Form($action, $method='post', $id='form1', $enc='application/x-www-form-urlencoded'){
		$this->Action = $action;
		$this->EncType = $enc;
		$this->Method = strtolower($method);
		$this->ID = $id;
		$this->TabIndex = 0;
		$this->InputFields = array();
		$this->Valid = true;
		$this->ShowIcon = true;
		$this->Errors = array();
		$this->DisableAutocomplete = false;
		$this->OnSubmitFunction = array();
		$this->Icons = array('valid' => sprintf('<img src="%simages/icon_tick_1.gif" width="16" height="16" align="absmiddle" />', $GLOBALS['IGNITION_ROOT']),
							 'invalid' => sprintf('<img src="%simages/icon_invalid_1.gif" width="16" height="16" align="absmiddle" />', $GLOBALS['IGNITION_ROOT']),
							 'required' => '<span class="required"><sup>*</sup></span>',
							 'optional' => '');
	}

	function OnSubmit($str){
		$this->OnSubmitFunction[] = $str;
	}

	function Open(){
		$onSubmit = '';
		if(count($this->OnSubmitFunction) > 0){
			$onSubmit = 'onSubmit="' . implode('; ', $this->OnSubmitFunction) . '"';
		}
		return sprintf("<form action=\"%s\" method=\"%s\" id=\"%s\" name=\"%s\" %s%s%s>\n", $this->Action, strtolower($this->Method), $this->ID, $this->ID, !empty($this->EncType) ? sprintf('enctype="%s" ', $this->EncType) : '', (($this->DisableAutocomplete) ? 'autocomplete="off" ' : ''), $onSubmit);
	}

	function Close(){
		return "</form>\n";
	}

	function AddField($id, $label, $htmlType, $value, $validationType, $minLength=2, $maxLength=128, $required=true, $attributes=""){
		$tempArray[$id] = new InputField($id, $label, $htmlType, $value, $validationType, $minLength, $maxLength, $required, $attributes);
		$this->InputFields = array_merge($this->InputFields, $tempArray);

		switch($validationType){
			case 'datetime':
				// AddDate($fieldName, $fieldStr, $rawDatetime='0000-00-00 00:00:00', $minYear=NULL, $maxYear=NULL)
				$this->AddDate($id, $label, $value, $minLength, $maxLength);
				break;
			case 'file':
				$this->EncType = "multipart/form-data";
				break;
		}
	}

	function AddOption($id, $value, $text=NULL, $group=NULL){
		switch(strtolower($this->InputFields[$id]->HtmlType)){
			case 'select':
			case 'selectmultiple':
				$this->InputFields[$id]->Options[] = $value;
				$this->InputFields[$id]->Options[] = $text;
				$this->InputFields[$id]->Options[] = $group;
				return true;
				break;
			case 'radio':
				$this->InputFields[$id]->Options[] = $value;
				$this->InputFields[$id]->Options[] = $text;
				return true;
				break;
			default:
				return false;
				break;
		}
	}

	function AddGroup($id, $group, $text){
		switch(strtolower($this->InputFields[$id]->HtmlType)){
			case 'select':
			case 'selectmultiple':
				$this->InputFields[$id]->Groups[] = $group;
				$this->InputFields[$id]->Groups[] = $text;
				return true;
				break;
			default:
				return false;
				break;
		}
	}

	function GetHTML($id, $option=NULL){
		++$this->TabIndex;
		
		$parts = explode('-', $this->InputFields[$id]->HtmlType);
		
		switch(strtolower($parts[0])){
			case 'text':
			case 'password':
			case 'hidden':
			case 'file':
				$type = isset($parts[1]) ? $parts[1] : '';
				
				switch(strtolower($type)) {
					case 'basic':
						$tempHTML = sprintf($this->HTMLArray['inputbasic'], $parts[0], $this->InputFields[$id]->ID, $this->FormatForInput($this->InputFields[$id]->Value, ENT_QUOTES), $this->GetAttributes($id));
						break;
					case 'array':
						$tempHTML = '';

						foreach($this->InputFields[$id]->Value as $item) {
							$tempHTML .= sprintf("<input type=\"%s\" name=\"%s[]\" value=\"%s\" %s />\n", $parts[0], $this->InputFields[$id]->ID, $item, $this->GetAttributes($id));
						}

						break;
					default:
						$tempHTML = sprintf($this->HTMLArray['input'], $parts[0], $this->InputFields[$id]->ID, $this->FormatForInput($this->InputFields[$id]->Value, ENT_QUOTES), $this->InputFields[$id]->ID, $this->GetAttributes($id));
						break;
				} 

				break;
			case 'select':
				$tempHTML = sprintf($this->HTMLArray['select'], $this->InputFields[$id]->ID, $this->InputFields[$id]->ID, $this->GetAttributes($id), $this->GetSelectOptions($id));
				break;
			case 'selectmultiple':
				$tempHTML = sprintf($this->HTMLArray['selectmultiple'], $this->InputFields[$id]->ID, $this->InputFields[$id]->ID, $this->GetAttributes($id), $this->GetSelectOptions($id));
				break;
			case 'textarea':
				$attributes = $this->GetAttributes($id);
				
				if(stripos($attributes, 'rows=') === false) {
					$attributes .= ' rows="3"';
				}
				
				if(stripos($attributes, 'cols=') === false) {
					$attributes .= ' cols="40"';
				}
				
				$tempHTML = sprintf($this->HTMLArray['textarea'], $this->InputFields[$id]->ID, $this->InputFields[$id]->ID, $attributes, $this->FormatForInput($this->InputFields[$id]->Value, ENT_QUOTES));
				break;
			case 'checkbox':
				if((isset($_REQUEST['action']) && isset($_REQUEST[$this->InputFields[$id]->ID])) || (strtolower($this->InputFields[$id]->Value) == 'y')){
					$this->InputFields[$id]->Attributes .= " checked=\"checked\"";
				}
				$tempHTML = sprintf($this->HTMLArray['input'], $this->InputFields[$id]->HtmlType, $this->InputFields[$id]->ID, "Y", $this->InputFields[$id]->ID, $this->GetAttributes($id));
				break;
			case 'radio':
				if(!is_null($option)){
					$tempID = sprintf("%s_%s", $this->InputFields[$id]->ID, $option);
					if($this->InputFields[$id]->Value == $this->InputFields[$id]->Options[(($option*2)-2)]){
						$tempAttributes = sprintf("%s checked=\"checked\"", $this->InputFields[$id]->Attributes);
					} else {
						$tempAttributes = $this->InputFields[$id]->Attributes;
					}
					$tempHTML = sprintf($this->HTMLArray['input'], $this->InputFields[$id]->HtmlType, $this->InputFields[$id]->ID, $this->InputFields[$id]->Options[($option*2)-2], $tempID, $this->GetAttributes($id, $tempAttributes));
				} else {
					$tempHTML = $this->InputFields[$id]->Label;
				}
				break;
			case 'datetime':
				$tempHTML = $this->GetHTML($id . '_dd') . $this->GetHTML($id . '_mm') . $this->GetHTML($id . '_yyyy');
				break;
		}
		return $tempHTML;
	}

	function GetLabel($id, $option=NULL){
		$tempHTML = "";
		switch(strtolower($this->InputFields[$id]->HtmlType)){
			case 'radio':
				if($option != NULL){
					$tempHTML = sprintf("<label for=\"%s_%s\">%s</label>\n", $this->InputFields[$id]->ID, $option, $this->InputFields[$id]->Options[($option*2)-1]);
				} else {
					$tempHTML = $this->InputFields[$id]->Label;
				}
				break;
			default:
				$tempHTML = sprintf("<label for=\"%s\">%s</label>\n", $this->InputFields[$id]->ID, $this->InputFields[$id]->Label);
				break;
		}
		return $tempHTML;
	}

	function GetIcon($id){
		if($this->InputFields[$id]->Valid){
			return $this->Icons['valid'];
		} elseif(!$this->InputFields[$id]->Valid && !is_null($this->InputFields[$id]->Valid)){
			return $this->Icons['invalid'];
		} elseif(is_null($this->InputFields[$id]->Valid) && ($this->InputFields[$id]->Required)){
			return $this->Icons['required'];
		} elseif(is_null($this->InputFields[$id]->Valid) && (!$this->InputFields[$id]->Required)){
			return $this->Icons['optional'];
		}
	}

	function GetTabIndex(){
		return ++$this->TabIndex;
	}

	function GetValue($id){
		if(strtolower($this->InputFields[$id]->ValidationType) == 'datetime'){
			$this->InputFields[$id]->Value = sprintf("%s-%s-%s 00:00:00",
							$this->InputFields[$id . "_yyyy"]->Value,
							$this->InputFields[$id . "_mm"]->Value,
							$this->InputFields[$id . "_dd"]->Value);
		}

		$value = (($this->InputFields[$id]->HtmlType == 'selectmultiple') || ($this->InputFields[$id]->HtmlType == 'hidden-array')) ? $this->InputFields[$id]->Value : addslashes($this->InputFields[$id]->Value);

		return $value;
	}

	function SetValue($id, $value){
		if(strtolower($this->InputFields[$id]->ValidationType) != 'datetime'){
			$this->InputFields[$id]->Value = $value;
		}
	}

	function Validate($field = null){
		reset($this->InputFields);

		while (!is_null($key = key($this->InputFields))) {
			if(is_null($field) || (!is_null($field) && ($field == $key))) {
				if(strtolower($this->InputFields[$key]->ValidationType) == 'boolean' && $this->InputFields[$key]->Required){
					$val = strtolower($this->InputFields[$key]->Value);
					$this->InputFields[$key]->Valid = ($val == 'y' || $val=='yes' || $val=='true' || $val=='1')?true:false;
					$str = $this->InputFields[$key]->Label . " is required.";
					if(!$this->InputFields[$key]->Valid) $this->AddError($str);
				} elseif(strtolower($this->InputFields[$key]->ValidationType) == 'datetime'){
					$this->InputFields[$key]->Valid = true;
				} elseif(strtolower($this->InputFields[$key]->ValidationType) != 'file'){
					if( $this->InputFields[$key]->Required && ( !isset($this->InputFields[$key]->Value) || (empty($this->InputFields[$key]->Value) && !is_numeric($this->InputFields[$key]->Value) ) || (is_array($this->InputFields[$key]->Value) && (count($this->InputFields[$key]->Value) == 0)) ) ){
						// invalid because a value is required
						$this->InputFields[$key]->Valid = false;
						$this->Valid = false;
						$this->SetError($key);
					} elseif(!$this->InputFields[$key]->Required && (!isset($this->InputFields[$key]->Value) || (empty($this->InputFields[$key]->Value) && !is_numeric($this->InputFields[$key]->Value)) || (is_array($this->InputFields[$key]->Value) && (count($this->InputFields[$key]->Value) == 0)))){
						// valid because no value is required
						$this->InputFields[$key]->Valid = true;
					} else {
						// Check validity against a regular expression
						$specificExp = sprintf($this->RegularExp[$this->InputFields[$key]->ValidationType], $this->InputFields[$key]->MinLength, $this->InputFields[$key]->MaxLength);
						//if(preg_match(sprintf("/%s/", $specificExp), html_entity_decode($this->InputFields[$key]->Value))){
						if(strtolower($this->InputFields[$key]->HtmlType) == "selectmultiple") {
							if(is_array($this->InputFields[$key]->Value)) {
								$invalid = false;

								foreach($this->InputFields[$key]->Value as $value) {
									if(preg_match(sprintf("/%s/iu", $specificExp), $value)){
										$this->InputFields[$key]->Valid = true;
									} else {
										$invalid = true;
									}
								}

								if($invalid) {
									$this->InputFields[$key]->Valid = false;
									$this->Valid = false;
									$this->SetError($key);
								}
							}
						} else {
							if(preg_match(sprintf("/%s/iu", $specificExp), $this->InputFields[$key]->Value)){
								$this->InputFields[$key]->Valid = true;
							} else {
								$this->InputFields[$key]->Valid = false;
								$this->Valid = false;
								$this->SetError($key);
							}
						}
					}
				} elseif(strtolower($this->InputFields[$key]->ValidationType) == 'file'){
					if($this->InputFields[$key]->Required && empty($_FILES[$key]['name'])){
						$this->Valid = false;
						$str = $this->InputFields[$key]->Label . " is a required field.";
						$this->AddError($str);
					}
				}
			}

			next($this->InputFields);
		}

		return $this->Valid;
	}

	function SetError($id){
		if($this->InputFields[$id]->MinLength == $this->InputFields[$id]->MaxLength){
			$tempNum = $this->InputFields[$id]->MinLength;
		} else {
			$tempNum = sprintf("between %d and %d", $this->InputFields[$id]->MinLength, $this->InputFields[$id]->MaxLength);
		}
		$this->Errors[] = sprintf($this->ExpErrors[$this->InputFields[$id]->ValidationType], $this->InputFields[$id]->Label, $tempNum);
	}

	function SetIcons($required='*', $invalid='*', $valid='', $optional=''){
		$this->Icons['valid'] = $valid;
		$this->Icons['invalid'] = $invalid;
		$this->Icons['required'] = $required;
		$this->Icons['optional'] = $optional;
		return true;
	}

	function GetError(){
		$tempHTML = "<ol>";
		for($i=0; $i < count($this->Errors); $i++){
			$tempHTML = sprintf("%s<li>%s</li>\n", $tempHTML, $this->Errors[$i]);
		}
		$tempHTML = sprintf("%s</ol>", $tempHTML);

		$bubble = new Bubble(
							'Please correct the following:',
							$tempHTML,
							(($this->ShowIcon) ? sprintf("<img src=\"%simages/icon_alert_2.gif\" width=\"16\" height=\"16\" align=\"absmiddle\">", $GLOBALS['IGNITION_ROOT']) : ''),
							'error');
		return $bubble->GetHTML();
	}

	function AddError($str, $id=NULL){
		if(!is_null($id)){
			$this->InputFields[$id]->Valid = false;
		}
		$this->Valid = false;
		$this->Errors[] = $str;
	}

	function GetAttributes($id, $attributes=NULL){
		$htmlType = strtolower($this->InputFields[$id]->HtmlType);
		$attributes = ($attributes==NULL)?$this->InputFields[$id]->Attributes:$attributes;

		if(!preg_match("/\btabindex/", $attributes) && ($htmlType != 'hidden')){
			$attributes = sprintf("%s tabindex=\"%s\"", $attributes, $this->TabIndex);
		}
		if(!preg_match("/\bmaxlength/", $attributes) && (($htmlType == 'text') || ($htmlType == 'password'))){
			if(!is_null($this->InputFields[$id]->MaxLength)){
				$attributes = sprintf("%s maxlength=\"%s\"", $attributes, $this->InputFields[$id]->MaxLength);
			}
		}
		return $attributes;
	}

	function GetSelectOptions($id){
		$tempHTML = "";

		for($i=0; $i < count($this->InputFields[$id]->Options); $i+=3){
			if(is_null($this->InputFields[$id]->Options[$i+2])) {
				$selected = '';

				if(strtolower($this->InputFields[$id]->HtmlType) == 'selectmultiple') {
					foreach($this->InputFields[$id]->Value as $value) {
						if($this->InputFields[$id]->Options[$i] == $value) {
							$selected = 'selected="selected"';
							break;
						}
					}
				} else {
					$selected = ($this->InputFields[$id]->Options[$i] == $this->InputFields[$id]->Value) ? 'selected="selected"' : '';
				}

				$thisOptionHTML = sprintf($this->HTMLArray['option'], $this->InputFields[$id]->Options[$i], $selected, $this->InputFields[$id]->Options[$i+1]);
				$tempHTML .= $thisOptionHTML;
			}
		}
		
		for($j=0; $j < count($this->InputFields[$id]->Groups); $j+=2){
			$children = 0;

			for($i=0; $i < count($this->InputFields[$id]->Options); $i+=3){
				if($this->InputFields[$id]->Options[$i+2] == $this->InputFields[$id]->Groups[$j]) {
					$children++;
				}
			}

			if($children > 0) {
				$tempHTML .= sprintf('<optgroup label="%s">', $this->InputFields[$id]->Groups[$j+1]);

				for($i=0; $i < count($this->InputFields[$id]->Options); $i+=3){
					$selected = '';

					if(strtolower($this->InputFields[$id]->HtmlType) == 'selectmultiple') {
						foreach($this->InputFields[$id]->Value as $value) {
							if($this->InputFields[$id]->Options[$i] == $value) {
								$selected = 'selected="selected"';
								break;
							}
						}
					} else {
						$selected = ($this->InputFields[$id]->Options[$i] == $this->InputFields[$id]->Value) ? 'selected="selected"' : '';
					}

					if($this->InputFields[$id]->Options[$i+2] == $this->InputFields[$id]->Groups[$j]) {
						$thisOptionHTML = sprintf($this->HTMLArray['option'], $this->InputFields[$id]->Options[$i], $selected, $this->InputFields[$id]->Options[$i+1]);
						$tempHTML .= $thisOptionHTML;
					}
				}

				$tempHTML .= sprintf('</optgroup>');
			}
		}
		return $tempHTML;
	}

	function AddDate($fieldName, $fieldStr, $rawDatetime='0000-00-00 00:00:00', $minYear=NULL, $maxYear=NULL){
		$defaultDatetime = '0000-00-00 00:00:00';
		// Day fields
		$this->AddField($fieldName . '_dd', $fieldStr . ' Days', 'select', cDatetime($rawDatetime, 'd'), 'numeric_unsigned', 2, 2);
		$this->AddOption($fieldName . '_dd', '00', 'Day');
		for($i=1; $i <= 31; $i++){
			$temp_i = ($i < 10)? "0" . $i:$i;
			$this->AddOption($fieldName . '_dd', $temp_i, $temp_i);
		}

		// Month Fields
		$this->AddField($fieldName . '_mm', $fieldStr . ' Months', 'select', cDatetime($rawDatetime, 'm'), 'numeric_unsigned', 2, 2);
		$this->AddOption($fieldName . '_mm', '00', 'Month');
		for($i=1; $i <= 12; $i++){
			$temp_i = ($i < 10)? "0" . $i:$i;
			$this->AddOption($fieldName . '_mm', $temp_i, $temp_i);
		}

		// Year fields
		if($minYear == NULL && $maxYear == NULL){
			$this->AddField($fieldName . '_yyyy', $fieldStr . ' Year', 'text', cDatetime($rawDatetime, 'y'), 'numeric_unsigned', 4, 4);
		} else {
			/*if(!isDatetime($rawDatetime)){
				$minYear = (cDatetime(getDatetime(), 'y') - 1);
				$maxYear = ($minYear + 11);
			}*/
			$this->AddField($fieldName . '_yyyy', $fieldStr . ' Year', 'select', cDatetime($rawDatetime, 'y'), 'numeric_unsigned', 4, 4);
			$this->AddOption($fieldName . '_yyyy', '0000', 'Year');
			for($i=$minYear; $i <= $maxYear; $i++){
				$temp_i = ($i < 10)? "0" . $i:$i;
				$this->AddOption($fieldName . '_yyyy', $temp_i, $temp_i);
			}
		}

		// time fields
		// still to do
	}

	function FormatForInput($value){
		return htmlspecialchars(stripslashes($value), ENT_QUOTES);
	}
}