<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');

if($GLOBALS['USE_SSL'] && ($_SERVER['SERVER_PORT'] != $GLOBALS['SSL_PORT'])){
	$url = ($GLOBALS['USE_SSL'])?$GLOBALS['HTTPS_SERVER']:$GLOBALS['HTTP_SERVER'];
	$self = 'gateway.php';
	$qs = '';
	if(!empty($_SERVER['QUERY_STRING'])){$qs = '?' . $_SERVER['QUERY_STRING'];}
	redirect(sprintf("Location: %s%s%s", $url, $self, $qs));
}

$direct = "accountcenter.php";
$isCheckout = false;
$isReturns = false;
$isIntroduce = false;
$isCancellation = false;
$isDuplication = false;

if(param('direct')) {
	$form = new Form('gateway.php');
	if (preg_match("/{$form->RegularExp['link_relative']}/", param('direct'))) {
		$direct = htmlspecialchars(param('direct'));
	}
	if(stristr($direct, 'checkout.php')) $isCheckout = true;
	if(stristr($direct, 'returnorder.php')) $isReturns = true;
	if(stristr($direct, 'introduce.php')) $isIntroduce = true;
	if(stristr($direct, 'cancel.php')) $isCancellation = true;
	if(stristr($direct, 'duplicate.php')) $isDuplication = true;
}

$login = new Form('gateway.php');
$login->AddField('action', 'Action', 'hidden', 'login', 'alpha', 4, 6);
$login->SetValue('action', 'login');
$login->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$login->AddField('direct', 'Direct', 'hidden', $direct, 'link_relative', 1, 255);
$login->SetValue('direct', $direct);
$login->AddField('username', 'E-mail Address', 'text', '', 'anything', 6, 100);
$login->AddField('password', 'Password', 'password', '', 'password', 6, 100);

$assistant = new Form('gateway.php');
$assistant->TabIndex = 3;
$assistant->AddField('action', 'Action', 'hidden', 'assistant', 'alpha', 1, 11);
$assistant->SetValue('action', 'assistant');
$assistant->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$assistant->AddField('emailOrUser', 'Email Address', 'text', '', 'paragraph', 1, 100);
$assistant->AddField('direct', 'Direct', 'hidden', $direct, 'link_relative', 1, 255);
$assistant->SetValue('direct', $direct);

if(strtolower(param('confirm', '')) == "true"){

	if($action == "login"){
		$login->Validate();

		if($session->Login($login->GetValue('username'), $login->GetValue('password'))){
			redirect("Location: " . $login->GetValue('direct'));
		} else {
			$login->AddError("Sorry you were unable to login. Please check your email address and password and try again. If you are struggling to log in you can use our forgotten password facility to retrieve your password.");
		}
	} elseif ($action == "assistant") {
		$str = $assistant->GetValue('emailOrUser');
		if(empty($str)){
			redirect("Location: gateway.php");
		} else {
			$customer = new Customer();

			if(!$customer->IsUnique($str)){
				$customer->Get();
				$customer->Contact->Get();
				$customer->ResetPasswordEmail();

				redirect(sprintf("Location: gateway.php?assistant=successful"));
			} else {
				$assistant->AddError('Sorry, we could not find your entry in our database.');
			}
		}
	}
}
include("ui/nav.php");
include("ui/search.php");?>
			<?php
				if($isCheckout){
			?>
            	<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Secure Checkout</span></div>
			<?php
				} elseif($isReturns){
			?>
            	<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Returns</span></div>
			<?php
				} elseif($isCancellation) {
			?>
            <div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Cancellations</span></div>
			<?php
				} elseif($isDuplication) {
			?>
            <div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Duplicate A Past Order</span></div>
			<?php
				} elseif($isIntroduce) {
			?>
            <div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Introduce A Friend</span></div>
            <?php
				} else  {
			?>
            <div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Login / Register</span></div>
			<?php
				}
			?>
