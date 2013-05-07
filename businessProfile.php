<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

$session->Secure();

$form = new Form($_SERVER['PHP_SELF']);
$form->Icons['valid'] = '';

$form->AddField('action', 'Action', 'hidden', 'register', 'alpha', 8, 8);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

$form->AddField('department', 'Department', 'text', $session->Customer->Contact->Person->Department, 'anything', 1, 40, false);
$form->AddField('position', 'Position', 'text', $session->Customer->Contact->Person->Position, 'anything', 1, 100, false);
$form->AddField('name', 'Business Name', 'text', $session->Customer->Contact->Parent->Organisation->Name, 'anything', 1, 100);

$form->AddField('type', 'Business Type', 'select', $session->Customer->Contact->Parent->Organisation->Type->ID, 'numeric_unsigned', 1, 11, false);
$form->AddOption('type', '0', '');

$type = new DataQuery("select * from organisation_type order by Org_Type asc");
while($type->Row){
	$form->AddOption('type', $type->Row['Org_Type_ID'], $type->Row['Org_Type']);
	$type->Next();
}
$type->Disconnect();

$form->AddField('industry', 'Industry', 'select', $session->Customer->Contact->Parent->Organisation->Industry->ID, 'numeric_unsigned', 1, 11, false);
$form->AddOption('industry', '0', '');

$industry = new DataQuery("select * from organisation_industry order by Industry_Name asc");
while($industry->Row){
	$form->AddOption('industry', $industry->Row['Industry_ID'], $industry->Row['Industry_Name']);
	$industry->Next();
}
$industry->Disconnect();

$form->AddField('reg', 'Company Registration', 'text', $session->Customer->Contact->Parent->Organisation->CompanyNo, 'anything', 1, 50, false);

if(strtolower(param('confirm')) == "true"){
	$form->Validate();
	if($form->Valid){
		$session->Customer->Contact->Person->Department = $form->GetValue('department');
		$session->Customer->Contact->Person->Position = $form->GetValue('position');
		$session->Customer->Contact->Person->Update();

		$session->Customer->Contact->Parent->Organisation->Name = $form->GetValue('name');
		$session->Customer->Contact->Parent->Organisation->Type->ID = $form->GetValue('type');
		$session->Customer->Contact->Parent->Organisation->Industry->ID = $form->GetValue('industry');
		$session->Customer->Contact->Parent->Organisation->CompanyNo = $form->GetValue('reg');
		$session->Customer->Contact->Parent->Organisation->Update();

		redirect("Location: accountcenter.php");
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Customer Account Centre</title>
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
	<!-- InstanceBeginEditable name="head" -->    <!-- InstanceEndEditable -->
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
              <h1>My Business Profile</h1>
              <div id="orderConfirmation">
				<p class="breadCrumb"><a href="accountcenter.php">My Account</a> | <a href="introduce.php">Introduce A Friend</a> | <a href="bulbs.php">My Bulbs</a> | <a href="quotes.php">My Quotes</a> | <a href="orders.php">My Orders</a> | <a href="invoices.php">My Invoices</a> | <a href="enquiries.php">Enquiry Centre</a> | <a href="eNotes.php">Order Notes</a> | <a href="duplicate.php">Duplicate A Past Order</a> | <a href="returnorder.php">Returns</a> | <a href="profile.php">My Profile</a><?php if($session->Customer->Contact->HasParent){ ?> | <a href="businessProfile.php">My Business Profile</a><?php } ?> | <a href="changePassword.php">Change Password</a></p>
			</div><?php if($session->Customer->Contact->HasParent){ ?>
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
                  <th colspan="2">Your Business Profile</th>
                </tr>
                <tr>
                  <td width="50%" align="right"><?php echo $form->GetLabel('department'); ?></td>
                  <td><?php echo $form->GetHtml('department'); ?> <?php echo $form->GetIcon('department'); ?></td>
                </tr>
                <tr>
                  <td align="right"><?php echo $form->GetLabel('position'); ?></td>
                  <td><?php echo $form->GetHtml('position'); ?> <?php echo $form->GetIcon('position'); ?></td>
                </tr>
              </table>
              <br />
              <table width="100%" cellspacing="0" class="form">
                <tr>
                  <th colspan="2">Your Business Details </th>
                </tr>
                <tr>
                  <td width="50%" align="right"><?php echo $form->GetLabel('name'); ?></td>
                  <td><?php echo $form->GetHtml('name'); ?> <?php echo $form->GetIcon('name'); ?></td>
                </tr>
                <tr>
                  <td align="right"><?php echo $form->GetLabel('type'); ?></td>
                  <td><?php echo $form->GetHtml('type'); ?> <?php echo $form->GetIcon('type'); ?></td>
                </tr>
                <tr>
                  <td align="right"><?php echo $form->GetLabel('industry'); ?></td>
                  <td><?php echo $form->GetHtml('industry'); ?> <?php echo $form->GetIcon('industry'); ?></td>
                </tr>
                <tr>
                  <td align="right"><?php echo $form->GetLabel('reg'); ?></td>
                  <td><?php echo $form->GetHtml('reg'); ?> <?php echo $form->GetIcon('reg'); ?></td>
                </tr>
              </table>
              <p> <br />
                  <input name="Update" type="submit" class="submit" id="Update" value="Update" />
              </p>
              <?php
					echo $form->Close();
				} else {
					echo "<p>Sorry, you do not have a Business Account.</p>";
				}
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
