<?php
class Cipher {
	var $Secret;
	var $Data;
	var $Value;
	var $Handle;
	var $IvSize;

    function Cipher($data){
		$this->Secret = $this->GetKey();
		$this->Data = $data;
		$this->Handle = mcrypt_module_open(MCRYPT_BLOWFISH, '', 'cfb', '');
		$this->IvSize = mcrypt_get_iv_size(MCRYPT_BLOWFISH, 'cfb');
    }

	function GetKey(){
		$keyFile = file($GLOBALS["DIR_WS_ADMIN"] . "lib/common/keyd.dat");
		return trim($keyFile[0]);
	}

	function Decrypt() {
		if(!empty($this->Data)){
			$data = base64_decode($this->Data);
			$ivsize = mcrypt_get_iv_size(MCRYPT_BLOWFISH, 'cfb');
			$iv = substr($data, 0, $ivsize);
			$data = substr($data, $ivsize);
			mcrypt_generic_init($this->Handle, $this->Secret, $iv);
			$plainText = mdecrypt_generic($this->Handle, $data);
			mcrypt_generic_deinit($this->Handle);
			mcrypt_module_close($this->Handle);
			$this->Value = $plainText;
		} else {
			$this->Value = '';
		}
	}

	function Encrypt() {
        if(!empty($this->Data)){
			$ivsize = mcrypt_enc_get_iv_size($this->Handle);
			$iv = substr($this->Secret, -mcrypt_enc_get_iv_size($this->Handle));
			mcrypt_generic_init($this->Handle, $this->Secret, $iv);
			$cryptText = mcrypt_generic($this->Handle, $this->Data);
			mcrypt_generic_deinit($this->Handle);
			$fullString = base64_encode($iv.$cryptText);

			mcrypt_module_close($this->Handle);
			$this->Value = $fullString;
        } else {
        	$this->Value = '';
        }
	}
}
?>