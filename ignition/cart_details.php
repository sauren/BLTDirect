<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Address.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CartLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerContact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IFile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Person.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

if($action == 'email'){
	$session->Secure(2);
	emailCustomer();
	exit;
}elseif($action == 'delete'){
	$session->Secure(2);
	delete();
	exit;
}else{
	$session->Secure(2);
	view();
	exit;
}

	
	function delete() {
	
		if(isset($_REQUEST['cartid']) && !empty($_REQUEST['cartid'])) {
			$cid = $_REQUEST['cartid'];
			$cart = new Cart($cid);
			$cart->getByID($cid);
			$cart->Delete();
			redirect('Location: cart_abandoned.php');
    		exit;
		} 
		redirect('Location: ' . $_SERVER['PHP_SELF']);
		exit;
	}

	function emailCustomer(){

		$pid = 0;
		$cid = 0;
		if(isset($_REQUEST['personid']) && !empty($_REQUEST['personid'])){
			$pid = $_REQUEST['personid'];
		}

		$person = new Person($pid);
		$person->Get();

		if(isset($_REQUEST['cartid']) && !empty($_REQUEST['cartid'])){
			$cid = $_REQUEST['cartid'];
		}

		$cart = new Cart($cid);
		$cart->getByID($cid);
		$cart->GetLines();
		$cart->Calculate();
		$cartLine = $cart->Line;

		$form = new Form($_SERVER['PHP_SELF']);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('action', 'Action', 'hidden', '', 'anything', 5, 5);
		$form->AddField('personid', 'Person ID', 'hidden', '', 'anything', 1, 11);
		$form->AddField('cartid', 'Cart ID', 'hidden', '', 'anything', 1, 11);
		$form->AddField('subject', 'Subject', 'text', '', 'anything', 1, 255, true, 'style="width: 100%;"');
		$form->AddField('message', 'Message', 'textarea', '', 'anything', 1, 16384, true, 'style="width: 100%; font-family: arial, sans-serif;" rows="15"');

		$form->AddField('template', 'Templates', 'select', '0', 'anything', 1, 255, false, 'onchange="populateResponse(this);"');
		$form->AddOption('template', '0', '');

		$templateCount = 0;
		$data = new DataQuery(sprintf("SELECT Customer_Basket_ID, Title, Template FROM customer_basket_template ORDER BY Title ASC"));
			while($data->Row) {
			$templateCount++;

				$form->AddOption('template', $data->Row['Customer_Basket_ID'], $data->Row['Title']);
				$data->Next();
			}
		$data->Disconnect();

		if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true') && isset($_REQUEST['post'])){
			if($form->Validate()) {

				$cartLinesHtml = "";
				$cartTotalHtml = "";
				$itemTotal = 0;
				$subTotal = 0;
				$savings = 0;

				$emailCart = new Cart($_REQUEST['cartid']);
				$emailCart->getByID($_REQUEST['cartid']);
				$emailCart->GetLines();
				$emailCart->Calculate();
				$emailCartLine = $emailCart->Line;
				foreach($emailCartLine as $emailLine) {
					$cartLinesHtml .= sprintf(
						"<tr class='cartLine'>
							<td>%sx</td>
							<td>%s</td>
							<td>%s</td>
							<td align=\"right\">&pound;%s</td>
							<td align=\"right\">&pound;%s</td>
							<td align=\"right\">&pound;%s</td>
						</tr>", 
						$emailLine->Quantity,
						$emailLine->Product->Name,
						$emailLine->Product->ID,
						number_format($emailLine->Price, 2, '.', ','),
						number_format($emailLine->Discount, 2, '.', ','),
						number_format($emailLine->Price - ($emailLine->Discount / $emailLine->Quantity) * $emailLine->Quantity, 2, '.', ','));

					$itemTotal = $emailLine->Price - ($emailLine->Discount / $emailLine->Quantity) * $emailLine->Quantity;
					$subTotal += $itemTotal; 
					$savings += $emailLine->Discount;
				}

				$subTotal = number_format($subTotal, 2, '.', ',');
				$savings = number_format($savings, 2, ".", ",");

				$cartTotalHtml .= sprintf(
					"<tr class='cartTotals'>
						<td colspan='5' align='right'>Shipping:</td>
						<td align='right'>&pound;%s</td>
					</tr>
					<tr class='cartTotals'>
						<td colspan='5' align='right'>Pre Tax Total:</td>
						<td align='right'>&pound;%s</td>
					</tr>
					<tr class='cartTotals'>
						<td colspan='5' align='right'>Tax:</td>
						<td align='right'>&pound;%s</td>
					</tr>
					<tr class='cartTotals'>
						<td colspan='5' align='right'>Total:</td>
						<td align='right'>&pound;%s</td>
					</tr>", 
					number_format($cart->ShippingTotal, 2, ".", ","),
					number_format($subTotal+$cart->ShippingTotal, 2, ".", ","),
					number_format($cart->TaxTotal, 2, ".", ","),
					number_format($subTotal+$cart->ShippingTotal+$cart->TaxTotal, 2, ".", ","));

				$name = $person->GetFullName();
				$email = $person->Email;
				$subject = $form->GetValue('subject');
				$message = $form->GetValue('message');

				$template = "lib/templates/email_cart_abandoned.tpl";
			
				$findReplace = new FindReplace;
				$findReplace->Add('/\[CARTLINES\]/', $cartLinesHtml);
				$findReplace->Add('/\[CARTTOTAL\]/', $cartTotalHtml);
				$findReplace->Add('/\[EMAIL\]/', $email);
				$findReplace->Add('/\[MESSAGE\]/', stripslashes($message));
				$findReplace->Add('/\[SUBTOTAL\]/', $subTotal);
				$findReplace->Add('/\[SAVINGS\]/', $savings);

				// Replace Order Template Variables
				$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . $template);
				$orderHtml = "";
				for($i=0; $i < count($orderEmail); $i++){
					$orderHtml .= $findReplace->Execute($orderEmail[$i]);
				}

				$findReplace = new FindReplace();
				$findReplace->Add('/\[BODY\]/', $orderHtml);
				$findReplace->Add('/\[NAME\]/', $name);
				// Get Standard Email Template
				$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
				$emailBody = "";
				for($i=0; $i < count($stdTmplate); $i++){
					$emailBody .= $findReplace->Execute($stdTmplate[$i]);
				}
				$mail = new htmlMimeMail5();
				$mail->setFrom($GLOBALS['EMAIL_SALES']);
				$mail->setSubject(sprintf("%s", $subject));
				$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
				$mail->setHTML($emailBody);
				$mail->send(array($email));

				$emailCart->ContactMade();

				redirect('Location:cart_details.php?cartid=' . $_REQUEST['cartid']);
   				exit;
			}
		}

		$script = '<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>';

