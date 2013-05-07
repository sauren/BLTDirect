<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Return.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReturnAbuse.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReturnLog.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReturnReason.php');

$session->Secure();

$orderId = id_param('orderid');
$typeId = id_param('typeid');

if(is_null($orderId)) {
	redirectTo('accountcenter.php');
}

$isComplete = (strtolower(param('status')) == 'complete');

$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE ((n.Parent_Contact_ID>0 AND n.Parent_Contact_ID=%d) OR (n.Parent_Contact_ID=0 AND n.Contact_ID=%d)) AND o.Is_Sample='N' AND o.Order_ID=%d", $session->Customer->Contact->Parent->ID, $session->Customer->Contact->ID, $orderId));
if($data->Row['Counter'] == 0) {
	redirectTo('accountcenter.php');
}
$data->Disconnect();

$reason = new ReturnReason();

if(!empty($typeId) && !$reason->Get($typeId)) {
	redirectTo('accountcenter.php');
}

$order = new Order($orderId);
$order->Customer->Get();
$order->Customer->Contact->Get();
$order->PaymentMethod->Get();
$order->Postage->Get();
$order->GetLines();

$count = 0;

for($i=0; $i<count($order->Line); $i++) {
	if($order->Line[$i]->DespatchID > 0) {
		$count++;
	}
}

if(empty($count)) {
	redirectTo('accountcenter.php');
}

