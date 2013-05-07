<?php
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLine.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/FindReplace.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Order.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/ReturnAbuse.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/ReturnLog.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/ReturnReason.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Setting.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Template.php');

class AutomateReturn {
	public static function processRequest($orderId, $typeId, $email = null) {
		$order = new Order();

		if($order->Get($orderId)) {
			$order->PaymentMethod->Get();
			$order->Customer->Get();
			$order->Customer->Contact->Get();
			$order->GetLines();

			$email = !is_null($email) ? $email : $order->Customer->Contact->Person->Email;

			$reason = new ReturnReason();

			if($reason->Get($typeId)) {
				$autoLogin = serialize(array($order->Customer->Contact->ID, $order->Customer->Contact->CreatedOn, date('Y-m-d H:i:s', strtotime('+1 week'))));

				$cypher = new Cipher($autoLogin);
				$cypher->Encrypt();

				$autoData = urlencode($cypher->Value);

				switch(strtolower($reason->Title)) {
					case 'incorrect goods received':
					case 'ordered incorrectly':
					case 'broken in transit':
					case 'faulty':

						$findReplace = new FindReplace();
						$findReplace->Add('/\[ORDER_ID\]/', $order->ID);
						$findReplace->Add('/\[ORDER_REFERENCE\]/', $order->Prefix.$order->ID);
						$findReplace->Add('/\[LOGIN_LINK\]/', sprintf('https://www.bltdirect.com/return.php?auto=%s&orderid=%d&typeid=%d', $autoData, $order->ID, $reason->ID));

						$templateHtml = $findReplace->Execute(Template::GetContent(sprintf('email_phone_%s', str_replace(' ', '_', strtolower($reason->Title)))));

						$findReplace = new FindReplace();
						$findReplace->Add('/\[BODY\]/', $templateHtml);
						$findReplace->Add('/\[NAME\]/', sprintf('%s %s', $order->Customer->Contact->Person->Name, $order->Customer->Contact->Person->LastName));

						$templateHtml = $findReplace->Execute(Template::GetContent('email_template_standard'));

						$mail = new htmlMimeMail5();
						$mail->setFrom($GLOBALS['EMAIL_FROM']);
						$mail->setSubject(sprintf('Return Acknowledgment: %s [#%d] - %s', $reason->Title, $order->ID, $GLOBALS['COMPANY']));
						$mail->setText('This is an HTML email. If you only see this text your email client only supports plain text emails.');
						$mail->setHTML($templateHtml);
						$mail->send(array($email));

						$log = new ReturnLog();
						$log->orderId = $order->ID;
						$log->type = 'email';
						$log->log = sprintf('Return Acknowledgment email sent for \'%s\' with auto login link.', $reason->Title);
						$log->add();

						break;

					case 'not received':

						if(strtolower($order->Status) != 'despatched') {
							$findReplace = new FindReplace();
							$findReplace->Add('/\[ORDER_ID\]/', $order->ID);
							$findReplace->Add('/\[ORDER_REFERENCE\]/', $order->Prefix.$order->ID);

							$templateHtml = $findReplace->Execute(Template::GetContent(sprintf('email_phone_%s_undespatched', str_replace(' ', '_', strtolower($reason->Title)))));

							$findReplace = new FindReplace();
							$findReplace->Add('/\[BODY\]/', $templateHtml);
							$findReplace->Add('/\[NAME\]/', sprintf('%s %s', $order->Customer->Contact->Person->Name, $order->Customer->Contact->Person->LastName));

							$templateHtml = $findReplace->Execute(Template::GetContent('email_template_standard'));

							$mail = new htmlMimeMail5();
							$mail->setFrom($GLOBALS['EMAIL_FROM']);
							$mail->setSubject(sprintf('Delay Acknowledgment: %s [#%d] - %s', $reason->Title, $order->ID, $GLOBALS['COMPANY']));
							$mail->setText('This is an HTML email. If you only see this text your email client only supports plain text emails.');
							$mail->setHTML($templateHtml);
							$mail->send(array($email));

							$log = new ReturnLog();
							$log->orderId = $order->ID;
							$log->type = 'email';
							$log->log = sprintf('Delay Acknowledgment email sent for \'%s\' with undespatched notification.', $reason->Title);
							$log->add();

						} else {
							$tooYoung = false;

							$data = new DataQuery(sprintf("SELECT MAX(Created_On) AS CreatedOn FROM despatch WHERE Order_ID=%d", $order->ID));
							if($data->TotalRows > 0) {
								$despatchDate = strtotime($data->Row['CreatedOn']);

								$postageThreshold = $order->Postage->Days * 2;
								$postageDate = strtotime(sprintf('+%d day', $postageThreshold), $despatchDate);

								if($postageDate > time()) {
									$tooYoung = true;
								}
							} else {
								$tooYoung = true;
							}
							$data->Disconnect();

							if($tooYoung) {
								$couriers = '';

								$data = new DataQuery(sprintf("SELECT c.Courier_Name, c.Courier_URL, d.Consignment FROM despatch AS d INNER JOIN courier AS c ON c.Courier_ID=d.Courier_ID WHERE d.Consignment<>'' AND d.Order_ID=%d", $order->ID));
								if($data->TotalRows > 0) {
									$couriers .= '<p>Please use the following links to track your shipments.</p>';
									$couriers .= '<table width="100%" class="order">';
									$couriers .= '<tr><th width="50%" align="left">Courier</th><th width="50%" align="left">Tracking Code</th></tr>';

									while($data->Row) {
										$couriers .= sprintf('<tr><td><a href="%s" target="_blank">%s</a></td><td>%s</td></tr>', $data->Row['Courier_URL'], $data->Row['Courier_Name'], $data->Row['Consignment']);

										$data->Next();
									}

									$couriers .= '</table>';
								}
								$data->Disconnect();

								$findReplace = new FindReplace();
								$findReplace->Add('/\[ORDER_ID\]/', $order->ID);
								$findReplace->Add('/\[ORDER_REFERENCE\]/', $order->Prefix.$order->ID);
								$findReplace->Add('/\[POSTAGE_NAME\]/', $order->Postage->Name);
								$findReplace->Add('/\[POSTAGE_DAYS\]/', $order->Postage->Days);
								$findReplace->Add('/\[DESPATCH_DATE\]/', date('d/m/Y', $despatchDate));
								$findReplace->Add('/\[DESPATCH_EXPECTED_DATE\]/', date('d/m/Y', $postageDate));
								$findReplace->Add('/\[COURIERS\]/', $couriers);
								
								$templateHtml = $findReplace->Execute(Template::GetContent(sprintf('email_phone_%s_wait', str_replace(' ', '_', strtolower($reason->Title)))));
								
								$findReplace = new FindReplace();
								$findReplace->Add('/\[BODY\]/', $templateHtml);
								$findReplace->Add('/\[NAME\]/', sprintf('%s %s', $order->Customer->Contact->Person->Name, $order->Customer->Contact->Person->LastName));

								$templateHtml = $findReplace->Execute(Template::GetContent('email_template_standard'));

								$mail = new htmlMimeMail5();
								$mail->setFrom($GLOBALS['EMAIL_FROM']);
								$mail->setSubject(sprintf('Delay Acknowledgment: %s [#%d] - %s', $reason->Title, $order->ID, $GLOBALS['COMPANY']));
								$mail->setText('This is an HTML email. If you only see this text your email client only supports plain text emails.');
								$mail->setHTML($templateHtml);
								$mail->send(array($email));

								$log = new ReturnLog();
								$log->orderId = $order->ID;
								$log->type = 'email';
								$log->log = sprintf('Delay Acknowledgment email sent for \'%s\' with despatch too young notification.', $reason->Title);
								$log->add();

							} else {
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
											$quantity += $order->Line[$j]->Quantity;
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
											$order->Line[$j]->Product->Get();

											if($standardShipping != $order->Line[$j]->Product->ShippingClass->ID) {
												$order->Line[$j]->Product->ShippingClass->Get();

												$replace = false;

												$log->log = sprintf('Shipping class is not standard (%s).', $order->Line[$j]->Product->ShippingClass->Name);
												$log->add();
											}
										}
									}
								}

								if($replace) {
									$cost = 0 ;

									for($j=0; $j<count($order->Line); $j++) {
										if($order->Line[$j]->DespatchID > 0) {
											$cost += $order->Line[$j]->Cost;
										}
									}

									if(($cost <= 0) || ($cost > Setting::GetValue('phone_system_not_received_order_cost'))) {
										$replace = false;

										$log->log = sprintf('Cost of replacement too great (&pound;%s).', number_format($cost, 2, '.', ','));
										$log->add();
									}
								}

								if($replace) {
									$abuse = new ReturnAbuse();

									if($abuse->getByOrderId($order->ID)) {
										$abuse->increment();

										if($abuse->counter > 1) {
											$replace = false;

											$log->log = sprintf('Return abuse counter exceeded (%d).', $abuse->counter);
											$log->add();
										}
									} else {
										$abuse->counter = 1;
										$abuse->add();
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
									$order->Prefix = 'N';
									$order->Referrer = '';
									$order->PaymentMethod->GetByReference('foc');
									$order->ParentID = $order->ID;
									$order->Add();
									
									for($j=0; $j<count($order->Line); $j++) {
										if($order->Line[$j]->DespatchID > 0) {
											$order->Line[$j]->Order = $order->ID;
											$order->Line[$j]->DespatchID = 0;
											$order->Line[$j]->InvoiceID = 0;
											$order->Line[$j]->Status = '';
											$order->Line[$j]->DespatchedFrom->ID = 0;
											$order->Line[$j]->FreeOfCharge = 'Y';
											$order->Line[$j]->Add();
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
									$mail->setSubject(sprintf('Delay Acknowledgment: %s [#%d] - %s', $reason->Title, $order->ID, $GLOBALS['COMPANY']));
									$mail->setText('This is an HTML email. If you only see this text your email client only supports plain text emails.');
									$mail->setHTML($templateHtml);
									$mail->send(array($email));

									$log = new ReturnLog();
									$log->orderId = $originalOrderId;
									$log->type = 'email';
									$log->log = sprintf('Delay Acknowledgment email sent for \'%s\' with replacement generated notification.', $reason->Title);
									$log->add();

									$log = new ReturnLog();
									$log->orderId = $originalOrderId;
									$log->type = 'order';
									$log->referenceId = $order->ID;
									$log->log = sprintf('Replacement order generated for \'%s\'.', $reason->Title);
									$log->add();

								} else {
									$findReplace = new FindReplace();
									$findReplace->Add('/\[ORDER_ID\]/', $order->ID);
									$findReplace->Add('/\[ORDER_REFERENCE\]/', $order->Prefix.$order->ID);

									$templateHtml = $findReplace->Execute(Template::GetContent(sprintf('email_phone_%s_manual', str_replace(' ', '_', strtolower($reason->Title)))));

									$enquiry = new Enquiry();
									$enquiry->Customer->ID = $order->Customer->ID;
									$enquiry->Subject = sprintf('Not Received Order #%d', $order->ID);
									$enquiry->Status = 'Unread';
									$enquiry->Type->GetByDeveloperKey('customerservices');
									$enquiry->Add(false);

									$enquiryLine = new EnquiryLine();
									$enquiryLine->Enquiry->ID = $enquiry->ID;
									$enquiryLine->Message = $templateHtml;
									$enquiryLine->EmailAddress = $email;
									$enquiryLine->Add();

									$log = new ReturnLog();
									$log->orderId = $order->ID;
									$log->type = 'enquiry';
									$log->referenceId = $enquiry->ID;
									$log->log = sprintf('Customer Service enquiry generated for \'%s\'.', $reason->Title);
									$log->add();
								}
							}
						}

						break;
				}
			}
		}
	}
}