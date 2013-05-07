<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

$session->Secure();

$isOrg = $session->Customer->Contact->HasParent;
$isUpdate = (isset($_REQUEST['status']) && $_REQUEST['status']=='update')?'true':'false'; 


  //param('status')=='update') ? true : false;

$form = new Form($_SERVER['PHP_SELF']);
$form->Icons['valid'] = '';
$form->AddField('action', 'Action', 'hidden', 'register', 'alpha', 8, 8);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('updatedetail', 'Update Details', 'hidden', $isUpdate, 'alpha', 0, 5);


$form->AddField('title', 'Title', 'select', $session->Customer->Contact->Person->Title, 'anything', 0, 20, false);
$form->AddOption('title', '', '');

$title = new DataQuery("select * from person_title order by Person_Title");
while($title->Row){
	$form->AddOption('title', $title->Row['Person_Title'], $title->Row['Person_Title']);
	$title->Next();
}
$title->Disconnect();

$form->AddField('fname', 'First Name', 'text', $session->Customer->Contact->Person->Name, 'name', 1, 60, true);
$form->AddField('iname', 'Initial', 'text', $session->Customer->Contact->Person->Initial, 'alpha', 1, 1, false, 'size="1"');
$form->AddField('lname', 'Last Name', 'text', $session->Customer->Contact->Person->LastName, 'name', 1, 60, true);

$form->AddField('email', 'E-mail Address', 'text', $session->Customer->GetEmail(), 'email', NULL, NULL);
$form->AddField('phone', 'Daytime Phone', 'text', $session->Customer->Contact->Person->Phone1, 'telephone', NULL, NULL, true);
$form->AddField('mobile', 'Mobile Phone', 'text', $session->Customer->Contact->Person->Mobile, 'telephone', NULL, NULL, false);

$form->AddField('address1', 'Property Name/Number', 'text', $session->Customer->Contact->Person->Address->Line1, 'address', 1, 150, true);
$form->AddField('address2', 'Street', 'text', $session->Customer->Contact->Person->Address->Line2, 'address', 1, 150, true);
$form->AddField('address3', 'Area', 'text', $session->Customer->Contact->Person->Address->Line3, 'address', 1, 150, false);
$form->AddField('city', 'City', 'text', $session->Customer->Contact->Person->Address->City, 'address', 1, 100, true);

$form->AddField('subscription', 'Newsletter subscription', 'radio', $session->Customer->Contact->OnMailingList, 'alpha', 1, 1, false);
$form->AddOption('subscription', 'N', 'I do not wish to subscribe');
$form->AddOption('subscription', 'P', 'I wish to receive newsletters in plain text.');
$form->AddOption('subscription', 'H', 'I wish to receive newsletters in HTML format.');

$form->AddField('country', 'Country', 'select', $session->Customer->Contact->Person->Address->Country->ID, 'numeric_unsigned', 1, 11, true, 'onChange="propogateRegions(\'region\', this);"');
$form->AddOption('country', '0', '');
$form->AddOption('country', '222', 'United Kingdom');

$data = new DataQuery("select * from countries order by Country asc");
while($data->Row){
	$form->AddOption('country', $data->Row['Country_ID'], $data->Row['Country']);
	$data->Next();
}
$data->Disconnect();

$regionCount = 0;
$countryId = $form->GetValue('country');
$region = new DataQuery(sprintf("select Region_ID, Region_Name FROM regions WHERE Country_ID=%d ORDER BY Region_Name ASC", mysql_real_escape_string($countryId)));
$regionCount = $region->TotalRows;
if($regionCount > 0){
	$form->AddField('region', 'Region', 'select', $session->Customer->Contact->Person->Address->Region->ID, 'numeric_unsigned', 1, 11, true);
	$form->AddOption('region', '0', '');
	while($region->Row){
		$form->AddOption('region', $region->Row['Region_ID'], $region->Row['Region_Name']);
		$region->Next();
	}
} else {
	$form->AddField('region', 'Region', 'select',  $session->Customer->Contact->Person->Address->Region->ID, 'numeric_unsigned', 1, 11, false, 'disabled="disabled"');
	$form->AddOption('region', '0', '');
}
$region->Disconnect();

$form->AddField('postcode', 'Postcode', 'text',  $session->Customer->Contact->Person->Address->Zip, 'postcode', 1, 10, false);
$form->AddField('solicitmobile', 'Mobile solicitation', 'checkbox', $session->Customer->Contact->IsSolicited(Contact::SOLICIT_MOBILE) ? 'Y' : 'N', 'boolean', 1, 1, false);

