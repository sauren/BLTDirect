<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'packages/Securimage/securimage.php');

if($GLOBALS['USE_SSL'] && ($_SERVER['SERVER_PORT'] != $GLOBALS['SSL_PORT'])){
	$url = ($GLOBALS['USE_SSL'])?$GLOBALS['HTTPS_SERVER']:$GLOBALS['HTTP_SERVER'];
	$self = substr($_SERVER['PHP_SELF'], 1);
	$qs = '';
	if(!empty($_SERVER['QUERY_STRING'])){
		$qs = '?' . $_SERVER['QUERY_STRING'];
	}
	redirect(sprintf("Location: %s%s%s", $url, $self, $qs));
}

$form = new Form($_SERVER['PHP_SELF']);
$form->Icons['valid'] = '';
$form->AddField('action', 'Action', 'hidden', 'send', 'alpha', 4, 4);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

if(!$session->IsLoggedIn) {
	$captcha = new Securimage();
	
	$form->AddField('title', 'Title', 'select', '', 'anything', 0, 20, false);
	$form->AddOption('title', '', '');
	
	$title = new DataQuery("SELECT * FROM person_title ORDER BY Person_Title ASC");
	while($title->Row){
		$form->AddOption('title', $title->Row['Person_Title'], $title->Row['Person_Title']);
		
		$title->Next();
	}
	$title->Disconnect();

	$form->AddField('fname', 'First Name', 'text', '', 'anything', 1, 60, false);
	$form->AddField('lname', 'Last Name', 'text', '', 'anything', 1, 60, false);
	$form->AddField('email', 'Email Address', 'text', '', 'email', NULL, NULL);
	$form->AddField('phone', 'Phone', 'text', '', 'telephone', NULL, NULL);
	$form->AddField('bname', 'Business Name', 'text', '', 'anything', 1, 255, false);
	$form->AddField('code', 'Code', 'text', '', 'paragraph', 5, 5);
}

$form->AddField('type', 'Category', 'select', '', 'numeric_unsigned', 1, 11);
$form->AddOption('type', '', '');

$data = new DataQuery(sprintf("SELECT * FROM enquiry_type WHERE Is_Public='Y' ORDER BY Name ASC"));
while($data->Row) {
	$form->AddOption('type', $data->Row['Enquiry_Type_ID'], $data->Row['Name']);

	$data->Next();
}
$data->Disconnect();

$form->AddField('subject', 'Subject', 'text', '', 'anything', 1, 255);
$form->AddField('message', 'Message', 'textarea', '', 'paragraph', 1, 16284, true, 'style="width:90%; height:150px;"');

