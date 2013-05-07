<?php
	require_once('lib/classes/ApplicationHeader.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerContact.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PaymentGateway.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Payment.php');

	$payment = new Payment(id_param('VendorTxCode'));
	$cipher = new Cipher($payment->Order->ID);
	$cipher->Encrypt();
	$url = sprintf('order_complete.php?o=%s', base64_encode($cipher->Value));
	$path = 'order_complete.php';
	$name = 'o';
	$value = base64_encode($cipher->Value);
	
	debug($_SESSION['PAYMENT_LAST_ACTION']);
	
	if(isset($_SESSION['PAYMENT_LAST_ACTION']) && $_SESSION['PAYMENT_LAST_ACTION'] == 'change'){
		$url = sprintf('order_details.php?orderid=%d', $payment->Order->ID);
		$path = 'order_details.php';
		$name = 'orderid';
		$value = $payment->Order->ID;
	} 
	$_SESSION['PAYMENT_LAST_ACTION'] = null;
?>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="css/i_import.css">
		<link rel="stylesheet" type="text/css" href="css/inframe.css">
		<script>
			function complete(){
				/*setTimeout(function() {
					window.parent.location.href = '<?php echo $url; ?>';
				}, 3000);*/
			}
		</script>
	</head>
	<body onLoad="complete();">
		<p><img src="/images/success.png" /></p>
		<h1>Thank you</h1>
		<p>Your card has been pre-authorised for &pound;<?php echo $payment->Amount; ?></p>
		<p>Your order reference is <strong><?php echo $payment->Order->Prefix . $payment->Order->ID; ?></strong>.</p>
		
		<a href="<?php echo sprintf('order_details.php?orderid=%d', $payment->Order->ID); ?>" target="_parent" class="btn">Order Details</a> 
		or 
		<a href="<?php echo sprintf('order_complete.php?o=%s', base64_encode($cipher->Value)); ?>" target="_parent" class="btn">Complete Order</a>
	</body>
</html>