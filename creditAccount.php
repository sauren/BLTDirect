<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLine.php');

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
	$form->AddField('email', 'Email Address', 'text', '', 'email', NULL, NULL);
	$form->AddField('phone', 'Phone', 'text', '', 'telephone', NULL, NULL);
	$form->AddField('subject', 'Subject', 'hidden', 'I am interested in obtaining a credit account.', 'anything', 1, 255);
	$form->AddField('company', 'Business Name', 'text', '', 'anything', 1, 255, false);
	$form->AddField('postcode', 'Postcode', 'text', '', 'postcode', 1, 10, false);
	$form->AddField('address1', 'Property Name/Number', 'text', '', 'anything', 1, 150, false);
	$form->AddField('address2', 'Street', 'text', '', 'anything', 1, 150, false);
	$form->AddField('address3', 'Area', 'text', '', 'anything', 1, 150, false);
	$form->AddField('city', 'City', 'text', '', 'anything', 1, 150, false);
	$form->AddField('country', 'Country', 'select', $GLOBALS['SYSTEM_COUNTRY'], 'numeric_unsigned', 1, 11, false, 'onchange="propogateRegions(\'region\', this);"');
	$form->AddOption('country', '0', '');
	$form->AddOption('country', '222', 'United Kingdom');

	$data = new DataQuery("SELECT * FROM countries ORDER BY Country ASC");
	while($data->Row) {
		$form->AddOption('country', $data->Row['Country_ID'], $data->Row['Country']);

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT Region_ID, Region_Name FROM regions WHERE Country_ID=%d ORDER BY Region_Name ASC", mysql_real_escape_string($form->GetValue('country'))));
	if($data->TotalRows > 0) {
		$form->AddField('region', 'Region', 'select', '', 'numeric_unsigned', 1, 11, false);
		$form->AddOption('region', '', '');

		while($data->Row){
			$form->AddOption('region', $data->Row['Region_ID'], $data->Row['Region_Name']);

			$data->Next();
		}
	} else {
		$form->AddField('region', 'Region', 'select', '', 'numeric_unsigned', 1, 11, false, 'disabled="disabled"');
		$form->AddOption('region', '', '');
	}
	$data->Disconnect();
}

$form->AddField('credit', 'Credit Amount', 'select', '', 'anything', 1, 32, true);
$form->AddOption('credit', '', '');
$form->AddOption('credit', '500-5000', '&pound;500 - &pound;5000');
$form->AddOption('credit', '5000-10000', '&pound;5000 - &pound;10000');
$form->AddOption('credit', '10000+', '&pound;10000+');
$form->AddField('message', 'Message', 'textarea', '', 'paragraph', 1, 16284, true, 'style="width:90%; height:150px;"');

