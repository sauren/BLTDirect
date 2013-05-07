<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLineDocument.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Quote.php');

$session->Secure();

if($action == 'new') {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->Icons['valid'] = '';
	$form->AddField('action', 'Action', 'hidden', 'new', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('subject', 'Subject', 'text', '', 'anything', 1, 255, true, 'style="width: 500px;"');
	$form->AddField('message', 'Enquiry', 'textarea', '', 'paragraph', 1, 16384, true, 'style="width: 500px;" rows="7"');
	$form->AddField('type', 'Category', 'select', '0', 'numeric_unsigned', 1, 11, true);
	$form->AddOption('type', '0', '');

	$additional = array();

	$data = new DataQuery(sprintf("SELECT * FROM enquiry_type WHERE Is_Public='Y' ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('type', $data->Row['Enquiry_Type_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(strtolower(param('confirm', '')) == "true") {
		if($form->GetValue('type') == 0) {
			$form->AddError('Category must have a selected value.', 'type');
		}

		if($form->Validate()) {
			$customer = new Customer();

			$enquiry = new Enquiry();
			$enquiry->Customer->ID = $session->Customer->ID;
			$enquiry->Status = 'Unread';
			$enquiry->Subject = $form->GetValue('subject');
			$enquiry->Type->ID = $form->GetValue('type');
			$enquiry->Add();

			$enquiryLine = new EnquiryLine();
			$enquiryLine->Enquiry->ID = $enquiry->ID;
			$enquiryLine->IsCustomerMessage = 'Y';
			$enquiryLine->Message = $form->GetValue('message');
			$enquiryLine->Add();

			redirect(sprintf("Location: enquiries.php?enquiryid=%d", $enquiry->ID));
		}
	}
} else {
	if(id_param('enquiryid')) {
		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM enquiry AS e INNER JOIN customer AS c ON c.Customer_ID=e.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE ((n.Parent_Contact_ID>0 AND n.Parent_Contact_ID=%d) OR (n.Parent_Contact_ID=0 AND n.Contact_ID=%d)) AND e.Enquiry_ID=%d", mysql_real_escape_string($session->Customer->Contact->Parent->ID), mysql_real_escape_string($session->Customer->Contact->ID), mysql_real_escape_string(id_param('enquiryid'))));

		if($data->Row['Counter'] == 0) {
			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}

		$enquiry = new Enquiry(id_param('enquiryid'));
		$enquiry->Customer->Get();
		$enquiry->Customer->Contact->Get();
		$enquiry->GetLines();

		$customerName = $enquiry->Customer->Contact->Person->GetFullName();

		$form = new Form($_SERVER['PHP_SELF']);
		$form->AddField('enquiryid', 'Enquiry ID', 'hidden', '', 'numeric_unsigned', 1, 11);
		$form->AddField('message', 'Response Message', 'textarea', '', 'anything', 1, 16384, false, 'style="width: 99%;" rows="7"');
		$form->AddField('comment', 'Customer Service Comments', 'textarea', '', 'anything', 1, 1024, false, 'style="width: 99%;" rows="3"');
		$form->AddField('rating', 'Rating', 'select', '0', 'numeric_unsigned', 1, 11, false);
		$form->AddOption('rating', '0', '');
		$form->AddOption('rating', '1', '1 - Lowest');
		$form->AddOption('rating', '2', '2');
		$form->AddOption('rating', '3', '3');
		$form->AddOption('rating', '4', '4');
		$form->AddOption('rating', '5', '5 - Highest');

		if(param('close')) {
			$form->InputFields['rating']->Required = true;
			$form->InputFields['comment']->Required = true;

			if($form->GetValue('rating') == 0) {
				$form->AddError('Rating must have a selected value.', 'rating');
			}

			if($form->Validate()) {
				$enquiry->Rating = $form->GetValue('rating');
				$enquiry->RatingComment = $form->GetValue('comment');
				$enquiry->Update();
				
				$enquiry->Close();

				redirect(sprintf("Location: %s?enquiryid=%d", $_SERVER['PHP_SELF'], $enquiry->ID));
			}
		} elseif(param('reply')) {
			$form->InputFields['message']->Required = true;

			if($form->Validate()) {
				$enquiry->IsPendingAction = 'Y';
				$enquiry->IsRequestingClosure = 'N';
				$enquiry->Update();

				$enquiryLine = new EnquiryLine();
				$enquiryLine->Enquiry->ID = $enquiry->ID;
				$enquiryLine->IsCustomerMessage = 'Y';
				$enquiryLine->Message = $form->GetValue('message');
				$enquiryLine->Add();

				redirect(sprintf("Location: %s?enquiryid=%d", $_SERVER['PHP_SELF'], $enquiry->ID));
			}
		}
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Enquiry Centre</title>
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
			<h1>Enquiry Centre</h1>
			<div id="orderConfirmation">
				<p class="breadCrumb"><a href="accountcenter.php">My Account</a> | <a href="introduce.php">Introduce A Friend</a> | <a href="bulbs.php">My Bulbs</a> | <a href="quotes.php">My Quotes</a> | <a href="orders.php">My Orders</a> | <a href="invoices.php">My Invoices</a> | <a href="enquiries.php">Enquiry Centre</a> | <a href="eNotes.php">Order Notes</a> | <a href="duplicate.php">Duplicate A Past Order</a> | <a href="returnorder.php">Returns</a> | <a href="profile.php">My Profile</a><?php if($session->Customer->Contact->HasParent){ ?> | <a href="businessProfile.php">My Business Profile</a><?php } ?> | <a href="changePassword.php">Change Password</a></p>
			</div>

			<?php
			if($action == 'new') {
				?>

				<p><a href="enquiries.php">&laquo; Back to Enquiry Centre</a></p>

				<?php
				if(!$form->Valid){
					echo $form->GetError();
					echo "<br>";
				}

				echo $form->Open();
				echo $form->GetHtml('action');
				echo $form->GetHtml('confirm');
				?>

				<table width="100%" border="0" cellpadding="0" cellspacing="0" class="bluebox">
					<tr>
						<td>
							<h3 class="blue">New Enquiry</h3>
							<p class="blue">Please complete the fields below. Required fields are marked with an asterisk (*).</p>

							Category Type *<br /><?php echo $form->GetHTML('type'); ?><br /><br />
							Subject *<br /><?php echo $form->GetHTML('subject'); ?><br /><br />
							<fieldset style="border: none; padding: 0;">Enquiry *<br /><?php echo $form->GetHTML('message'); ?></fieldset><br />
							<input name="submit" type="submit" class="submit" value="Submit" />
						</td>
					</tr>
				</table>

				<?php
				echo $form->Close();
			} else {
				if(id_param('enquiryid')){
					?>
					<p><a href="enquiries.php">&laquo; Back to Enquiry Centre</a></p>

					<?php
					if(!$form->Valid){
						echo $form->GetError();
						echo "<br>";
					}
					?>

					<table width="100%" border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td valign="top">

								<table width="100%" border="0" cellpadding="0" cellspacing="0" class="bluebox">
									<tr>
										<td>
											<h3 class="blue"><?php print $enquiry->Subject; ?></h3><br />

											<?php
											$quote = new Quote();
											$user = new User();

											for($i=0;$i<count($enquiry->Line);$i++) {
												if(($enquiry->Line[$i]->IsPublic == 'Y') && ($enquiry->Line[$i]->IsDraft == 'N')) {
													if($enquiry->Line[$i]->IsCustomerMessage == 'Y') {
														$author = sprintf('%s%s', $customerName, ($enquiry->Customer->Contact->Parent->ID > 0) ? sprintf(' @ %s', $orgName) : '');
													} else {
														$user->ID = $enquiry->Line[$i]->CreatedBy;
														$user->Get();
														$author = sprintf('%s @ %s', trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)), $GLOBALS['COMPANY']);
													}
													?>

													 <div style="border:1px dotted #A0B4CF; padding: 10px;">
													 	<p><strong><?php print $author; ?> said:</strong><br /><em><?php print $enquiry->Line[$i]->CreatedOn; ?></em></p>

													 	<?php
													 	echo sprintf('<p>%s</p>', nl2br($enquiry->Line[$i]->Message));

													 	if(count($enquiry->Line[$i]->Quotes) > 0) {
													 		echo '<p><strong><em>Attached Quotes:</em></strong></p>';
													 		echo '<ul style="margin-top: 0; margin-bottom: 0;">';

													 		foreach($enquiry->Line[$i]->Quotes as $quote) {
													 			$quote->Quote->Get();
													 			echo sprintf('<li><a href="quote.php?quoteid=%d">%s%s</a> (%s)</li>', $quote->Quote->ID, $quote->Quote->Prefix, $quote->Quote->ID, cDatetime($quote->Quote->CreatedOn, 'shortdatetime'));

													 		}

													 		echo '</ul>';
													 		echo '<br />';
													 	}

													 	if(count($enquiry->Line[$i]->Documents) > 0) {
													 		$lines = array();

													 		foreach($enquiry->Line[$i]->Documents as $document) {
													 			if(!empty($document->File->FileName) && file_exists($GLOBALS['ENQUIRY_DOCUMENT_DIR_FS'].$document->File->FileName)) {
													 				if($document->IsPublic == 'Y') {
													 					$lines[] = sprintf('<div style="padding: 0 0 0 20px;"><a %s href="enquiryDownload.php?documentid=%d" target="_blank">%s</a> (%s bytes)</div>', ($document->IsPublic == 'N') ? 'class="enquiryHiddenDocument"' : '', $document->ID, $document->File->FileName, number_format(filesize($GLOBALS['ENQUIRY_DOCUMENT_DIR_FS'].$document->File->FileName), 0, '.', ','));
													 				}
													 			} else {
													 				$document = new EnquiryLineDocument();
													 				$document->Delete($this->ID);
													 			}
													 		}

													 		if(count($lines) > 0) {
													 			echo '<p><strong><em>Attached Documents:</em></strong></p>';

													 			foreach($lines as $line) {
													 				echo $line;
													 			}

													 			echo '<br />';
													 		}
													 	}
													 	?>

													 </div>
													 <br />

													 <?php
												}
											}
											?>

										</td>
									</tr>
								</table><br />

								<?php
								echo $form->Open();
								echo $form->GetHTML('enquiryid');

								if($enquiry->Status != 'Closed') {
									?>

									<table width="100%" border="0" cellpadding="0" cellspacing="0" class="greybox">
										<tr>
											<td>
												<strong><?php echo $form->GetLabel('message'); ?></strong><br />
												<fieldset style="border: none; padding: 0;"><?php echo $form->GetHTML('message'); ?></fieldset><br />
												<input type="submit" name="reply" value="Post Response" class="submit" />&nbsp;
											</td>
										</tr>
									</table>
									<br />

									<?php
								}

								if((($enquiry->Status == 'Closed') && ($enquiry->Rating == 0)) || (($enquiry->Status != 'Closed') && ($enquiry->IsRequestingClosure == 'Y') && ($enquiry->Rating == 0))) {
									?>

									<table width="100%" border="0" cellpadding="0" cellspacing="0" class="greybox">
										<tr>
											<td>
												<?php
												if(($enquiry->Status == 'Closed') && ($enquiry->Rating == 0)) {
													echo '<p>You have not yet rated the performance of our customer service team in relation to this enquiry. Please complete the following form so that we may improve our customer relations in the future.</p>';
												} elseif(($enquiry->Status != 'Closed') && ($enquiry->IsRequestingClosure == 'Y') && ($enquiry->Rating == 0)) {
													echo sprintf('<p>%s is requesting to close this enquiry. Please comment on the performance of our customer service team in relation to this enquiry so that we may improve our customer relations in the future.</p>', $GLOBALS['COMPANY']);
												}
												?>

												<strong><?php echo $form->GetLabel('rating'); ?></strong><br />
												<?php echo $form->GetHTML('rating'); ?><br /><br />

												<strong><?php echo $form->GetLabel('comment'); ?></strong><br />
												<fieldset style="border: none; padding: 0;"><?php echo $form->GetHTML('comment'); ?></fieldset><br />

												<input type="submit" name="close" value="Close Enquiry" class="submit" />
											</td>
										</tr>
									</table>
									<br />

									<?php
								}

								echo $form->Close();
								?>

							</td>
							<td width="20"></td>
							<td valign="top" width="250">

								<table width="100%" border="0" cellpadding="0" cellspacing="0" class="greybox">
									<tr>
										<td>
											<h3 class="grey">Enquiry Info</h3><br />

											<?php
											$status = $enquiry->Status;

											if($enquiry->Status != 'Unread') {
												if($enquiry->Status != 'Closed') {
													if($enquiry->IsRequestingClosure == 'Y') {
														$status .= ' (Requested Closure)';
													} elseif($enquiry->IsPendingAction == 'Y') {
														$status .= ' (Awaiting Response)';
													}
												}
											}
											?>

											<p><strong>Reference:</strong> <?php print $enquiry->GetPrefix().$enquiry->ID; ?></p>
											<p><strong>Status:</strong> <?php print $status; ?></p>
											<p><strong>Category:</strong> <?php print $enquiry->Type->Name; ?></p>

											<?php
											if($enquiry->Rating > 0) {
												$rating = number_format($enquiry->Rating, 0, '', '');
												$ratingImg = '';

												for($i=0;$i<$rating;$i++) {
													$ratingImg .= sprintf('<img src="ignition/images/enquiry_rating_on.gif" align="absmiddle" height="15" width="16" alt="%d out of 5" />', $rating);
												}
												for($i=$rating;$i<5;$i++) {
													$ratingImg .= sprintf('<img src="ignition/images/enquiry_rating_off.gif" align="absmiddle" height="15" width="16" alt="%d out of 5" />', $rating);
												}
												?>
												<p><strong>Rating:</strong> <?php print $ratingImg; ?></p>
												<p><strong>Comment:</strong> <?php print $enquiry->RatingComment; ?></p>
												<?php
											}
											?>
										</td>
									</tr>
								</table>

							</td>
						</tr>
					</table>

					<?php
				} else {
					?>
					<p>Below is a list of your enquiries. Your most recent enquiries are displayed first.</p>

					<table cellspacing="0" class="myAccountOrderHistory">
						<tr>
						 	<th>Enquiry Date</th>
							<th>Subject</th>
							<th>Type</th>
							<th>Enquired By</th>
							<th>Enquiry Number</th>
							<th>Status</th>
						</tr>

						<?php
						$data = new DataQuery(sprintf("SELECT e.*, et.Name, p2.Name_First, p2.Name_Last FROM enquiry AS e INNER JOIN enquiry_type AS et ON e.Enquiry_Type_ID=et.Enquiry_Type_ID INNER JOIN enquiry_line AS el ON el.Enquiry_ID=e.Enquiry_ID INNER JOIN customer AS c ON c.Customer_ID=e.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN person AS p2 ON p2.Person_ID=n.Person_ID WHERE el.Is_Public='Y' AND el.Is_Draft='N' AND ((n.Parent_Contact_ID>0 AND n.Parent_Contact_ID=%d) OR (n.Parent_Contact_ID=0 AND n.Contact_ID=%d)) GROUP BY e.Enquiry_ID ORDER BY e.Created_On DESC", mysql_real_escape_string($session->Customer->Contact->Parent->ID), mysql_real_escape_string($session->Customer->Contact->ID)));
						if($data->TotalRows == 0) {
							?>

							<tr>
								<td colspan="6" class="center">There are no enquiries available for viewing</td>
					 		</tr>

					  		<?php
						} else {
							while($data->Row){
								?>

								<tr>
								 	<td><?php echo cDatetime($data->Row['Created_On'], 'longdate'); ?></td>
									<td <?php echo ((($data->Row['Status'] != 'Closed') && (($data->Row['Is_Pending_Action'] == 'N') || ($data->Row['Is_Requesting_Closure'] == 'Y'))) || (($data->Row['Status'] == 'Closed') && ($data->Row['Rating'] == 0))) ? 'style="font-weight: bold;"' : ''; ?>><a href="enquiries.php?enquiryid=<?php echo $data->Row['Enquiry_ID']; ?>"><?php echo ucfirst($data->Row['Subject']); ?></a></td>
									<td><?php echo ucfirst($data->Row['Name']); ?></td>
									<td><?php echo trim(sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last'])); ?></td>
									<td><?php echo $data->Row['Enquiry_ID']; ?></td>
									<td><?php echo ucfirst($data->Row['Status']); ?></td>
								</tr>

								<?php
								$data->Next();
							}
						}
						$data->Disconnect();
						?>

					</table><br />

					<p style="color: #c00; font-weight: bold;">Always continue with an existing enquiry in response to your latest message. Only use the new enquiry button for a brand new enquiry.</p>

					<form action="enquiries.php" method="post">
						<input type="hidden" name="action" value="new" />
						<input type="submit" class="submit" name="add" value="New Enquiry" />
					</form>

					<?php
				}
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