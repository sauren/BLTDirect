<?php
require_once('../lib/common/appHeadermobile.php');
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
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Enquiry Centre</span></div>
<div class="maincontent">
<div class="maincontent1">
			<div id="orderConfirmation">
						<p class="breadCrumb"><a href="accountcenter.php">My Account</a> | <a href="introduce.php">Introduce A Friend</a> | <a href="bulbs.php">My Bulbs</a> | <a href="quotes.php">My Quotes</a> | <a href="orders.php">My Orders</a> | <a href="invoices.php">My Invoices</a> | <a href="enquiries.php">Enquiry Centre</a> | <a href="eNotes.php">Order Notes</a> | <a href="duplicate.php">Duplicate A Past Order</a> | <a href="returnorder.php">Returns</a> | <a href="profile.php">My Profile</a> <?php if($session->Customer->Contact->HasParent){ ?> | <a href="businessProfile.php">My Business Profile</a><?php } ?> | <a href="changePassword.php">Change Password</a> | <a href="?action=logout">Logout</a></p>			</div>

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
													$ratingImg .= sprintf('<img src="../ignition/images/enquiry_rating_on.gif" align="absmiddle" height="15" width="16" alt="%d out of 5" />', $rating);
												}
												for($i=$rating;$i<5;$i++) {
													$ratingImg .= sprintf('<img src="../ignition/images/enquiry_rating_off.gif" align="absmiddle" height="15" width="16" alt="%d out of 5" />', $rating);
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
						 	<th><strong>Enquiry Date</strong></th>
							<th><strong>Subject</strong></th>
							<th><strong>Type</strong></th>
							<th><strong>Enquired By</strong></th>
							<th><strong>Enquiry Number</strong></th>
							<th><strong>Status</strong></th>
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
						<p style="line-height:20px">
						  <input type="hidden" name="action" value="new" />
						  <input type="submit" class="submit" name="add" value="New Enquiry" />
						</p>
						<p style="line-height:0px">&nbsp; </p>
    </form>
					<?php
				}
			}
			?>
</div>
</div>
<?php include("ui/footer.php");