$emailError = '';


if(param('status')=='update'){
  $form->Validate();
}

if(strtolower(param('confirm', '')) == "true"){
	$form->Validate();

  if($form->GetValue('country') == 0){
    $form->AddError('You have yet to select a country.', 'country');
  }

  if($form->GetValue('region') == 0){
    $form->AddError('You have yet to select a region.', 'region');
  }


	if(($session->Customer->GetEmail() != $form->GetValue('email')) && !$session->Customer->IsEmailUnique($form->GetValue('email'))){
		$form->AddError('Your Email address already exists on our system.', 'email');
		$emailError = "<span class=\"alert\">Already exists in our database.</span>";
	}

	if($form->Valid){
		if($session->Customer->GetEmail() != $form->GetValue('email')) {
			$session->Customer->Contact->IsEmailInvalid = 'N';
		}

		$session->Customer->Contact->Person->Title = $form->GetValue('title');
		$session->Customer->Contact->Person->Name = $form->GetValue('fname');
		$session->Customer->Contact->Person->LastName = $form->GetValue('lname');
		$session->Customer->Contact->Person->Initial = $form->GetValue('iname');
		$session->Customer->Contact->Person->Phone1 = $form->GetValue('phone');
		$session->Customer->Contact->Person->Mobile = $form->GetValue('mobile');
		$session->Customer->Contact->Person->Email = $form->GetValue('email');
		$session->Customer->Contact->Person->Address->Line1 = $form->GetValue('address1');
		$session->Customer->Contact->Person->Address->Line2 = $form->GetValue('address2');
		$session->Customer->Contact->Person->Address->Line3 = $form->GetValue('address3');
		$session->Customer->Contact->Person->Address->City = $form->GetValue('city');
		$session->Customer->Contact->Person->Address->Country->ID = $form->GetValue('country');
		$session->Customer->Contact->Person->Address->Region->ID = $form->GetValue('region');
		$session->Customer->Contact->Person->Address->Zip = $form->GetValue('postcode');
		$session->Customer->Contact->Solicitation = 0;
		
		if($form->GetValue('solicitmobile') == 'Y') {
			$session->Customer->Contact->SetSolicitation(Contact::SOLICIT_MOBILE);
		}

		$session->Customer->Contact->OnMailingList = $form->GetValue('subscription');
		$session->Customer->Contact->Update();
		$session->Customer->Username = $form->GetValue('email');
		$session->Customer->Update();

    if(isset($_REQUEST['updatedetail']) && strtolower($_REQUEST['updatedetail']) == "true"){
      redirect("Location: checkout.php");
    }else{
		  redirect("Location: accountcenter.php");
    }
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Edit My Profile</title>
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
              <h1>My Profile </h1>
              <div id="orderConfirmation">
				<p class="breadCrumb"><a href="accountcenter.php">My Account</a> | <a href="introduce.php">Introduce A Friend</a> | <a href="bulbs.php">My Bulbs</a> | <a href="quotes.php">My Quotes</a> | <a href="orders.php">My Orders</a> | <a href="invoices.php">My Invoices</a> | <a href="enquiries.php">Enquiry Centre</a> | <a href="eNotes.php">Order Notes</a> | <a href="duplicate.php">Duplicate A Past Order</a> | <a href="returnorder.php">Returns</a> | <a href="profile.php">My Profile</a><?php if($session->Customer->Contact->HasParent){ ?> | <a href="businessProfile.php">My Business Profile</a><?php } ?> | <a href="changePassword.php">Change Password</a></p>
			</div><?php
      if(param('status')=='update'){ ?>
      <div class="detailNotification"> 
        <h1>Customer Details Missing</h1>
        <p>Dear Customer, <br/>
        You have been redirected back to your profile page as the details you have provided are incomplete or are invalid. Please ammend and save your changes to be able to proceed with your order.</p>
     </div>
     <br/>
		<?php }
      if(!$form->Valid){
				echo $form->GetError();
				echo "<br>";
			}

			echo $form->Open();
			echo $form->GetHtml('action');
			echo $form->GetHtml('confirm');
      echo $form->GetHtml('updatedetail');
			?>

			  <table width="100%" cellspacing="0" class="form">
                <tr>
                  <th colspan="2">Your Login Details</th>
                </tr>
                <tr>
                  <td colspan="2">Please remember your below e-mail address for the purposes of logging in.</td>
                </tr>
                <tr>
                  <td> <?php echo $form->GetLabel('email'); ?> </td>
                  <td> <?php echo $form->GetHtml('email'); ?> <?php echo $form->GetIcon('email'); ?> <?php echo $emailError; ?></td>
                </tr>
              </table>
              <p class="breadCrumb">&nbsp;</p>
              <table width="100%" cellspacing="0" class="form">
                <tr>
                  <th colspan="2">Your Contact Details</th>
                </tr>
                <tr>
                  <td width="28%"><?php echo $form->GetLabel('title'); ?></td>
                  <td> <?php echo $form->GetHtml('title'); ?><?php echo $form->GetIcon('title'); ?></td>
                </tr>
                <tr>
                  <td width="28%"><?php echo $form->GetLabel('fname'); ?></td>
                  <td> <?php echo $form->GetHtml('fname'); ?><?php echo $form->GetIcon('fname'); ?></td>
                </tr>
                <tr>
                  <td width="28%"><?php echo $form->GetLabel('iname'); ?></td>
                  <td> <?php echo $form->GetHtml('iname'); ?><?php echo $form->GetIcon('iname'); ?></td>
                </tr>
                <tr>
                  <td width="28%"><?php echo $form->GetLabel('lname'); ?></td>
                  <td> <?php echo $form->GetHtml('lname'); ?><?php echo $form->GetIcon('lname'); ?></td>
                </tr>
                <tr>
                  <td> <?php echo $form->GetLabel('phone'); ?> </td>
                  <td> <?php echo $form->GetHtml('phone'); ?> <?php echo $form->GetIcon('phone'); ?></td>
                </tr>
                <tr>
                  <td> <?php echo $form->GetLabel('mobile'); ?> </td>
                  <td><?php echo $form->GetHtml('mobile'); ?> <?php echo $form->GetIcon('mobile'); ?></td>
                </tr>
              </table>
              <p class="breadCrumb">&nbsp;</p>
              <table width="100%" cellspacing="0" class="form">
                <tr>
                  <th colspan="5">Your Address</th>
                </tr>
                <tr>
                  <td colspan="5">Please complete your address below. This must be the same as your credit card billing address. You can also change your billing address during the checkout process of any purchase. </td>
                </tr>
                <tr>
                  <td><?php echo $form->GetLabel('postcode'); ?> </td>
                  <td colspan="4"><?php echo $form->GetHtml('postcode'); ?> <?php echo $form->GetIcon('postcode'); ?>
                  <a href="javascript:getAddress();"><img src="images/searchIcon.gif" border="0" align="absmiddle" />
				  Auto-complete address (UK residents)</a> </td>
                </tr>
                <tr>
                  <td width="28%"><?php echo $form->GetLabel('address1'); ?> </td>
                  <td width="72%" colspan="4"><?php echo $form->GetHtml('address1'); ?> <?php echo $form->GetIcon('address1'); ?></td>
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
              <p class="breadCrumb">&nbsp;</p>
              <table width="100%" cellspacing="0" class="form">
                <tr>
                  <th colspan="5">Your Preferences </th>
                </tr>
                <tr>
				  <td colspan="5">You can personalise your account and
					services below.</td>
                </tr>
                <tr>
                  <td width="28%"><?php echo $form->GetHtml('subscription'); ?> </td>
				  <td width="72%" colspan="4">
					<table>
						<tr>
							<td><?php echo $form->GetHtml('subscription', 1); ?></td>
							<td><?php echo $form->GetLabel('subscription', 1); ?></td>
						</tr>
						<tr>
							<td><?php echo $form->GetHtml('subscription', 2); ?></td>
							<td><?php echo $form->GetLabel('subscription', 2); ?></td>
						</tr>
						<tr>
							<td><?php echo $form->GetHtml('subscription', 3); ?></td>
							<td><?php echo $form->GetLabel('subscription', 3); ?></td>
						</tr>
					</table>
				  </td>
                </tr>
                <tr>
                  <td><?php echo $form->GetLabel('solicitmobile'); ?> </td>
				  <td colspan="4"><?php echo $form->GetHtml('solicitmobile'); ?> <?php echo $form->GetIcon('solicitmobile'); ?></td>
                </tr>
              </table>
              <p>&nbsp;              </p>
              <p>
                <input name="Update" type="submit" class="submit" id="Update" value="Update" />
              </p>
              <p><?php echo $form->Close(); ?></p>
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
