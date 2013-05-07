<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');

class SageTemplate {
	protected $findReplace;
	protected $template;
	public $parameter;

	public function __construct() {
		$this->findReplace = new FindReplace();
		$this->parameter = array();
	}

	public function buildXml() {
		$xml = '';
		$fileName = sprintf('%slib/templates/xml/%s', $GLOBALS["DIR_WS_ADMIN"], $this->template);
		$template = file($fileName);

	    for($i=0; $i<count($template); $i++) {
			$xml .= $this->findReplace->Execute($template[$i]);
		}

		return $xml;
	}

	public function formatXml($xml) {
		$indent = 0;
		$output = array();
		
		$xml = utf8_encode($xml);
		
		libxml_use_internal_errors(true);
		
		$xmlObject = simplexml_load_string($xml);

		if(!$xmlObject) {
			foreach(libxml_get_errors() as $error) {
		        print_r($error);
		    }
		} else {
			$xml = explode("\n", preg_replace('/>\s*</', ">\n<", $xmlObject->asXML()));

			if(count($xml) && preg_match('/^<\?\s*xml/', $xml[0])) {
				$output[] = array_shift($xml);
			}

			foreach($xml as $xmlItem) {
				if(preg_match('/^<([\w])+[^>\/]*>$/U', $xmlItem)) {
					$output[] = str_repeat("\t", $indent) . $xmlItem;
					$indent++;
				} else {
					if(preg_match('/^<\/.+>$/', $xmlItem)) {
						$indent--;
					}

					if($indent < 0) {
						$indent++;
					}

					$output[] = str_repeat("\t", $indent) . $xmlItem;
				}
			}
		}

		return implode("\n", $output);
	}

	public function formatCharacterData($data) {
		return !empty($data) ? sprintf('<![CDATA[%s]]>', trim($data)) : trim($data);
	}

	public function setParameter($key, $value) {
		$this->parameter[$key] = $value;
	}

	public function getParameter($key) {
		return isset($this->parameter[$key]) ? $this->parameter[$key] : null;
	}
}