<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

$form = new Form($_SERVER['PHP_SELF']);
$form->Icons['valid'] = '';
$form->AddField('action', 'Action', 'hidden', 'send', 'alpha', 4, 4);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('title', 'Title', 'select', '', 'anything', 0, 20, false);
$form->AddOption('title', '', '');

$data = new DataQuery("SELECT * FROM person_title ORDER BY Person_Title ASC");
while($data->Row){
	$form->AddOption('title', $data->Row['Person_Title'], $data->Row['Person_Title']);

	$data->Next();
}
$data->Disconnect();

$form->AddField('fname', 'First Name', 'text', '', 'anything', 1, 60, false);
$form->AddField('lname', 'Last Name', 'text', '', 'anything', 1, 60, false);
$form->AddField('email', 'Email Address', 'text', '', 'email', NULL, NULL, true, 'style="width: 200px;"');
$form->AddField('format', 'Your Preferred Email Format', 'select', 'H', 'alpha', 1, 1);
$form->AddOption('format', 'H', 'HTML');
$form->AddOption('format', 'P', 'Plain Text');

if(strtolower(param('confirm', '')) == "true"){
	if($form->Validate()) {
		$emailAddress = trim($form->GetValue('email'));
		$data = new DataQuery(sprintf("SELECT c.Contact_ID FROM person AS p INNER JOIN contact AS c ON c.Person_ID=p.Person_ID WHERE Email LIKE '%s'", mysql_real_escape_string($emailAddress)));
		if($data->TotalRows > 0) {
			while($data->Row) {
				$contact = new Contact($data->Row['Contact_ID']);
				$contact->OnMailingList = $form->GetValue('format');
				$contact->Update();

				$data->Next();
			}
		} else {
			$customer = new Customer();
			$customer->Username = $form->GetValue('email');
			$customer->Contact->Type = 'I';
			$customer->Contact->Person->Title = $form->GetValue('title');
			$customer->Contact->Person->Name = addslashes($form->GetValue('fname'));
			$customer->Contact->Person->LastName = addslashes($form->GetValue('lname'));
			$customer->Contact->Person->Email = $form->GetValue('email');
			$customer->Contact->OnMailingList = $form->GetValue('format');
			$customer->Contact->Add();
			$customer->Add();
		}

		redirect(sprintf("Location: %s?subscribed=true", $_SERVER['PHP_SELF']));
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>BLT Direct</title>
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
		<?php if(param('subscribed')){ ?>
			   <h1>You have Subscribed to BLT Direct</h1>
			  <p>Your email address has been added to our newsletter mailing list.</p>
			  <p><a href="./index.php">Visit our homepage</a></p>
		<?php } else { ?>
              <h1>Subscribe to BLT Direct</h1>
			  <p>Subscribe to our e-newsletter and get amazing light bulbs, lamp and tube offers and discounts delivered direct to your inbox. Keep up to date with new lighting technology emerging to help save on your electricity bill and more. </p>
			  <p>To subscribe please enter your email address in the field below and press the yes button.</p>

		<?php
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}
		echo $form->Open();
		echo $form->GetHtml('action');
		echo $form->GetHtml('confirm');
		?>
			<table width="100%" cellspacing="0" class="form">
				<tr>
					<th colspan="4">Your Subscription Details</th>
				</tr>
				<tr>
					<td width="28%">Full Name</td>
					<td colspan="3">
						<table border="0" cellspacing="0" cellpadding="0">
							<tr>
								<td><?php echo $form->GetLabel('title'); ?> <?php echo $form->GetIcon('title'); ?><br />
								<?php echo $form->GetHtml('title'); ?> </td>
								<td><?php echo $form->GetLabel('fname'); ?> <?php echo $form->GetIcon('fname'); ?><br />
								<?php echo $form->GetHtml('fname'); ?> </td>
								<td><?php echo $form->GetLabel('lname'); ?> <?php echo $form->GetIcon('lname'); ?><br />
								<?php echo $form->GetHtml('lname'); ?></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td width="28%"><?php echo $form->GetLabel('email'); ?></td>
					<td colspan="3"><?php echo $form->GetHtml('email'); ?><?php echo $form->GetIcon('email'); ?></td>
				</tr>
				<tr>
					<td width="28%"><?php echo $form->GetLabel('format'); ?></td>
					<td colspan="3"><?php echo $form->GetHtml('format'); ?><?php echo $form->GetIcon('format'); ?></td>
				</tr>
			</table><br />

			<input type="submit" name="Subscribe" value="Subscribe" class="submit" /><br />
		<?php
		echo $form->Close();
		}
		?>
		<br />
		<p>If you are already subscribed and would like to cancel your subscription, <a href="unsubscribe.php">click here</a>.</p>
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
