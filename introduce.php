<?php
	require_once('lib/common/appHeader.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Coupon.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Password.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

	$session->Secure();

	$coupon = new Coupon();

	$data = new DataQuery(sprintf("SELECT Coupon_ID FROM coupon WHERE Introduced_By=%d", mysql_real_escape_string($session->Customer->ID)));
	if($data->TotalRows > 0) {
		$coupon->Get($data->Row['Coupon_ID']);
	} else {
		$coupon->Reference = $coupon->GenerateReference();
		$coupon->Name = 'Introduce A Friend';
		$coupon->Description = 'Introduce A Friend';
		$coupon->Discount = Setting::GetValue('customer_coupon_discount');
		$coupon->IsFixed = 'N';
		$coupon->OrdersOver = Setting::GetValue('customer_coupon_orders_over');
		$coupon->UsageLimit = Setting::GetValue('customer_coupon_usage_limit');
		$coupon->IsAllProducts = 'Y';
		$coupon->IsActive = 'Y';
		$coupon->StaffOnly = 'N';
		$coupon->UseBand = 0;
		$coupon->IsAllCustomers = 'Y';
		$coupon->ExpiresOn = '0000-00-00 00:00:00';
		$coupon->IntroducedBy = $session->Customer->ID;
		$coupon->Add();
	}
	$data->Disconnect();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'send', 'alpha', 4, 4);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('email', 'Friends Email Address', 'text', '', 'email', 1, 255);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Introduce A Friend</title>
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
	<!-- InstanceBeginEditable name="head" --><!-- InstanceEndEditable -->
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
			<h1>Introduce A Friend</h1>
			<div id="orderConfirmation">
				<p class="breadCrumb"><a href="accountcenter.php">My Account</a> | <a href="introduce.php">Introduce A Friend</a> | <a href="bulbs.php">My Bulbs</a> | <a href="quotes.php">My Quotes</a> | <a href="orders.php">My Orders</a> | <a href="invoices.php">My Invoices</a> | <a href="enquiries.php">Enquiry Centre</a> | <a href="eNotes.php">Order Notes</a> | <a href="duplicate.php">Duplicate A Past Order</a> | <a href="returnorder.php">Returns</a> | <a href="profile.php">My Profile</a><?php if($session->Customer->Contact->HasParent){ ?> | <a href="businessProfile.php">My Business Profile</a><?php } ?> | <a href="changePassword.php">Change Password</a></p>
			</div>
			<p>We would like to thank you for your business to date with BLT Direct, now you can introduce friends and contacts to us and both benefit from the experience. Simply let your friends have the introduction coupon below which will discount their order by <?php print $coupon->Discount; ?>%. For each successfully placed order using your coupon you will receive discount reward proportional to that of your friends.</p>

			<p style="font-weight: bold; color: #0c0; font-size: 18px;"><?php print $coupon->Reference; ?></p>

			<h3>Discount Rewarded</h3>
			<p>You will receive the following discount off your next order. Any remaining discount reward will be carried over to subsequent orders.</p>
			<p style="font-weight: bold; color: #0c0; font-size: 18px;">&pound;<?php print number_format($session->Customer->AvailableDiscountReward, 2, '.', ','); ?></p>

			<h3>Introduced Friends</h3>
			<?php
			$data = new DataQuery(sprintf("SELECT Customer_ID, Ordered_On FROM orders WHERE Coupon_ID=%d AND Status='Despatched'", mysql_real_escape_string($coupon->ID)));
			if($data->TotalRows > 0) {
				echo '<p>Friends who have successfully placed orders using your introductory coupon:</p>';
				echo '<table cellspacing="0" class="catProducts">';
				echo '<tr>';
				echo '<th width="50%">Friend</th>';
				echo '<th width="50%">Ordered On</th>';
				echo '</tr>';

				while($data->Row) {
					$customer = new Customer($data->Row['Customer_ID']);
					$customer->Contact->Get();

					echo '<tr>';
					echo sprintf('<td>%s %s</td>', $customer->Contact->Person->Name, $customer->Contact->Person->LastName);
					echo sprintf('<td>%s</td>', cDatetime($data->Row['Ordered_On'], 'longdate'));
					echo '</tr>';

					$data->Next();
				}

				echo '</table>';
			} else {
				echo '<p>Non of your friends have used your discount coupon.</p>';
			}
			$data->Disconnect();
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