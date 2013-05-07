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
	$data = new DataQuery(sprintf("SELECT Contact_ID FROM contact WHERE Parent_Contact_ID=%d", $session->Customer->Contact->Parent->ID));
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
						<h3>Orders</h3>
					</span>
				  <p>Bellow is all your current orders</p><br />
					<div class="optionsNav">
						<ul>
							<li class="ord"><a href="orders.php">Orders</a></li>
							<li><a href="invoices.php">Invoices</a></li>
							<li><a href="quotes.php">Quotes</a></li>
					  </ul>
				  </div>
					<!--<div class="infoText">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis leo magna, volutpat nec pulvinar ac, interdum ac diam. Phasellus non nibh lorem.</div> -->
					
					<div class="ordersContainer">
					<table class="summaryTable" cellspacing="0">
						<tbody>
							
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

			$data = new DataQuery(sprintf("SELECT o.*, CONCAT_WS(' ', p2.Name_First, p2.Name_Last) AS Name FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN person AS p2 ON p2.Person_ID=n.Person_ID WHERE c.Contact_ID IN (%s) AND o.Is_Sample='N' AND o.Status NOT IN ('Incomplete', 'Unauthenticated') ORDER BY o.Order_ID DESC LIMIT 10", implode(', ', $contacts)));
			if($data->TotalRows == 0) {
					?>

					<tr>
						<td colspan="4" align="center">There are no orders available for viewing.</td>
				  </tr>

				  <?php
			} else {
				while($data->Row) {
					$status = ucfirst($data->Row['Status']);
					$numOfItems = $data->Row['Total_Lines'];				
					if($data->Row['Is_Declined'] == 'Y') {
						$status = '<strong style="color: #c60909;">Payment Error</strong>';	
					}
						$icon = '';
						if($status == 'Unread'){
							$icon = 'attentionIcon';
						} else if($status == 'Pending') {
							$icon = 'processing';
						} else if($status == 'Cancelled') {
							$icon = 'cancelled';
						} else if($status == 'Packing') {
							$icon = 'packing';
						} else if($status == 'Despatched') {
							$icon = 'despatched';
						} else if($status == 'Partially Despatched'){
							$icon = 'packing';
						}
					?>
					 <tr>
								<td><a href="orders.php?orderid=<?php echo $data->Row['Order_ID']; ?>"><?php echo fromNow($data->Row['Ordered_On']); ?></a></td>
								<td width="150"><a href="orders.php?orderid=<?php echo $data->Row['Order_ID']; ?>"><?php echo $data->Row['Order_Prefix'] . $data->Row['Order_ID']; ?></a><br />
									<span class="hideOnHover">
									<?php echo $numOfItems; ?> Items
									</span>
									<?php if($data->Row['Status'] =='Despatched'){?>
										<span class="numItems">
											<a href="return.php?orderid=<?php echo $data->Row['Order_ID']; ?>" class="showOnHover">
												<span class="icon return"></span>
												Return
											</a>
									<?php } ?>
											<a href="orders.php?orderid=<?php echo $data->Row['Order_ID']; ?>&amp;action=duplicate" class="showOnHover">
												<span class="icon duplicate"></span>
												Duplicate
											</a>
										</span>
								</td>
								<td>&pound;<?php echo number_format($data->Row['Total'], 2, '.', ','); ?></td>
								<td>
									<div class="icon <?php echo $icon; ?>"><br /><?php echo $status; ?></div>
								</td>
						  </tr>
					<?php
						$data->Next();
				}
			}
			?>
						</tbody>
					</table>
					<a href="orders.php"><div class="more">-- More --</div></a>
					</div>
				</div>
				<div class="returns">
					<span class="inline">
						<h3>Returns</h3>
					</span>
					<p>Use our fast and efficent customer services center 24 hours a day, 7 days a week. Inform us of Faulty, damaged, inccorently recieved and not receivied goods.</p>
					<div class="returnsContainer">
					<table class="summaryTable" cellspacing="0">
						<tbody>
							<tr>
								<td>Today</td>
								<td>Return #100010</td>
								<td></td>
								<td>£20.00</td>
								<td><div class="icon attentionIcon"></div></td>
							</tr>
						<tr>
							<td>01/12/2011</td>
							<td>Return #100009</td>
							<td></td>
							<td>£20.00</td>
							<td><div class="icon complete"></div></td>
						</tr>
						<tr class="hoverRegion">
							<td>01/12/2011</td>
							<td>Return #100009</td>
							<td></td>
							<td>£20.00</td>
							<td><div class="icon complete"></div></td>
						</tr>
						</tbody>
					</table>
					<a href="returnorder.php">
						<div class="more">
							<span class="icon largeMore"></span>
							Request a return Or Enquire into unrecieved goods.</div>
				  </div>
					</a>
				</div>
				
				<div class="enquiries">
					<span class="inline">
						<h3>Enquiries</h3>
					</span>
					<p>Bellow is all your current enquiries</p>
					<div class="enquiriesContainer">
						<table class="summaryTable" cellspacing="0">
							<tbody>

							<?php
						$data = new DataQuery(sprintf("SELECT e.*, et.Name, p2.Name_First, p2.Name_Last FROM enquiry AS e INNER JOIN enquiry_type AS et ON e.Enquiry_Type_ID=et.Enquiry_Type_ID INNER JOIN enquiry_line AS el ON el.Enquiry_ID=e.Enquiry_ID INNER JOIN customer AS c ON c.Customer_ID=e.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN person AS p2 ON p2.Person_ID=n.Person_ID WHERE el.Is_Public='Y' AND el.Is_Draft='N' AND ((n.Parent_Contact_ID>0 AND n.Parent_Contact_ID=%d) OR (n.Parent_Contact_ID=0 AND n.Contact_ID=%d)) GROUP BY e.Enquiry_ID ORDER BY e.Created_On DESC LIMIT 5", $session->Customer->Contact->Parent->ID, $session->Customer->Contact->ID));
						if($data->TotalRows == 0) {
							?>

							<tr>
								<td colspan="3" align="center">There are no enquiries available for viewing</td>
					 		</tr>

					  		<?php
						} else {
							while($data->Row){

					 		$enquiryStatus = '';
					 		$enquiryStatus = ucfirst($data->Row['Status']);
				 			$enquiryIcon = '';
							if($enquiryStatus == 'Open'){
							$enquiryIcon = 'openEnquiries';
							} else if($enquiryStatus == 'Closed') {
							$enquiryIcon = 'closedEnquiries';
							} else if ($enquiryStatus == 'Unread'){
								$enquiryIcon = 'unreadEnquiries';
							}
								?>

								<tr>
								 	<td><?php echo fromNow($data->Row['Created_On']); ?></td>
									<td>#<?php echo $data->Row['Enquiry_ID']; ?> - 
									<?php echo ((($data->Row['Status'] != 'Closed') && (($data->Row['Is_Pending_Action'] == 'N') || ($data->Row['Is_Requesting_Closure'] == 'Y'))) || (($data->Row['Status'] == 'Closed') && ($data->Row['Rating'] == 0))) ? '' : ''; ?><a href="enquiries.php?enquiryid=<?php echo $data->Row['Enquiry_ID']; ?>"><?php echo ucfirst($data->Row['Subject']); ?></a>
									<br />
										<span></span>
										<span class="numItems">
											<?php if($data->Row['Status'] !== 'Closed'){ ?>
												<a href="enquiries.php?enquiryid=<?php echo $data->Row['Enquiry_ID']; ?>" class="showOnHover">
												<span class="icon new"></span>
												Reply
											</a>
											<?php } ?>
											<?php if($data->Row['Is_Requesting_Closure'] == 'Y'){
												echo ((($data->Row['Status'] != 'Closed') && (($data->Row['Is_Pending_Action'] == 'N') || ($data->Row['Is_Requesting_Closure'] == 'Y'))) || (($data->Row['Status'] == 'Closed') && ($data->Row['Rating'] == 0))) ? '' : ''; ?><a href="enquiries.php?enquiryid=<?php echo $data->Row['Enquiry_ID']; ?>" class="showOnHover">
												<span class="icon deleted"></span>
												close
											</a>
											<?php } ?>
										</span>
									</td>
									<td>
										<div class="<?php echo $enquiryIcon; ?>"><br />
											<?php echo $enquiryStatus; ?>
										</div>
									</td>
								</tr>
								<?php
								$data->Next();
							}
						}
						?>
							</tbody>
						</table>
					<a href="enquiries.php"><div class="more">-- More --</div></a>
					</div>
				</div>
			<div class="clear"></div>
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