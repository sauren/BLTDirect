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
			$items[] = sprintf('<p><a href="returnorder.php"><strong>Return Online</strong><br />Register Return, Breakage or Not Received</a></p>');
			?>
			<div class="mainCustomer">
				<div class="orders">
					<span class="inline">
						<h3>All of your tickets </h3>
						<div class="info"></div>
					</span>
				  <p>Bellow is a list of the tickets that you have...</p><br />
					<!--<div class="infoText">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis leo magna, volutpat nec pulvinar ac, interdum ac diam. Phasellus non nibh lorem.</div> -->
					<div class="ticketsContainer">
					<table class="summaryTable" cellspacing="0">
						<tbody>
							<tr>
								<td>Today</td>
								<td width="408">Ticket #100010<br />
								<span class="hideOnHover">
								</span>
									<span class="numItems">
										<a href="#" class="showOnHover">
											<span class="icon new"></span>
											New
										</a>
										<a href="#" class="showOnHover">
											<span class="icon deleted"></span>
											Delete
										</a>
									</span>
								</td>
								<td></td>
								<td>£20.00</td>
								<td><div class="attentionIcon"></div></td>
						  </tr>
							<tr>
								<td>01/12/2011</td>
								<td width="150">Ticket #100009<br />
								<span class="hideOnHover">
								</span>
									<span class="numItems">
										<a href="#" class="showOnHover">
											<span class="icon new"></span>
											New
										</a>
										<a href="#" class="showOnHover">
											<span class="icon deleted"></span>
											Delete
										</a>
									</span>
								</td>
								<td></td>
								<td>£37.00</td>
								<td><div class="complete"></div></td>
							</tr>
							<tr>
								<td>23/11/2011</td>
								<td>Ticket #1000<br />
								<span class="hideOnHover">
								</span>
									<span class="numItems">
										<a href="#" class="showOnHover">
											<span class="icon new"></span>
											new
										</a>
										<a href="#" class="showOnHover">
											<span class="icon deleted"></span>
											Delete
										</a>
									</span>
								</td>
								<td></td>
								<td>£20.00</td>
								<td><div class="cancelled"></div></td>
							</tr>
							<tr>
								<td>10/11/2011</td>
								<td>Ticket #100007<br />
								<span class="hideOnHover">
								</span>
									<span class="numItems">
										<a href="#" class="showOnHover">
											<span class="icon new"></span>
											New
										</a>
										<a href="#" class="showOnHover">
											<span class="icon deleted"></span>
											Delete
										</a>
									</span>
								</td>
								<td></td>
								<td>£50.00</td>
								<td><div class="cancelled"></div></td>
							</tr>
							<tr>
								<td>Today</td>
								<td>Ticket #100006<br />
								<span class="hideOnHover">
								</span>
									<span class="numItems">
										<a href="#" class="showOnHover">
											<span class="icon new"></span>
											New
										</a>
										<a href="#" class="showOnHover">
											<span class="icon delete"></span>
											Delete
										</a>
									</span>
								</td>
								<td></td>
								<td>£5.00</td>
								<td><div class="processing"></div></td>
							</tr>
							<tr>
								<td>Today</td>
								<td>Ticket #100005<br />
								<span class="hideOnHover">
								</span>
									<span class="numItems">
										<a href="#" class="showOnHover">
											<span class="icon new"></span>
											New
										</a>
										<a href="#" class="showOnHover">
											<span class="icon deleted"></span>
											Delete
										</a>
									</span>
								</td>
								<td></td>
								<td>£20.00</td>
								<td><div class="attentionIcon"></div></td>
							</tr>
							<tr>
								<td>Today</td>
								<td>Ticket #100004<br />
								<span class="hideOnHover">
								</span>
									<span class="numItems">
										<a href="#" class="showOnHover">
											<span class="icon new"></span>
											New
										</a>
										<a href="#" class="showOnHover">
											<span class="icon deleted"></span>
											Delete
										</a>
									</span>
								</td>
								<td></td>
								<td>£20.00</td>
								<td><div class="complete"></div></td>								
							</tr>
							<tr>
								<td>Today</td>
								<td>Ticket #100003<br />
								<span class="hideOnHover">
								</span>
									<span class="numItems">
										<a href="#" class="showOnHover">
											<span class="icon new"></span>
											New
										</a>
										<a href="#" class="showOnHover">
											<span class="icon deleted"></span>
											Delete
										</a>
									</span>
								</td>
								<td></td>
								<td>£20.00</td>
								<td><div class="complete"></div></td>
						  </tr>
							<tr>
								<td>Today</td>
								<td>Ticket #100002<br />
								<span class="hideOnHover">
								</span>
									<span class="numItems">
										<a href="#" class="showOnHover">
											<span class="icon new"></span>
											New
										</a>
										<a href="#" class="showOnHover">
											<span class="icon deleted"></span>
											Delete
										</a>
									</span>
								</td>
								<td></td>
								<td>£20.00</td>
								<td><div class="attentionIcon"></div></td>
							</tr>
						</tbody>
					</table>
				  </div>
				</div>
			<div class="clear"></div>
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

