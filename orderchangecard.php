<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PaymentGateway.php');

$session->Secure();

if(!isset($_REQUEST['orderid']) || !is_numeric($_REQUEST['orderid'])) {
	redirectTo('index.php');
}

$order = new Order($_REQUEST['orderid']);
$order->PaymentMethod->Get();
$order->Customer->Get();
$order->Customer->Contact->Get();
$order->Customer->Contact->Person->Get();

if($order->PaymentMethod->Reference != 'card') {
	redirect(sprintf('Location: orders.php?orderid=%d', $order->ID));	
}

$form = new Form($_SERVER['PHP_SELF']);
$form->DisableAutocomplete = true;
$form->Icons['valid'] = '';
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('orderid', 'Order ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('title', 'Card Holder Title', 'select', $order->Card->Title, 'anything', 1, 20);
$form->AddOption('title', '', '');

$data = new DataQuery("SELECT * FROM person_title ORDER BY Person_Title ASC");
while($data->Row){
	$form->AddOption('title', $data->Row['Person_Title'], $data->Row['Person_Title']);

	$data->Next();
}
$data->Disconnect();

$form->AddField('initial', 'Card Holder First Initial', 'text', $order->Card->Initial, 'alpha_numeric', 1, 1, true, 'size="2"');
$form->AddField('surname', 'Card Holder Last Name', 'text', $order->Card->Surname, 'anything', 1, 60);
$form->AddField('cardNumber', 'Card Number', 'text', $order->Card->GetNumber(), 'numeric_unsigned', 16, 19);
$form->AddField('cardType', 'Card Type', 'select', $order->Card->Type->ID, 'numeric_unsigned', 1, 11, true, 'onchange="toggleType(this);"');
$form->AddOption('cardType', '', '');

$data = new DataQuery("SELECT * FROM card_type ORDER BY Card_Type ASC");
while($data->Row){
	$form->AddOption('cardType', $data->Row['Card_Type_ID'], $data->Row['Card_Type']);

	$data->Next();
}
$data->Disconnect();

$form->AddField('cvn', 'Card Verification Number', 'text', '', 'numeric_unsigned', 3, 3, true, 'size="4"');
$form->AddField('starts', 'Starts (MMYY)', 'text', '', 'numeric_unsigned', 4, 4, false, 'size="5"');
$form->AddField('expires', 'Expires (MMYY)', 'text', '', 'numeric_unsigned', 4, 4, true, 'size="5"');
$form->AddField('issue', 'Issue Number', 'text', '', 'numeric_unsigned', 1, 3, false, 'size="3"');

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
	if($form->Validate()) {
		$order->Card->Type->Get($form->GetValue('cardType'));
		$order->Card->SetNumber($form->GetValue('cardNumber'));
		$order->Card->Expires = $form->GetValue('expires');
		$order->Card->Title = $form->GetValue('title');
		$order->Card->Initial = $form->GetValue('initial');
		$order->Card->Surname = $form->GetValue('surname');

        $gateway = new PaymentGateway();

		if($gateway->GetDefault()) {
			$auth3d = false;

			require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/gateways/' . $gateway->ClassFile);

			$addressData = array();

			if(!empty($order->Billing->Address->Line1)) {
				$addressData[] = $order->Billing->Address->Line1;
			}

			if(!empty($order->Billing->Address->Line2)) {
				$addressData[] = $order->Billing->Address->Line2;
			}

			if(!empty($order->Billing->Address->Line3)) {
				$addressData[] = $order->Billing->Address->Line3;
			}

			if(!empty($order->Billing->Address->City)) {
				$addressData[] = $order->Billing->Address->City;
			}

			if(!empty($order->Billing->Address->Region->Name)) {
				$addressData[] = $order->Billing->Address->Region->Name;
			}

			if(!empty($order->Billing->Address->Country->Name)) {
				$addressData[] = $order->Billing->Address->Country->Name;
			}

			$paymentProcessor = new PaymentProcessor($gateway->VendorName, $gateway->IsTestMode);
			$paymentProcessor->Amount = $order->Total;
			$paymentProcessor->Description = $GLOBALS['COMPANY'] . ' Credit Card Authentication';
			$paymentProcessor->BillingAddress = implode(', ', $addressData);
			$paymentProcessor->BillingPostcode = $order->Customer->Contact->Person->Address->Zip;
			$paymentProcessor->ContactNumber = $order->Customer->Contact->Person->Phone1;
			$paymentProcessor->CustomerEMail = $order->Customer->GetEmail();
			$paymentProcessor->CardHolder = sprintf('%s %s %s', $form->GetValue('title'), $form->GetValue('initial'), $form->GetValue('surname'));
			$paymentProcessor->CardNumber = $form->GetValue('cardNumber');
			$paymentProcessor->StartDate = $form->GetValue('starts');
			$paymentProcessor->ExpiryDate = $form->GetValue('expires');
			$paymentProcessor->CardType = $order->Card->Type->Reference;
			$paymentProcessor->ClientNumber = $order->Customer->ID;
			$paymentProcessor->IssueNumber = $form->GetValue('issue');
			$paymentProcessor->CV2 = $form->GetValue('cvn');
			$paymentProcessor->Payment->Gateway->ID = $gateway->ID;

            if(Setting::GetValue('disable_3dauth_checks') == 'true') {
				$paymentProcessor->AccountType = 'M';
			}

            if(!$paymentProcessor->PreAuthorise()) {
				for($i=0; $i < count($paymentProcessor->Error); $i++){
					$form->AddError($paymentProcessor->Error[$i]);
				}
			} elseif(trim($paymentProcessor->Payment->Status) == '3DAUTH') {
				$auth3d = true;
				$direct = sprintf("orderauthenticate.php?ASCURL=%s&PaReq=%s&MD=%s", urlencode($paymentProcessor->Response["ACSURL"]), urlencode($paymentProcessor->Response["PAReq"]), $paymentProcessor->Response["MD"]);
			}

			if($form->Valid){
				$order->Update();

				$paymentProcessor->Payment->Order->ID = $order->ID;
				$paymentProcessor->Payment->Update();

				$payment = new Payment();

                $data = new DataQuery(sprintf("SELECT Payment_ID, Transaction_Type FROM payment WHERE ((Transaction_Type LIKE '3DAUTH' AND Status LIKE 'AUTHENTICATED') OR (Transaction_Type LIKE 'AUTHENTICATE' AND Status LIKE 'REGISTERED')) AND Order_ID=%d AND Payment_ID<>%d ORDER BY Payment_ID DESC LIMIT 0, 1", $order->ID, $paymentProcessor->Payment->ID));
				if($data->TotalRows > 0) {
					if($data->Row['Transaction_Type'] == '3DAUTH') {
						$data2 = new DataQuery(sprintf("SELECT Payment_ID FROM payment WHERE Transaction_Type LIKE 'AUTHENTICATE' AND Status LIKE '3DAUTH' AND Order_ID=%d AND Payment_ID<>%d ORDER BY Payment_ID DESC LIMIT 0, 1", $order->ID, $paymentProcessor->Payment->ID));
						if($data2->TotalRows > 0) {
							$paymentId = $data2->Row['Payment_ID'];
						}
						$data2->Disconnect();
					} else {
						$paymentId = $data->Row['Payment_ID'];
					}

					if($paymentId > 0) {
						$payment->Get($paymentId);

						$paymentProcessor->Description = $GLOBALS['COMPANY'] . ' Authentication Cancellation';
						$paymentProcessor->Cancel($payment);
					}
				}
				$data->Disconnect();

				if($auth3d) {
					redirect(sprintf('Location: %s&orderid=%d', $direct, $order->ID));
				} else {
					redirect(sprintf("Location: orders.php?orderid=%d", $order->ID));
				}
			}
		}
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Change Card for Order Ref #<?php echo $order->ID; ?></title>
	<!-- InstanceEndEditable -->

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Language" content="en" />
	<link rel="stylesheet" type="text/css" href="css/lightbulbs.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="css/lightbulbs_print.css" media="print" />
	<link rel="stylesheet" type="text/css" href="css/Navigation.css" />
	<link rel="stylesheet" type="text/css" href="css/Menu.css" />
    
    <?php
	if($session->Customer->Contact->IsTradeAccount == 'Y') {
		?>
		<link rel="stylesheet" type="text/css" href="css/Trade.css" />
        <?php
	}
	?>
	<link rel="shortcut icon" href="favicon.ico" />
<!--    <script type='text/javascript' src='http://api.handsetdetection.com/sites/js/43071.js'></script>-->
	<script language="javascript" type="text/javascript" src="js/generic.js"></script>
	<script language="javascript" type="text/javascript" src="js/evance_api.js"></script>
	<script language="javascript" type="text/javascript" src="js/mootools.js"></script>
	<script language="javascript" type="text/javascript" src="js/evance.js"></script>
	<script language="javascript" type="text/javascript" src="js/bltdirect.js"></script>
    <script language="javascript" type='text/javascript' src="js/api.js"></script>
    
    <?php
	if($session->Customer->Contact->IsTradeAccount == 'N') {
		?>
		<script language="javascript" type="text/javascript" src="js/bltdirect/template.js"></script>
        <?php
	}
	?>
    
	<script language="javascript" type="text/javascript">
	//<![CDATA[
		<?php
		for($i=0; $i<count($GLOBALS['Cache']['Categories']); $i=$i+2) {
			echo sprintf("menu1.add('navProducts%d', 'navProducts', '%s', '%s', null, 'subMenu');", $i, $GLOBALS['Cache']['Categories'][$i], $GLOBALS['Cache']['Categories'][$i+1]);
		}
		?>
	//]]>
	</script>	
	<!-- InstanceBeginEditable name="head" -->
    <script language="javascript" type="text/javascript">
	function disableSubmit(){
		var placeOrder = document.getElementById('placeOrder');
		placeOrder.disabled = true;
	}

	function toggleType(obj) {
		var e = document.getElementById('issue');

		if(e) {
			switch(obj.value) {
				case '5':
				case '6':
					e.removeAttribute('disabled');
					break;
				default:
					e.setAttribute('disabled', 'disabled');
					break;
			}
		}
	}

	var disableIssue = <?php echo (($form->GetValue('cardType') == 5) || ($form->GetValue('cardType') == 6) || ($form->GetValue('cardType') == 7)) ? 'false' : 'true'; ?>

	window.onload = function() {
		if(disableIssue) {
			var e = document.getElementById('issue');

			if(e) {
				e.setAttribute('disabled', 'disabled');
			}
		}
	}
	</script>
	<!-- InstanceEndEditable -->
</head>
<body>

    <div id="Wrapper">
        <div id="Header">
            <div id="HeaderInner">
                <?php require('lib/templates/header.php'); ?>
            </div>
        </div>
        <div id="PageWrapper">
            <div id="Page">
                <div id="PageContent">
                    <?php
                    if(strtolower(Setting::GetValue('site_message_active')) == 'true') {
                        ?>

                        <div id="SiteMessage">
                            <div id="SiteMessageLeft">
                                <div id="SiteMessageRight">
                                    <marquee scrollamount="4"><?php echo Setting::GetValue('site_message_value'); ?></marquee>
                                </div>
                            </div>
                        </div>

                        <?php
                    }
                    ?>
                    
                    <a name="top"></a>
                    
                    <!-- InstanceBeginEditable name="pageContent" -->
                    <h1>Change Card for Order Ref #<?php echo $order->ID; ?></h1>
					<p><a href="orders.php">&laquo; View All Orders</a> | <a href="/orders.php?orderid=<?php echo $order->ID; ?>">View Order Ref #<?php echo $order->ID; ?> Details</a> </p>

					<?php
					if(!$form->Valid){
						echo $form->GetError();
						echo "<br>";
					}

					echo $form->Open();
					echo $form->GetHtml('action');
					echo $form->GetHtml('confirm');
					echo $form->GetHtml('orderid');
					?>

					<table cellspacing="0" class="checkoutPayment">
                    	<tr>
							<td colspan="2"><strong>Change Credit/Debit Card Details</strong></td>
						</tr>
                        <tr>
							<td align="right"><?php echo $form->GetLabel('title'); ?>:</td>
							<td><?php echo $form->GetHtml('title'); ?> <?php echo $form->GetIcon('title'); ?></td>
						</tr>
						<tr>
							<td align="right"><?php echo $form->GetLabel('initial'); ?>:</td>
							<td><?php echo $form->GetHtml('initial'); ?> <?php echo $form->GetIcon('initial'); ?></td>
						</tr>
						<tr>
							<td align="right"><?php echo $form->GetLabel('surname'); ?>:</td>
							<td><?php echo $form->GetHtml('surname'); ?> <?php echo $form->GetIcon('surname'); ?></td>
						</tr>
						<tr>
							<td align="right"><?php echo $form->GetLabel('cardNumber'); ?>:</td>
							<td><?php echo $form->GetHtml('cardNumber'); ?> <?php echo $form->GetIcon('cardNumber'); ?></td>
						</tr>
						<tr>
							<td align="right"><?php echo $form->GetLabel('cardType'); ?>:<br /><span class="smallGreyText">We do not accept Amex Cards</span></td>
							<td><?php echo $form->GetHtml('cardType'); ?> <?php echo $form->GetIcon('cardType'); ?></td>
						</tr>
						<tr>
							<td align="right"><?php echo $form->GetLabel('starts'); ?>:</td>
							<td><?php echo $form->GetHtml('starts'); ?> <?php echo $form->GetIcon('starts'); ?></td>
						</tr>
						<tr>
							<td align="right"><?php echo $form->GetLabel('expires'); ?>:</td>
							<td><?php echo $form->GetHtml('expires'); ?> <?php echo $form->GetIcon('expires'); ?></td>
						</tr>
						<tr>
							<td align="right"><?php echo $form->GetLabel('issue'); ?>:<br /><span class="smallGreyText">Maestro/Switch cards only</span></td>
							<td><?php echo $form->GetHtml('issue'); ?> <?php echo $form->GetIcon('issue'); ?></td>
						</tr>
						<tr>
							<td align="right"><?php echo $form->GetLabel('cvn'); ?>:</td>
							<td><?php echo $form->GetHtml('cvn'); ?> <img src="images/icon_cvn_1.gif" width="51" height="31" align="absmiddle" /><?php echo $form->GetIcon('cvn'); ?></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><input type="submit" class="submit" name="update" value="Update" /></td>
						</tr>
					</table>

					<?php
					echo $form->Close();
					?>

					<!-- InstanceEndEditable -->
                </div>
            </div>
            <div id="PageFooter">
                <ul class="links">
                    <li><a href="./terms.php" title="BLT Direct Terms and Conditions of Use and Sale">Terms and Conditions</a></li>
                    <li><a href="./privacy.php" title="BLT Direct Privacy Policy">Privacy Policy</a></li>
                    <li><a href="./company.php" title="About BLT Direct">About Us</a></li>
                    <li><a href="./sitemap.php" title="Map of Site Contents">Site Map</a></li>
                    <li><a href="./support.php" title="Contact BLT Direct">Contact Us</a></li>
                    <li><a href="./index.php" title="Light Bulbs">Light Bulbs</a></li>
                    <li><a href="./products.php?cat=1251&amp;nm=Christmas+Lights" title="Christmas Lights">Christmas Lights</a></li> 
                    <li><a href="./Projector-Lamps.php" title="Projector Lamps">Projector Lamps</a></li>
                    <li><a href="./articles.php" title="Press Releases/Articles">Press Releases/Articles</a></li>
                </ul>
                
                <p class="copyright">Copyright &copy; BLT Direct, 2005. All Right Reserved.</p>
            </div>
        </div>
        <div id="LeftNav">
            <?php require('lib/templates/left.php'); ?>
        </div>
        <div id="RightNav">
            <?php require('lib/templates/right.php'); ?>
        
            <div id="Azexis">
                <a href="http://www.azexis.com" target="_blank" title="Web Designers">Web Designers</a>
            </div>
        </div>
    </div>
	<script src="<?php print ($_SERVER['SERVER_PORT'] != $GLOBALS['SSL_PORT']) ? 'http://www' : 'https://ssl'; ?>.google-analytics.com/urchin.js" type="text/javascript"></script>
	<script type="text/javascript">
	//<![CDATA[
		_uacct = "UA-1618935-2";
		urchinTracker();
	//]]>
	</script>

	<!-- InstanceBeginEditable name="Tracking Script" -->

<!--
<script language="javascript">
var parm,data,rf,sr,htprot='http'; if(self.location.protocol=='https:')htprot='https';
rf=document.referrer;sr=document.location.search;
if(top.document.location==document.referrer||(document.referrer == '' && top.document.location != '')) {rf=top.document.referrer;sr=top.document.location.search;}
data='cid=256336&rf=' + escape(rf) + '&sr=' + escape(sr); parm=' border="0" hspace="0" vspace="0" width="1" height="1" '; document.write('<img '+parm+' src="'+htprot+'://stats1.saletrack.co.uk/scripts/stinit.asp?'+data+'">');
</script>
<noscript>
<img src="http://stats1.saletrack.co.uk/scripts/stinit.asp?cid=256336&rf=JavaScri
pt%20Disabled%20Browser" border="0" width="0" height="0" />
</noscript>
-->

<!-- InstanceEndEditable -->
</body>
<!-- InstanceEnd --></html>
<?php
include('lib/common/appFooter.php');