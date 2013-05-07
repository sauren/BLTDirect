<?php
	require_once('lib/common/appHeader.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');

	if($GLOBALS['USE_SSL'] && ($_SERVER['SERVER_PORT'] != $GLOBALS['SSL_PORT'])){
		$url = ($GLOBALS['USE_SSL'])?$GLOBALS['HTTPS_SERVER']:$GLOBALS['HTTP_SERVER'];
		$self = substr($_SERVER['PHP_SELF'], 1);
		$qs = '';
		if(!empty($_SERVER['QUERY_STRING'])){$qs = '?' . $_SERVER['QUERY_STRING'];}
		redirect(sprintf("Location: %s%s%s", $url, $self, $qs));
	}

	$email = 'sales@bltdirect.com';

	$form = new Form($_SERVER['PHP_SELF']);
	$form->Icons['valid'] = '';
	$form->AddField('action', 'Action', 'hidden', 'send', 'alpha', 4, 4);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

	$form->AddField('title', 'Title', 'select', 'Mr', 'alpha', 1, 4);
	$title = new DataQuery("select * from person_title order by Person_Title");
	while($title->Row){
		$form->AddOption('title', $title->Row['Person_Title'], $title->Row['Person_Title']);
		$title->Next();
	}
	$title->Disconnect();
	unset($title);

	$form->AddField('fname', 'First Name', 'text', '', 'alpha_numeric', 1, 60);
	$form->AddField('lname', 'Last Name', 'text', '', 'alpha_numeric', 1, 60);

	$form->AddField('email', 'Your Email Address', 'text', '', 'email', NULL, NULL);
	$form->AddField('phone', 'Daytime Phone', 'text', '', 'telephone', NULL, NULL, false);

	$form->AddField('message', 'Message', 'textarea', '', 'paragraph', 1, 2000, true, 'style="width:90%; height:150px;"');

	$form->AddField('foundVia', 'How did you find us?', 'select', '0', 'numeric_unsigned', 1, 11);
	$found = new DataQuery("select * from customer_found_via order by Found_Via");
	while($found->Row){
		$form->AddOption('foundVia', $found->Row['Found_Via_ID'], $found->Row['Found_Via']);
		$found->Next();
	}
	$found->Disconnect();

	$confirmPassError  =  "";
	$userError = "";
	$emailError = "";
	if(strtolower(param('confirm', '')) == "true"){
		if($form->Validate()) {
			$findReplace = new FindReplace;
			$findReplace->Add('/\[TITLE\]/', $form->GetValue('title'));
			$findReplace->Add('/\[FNAME\]/', $form->GetValue('fname'));
			$findReplace->Add('/\[LNAME\]/', $form->GetValue('lname'));
			$findReplace->Add('/\[EMAIL\]/', $form->GetValue('email'));
			$findReplace->Add('/\[PHONE\]/', $form->GetValue('phone'));
			$findReplace->Add('/\[MESSAGE\]/', $form->GetValue('message'));

			// Replace Order Template Variables
			$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_job.tpl");
			$orderHtml = "";
			for($i=0; $i < count($orderEmail); $i++){
				$orderHtml .= $findReplace->Execute($orderEmail[$i]);
			}

			unset($findReplace);
			$findReplace = new FindReplace;
			$findReplace->Add('/\[BODY\]/', $orderHtml);
			$findReplace->Add('/\[NAME\]/', 'BLT Direct');
			// Get Standard Email Template
			$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
			$emailBody = "";
			for($i=0; $i < count($stdTmplate); $i++){
				$emailBody .= $findReplace->Execute($stdTmplate[$i]);
			}

			$mail = new htmlMimeMail5();
			$mail->setFrom($GLOBALS['EMAIL_FROM']);
			$mail->setSubject(sprintf("%s Job Query [%s]", $GLOBALS['COMPANY'], $subject));
			$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
			$mail->setHTML($emailBody);
			$mail->send(array($email));

			redirect("Location: thanks.php");
		}
	}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>BLT Direct - Job Opportunities</title>
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
              <h1>BLT Direct Job Opportunities</h1>
              <p class="breadCrumb"><a href="#">Home</a></p>
              <p>If you work or have worked within the lighting industry and you think you can bring skills to our company either in product ranges or sales why not drop us an email telling us about you.</p>
              <p>We are also looking for companies and individuals to ship products for us in other european countries.</p>

			  <?php
				if(!$form->Valid){
					echo $form->GetError();
					echo "<br>";
				}
				echo $form->Open();
				echo $form->GetHtml('action');
				echo $form->GetHtml('confirm');
			?>

			  <table width="100%" border="0" cellpadding="0" cellspacing="0" class="bluebox">
                <tr>
                  <td><h3 class="blue">Contact Us Form </h3>
                  <p class="blue">Please complete the fields below. Required fields are marked with an asterisk (*).</p>

				  <table border="0" cellspacing="0" cellpadding="5">
					<tr>
					  <td>Title<?php echo $form->GetIcon('title'); ?><br /><?php echo $form->GetHTML('title'); ?></td>
					  <td>First Name<?php echo $form->GetIcon('fname'); ?> <br /><?php echo $form->GetHTML('fname'); ?></td>
					  <td>Last Name<?php echo $form->GetIcon('lname'); ?> <br /><?php echo $form->GetHTML('lname'); ?></td>
					</tr>
				  </table>
				  <br />
				  <p>Email Address<?php echo $form->GetIcon('email'); ?><br /><?php echo $form->GetHTML('email'); ?></p>
				  <p>Phone<?php echo $form->GetIcon('phone'); ?><br /><?php echo $form->GetHTML('phone'); ?></p>
				  <p>Your Message to Us<?php echo $form->GetIcon('message'); ?><br /><?php echo $form->GetHTML('message'); ?></p>
				  <p><input name="Send" type="submit" class="submit" id="Send" value="Send" /></p>


				  </td>
                </tr>
              </table>

			  <?php echo $form->Close(); ?>
              <br />

        <h3>Contacting Us Directly  </h3>
              <p>If you prefer to contact us directly by phone, fax or post please find our details below: </p>
              <p>BLT Direct,<br />
                Unit 9, The Quadrangle, <br />
                The Drift, Nacton Road,<br />
        Ipswich, Suffolk IP3 9QR</p>
              <ul>
                <li>Tel <?php echo Setting::GetValue('telephone_sales_hotline'); ?></li>
                <li>Fax 01473 718128 </li>
              </ul>
              <p>&nbsp; </p>
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
