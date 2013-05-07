<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Despatch.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLine.php');

$session->Secure();

$months = 24;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Return Online</title>
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
			<h1>Return Online</h1>
			<div id="orderConfirmation">
				<p class="breadCrumb"><a href="accountcenter.php">My Account</a> | <a href="introduce.php">Introduce A Friend</a> | <a href="bulbs.php">My Bulbs</a> | <a href="quotes.php">My Quotes</a> | <a href="orders.php">My Orders</a> | <a href="invoices.php">My Invoices</a> | <a href="enquiries.php">Enquiry Centre</a> | <a href="eNotes.php">Order Notes</a> | <a href="duplicate.php">Duplicate A Past Order</a> | <a href="returns.php">Returns</a> | <a href="profile.php">My Profile</a><?php if($session->Customer->Contact->HasParent){ ?> | <a href="businessProfile.php">My Business Profile</a><?php } ?> | <a href="changePassword.php">Change Password</a></p>
			</div>
			<p>You can start a return process by selecting one of your following recent despatches.</p>

			<?php
			if($action == 'returned') {
				$bubble = new Bubble('Return Raised', 'Your online return has been raised. Please be patient while we process your request. We aim to be back to you within 3 working hours.');

				echo $bubble->GetHTML();
				echo '<br />';			
			}
			?>

			<table cellspacing="0" class="myAccountOrderHistory">
				<tr>
					<th>Number</th>
					<th>Delivery Address</th>
					<th>Order</th>
					<th>Postage</th>
					<th style="text-align: right;">Despatched</th>
					<th style="text-align: right;">Your Expected Date</th>
					<th width="1%">&nbsp;</th>
				</tr>

				<?php
				$contacts = array();

				if($session->Customer->Contact->HasParent) {
					$data = new DataQuery(sprintf("SELECT Contact_ID FROM contact WHERE Parent_Contact_ID=%d", $session->Customer->Contact->Parent->ID));
					while($data->Row) {
						$contacts[] = $data->Row['Contact_ID'];
						
						$data->Next();	
					}
					$data->Disconnect();
				} else {
					$contacts[] = $session->Customer->Contact->ID;
				}

				$data = new DataQuery(sprintf("SELECT d.*, p.Postage_Title, p.Postage_Days FROM despatch AS d INNER JOIN orders AS o ON o.Order_ID=d.Order_ID INNER JOIN postage AS p ON p.Postage_ID=o.Postage_ID INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE c.Contact_ID IN (%s) ORDER BY d.Despatch_ID DESC", implode(', ', $contacts)));
				if($data->TotalRows > 0) {
					while($data->Row) {
						$siteAddress = array();

						if(!empty($data->Row['Despatch_Address_1'])) {
							$siteAddress[] = $data->Row['Despatch_Address_1'];
						}

						if(!empty($data->Row['Despatch_Address_2'])) {
							$siteAddress[] = $data->Row['Despatch_Address_2'];
						}

						if(!empty($data->Row['Despatch_City'])) {
							$siteAddress[] = $data->Row['Despatch_City'];
						}

						$thresholdMin = strtotime($data->Row['Despatched_On']) + ($data->Row['Postage_Days'] * 2 * 86400);
						$thresholdMax = mktime(date('H'), date('i'), date('s'), date('m')-$months, date('d'), date('Y'));
						?>

						<tr>
							<td><a href="javascript:popUp('despatch.php?despatchid=<?php echo $data->Row['Despatch_ID']; ?>', 800, 600);"><?php echo $data->Row['Despatch_ID']; ?></a></td>
							<td><?php echo implode(', ', $siteAddress); ?></td>
							<td><a href="orders.php?orderid=<?php echo $data->Row['Order_ID']; ?>"><?php echo $data->Row['Order_ID']; ?></a></td>
							<td><?php echo $data->Row['Postage_Title']; ?></td>
							<td align="right"><?php echo date('jS M Y', strtotime($data->Row['Despatched_On'])); ?></td>
							<td align="right"><?php echo date('jS M Y', strtotime($data->Row['Despatched_On']) + ($data->Row['Postage_Days'] * 86400)); ?></td>
							<td nowrap="nowrap">
								<?php
								if(($thresholdMin < time()) && ($thresholdMax < strtotime($data->Row['Despatched_On']))) {
									?>

									<input class="submit" type="button" value="Raise Return" onclick="window.self.location.href = 'returnonline.php?despatchid=<?php echo $data->Row['Despatch_ID']; ?>';" />

									<?php
								}
								?>

							</td>
						</tr>

						<?php
						$data->Next();
					}
				} else {
					?>

					<tr>
						<td colspan="7" align="center">There are no items available for viewing.</td>
				  </tr>

				  <?php
				}
				$data->Disconnect();
			?>

			</table>

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