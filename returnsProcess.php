<?php
require_once('lib/common/appHeader.php');

$session->Secure();

$showAlert = false;

if($session->Customer->IsSecondaryActive == 'Y') {
	$session->Customer->IsSecondaryActive = 'N';
	$session->Customer->Update();

	$showAlert = true;
}

$contacts = array();

if($session->Customer->Contact->HasParent) {
	$data = new DataQuery(sprintf("SELECT Contact_ID FROM contact WHERE Parent_Contact_ID=%d", mysql_real_escape_string($session->Customer->Contact->Parent->ID)));
	while($data->Row) {
		$contacts[] = $data->Row['Contact_ID'];
		
		$data->Next();	
	}
	$data->Disconnect();
} else {
	$contacts[] = $session->Customer->Contact->ID;
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
              <h1>Welcome back <?php print $session->Customer->Contact->Person->Name; ?></h1>
              <p>It's great to see you again</p>

			<?php
			if($showAlert) {
				?>

				<table width="100%" border="0" cellpadding="0" cellspacing="0" class="alert">
					<tr>
						<td align="center">
							<br />
							<p><strong>Changes have been made to your account affecting the way in which you are required to log in.</strong></p>
							<p>From now on you will be prompted for your email address and password when logging in. If you forget your password you may request it through our forgotten password facility which will be sent to your chosen email address. Your email address for this account may be changed within your online <a href="profile.php">profile</a>.</p>
							<p>If you require further assistance please do not hesitate to <a href="support.php">contact us</a>.</p>
						</td>
					</tr>
				</table><br />

				<?php
			}
			?>

              <br />

			<?php
			if($session->Customer->Contact->IsEmailInvalid == 'Y') {
				echo '<span class="alert"><strong>Invalid Email Address</strong><br />Recent attempts to contact you via your email address have failed.<br />Please review and update, if necessary, your Profile with a valid email address through the below highlighted link or by <a href="/profile.php">clicking here</a>. Please note that this warning may appear for a valid email address if network problems prevent successful submission of an email to your account.</span>';
				echo '<br />';
			}
			
			$items = array();
			$items[] = sprintf('<p><a href="despatches.php"><strong>Return Online</strong><br />Register Return, Breakage or Not Received</a></p>');

				$queryString = 'type=';
				if(id_param('orderid')){
					$queryString = 'orderid=' . id_param('orderid') . '&type=';
				}
			?>
			<div class="mainCustomer">
				<div class="orders">
					<span class="inline">
					<?php
					$id = '';
					if(id_param('orderid')) {
						$id = id_param('orderid');
						}?>
						<h3>Raise a return for the order No. T<?php echo $id; ?><br /></h3>
					</span>
				<br />
				<p>Use our fast and efficient returns system 24 hours a day 7 days a week.</p>
				<br />
				<p>Please click on the most appropriate link below</p>
				<br />
				<a href="selectOrder.php?<?php echo $queryString; ?>incorrect">
					<p> - The goods I received were incorrect</p>
				</a>
				<br />
				<a href="selectOrder.php?<?php echo $queryString; ?>damaged">
					<p> - I received damaged or broken goods on arrival</p>
				</a>
				<br />
				<a href="selectOrder.php?<?php echo $queryString; ?>faulty">
					<p> - The goods I received were faulty</p>
				</a>
				<br />
				<a href="selectOrder.php?<?php echo $queryString; ?>missing">
					<p> - I haven't received my order</p>
				</a>
			<div class="clear"></div>
			<!--<div class="accountCenterLinks">
				<table width="100%">
					<tr>
						<td width="50%" valign="top">

							<?php
							for($i=0; $i<ceil(count($items)/2); $i++) {
								echo $items[$i];
							}
							?>

						</td>
						<td width="50%" valign="top">

							<?php
							for($i=ceil(count($items)/2); $i<count($items); $i++) {
								echo $items[$i];
							}
							?>

						</td>
					</tr>
				</table>

			</div>
			<div class="accountCenterLinks">

			<?php
			$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM customer_product AS cp INNER JOIN product AS p ON p.Product_ID=cp.Product_ID INNER JOIN customer AS c ON c.Customer_ID=cp.Customer_ID WHERE c.Contact_ID IN (%s)", implode(', ', $contacts)));
			$productCount = $data->Row['Count'];
			$data->Disconnect();
			
			$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM quote AS q INNER JOIN customer AS c ON c.Customer_ID=q.Customer_ID WHERE c.Contact_ID IN (%s)", implode(', ', $contacts)));
			$quoteCount = $data->Row['Count'];
			$data->Disconnect();

			$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID WHERE c.Contact_ID IN (%s) AND o.Is_Sample='N' AND o.Status NOT IN ('Incomplete', 'Unauthenticated')", implode(', ', $contacts)));
			$orderCount = $data->Row['Count'];
			$data->Disconnect();

			$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM invoice AS i INNER JOIN customer AS c ON c.Customer_ID=i.Customer_ID WHERE c.Contact_ID IN (%s)", implode(', ', $contacts)));
			$invoiceCount = $data->Row['Count'];
			$data->Disconnect();

			$items = array();
			
			if($session->Customer->Contact->IsTradeAccount == 'N') {
				$items[] = sprintf('<p><a href="index.php"><strong>My Home Page</strong><br />View our best selling products, your bulbs and any special offers.</a></p>');
				$items[] = sprintf('<p><a href="introduce.php"><strong>Introduce A Friend</strong><br />Introduce your friends to receive discount rewards.</a></p>');
			}
			
			$items[] = sprintf('<p><a href="bulbs.php"><strong>My Bulbs</strong> (%d)<br />View Past Products/Locations | Favourite Products</a></p>', $productCount);
			$items[] = sprintf('<p><a href="quotes.php"><strong>My Quotes</strong> (%d)<br />View New/Open Quotes | List All Past Quotes</a></p>', $quoteCount);
			$items[] = sprintf('<p><a href="orders.php"><strong>My Orders</strong> (%d)<br />View New/Open Orders | List All Past Orders</a></p>', $orderCount);

			$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID WHERE c.Contact_ID IN (%s) AND o.Is_Sample='Y'", implode(', ', $contacts)));
			if($data->Row['Count'] > 0) {
				$items[] = sprintf('<p><a href="samples.php"><strong>My Samples</strong> (%d)<br />View New/Open Samples | List All Past Samples</a></p>', $data->Row['Count']);
			}
			$data->Disconnect();

			$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM coupon AS co INNER JOIN customer AS c ON co.Owned_By=c.Customer_ID WHERE c.Contact_ID IN (%s)", implode(', ', $contacts)));
			if($data->Row['Count'] > 0) {
				$items[] = sprintf('<p><a href="coupons.php"><strong>My Coupons</strong> (%d)<br />View Current Coupons</a></p>', $data->Row['Count']);
			}
			$data->Disconnect();

			$items[] = sprintf('<p><a href="invoices.php"><strong>My Invoices</strong> (%d)<br />View New Invoices | List All Past Invoices</a></p>', $invoiceCount);

			if($session->Customer->IsAffiliate == 'Y') {
				$items[] = sprintf('<p><a href="affiliate.php"><strong>Affiliate Information</strong><br />View Affiliate Information | List All Affiliate Statistics</a></p>', $invoiceCount);
			}

			$items[] = sprintf('<p><a href="enquiries.php"><strong>Enquiry Centre</strong><br />Contact our enquiry centre for all your sales needs.</a></p>');
			$items[] = sprintf('<p><a href="eNotes.php"><strong>Order Notes</strong><br />Send us a message regarding returns, errors, etc</a></p>');
			$items[] = sprintf('<p><a href="duplicate.php"><strong>Duplicate A Past Order</strong><br />Duplicate New/Open Orders</a></p>');
			$items[] = sprintf('<p><a href="returns.php"><strong>Returns</strong><br />Return Damaged, Faulty or Incorrect Goods</a></p>');
			$items[] = sprintf('<p><a %s href="profile.php"><strong>My Profile</strong><br />View/Edit my contact details</a></p>', ($session->Customer->Contact->IsEmailInvalid == 'Y') ? 'style="background-color: #FDFFA8;"' : '');

			if($session->Customer->Contact->HasParent){
				$items[] = sprintf('<p><a href="businessProfile.php"><strong>My Business Profile</strong><br />View/Edit my business details </a></p>');
			}

			$items[] = sprintf('<p><a href="changePassword.php"><strong>Change Password</strong><br />Worried about your security, or if you just keep forgetting your password you can change it here.</a></p>');
			?>

			<table width="100%">
				<tr>
					<td width="50%" valign="top">

						<?php
						for($i=0; $i<ceil(count($items)/2); $i++) {
							echo $items[$i];
						}
						?>

					</td>
					<td width="50%" valign="top">

						<?php
						for($i=ceil(count($items)/2); $i<count($items); $i++) {
							echo $items[$i];
						}
						?>

					</td>
				</tr>
			</table> -->

              </div><!-- InstanceEndEditable -->
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
