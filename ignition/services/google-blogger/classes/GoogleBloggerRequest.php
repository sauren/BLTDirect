<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Article.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/XmlParser.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/XmlBuilder.php');

class GoogleBloggerRequest {
	private $authKey;
	private $authenticated;
	private $blogId;
	private $feedItem;
	private $feedBatch;
	private $xmlRoot;
	private	$xmlData;
	private $expectedRoot;

	public function __construct() {
		$this->authenticated = false;
	}

	public function isAuthenticated() {
		return $this->authenticated;
	}
	
	public function login() {
		$curlSession = curl_init();

		curl_setopt($curlSession, CURLOPT_URL, 'https://www.google.com/accounts/ClientLogin');
		curl_setopt($curlSession, CURLOPT_HEADER, 0);
		curl_setopt($curlSession, CURLOPT_POST, 1);
		curl_setopt($curlSession, CURLOPT_POSTFIELDS, 'accountType=HOSTED_OR_GOOGLE&Email=advertising@bltdirect.com&Passwd=laptop5&service=blogger&source=EllwoodElectrical-Ignition-1.0');
		curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curlSession, CURLOPT_TIMEOUT, 60);
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, 1);
		
		$errorNo = curl_errno($curlSession);
		
		if($errorNo > 0) {
			$error = curl_error($curlSession);
		} else {
			$response = curl_exec($curlSession);
		}

		curl_close($curlSession);
		
		if($errorNo == 0) {
			$queryStrings = explode("\n", $response);

			foreach($queryStrings as $queryString) {
				$items = explode('=', $queryString);
				
				if(count($items) == 2) {
					if(stristr('auth', $items[0])) {
						$this->authKey = $items[1];
						$this->authenticated = true;
						
						break;
					}
				}
			}

			return true;
		}

		return false;
	}
	
	private function request($url, $xml) {
		$data = '<?xml version="1.0" encoding="UTF-8"?>';
		$data .= "\n";
		$data .= $xml;

		$curlSession = curl_init();
//GData-Version: 2
		curl_setopt($curlSession, CURLOPT_URL, $url);
		curl_setopt($curlSession, CURLOPT_HEADER, 0);
		curl_setopt($curlSession, CURLOPT_HTTPHEADER, array('Authorization: GoogleLogin auth=' . $this->authKey, 'Content-Type: application/atom+xml'));
		curl_setopt($curlSession, CURLOPT_POST, 1);
		curl_setopt($curlSession, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curlSession, CURLOPT_TIMEOUT, 1800);
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, 1);
		
		$errorNo = curl_errno($curlSession);

		if($errorNo > 0) {
			$error = curl_error($curlSession);
		} else {
			$response = curl_exec($curlSession);
		}

		curl_close($curlSession);

		if($errorNo == 0) {
			if($this->parseXml($response)) {
				if(stristr($this->xmlRoot, $this->expectedRoot)) {
					return true;
				}
			}
		}
		
		return false;
	}
	
	private function parseXml($xml=null) {
		$this->xmlRoot = null;
		$this->xmlData = null;
		
		if(!is_null($xml) && !empty($xml)) {
			$xmlParser = new XmlParser($xml);
			
	        $this->xmlRoot = $xmlParser->GetRoot();
	        $this->xmlData = $xmlParser->GetData();
	        
	        return true;
		}
		
		return false;
	}
	
	private function getXmlEntry($name) {
		$attributes = array();
		$attributes['xmlns'] = 'http://www.w3.org/2005/Atom';
		
		return new XmlElement($name, null, $attributes);
	}
	
	public function insertItem($articleId) {
		$this->expectedRoot = 'entry';
		
		$article = new Article();
		
		if($article->Get($articleId)) {
			$xml = $this->getXmlEntry('entry');
			$xml->AddChildElement(new XmlElement('title', null, array('type' => 'text'), $article->Name));
			$xml->AddChildElement(new XmlElement('content', null, array('type' => 'xhtml'), $article->Description));
			
			return $this->request($this->feedItem, $xml->ToString());
		}
		
		return false;
	}
}