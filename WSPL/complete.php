<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');

if(trim(param('payment', '')) == 'google') {
	for($i=0; $i < count($cart->Line); $i++){
		$cart->Line[$i]->Remove();
	}
	$cart->Coupon->ID = 0;
	$cart->Update();
}

if(strlen(param('o', '')) > 0) {
    $session->Secure();

	$o = base64_decode(param('o'));
	$orderNum = new Cipher($o);
	$orderNum->Decrypt();

	$order = new Order($orderNum->Value);
	$order->PaymentMethod->Get();
	$order->GetLines();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('o', 'Order ID', 'hidden', $o, 'paragraph', 1, 100);

	if($order->PaymentMethod->Reference != 'google') {
		$form->AddField('custom', 'Custom Reference Number', 'text', '', 'alpha_numeric', 1, 32, false);
	}

	$form->AddField('message', 'Order Note', 'textarea', '', 'paragraph', 1, 2000, false, 'style="width:90%; height:100px"');
	$form->AddField('delivery', 'Delivery Instructions', 'textarea', '', 'paragraph', 1, 2000, false, 'style="width:90%; height:100px"');

	if(strtolower(param('confirm', '')) == "true"){
		if($form->Validate()){
			$note = new OrderNote();
			$note->Message = $form->GetValue('message');
			$note->OrderID = $order->ID;
			$note->IsPublic = 'Y';

			if(!empty($note->Message)){
				$order->Customer->Get();
				$order->Customer->Contact->Get();

				$note->Add();
				$note->SendToAdmin($order->Customer->Contact->Person->GetFullName(), $order->Customer->GetEmail());
			}

			if($order->PaymentMethod->Reference != 'google') {
				$order->CustomID = $form->GetValue('custom');
			}

			$order->DeliveryInstructions = $form->GetValue('delivery');
			$order->IsNotesUnread = 'Y';
			$order->Update();

			redirect(sprintf("Location: ordersconfirmation.php?orderid=%d", $order->ID));
		}
	}
}
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Order Complete</span></div>
<div class="maincontent">
<div class="maincontent1">
			<?php
			if(strlen($_REQUEST['o']) > 0) {
				?>
				<p>Thank you for shopping with <?php echo $GLOBALS['COMPANY']; ?>. Your order (Ref. <strong><?php echo $order->Prefix . $order->ID; ?></strong>) was successfully completed. Your order will be subject to security checks before your credit card is charged. When contacting us regarding your order please remember to quote your order reference number. We recommend that you print this page for your records, however a full history of your online orders is available through '<a href="accountcenter.php">My Account</a>'. </p>
				<?php
			} else {
				?>
				<p>Thank you for shopping with <?php echo $GLOBALS['COMPANY']; ?>. Your order was successfully completed. Your order will be subject to security checks before your credit card is charged. When contacting us regarding your order please remember to quote your order reference number. We recommend that you print this page for your records, however a full history of your online orders is available through '<a href="accountcenter.php">My Account</a>'.</p>
				<?php
			}

			if(strlen($_REQUEST['o']) > 0) {
				echo $form->Open();
				echo $form->GetHTML('action');
				echo $form->GetHTML('confirm');
				echo $form->GetHTML('o');

				if(!$form->Valid){
					echo $form->GetError();
					echo "<br>";
				}
			?>
			<br />
			<table border="0" cellpadding="10" cellspacing="0" class="bluebox">
              <?php
              if($order->PaymentMethod->Reference != 'google') {
                	?>
                	<tr>
		                <td><h3 class="blue">Add Your Own Reference</h3>
		                <p>If you have your own purchasing or order reference number enter it below: </p>
		                <p>
						<?php echo $form->GetHTML('custom'); ?>
						</p>
						</td>
              </tr>
              	<?php
              }
                ?>
              <tr>
                <td>

                <h3 class="blue">Delivery Instructions</h3>
                    <p>If you have special delivery requirements for your order please use the field below: </p>
                    <p><?php echo $form->GetHTML('delivery'); ?></p>
                </td>
              </tr>
              <tr>
                <td>

                <h3 class="blue">Add Additional Information</h3>
                    <p>If you would like to add additional information to your order please use the field below: </p>
                    <p><?php echo $form->GetHTML('message'); ?></p>
                    <p>When you click Continue below you will be redirected to a summary of your order. </p>
                    <p>
                      <input name="Submit" type="submit" class="submit" value="Continue" tabindex="<?php echo $form->GetTabIndex(); ?>" />
                    </p></td>
              </tr>
            </table>
			<?php
			echo $form->Close();
			}
			?>

			<!-- Yahoo Code for Purchase Convertion Page -->
			<script type="text/javascript">
			<!-- Overture Services Inc. 07/15/2003
			var cc_tagVersion = "1.0";
			var cc_accountID = "4005137100";
			var cc_marketID =  "1";
			var cc_protocol="http";
			var cc_subdomain = "convctr";
			if(location.protocol == "https:")
			{
				cc_protocol="https";
				cc_subdomain="convctrs";
			}
			var cc_queryStr = "?" + "ver=" + cc_tagVersion + "&aID=" + cc_accountID + "&mkt=" + cc_marketID +"&ref=" + escape(document.referrer);
			var cc_imageUrl = cc_protocol + "://" + cc_subdomain + ".overture.com/images/cc/cc.gif" + cc_queryStr;
			var cc_imageObject = new Image();
			cc_imageObject.src = cc_imageUrl;
			// -->
			</script>

			<!-- MSN Code for Purchase Convertion Page -->
			<script type="text/javascript">
			microsoft_adcenterconversion_domainid = 53924;
			microsoft_adcenterconversion_cp = 5050;
			</script>
			<script src=" https://0.r.msn.com/scripts/microsoft_adcenterconversion.js"></script>
			<noscript><img width="1" height="1" src="https://53924.r.msn.com/?type=1&amp;cp=1"/></noscript>
            <script>