if(strtolower(param('confirm', '')) == "true") {
	if($form->Validate()){
		if(!$session->IsLoggedIn) {
			if(!$captcha->check($form->GetValue('code'))) {
				$form->AddError('Sorry, the form validation code entered was incorrect. Please try again.', 'code');
			}
		}

	    if($form->Valid) {
			$customerId = $session->Customer->ID;
			$direct = '';
			
			if(!$session->IsLoggedIn) {
				$customer = new Customer();
				$customerFound = false;
				$emailAddress = trim(strtolower($form->GetValue('email')));
				$data = new DataQuery(sprintf("SELECT Customer_ID FROM customer WHERE Username LIKE '%s'", mysql_real_escape_string($emailAddress)));
				if($data->TotalRows > 0) {
					if($customer->Get($data->Row['Customer_ID'])) {
						$customerFound = true;
					}
				}
				$data->Disconnect();

				if(!$customerFound) {
					$customer->Username = trim(strtolower($form->GetValue('email')));
					$customer->Contact->Type = 'I';
					$customer->Contact->IsCustomer = 'Y';
					$customer->Contact->Person->Title = $form->GetValue('title');
					$customer->Contact->Person->Name = $form->GetValue('fname');
					$customer->Contact->Person->LastName = $form->GetValue('lname');
					$customer->Contact->Person->Phone1 = $form->GetValue('phone');
					$customer->Contact->Person->Email  = $form->GetValue('email');
					$customer->Contact->OnMailingList = 'H';
					$customer->Contact->Add();
					$customer->Add();

					if(strlen(trim($form->GetValue('bname'))) > 0) {
						$contact = new Contact();
						$contact->Type = 'O';
						$contact->Organisation->Name = $form->GetValue('bname');
						$contact->Organisation->Type->ID = 0;
						$contact->Organisation->Email = $customer->GetEmail();
						$contact->Add();

						$customer->Contact->Parent->ID = $contact->ID;
						$customer->Contact->Update();
					}

					$session->Customer->ID = $customer->ID;
					$session->Update();

					$direct = '?newaccount=true';
				}
				
				$customerId = $customer->ID;
			}

			$enquiry = new Enquiry();
			$enquiry->Type->ID = $form->GetValue('type');
			$enquiry->Customer->ID = $customerId;
			$enquiry->Status = 'Unread';
			$enquiry->Subject = $form->GetValue('subject');
			$enquiry->Add();

			$enquiryLine = new EnquiryLine();
			$enquiryLine->Enquiry->ID = $enquiry->ID;
			$enquiryLine->IsCustomerMessage = 'Y';
			$enquiryLine->Message = $form->GetValue('message');
			$enquiryLine->Add();

			redirect(sprintf("Location: thanks.php%s", $direct));
		}
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>BLT Direct - Support</title>
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
              <h1>BLT Direct Support</h1>
              <p class="breadCrumb"><a href="#">Home</a></p>
              <p>Thank you for visiting BLT Direct. We hope that you've enjoyed your visit and have been able to find the products you require with ease. </p>
              <p>If you would like to find out more about the progress of an order placed on this site please consult <a href="accountcenter.php">My Account</a> before contacting us. For all other enquiries please use the form below:</p>
              <p>If you cannot find a product on our site, you can call us on the telephone number below or contact us through the form provided.</p>
              <p>If you have broken, faulty or incorrect goods please go to our <a href="returnorder.php">Returns</a> page.</p>

              <p><strong>Please note:</strong> Do not write to us or send emails to us containing any of your credit card details. We will delete any communications containing such details.</p>

			  <?php
			  if(!$form->Valid){
			  	echo $form->GetError();
			  	echo "<br>";
			  }
			  echo $form->Open();
			  echo $form->GetHtml('action');
			  echo $form->GetHtml('confirm');
			?>

			  <table style="width:100%; border:0px;" class="bluebox">
                <tr>
                  <td><h3 class="blue">Contact Us Form </h3>
                  <p class="blue">Please complete the fields below. Required fields are marked with an asterisk (*).</p>

                  <?php
					if(!$session->IsLoggedIn) {
						?>
						
					  <table style="border:0px; border-collapse:collapse; border-spacing:5px;">
						<tr>
						  <td>Title<?php echo $form->GetIcon('title'); ?><br /><?php echo $form->GetHTML('title'); ?></td>
						  <td>First Name<?php echo $form->GetIcon('fname'); ?> <br /><?php echo $form->GetHTML('fname'); ?></td>
						  <td>Last Name<?php echo $form->GetIcon('lname'); ?> <br /><?php echo $form->GetHTML('lname'); ?></td>
						</tr>
					  </table>
					  <br />
					  
				  <p>Business Name<?php echo $form->GetIcon('bname'); ?><br /><?php echo $form->GetHTML('bname'); ?></p>
				  <p>Email Address<?php echo $form->GetIcon('email'); ?><br /><?php echo $form->GetHTML('email'); ?></p>
				  <p>Phone<?php echo $form->GetIcon('phone'); ?><br /><?php echo $form->GetHTML('phone'); ?></p>
				  
				    <?php
					}
					?>
					
				  <p>Category<?php echo $form->GetIcon('type'); ?><br /><?php echo $form->GetHTML('type'); ?></p>
				  <p>Subject<?php echo $form->GetIcon('subject'); ?><br /><?php echo $form->GetHTML('subject'); ?></p>
				  <p>Your Message to Us<?php echo $form->GetIcon('message'); ?><br /><?php echo $form->GetHTML('message'); ?></p>
				  
				  
                  <?php
					if(!$session->IsLoggedIn) {
						?>
						
						<p>Form Validation Code<?php echo $form->GetIcon('code'); ?><br /><?php echo $form->GetHTML('code'); ?></p>
				  		<p>
				  			<span class="captcha">
								<img src="securimage.php" alt="Click to change image" onclick="this.src = 'securimage.php?sid=' + Math.random();" />
							</span>
							
							<object type="application/x-shockwave-flash" data="<?php echo rawurlencode('/ignition/packages/Securimage/securimage_play.swf?audio=/ignition/packages/Securimage/securimage_play.php&amp;bgColor1=#fff&amp;bgColor2=#fff&amp;iconColor=#777&amp;borderWidth=1&amp;borderColor=#000'); ?>" width="19" height="19">
								<param name="movie" value="<?php echo rawurlencode('/ignition/packages/Securimage/securimage_play.swf?audio=/ignition/packages/Securimage/securimage_play.php&amp;bgColor1=#fff&amp;bgColor2=#fff&amp;iconColor=#777&amp;borderWidth=1&amp;borderColor=#000'); ?>" />
						  </object>
					</p>
						
						<?php
					}
					?>
					
				  <p><input name="Send" type="submit" class="submit" id="Send" value="Send" /></p>

				  </td>
                </tr>
              </table>

			  <?php echo $form->Close(); ?>
              <br />

			  <h3>Contact Us Directly</h3>
              <p>If you prefer to contact us directly by phone, fax or post please find our details below: </p>
              <p>BLT Direct,<br />Unit 9, The Quadrangle,<br />The Drift, Nacton Road,<br />Ipswich, Suffolk IP3 9QR</p>
              <ul>
                <li><strong>Telephone</strong><br /><?php echo Setting::GetValue('telephone_sales_hotline'); ?> (Sales - Line Closes 10PM)<br /><?php echo  Setting::GetValue('telephone_customer_services'); ?> (Customer Service)<br />&nbsp;</li>
                <li><strong>Fax</strong><br />01473 718 128</li>
              </ul>
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
<?php include('lib/common/appFooter.php'); ?>