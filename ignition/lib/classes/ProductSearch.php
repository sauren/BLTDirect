<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class ProductSearch{
	var $Query;
	var $String;
	var $Keywords;
	var $Results;
	var $FullTextSearch;
	var $FullTextBoolean;
	var $Limit = 50;
	var $Redirect;
	var $JoinInfo;
	var $JoinConditions;
	var $Order;

	function ProductSearch($string='', $redirect='product_profile.php?pid=',$joinInfo = '',$joinCon ='',$order='desc', $quickSearch=true){
		$this->Keywords = array();
		$this->String = $string;
		$this->Redirect = $redirect;
		$this->JoinInfo = $joinInfo;
		$this->JoinConditions = $joinCon;
		$this->Order = $order;
		$this->ParseString();
		//$this->Search();
		
		if($quickSearch) {
			if(count($this->Keywords) == 1){
				$hasResults = $this->QuickSearch();
			}
		}
	}

	function ParseString(){
		$this->Keywords = array();
		$this->String = trim(strtolower(stripslashes($this->String)));
		
		if(!empty($this->String)) {
			// Break up $this->String on whitespace, quoted strings will be reconstructed later
			$pieces = preg_split ('/\s+/', $this->String);
			
			$tmpstring = '';
			$flag = '';
	
			for ($k=0; $k<count($pieces); $k++) {
				// Check for opening parenthis
				while (substr($pieces[$k], 0, 1) == '(') {
					$this->Keywords[] = '(';
					if (strlen($pieces[$k]) > 1) {
						$pieces[$k] = substr($pieces[$k], 1);
					} else {
						$pieces[$k] = '';
					}
				} // End While
	
				$post_objects = array();
	
				// Check for closing parenthis
				while (substr($pieces[$k], -1) == ')')  {
					$post_objects[] = ')';
					if (strlen($pieces[$k]) > 1) {
						$pieces[$k] = substr($pieces[$k], 0, -1);
					} else {
						$pieces[$k] = '';
					}
				}
	
				// Check individual words
				if ((substr($pieces[$k], -1) != '"') && (substr($pieces[$k], 0, 1) != '"') ) {
					$this->Keywords[] = trim($pieces[$k]);
	
					for ($j=0; $j<count($post_objects); $j++) {
						$this->Keywords[] = $post_objects[$j];
					}
				} else {
					/* This means that the $piece is either the beginning or the end of a string.
					Go through each of the $pieces and stick them together until we get to the
					end of the string or run out of pieces.
					*/
	
					// Add this word to the $tmpstring, starting the $tmpstring
					$tmpstring = trim(ereg_replace('"', ' ', $pieces[$k]));
	
					// Check for one possible exception to the rule. That there is a single quoted word.
					if (substr($pieces[$k], -1 ) == '"') {
						// Turn the flag off for future iterations
						$flag = 'off';
	
						$this->Keywords[] = trim($pieces[$k]);
	
						for ($j=0; $j<count($post_objects); $j++) {
							$this->Keywords[] = $post_objects[$j];
						}
	
						unset($tmpstring);
	
						// Stop looking for the end of the string and move onto the next word.
						continue;
					}
	
					// Otherwise, turn on the flag to indicate no quotes have been found attached to this word in the string.
					$flag = 'on';
	
					// Move on to the next word
					$k++;
	
					// Keep reading until the end of the string as long as the $flag is on
	
					while ( ($flag == 'on') && ($k < count($pieces)) ) {
						while (substr($pieces[$k], -1) == ')') {
							$post_objects[] = ')';
							if (strlen($pieces[$k]) > 1) {
								$pieces[$k] = substr($pieces[$k], 0, -1);
							} else {
								$pieces[$k] = '';
							}
						}
	
						// If the word doesn't end in double quotes, append it to the $tmpstring.
						if (substr($pieces[$k], -1) != '"') {
							// Tack this word onto the current string entity
							$tmpstring .= ' ' . $pieces[$k];
	
							// Move on to the next word
							$k++;
							continue;
						} else {
							/* If the $piece ends in double quotes, strip the double quotes, tack the
							$piece onto the tail of the string, push the $tmpstring onto the $haves,
							kill the $tmpstring, turn the $flag "off", and return.
							*/
							$tmpstring .= ' ' . trim(ereg_replace('"', ' ', $pieces[$k]));
	
							// Push the $tmpstring onto the array of stuff to search for
							$this->Keywords[] = trim($tmpstring);
	
							for ($j=0; $j<count($post_objects); $j++) {
								$this->Keywords[] = $post_objects[$j];
							}
	
							unset($tmpstring);
	
							// Turn off the flag to exit the loop
							$flag = 'off';
						}
					}
				}
			}
	
			// add default logical operators if needed
			$temp = array();
			for($i=0; $i<(count($this->Keywords)-1); $i++) {
				$temp[] = $this->Keywords[$i];
				if ( ($this->Keywords[$i] != 'and') &&
				($this->Keywords[$i] != 'or') &&
				($this->Keywords[$i] != '(') &&
				($this->Keywords[$i+1] != 'and') &&
				($this->Keywords[$i+1] != 'or') &&
				($this->Keywords[$i+1] != ')') ) {
					$temp[] = 'and';
				}
			}
			$temp[] = $this->Keywords[$i];
			$this->Keywords = $temp;
	
			$keyword_count = 0;
			$operator_count = 0;
			$balance = 0;
			for($i=0; $i<count($this->Keywords); $i++) {
				if ($this->Keywords[$i] == '(') $balance --;
				if ($this->Keywords[$i] == ')') $balance ++;
				if ( ($this->Keywords[$i] == 'and') || ($this->Keywords[$i] == 'or') ) {
					$operator_count ++;
				} elseif ( ($this->Keywords[$i]) && ($this->Keywords[$i] != '(') && ($this->Keywords[$i] != ')') ) {
					$keyword_count ++;
				}
			}
	
			if ( ($operator_count < $keyword_count) && ($balance == 0) ) {
				return true;
			} else {
				return false;
			}
		}
		
		return true;
	}

	function Search(){
		$hasResults = false;
		if(count($this->Keywords) == 1 ){
			$hasResults = $this->QuickSearch();
		}
		if(!$hasResults){
			$hasResults = $this->DetailedSearch();
		}
		return $hasResults;
	}

	function DetailedSearch(){
		$this->PrepareSQL();

		$this->Results = new DataQuery($this->Query);
		if($this->Results->TotalRows == 0) $this->Results->Disconnect();
	}

	function PrepareSQL(){
		$tempQuery = "";
		$this->FullTextSearch = "";
		$this->FullTextBoolean = "";

		$this->Query = "select p.Product_ID,
				MATCH(p.Product_Title, p.Product_Codes, p.Product_Description, p.Meta_Title, p.Meta_Description, p.Meta_Keywords, p.Cache_Specs) against('%s') as score
				from product as p %s
				where
				p.Is_Active='Y' AND p.Is_Demo_Product='N' AND
				  ((Now() Between p.Sales_Start and p.Sales_End) or
					(p.Sales_Start='0000-00-00 00:00:00' and p.Sales_End='0000-00-00 00:00:00') or
					(p.Sales_Start='0000-00-00 00:00:00' and p.Sales_End>Now()) or
					(p.Sales_Start<=Now() and p.Sales_End='0000-00-00 00:00:00')
				  ) and
				(%s) %s";

		$flag = "+";

		$units = array('watts', 'volts', 'meters',
		'watt', 'volt', 'metres', 'metres', 'meter', 'kg', 'lb', 'kilogram', 'kilograms', 'gram',
		'grams', 'oz', 'kelvin', 'ohm', 'kelvins', 'ohms', 'dpi', 'kb', 'mb', 'gb', 'kbps', 'mbps', 'gbps',
		'second', 'seconds', 'amps', 'amp', 'ampere', 'amperes', 'mole', 'moles', 'candela', 'n', 'newton', 'newton',
		'joule', 'joules', 'j', 'lumen', 'lumens', 'hertz', 'hz', 'lux', 'minute', 'min', 'minutes', 'hour', 'hours', 'hr',
		'day', 'days', 'yr', 'pa', 'p.a', 'p.a.', 'an', 'annum', 'year', 'years', 'km', 'kilometre', 'kilometres', 'kilometer', 'kilometers', 's', 'cd',
		'a', 'mol', 'pascal', 'pa', 'pascals', 'v', 'farad', 'f', 'farads', 'siemens', 'weber', 'wb', 'tesla', 'teslas', 't', 'henry', 'h',
		'degree', 'degrees', 'deg', 'lm', 'lx', 'gray', 'gy', 'katal', 'kat', 'gal', 'gals', 'gallon', 'gallons', 'pint', 'pints',
		'feet', 'ft', 'foot', 'pound', 'pounds', 'yard', 'yards', 'inch', 'inches', 'ounce', 'ounces', 'ltr', 'litre', 'liter', 'litres', 'liters',
		'pounds', 'psi', 'furlongs', 'furlong', 'mm', 'millimetres', 'millimetre', 'faranheit', 'c', 'decibel', 'db');
		// go through each keyword entry
		for($i=0; $i<count($this->Keywords); $i++){

			if($i>0 && in_array(strtolower($this->Keywords[$i]), $units) && is_numeric($this->Keywords[$i-2])){
				// do something
				$this->Keywords[$i] = $this->Keywords[$i-2] . ' ' . $this->Keywords[$i];
			}

			switch($this->Keywords[$i]){
				case 'and':
					$flag = "+";
					$tempQuery .= "and ";
					break;
				case 'or':
					$flag = "<";
					$tempQuery .= "or ";
					break;
				case '(':
					$this->FullTextBoolean .= $flag . $this->Keywords[$i] . ' ';
					$flag = "+";
					$tempQuery .= "(";
					break;
				case ')':
					$this->FullTextBoolean .= $this->Keywords[$i] . ' ';
					$tempQuery .= ")";
					break;
				default:
					/*if(stristr($this->Keywords[$i], ' ') === TRUE){
					// enclose string
					// append string
					$this->FullTextSearch .= '"' . $this->Keywords[$i] . '"';
					$this->FullTextBoolean .= $flag . '"' . $this->Keywords[$i] . '" ';
					} else {
					// append string
					$this->FullTextSearch .= $this->Keywords[$i] . ' ';
					$this->FullTextBoolean .= $flag . $this->Keywords[$i] . ' ';
					}*/
					$this->Keywords[$i] = mysql_real_escape_string($this->Keywords[$i]);
					$this->FullTextSearch .= '"' . $this->Keywords[$i] . '" ';
					$this->FullTextBoolean .= $flag . '"' . $this->Keywords[$i] . '" ';
					$tempQuery .= "
						(Product_Title like '% {$this->Keywords[$i]}%' or
							Product_Title like '{$this->Keywords[$i]}%' or
							SKU like '{$this->Keywords[$i]}%' or
							Product_Description like '{$this->Keywords[$i]}%' or
							Product_Description like '% {$this->Keywords[$i]}%' or
							Meta_Keywords like '{$this->Keywords[$i]}%' or
							Meta_Keywords like '%,{$this->Keywords[$i]}%' or
							Meta_Keywords like '%, {$this->Keywords[$i]}%' or
							Cache_Specs like '{$this->Keywords[$i]}%' or
							Cache_Specs like '%={$this->Keywords[$i]}%' or
							Cache_Specs like '%;{$this->Keywords[$i]}%') ";

					/* $tempQuery .= "(Product_Title REGEXP '^({$this->Keywords[$i]}.*)|(.*([[:space:]]{$this->Keywords[$i]}).*)|(.*[\:\;]{$this->Keywords[$i]}.*)$' or
					Product_Description REGEXP '^({$this->Keywords[$i]}.*)|(.*([[:space:]]{$this->Keywords[$i]}).*)|(.*[\:\;]{$this->Keywords[$i]}.*)$' or
					Meta_Keywords REGEXP '^({$this->Keywords[$i]}.*)|(.*([[:space:]]{$this->Keywords[$i]}).*)|(.*[\:\;]{$this->Keywords[$i]}.*)$' or
					Cache_Specs REGEXP '^({$this->Keywords[$i]}.*)|(.*([[:space:]]{$this->Keywords[$i]}).*)|(.*[\:\;]{$this->Keywords[$i]}.*)$')";

					$tempQuery .= "(Product_Title REGEXP '^({$this->Keywords[$i]}.*)|(.*([[:space:]]{$this->Keywords[$i]}).*)|(.*[\:\;]{$this->Keywords[$i]}.*)$' or
					Product_Description REGEXP '^({$this->Keywords[$i]}.*)|(.*([[:space:]]{$this->Keywords[$i]}).*)|(.*[\:\;]{$this->Keywords[$i]}.*)$' or
					Meta_Title REGEXP '^({$this->Keywords[$i]}.*)|(.*([[:space:]]{$this->Keywords[$i]}).*)|(.*[\:\;]{$this->Keywords[$i]}.*)$' or
					Meta_Description REGEXP '^({$this->Keywords[$i]}.*)|(.*([[:space:]]{$this->Keywords[$i]}).*)|(.*[\:\;]{$this->Keywords[$i]}.*)$' or
					Meta_Keywords REGEXP '^({$this->Keywords[$i]}.*)|(.*([[:space:]]{$this->Keywords[$i]}).*)|(.*[\:\;]{$this->Keywords[$i]}.*)$' or
					Cache_Specs REGEXP '^({$this->Keywords[$i]}.*)|(.*([[:space:]]{$this->Keywords[$i]}).*)|(.*[\:\;]{$this->Keywords[$i]}.*)$')";
					*/
					break;
			}
		}

		$this->Query = sprintf($this->Query, trim($this->FullTextSearch),$this->JoinInfo, trim($tempQuery),$this->JoinConditions,$this->Order, $this->Limit);
	}

	// Quick Search is based on quickfind code or sku number
	// returns the number of results.
	function QuickSearch(){
		$sql = '';
		$term = addslashes($this->Keywords[0]);
		$term2 = str_replace($GLOBALS['PRODUCT_PREFIX'], '', strtoupper($term));

		if(is_numeric($term2)){
			$sql = "select * from product where Is_Active='Y' AND Is_Demo_Product='N' AND Product_ID={$term2}";
		} else {
			$sql = "select * from product where Is_Active='Y' AND Is_Demo_Product='N' AND SKU like '%{$term}%'";
		}
		$this->Query = $sql;

		$this->Results = new DataQuery($this->Query);
		if($this->Results->TotalRows == 1){
			// one match only - go to it
			$pid = $this->Results->Row['Product_ID'];
			$this->Results->Disconnect();
			if(isset($_REQUEST['serve']) && strtolower($_REQUEST['serve']) == 'pop'){
				redirect('Location: ' . $_SERVER['PHP_SELF'] . '?action=use&pid=' . $pid);
			} else {
				redirect("Location: " . $this->Redirect . $pid);
			}
		} elseif($this->Results->TotalRows > 1){
			// more than one match - show each
			//$this->Results->Disconnect();
			return true;
		} elseif(empty($this->Results->TotalRows)){
			// no matches - reset search
			$this->Results->Disconnect();
			$this->Results = NULL;
			return false;
		}
	}
}