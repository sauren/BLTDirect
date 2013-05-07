<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PaymentGateway.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Payment.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');

$session->Secure();

$form = new Form($_SERVER['PHP_SELF']);

$o = base64_decode($_REQUEST['o']);
$orderNum = new Cipher($o);
$orderNum->Decrypt();

$order = new Order();
if(!$order->Get($orderNum->Value)) {
	redirect("Location: cart.php");
}

if($action == "complete") {
	echo '<html>
			<link href="css/lightbulbs.css" rel="stylesheet" type="text/css" media="screen" />
			<link href="css/lightbulbs_print.css" rel="stylesheet" type="text/css" media="print" />
			<script>
				function complete(){
					setTimeout(function() {
						window.parent.complete(\''.$_REQUEST['o'].'\');
					}, 3000);
				}
			</script>
			<style>
			body {
				background-color: #fff;
				background-image: none;
			}
			</style>
			<body onload="complete();">';

	echo '<br /><br /><p><strong>Authentication was successful...</strong><br />You will now be redirected to your order summary, please <a href="complete.php?o='.$_REQUEST['o'].'" target="_parent">click here</a> if your browser does not redirect.</p></center>';

	echo '</body></html>';
	exit;
}

if($order->Status != 'Unauthenticated') {
	redirect("Location: cart.php");
}

$order->Customer->Get();
$order->Customer->Contact->Get();

$ascUrl = $_REQUEST['ASCURL'];
$paReq = $_REQUEST['PaReq'];
$mD = $_REQUEST['MD'];

if(Setting::GetValue('disable_3dauth_retries') == 'true') {
	$canReauth = false;
} else {
	$canReauth = true;
}

$gateway = new PaymentGateway();
$hasGateway = $gateway->GetDefault();

if($hasGateway && (strtoupper($gateway->HasPreAuth) == 'Y')){
	if(isset($_REQUEST['action']) && ($_REQUEST['action'] == "verify")) {
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/gateways/' . $gateway->ClassFile);

		$paymentProcessor = new PaymentProcessor($gateway->VendorName, $gateway->IsTestMode);
		$paymentProcessor->PARes = $_REQUEST['PaRes'];
		$paymentProcessor->MD = $_REQUEST['MD'];
		$paymentProcessor->Amount = $order->Total;
		$paymentProcessor->Payment->Gateway->ID = $gateway->ID;
		$paymentProcessor->Payment->Order->ID = $order->ID;

		$payment = new Payment();

		$data = new DataQuery(sprintf("SELECT Payment_ID FROM payment WHERE Transaction_Type LIKE 'AUTHENTICATE' AND Status LIKE '3DAUTH' AND Order_ID=%d ORDER BY Created_On DESC LIMIT 0, 1", $order->ID));
		if($data->TotalRows > 0) {
			$payment->Get($data->Row['Payment_ID']);

			if(!$paymentProcessor->Callback($payment)){
				for($i=0; $i < count($paymentProcessor->Error); $i++){
					$form->AddError($paymentProcessor->Error[$i]);
				}

				// recreate authenticate transaction to allow customer to try again
				if($canReauth) {
					$paymentProcessor = new PaymentProcessor($gateway->VendorName, $gateway->IsTestMode);
					$paymentProcessor->Amount = $order->Total;
					$paymentProcessor->Description = $GLOBALS['COMPANY'] . ' Credit Card Authentication';
					$paymentProcessor->Payment->Gateway->ID = $gateway->ID;
					$paymentProcessor->Payment->Order->ID = $order->ID;

					$billing = &$order->Customer->Contact->Person->Address;
					$addressData = array();
					if(!empty($billing->Line1)) $addressData[] = $billing->Line1;
					if(!empty($billing->Line2)) $addressData[] = $billing->Line2;
					if(!empty($billing->Line3)) $addressData[] = $billing->Line3;
					if(!empty($billing->City)) $addressData[] = $billing->City;
					if(!empty($billing->Region->Name)) $addressData[] = $billing->Region->Name;
					if(!empty($billing->Country->Name)) $addressData[] = $billing->Country->Name;
					$addressString = implode(', ', $addressData);

					$paymentProcessor->BillingAddress = $addressString; // Optional, up to 200 characters
					$paymentProcessor->BillingPostcode = $billing->Zip; // Optional, up to 10 characters
					$paymentProcessor->ContactNumber = $order->Customer->Contact->Person->Phone1; // Optional, up to 20 characters
					$paymentProcessor->CustomerEMail = $order->Customer->GetEmail(); // Optional, up to 255 characters

					$paymentProcessor->CardHolder = sprintf('%s %s %s', $order->Card->Title, $order->Card->Initial, $order->Card->Surname);
					$paymentProcessor->CardNumber = $order->Card->GetNumber();
					$paymentProcessor->ExpiryDate = $order->Card->Expires;

					$paymentProcessor->ClientNumber = $order->Customer->ID;

					$data = new DataQuery(sprintf("SELECT Reference FROM card_type WHERE Card_Type LIKE '%s'", $order->Card->Type->Name));
					if($data->TotalRows == 0) {
						$canReauth = false;
					} else {
						$paymentProcessor->CardType = $data->Row['Reference'];

						if($paymentProcessor->PreAuthorise()){
							$ascUrl = $paymentProcessor->Response["ACSURL"];
							$paReq = $paymentProcessor->Response["PAReq"];
							$mD = $paymentProcessor->Response["MD"];

						} else {
							$canReauth = false;
						}
					}
					$data->Disconnect();
				} else {
					$order->Delete();
				}

				if(!$canReauth) {
					$order->GetLines();

					for($i = 0; $i < count($order->Line); $i++) {
						$cart->AddLine($order->Line[$i]->Product->ID, $order->Line[$i]->Quantity);
					}
				}
			}
		} else {
			$form->AddError("Unable to 3D Auth this transactions as no authentication has taken place.");
		}
		$data->Disconnect();

		if($form->Validate()) {
			$order->Status = 'Unread';
			$order->Update();

			redirect(sprintf("Location: %s?o=%s&action=complete", $_SERVER['PHP_SELF'], $_REQUEST['o']));
		}
	}
} else {
	redirect("Location: cart.php");
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Authenticate Payment</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="css/lightbulbs.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="css/lightbulbs_print.css" rel="stylesheet" type="text/css" media="print" />
	<script language="javascript" src="js/generic.js" type="text/javascript"></script>
	<script language="Javascript">
	function redirect(perform) {
		if(perform) {
			setTimeout(function() {
				document.form.submit();
			}, 3000);
		}
	}
	</script>
	<style>
	body {
		background-color: #fff;
		background-image: none;
	}
	</style>
</head>
<body onload="redirect(<?php echo ($form->Valid && $canReauth) ? 'true' : 'false'; ?>);">

<?php
if(!$form->Valid){
	echo $form->GetError();
}

if($canReauth) {
	?>

	<form name="form" action="<?php print $ascUrl; ?>" method="post" />
	<input type="hidden" name="PaReq" value="<?php print $paReq; ?>"/>
	<input type="hidden" name="TermUrl" value="<?php print $GLOBALS['HTTPS_SERVER']; ?>authverify.php?action=verify&o=<?php print $_REQUEST['o']; ?>&ASCURL=<?php print urlencode($ascUrl); ?>&PaReq=<?php print urlencode($paReq); ?>"/>
	<input type="hidden" name="MD" value="<?php print $mD; ?>"/>

	<?php
	if(!$form->Valid){
		echo '<center><br /><br /><p><strong>Authentication required...</strong><br />Please click the button below to Authenticate your card.</p></center>';
	} else {
		echo '<center><br /><br /><p><strong>Authentication required...</strong><br />You will now be redirected to your card issuer authentication site, click the button below if your browser does not redirect.</p></center>';
	}
	?>

	<br />
	<center><input type="submit" class="submit" value="Authenticate"/></center>

	</form>

	<?php
} else {
	echo '<center><br /><br /><p><strong>Re-authentication failed...</strong><br />Your card could not be re-authenticated, please return to your <a href="cart.php" target="_parent">shopping cart</a> and continue with the checkout process.</p></center>';
}
?>

</html>
<?php include('lib/common/appFooter.php'); ?>