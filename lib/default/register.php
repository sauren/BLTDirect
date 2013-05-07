<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>New Customer Registration</title>
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
   <script src="ignition/js/regions.php" type="text/javascript"></script>
	<script type="text/javascript" src="js/pcAnywhere.js"></script>
	<script type="text/javascript">
	function swapAccount(obj){
		var position = document.getElementById('position');
		var business = document.getElementById('business');
		var same = document.getElementById('asbusinessRow');
		var asBusinessBox = document.getElementById('asBusiness');
		var country = document.getElementById('country');
		
		if(obj.value == 'O'){
			position.style.display = '';
			business.style.display = '';
			same.style.display = '';
		} else {
			if(asBusinessBox.checked){
				asBusinessBox.checked = false;
				asbusiness(asBusinessBox);
			}
			country.disabled = false;
			position.style.display = 'none';
			business.style.display = 'none';

			same.style.display = 'none';
		}
	}

	jQuery(function($) {
		$(document).ready(function(){

			var address1 = $('#address1');
			var address2 = $('#address2');
			var address3 = $('#address3');
			var city = $('#city');
			var country = $('#country');
			var region = $('#region');
			var postcode = $('#postcode');

			var businessAddress1 = $('#businessaddress1');
			var businessAddress2 = $('#businessaddress2');
			var businessAddress3 = $('#businessaddress3');
			var businessCity = $('#businesscity');
			var businessCountry = $('#businesscountry');
			var businessRegion = $('#businessregion');
			var businessPostcode = $('#businesspostcode');

			var businessAddress1Changed = false;
			var businessAddress2Changed = false;
			var businessAddress3Changed = false;
			var businessCityChanged = false;
			var businessCountryChanged = false;
			var businessRegionChanged = false;
			var businessPostcodeChanged = false;


			address1.change(function(){
				if(!businessAddress1Changed && !businessAddress1.val()){
					businessAddress1.val(address1.val());
				}
			});
			
			businessAddress1.change(function(){
				businessAddress1Changed = true;
			});

			address2.change(function(){
				if(!businessAddress2Changed && !businessAddress2.val()){
					businessAddress2.val(address2.val());
				}
			});
			
			businessAddress2.change(function(){
				businessAddress2Changed = true;
			});

			address3.change(function(){
				if(!businessAddress3Changed && !businessAddress3.val()){
					businessAddress3.val(address3.val());
				}
			});
			
			businessAddress3.change(function(){
				businessAddress3Changed = true;
			});

			city.change(function(){
				if(!businessCityChanged && !businessCity.val()){
					businessCity.val(city.val());
				}
			});
			

			businessCity.change(function(){
				businessCityChanged = true;
			});

			country.change(function(){
				if(!businessCountryChanged){
					businessCountry.val(country.val());
					businessCountry.trigger("change");
				}
			});
			
			businessCountry.change(function(){
				businessCountryChanged = true;
			});

			
			region.change(function(){
				if(!businessRegionChanged){
					businessRegion.val(region.val());
				}
			});
			
			businessRegion.change(function(){
				businessRegionChanged = true;
			});


			postcode.change(function(){
				if(!businessPostcodeChanged && !businessPostcode.val()){
					businessPostcode.val(postcode.val());
				}
			});
			
			businessPostcode.change(function(){
				businessPostcodeChanged = true;
			});
		});
	});

	function asbusiness(obj){
		var businessAddress1 = document.getElementById('businessaddress1');
		var businessAddress2 = document.getElementById('businessaddress2');
		var businessAddress3 = document.getElementById('businessaddress3');
		var businessCity = document.getElementById('businesscity');
		var businessCountry = document.getElementById('businesscountry');
		var businessRegion = document.getElementById('businessregion');
		var businessPostcode = document.getElementById('businesspostcode');
		var address1 = document.getElementById('address1');
		var address2 = document.getElementById('address2');
		var address3 = document.getElementById('address3');
		var city = document.getElementById('city');
		var country = document.getElementById('country');
		var region = document.getElementById('region');
		var postcode = document.getElementById('postcode');


		if(obj.checked){
			address1.value = businessAddress1.value;
			address2.value = businessAddress2.value;
			address3.value = businessAddress3.value;
			city.value = businessCity.value;
			postcode.value = businessPostcode.value;

			country.options[businessCountry.selectedIndex].selected = true;
			propogateRegions('region', country);
			region.options[businessRegion.selectedIndex].selected = true;

			address1.disabled = true;
			address2.disabled = true;
			address3.disabled = true;
			city.disabled = true;
			country.disabled = true;
			region.disabled = true;
			postcode.disabled = true;

		} else {
			address1.disabled = false;
			address2.disabled = false;
			address3.disabled = false;
			city.disabled = false;
			country.disabled = false;
			region.disabled = false;
			postcode.disabled = false;

			if(address1.value == businessAddress1.value) address1.value = '';
			if(address2.value == businessAddress2.value) address2.value = '';
			if(address3.value == businessAddress3.value) address3.value = '';
			if(city.value == businessCity.value) city.value = '';
			if(postcode.value == businessPostcode.value) postcode.value = '';
		}
	}

	function getBusinessAddress() {
		var businessCountry = document.getElementById('businesscountry');

		if(businessCountry) {
			businessCountry.options.selectedIndex = 1;
			propogateRegions('businessregion', businessCountry);
			Address.find(document.getElementById('businesspostcode'));
		}
	}

	function getAddress() {
		var country = document.getElementById('country');

		if(country) {
			country.options.selectedIndex = 1;
			propogateRegions('region', country);
			Address.find(document.getElementById('postcode'));
		}
	}

	Address.account = '<?php echo $GLOBALS['POSTCODEANYWHERE_ACCOUNT']; ?>';
	Address.licence = '<?php echo $GLOBALS['POSTCODEANYWHERE_LICENCE']; ?>';

	Address.add('businesspostcode', 'line1', 'businessaddress2');
	Address.add('businesspostcode', 'line2', 'businessaddress3');
	Address.add('businesspostcode', 'line3', null);
	Address.add('businesspostcode', 'city', 'businesscity');
	Address.add('businesspostcode', 'county', 'businessregion');

	Address.add('postcode', 'line1', 'address2');
	Address.add('postcode', 'line2', 'address3');
	Address.add('postcode', 'line3', null);
	Address.add('postcode', 'city', 'city');
	Address.add('postcode', 'county', 'region');
	</script>


	<meta name="Keywords" content="light bulbs, light bulb, lightbulbs, lightbulb, lamps, fluorescent, tubes, osram, energy saving, sylvania, philips, ge, halogen, low energy, metal halide, candle, dichroic, gu10, projector, blt direct" />
	<meta name="Description" content="We specialise in supplying lamps, light bulbs and fluorescent tubes, Our stocks include Osram,GE, Sylvania, Omicron, Pro lite, Crompton, Ushio and Philips light bulbs, " />
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
			<h1>Your Details</h1>
			<p>Required fields are marked with an asterisk (*) and must be filled to complete your registration.</p>
			
			<?php
				if(!$form->Valid){
					echo $form->GetError();
					echo "<br>";
				}
			?>

			<?php if(strtolower(param('confirm', '')) == "true" && (!$assistant->Valid || !$login->Valid || !$customer->IsEmailUnique($form->GetValue('email')))){ ?>
				<div class="emailExistingWarning">
					<p><strong>Your email address already exists within our system.</strong><br />If you remember your Password you can login below, or use our Forgotten Password facility.</p>
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
					<div id="loginBox" class="gatewayBox">
						<div class="container">
							<div class="title">Existing Customer</div>
							<div class="content">
								<p><?php echo $login->GetLabel('username'); ?>:<br />
								<?php echo $login->GetHtml('username'); ?></p>
								<p><?php echo $login->GetLabel('password'); ?>:<br />
								<?php echo $login->GetHtml('password'); ?></p>
							</div>
							<input type="image" src="/images/login/blueBox_continue.gif" alt="Login" class="image" />
						</div>
					</div>
				<?php
					echo $login->Close();

					if(!$assistant->Valid){
						echo $assistant->GetError();
						echo "<br>";
					}
					
					echo $assistant->Open();
					echo $assistant->GetHtml('action');
					echo $assistant->GetHtml('confirm');
					echo $assistant->GetHtml('direct');
				?>
					<div style="float:left; margin:20px;">
						<h3>Forgotten Password?</h3>
						<?php
							if(isset($_REQUEST['assistant']) && ($_REQUEST['assistant'] == 'successful')) {
								echo "<span class=\"alert\">Your password reset information has been sent to your email address.</span><br />";
							} else {
								if(!$assistant->Valid){
									echo "<span class=\"alert\">Sorry, we could not find your entry in our database.</span><br />";
									echo "<br>";
								}
							}

							echo sprintf('%s : %s', $assistant->GetLabel('emailOrUser'), $assistant->GetHtml('emailOrUser'));
						?>			
						<input type="submit" class="greySubmit" name="continue" value="continue" />
					</div>
					<?php
						echo $assistant->Close();
					?>
					<div class="clear"></div>
				</div>
			<?php } ?>
			<div class="clear"></div>
			<br />

			<?php 
				echo $form->Open();
				echo $form->GetHtml('action');
				echo $form->GetHtml('confirm');
				echo $form->GetHtml('direct');
			?>

			<table style="width:100%" cellspacing="0" class="form">
              <tr>
