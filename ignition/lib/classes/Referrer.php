<?php
class Referrer {
	var $Url;
	var $QueryString;
	var $SearchString;
	var $FullUrl;
	var $Domain;

	function Referrer($url) {
		$this->FullUrl = $url;
		
		$traceSplitter = ' : ';
		
		if(($pos = stripos($this->FullUrl, $traceSplitter)) !== false) {
			$this->FullUrl = trim(substr($this->FullUrl, $pos + strlen($traceSplitter)));
		}
			
		$this->SetUrl($this->FullUrl);
	}

	function SetUrl($url) {		
		$splitUrl = explode('?', $url);
		
		if(count($splitUrl) > 1){
			$this->QueryString = $splitUrl[1];
		}

		$this->Url = $this->RemoveProtocols($splitUrl[0]);
		$this->Domain = $this->GetDomain($this->Url);

		$this->GetSearchString($url);
	}

	function RemoveProtocols($url) {
		$protocols = array();
		$protocols[] = 'http';
		$protocols[] = 'https';
		
		foreach($protocols as $protocol) {
			$url = str_replace($protocol.'://', '', $url);
		}
		
		return $url;
	}
	
	function GetDomain($url) {
		if(($pos = stripos($url, '/')) !== false) {
			$url = substr($url, 0, $pos);
		}

		return $url;
	}
	
	function GetSearchString($searchString) {
		$queryChecks = array();
		$queryChecks[] = array('q=', '&');
		$queryChecks[] = array('search=', '&');
		$queryChecks[] = array('p=', '&');
		$queryChecks[] = array('query=', '&');
		$queryChecks[] = array('qry=', '&');
		$queryChecks[] = array('qt=', '&');
		$queryChecks[] = array('terms=', '&');
		$queryChecks[] = array('ix=', '&');
		$queryChecks[] = array('keywords=', '&');
		$queryChecks[] = array('searchfor=', '&');
		$queryChecks[] = array('ask=', '&');
		
		$preQueryCheck = array();
		$preQueryCheck[] = '?';
		$preQueryCheck[] = '&';

		foreach($queryChecks as $check) {
			foreach($preQueryCheck as $preCheck) {
				if(stristr($searchString, $preCheck.$check[0]) !== false) {
					$this->SearchString = $this->ExtractSearchString($searchString, $preCheck.$check[0], $check[1]);
					
					return true;		
				}
			}
		}
		
		$urlChecks = array();
		$urlChecks[] = array('/web/', '/');

		foreach($urlChecks as $check) {
			if(stristr($searchString, $check[0])) {
				$this->SearchString = $this->ExtractSearchString($searchString, $check[0], $check[1]);
				
				return true;
			}
		}

		return false;
	}

	function ExtractSearchString($searchString, $var, $end) {
		$searchString = substr($searchString, stripos($searchString, $var) + strlen($var));

		if(stristr($searchString, $end)) {
			$searchString = substr($searchString, 0, stripos($searchString, $end));
		}
				
		return trim(strtolower(str_replace('+', ' ', urldecode($searchString))));
	}
}
?>