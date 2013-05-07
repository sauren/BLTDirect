<?php
require_once($GLOBALS['DIR_WS_ADMIN'] . 'services/google-checkout/classes/GoogleMerchant.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'services/google-checkout/classes/GoogleLog.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'services/google-checkout/classes/GoogleMerchantCalculation.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'services/google-checkout/classes/GoogleRequest.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerSession.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/XmlParser.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/GlobalTaxCalculator.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Payment.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Despatch.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Invoice.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TaxCalculator.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierShippingCalculator.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ShippingCostCalculator.php');

class GoogleResponse{
	var $Merchant;
	var $SchemaUrl;
	var $Log;
	var $_Data;
	var $_Root;
	var $_XmlParser;
	var $Session;
	var $Cart;
	var $GlobalTaxCalculator;
	var $Logging;

	function GoogleResponse($id=null, $key=null){
		global $cart;
		global $session;
		global $globalTaxCalculator;
		$cart = &$this->Cart;
		$session = &$this->Session;
		$globalTaxCalculator = &$this->GlobalTaxCalculator;

		$this->Merchant = new GoogleMerchant($id, $key);
		$this->SchemaUrl = "http://checkout.google.com/schema/2";
		$this->Log = new GoogleLog;
		$this->_Data = array();
		$this->Logging = true;
	}

	function ParseXml($xml=null){
		if(!is_null($xml) && !empty($xml)){
			if($this->Logging) {
				$this->Log->LogRequest($xml);
			}
			
			$this->_XmlParser = new XmlParser($xml);
			$this->_Root = $this->_XmlParser->GetRoot();
			$this->_Data = $this->_XmlParser->GetData();
			return true;
		} else {
			return false;
		}
	}

	function Error($message){
		if($this->Logging) {
			$this->Log->LogError($message);
		}
	}

	function Execute(){
		if($this->StartSession()){
			switch ($this->_Root){
				case "request-received":
					if($this->Logging) {
						$this->Log->Add('DEBUG', 'request-received');
					}
					break;
				case "error":
					if($this->Logging) {
						$this->Log->Add('DEBUG', 'error');
					}
					break;
				case "diagnosis":
					if($this->Logging) {
						$this->Log->Add('DEBUG', 'diagnosis');
					}
					break;
				case "checkout-redirect":
					if($this->Logging) {
						$this->Log->Add('DEBUG', 'checkout-redirect');
					}
					break;
				case "merchant-calculation-callback":
					$this->onMerchantCalculationCallback();
					break;
				case "merchant-calculation-callback-single":
					if($this->Logging) {
						$this->Log->Add('DEBUG', 'merchant-calculation-callback-single');
					}
					break;
				case "new-order-notification":
					$this->onNewOrderNotification();
					break;
				case "order-state-change-notification":
					$this->onOrderStateChange();
					break;
				case "charge-amount-notification":
					$this->onChargeAmount();
					break;
				case "chargeback-amount-notification":
					$this->onChargebackAmount();
					break;
				case "refund-amount-notification":
					$this->onRefundAmount();
					break;
				case "risk-information-notification":
					$this->onRiskInformationNotification();
					break;
				default:
					break;
			}
		} else {
			$this->Error('Unable to retrieve session-data from Response XML.');
		}
	}

	/*
	Method: onChargebackAmount()
	-----------------------------------------------------------------------------------------------
	Used when Google sends a confirmation that a card has been chargedback.
	*/
	function onChargebackAmount(){
		// add order note
		// add payment history
		$customId = $this->_Data[$this->_Root]['google-order-number']['VALUE'];
		$order = new Order();
		$order->Get($customId, true);

		$note = new OrderNote();
		$note->OrderID = $order->ID;
		$note->IsPublic = 'N';
		$note->IsAlert = 'Y';
		$note->Subject = 0;
		$note->TypeID = 0;
		$note->Message = '<strong>Google Checkout: Chargeback Notification!</strong><br />';
		$note->Message .= sprintf('Amount Chargedback (%s): %s.<br />',  $this->_Data[$this->_Root]['latest-chargeback-amount']['currency'], $this->_Data[$this->_Root]['latest-chargeback-amount']['VALUE']);
		$note->Message .= sprintf('Total Chargebacks on Order (%s): %s.<br />',  $this->_Data[$this->_Root]['total-chargeback-amount']['currency'],  $this->_Data[$this->_Root]['total-chargeback-amount']['VALUE']);
		$note->Add();

		// this probably needs some work on it
		$payment = new Payment();
		$payment->Order->ID = $order->ID;
		$payment->Type = 'CHARGEBACK';
		$payment->SecurityKey = 'GoogleCheck';
		$payment->Reference = $this->_Data[$this->_Root]['serial-number'];
		$payment->Status = 'OK';
		$payment->StatusDetail = 'Chargeback made through Google Checkout.';
		$payment->Amount = $this->_Data[$this->_Root]['latest-chargeback-amount']['VALUE'];
		$payment->Add();

		header('HTTP/1.0 200 OK');
	}

	/*
	Method: onRefundAmount()
	-----------------------------------------------------------------------------------------------
	Used when Google sends a confirmation that a card has been refunded.
	*/
	function onRefundAmount(){
		// add order note
		// add payment history
		$customId = $this->_Data[$this->_Root]['google-order-number']['VALUE'];
		$order = new Order();
		$order->Get($customId, true);

		$note = new OrderNote();
		$note->OrderID = $order->ID;
		$note->IsPublic = 'N';
		$note->IsAlert = 'Y';
		$note->Subject = 0;
		$note->TypeID = 0;
		$note->Message = '<strong>Google Checkout: Refund Notification!</strong><br />';
		$note->Message .= sprintf('Amount Refunded (%s): %s.<br />',  $this->_Data[$this->_Root]['latest-refund-amount']['currency'], $this->_Data[$this->_Root]['latest-refund-amount']['VALUE']);
		$note->Message .= sprintf('Total Refunds on Order (%s): %s.<br />',  $this->_Data[$this->_Root]['total-refund-amount']['currency'],  $this->_Data[$this->_Root]['total-refund-amount']['VALUE']);
		$note->Add();

		// this probably needs some work on it
		$payment = new Payment();
		$payment->Order->ID = $order->ID;
		$payment->Type = 'REFUND';
		$payment->SecurityKey = 'GoogleCheckout';
		$payment->Reference = $this->_Data[$this->_Root]['serial-number'];
		$payment->Status = 'OK';
		$payment->StatusDetail = 'Refund made through Google Checkout.';
		$payment->Amount = $this->_Data[$this->_Root]['latest-refund-amount']['VALUE'];
		$payment->Add();

		header('HTTP/1.0 200 OK');
	}
	/*
	Method: onChargeAmount()
	-----------------------------------------------------------------------------------------------
	Used when Google sends a confirmation that a card has been charged.
	*/
	function onChargeAmount(){
		// add order note
		// add payment history
		$customId = $this->_Data[$this->_Root]['google-order-number']['VALUE'];

		$order = new Order();
		if($order->Get($customId, true)) {

			$note = new OrderNote();
			$note->OrderID = $order->ID;
			$note->IsPublic = 'N';
			$note->IsAlert = 'Y';
			$note->Subject = 0;
			$note->TypeID = 0;
			$note->Message = '<strong>Google Checkout: Payment Notification!</strong><br />';
			$note->Message .= sprintf('Payment Taken (%s): %s.<br />',  $this->_Data[$this->_Root]['latest-charge-amount']['currency'], $this->_Data[$this->_Root]['latest-charge-amount']['VALUE']);
			$note->Message .= sprintf('Total Payments on Order (%s): %s.<br />',  $this->_Data[$this->_Root]['total-charge-amount']['currency'],  $this->_Data[$this->_Root]['total-charge-amount']['VALUE']);
			$note->Add();
			
			$payment = new Payment();
			$payment->Order->ID = $order->ID;
			$payment->Type = 'PAYMENT';
			$payment->SecurityKey = 'GoogleCheckout';
			$payment->Reference = $this->_Data[$this->_Root]['serial-number'];
			$payment->Status = 'OK';
			$payment->StatusDetail = 'Payment taken through Google Checkout.';
			$payment->Amount = $this->_Data[$this->_Root]['latest-charge-amount']['VALUE'];
			$payment->Add();
		}

		header('HTTP/1.0 200 OK');
	}

	/*
	Method: onOrderStateChange()
	-----------------------------------------------------------------------------------------------
	Used when Google sends a state change to us.
	*/
	function onOrderStateChange(){
		// need to find the order by the encrypted order number
		$customId = $this->_Data[$this->_Root]['google-order-number']['VALUE'];

		$order = new Order();
		if($order->Get($customId, true)) {

			$note = new OrderNote();
			$note->OrderID = $order->ID;
			$note->IsPublic = 'N';
			$note->IsAlert = 'Y';
			$note->Subject = 0;
			$note->TypeID = 0;
			$note->Message = '<strong>Google Checkout: Order Changed State!</strong><br />';
			$note->Message .= sprintf('New Financial State: %s.<br />',  $this->_Data[$this->_Root]['new-financial-order-state']['VALUE']);
			$note->Message .= sprintf('New Fulfillment State: %s.<br />',  $this->_Data[$this->_Root]['new-fulfillment-order-state']['VALUE']);
			$note->Message .= sprintf('Previous Financial State: %s.<br />',  $this->_Data[$this->_Root]['previous-financial-order-state']['VALUE']);
			$note->Message .= sprintf('Previous Fulfillment State: %s.<br />',  $this->_Data[$this->_Root]['previous-fulfillment-order-state']['VALUE']);
			$note->Add();

			switch($this->_Data[$this->_Root]['new-fulfillment-order-state']['VALUE']) {
				case 'DELIVERED':
					$generateInvoice = true;

					$order->PaymentMethod->Get();
					$order->GetLines();
					$order->Customer->Get();
					$order->Customer->Contact->Get();
					$chargeShipping = ($order->HasInvoices)?false:true;

					// Get Payment Id
					$data = new DataQuery(sprintf("SELECT Payment_ID FROM payment WHERE Gateway_ID=0 AND Order_ID=%d AND Transaction_Type LIKE 'PAYMENT' AND Status LIKE 'OK' LIMIT 0, 1", $order->ID));
					if($data->TotalRows > 0) {
						$paymentId = $payment->ID;
					}
					$data->Disconnect();

					// Get all warehouses
					$warehouses = array();

					for($i=0; $i<count($order->Line); $i++){
						$warehouses[$order->Line[$i]->DespatchedFrom->ID] = $order->Line[$i]->DespatchedFrom;
					}

					// Despatch from all warehouses
					foreach($warehouses as $warehouseId => $warehouse) {
						$invoice = new Invoice;

						if($generateInvoice){
							$invoice->PaymentMethod->ID = $order->PaymentMethod->ID;
							$invoice->Order->ID = $order->ID;
							$invoice->Customer->ID = $order->Customer->ID;
							if(empty($order->Customer->Contact->ID)) $order->Customer->Get();

							$invoice->Payment = 0;
							$invoice->IsDespatched = 'Y';
							$invoice->Organisation = $order->InvoiceOrg;
							$invoice->Person->Title = $order->Invoice->Title;
							$invoice->Person->Name = addslashes(stripslashes($order->Invoice->Name));
							$invoice->Person->Initial = addslashes(stripslashes($order->Invoice->Initial));
							$invoice->Person->LastName = addslashes(stripslashes($order->Invoice->LastName));
							$invoice->Person->Address->Line1 = addslashes(stripslashes($order->Invoice->Address->Line1));
							$invoice->Person->Address->Line2 = addslashes(stripslashes($order->Invoice->Address->Line2));
							$invoice->Person->Address->Line3 = addslashes(stripslashes($order->Invoice->Address->Line3));
							$invoice->Person->Address->City = addslashes(stripslashes($order->Invoice->Address->City));
							$invoice->Person->Address->Region->ID = $order->Invoice->Address->Region->ID;
							$invoice->Person->Address->Region->Get();
							$invoice->Person->Address->Country->ID = $order->Invoice->Address->Country->ID;
							$invoice->Person->Address->Country->Get();
							$invoice->Person->Address->Zip = $order->Invoice->Address->Zip;
							$invoice->NominalCode = $order->NominalCode;
                            $invoice->IsPaid = 'N';

		                    if($order->PaymentMethod->Reference == 'credit') {
								$invoice->DueOn = date('Y-m-d H:i:s', time() + (86400 * $order->Customer->CreditPeriod));

							} elseif($order->PaymentMethod->Reference == 'card') {
								$invoice->IsPaid = 'Y';
							}
						}

						// Generate Despatch Note
						$data = new DataQuery(sprintf("SELECT * FROM courier WHERE Is_Default='Y'"));
						$courierID = $data->Row['Courier_ID'];
						$data->Disconnect();

						// Calculate weight
						$totalWeight = 0;
						for($i=0; $i<count($order->Line); $i++){
							if(empty($order->Line[$i]->DespatchID) && ($order->Line[$i]->Status != 'Cancelled')){
								$order->Line[$i]->Product->Get();
								$totalWeight += $order->Line[$i]->Product->Weight * $order->Line[$i]->Quantity;
							}
						}

						$despatch = new Despatch;
						$despatch->Order->ID = $order->ID;
						$despatch->Courier->ID = $courierID;
						$despatch->Consignment = '';
						$despatch->Weight = $totalWeight;
						$despatch->Boxes = 1;
						$despatch->Postage->ID = $order->Postage->ID;
						$despatch->DespatchedOn = getDatetime();
						$despatch->DespatchedFrom->ID = $warehouseId;
						$despatch->Person->Title = $order->Shipping->Title;
						$despatch->Person->Name = $order->Shipping->Name;
						$despatch->Person->Initial = $order->Shipping->Initial;
						$despatch->Person->LastName = $order->Shipping->LastName;
						$despatch->Organisation = $order->ShippingOrg;
						$despatch->Person->Address->Line1 = $order->Shipping->Address->Line1;
						$despatch->Person->Address->Line2 = $order->Shipping->Address->Line2;
						$despatch->Person->Address->Line3 = $order->Shipping->Address->Line3;
						$despatch->Person->Address->City = $order->Shipping->Address->City;
						$despatch->Person->Address->Region->ID = $order->Shipping->Address->Region->ID;
						$despatch->Person->Address->Region->Get();
						$despatch->Person->Address->Country->ID = $order->Shipping->Address->Country->ID;
						$despatch->Person->Address->Country->Get();
						$despatch->Person->Address->Zip = $order->Shipping->Address->Zip;

                        $cost = 0;
						$weight = 0;

						$shippingProducts = array();

						for($i=0; $i<count($order->Line); $i++){
							if($order->Line[$i]->DespatchedFrom->ID == $warehouseId) {
								if(empty($order->Line[$i]->DespatchID) && ($order->Line[$i]->Status != 'Cancelled')){

									$despatchedLine = new DespatchLine();
									$despatchedLine->Quantity = $order->Line[$i]->Quantity;
									$despatchedLine->Product->ID = $order->Line[$i]->Product->ID;
									$despatchedLine->Product->Name = $order->Line[$i]->Product->Name;
									$despatchedLine->IsComplementary = $order->Line[$i]->IsComplementary;

									$despatch->Line[] = $despatchedLine;

									// set the status to despatched
									$order->Line[$i]->Status = "Despatched";
									$order->Line[$i]->DespatchID = NULL;

									// do we generate an invoice for this line?
									if($generateInvoice && empty($order->Line[$i]->InvoiceID)){

										// Increment Running Totals
										$invoice->SubTotal += $order->Line[$i]->Total;
										$invoice->Tax += $order->Line[$i]->Tax;
										$invoice->Discount += $order->Line[$i]->Discount;

										// add invoice line
										$invoiceLine = new InvoiceLine;
										$invoiceLine->Quantity = $order->Line[$i]->Quantity;
										$invoiceLine->Description = $order->Line[$i]->Product->Name;
										$invoiceLine->Product->ID = $order->Line[$i]->Product->ID;
										$invoiceLine->Price = $order->Line[$i]->Price;
										$invoiceLine->Total = $order->Line[$i]->Total;
										$invoiceLine->Discount = $order->Line[$i]->Discount;
										$invoiceLine->DiscountInformation = $order->Line[$i]->DiscountInformation;
										$invoiceLine->Tax = $order->Line[$i]->Tax;
										$invoice->Line[] = $invoiceLine;

										$order->Line[$i]->Status = 'Invoiced';
										$order->Line[$i]->InvoiceID = NULL;
									}

                                    $cost += $order->Line[$i]->Cost * $order->Line[$i]->Quantity;
									$weight += $order->Line[$i]->Product->Weight * $order->Line[$i]->Quantity;

                                    $shippingProducts[] = array('Quantity' => $order->Line[$i]->Quantity, 'ShippingClassID' => $order->Line[$i]->Product->ShippingClass->ID);
								}
							}
						}

                        if($warehouse->Type == 'S') {
							$calc = new SupplierShippingCalculator($despatch->Person->Address->Country->ID, $despatch->Person->Address->Region->ID, $cost, $weight, $despatch->Postage->ID, $warehouse->Contact->ID);
						} elseif($warehouse->Type == 'B') {
							$calc = new ShippingCostCalculator($despatch->Person->Address->Country->ID, $despatch->Person->Address->Region->ID, $cost, $weight, $despatch->Postage->ID);
						}
						
						foreach($shippingProducts as $item) {
							$calc->Add($item['Quantity'], $item['ShippingClassID']);
						}

						// Do we need to invoice?
						if($generateInvoice && ((count($invoice->Line) > 0))) {
                    		$taxCalculator = new GlobalTaxCalculator($order->Shipping->Address->Country->ID, $order->Shipping->Address->Region->ID);
							$taxCalculator->GetClasses();

				            $data = new DataQuery("SELECT Tax_Class_ID FROM tax_class WHERE Is_Default='Y'");
							if($data->TotalRows > 0) {
								 $invoice->TaxRate = $taxCalculator->Classes['class_'.$data->Row['Tax_Class_ID']]->Calculator->TaxRate;
							}
							$data->Disconnect();

							if($chargeShipping && $generateInvoice){
								$invoice->Shipping = $order->TotalShipping;

								$tempTax = new TaxCalculator($order->TotalShipping, $order->Billing->Address->Country->ID, $order->Billing->Address->Region->ID, $GLOBALS['DEFAULT_TAX_ON_SHIPPING']);
								$invoice->Tax += $tempTax->Tax;
							}

							$invoice->Tax = round($invoice->Tax, 2);
							$invoice->Total = $invoice->SubTotal + $invoice->Tax + $invoice->Shipping - $invoice->Discount;
						}

						// Change the quantities of the product from each order
						for($i=0; $i<count($order->Line); $i++){
							if($order->Line[$i]->DespatchedFrom->ID == $warehouseId) {
								if(empty($order->Line[$i]->DespatchID) && ($order->Line[$i]->Status != 'Cancelled')){
									$order->Line[$i]->DespatchedFrom->ChangeQuantity($order->Line[$i]->Product->ID, $order->Line[$i]->Quantity);
								}
							}
						}

						// Now add Invoice and Its Lines
						if($generateInvoice && (count($invoice->Line) > 0)) {
							$invoice->Add();

							for($i=0; $i < count($invoice->Line); $i++){
								$invoice->Line[$i]->InvoiceID = $invoice->ID;
								$invoice->Line[$i]->Add();
							}
						}

						$despatch->PostageCost = $calc->GetTotal();
						$despatch->Add();

						for($i=0; $i < count($despatch->Line); $i++){
							$despatch->Line[$i]->Despatch = $despatch->ID;
							$despatch->Line[$i]->Add();
						}

						// Update Order and Its Lines
						for($i=0; $i < count($order->Line); $i++){
							if($order->Line[$i]->DespatchedFrom->ID == $warehouseId) {
								if($order->Line[$i]->Status != 'Cancelled') {
									if($generateInvoice) {
										if(empty($order->Line[$i]->InvoiceID) && $generateInvoice && count($invoice->Line) > 0 && ($order->Line[$i]->Status == 'Invoiced' || $order->Line[$i]->Status == 'Despatched')) $order->Line[$i]->InvoiceID = $invoice->ID;
									}
									if(empty($order->Line[$i]->DespatchID) && ($order->Line[$i]->Status == 'Invoiced' || $order->Line[$i]->Status == 'Despatched')) $order->Line[$i]->DespatchID = $despatch->ID;
									$order->Line[$i]->Update();
								}
							}
						}

						// Mark Invoice as Paid
						if($generateInvoice && (count($invoice->Line) > 0)) {
							$invoice->IsPaid = 'Y';
							$invoice->Paid = $invoice->Total;
							$invoice->Despatch = $despatch->ID;
							$invoice->Payment = $paymentId;
							$invoice->Update();
						}

						// Email Invoice and Despatch Note according to Warehouse Options
						if($warehouse->Despatch == 'B' || $warehouse->Despatch == 'E') {
							$despatch->EmailCustomer();
						}

						if($generateInvoice && (count($invoice->Line) > 0)) {
							if($warehouse->Invoice == 'B' || $warehouse->Invoice == 'E') {
								$invoice->EmailCustomer();
							}
						}
					}

					$order->Status = 'Despatched';
					$order->Despatch(false);
					$order->Update();

					break;
			}

			switch($this->_Data[$this->_Root]['new-financial-order-state']['VALUE']) {
				case 'CANCELLED':
				case 'CANCELLED_BY_GOOGLE':
					if(strtolower($order->Status) != 'despatched') {
						if(strtolower($order->Status) == 'partially despatched'){
							$order->GetLines();

							for($i=0; $i < count($order->Line); $i++){
								if(empty($order->Line[$i]->DespatchID) && empty($order->Line[$i]->InvoiceID)){
									$order->Line[$i]->Status = 'Cancelled';
									$order->Line[$i]->Update();
								}
							}

							$order->Status = 'Despatched';
							$order->Update();
						} else {
							$order->GetLines();

							for($i=0; $i < count($order->Line); $i++){
								$order->Line[$i]->Status = 'Cancelled';
								$order->Line[$i]->Update();
							}

							$order->Status = 'Cancelled';
							$order->Update();
						}
					}

					break;
			}
		}

		header('HTTP/1.0 200 OK');
	}

	/*
	Method: onRiskInformationNotification()
	-----------------------------------------------------------------------------------------------
	Used when Google sends customer details back.
	*/
	function onRiskInformationNotification(){
		// need to find the order by the encrypted order number
		$customId = $this->_Data[$this->_Root]['google-order-number']['VALUE'];

		$order = new Order();
		if($order->Get($customId, true)) {
			$note = new OrderNote();
			$note->OrderID = $order->ID;
			$note->IsPublic = 'N';
			$note->IsAlert = 'Y';
			$note->Subject = 0;
			$note->TypeID = 0;
			$note->Message = '<strong>Google Checkout: Risk Assessment Received!</strong><br />';
			$note->Message .= sprintf('AVS Response: %s.<br />',  $this->_Data[$this->_Root]['risk-information']['avs-response']['VALUE']);
			$note->Message .= sprintf('CVN Response: %s.<br />',  $this->_Data[$this->_Root]['risk-information']['cvn-response']['VALUE']);
			$note->Message .= sprintf('Eligible for Card Protection: %s.<br />',  $this->_Data[$this->_Root]['risk-information']['eligible-for-protection']['VALUE']);
			$note->Message .= sprintf('Buyers IP Address: %s.<br />',  $this->_Data[$this->_Root]['risk-information']['ip-address']['VALUE']);
			$note->Add();
		}

		header('HTTP/1.0 200 OK');
	}

	/*
	Method: onNewOrderNotification()
	-----------------------------------------------------------------------------------------------
	Used when Google creates a new order notfication
	*/
	function onNewOrderNotification(){
		$orderCheck = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM orders WHERE Custom_Order_No LIKE '%s'", mysql_real_escape_string($this->_Data[$this->_Root]['google-order-number']['VALUE'])));
		if($orderCheck->Row['Counter'] == 0) {
			$customersEmail = $this->_Data[$this->_Root]['buyer-billing-address']['email']['VALUE'];
			$order = new Order();
			$order->PaymentMethod->GetByReference('google');
			$customerExists = false;

			if($order->Customer->FindByEmail($customersEmail)){
				$customerExists = true;
				$order->Customer->Contact->Get();
			} 

			$order->Customer->Contact->Person->Address->Line1 = addslashes($this->_Data[$this->_Root]['buyer-billing-address']['address1']['VALUE']);
			$order->Customer->Contact->Person->Address->Line2 = addslashes($this->_Data[$this->_Root]['buyer-billing-address']['address2']['VALUE']);
			$order->Customer->Contact->Person->Address->City = addslashes($this->_Data[$this->_Root]['buyer-billing-address']['city']['VALUE']);
			$order->Customer->Contact->Person->Address->Country->ISOCode2 = $this->_Data[$this->_Root]['buyer-billing-address']['country-code']['VALUE'];
			$order->Customer->Contact->Person->Address->Country->GetIDFromIsoCode2();
			$order->Customer->Contact->Person->Address->Region->GetIDFromString(addslashes($this->_Data[$this->_Root]['buyer-billing-address']['region']['VALUE']));
			$order->Customer->Contact->Person->Address->Zip = addslashes($this->_Data[$this->_Root]['buyer-billing-address']['postal-code']['VALUE']);
			$order->Customer->Contact->Person->Phone1 = addslashes($this->_Data[$this->_Root]['buyer-billing-address']['phone']['VALUE']);
			$order->Customer->Contact->Person->Email = addslashes($this->_Data[$this->_Root]['buyer-billing-address']['email']['VALUE']);
			$order->Customer->Contact->OnMailingList = ($this->_Data[$this->_Root]['buyer-marketing-preferences']['email-allowed'] == 'true')?'H':'N';

			if(!$customerExists){
				$order->Customer->Username = addslashes($customersEmail);

				$password = new Password(PASSWORD_LENGTH_CUSTOMER);
				
				$order->Customer->SetPassword($password->Value);
				$order->Customer->Contact->Type = 'I';
				$order->Customer->Contact->IsCustomer = 'Y';
				$order->Customer->Contact->Person->Name = addslashes($this->_Data[$this->_Root]['buyer-billing-address']['structured-name']['first-name']['VALUE']);
				$order->Customer->Contact->Person->LastName = addslashes($this->_Data[$this->_Root]['buyer-billing-address']['structured-name']['last-name']['VALUE']);
				$order->Customer->Contact->Add();
				$order->Customer->Add();
			}

			$order->Billing = $order->Customer->Contact->Person;

			$order->OrderedOn = getDatetime();
			$order->Status = 'Unread';
			$order->Postage->GetByName($this->_Data[$this->_Root]['order-adjustment']['shipping']['merchant-calculated-shipping-adjustment']['shipping-name']['VALUE']);

			// Set Totals
			$order->Total = $this->_Data[$this->_Root]['order-total']['VALUE'];
			$order->TotalShipping = $this->_Data[$this->_Root]['order-adjustment']['shipping']['merchant-calculated-shipping-adjustment']['shipping-cost']['VALUE'];
			$order->TotalDiscount = 0;
			$order->TotalTax = $this->_Data[$this->_Root]['order-adjustment']['total-tax']['VALUE'];
			$order->TotalLines = count($this->_Data[$this->_Root]['shopping-cart']['items']['item']);
			$order->SubTotal = $order->Total - $order->TotalTax - $order->TotalShipping;

			// shipping address
			$order->Shipping->Name = addslashes($this->_Data[$this->_Root]['buyer-shipping-address']['structured-name']['first-name']['VALUE']);
			$order->Shipping->LastName = addslashes($this->_Data[$this->_Root]['buyer-shipping-address']['structured-name']['last-name']['VALUE']);
			$order->Shipping->Phone1 = addslashes($this->_Data[$this->_Root]['buyer-shipping-address']['phone']['VALUE']);
			$order->Shipping->Email = addslashes($this->_Data[$this->_Root]['buyer-shipping-address']['email']['VALUE']);
			$order->Shipping->Address->Line1 = addslashes($this->_Data[$this->_Root]['buyer-shipping-address']['address1']['VALUE']);
			$order->Shipping->Address->Line2 = addslashes($this->_Data[$this->_Root]['buyer-shipping-address']['address2']['VALUE']);
			$order->Shipping->Address->City = addslashes($this->_Data[$this->_Root]['buyer-shipping-address']['city']['VALUE']);
			$order->Shipping->Address->Country->ISOCode2 = $this->_Data[$this->_Root]['buyer-shipping-address']['country-code']['VALUE'];
			$order->Shipping->Address->Country->GetIDFromIsoCode2();
			$order->Shipping->Address->Region->GetIDFromString(addslashes($this->_Data[$this->_Root]['buyer-shipping-address']['region']['VALUE']));
			$order->Shipping->Address->Zip = addslashes($this->_Data[$this->_Root]['buyer-shipping-address']['postal-code']['VALUE']);

			if($order->Billing->Address->Zip == $order->Shipping->Address->Zip){
				if(empty($order->Billing->Name)){
					$order->Billing->Name = $order->Shipping->Name;
				}
				if(empty($order->Billing->LastName)){
					$order->Billing->LastName = $order->Shipping->LastName;
				}
				if(empty($order->Billing->Phone1)){
					$order->Billing->Phone1 = $order->Shipping->Phone1;
				}
				if(empty($order->Billing->Address->Line1)){
					$order->Billing->Address->Line1 = $order->Shipping->Address->Line1;
				}
				if(empty($order->Billing->Address->Line2)){
					$order->Billing->Address->Line2 = $order->Shipping->Address->Line2;
				}
				if(empty($order->Billing->Address->City)){
					$order->Billing->Address->City = $order->Shipping->Address->City;
				}
				if(empty($order->Billing->Address->Country->Name)){
					$order->Billing->Address->Country->ID = $order->Shipping->Address->Country->ID;
					$order->Billing->Address->Country->Get();
				}
				if(empty($order->Billing->Address->Region->Name)){
					$order->Billing->Address->Region->ID = $order->Shipping->Address->Region->ID;
					$order->Billing->Address->Region->Get();
				}
			}

			// Setup google details
			$order->Card->Type->ID = 0;
			$order->Card->SetNumber($this->_Data[$this->_Root]['google-order-number']['VALUE']);
			$order->CustomID = $this->_Data[$this->_Root]['google-order-number']['VALUE'];

			// add coupon id
			$order->Coupon->ID = $this->_Data[$this->_Root]['shopping-cart']['merchant-private-data']['order-coupon']['VALUE'];

			//add the order first
			$order->Add();

			// send merchant order number to google checkout
			$googleRequest = new GoogleRequest();
			$googleRequest->addMerchantOrderNumber($order->CustomID, $order->ID);

			// add the lines of the order
			foreach ($this->_Data[$this->_Root]['shopping-cart']['items'] as $key=>$line){

				if(array_key_exists(0, $line)) {

					foreach($line as $subLine) {
						$orderLine = $order->AddLine($subLine['quantity']['VALUE'], $subLine['merchant-item-id']['VALUE']);
						$orderLine->Price = $subLine['unit-price']['VALUE'];
						$orderLine->Total = $orderLine->Price * $orderLine->Quantity;
						$orderLine->Update();
					}
				} else {
					$orderLine = $order->AddLine($line['quantity']['VALUE'], $line['merchant-item-id']['VALUE']);
					$orderLine->Price = $line['unit-price']['VALUE'];
					$orderLine->Total = $orderLine->Price * $orderLine->Quantity;
					$orderLine->Update();
				}
			}

			$order->GetLines();

			$customerId = $this->_Data[$this->_Root]['shopping-cart']['merchant-private-data']['order-coupon']['VALUE'];

			// use merchant private data customer id, to identify the if of the customer IF logged in.
			// also prevents the customer using the email address of a friend to achieve higher discount schema.
			if(is_numeric($customerId) && ($customerId > 0)) {
				$order->DiscountCollection->Get($customerId);
			}

			$taxCalculator = new GlobalTaxCalculator($order->Shipping->Address->Country->ID, $order->Shipping->Address->Region->ID);

			if(!empty($order->Coupon->ID)) $order->Coupon->Get();
			for($i=0; $i < count($order->Line); $i++){
				$order->Line[$i]->Product->Get();
				$order->Line[$i]->Discount = 0;
				$order->Line[$i]->Tax = 0;
				$order->Line[$i]->DiscountInformation = '';

				// recalculate discounts based on attached coupon
				if(!empty($order->Coupon->ID)){
					// check if this product can be discounted
					$couponLineTotal = $order->Coupon->DiscountProduct($order->Line[$i]->Product, $order->Line[$i]->Quantity);
					if($couponLineTotal < $order->Line[$i]->Total){
						$order->Line[$i]->Discount = $order->Line[$i]->Total - $couponLineTotal;
						$order->Line[$i]->DiscountInformation = sprintf('%s (Ref: %s)', $order->Coupon->Name, $order->Coupon->Reference);
					}
				}

				// recalculate discounts based on customer discounts
				if(count($order->DiscountCollection->Line) > 0){
					list($tempLineTotal, $discountName) = $order->DiscountCollection->DiscountProduct($order->Line[$i]->Product, $order->Line[$i]->Quantity);

					if((($order->Line[$i]->Total - $tempLineTotal) > $order->Line[$i]->Discount) && ($tempLineTotal > 0)){
						$order->Line[$i]->DiscountInformation = $discountName;
						$order->Line[$i]->Discount = $order->Line[$i]->Total - $tempLineTotal;
					}
				}

				if(!empty($order->Line[$i]->Product->PriceOffer) && ($order->Line[$i]->Product->PriceOffer < ($order->Line[$i]->Price - $order->Line[$i]->Discount))) {
					$order->Line[$i]->DiscountInformation = 'Special offer';
					$order->Line[$i]->Discount = $order->Line[$i]->Total - ($order->Line[$i]->Product->PriceOffer * $order->Line[$i]->Quantity);
				}

				$order->Line[$i]->Tax = $taxCalculator->GetTax(($order->Line[$i]->Total-$order->Line[$i]->Discount), $order->Line[$i]->Product->TaxClass->ID);
				$order->Line[$i]->Tax = round($order->Line[$i]->Tax, 2);
				$order->Line[$i]->Update();

				$order->TotalDiscount += $order->Line[$i]->Discount;
			}

			$order->Update();
			$order->SendEmail();

			$note = new OrderNote();
			$note->IsAlert = 'Y';
			$note->IsPublic = 'N';
			$note->OrderID = $order->ID;
			$note->Message = '<strong>Google Checkout Order!</strong><br />';
			$note->Message .= 'Ref#: ' . $this->_Data[$this->_Root]['google-order-number']['VALUE'].'<br />';
			$note->Message .= 'Note: Please wait for Chargeable status before packing!<br />';
			$note->Subject = 0;
			$note->Add();
		}
		$orderCheck->Disconnect();

		header('HTTP/1.0 200 OK');
	}

	/*
	Method: onMerchantCalculationCallback()
	-----------------------------------------------------------------------------------------------
	Used in Google requests for cart calculations.
	*/
	function onMerchantCalculationCallback(){
		// gets the billing and shipping addresses
		$addresses = $this->getAddresses($this->_Data[$this->_Root]['calculate']['addresses']['anonymous-address']);

		$this->Cart = new Cart($this->Session);
		if(isset($addresses['billing']->Country->ID)) $this->Cart->BillingCountry->ID = $addresses['billing']->Country->ID;
		if(isset($addresses['billing']->Region->ID)) $this->Cart->BillingRegion->ID = $addresses['billing']->Region->ID;

		$calculation = new GoogleMerchantCalculation();

		foreach($addresses as $address){
			$this->Cart->ShippingCountry->ID = $address->Country->ID;
			$this->Cart->ShippingRegion->ID = $address->Region->ID;
			$this->Cart->Calculate();

			$calculation->AddShipping($this->Cart->ShippingCalculator->AvailablePostage, $address->IntegrationID);
		}

		$result = $calculation->GetResults();

		if($this->Logging) {
			$this->Log->Add('DEBUG', $result);
		}

		$this->RespondOK($result);
	}

	function RespondOK($result){
		header('HTTP/1.0 200 OK');
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo "\n";
		echo $result;
	}

	function getAddresses($addressNode){
		$addresses = array();
		//$addresses['billing'] = new Address();
		$results = $this->getArrayResult($addressNode);
		/*
		Results:
		[0]	Billing Address
		[1] Shipping Address
		*/
		/*$addresses['billing']->Country->ISOCode2 = $results[0]['country-code']['VALUE'];
		$addresses['billing']->City = addslashes($results[0]['city']['VALUE']);
		$addresses['billing']->Region->Name = addslashes($results[0]['region']['VALUE']);
		$addresses['billing']->Zip = addslashes($results[0]['postal-code']['VALUE']);
		$addresses['billing']->IntegrationID = $results[0]['id'];
		$addresses['billing']->Country->GetIDFromIsoCode2();
		$addresses['billing']->Region->GetIDFromString();

		if(count($results) > 1){
			$addresses['shipping'] = new Address();
			$addresses['shipping']->Country->ISOCode2 = $results[1]['country-code']['VALUE'];
			$addresses['shipping']->City = addslashes($results[1]['city']['VALUE']);
			$addresses['shipping']->Region->Name = addslashes($results[1]['region']['VALUE']);
			$addresses['shipping']->Zip = addslashes($results[1]['postal-code']['VALUE']);
			$addresses['shipping']->IntegrationID = $results[1]['id'];
			$addresses['shipping']->Country->GetIDFromIsoCode2();
			$addresses['shipping']->Region->GetIDFromString();
		}*/
		
		foreach($results as $result) {
			$address = new Address();
			$address->Country->ISOCode2 = $result['country-code']['VALUE'];
			$address->City = addslashes($result['city']['VALUE']);
			$address->Region->Name = addslashes($result['region']['VALUE']);
			$address->Zip = addslashes($result['postal-code']['VALUE']);
			$address->IntegrationID = $result['id'];
			$address->Country->GetIDFromIsoCode2();
			$address->Region->GetIDFromString();
			
			$addresses[] = $address;
		}
		
		return $addresses;
	}

	function getArrayResult($child_node){
		$result = array();
		if(isset($child_node)) {
			if($this->isAssociativeArray($child_node)) {
				$result[] = $child_node;
			} else {
				foreach($child_node as $curr_node){
					$result[] = $curr_node;
				}
			}
		}

		return $result;
	}

	function isAssociativeArray($var){
		return is_array($var) && !is_numeric(implode('', array_keys($var)));
	}

	function StartSession(){
		if(isset($this->_Data[$this->_Root]['shopping-cart']['merchant-private-data']['session-data']['VALUE'])){
			$sessionId = $this->_Data[$this->_Root]['shopping-cart']['merchant-private-data']['session-data']['VALUE'];
			session_id($sessionId);
		}

		$this->Session = new CustomerSession();
		$this->Session->Start();

		return true;
	}
}