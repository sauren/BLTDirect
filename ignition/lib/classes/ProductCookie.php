<?php
class ProductCookie {
	var $Data;
	var $Period;

	function ProductCookie() {
		$this->Reset();
		$this->Period = time() + (60 * 60 * 24 * 3);

		if(array_key_exists('ProductCookie', $_COOKIE)){
			if(!$this->Unpackage($_COOKIE['ProductCookie'])){
				$this->Reissue();
			}
		}
	}

	function Reset() {
		$this->Data = array();
		$this->Data['CreatedOn'] = time();
		$this->Data['Products'] = array();
	}

	function Add($productId) {
		$this->Data['Products'][$productId] = date('Y-m-d H:i:s');
	}

	function Get($key) {
		if(array_key_exists($key, $this->Data)){
			return $this->Data[$key];
		}
		
		return false;
	}
	
	function GetProducts() {
		$products = $this->Get('Products');
		
		if(is_array($products)) {
			$tempProducts = array();
			
			foreach($products as $productId => $lastViewedOn) {
				$tempProducts[$lastViewedOn] = $productId;
			}
			
			krsort($tempProducts);
			
			return $tempProducts;
		}
		
		return false;
	}
	
	function Set() {
		if(checkPhpVersion('5.2.0')){
			setcookie('ProductCookie', $this->Package(), $this->Period, '/', '', isset($_SERVER["HTTPS"]), false);
		} else {
			setcookie('ProductCookie', $this->Package(), $this->Period, '/', '', isset($_SERVER["HTTPS"]));
		}
	}

	function Remove($key) {
		if(array_key_exists($key, $this->Data)){
			unset($this->Data[$key]);
		}
	}

	function Package() {	
		return base64_encode(serialize($this->Data));
	}

	function Unpackage($cookie) {
		$buffer = base64_decode($cookie);
		$this->Data = @unserialize($buffer);
		
		return (!$this->Data && !is_array($this->Data)) ? false : true;
	}

	function Reissue() {
		$this->Reset();
		$this->Set();
	}
}
?>