var parm,data,htprot='http';
data = 'cid=256336&cs=<?php echo $order->Total; ?>&it=<?php echo count($order->Line); ?>&oi=<?php echo $order->ID; ?>';
parm=' border="0" hspace="0" vspace="0" width="1" height="1"'; if(self.location.protocol=='https:')htprot='https';
document.write('<img '+parm+' src="'+htprot+'://stats1.saletrack.co.uk/scripts/stexit.asp?'+ data + '">');  </script>
<noscript>
<img src="http://stats1.saletrack.co.uk/scripts/stexit.asp?cid=256336&cs=<?php echo $order->Total; ?>&it=<?php echo count($order->Line); ?>&oi=<?php echo $order->ID; ?>" border="0" width="0" height="0">
</noscript>
-->


<?php
	if(strlen($_REQUEST['o']) == 0) {
?>

<script type="text/javascript">
<!-- Yahoo!
window.ysm_customData = new Object();
window.ysm_customData.conversion = "transId=,currency=,amount=";

var ysm_accountid  = "1JGL043GL8AJ7C61MK9SVDEMIDG";
document.write("<SCR" + "IPT language='JavaScript' type='text/javascript' "
+ "SRC=//" + "srv1.wa.marketingsolutions.yahoo.com" +
"/script/ScriptServlet" + "?aid=" + ysm_accountid
+ "></SCR" + "IPT>");

// -->
</script>

<script type="text/javascript">if (!window.mstag) mstag = {loadTag : function(){},time : (new Date()).getTime()};</script>
<script id="mstag_tops" type="text/javascript" src="//flex.atdmt.com/mstag/site/3fa51111-b16d-424a-932c-6438dbb7b744/mstag.js"></script>
<script type="text/javascript">mstag.loadTag("conversion", {cp:"5050",dedup:"1"})</script>
<noscript><iframe src="//flex.atdmt.com/mstag/tag/3fa51111-b16d-424a-932c-6438dbb7b744/conversion.html?cp=5050&amp;dedup=1" frameborder="0" scrolling="No" width="1" height="1" style="visibility: hidden; display: none;"></iframe></noscript>

<?php
}
?>
</div>
</div>
<?php require_once('../lib/common/appFooter.php');