<div class="maincontent">
<div class="maincontent1">
			<?php
				if($isCheckout){
			?>
					<p>Are you an existing BLT Direct customer? Or, are you new to BLT Direct? Select the appropriate button below.</p>
			<?php
				} elseif($isReturns){
			?>
					<p>Our aim is to provide our customers with the highest level of service possible and this extends to our customer friendly policies.</p>
					<p>Please <strong><u>Do Not</u></strong> return any item to BLT Direct without first following our simple to use return procedure.</p>
					<p>Please note all returns are subject to section 8 of our Terms &amp; Conditions; <a href="terms.php#warranties" title="Terms &amp; Conditions: Warranties &amp; Returns">8. Warranties &amp; Returns</a>.</p>
					<p>Should you experience a problem at any stage of our returns procedure please contact our customer service team on 01473 559501 or alternatively by email at customerservices@bltdirect.com.</p>
					<p>If you have received damaged, faulty or incorrect items or have ordered incorrectly please login below.</p>
			<?php
				} elseif($isCancellation) {
			?>
					<p>If you would like to cancel entire orders or individual products, please login and navigate to the appropriate order.</p>

			<?php
				} elseif($isDuplication) {
			?>
					<p>If you would like to duplicate entire or partial orders, please login and navigate to the appropriate order.</p>


			<?php
				} elseif($isIntroduce) {
			?>
					<p>Introduce a friend and your friend will get a <?php print Setting::GetValue('customer_coupon_discount'); ?>% discount and you will earn a credit to <?php print Setting::GetValue('customer_coupon_discount'); ?>% of your friend's first order to spend against your future purchases with BLT Direct.</p>
					<p>Log in to get your coupon code. Just ask your friend to quote this coupon code in his or her purchase with us. The coupon will be valid until <?php print date('jS F Y', mktime(0, 0, 0, date('m'), date('d'), date('Y')+1)); ?>.</p>

            <?php
				} else  {
			?>
				<p>You are at a gateway to a secure area of our website. Please choose from one of the following options to proceed.</p>

			<?php
				}
			?>
			<?php
				if(!$login->Valid){
					echo $login->GetError();
					echo "<br>";
				}

				echo $login->Open();
				echo $login->GetHtml('action');
				echo $login->GetHtml('confirm');
				echo $login->GetHtml('direct');
			?>
			<!-- new stuff -->
            <div class="login_image">
			<div class="gatewayBox">
					<div class="title">Existing Customer</div>
					<div class="content"> 
                    <table width="100%">
                    <tr><td>                                    
					    <?php echo $login->GetLabel('username'); ?>:</td></tr>
                        <tr><td>
						<?php echo $login->GetHtml('username'); ?></td></tr>
                        <tr><td>
						<?php echo $login->GetLabel('password'); ?>:</td></tr>
                        <tr><td>
						<?php echo $login->GetHtml('password'); ?></td></tr>
                        </table>
                    </div>
					<p><input type="submit" value="Login" /></p>
				</div>
			</div>
            
			<?php echo $login->Close();
				if(!$isReturns && !$isCancellation && !$isDuplication) {
				?>
	<div class="register_img">
				<div class="gatewayBox">
						<div class="title">New Customer</div>
						<div class="content">
						<br /><p>New to BLT Direct? Create an account and benefit from future discount schemes.</p>
						</div>
<form action="register.php?direct=<?php echo $direct; ?>" method="post"><input type="submit" value="Continue" />
</form>

			</div>
                </div>
			<?php }
				if($isCheckout){
			 ?>
             <div class="express_img">
			<div id="expressCheckoutBox" class="gatewayBox">
					<div class="title">Express Checkout</div>
					<div class="content">
                        <br /><p>In a hurry? A faster way of checking out. Ideal for one-off sales.</p>
					</div>
                    <form action="register.php?express=true&amp;direct=<?php echo $direct; ?>" method="post"><input type="submit" value="Checkout" />
</form>
				</div>
            </div>
            			<?php } ?>
			<div class="clear"></div>
			<br />
			

				<?php
					if(!$assistant->Valid){
						echo $assistant->GetError();
						echo "<br>";
					}
					
					echo $assistant->Open();
					echo $assistant->GetHtml('action');
					echo $assistant->GetHtml('confirm');
					echo $assistant->GetHtml('direct');
				?>
				<div class="title"><font size="4px">Forgotten Password?</font></div>
					<div class="content"></div>
				<?php
				if(isset($_REQUEST['assistant']) && ($_REQUEST['assistant'] == 'successful')) {
					echo "<span class=\"alert\">Your password reset information has been sent to your email address.</span><br />";
				} else {
					if(!$assistant->Valid){
						echo "<span class=\"alert\">Sorry, we could not find your entry in our database.</span><br />";
						echo "<br>";
					} else {
						?>
				        <!-- <p>If you have forgotten your password don't worry. Simply enter your email address below and we'll send you your login details.</p> -->
				        <p><?php
					}
				}

				echo sprintf('%s : %s', $assistant->GetLabel('emailOrUser'), $assistant->GetHtml('emailOrUser')); ?></p>
				<p>
			              <input name="Continue" type="submit" class="submit" id="Continue" value="Continue" />
		                </p><br />
				<?php echo $assistant->Close(); ?>

</div>
</div>
<?php include("ui/footer.php");?>
<?php require_once('../lib/common/appFooter.php');