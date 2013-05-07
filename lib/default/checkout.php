<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Checkout</title>
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

		Address.add('postcode', 'line1', 'address2');
		Address.add('postcode', 'line2', 'address3');
		Address.add('postcode', 'line3', null);
		Address.add('postcode', 'city', 'city');
		Address.add('postcode', 'county', 'region');
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
					<?php if($action == 'edit' || $action == 'editbilling'){ ?>
						<h1>Edit Address</h1>
						<p>Please complete the form below. Required fields are marked with an asterisk.</p>
					<?php } else if(param('type') == 'billing'){ ?>
						<h1>Billing Address</h1>
						<p>Please select your billing address from below. You may also add additional billing addresses, which will be kept for use later.</p>
					<?php } else { ?>
						<h1>Shipping Address</h1>
						<p>Please select your shipping address from below. You may also add additional shipping addresses, which will be kept for use later.</p>
					<?php } ?>

					<?php if(param('status')=='update'){ ?>
						<div class="detailNotification"> 
							<h1>Shipping Address Missing</h1>
							<br/>
							<p>You have been redirected back to this page as the shipping address seleted is incomplete or not valid. Please ammend and save the details to the correct format / required fields before proceeding with the order.</p>
						</div>
						<br/>
					<?php } ?>

					<?php if($action != "edit" && $action != 'editbilling'){ ?>
						<table class="checkoutSelectAddress" cellspacing="0">
						<?php
							$cart->Customer->GetContacts();
							for($i=0; $i<count($cart->Customer->Contacts->Line); $i++){
						?>
							<tr>
								<td nowrap="nowrap">
									<?php
										echo $cart->Customer->Contacts->Line[$i]->GetFullName();
										echo "<br />";
										echo $cart->Customer->Contacts->Line[$i]->Address->GetFormatted('<br />');
									?>
								</td>
								<td>
									<form action="summary.php" method="post">
										<input type="hidden" name="action" value="ship" />
										<input type="hidden" name="shipTo" value="<?php echo $cart->Customer->Contacts->Line[$i]->ID; ?>" />
										<input type="submit" class="submit" name="Select this Address" value="Select this Address" />
									</form>

									<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
										<input type="hidden" name="action" value="edit" />
										<input type="hidden" name="contact" value="<?php echo $cart->Customer->Contacts->Line[$i]->ID; ?>" />
										<input type="hidden" name="type" value="contact" />
										<input type="submit" class="greySubmit" name="Edit" value="Edit" />
									</form>

									<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
										<input type="hidden" name="action" value="remove" />
										<input type="hidden" name="contact" value="<?php echo $cart->Customer->Contacts->Line[$i]->ID; ?>" />
										<input type="hidden" name="type" value="contact" />
										<input type="submit" class="greySubmit" name="Remove" value="Remove" />
									</form>
								</td>
							</tr>
						<?php
							}
							$shippingCalc = new ShippingCalculator();
					 	?>
							<tr>
								<td nowrap="nowrap" class="billing">
									<?php
										if(empty($cart->Customer->Contact->ID)) $cart->Customer->Get();
										if(empty($cart->Customer->Contact->Person->ID)) $cart->Customer->Contact->Get();
										echo $cart->Customer->Contact->Person->GetFullName();
										if($cart->Customer->Contact->HasParent){
											echo "<br />";
											echo $cart->Customer->Contact->Parent->Organisation->Name;
										}
										echo "<br />";
										echo $cart->Customer->Contact->Person->Address->GetFormatted('<br />');
									?>
								</td>
								<td class="billing">
									<strong>This is your billing address.</strong><br />
									(You may not remove this address, but you can edit it.)<br/>
									<br />
									<form action="summary.php" method="post">
										<input type="hidden" name="shipTo" value="billing" />
										<input type="submit" class="submit" name="Select this Address" value="Select this Address" />
									</form>

									<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
										<input type="hidden" name="action" value="editBilling" />
										<input type="hidden" name="contact" value="" />
										<input type="hidden" name="type" value="<?php echo param('type', 'billing'); ?>" />
										<input type="submit" class="greySubmit" name="Edit" value="Edit" />
									</form>
								</td>
							</tr>
						</table>
						<br />
					<?php
					}

					if(!$form->Valid){
						echo $form->GetError();
						echo "<br>";
					}
					
					echo $form->Open();
					echo $form->GetHtml('action');
					echo $form->GetHtml('confirm');
					echo $form->GetHtml('contact');
					echo $form->GetHtml('type');
					?>
					<table width="100%" cellspacing="0" class="form">
						<tr>
							<th colspan="2"><?php echo $formTitle; ?> Address</th>
						</tr>
						<tr>
							<td><?php echo $form->GetLabel('title'); ?></td>
							<td><?php echo $form->GetHtml('title'); ?><?php echo $form->GetIcon('title'); ?></td>
						</tr>
						<tr>
							<td><?php echo $form->GetLabel('fname'); ?></td>
							<td><?php echo $form->GetHtml('fname'); ?><?php echo $form->GetIcon('fname'); ?></td>
						</tr>
						<tr>
							<td><?php echo $form->GetLabel('lname'); ?></td>
							<td><?php echo $form->GetHtml('lname'); ?><?php echo $form->GetIcon('lname'); ?></td>
						</tr>
						<?php if($action != "editbilling") { ?>
							<tr>
								<td><?php echo $form->GetLabel('oname'); ?></td>
								<td><?php echo $form->GetHtml('oname'); ?><?php echo $form->GetIcon('oname'); ?></td>
							</tr>
						<?php } ?>
						<tr>
							<td colspan="2">&nbsp;</td>
						</tr>
						<tr>
							<td><?php echo $form->GetLabel('postcode'); ?> </td>
							<td>
								<?php echo $form->GetHtml('postcode'); ?> <?php echo $form->GetIcon('postcode'); ?>
								<a href="javascript:getAddress();"><img src="images/searchIcon.gif" border="0" align="absmiddle" />
								Auto-complete address (UK residents)</a>
							</td>
						</tr>
						<tr>
							<td width="28%"><?php echo $form->GetLabel('address1'); ?> </td>
							<td width="72%"><?php echo $form->GetHtml('address1'); ?> <?php echo $form->GetIcon('address1'); ?></td>
						</tr>
						<tr>
							<td><?php echo $form->GetLabel('address2'); ?> </td>
							<td><?php echo $form->GetHtml('address2'); ?> <?php echo $form->GetIcon('address2'); ?></td>
						</tr>
						<tr>
							<td><?php echo $form->GetLabel('address3'); ?> </td>
							<td><?php echo $form->GetHtml('address3'); ?> <?php echo $form->GetIcon('address3'); ?></td>
						</tr>
						<tr>
							<td><?php echo $form->GetLabel('city'); ?> </td>
							<td><?php echo $form->GetHtml('city'); ?> <?php echo $form->GetIcon('city'); ?></td>
						</tr>
						<tr>
							<td><?php echo $form->GetLabel('country'); ?> </td>
							<td><?php echo $form->GetHtml('country'); ?> <?php echo $form->GetIcon('country'); ?></td>
						</tr>
						<tr>
							<td><?php echo $form->GetLabel('region'); ?> </td>
							<td><?php echo $form->GetHtml('region'); ?> <?php echo $form->GetIcon('region'); ?></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><input name="<?php echo $formTitle; ?>" type="submit" class="submit" id="<?php echo $formTitle; ?>" value="<?php echo $formTitle; ?>" /></td>
						</tr>
					</table>
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