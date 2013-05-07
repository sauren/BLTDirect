<?php
class GoogleMerchant{
	var $ID;
	var $Key;

	function GoogleMerchant($id=null, $key=null){
		$this->ID = $id;
		$this->Key = $key;
	}

	// check port is secure
	function verifyPort(){
	}

	// security mesaure
	function verifyMerchant(){
		// check merchantId and merchantKey
	}

	// called when a user creates a Google account
	function onCreateAccount(){
	}

	// called when a user signs into their Google account to complete an order.
	function onLogin(){
	}

	// called when a user enters a new shipping address on the Place Order page.
	function onNewShippingAddress(){
	}

	// called when coupon entered by user.
	function onCoupon(){
	}

	// called when a user changes their shipping address.
	function onChangeShippingAddress(){
	}

	// used to return results
	function calculateResults(){
	}
}
?>