<?php if($isExpress){ ?>
                <th style="width:100%;">Customer Type</th>
<?php } else { ?>
                <th style="width:100%;">Account Type</th>
<?php } ?>
              </tr>
              <tr>
                <td>
                  <label for="account">I am a </label>
				  <?php echo $form->GetHtml('account'); ?> customer.
				</td>
              </tr>
            </table>
			<br />

			<table style="width:100%" cellspacing="0" class="form">
				<tr>
					<th colspan="4">Your Login Details</th>
				</tr>
				<tr>
                  <td colspan="4">Please remember your below e-mail address for the purposes of logging in.</td>
				</tr>
				<tr>
				  <td style="width:28%;"> <?php echo $form->GetLabel('email'); ?> </td>
				  <td colspan="3"> <?php echo $form->GetHtml('email'); ?> <?php echo $form->GetIcon('email'); ?> <?php echo $emailError; ?></td>
				</tr>
			</table><br />


		<table style="width:100%;" cellspacing="0" class="form">
							<tr>
								<th colspan="4">Your Contact Details</th>
							</tr>
							<tr>
							  	<td style="width:28%;">Your Name </td>
								<td colspan="3"><table style="border:0px" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td><?php echo $form->GetLabel('title'); ?> <?php echo $form->GetIcon('title'); ?><br />
                                            <?php echo $form->GetHtml('title'); ?> </td>
                                        <td><?php echo $form->GetLabel('fname'); ?> <?php echo $form->GetIcon('fname'); ?><br />
                                            <?php echo $form->GetHtml('fname'); ?> </td>
                                        <td><?php echo $form->GetLabel('lname'); ?> <?php echo $form->GetIcon('lname'); ?><br />
                                            <?php echo $form->GetHtml('lname'); ?></td>
                                    </tr>
                                </table></td>
							</tr>
							<tr id="position" style="display:none">
								<td style="width:28%;"><?php echo $form->GetLabel('position'); ?></td>
								<td colspan="3"><?php echo $form->GetHtml('position'); ?><?php echo $form->GetIcon('position'); ?></td>
							</tr>
							<tr>
							  <td> <?php echo $form->GetLabel('phone'); ?> </td>
							  <td colspan="3"> <?php echo $form->GetHtml('phone'); ?> <?php echo $form->GetIcon('phone'); ?></td>
						  </tr>
			  </table>
			  <div  id="business" style="display:<?php echo ($isBusiness)?'block':'none';?>">
			  <br />
					<table style="width:100%;" cellspacing="0" class="form">
                      <tr>
                        <th colspan="5">Your Business Details </th>
                      </tr>
                      <tr>
                        <td colspan="5">You may add an alternative address for delivery during the order process.</td>
                      </tr>
                      <tr>
                        <td style="width:28%;"><?php echo $form->GetLabel('name'); ?> </td>
                        <td style="width:72%;" colspan="4"><?php echo $form->GetHtml('name'); ?> <?php echo $form->GetIcon('name'); ?></td>
                      </tr>
					  <tr>
                        <td><?php echo $form->GetLabel('businesspostcode'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('businesspostcode'); ?> <?php echo $form->GetIcon('businesspostcode'); ?>
						<a href="javascript:getBusinessAddress();"><img src="images/searchIcon.gif" border="0" align="absmiddle" />
						 Auto-complete address (UK residents)</a> </td>
                      </tr>
                      <tr>
                        <td style="width:28%;"><?php echo $form->GetLabel('businessaddress1'); ?> </td>
                        <td style="width:72%;" colspan="4"><?php echo $form->GetHtml('businessaddress1'); ?> <?php echo $form->GetIcon('businessaddress1'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('businessaddress2'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('businessaddress2'); ?> <?php echo $form->GetIcon('businessaddress2'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('businessaddress3'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('businessaddress3'); ?> <?php echo $form->GetIcon('businessaddress3'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('businesscity'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('businesscity'); ?> <?php echo $form->GetIcon('businesscity'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('businesscountry'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('businesscountry'); ?> <?php echo $form->GetIcon('businesscountry'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('businessregion'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('businessregion'); ?> <?php echo $form->GetIcon('businessregion'); ?></td>
					</tr>
					<tr>
						<td><?php echo $form->GetLabel('industry'); ?></td>
						<td><?php echo $form->GetHtml('industry'); ?> <?php echo $form->GetIcon('industry'); ?></td>
					</tr>
                    </table>
                    </div>

					<br />
					<table style="width:100%;" cellspacing="0" class="form">
                      <tr>
                        <th colspan="5">Your Credit Card Billing Address </th>
                      </tr>
                      <tr>
                        <td colspan="5">Please complete your address below. <b>This must be the same as your credit card billing address</b>. You may add an alternative address for delivery during the order process.</td>
                      </tr>
                      <tr id="asbusinessRow" style="display:<?php echo ($isBusiness)?'block':'none';?>">
                        <td style="width:28%;"><?php echo $form->GetLabel('asBusiness'); ?> </td>
                        <td style="width:72%;" colspan="4"><?php echo $form->GetHtml('asBusiness'); ?> <?php echo $form->GetIcon('asBusiness'); ?></td>
                      </tr>
					  <tr>
                        <td><?php echo $form->GetLabel('postcode'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('postcode'); ?> <?php echo $form->GetIcon('postcode'); ?>
						<a href="javascript:getAddress();"><img src="images/searchIcon.gif" border="0" align="absmiddle" />
						 Auto-complete address (UK residents)</a>
						</td>
                      </tr>
                      <tr>
                        <td style="width:28%;"><?php echo $form->GetLabel('address1'); ?> </td>
                        <td style="width:72%;" colspan="4"><?php echo $form->GetHtml('address1'); ?> <?php echo $form->GetIcon('address1'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('address2'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('address2'); ?> <?php echo $form->GetIcon('address2'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('address3'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('address3'); ?> <?php echo $form->GetIcon('address3'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('city'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('city'); ?> <?php echo $form->GetIcon('city'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('country'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('country'); ?> <?php echo $form->GetIcon('country'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('region'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('region'); ?> <?php echo $form->GetIcon('region'); ?></td>
                      </tr>

                    </table>

					<br />
				<?php
				if($isExpress){
					$form->AddField('express', 'express', 'hidden', 'true', 'alpha', 4, 4);
					echo $form->GetHtml('express');
				} else {
				?>
					    <table style="width:100%;" cellspacing="0" class="form">
							<tr>
								<th colspan="2">Your Security Information</th>
							</tr>
							<tr>
								<td colspan="2">Please complete the following fields for your personal security. </td>
							</tr>
							<tr>
							  <td><?php echo $form->GetLabel('password'); ?> (8 - 100 Alphanumeric Characters) <br />					          </td>
					  <td><?php echo $form->GetHtml('password'); ?> <?php echo $form->GetIcon('password'); ?></td>
						  </tr>
							<tr>
							  <td><?php echo $form->GetLabel('confirmPassword'); ?> <br />					          </td>
					  <td><?php echo $form->GetHtml('confirmPassword'); ?> <?php echo $form->GetIcon('confirmPassword')  . " ". $confirmPassError; ?></td>
						  </tr>
				</table>
<?php } ?>
			            <p>&nbsp;</p>
					    <p>
			              <?php echo $form->GetHtml('terms'); ?>
					   <label for="terms"> I have read and Accept the </label>
						<a href="terms.php" target="_blank">Terms and Conditions</a> of Use. <?php echo $form->GetIcon('terms'); ?></p>
						<p>
			              <?php echo $form->GetHtml('subscribe'); ?>
						  <label for="subscribe">I would like to subscribe to your newsletter (you can change your mind in your profile).</label>
						</p>
			            <p>
			              <input name="Continue" type="submit" class="submit" id="Continue" value="Continue" />
		                </p>
						<?php echo $form->Close(); ?>
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
<script>
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