$formReturn = new Form($_SERVER['PHP_SELF']);
$formReturn->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$formReturn->AddField('orderid', 'Order ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$formReturn->AddField('typeid', 'Type ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$formReturn->AddField('comment', 'Comment', 'textarea', '', 'anything', 1, 1024, false, 'style="width: 50%;" rows="5"');

for($i=0; $i<count($order->Line); $i++) {
	if($order->Line[$i]->DespatchID > 0) {
		$formReturn->AddField('quantity_' . $order->Line[$i]->ID, 'Quantity', 'select', '0', 'numeric_unsigned', 1, 11);

		for($j=0; $j<=$order->Line[$i]->Quantity; $j++) {
			$formReturn->AddOption('quantity_' . $order->Line[$i]->ID, $j, $j);
		}
	}
}

$formChange = new Form($_SERVER['PHP_SELF']);
$formChange->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$formChange->AddField('orderid', 'Order ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$formChange->AddField('typeid', 'Type ID', 'select', '', 'numeric_unsigned', 1, 11, false);
$formChange->AddOption('typeid', '', '');

$data = new DataQuery(sprintf("SELECT Reason_ID, Reason_Title FROM return_reason WHERE Reason_Title NOT LIKE 'Not Received' ORDER BY Reason_Title ASC"));
while($data->Row) {
	$formChange->AddOption('typeid', $data->Row['Reason_ID'], $data->Row['Reason_Title']);

	$data->Next();
}
$data->Disconnect();

if(isset($_REQUEST['confirm'])) {
	if(isset($_REQUEST['change'])) {
		if($formChange->Validate()) {
			redirectTo(sprintf('?orderid=%d&typeid=%d', $orderId, $formChange->GetValue('typeid')));
		}
	} elseif(isset($_REQUEST['return'])) {
		if($formReturn->Validate()) {
			$replace = false;

			if((strtolower($reason->Title) == 'broken in transit') || (strtolower($reason->Title) == 'faulty')) {
				$log = new ReturnLog();
				$log->orderId = $order->ID;
				$log->type = 'auto failed';

				$replace = true;

				if($replace) {
					if(in_array($order->Prefix, array('R', 'N', 'B'))) {
						$replace = false;

						$log->log = sprintf('Prefix is incorrect (%s).', $order->Prefix);
						$log->add();
					}
				}

				if($replace) {
					if(strtotime($order->CreatedOn) < strtotime(sprintf('-%d months', Setting::GetValue('phone_system_automated_order_period')))) {
						$replace = false;

						$log->log = sprintf('Order date too old (%s).', $order->CreatedOn);
						$log->add();
					}
				}

				if($replace) {
					if($order->PaymentMethod->Reference == 'google') {
						$replace = false;

						$log->log = sprintf('Payment method is invalid (Google Checkout).');
						$log->add();
					}
				}
								
				if($replace) {
					$order->Shipping->Address->Country->Get();

					if($order->Shipping->Address->Country->ISOCode2 != 'GB') {
						$replace = false;

						$log->log = sprintf('Shipping address is incorrect (%s).', $order->Shipping->Address->Country->ISOCode2);
						$log->add();
					}
				}

				if($replace) {
					$quantity = 0;

					for($j=0; $j<count($order->Line); $j++) {
						if($order->Line[$j]->DespatchID > 0) {
							if($formReturn->GetValue('quantity_' . $order->Line[$j]->ID) > 0) {
								$quantity += $order->Line[$j]->Quantity;
							}
						}
					}

					if($quantity <> 1) {
						$replace = false;

						$log->log = sprintf('Quantity exceeds 1 unit (%d).', $quantity);
						$log->add();
					}
				}

				if($replace) {
					$data = new DataQuery(sprintf("SELECT Shipping_Class_ID FROM shipping_class WHERE Is_Default='Y'"));
					$standardShipping = ($data->TotalRows > 0) ? $data->Row['Shipping_Class_ID'] : 0;
					$data->Disconnect();

					for($j=0; $j<count($order->Line); $j++) {
						if($order->Line[$j]->DespatchID > 0) {
							if($formReturn->GetValue('quantity_' . $order->Line[$j]->ID) > 0) {
								$order->Line[$j]->Product->Get();

								if($standardShipping != $order->Line[$j]->Product->ShippingClass->ID) {
									$replace = false;

									$log->log = sprintf('Shipping class is not standard (%s).', $order->Line[$j]->Product->ShippingClass->Name);
									$log->add();
								}
							}
						}
					}
				}

				if($replace) {
					$cost = 0 ;

					for($j=0; $j<count($order->Line); $j++) {
						if($order->Line[$j]->DespatchID > 0) {
							$quantity = $formReturn->GetValue('quantity_' . $order->Line[$j]->ID);

							if($quantity > 0) {
								$cost += $order->Line[$j]->Cost * $quantity;
							}
						}
					}

					if(($cost <= 0) || ($cost > Setting::GetValue('phone_system_faulty_cost'))) {
						$replace = false;

						$log->log = sprintf('Cost of replacement too great (&pound;%s).', number_format($cost, 2, '.', ','));
						$log->add();
					}
				}

				if($replace) {
					$abuse = new ReturnAbuse();

					if($abuse->getByOrderId($order->ID)) {
						$abuse->increment();

						if($abuse->counter > 2) {
							$replace = false;

							$log->log = sprintf('Return abuse counter exceeded (%d).', $abuse->counter);
							$log->add();
						}
					} else {
						$abuse->counter = 1;
						$abuse->add();
					}
				}
			}			

			if($replace) {
				$originalOrderId = $order->ID;
				$originalOrderPrefix = $order->Prefix;

				$order->Card = new Card();
				$order->IsCustomShipping = 'Y';
				$order->TotalShipping = 0;
				$order->OrderedOn = now();
				$order->CustomID = '';
				$order->Status = 'Unread';
				$order->Prefix = 'B';
				$order->Referrer = '';
				$order->PaymentMethod->GetByReference('foc');
				$order->ParentID = $originalOrderId;
				$order->Add();
				
				for($j=0; $j<count($order->Line); $j++) {
					if($order->Line[$j]->DespatchID > 0) {
						if($formReturn->GetValue('quantity_' . $order->Line[$j]->ID) > 0) {
							$order->Line[$j]->Order = $order->ID;
							$order->Line[$j]->Quantity = $formReturn->GetValue('quantity_' . $order->Line[$j]->ID);
							$order->Line[$j]->DespatchID = 0;
							$order->Line[$j]->InvoiceID = 0;
							$order->Line[$j]->Status = '';
							$order->Line[$j]->DespatchedFrom->ID = 0;
							$order->Line[$j]->FreeOfCharge = 'Y';
							$order->Line[$j]->Add();
						}
					}
				}

				$order->GetLines();
				$order->Recalculate();

				$findReplace = new FindReplace();
				$findReplace->Add('/\[ORDER_ID\]/', $originalOrderId);
				$findReplace->Add('/\[ORDER_REFERENCE\]/', $originalOrderPrefix.$originalOrderId);
				$findReplace->Add('/\[REPLACEMENT_ORDER_ID\]/', $order->ID);
				$findReplace->Add('/\[REPLACEMENT_ORDER_REFERENCE\]/', $order->Prefix.$order->ID);

				$templateHtml = $findReplace->Execute(Template::GetContent(sprintf('email_phone_%s_replacement', str_replace(' ', '_', strtolower($reason->Title)))));

				$findReplace = new FindReplace();
				$findReplace->Add('/\[BODY\]/', $templateHtml);
				$findReplace->Add('/\[NAME\]/', sprintf('%s %s', $order->Customer->Contact->Person->Name, $order->Customer->Contact->Person->LastName));

				$templateHtml = $findReplace->Execute(Template::GetContent('email_template_standard'));

				$mail = new htmlMimeMail5();
				$mail->setFrom($GLOBALS['EMAIL_FROM']);
				$mail->setSubject(sprintf('Return Acknowledgment: %s [#%d] - %s', $reason->Title, $order->ID, $GLOBALS['COMPANY']));
				$mail->setText('This is an HTML email. If you only see this text your email client only supports plain text emails.');
				$mail->setHTML($templateHtml);
				$mail->send(array($order->Customer->Contact->Person->Email));

				$log = new ReturnLog();
				$log->orderId = $originalOrderId;
				$log->type = 'email';
				$log->log = sprintf('Return Acknowledgment email sent for \'%s\' with replacement generated notification.', $reason->Title);
				$log->add();

				$log = new ReturnLog();
				$log->orderId = $originalOrderId;
				$log->type = 'order';
				$log->referenceId = $order->ID;
				$log->log = sprintf('Replacement order generated for \'%s\'.', $reason->Title);
				$log->add();

				redirectTo(sprintf('?orderid=%d&typeid=%d&status=complete', $orderId, $typeId));

			} else {
				$returns = array();

				for($i=0; $i<count($order->Line); $i++) {
					if($order->Line[$i]->DespatchID > 0) {
						$quantity = $formReturn->GetValue('quantity_' . $order->Line[$i]->ID);

						if($quantity > 0) {
							$return  = new ProductReturn();
							$return->OrderLine->ID = $order->Line[$i]->ID;
							$return->Invoice->ID = $order->Line[$i]->InvoiceID;
							$return->Customer->ID = $session->Customer->ID;
							$return->Reason->ID = $reason->ID;
							$return->Quantity = $quantity;
							$return->RequestedOn = now();
						
							if(strtolower($reason->Title) == 'incorrect goods received') {
								$comment = $formReturn->GetValue('comment');

								if(!empty($comment)) {
									$return->Note = $comment;
								}
							}

							$return->Add();

							$returns[] = $return->ID;

							$log = new ReturnLog();
							$log->orderId = $order->ID;
							$log->type = 'return';
							$log->referenceId = $return->ID;
							$log->log = sprintf('Return generated for \'%s\'.', $reason->Title);
							$log->add();
						}
					}
				}

				if(!empty($returns)) {
					$findReplace = new FindReplace();
					$findReplace->Add('/\[ORDER_ID\]/', $order->ID);
					$findReplace->Add('/\[ORDER_REFERENCE\]/', $order->Prefix.$order->ID);

					$templateHtml = sprintf('<p>Thank you for using our online returns system for processing your request which we endeavour to resolve as soon as possible.</p><p>For your reference the following return numbers have been created; %s.</p>', implode(', ', $returns));

					$mail = new htmlMimeMail5();
					$mail->setFrom($GLOBALS['EMAIL_FROM']);
					$mail->setSubject(sprintf('Return Order: #%d', $order->ID));
					$mail->setText('This is an HTML email. If you only see this text your email client only supports plain text emails.');
					$mail->setHTML($templateHtml);
					$mail->send(array($order->Customer->Contact->Person->Email));

					$log = new ReturnLog();
					$log->orderId = $order->ID;
					$log->type = 'email';
					$log->log = sprintf('Returns generated email sent for \'%s\'.', $reason->Title);
					$log->add();

					redirectTo(sprintf('?orderid=%d&typeid=%d&status=complete', $orderId, $typeId));
				}
			}

			redirectTo(sprintf('?orderid=%d&typeid=%d', $orderId, $typeId));
		}
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Return Order #<?php echo $order->ID; ?></title>
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
					<h1>Return Order #<?php echo $order->ID; ?> <?php echo !empty($typeId) ? sprintf('(%s)', $reason->Title) : ''; ?></h1>
					<br />

					<?php
					if($isComplete) {
						$bubble = new Bubble('Return Complete', 'Your return request has been successfully submitted. An email will follow shortly advising you of the procedure for returning your goods to us.<br /><br /><input class="submit" type="button" name="back" value="Back" onclick="window.self.location.href = \'accountcenter.php\';" />');

						echo $bubble->GetHTML();
						echo '<br />';
					}

					if(!$formReturn->Valid) {
						echo $formReturn->GetError();
						echo '<br />';
					}

					if(!$formChange->Valid) {
						echo $formChange->GetError();
						echo '<br />';
					}

					if(!$isComplete) {
						echo $formChange->Open();
						echo $formChange->GetHTML('confirm');
						echo $formChange->GetHTML('orderid');
						?>

						<h3>1. Return reason</h3>
						<p style="font-size: 14px; color: #c00;">Simply select the most appropriate action for this return.</p>
						
						<p><?php echo $formChange->GetHTML('typeid'); ?></p>

						<input name="change" class="submit" type="submit" value="Change" />
						<br /><br />

						<?php
						echo $formChange->Close();

						if(!empty($typeId)) {
							switch(strtolower($reason->Title)) {
								case 'broken in transit':
									echo '<h3>2. Replace quantities</h3>';
									echo sprintf('<p style="font-size: 14px; color: #c00;">Thank you for contacting us regarding your goods that were %s, please select the quantities and items that were damaged below and we will send you a good return request immediately.</p>', strtolower($reason->Title));

									break;

								case 'faulty':
									echo '<h3>2. Replace quantities</h3>';
									echo sprintf('<p style="font-size: 14px; color: #c00;">Thank you for contacting us regarding your goods that are %s, please select the quantities and items that are faulty below and we will send you a good return request immediately.</p>', strtolower($reason->Title));

									break;

								case 'ordered incorrectly':
									echo '<h3>2. Return quantities</h3>';
									echo sprintf('<p style="font-size: 14px; color: #c00;">Thank you for contacting us regarding your incorrectly ordered goods, please select the quantities for return below and we will process your request immediately.</p>');

									break;

								case 'incorrect goods received':
									echo '<h3>2. Missing quantities</h3>';
									echo sprintf('<p style="font-size: 14px; color: #c00;">Thank you for contacting us regarding your incorrectly received goods, please select the quantities of products that you have not received from your order below. For any products you have received leave the quantity at zero.</p>');

									break;
							}
							?>

							<table width="100%"  border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td valign="top">

										<table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
											<tr>
												<td valign="top" class="billing">
													<p>
														<strong>Organisation/Individual:</strong><br />
														<?php echo $order->GetBillingAddress();  ?>
													</p>
												</td>
												<td valign="top" class="shipping">
													<p>
														<strong>Shipping Address:</strong><br />
														<?php echo $order->GetShippingAddress(); ?>
													</p>
												</td>
												<td valign="top" class="shipping">
													<p>
														<strong>Invoice Address:</strong><br />
														<?php echo $order->GetInvoiceAddress(); ?>
													</p>
												</td>
											</tr>
										</table>

									</td>
									<td align="right" valign="top">

										<table cellpadding="0" cellspacing="0" border="0" class="invoicePaymentDetails">
											<tr>
												<th>Order Ref:</th>
												<td><strong><?php echo $order->Prefix . $order->ID; ?></strong></td>
											</tr>

											<?php
											if(!empty($order->CustomID)) {
												?>

												<tr>
													<th>Your Ref:</th>
													<td><strong><?php echo $order->CustomID; ?></strong></td>
												</tr>

												<?php
											}
											?>

											<tr>
												<th>Order Date:</th>
												<td><?php echo cDatetime($order->OrderedOn, 'longdate'); ?></td>
											</tr>
											<tr>
												<th>Status:</th>
												<td><?php echo ucfirst($order->Status); ?></td>
											</tr>
											<tr>
												<th>&nbsp;</th>
												<td>&nbsp;</td>
											</tr>
											<tr>
												<th>Payment Method:</th>
												<td><?php echo $order->GetPaymentMethod(); ?></td>
											</tr>

											<?php
											if($order->PaymentMethod->Reference == 'card') {
												?>

												<tr>
													<th>Card</th>
													<td><?php echo $order->Card->PrivateNumber(); ?></td>
												</tr>
											
												<?php
											}
											?>

										</table>

									</td>
								</tr>
							</table>
							<br />

							<?php
							echo $formReturn->Open();
							echo $formReturn->GetHTML('confirm');
							echo $formReturn->GetHTML('orderid');
							echo $formReturn->GetHTML('typeid');

							switch(strtolower($reason->Title)) {
								case 'incorrect goods received':
									?>

									<table cellspacing="0" class="catProducts">
										<tr>
											<th width="10%">Quantity</th>
											<th>Product</th>
											<th>Quickfind</th>
											<th>Quantity Not Received</th>
										</tr>

										<?php
										for($i=0; $i < count($order->Line); $i++) {
											if($order->Line[$i]->DespatchID > 0) {
												?>

												<tr>
													<td><?php echo $order->Line[$i]->Quantity; ?></td>
													<td><?php echo ($order->Line[$i]->Product->ID > 0) ? $order->Line[$i]->Product->Name : $order->Line[$i]->AssociativeProductTitle; ?></td>
													<td><a href="product.php?pid=<?php echo $order->Line[$i]->Product->ID; ?>"><?php echo $order->Line[$i]->Product->ID; ?></a></td>
													<td><?php echo $formReturn->GetHTML('quantity_' . $order->Line[$i]->ID); ?></td>
												</tr>

												<?php
											}
										}
										?>

									</table>
									<br />

									<?php
									break;

								default:
									?>

									<table cellspacing="0" class="catProducts">
										<tr>
											<th>Product</th>
											<th>Quickfind</th>
											<th>Quantity</th>
										</tr>

										<?php
										for($i=0; $i < count($order->Line); $i++) {
											if($order->Line[$i]->DespatchID > 0) {
												?>

												<tr>
													<td><?php echo ($order->Line[$i]->Product->ID > 0) ? $order->Line[$i]->Product->Name : $order->Line[$i]->AssociativeProductTitle; ?></td>
													<td><a href="product.php?pid=<?php echo $order->Line[$i]->Product->ID; ?>"><?php echo $order->Line[$i]->Product->ID; ?></a></td>
													<td><?php echo $formReturn->GetHTML('quantity_' . $order->Line[$i]->ID); ?></td>
												</tr>

												<?php
											}
										}
										?>

									</table>
									<br />

									<?php
									break;
							}

							switch(strtolower($reason->Title)) {
								case 'incorrect goods received':
									?>
								
									<h3>3. Incorrect products</h3>
									<p style="font-size: 14px; color: #c00;">Please provide quantities and brief descriptions of the products you have received incorrectly below. Once we have received these we will despatch the correct goods to you.</p>

									<?php echo $formReturn->GetHTML('comment'); ?>
									<br />

									<?php
									break;

								case 'ordered incorrectly':
									?>

									<h3>3. Required products</h3>
									<p style="font-size: 14px; color: #c00;">Please provide a brief description of the products you would like to order for our sales team to contact you.</p>

									<?php echo $formReturn->GetHTML('comment'); ?>
									<br />

									<?php
									break;

							}
							?>

							<input name="return" class="submit" type="submit" value="Continue" />

							<?php
							echo $formReturn->Close();
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
<?php include('lib/common/appFooter.php'); ?>

