<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Address.php");

	class PostcodeAnywhere{
		var $Licence;
		var $Account;
		var $URL;
		var $Postcode;
		var $Error;
		var $Address;
		var $XMLData;
		var $IsActive;
		var $XMLParser;
		var $Query;
		var $FlagMultipleAddressError;
		var $Data;

		function PostcodeAnywhere(){
			$this->Licence = $GLOBALS['POSTCODEANYWHERE_LICENCE'];
			$this->Account = $GLOBALS['POSTCODEANYWHERE_ACCOUNT'];
			$this->URL = sprintf('http://services.postcodeanywhere.co.uk/xml.asp?account_code=%s&license_code=%s',
								$this->Account,
								$this->Licence);
			$this->Error = array();
			$this->ResetAddress();
			$this->IsActive = true;
			$this->Data = array();
		}

		function ResetAddress(){
			$this->Address = NULL;
			$this->Address = new Address;
		}

		function GetAddress($building, $postcode){
			$building = $this->CleanBuildingName($building);
			$this->Postcode = $postcode;
			$this->ResetAddress();

			$this->XMLParser = xml_parser_create();
			xml_set_object ($this->XMLParser, $this);
			xml_set_element_handler ($this->XMLParser, 'StartElement', 'EndElement' );
			xml_set_character_data_handler ($this->XMLParser, 'TagContent');

			// create query string to get XML result from PostcodeAnywhere
			$this->Query  = $this->BuildQuery($building, $this->Postcode);
			/*echo $this->Query;
			exit;*/
			$ch = curl_init($this->Query);
			$fp = @fopen($GLOBALS["DIR_WS_ADMIN"]."temp/postcodeanywhere.xml", "w");
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_exec($ch);
			curl_close($ch);
			fclose($fp);
			$file = $GLOBALS["DIR_WS_ADMIN"]."temp/postcodeanywhere.xml";
			$fp = fopen($file, "r");

			/*
			// open xml document, where fp is a file pointer
			if (!($fp = fopen($this->Query, "r") ) ) {
				// failed to open remote xml document, switch to alternate method
				$this->IsActive = false;
			}
		  	*/
		  	// process xml document
		  	while ($this->XMLData = fread($fp, 8192)) {
				if (!xml_parse($this->XMLParser, $this->XMLData, feof($fp))) {
			  		// failed to process remote xml document, switch to alternate method
			  		$this->IsActive = false;
			  	}
			}
		  	xml_parser_free($this->XMLParser);

			return (count($this->Error) > 0)? false : true;
		}

		function StartElement($parser, $name, $attributes){
			$this->Data[$name] = $attributes;
			$this->FlagMultipleAddressError = false;
			  switch ($name) {
				case "SCHEMA":
				  if ($attributes['ITEMS'] == "2") {
					$this->Error[] = "Fault, if problem persists contact support";
					}
				  break;

				case "DATA":
				  if ($attributes['ITEMS'] == "0")
					$this->Error[] = "Error, incorrect address, please try again";

				  if ($attributes['ITEMS'] > "1")
					$this->FlagMultipleAddressError  = true;
				  break;

				case "ITEM":
				  if ($this->FlagMultipleAddressError)
					$this->Error[] = ("Error, PostcodeAnywhere multiple postcode error; ".$attributes['LINE1'].", ".$attributes['POSTCODE']."<br />");

				  if (isset($attributes['LINE1']))
					$this->Address->Line2 = $attributes['LINE1'];

				  if (isset($attributes['LINE2']))
					$this->Address->Line3 = $attributes['LINE2'];

				  if (isset($attributes['LINE3']))
					$this->Address->Line4 = $attributes['LINE3'];

				  if (isset($attributes['LINE4']))
					$this->Address->Line5 = $attributes['LINE4'];

				  if (isset($attributes['POST_TOWN']))
					$this->Address->City = $attributes['POST_TOWN'];

				  if (isset($attributes['COUNTY'])) {
					$this->Address->Region->Name = $attributes['COUNTY'];
					$this->Address->Region->GetIDFromString($attributes['COUNTY']);
					$this->Address->Country->Get($this->Address->Region->CountryID);
				  }

				  if (isset($attributes['POSTCODE']))
					$this->Address->Zip = $attributes['POSTCODE'];

				  break;
				}
		}
		function EndElement($parser, $name){
		}
		function TagContent($parser, $value){
		}

		function BuildQuery($building, $postcode){
			$query = $this->URL . "&action=fetch&style=raw";
			$query .= "&building=" . urlencode($building);
			$query .= "&postcode=" . urlencode($postcode);
			return $query;
		}

		function CleanBuildingName($building){
			$building = str_replace("/", " ", $building);
			$building = trim($building);
			// if the first part is a number, extract it e.g. "4-8" becomes "4"
			$tmp_name = $building;
			$tmp_name = str_replace("-", " ", $tmp_name);
			$tmp_name = str_replace(",", " ", $tmp_name);
			$tmp_name = trim($tmp_name);
			if (!$tmp_name) {
				return $building;
			}
			$tmp = explode(" ", $tmp_name);
			if(isset($tmp[0])) {
				$tmp = trim($tmp[0]);
				if (is_numeric($tmp[0]))$building = $tmp;
			}
			return $building;
		}

		function ValidPostcode($postcode){
		}
	}
?>