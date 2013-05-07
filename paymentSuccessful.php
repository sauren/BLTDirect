<?php
	require_once('lib/common/appHeader.php');
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
	$url = sprintf('complete.php?o=%s&paymenttype=card', base64_encode($cipher->Value));
	$path = 'complete.php';
	$name = 'o';
	$value = base64_encode($cipher->Value);
	
	if(isset($_SESSION['PAYMENT_LAST_ACTION']) && $_SESSION['PAYMENT_LAST_ACTION'] == 'change'){
		$url = sprintf('orders.php?orderid=%d', $payment->Order->ID);
		$path = 'orders.php';
		$name = 'orderid';
		$value = $payment->Order->ID;
	} 
	$_SESSION['PAYMENT_LAST_ACTION'] = null;
?>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="css/lightbulbs.css">
		<link rel="stylesheet" type="text/css" href="css/inframe.css">
		<script>
			function complete(){
				setTimeout(function() {
					window.parent.location.href = '<?php echo $url; ?>';
				}, 3000);
			}
		</script>
	</head>
	<body>
		<p><img src="/images/success.png" /></p>
		<h1>Thank you</h1>
		<p>Your card has been pre-authorised for &pound;<?php echo $payment->Amount; ?></p>
		<p>Your order reference is <strong><?php echo $payment->Order->Prefix . $payment->Order->ID; ?></strong>.</p>
		
		<form id="MyForm" action="<?php echo $path; ?>" method="get" target="_top">
			<input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>" />
			<input type="submit" name="Continue" value="Continue" class="submit" title="Get your order details">
		</form>

		<script type="text/javascript">
			  var _gaq = _gaq || [];
			  _gaq.push(['_setAccount', 'UA-1618935-2']);
			  _gaq.push(['_setDomainName', 'bltdirect.com']);
			  _gaq.push(['_trackPageview']);

			  _gaq.push(['_addTrans',
			    '<?php echo $order->Prefix . $order->ID; ?>',           // order ID - required
			    'BLT Direct', // affiliation or store name
			    '<?php echo number_format($order->Total, 2, ".", ""); ?>',          // total - required
			    '<?php echo number_format($order->TotalTax, 2, ".", ""); ?>',           // tax
			    '<?php echo number_format($order->TotalShipping, 2, ".", ""); ?>',              // shipping
			    '<?php echo $order->Shipping->Address->City; ?>',       // city
			    '<?php echo $order->Shipping->Address->Region->Name; ?>',     // state or province
			    '<?php echo $order->Shipping->Address->Country->Name; ?>'             // country
			  ]);

			   // add item might be called for every item in the shopping cart
			   // where your ecommerce engine loops through each item in the cart and
			   // prints out _addItem for each
			<?php
				for($i=0; $i < count($order->Line); $i++){
					if($order->Line[$i]->Product->ID > 0) {
						$itemPrice = ($order->Line[$i]->Price-($order->Line[$i]->Discount/$order->Line[$i]->Quantity));
						$itemTotal = ($order->Line[$i]->Price-($order->Line[$i]->Discount/$order->Line[$i]->Quantity))*$order->Line[$i]->Quantity;
					} else {
						$itemPrice = $order->Line[$i]->Price;
						$itemTotal = $order->Line[$i]->Price * $order->Line[$i]->Quantity;
					}
					if($order->Line[$i]->Product->ID > 0) {
						$productTitle = $order->Line[$i]->Product->Name;
					} else {
						$productTitle = $order->Line[$i]->AssociativeProductTitle;
					}
			?>
			  _gaq.push(['_addItem',
			    '<?php echo $order->Prefix . $order->ID; ?>',           // order ID - required
			    '<?php echo $order->Line[$i]->Product->ID; ?>',           // SKU/code - required
			    '<?php echo htmlentities($productTitle); ?>',        // product name
			    '',   // category or variation
			    '<?php echo number_format($itemPrice, 2, ".", ""); ?>',          // unit price - required
			    '<?php echo $order->Line[$i]->Quantity; ?>'               // quantity - required
			  ]);
			<?php } ?>

			  //submits transaction to the Analytics servers
			  _gaq.push(['_trackTrans']); 


			  (function() {
			    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			  })();
		</script>

		<!-- Google -->
		<!-- Google Code for lead Conversion Page -->
		<script type="text/javascript">
		<!--
			var google_conversion_id = 1070689084;
			var google_conversion_language = "en";
			var google_conversion_format = "3";
			var google_conversion_color = "666666";
			var google_conversion_value = "<?php echo number_format(($order->Total-$order->TotalTax), 2, ".", ""); ?>";
			var google_conversion_label = "fnS1CNLkPRC81sX-Aw";
		//-->
		</script>
		<script type="text/javascript" src="https://www.googleadservices.com/pagead/conversion.js">
		</script>
		<noscript>
		<div style="display:inline;">
		<img height="1" width="1" style="border-style:none;" alt="" src="https://www.googleadservices.com/pagead/conversion/1070689084/?value=0&amp;label=fnS1CNLkPRC81sX-Aw&amp;guid=ON&amp;script=0"/>
		</div>
		</noscript>
	</body>
</html>


