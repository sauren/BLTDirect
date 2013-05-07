<?php
	class HttpPost{
		var $Arguments;
		var $UserAgent;
		var $UrlEncoded;
		var $ContentLength;
		var $Url;
		var $Server;
		var $Port;
		var $Headers;
		var $AcceptLang;
		var $Protocol;
		var $Error;
		var $ErrorNum;
		
		function HttpPost($url){
			$this->UserAgent = $_SERVER['HTTP_USER_AGENT'];
			$this->UrlEncoded = '';
			$this->Arguments = array();
			$this->Server = $_SERVER['HTTP_HOST'];
			$this->Port= $_SERVER['SERVER_PORT'];
			$this->AcceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
			$this->Protocol = $_SERVER['SERVER_PROTOCOL'];
			$this->Url = $url;
			$this->UrlEncoded = "";
			$this->ContentLength = 0;
			$this->Headers = "POST %s %s
Accept: */*
Accept-Language: %s
Content-Type: application/x-www-form-urlencoded
User-Agent: %s
Host: %s
Connection: Keep-Alive
Cache-Control: no-cache
Content-Length: %s

";
		}
		
		function Execute(){
			foreach($this->Arguments as $key=>$value){
				$this->UrlEncoded .= urlencode($key) . "=" . urlencode($value) . "&";
			}
			$this->UrlEncoded = substr($this->UrlEncoded,0,-1);	
			$this->ContentLength = strlen($this->UrlEncoded);
			
			$this->Headers = sprintf($this->Headers,
									$this->Url,
									$this->Protocol,
									$this->AcceptLang,
									$this->UserAgent,
									$this->Server,
									$this->ContentLength);

			$fp = fsockopen($this->Server, $this->Port, $this->ErrorNum, $this->Error);
			if(!$fp) return false;
		
			fputs($fp, $this->Headers);
			fputs($fp, $this->UrlEncoded);
			
			$ret = "";
			while (!feof($fp))
				$ret.= fgets($fp, 1024);
				
			fclose($fp);
			
			return $ret;
		}
		
		function AddArgument($key, $value){
			$this->Arguments[$key] = $value;
		}
	}
?>