<?php
class CustomerSessionCookie{
	var $Handle;
	var $Data;

	function CustomerSessionCookie() {
		$this->Reset();

		$this->Handle = mcrypt_module_open('blowfish', '', 'cfb', '');

		if(array_key_exists('SessionCookie', $_COOKIE)){
			if(!$this->Unpackage($_COOKIE['SessionCookie'])){
				$this->Reissue();
			}
		}
	}

	function Reset(){
		$this->Data = array();
		$this->Data['CreatedOn'] = time();
		$this->Data['SessionId'] = session_id();
	}

	function Add($key, $value){
		$this->Data[$key] = $value;
	}

	function Get($key){
		if(array_key_exists($key, $this->Data)){
			return $this->Data[$key];
		}
		
		return false;
	}

	function Set(){
		if(checkPhpVersion('5.2.0')){
			setcookie('SessionCookie', $this->Package(), 0, '/', '', isset($_SERVER["HTTPS"]), true);
		} else {
			setcookie('SessionCookie', $this->Package(), 0, '/', '', isset($_SERVER["HTTPS"]));
		}
	}

	function Remove($key){
		if(array_key_exists($key, $this->Data)){
			unset($this->Data[$key]);
		}
	}

	function Package(){	
		return $this->Encrypt(serialize($this->Data));
	}

	function Unpackage($cookie){
		$buffer = $this->Decrypt($cookie);
		$this->Data = @unserialize($buffer);
		
		return (!$this->Data && !is_array($this->Data)) ? false : true;
	}

	function Encrypt($cookie){
		$ivsize = mcrypt_enc_get_iv_size($this->Handle);
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($this->Handle), MCRYPT_RAND);
		
		mcrypt_generic_init($this->Handle, 'nuoicn938tyvbp84yat433tyhg', $iv);
		$cryptText = mcrypt_generic($this->Handle, $cookie);
		mcrypt_generic_deinit($this->Handle);
		
		return base64_encode($iv.$cryptText);
	}

	function Decrypt($cookie){
		$cookie = base64_decode($cookie);
		$ivsize = mcrypt_get_iv_size('blowfish', 'cfb');
		$iv = substr($cookie, 0, $ivsize);
		$cookie = substr($cookie, $ivsize);
		
		mcrypt_generic_init($this->Handle, 'nuoicn938tyvbp84yat433tyhg', $iv);
		$plainText = mdecrypt_generic($this->Handle, $cookie);
		mcrypt_generic_deinit($this->Handle);
		
		return $plainText;
	}

	function Reissue(){
		$this->Reset();
		$this->Set();
	}
}