$script .= sprintf('<script language="javascript" type="text/javascript">
var parseResponseHandler = function(response) {
	tinyMCE.execInstanceCommand(\'mceFocus\', false, \'message\');
	tinyMCE.activeEditor.setContent(response);
}

var parseRequest = new HttpRequest();
parseRequest.setCaching(false);
parseRequest.setHandlerResponse(parseResponseHandler);

var parseTemplate = function(id) {
	parseRequest.abort();
	parseRequest.get(\'lib/util/parseCartTemplate.php?id=\' + id + \'&personid=%d\');
}
</script>', $person->ID);

$script .= sprintf('<script language="javascript" type="text/javascript">
var templateResponseHandler = function(details) {
	var items = details.split("{br}\n");

	parseTemplate(items[0]);
}
var templateRequest = new HttpRequest();
templateRequest.setCaching(false);
templateRequest.setHandlerResponse(templateResponseHandler);

var populateResponse = function(obj) {
console.log(obj.value);
	if(obj.value == 0) {
		tinyMCE.execInstanceCommand(\'mceFocus\', false, \'message\');
		tinyMCE.activeEditor.setContent(\'\');
	} else {
		templateRequest.abort();
		templateRequest.get(\'lib/util/getCartTemplate.php?id=\' + obj.value);
	}
}
</script>');

$script .= sprintf('<script language="javascript" type="text/javascript">
var typeResponseHandler = function(details) {
	var items = details.split("{br}{br}\n");
	var subItems = null;
	var e = document.getElementById(\'template\');
	var templateContainer = null;

	if(e) {
		e.options.length = 1;

		for(var i=0; i < items.length; i++) {
			subItems = items[i].split("{br}\n");

			if(subItems[0] && subItems[1]) {
				e.options[i+1] = new Option(subItems[1], subItems[0]);
			}
		}

		e.selectedIndex = 0;
		templateContainer = document.getElementById(\'templateContainer\');
		if(templateContainer) {
			if(e.options.length > 1) {
				templateContainer.style.display = \'block\';
			} else {
				templateContainer.style.display = \'none\';
			}
		}

		if(templateId > 0) {
			for(var i=0; i<e.options.length; i++) {
				if(e.options[i].value == templateId) {
					e.selectedIndex = i;
					break;
				}
			}
		}
	}
}

var typeRequest = new HttpRequest();
typeRequest.setCaching(false);
typeRequest.setHandlerResponse(typeResponseHandler);

var populateTemplate = function(obj) {
	typeRequest.get(\'lib/util/getCartTemplatesByType.php?id=\' + obj.value);
}
</script>');

		$page = new Page('Contact Customer - Abandoned Cart', sprintf('Contact %s.', $person->GetFullName()));
		$page->AddToHead('<link rel="stylesheet" type="text/css" href="css/m_enquiries.css" />');
		$page->AddToHead($script);
		$page->SetEditor(true);
		$page->Display('header');

		echo $form->Open();
		echo $form->GetHTML('customerid');

		if(!$form->Valid) {
			echo $form->GetError();
			echo '<br />';
		}
		?>
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td valign="top">
					<table width="100%">
						<tr>
							<td class="enquiryBlock">
								<p>
									<span class="pageSubTitle">Abandon Cart Message</span>
									<br />
									<span class="pageDescription">Enter the specific message details here.</span>
								</p>
									<?php 
										echo $form->GetHTML('confirm');
										echo $form->GetHTML('action');
										echo $form->GetHTML('personid');
										echo $form->GetHTML('cartid');
										echo sprintf('<strong>%s</strong><br />%s<br />', $form->GetLabel('subject'), $form->GetHTML('subject'));

										echo sprintf('<div id="templateContainer" style="%s"><strong>%s</strong><br />%s<br /><br /></div>', ($templateCount == 0) ? 'display: none;' : '',$form->GetLabel('template'), $form->GetHTML('template'));

										echo sprintf('<strong>%s</strong><br />%s<br />', $form->GetLabel('message'), $form->GetHTML('message'));
									?>
								<br/>

									<hr style="background-color: #eee; color: #eee; height: 1px;" />
									<p><span class="pageSubTitle">Cart Information</span></p>
									<table width="100%">
										<tr>
											<td><p><strong>Qty</strong></p></td>
											<td><p><strong>Product</strong></p></td>
											<td><p><strong>Quickfind</strong></p></td>
											<td align="right"><p><strong>Price</strong></p></td>
											<td align="right"><p><strong>Discount</strong></p></td>
											<td align="right"><p><strong>Line Price</strong></p></td>
										</tr>
									<?php foreach($cartLine as $line){ ?>
										<tr>
											<td><?php echo $line->Quantity; ?>x</td>
											<td><?php echo $line->Product->Name; ?></td>
											<td><?php echo $line->Product->ID; ?></td>
											<td align="right">&pound;<?php echo number_format($line->Price, 2, '.', ','); ?></td>
											<td align="right">
												<?php if($line->Discount > 0){ ?>
													&pound;<?php echo number_format($line->Discount, 2, '.', ','); 
												} else {
													echo "-";
												}?>
											</td>
											<td align="right">&pound;<?php echo number_format($line->Price - ($line->Discount / $line->Quantity) * $line->Quantity, 2, '.', ','); ?></td>
										</tr>
										<?php $itemTotal = $line->Price - ($line->Discount / $line->Quantity) * $line->Quantity;
											$subTotal += $itemTotal; 
											$savings += $line->Discount;?>
									<?php }?>
										<tr class="subTotal">
											<td colspan='5' align='right'><p><Strong>Sub Total:</Strong></p></td>
											<td align='right'>&pound;<?php echo number_format($subTotal, 2, '.', ','); ?></td>
										</tr>
										<tr class="cartTotals">
											<td colspan='5' align='right'>Shipping:</td>
											<td align='right'>&pound;<?php echo number_format($cart->ShippingTotal, 2, ".", ","); ?></td>
										</tr>
										<tr class="cartTotals">
											<td colspan='5' align='right'>Pre Tax Total:</td>
											<td align='right'>&pound;<?php echo number_format($subTotal+$cart->ShippingTotal, 2, ".", ","); ?></td>
										</tr>
										<tr class="cartTotals">
											<td colspan='5' align='right'>VAT:</td>
											<td align='right'>&pound;<?php echo number_format($cart->TaxTotal, 2, ".", ","); ?></td>
										</tr>
										<tr class="cartTotals">
											<td colspan='5' align='right'><p><Strong>Total:</Strong></p></td>
											<td align='right'>&pound;<?php echo number_format($subTotal+$cart->ShippingTotal+$cart->TaxTotal, 2, ".", ","); ?></td>
										</tr>
										<?php if($savings > 0){ ?>
											<tr class="cartTotals">
												<td colspan='5' align='right'>Total Saving:</td>
												<td align='right'>&pound;<?php echo number_format($savings, 2, ".", ","); ?></td>
											</tr>
										<?php } ?>
									</table>
								<br/>
								<br/>
								<input type="submit" name="post" action="email" value="Send Email" class="btn" />
							</td>
						</tr>
					</table>
				</td>
				<td width="15"></td>
				<td valign="top" width="300">

					<div class="customerDetails">
						<p><span class="pageSubTitle">Customer Info</span><br /><span class="pageDescription">Contact details for this customer.</span></p>

						<table cellpadding="0" cellspacing="0" border="0" class="enquiryForm">
							<tr>
								<td><p><strong>Customer:</strong></p></td>
								<td><p><?php echo $person->GetFullName(); ?></p></td>
							</tr>
							<tr>
								<td><p><strong>Address:</strong></p></td>
								<td><p><?php echo $person->Address->GetLongString(); ?></p></td>
							</tr>
							<tr>
								<td><p><strong>Phone:</strong></p></td>
								<td><p><?php echo $person->Phone1; ?></p></td>
							</tr>
							<tr>
								<td><p><strong>Email:</strong></p></td>
								<td><p><?php echo $person->Email; ?></p></td>
							</tr>
						</table>
					</div>
				</td>
			</tr>
		</table>

		<?php
		echo $form->Close();
		$page->Display('footer');
		require_once('lib/common/app_footer.php');
	}

	function view(){
		$cid = 0;
		if(isset($_REQUEST['cartid']) && !empty($_REQUEST['cartid'])){
			$cid = $_REQUEST['cartid'];
		}

		$cart = new Cart($cid);
		$cart->getByID($cid);
		$cart->GetLines();
		$cart->Calculate();

		$cartLine = $cart->Line;

		$person = new Person($cart->Customer->Contact->Person->ID);
		$person->Get();

		$billingName = $person->GetFullName();
		$billingAddress = $person->Address;

		if(isset($cart->ShipTo) && !empty($cart->ShipTo) && $cart->ShipTo != 'billing'){
			$customerShippingDetails = new CustomerContact($cart->ShipTo);
			$customerShippingDetails->Get();
			$shippingName = $customerShippingDetails->GetFullName();
			$shippingAddress = $customerShippingDetails->Address;
		} else {
			$shippingName = $person->GetFullName();
			$shippingAddress = $person->Address;
		}

		$orderCount = 0;
		$data = new DataQuery(sprintf("SELECT o2.orderCount FROM customer_basket AS cb
			LEFT JOIN (
				SELECT count(*) as orderCount, Customer_ID, Created_On
				FROM orders
				WHERE Created_On > DATE_SUB(NOW(), INTERVAL 3 DAY)
				GROUP BY Customer_ID, DATE(Created_On)
			) AS o2 ON o2.Customer_ID=cb.Customer_ID AND DATE(o2.Created_On)=DATE(cb.Created_On)
			WHERE cb.Basket_ID = %d", mysql_real_escape_string($cid)));
			if($data->TotalRows > 0) {
				$orderCount = $data->Row['orderCount'];
			}
		$data->Disconnect();

		$subTotal = 0;
		$itemTotal = 0;
		$savings = 0; 

		$page = new Page('Abandoned Cart', 'Abandoned Cart Details.');
		$page->AddToHead('<link rel="stylesheet" type="text/css" href="css/m_enquiries.css" />');
		$page->AddToHead($script);
		$page->Display('header');

		?>

		<div class="page">

			<table width="100%" border="0" cellpadding="0" cellspacing="0" class="contactInfo">
				<tr>
					<td valign="top" width="400" class="customerDetails">
						<h2>Contact Details</h2>
				    	<table cellpadding="0" cellspacing="0" border="0" class="enquiryForm">
							<tr>
								<td><p><strong>Name:</strong></p></td>
								<td><p><?php echo $person->GetFullName(); ?></p></td>
							</tr>
							<tr>
								<td><p><strong>Address:</strong></p></td>
								<td><p><?php echo $person->Address->GetLongString(); ?></p></td>
							</tr>
							<tr>
								<td><p><strong>Phone:</strong></p></td>
								<td><p><?php echo $person->Phone1; ?></p></td>
							</tr>
							<tr>
								<td><p><strong>Email:</strong></p></td>
								<td><p><?php echo $person->Email; ?></p></td>
							</tr>
						</table>
					</td>
					<td width="15"></td>
					<td valign="top" width="400"  class="customerDetails">
						<h2>Billing Address</h2>
				    	<table cellpadding="0" cellspacing="0" border="0" class="enquiryForm">
							<tr>
								<td><p><strong>Name:</strong></p></td>
								<td><p><?php echo $billingName;  ?></p></td>
							</tr>
							<tr>
								<td><p><strong>Address:</strong></p></td>
								<td><p><?php echo $billingAddress->GetLongString(); ?></p></td>
							</tr>
						</table>
					</td>
					<td width="15"></td>
					<td valign="top" width="400"  class="customerDetails">
						<h2>Shipping Address</h2>
				    	<table cellpadding="0" cellspacing="0" border="0" class="enquiryForm">
							<tr>
								<td><p><strong>Name:</strong></p></td>
								<td><p><?php echo $shippingName;   ?></p></td>
							</tr>
							<tr>
								<td><p><strong>Address:</strong></p></td>
								<td><p><?php echo $shippingAddress->GetLongString(); ?></p></td>
							</tr>
						</table>
					</td>
					<td width="15"></td>
					<td valign="top" width="200"  class="customerDetails adminOptions">
						<p><span class="pageSubTitle">Admin Controls:</span></p>
						<div style="display: block; position:relative; text-align:center;">
							<?php if($cart->ContactedOn > '0000-00-00 00:00:00'){ ?>
								<p><strong>Last Contacted:</strong><br /><?php echo date('jS F Y', strtotime($cart->ContactedOn)); ?></p>
							<?php } ?>

							<br />

							<input type="button" name="adoptcart" value="Adopt Cart" class="btn" onclick="window.location.href='order_adopt.php?adopt=adopt&ref=<?php echo $cart->ID;?>';" />

							<br /><br />

							<input type="button" name="sendemail" value="Send Email" class="btn" onclick="window.location.href='cart_details.php?action=email&personid=<?php echo $person->ID; ?>&cartid=<?php echo $cart->ID; ?> ';" />

							<br /><br />

							<input type="button" name="delete" value="Delete Cart" class="btn" onclick="window.location.href='cart_details.php?action=delete&cartid=<?php echo $cart->ID; ?>';" />
						</div>
					</td>
				</tr>
			</table>

			  

			<table width="100%"  border="0" cellspacing="0" cellpadding="0" class="abandonedCartList">
				<tr>
					<td colspan='5'>
						<p>
							<span class="pageSubTitle">Customer Cart</span>
						</p>
						<?php if($orderCount > 0){ ?>
							<p><span class="pageSubTitle">Please Note:- </span> This customer has made <?php echo $orderCount; ?> order(s) on the same day, that this cart was abandoned. 
							<a href="customer_orders.php?customer=<?php echo $cart->Customer->ID;?>">Click to view customer order history</a>

							</p>

						<?php }
						 ?>
					</td>
				<tr>
				<tr>
					<td><p><Strong>Qty</Strong></p></td>
					<td><p><Strong>Product</Strong></p></td>
					<td><p><Strong>Quickfind</Strong></p></td>
					<td align="right"><p><Strong>Price</Strong></p></td>
					<td align="right"><p><Strong>Discount</Strong></p></td>
					<td align="right"><p><Strong>Line Price</Strong></p></td>
				</tr>
				<?php foreach($cartLine as $line){ ?>
					<tr>
						<td><?php echo $line->Quantity; ?>x</td>
						<td><?php echo $line->Product->Name; ?></td>
						<td><?php echo $line->Product->ID; ?></td>
						<td align="right">&pound;<?php echo number_format($line->Price, 2, '.', ','); ?></td>
						<td align="right">
							<?php if($line->Discount > 0){ ?>
								&pound;<?php echo number_format($line->Discount, 2, '.', ','); 
							} else {
								echo "-";
							}?>
						</td>

						<td align="right">&pound;<?php echo number_format($line->Price - ($line->Discount / $line->Quantity) * $line->Quantity, 2, '.', ','); ?></td>
					</tr>
					<?php $itemTotal = $line->Price - ($line->Discount / $line->Quantity) * $line->Quantity;
					$subTotal += $itemTotal; 
					$savings += $line->Discount;
					?>
				<?php }?>
				<tr class="subTotal">
					<td colspan='5' align='right'><p><Strong>Sub Total:</Strong></p></td>
					<td align='right'>&pound;<?php echo number_format($subTotal, 2, '.', ','); ?></td>
				</tr>
			

				<tr class="cartTotals">
					<td colspan='5' align='right'>Shipping:</td>
					<td align='right'>&pound;<?php echo number_format($cart->ShippingTotal, 2, ".", ","); ?></td>
				</tr>
				<tr class="cartTotals">
					<td colspan='5' align='right'>Pre Tax Total:</td>
					<td align='right'>&pound;<?php echo number_format($subTotal+$cart->ShippingTotal, 2, ".", ","); ?></td>
				</tr>
				<tr class="cartTotals">
					<td colspan='5' align='right'>VAT:</td>
					<td align='right'>&pound;<?php echo number_format($cart->TaxTotal, 2, ".", ","); ?></td>
				</tr>
				<tr class="cartTotals">
					<td colspan='5' align='right'><p><Strong>Total:</Strong></p></td>
					<td align='right'>&pound;<?php echo number_format($subTotal+$cart->ShippingTotal+$cart->TaxTotal, 2, ".", ","); ?></td>
				</tr>
				<?php if($savings > 0){ ?>
					<tr class="cartTotals">
						<td colspan='5' align='right'>Total Saving:</td>
						<td align='right'>&pound;<?php echo number_format($savings, 2, ".", ","); ?></td>
					</tr>
				<?php } ?>
			</table>
		</div>

		<?php
		$page->Display('footer');
		require_once('lib/common/app_footer.php');
		}