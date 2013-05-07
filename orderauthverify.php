<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PaymentGateway.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Payment.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');

$session->Secure();

$form = new Form($_SERVER['PHP_SELF']);

$order = new Order();

if(!isset($_REQUEST['orderid']) || !$order->Get($_REQUEST['orderid'])) {
	redirect("Location: orders.php");
}

if($action == "complete") {
	echo '<html>
			<link href="css/lightbulbs.css" rel="stylesheet" type="text/css" media="screen" />
			<link href="css/lightbulbs_print.css" rel="stylesheet" type="text/css" media="print" />
			<script>
				function complete(){
					setTimeout(function() {
						window.parent.complete();
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

	echo '<br /><br /><p><strong>Authentication was successful...</strong><br />You will now be redirected to your order summary, please <a href="orders.php?orderid='.$order->ID.'" target="_parent">click here</a> if your browser does not redirect.</p></center>';

	echo '</body></html>';
	exit;
}

$order->Customer->Get();
$order->Customer->Contact->Get();

$ascUrl = $_REQUEST['ASCURL'];
$paReq = $_REQUEST['PaReq'];
$mD = $_REQUEST['MD'];

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
			}
		} else {
			$form->AddError("Unable to 3D Auth this transactions as no authentication has taken place.");
		}
		$data->Disconnect();

		if($form->Validate()) {
			redirect(sprintf("Location: %s?orderid=%d&action=complete", $_SERVER['PHP_SELF'], $order->ID));
		}
	}
} else {
	redirect("Location: orders.php");
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Authenticate Card Details</title>
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
<body onload="redirect(<?php echo ($form->Valid) ? 'true' : 'false'; ?>);">

<?php
if(!$form->Valid){
	echo $form->GetError();
}
?>

<form name="form" action="<?php print $ascUrl; ?>" method="post" />
	<input type="hidden" name="PaReq" value="<?php print $paReq; ?>"/>
	<input type="hidden" name="TermUrl" value="<?php echo $GLOBALS['HTTPS_SERVER']; ?>orderauthverify.php?action=verify&orderid=<?php print $order->ID; ?>&ASCURL=<?php print urlencode($ascUrl); ?>&PaReq=<?php print urlencode($paReq); ?>"/>
	<input type="hidden" name="MD" value="<?php print $mD; ?>"/>

	<center><br /><br /><p><strong>Authentication required...</strong><br />You will now be redirected to your card issuer authentication site, click the button below if your browser does not redirect.</p></center><br />
	<center><input type="submit" class="submit" value="Authenticate"/></center>
</form>

</html>
<?php
include('lib/common/appFooter.php');