if(strtolower(param('confirm', '')) == "true") {
	if($form->Validate()) {
		$typeId = 0;

		$data = new DataQuery(sprintf("SELECT Enquiry_Type_ID FROM enquiry_type WHERE Developer_Key LIKE 'customerservices'"));
		if($data->TotalRows > 0) {
			$typeId = $data->Row['Enquiry_Type_ID'];
		}
		$data->Disconnect();

		if($typeId == 0) {
			$data = new DataQuery(sprintf("SELECT Enquiry_Type_ID FROM enquiry_type WHERE Is_Public='Y' ORDER BY Enquiry_Type_ID ASC LIMIT 0, 1"));
			if ($data->TotalRows > 0) {
				$typeId = $data->Row['Enquiry_Type_ID'];
			}
			$data->Disconnect();
		}

		if(!$session->IsLoggedIn) {
			$emailAddress = trim(strtolower($form->GetValue('email')));
			$data = new DataQuery(sprintf("SELECT Customer_ID FROM customer WHERE Username LIKE '%s'", mysql_real_escape_string($emailAddress)));
			if($data->TotalRows > 0) {
				$session->Customer->ID = $data->Row['Customer_ID'];
			}
			$data->Disconnect();

			if($session->Customer->ID == 0) {
				$customer = new Customer();
				$customer->Username = trim(strtolower($form->GetValue('email')));
				$customer->Contact->Type = 'I';
				$customer->Contact->IsCustomer = 'Y';
				$customer->Contact->OnMailingList = 'H';
				$customer->Contact->Person->Title = $form->GetValue('title');
				$customer->Contact->Person->Name = $form->GetValue('fname');
				$customer->Contact->Person->LastName = $form->GetValue('lname');
				$customer->Contact->Person->Phone1 = $form->GetValue('phone');
				$customer->Contact->Person->Email  = $form->GetValue('email');
				$customer->Contact->Person->Address->Line1 = $form->GetValue('address1');
				$customer->Contact->Person->Address->Line2 = $form->GetValue('address2');
				$customer->Contact->Person->Address->Line3 = $form->GetValue('address3');
				$customer->Contact->Person->Address->City = $form->GetValue('city');
				$customer->Contact->Person->Address->Region->ID = $form->GetValue('region');
				$customer->Contact->Person->Address->Country->ID = $form->GetValue('country');
				$customer->Contact->Person->Address->Zip = $form->GetValue('postcode');
				$customer->Contact->Add();
				$customer->Add();

				if(strlen(trim($form->GetValue('company'))) > 0) {
					$contact = new Contact();
					$contact->Type = 'O';
					$contact->Organisation->Name = $form->GetValue('company');
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
		}

		$enquiry = new Enquiry();
		$enquiry->Type->ID = $typeId;
		$enquiry->Customer->ID = $session->Customer->ID;
		$enquiry->Status = 'Unread';
		$enquiry->Subject = $form->GetValue('subject');
		$enquiry->Add();

		$enquiryLine = new EnquiryLine();
		$enquiryLine->Enquiry->ID = $enquiry->ID;
		$enquiryLine->IsCustomerMessage = 'Y';
		$enquiryLine->Message = sprintf('<table width="100%%"><tr><td width="1%%" style="padding: 0 10px 0 0; white-space: nowrap;"><strong>Credit Required:</strong></td><td>&pound;%s</td></tr><tr><td width="1%%" style="padding: 0 10px 0 0; white-space: nowrap;"><strong>Message:</strong></td><td>%s</td></tr></table>', $form->GetValue('credit'), $form->GetValue('message'));
		$enquiryLine->Add();

		redirect(sprintf("Location: thanks.php%s", $direct));
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>BLT Direct - Become An Affiliate</title>
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
	<script type="text/javascript" src="js/pcAnywhere.js"></script>
	<script type="text/javascript" src="ignition/js/regions.php"></script>
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
              <h1>Credit Application</h1>
              <p class="breadCrumb"><a href="index.php">Home</a></p>

              <p>We offer credit accounts to people who spend regularly on light bulbs or lighting products. Please complete the following application form giving an annual estimate of your spend.</p>

			  <?php
			  if(!$form->Valid){
			  	echo $form->GetError();
			  	echo '<br />';
			  }

			  echo $form->Open();
			  echo $form->GetHtml('action');
			  echo $form->GetHtml('confirm');
			  echo $form->GetHtml('subject');
			?>

			  <h3>Contact Us Form </h3>
              <p>Please complete the fields below. Required fields are marked with an asterisk (*).</p>

			  <?php
			  if(!$session->IsLoggedIn) {
			  	?>

				<table width="100%" cellspacing="0" class="form">
					<tr>
						<th colspan="4">Your Contact Details</th>
					</tr>
					<tr>
					  	<td width="28%">Your Name</td>
						<td colspan="3">
							<table border="0" cellspacing="0" cellpadding="0">
							    <tr>
							        <td><?php echo $form->GetLabel('title'); ?> <?php echo $form->GetIcon('title'); ?><br /><?php echo $form->GetHtml('title'); ?></td>
							        <td><?php echo $form->GetLabel('fname'); ?> <?php echo $form->GetIcon('fname'); ?><br /><?php echo $form->GetHtml('fname'); ?></td>
							        <td><?php echo $form->GetLabel('lname'); ?> <?php echo $form->GetIcon('lname'); ?><br /><?php echo $form->GetHtml('lname'); ?></td>
							    </tr>
							</table>
						</td>
					</tr>
					<tr>
					  <td> <?php echo $form->GetLabel('company'); ?> </td>
					  <td colspan="3"> <?php echo $form->GetHtml('company'); ?> <?php echo $form->GetIcon('company'); ?></td>
				    </tr>
				    <tr>
					  <td> <?php echo $form->GetLabel('email'); ?> </td>
					  <td colspan="3"> <?php echo $form->GetHtml('email'); ?> <?php echo $form->GetIcon('email'); ?></td>
				    </tr>
				    <tr>
					  <td> <?php echo $form->GetLabel('phone'); ?> </td>
					  <td colspan="3"> <?php echo $form->GetHtml('phone'); ?> <?php echo $form->GetIcon('phone'); ?></td>
				    </tr>
				</table><br />

				  <table width="100%" cellspacing="0" class="form">
                      <tr>
                        <th colspan="5">Your Head Office Address</th>
                      </tr>
                      <tr>
                        <td colspan="5">Please complete your address below.</td>
                      </tr>
					  <tr>
                        <td width="28%"><?php echo $form->GetLabel('postcode'); ?> </td>
                        <td width="72%" colspan="4"><?php echo $form->GetHtml('postcode'); ?> <?php echo $form->GetIcon('postcode'); ?> <a href="javascript:getAddress();"><img src="images/searchIcon.gif" border="0" align="absmiddle" /> Auto-complete address (UK residents)</a></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('address1'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('address1'); ?> <?php echo $form->GetIcon('address1'); ?></td>
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
                    </table><br />

	                   <?php
				  }
				  ?>

 				<table width="100%" cellspacing="0" class="form">
                      <tr>
                        <th colspan="5">Your Credit Requirements</th>
                      </tr>
                      <tr>
                        <td width="28%"><?php echo $form->GetLabel('credit'); ?> </td>
                        <td width="72%" colspan="4"><?php echo $form->GetHtml('credit'); ?> <?php echo $form->GetIcon('credit'); ?></td>
                      </tr>
                      <tr>
                        <td width="28%"><?php echo $form->GetLabel('message'); ?> </td>
                        <td width="72%" colspan="4"><?php echo $form->GetHtml('message'); ?> <?php echo $form->GetIcon('message'); ?></td>
                      </tr>
                    </table><br />

				 <input name="Send" type="submit" class="submit" id="Send" value="Send" />

			  <?php echo $form->Close(); ?>
              <br /><br />

			  <h3>Contact Us Directly</h3>
              <p>If you prefer to contact us directly by phone, fax or post please find our details below: </p>
              <p>BLT Direct,<br />Unit 9, The Quadrangle, <br />The Drift, Nacton Road,<br />Ipswich, Suffolk IP3 9QR</p>
              <ul>
                <li><strong>Telephone</strong><br /><?php echo Setting::GetValue('telephone_sales_hotline'); ?> (Sales)<br /><?php echo  Setting::GetValue('telephone_customer_services'); ?> (Customer Service)<br />&nbsp;</li>
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
