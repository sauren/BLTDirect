<?php
require_once('lib/common/appHeader.php');

$session->Secure();

if($session->Customer->IsAffiliate == 'N') {
	redirect(sprintf("Location: becomeAffiliate.php"));
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Affiliate Centre</title>
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
				<h1>Affiliate Centre</h1>
				<p>Your affiliate statistics are detailed below.</p>

				<?php
				$period = id_param('period', 0);

				$start = date('Y-m-d', mktime(0, 0, 0, date('m') + $period, 1, date('Y')));
				$end = date('Y-m-d', mktime(0, 0, 0, date('m') + 1 + $period, 1, date('Y')));

		 		$data = new DataQuery(sprintf("SELECT COUNT(c.Coupon_ID) AS CouponCount, COUNT(DISTINCT o.Order_ID) AS SalesCount, SUM(o.SubTotal) AS SalesTotal, SUM((o.SubTotal / 100) * c.Commission_Amount) AS Commission FROM coupon AS c INNER JOIN customer AS cu ON c.Owned_By=cu.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=cu.Contact_ID LEFT JOIN orders AS o ON o.Coupon_ID=c.Coupon_ID AND o.Created_On>='%s' AND o.Created_On<'%s' AND o.Status LIKE 'Despatched' WHERE ((n.Parent_Contact_ID>0 AND n.Parent_Contact_ID=%d) OR (n.Parent_Contact_ID=0 AND n.Contact_ID=%d))", mysql_real_escape_string($start), mysql_real_escape_string($end), mysql_real_escape_string($session->Customer->Contact->Parent->ID), mysql_real_escape_string($session->Customer->Contact->ID)));
				if($data->Row['CouponCount'] > 0) {
					?>

					<h3>Coupons</h3>
					<p>Sales made using one of your uniquely assigned coupon codes.</p>

					<table cellspacing="0" class="homeProducts">
						<tr>
							<th style="text-align: left;"><a href="<?php print $_SERVER['PHP_SELF']; ?>?period=<?php print ($period - 1); ?>">Previous Month</a></th>
							<th style="text-align: right;"><a href="<?php print $_SERVER['PHP_SELF']; ?>?period=<?php print ($period + 1); ?>">Next Month</a></th>
						</tr>
						<tr>
							<td width="50%"><strong>Period</strong></td>
							<td><?php echo cDatetime($start, 'shortdate'); ?> - <?php print cDatetime($end, 'shortdate'); ?></td>
						</tr>
						<tr>
							<td><strong>Sales Count</strong></td>
							<td><?php echo $data->Row['SalesCount']; ?></td>
						</tr>
						<tr>
							<td><strong>Sales Total</strong></td>
							<td>&pound;<?php echo number_format($data->Row['SalesTotal'], 2, '.', ','); ?></td>
						</tr>
						<tr>
							<td><strong>Commission Rate</strong></td>
							<td><?php echo ($data->Row['Commission'] / $data->Row['SalesTotal']) * 100; ?>%</td>
						</tr>
						<tr>
							<td><strong>Commission Earnt</strong></td>
							<td>&pound;<?php echo number_format($data->Row['Commission'], 2, '.', ','); ?></td>
						</tr>
					</table><br />

					<?php
				}
				?>

				<h3>Web Links</h3>
				<p>These figures are derived from visits to our site through your unique affiliate reference tracking key.</p>

				<?php
				$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM customer_session WHERE Affiliate_ID=%d AND Created_On BETWEEN '%s' AND '%s'", mysql_real_escape_string($session->Customer->ID), mysql_real_escape_string($start), mysql_real_escape_string($end)));
				$clicks = $data->Row['Count'];
				$data->Disconnect();

				$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count, SUM(SubTotal) AS SubTotal FROM orders WHERE Affiliate_ID=%d AND Created_On BETWEEN '%s' AND '%s' AND Status!='Cancelled' AND Status NOT IN ('Incomplete', 'Unauthenticated')", mysql_real_escape_string($session->Customer->ID), mysql_real_escape_string($start), mysql_real_escape_string($end)));

				$salesCount = $data->Row['Count'];
				$salesTotal = $data->Row['SubTotal'];
				$data->Disconnect();
				?>
				<table cellspacing="0" class="homeProducts">
					<tr>
						<th style="text-align: left;"><a href="<?php print $_SERVER['PHP_SELF']; ?>?period=<?php print ($period - 1); ?>">Previous Month</a></th>
						<th style="text-align: right;"><a href="<?php print $_SERVER['PHP_SELF']; ?>?period=<?php print ($period + 1); ?>">Next Month</a></th>
					</tr>
					<tr>
						<td width="50%"><strong>Period</strong></td>
						<td><?php print cDatetime($start, 'shortdate'); ?> - <?php print cDatetime($end, 'shortdate'); ?></td>
					</tr>
					<tr>
						<td><strong>Click Throughs</strong></td>
						<td><?php print $clicks; ?></td>
					</tr>
					<tr>
						<td><strong>Sales Count</strong></td>
						<td><?php print $salesCount; ?></td>
					</tr>
					<tr>
						<td><strong>Sales Total</strong></td>
						<td>&pound;<?php print number_format($salesTotal, 2, '.', ','); ?></td>
					</tr>
					<tr>
						<td><strong>Commission Rate</strong></td>
						<td><?php print $session->Customer->AffiliateCommissionRate; ?>%</td>
					</tr>
					<tr>
						<td><strong>Commission Earnt</strong></td>
						<td>&pound;<?php print number_format(($salesTotal / 100) * $session->Customer->AffiliateCommissionRate, 2, '.', ','); ?></td>
					</tr>
				</table><br />

              <p>Use any of the following URLs to advertise with us which include your unique tracking reference.</p>

              <table cellspacing="0" class="catProducts">
              	<tr>
              		<th width="50%">HTML Code</th>
              		<th width="50%" class="center">Preview</th>
              	</tr>
              	<tr>
              		<td>
						<textarea style="width: 98%;" onclick="select();" rows="4"><a href="<?php print $GLOBALS['HTTP_SERVER']; ?>?trace=affiliate<?php print $session->Customer->ID; ?>" title="Light bulbs from BLT Direct">Light bulbs from BLT Direct</a></textarea>
           		  </td>
              		<td class="center">
						<a href="<?php print $GLOBALS['HTTP_SERVER']; ?>" title="Light bulbs from BLT Direct">Light bulbs from BLT Direct</a>
              		</td>
              	</tr>
              	<tr>
              		<td>
              			<textarea style="width: 98%;" onclick="select();" rows="4"><a href="<?php print $GLOBALS['HTTP_SERVER']; ?>?trace=affiliate<?php print $session->Customer->ID; ?>"><img src="<?php print $GLOBALS['HTTP_SERVER']; ?>images/logo_blt_2.gif" alt="Light bulbs from BLT Direct" title="Light bulbs from BLT Direct" width="146" height="57" /></a></textarea>
           		  </td>
              		<td class="center">
						<a href="<?php print $GLOBALS['HTTP_SERVER']; ?>"><img src="images/logo_blt_2.gif" alt="Light bulbs from BLT Direct" title="Light bulbs from BLT Direct" width="146" height="57" /></a>
              		</td>
              	</tr>
              </table><br />

					<h3>Agreement Documents</h3>
					<p>You must sign and return the appropriate agreement document for our records to advertise with BLT Direct.</p>

					<ul>
						<li><a href="javascript:popUp('affiliate_download.php?document=leaflet', 800, 600);">Agreement to advertise via leaflets &amp; discount codes.</a></li>
						<li><a href="javascript:popUp('affiliate_download.php?document=website', 800, 600);">Agreement to advertise via web services.</a></li>
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