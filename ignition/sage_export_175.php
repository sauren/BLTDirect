<?php
require_once('lib/common/app_header.php');

if($action == 'locked') {
	$session->Secure(2);
	locked();
	exit;
} elseif($action == 'complete') {
	$session->Secure(2);
	complete();
	exit;
} else {
	$session->Secure(3);
	integrate();
	exit;
}

function locked() {
	$page = new Page(sprintf('<a href="%s">Sage Export</a> &gt; Integration Locked', $_SERVER['PHP_SELF']), 'Sage integration locked.');
	$page->Display('header');

	echo '<p>There are outstanding Sage export related data feeds awaiting execution.<br />Allowing more than one consecutive integration session may cause duplicate data to appear within Sage and compromise the referential integrity of any unconfirmed integration associations.</p>';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function complete() {
	$page = new Page(sprintf('<a href="%s">Sage Export</a> &gt; Integration Complete', $_SERVER['PHP_SELF']), 'Sage integration complete.');
	$page->AddToHead(sprintf('<script language="javascript" type="text/javascript">%s</script>', isset($_REQUEST['documents']) ? sprintf('popUrl(\'sage_export_print.php?documents=%s\', 800, 600);', $_REQUEST['documents']) : ''));
	$page->Display('header');

	echo '<p>Sage integration was completed succesfully.<br />Please allow for the next integration iteration before data is available within Sage.</p>';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function integrate() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactAccountIndex.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CreditNote.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Country.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IntegrationSage.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IntegrationSageLog.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Invoice.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateCompany.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateCustomerContainer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateInvoiceContainer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateItemContainer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateProductContainer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TaxCalculator.php');

	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM integration_sage WHERE (Type LIKE 'Export' OR Type LIKE 'Confirmation') AND Is_Synchronised='N'"));
	if($data->Row['Count'] > 0) {
		redirect(sprintf("Location: %s?action=locked", $SERVER['PHP_SELF']));
	}
	$data->Disconnect();

	$methods = array();

    $data = new DataQuery(sprintf("SELECT * FROM payment_method WHERE Reference NOT IN ('google', 'paypal', 'foc') ORDER BY Method ASC"));
	while($data->Row) {
		$methods[] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'step1', 'alpha_numeric', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('method', 'Payment Method', 'radio', '0', 'numeric_unsigned', 1, 11, false);

	for($i=0; $i<count($methods); $i++) {
		$form->AddOption('method', $methods[$i]['Payment_Method_ID'], $methods[$i]['Method']);
	}

	$customers = array();

	if($form->GetValue('method') > 0) {
		$data = new DataQuery(sprintf("SELECT IF(c2.Contact_ID IS NOT NULL, c2.Contact_ID, c.Contact_ID) AS Contact_ID, IF(c2.Contact_ID IS NOT NULL, c2.Integration_Reference, c.Integration_Reference) AS Integration_Reference, IF(c2.Contact_ID IS NOT NULL, c2.Is_Integration_Locked, c.Is_Integration_Locked) AS Is_Integration_Locked, IF(c2.Contact_ID IS NOT NULL, c2.Nominal_Code, c.Nominal_Code) AS Nominal_Code, TRIM(IF(o.Org_Name IS NOT NULL, o.Org_Name, CONCAT_WS(' ', p.Name_First, p.Name_Last))) AS Contact_Name, cu.Customer_ID, cu.Is_Credit_Active, cu.Credit_Limit FROM contact AS c INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN customer AS cu ON cu.Contact_ID=c.Contact_ID INNER JOIN invoice AS i ON i.Customer_ID=cu.Customer_ID INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=i.Payment_Method_ID AND pm.Payment_Method_ID=%d WHERE i.Invoice_Total>0 AND i.Created_On>='%s' AND i.Tax_Rate<>20.0 AND i.Integration_ID='' AND i.Invoice_Tax>0 AND i.Invoice_Country LIKE 'United Kingdom' GROUP BY c.Contact_ID ORDER BY Contact_Name ASC", mysql_real_escape_string($form->GetValue('method')), mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE'])));
		while($data->Row) {
			$customers[$data->Row['Contact_ID']] = $data->Row;

			$data->Next();
		}
		$data->Disconnect();

        $data = new DataQuery(sprintf("SELECT IF(c2.Contact_ID IS NOT NULL, c2.Contact_ID, c.Contact_ID) AS Contact_ID, IF(c2.Contact_ID IS NOT NULL, c2.Integration_Reference, c.Integration_Reference) AS Integration_Reference, IF(c2.Contact_ID IS NOT NULL, c2.Is_Integration_Locked, c.Is_Integration_Locked) AS Is_Integration_Locked, IF(c2.Contact_ID IS NOT NULL, c2.Nominal_Code, c.Nominal_Code) AS Nominal_Code, TRIM(IF(o.Org_Name IS NOT NULL, o.Org_Name, CONCAT_WS(' ', p.Name_First, p.Name_Last))) AS Contact_Name, cu.Customer_ID, cu.Is_Credit_Active, cu.Credit_Limit FROM contact AS c INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN customer AS cu ON cu.Contact_ID=c.Contact_ID INNER JOIN orders AS o2 ON o2.Customer_ID=cu.Customer_ID INNER JOIN credit_note AS cn ON cn.Order_ID=o2.Order_ID INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o2.Payment_Method_ID AND pm.Payment_Method_ID=%d WHERE cn.Total>0 AND cn.Credited_On>='%s' AND cn.Tax_Rate<>20.0 AND cn.Integration_ID='' AND cn.TotalTax>0 AND o2.Billing_Country_ID=222 GROUP BY c.Contact_ID ORDER BY Contact_Name ASC", mysql_real_escape_string($form->GetValue('method')), mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE'])));
		while($data->Row) {
			$customers[$data->Row['Contact_ID']] = $data->Row;

			$data->Next();
		}
		$data->Disconnect();

		foreach($customers as $customerId=>$customer) {
			$customers[sprintf('customer-%s-%s', strtolower(str_replace(' ', '', $customer['Contact_Name'])), $customerId)] = $customer;

			unset($customers[$customerId]);
		}

		ksort($customers);
	}

	$sageTemplate = new SageTemplateCompany();

	$defaultNominalCode = $GLOBALS['SAGE_DEFAULT_NOMINAL_CODE'];

	foreach($customers as $customer) {
		$template = new SageTemplateCustomerContainer();
		$template->customer->id = $customer['Contact_ID'];
		$template->customer->companyName = $customer['Contact_Name'];
		$template->customer->accountReference = $customer['Integration_Reference'];
		$template->customer->creditLimit = $customer['Credit_Limit'];
		$template->customer->terms = (($customer['Credit_Limit'] > 0) && ($customer['Is_Credit_Active'] == 'Y')) ? 'true' : 'false';
		$template->customer->nominalCode = !empty($customer['Nominal_Code']) ? $customer['Nominal_Code'] : $defaultNominalCode;
		$template->customer->setParameter('locked', $customer['Is_Integration_Locked']);
		$template->customer->setParameter('customer_id', $customer['Customer_ID']);
		$template->customer->setParameter('credit_active', $customer['Is_Credit_Active']);

		$sageTemplate->addCustomer($template);
	}

	for($i=0; $i<count($sageTemplate->customers); $i++) {
		$form->AddField(sprintf('customer_new_%d', $sageTemplate->customers[$i]->customer->id), sprintf('New Account for \'%s\'', $sageTemplate->customers[$i]->customer->companyName), 'checkbox', 'N', 'boolean', 1, 1, false);
		$form->AddField(sprintf('customer_account_%d', $sageTemplate->customers[$i]->customer->id), sprintf('Account Reference for \'%s\'', $sageTemplate->customers[$i]->customer->companyName), 'text', $sageTemplate->customers[$i]->customer->accountReference, 'anything', 1, 8, false, 'style="font-family: arial, sans-serif;"');
		$form->AddField(sprintf('customer_nominal_%d', $sageTemplate->customers[$i]->customer->id), sprintf('Nominal Code for \'%s\'', $sageTemplate->customers[$i]->customer->companyName), 'select', $sageTemplate->customers[$i]->customer->nominalCode, 'numeric_unsigned', 1, 64, false, 'style="font-family: arial, sans-serif;"');
		$form->AddOption(sprintf('customer_nominal_%d', $sageTemplate->customers[$i]->customer->id), '4100', '4100: Sales');
		$form->AddOption(sprintf('customer_nominal_%d', $sageTemplate->customers[$i]->customer->id), '4103', '4103: Sales - EC VAT Free');
		$form->AddOption(sprintf('customer_nominal_%d', $sageTemplate->customers[$i]->customer->id), '4104', '4104: Sales - WW VAT Free');
		$form->AddOption(sprintf('customer_nominal_%d', $sageTemplate->customers[$i]->customer->id), '4105', '4105: Sales - UK VAT Free');
		$form->AddOption(sprintf('customer_nominal_%d', $sageTemplate->customers[$i]->customer->id), '4106', '4106: Sales - UK Credit Account');
		$form->AddOption(sprintf('customer_nominal_%d', $sageTemplate->customers[$i]->customer->id), '4109', '4109: Sales - EC Credit Account');
		$form->AddOption(sprintf('customer_nominal_%d', $sageTemplate->customers[$i]->customer->id), '4110', '4110: Sales - WW Credit Account');
		$form->AddOption(sprintf('customer_nominal_%d', $sageTemplate->customers[$i]->customer->id), '4111', '4111: Sales - UK Credit Account VAT Free');
		$form->AddOption(sprintf('customer_nominal_%d', $sageTemplate->customers[$i]->customer->id), '4112', '4112: Sales - EC Credit Account VAT Free');
		$form->AddField(sprintf('customer_locked_%d', $sageTemplate->customers[$i]->customer->id), sprintf('Locked for \'%s\'', $sageTemplate->customers[$i]->customer->companyName), 'checkbox', $sageTemplate->customers[$i]->customer->getParameter('locked'), 'boolean', 1, 1, false);
		$form->AddField(sprintf('customer_credit_active_%d', $sageTemplate->customers[$i]->customer->id), sprintf('Credit Active for \'%s\'', $sageTemplate->customers[$i]->customer->companyName), 'checkbox', $sageTemplate->customers[$i]->customer->getParameter('credit_active'), 'boolean', 1, 1, false);
		$form->AddField(sprintf('customer_credit_limit_%d', $sageTemplate->customers[$i]->customer->id), sprintf('Credit Limit for \'%s\'', $sageTemplate->customers[$i]->customer->companyName), 'text', $sageTemplate->customers[$i]->customer->creditLimit, 'float', 1, 11, false);

		if(isset($_REQUEST['filter'])) {
			$form->SetValue(sprintf('customer_locked_%d', $sageTemplate->customers[$i]->customer->id), $sageTemplate->customers[$i]->customer->getParameter('locked'));
			$form->SetValue(sprintf('customer_credit_active_%d', $sageTemplate->customers[$i]->customer->id), $sageTemplate->customers[$i]->customer->getParameter('credit_active'));
		}
	}

	$totals = array('Invoices' => array(), 'Credits' => array());
	$invoices = array();

	if($form->GetValue('method') > 0) {
		$data = new DataQuery(sprintf("SELECT IF(c2.Contact_ID IS NOT NULL, c2.Contact_ID, c.Contact_ID) AS Contact_ID, i.Invoice_ID, i.Invoice_Shipping, i.Invoice_Total, ROUND((Invoice_Tax/(Invoice_Net+Invoice_Shipping-Invoice_Discount)) * 100, 1) AS Tax_Rate, i.Invoice_Title, i.Invoice_First_Name, i.Invoice_Last_Name, i.Invoice_Organisation, i.Invoice_Address_1, i.Invoice_Address_2, i.Invoice_Address_3, i.Invoice_City, ic.Country_ID AS Invoice_Country_ID, ir.Region_ID AS Invoice_Region_ID, i.Invoice_Zip, i.Created_On, ic.ISO_Code_2 AS Invoice_Country_Code, ir.Region_Name AS Invoice_Region, o.Order_ID, o.Custom_Order_No AS Custom_Reference, o.Shipping_Title, o.Shipping_First_Name, o.Shipping_Last_Name, o.Shipping_Organisation_Name, o.Shipping_Address_1, o.Shipping_Address_2, o.Shipping_Address_3, o.Shipping_City, o.Shipping_Zip, irs.Region_Name AS Shipping_Region, ics.ISO_Code_2 AS Shipping_Country_Code, o.TaxExemptCode AS Tax_Exemption_Code, pm.Method AS Payment_Method FROM invoice AS i INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=i.Payment_Method_ID AND pm.Payment_Method_ID=%d LEFT JOIN countries AS ic ON ic.Country LIKE i.Invoice_Country LEFT JOIN regions AS ir ON ir.Region_Name LIKE i.Invoice_Region AND ir.Country_ID=ic.Country_ID INNER JOIN customer AS cu ON cu.Customer_ID=i.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID INNER JOIN orders AS o ON o.Order_ID=i.Order_ID LEFT JOIN countries AS ics ON ics.Country_ID=o.Shipping_Country_ID LEFT JOIN regions AS irs ON irs.Region_ID=o.Shipping_Region_ID AND irs.Country_ID=ics.Country_ID WHERE i.Invoice_Total>0 AND i.Created_On>='%s' AND i.Tax_Rate<>20.0 AND i.Integration_ID='' AND i.Invoice_Tax>0 AND i.Invoice_Country LIKE 'United Kingdom' GROUP BY i.Invoice_ID", mysql_real_escape_string($form->GetValue('method')), mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE'])));
		while($data->Row) {
			for($i=0; $i<count($sageTemplate->customers); $i++) {
				if($sageTemplate->customers[$i]->customer->id == $data->Row['Contact_ID']) {
					$template = new SageTemplateInvoiceContainer();
					$template->invoice->id = sprintf('orderinvoice-%s', $data->Row['Invoice_ID']);
					$template->invoice->customerId = $data->Row['Contact_ID'];
					$template->invoice->invoiceDate = date('c', strtotime($data->Row['Created_On']));
					$template->invoice->invoiceAddress->title = empty($data->Row['Invoice_Organisation']) ? $data->Row['Invoice_Title'] : '';
					$template->invoice->invoiceAddress->forename = empty($data->Row['Invoice_Organisation']) ? $data->Row['Invoice_First_Name'] : '';
					$template->invoice->invoiceAddress->surname = empty($data->Row['Invoice_Organisation']) ? $data->Row['Invoice_Last_Name'] : '';
					$template->invoice->invoiceAddress->company = $data->Row['Invoice_Organisation'];
					$template->invoice->carriage->quantity = 1;
					$template->invoice->carriage->price = $data->Row['Invoice_Shipping'];
					$template->invoice->carriage->taxRate = $data->Row['Tax_Rate'];
					$template->invoice->carriage->totalNet = $template->invoice->carriage->price * $template->invoice->carriage->quantity;
					$template->invoice->carriage->totalTax = $template->invoice->carriage->totalNet * ($data->Row['Tax_Rate'] / 100);
					$template->invoice->setParameter('entityNumber', sprintf('Invoice %s', $data->Row['Invoice_ID']));
					$template->invoice->setParameter('parentNumber', sprintf('Order %s', $data->Row['Order_ID']));
					$template->invoice->setParameter('parentId', $data->Row['Order_ID']);
					$template->invoice->setParameter('paymentMethod', $data->Row['Payment_Method']);
					$template->invoice->setParameter('invoiceId', $data->Row['Invoice_ID']);
					$template->invoice->setParameter('taxRate', $data->Row['Tax_Rate']);

                    $data2 = new DataQuery(sprintf("SELECT il.Quantity, (il.Line_Total / il.Quantity) AS Price, (il.Line_Discount / il.Quantity) AS Discount_Amount, (((il.Line_Discount / il.Quantity) / il.Price)*100) AS Discount_Percentage, il.Line_Total, il.Line_Discount, il.Line_Tax FROM invoice_line AS il WHERE il.Invoice_ID=%d", $data->Row['Invoice_ID']));
					while($data2->Row) {
						$item = new SageTemplateItemContainer();
						$item->item->quantity = $data2->Row['Quantity'];
						$item->item->price = $data2->Row['Price'];
						$item->item->discountAmount = $data2->Row['Discount_Amount'];
						$item->item->discountPercentage = $data2->Row['Discount_Percentage'];
						$item->item->taxRate = $data->Row['Tax_Rate'];
						$item->item->totalNet = $data2->Row['Line_Total'] - $data2->Row['Line_Discount'];
						$item->item->totalTax = $data2->Row['Line_Tax'];

						$template->invoice->addItem($item);

						$data2->Next();
					}
					$data2->Disconnect();

					$totals['Invoices'][$data->Row['Invoice_ID']] = $data->Row['Invoice_Total'];

					$invoices[] = $template;

					$billingContact = array();

					if(!empty($template->invoice->invoiceAddress->title)) {
						$billingContact[] = $template->invoice->invoiceAddress->title;
					}

					if(!empty($template->invoice->invoiceAddress->forename)) {
						$billingContact[] = $template->invoice->invoiceAddress->forename;
					}

					if(!empty($template->invoice->invoiceAddress->surname)) {
						$billingContact[] = $template->invoice->invoiceAddress->surname;
					}

					$form->AddField(sprintf('invoice_submit_%s', $template->invoice->id), sprintf('Submit Entity for \'%s\'', implode(' ', $billingContact)), 'checkbox', 'Y', 'boolean', 1, 1, false);

					if(!isset($_REQUEST['continue'])) {
						$form->SetValue(sprintf('invoice_submit_%s', $template->invoice->id), 'Y');
					}

					break;
				}
			}

			$data->Next();
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT IF(c2.Contact_ID IS NOT NULL, c2.Contact_ID, c.Contact_ID) AS Contact_ID, cn.Credit_Note_ID, cn.TotalShipping, cn.Total, cn.Tax_Rate, o.Billing_Title, o.Billing_First_Name, o.Billing_Last_Name, o.Billing_Organisation_Name, o.Billing_Address_1, o.Billing_Address_2, o.Billing_Address_3, o.Billing_City, o.Billing_Country_ID, o.Billing_Region_ID, o.Billing_Zip, cn.Credited_On, oc.ISO_Code_2 AS Billing_Country_Code, orr.Region_Name AS Billing_Region, o.Order_ID, o.Custom_Order_No AS Custom_Reference, o.Shipping_Title, o.Shipping_First_Name, o.Shipping_Last_Name, o.Shipping_Organisation_Name, o.Shipping_Address_1, o.Shipping_Address_2, o.Shipping_Address_3, o.Shipping_City, o.Shipping_Zip, ors.Region_Name AS Shipping_Region, ocs.ISO_Code_2 AS Shipping_Country_Code, o.TaxExemptCode AS Tax_Exemption_Code, pm.Method AS Payment_Method FROM credit_note AS cn INNER JOIN orders AS o ON o.Order_ID=cn.Order_ID INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Payment_Method_ID=%d LEFT JOIN countries AS oc ON oc.Country_ID=o.Billing_Country_ID LEFT JOIN regions AS orr ON orr.Region_ID=o.Billing_Region_ID LEFT JOIN countries AS ocs ON ocs.Country_ID=o.Shipping_Country_ID LEFT JOIN regions AS ors ON ors.Region_ID=o.Shipping_Region_ID INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID WHERE cn.Total>0 AND cn.Credited_On>='%s' AND cn.Tax_Rate<>20.0 AND cn.Integration_ID='' AND cn.TotalTax>0 AND o.Billing_Country_ID=222 GROUP BY cn.Credit_Note_ID", mysql_real_escape_string($form->GetValue('method')), mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE'])));
		while($data->Row) {
        	for($i=0; $i<count($sageTemplate->customers); $i++) {
				if($sageTemplate->customers[$i]->customer->id == $data->Row['Contact_ID']) {
					$template = new SageTemplateInvoiceContainer();
					$template->invoice->id = sprintf('ordercredit-%s', $data->Row['Credit_Note_ID']);
					$template->invoice->customerId = $data->Row['Contact_ID'];
					$template->invoice->invoiceDate = date('c', strtotime($data->Row['Credited_On']));
                    $template->invoice->invoiceAddress->title = empty($data->Row['Billing_Organisation_Name']) ? $data->Row['Billing_Title'] : '';
					$template->invoice->invoiceAddress->forename = empty($data->Row['Billing_Organisation_Name']) ? $data->Row['Billing_First_Name'] : '';
					$template->invoice->invoiceAddress->surname = empty($data->Row['Billing_Organisation_Name']) ? $data->Row['Billing_Last_Name'] : '';
					$template->invoice->invoiceAddress->company = $data->Row['Billing_Organisation_Name'];
					$template->invoice->carriage->quantity = 1;
					$template->invoice->carriage->price = $data->Row['TotalShipping'];
					$template->invoice->carriage->taxRate = $data->Row['Tax_Rate'];
					$template->invoice->carriage->totalNet = $template->invoice->carriage->price * $template->invoice->carriage->quantity;
					$template->invoice->carriage->totalTax = $template->invoice->carriage->totalNet * ($data->Row['Tax_Rate'] / 100);
					$template->invoice->setParameter('entityNumber', sprintf('Credit %s', $data->Row['Credit_Note_ID']));
					$template->invoice->setParameter('parentNumber', sprintf('Order %s', $data->Row['Order_ID']));
					$template->invoice->setParameter('parentId', $data->Row['Order_ID']);
					$template->invoice->setParameter('paymentMethod', $data->Row['Payment_Method']);
					$template->invoice->setParameter('creditId', $data->Row['Credit_Note_ID']);
					$template->invoice->setParameter('taxRate', $data->Row['Tax_Rate']);

                    $data2 = new DataQuery(sprintf("SELECT cnl.Quantity, cnl.Price, cnl.TotalNet, cnl.TotalTax FROM credit_note_line AS cnl WHERE cnl.Credit_Note_ID=%d", $data->Row['Credit_Note_ID']));
					while($data2->Row) {
						$item = new SageTemplateItemContainer();
						$item->item->quantity = $data2->Row['Quantity'];
						$item->item->price = $data2->Row['Price'];
						$item->item->taxRate = $data->Row['Tax_Rate'];
						$item->item->totalNet = $data2->Row['TotalNet'];
						$item->item->totalTax = $data2->Row['TotalTax'];

						$template->invoice->addItem($item);

						$data2->Next();
					}
					$data2->Disconnect();

					$totals['Credits'][$data->Row['Credit_Note_ID']] = $data->Row['Total'];

					$invoices[] = $template;

					$billingContact = array();

					if(!empty($template->invoice->invoiceAddress->title)) {
						$billingContact[] = $template->invoice->invoiceAddress->title;
					}

					if(!empty($template->invoice->invoiceAddress->forename)) {
						$billingContact[] = $template->invoice->invoiceAddress->forename;
					}

					if(!empty($template->invoice->invoiceAddress->surname)) {
						$billingContact[] = $template->invoice->invoiceAddress->surname;
					}

					$form->AddField(sprintf('invoice_submit_%s', $template->invoice->id), sprintf('Submit Entity for \'%s\'', implode(' ', $billingContact)), 'checkbox', 'Y', 'boolean', 1, 1, false);

                    if(!isset($_REQUEST['continue'])) {
						$form->SetValue(sprintf('invoice_submit_%s', $template->invoice->id), 'Y');
					}

					break;
				}
			}

			$data->Next();
		}
		$data->Disconnect();
	}

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if(isset($_REQUEST['continue'])) {
			if($form->Validate()) {
				$print = array();

				for($i=0; $i<count($sageTemplate->customers); $i++) {
					$sageTemplate->customers[$i]->customer->accountReference = strtoupper($form->GetValue(sprintf('customer_account_%d', $sageTemplate->customers[$i]->customer->id)));
					$sageTemplate->customers[$i]->customer->creditLimit = $form->GetValue(sprintf('customer_credit_limit_%d', $sageTemplate->customers[$i]->customer->id));
					$sageTemplate->customers[$i]->customer->nominalCode = $form->GetValue(sprintf('customer_nominal_%d', $sageTemplate->customers[$i]->customer->id));
					$sageTemplate->customers[$i]->customer->setParameter('locked', $form->GetValue(sprintf('customer_locked_%d', $sageTemplate->customers[$i]->customer->id)));
					$sageTemplate->customers[$i]->customer->setParameter('credit_active', $form->GetValue(sprintf('customer_credit_active_%d', $sageTemplate->customers[$i]->customer->id)));

					$contact = new Contact();
					$contact->Get($sageTemplate->customers[$i]->customer->id);
					$contact->IntegrationReference = $sageTemplate->customers[$i]->customer->accountReference;
					$contact->IsIntegrationLocked = $sageTemplate->customers[$i]->customer->getParameter('locked');
					$contact->NominalCode = $sageTemplate->customers[$i]->customer->nominalCode;
					$contact->Update();

					$customer = new Customer();
					$customer->Get($sageTemplate->customers[$i]->customer->getParameter('customer_id'));
					$customer->IsCreditActive = $sageTemplate->customers[$i]->customer->getParameter('credit_active');
					$customer->CreditLimit = $sageTemplate->customers[$i]->customer->creditLimit;
					$customer->Update();

					switch(strtoupper($contact->Type)) {
						case 'I':
							$sageTemplate->customers[$i]->customer->invoiceAddress->title = $contact->Person->Title;
							$sageTemplate->customers[$i]->customer->invoiceAddress->forename = $contact->Person->Name;
							$sageTemplate->customers[$i]->customer->invoiceAddress->surname = $contact->Person->LastName;
							$sageTemplate->customers[$i]->customer->invoiceAddress->address1 = $contact->Person->Address->Line1;
							$sageTemplate->customers[$i]->customer->invoiceAddress->address2 = $contact->Person->Address->Line2;
							$sageTemplate->customers[$i]->customer->invoiceAddress->address3 = $contact->Person->Address->Line3;
							$sageTemplate->customers[$i]->customer->invoiceAddress->town = $contact->Person->Address->City;
							$sageTemplate->customers[$i]->customer->invoiceAddress->postcode = $contact->Person->Address->Zip;
							$sageTemplate->customers[$i]->customer->invoiceAddress->county = $contact->Person->Address->Region->Name;
							$sageTemplate->customers[$i]->customer->invoiceAddress->country = $contact->Person->Address->Country->ISOCode2;
							$sageTemplate->customers[$i]->customer->invoiceAddress->telephone = $contact->Person->Phone1;
							$sageTemplate->customers[$i]->customer->invoiceAddress->fax = $contact->Person->Fax;
							$sageTemplate->customers[$i]->customer->invoiceAddress->mobile = $contact->Person->Mobile;
							$sageTemplate->customers[$i]->customer->invoiceAddress->email = $contact->Person->Email;
							break;
						case 'O':
							$sageTemplate->customers[$i]->customer->invoiceAddress->company = $contact->Organisation->Name;
							$sageTemplate->customers[$i]->customer->invoiceAddress->address1 = $contact->Organisation->Address->Line1;
							$sageTemplate->customers[$i]->customer->invoiceAddress->address2 = $contact->Organisation->Address->Line2;
							$sageTemplate->customers[$i]->customer->invoiceAddress->address3 = $contact->Organisation->Address->Line3;
							$sageTemplate->customers[$i]->customer->invoiceAddress->town = $contact->Organisation->Address->City;
							$sageTemplate->customers[$i]->customer->invoiceAddress->postcode = $contact->Organisation->Address->Zip;
							$sageTemplate->customers[$i]->customer->invoiceAddress->county = $contact->Organisation->Address->Region->Name;
							$sageTemplate->customers[$i]->customer->invoiceAddress->country = $contact->Organisation->Address->Country->ISOCode2;
							$sageTemplate->customers[$i]->customer->invoiceAddress->telephone = $contact->Organisation->Phone1;
							$sageTemplate->customers[$i]->customer->invoiceAddress->fax = $contact->Organisation->Fax;
							$sageTemplate->customers[$i]->customer->invoiceAddress->email = $contact->Organisation->Email;
							break;
					}
				}

				for($i=count($sageTemplate->customers)-1; $i>=0; $i--) {
					if(empty($sageTemplate->customers[$i]->customer->accountReference)) {
						if($form->GetValue(sprintf('customer_new_%d', $sageTemplate->customers[$i]->customer->id)) == 'Y') {
							$reference = trim(str_replace(' ', '', strtoupper($sageTemplate->customers[$i]->customer->companyName)));
							$reference = preg_replace('/[^A-Za-z0-9]/', '', $reference);
							$reference = (strlen($reference) >= 4) ? substr($reference, 0, 4) : $reference;
							$reference = (strlen($reference) == 1) ? sprintf('%s0', $reference) : $reference;
							$reference = (strlen($reference) == 0) ? '00' : $reference;

							$index = new ContactAccountIndex();
							$index->IncreaseIndex($reference);

							$number = $index->NextIndexNumber;

							while(strlen($number) < 3) {
								$number = sprintf('0%s', $number);
							}

                            $contact = new Contact();
							$contact->Get($sageTemplate->customers[$i]->customer->id);
							$contact->IntegrationReference = strtoupper(sprintf('%s%s%s', $reference, $number, $GLOBALS['SAGE_DEPARTMENT_KEY']));
							$contact->Update();

							$sageTemplate->customers[$i]->customer->accountReference = $contact->IntegrationReference;
						}
					}

					if(empty($sageTemplate->customers[$i]->customer->accountReference)) {
						array_splice($sageTemplate->customers, $i, 1);
					}
				}

				if($form->GetValue('method') > 0) {
					$defaultTaxCode = 6;

					$data = new DataQuery(sprintf("SELECT IF(c2.Contact_ID IS NOT NULL, c2.Contact_ID, c.Contact_ID) AS Contact_ID, i.Invoice_ID, i.Invoice_Shipping, i.Invoice_Total, ROUND((Invoice_Tax/(Invoice_Net+Invoice_Shipping-Invoice_Discount)) * 100, 1) AS Tax_Rate, i.Is_Paid, i.Invoice_Total, i.Invoice_Title, i.Invoice_First_Name, i.Invoice_Last_Name, i.Invoice_Organisation, i.Invoice_Address_1, i.Invoice_Address_2, i.Invoice_Address_3, i.Invoice_City, ic.Country_ID AS Invoice_Country_ID, ir.Region_ID AS Invoice_Region_ID, i.Invoice_Zip, i.Created_On, ic.ISO_Code_2 AS Invoice_Country_Code, ir.Region_Name AS Invoice_Region, o.Order_ID, o.Custom_Order_No AS Custom_Reference, o.Shipping_Title, o.Shipping_First_Name, o.Shipping_Last_Name, o.Shipping_Organisation_Name, o.Shipping_Address_1, o.Shipping_Address_2, o.Shipping_Address_3, o.Shipping_City, o.Shipping_Zip, irs.Region_Name AS Shipping_Region, ics.ISO_Code_2 AS Shipping_Country_Code, o.TaxExemptCode AS Tax_Exemption_Code, o.Nominal_Code, pm.Reference FROM invoice AS i INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=i.Payment_Method_ID AND pm.Payment_Method_ID=%d LEFT JOIN countries AS ic ON ic.Country LIKE i.Invoice_Country LEFT JOIN regions AS ir ON ir.Region_Name LIKE i.Invoice_Region AND ir.Country_ID=ic.Country_ID INNER JOIN customer AS cu ON cu.Customer_ID=i.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID INNER JOIN orders AS o ON o.Order_ID=i.Order_ID LEFT JOIN countries AS ics ON ics.Country_ID=o.Shipping_Country_ID LEFT JOIN regions AS irs ON irs.Region_ID=o.Shipping_Region_ID AND irs.Country_ID=ics.Country_ID WHERE i.Invoice_Total>0 AND i.Created_On>='%s' AND i.Tax_Rate<>20.0 AND i.Integration_ID='' AND i.Invoice_Tax>0 AND i.Invoice_Country LIKE 'United Kingdom' GROUP BY i.Invoice_ID", mysql_real_escape_string($form->GetValue('method')), mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE'])));
					while($data->Row) {
						for($i=0; $i<count($sageTemplate->customers); $i++) {
							if($sageTemplate->customers[$i]->customer->id == $data->Row['Contact_ID']) {
								$taxRate = number_format($data->Row['Tax_Rate'], 2, '.', '');
								$taxCode = $defaultTaxCode;

								$data2 = new DataQuery(sprintf("SELECT Integration_Reference FROM tax_code WHERE Rate=%f LIMIT 0, 1", mysql_real_escape_string($taxRate)));
								if($data2->TotalRows > 0) {
									$taxCode = $data2->Row['Integration_Reference'];
								}
								$data2->Disconnect();

								if(!empty($data->Row['Tax_Exemption_Code'])) {
									$taxCode = $GLOBALS['SAGE_TAX_EXEMPT_CODE'];

									$country = new Country();

									if($country->Get($data->Row['Billing_Country_ID'])) {
										if($country->ExemptTaxCode->Get()) {
											$taxCode = $country->ExemptTaxCode->IntegrationReference;
										}
									}
								}

								$template = new SageTemplateInvoiceContainer();
								$template->invoice->id = sprintf('orderinvoice-%s', $data->Row['Invoice_ID']);
								$template->invoice->customerId = $data->Row['Contact_ID'];
								$template->invoice->customerOrderNumber = $data->Row['Custom_Reference'];
								$template->invoice->accountReference = $sageTemplate->customers[$i]->customer->accountReference;
								$template->invoice->orderNumber = $data->Row['Order_ID'];
								$template->invoice->currency = 'GBP';
								$template->invoice->notes1 = $data->Row['Invoice_ID'];
								$template->invoice->invoiceDate = date('c', strtotime($data->Row['Created_On']));
                                $template->invoice->invoiceAddress->title = empty($data->Row['Invoice_Organisation']) ? $data->Row['Invoice_Title'] : '';
								$template->invoice->invoiceAddress->forename = empty($data->Row['Invoice_Organisation']) ? $data->Row['Invoice_First_Name'] : '';
								$template->invoice->invoiceAddress->surname = empty($data->Row['Invoice_Organisation']) ? $data->Row['Invoice_Last_Name'] : '';
								$template->invoice->invoiceAddress->company = $data->Row['Invoice_Organisation'];
								$template->invoice->invoiceAddress->address1 = $data->Row['Invoice_Address_1'];
								$template->invoice->invoiceAddress->address2 = $data->Row['Invoice_Address_2'];
								$template->invoice->invoiceAddress->address3 = $data->Row['Invoice_Address_3'];
								$template->invoice->invoiceAddress->town = $data->Row['Invoice_City'];
								$template->invoice->invoiceAddress->postcode = $data->Row['Invoice_Zip'];
								$template->invoice->invoiceAddress->county = $data->Row['Invoice_Region'];
								$template->invoice->invoiceAddress->country = $data->Row['Invoice_Country_Code'];
                                $template->invoice->deliveryAddress->title = empty($data->Row['Shipping_Organisation_Name']) ? $data->Row['Shipping_Title'] : '';
								$template->invoice->deliveryAddress->forename = empty($data->Row['Shipping_Organisation_Name']) ? $data->Row['Shipping_First_Name'] : '';
								$template->invoice->deliveryAddress->surname = empty($data->Row['Shipping_Organisation_Name']) ? $data->Row['Shipping_Last_Name'] : '';
								$template->invoice->deliveryAddress->company = $data->Row['Shipping_Organisation_Name'];
								$template->invoice->deliveryAddress->address1 = $data->Row['Shipping_Address_1'];
								$template->invoice->deliveryAddress->address2 = $data->Row['Shipping_Address_2'];
								$template->invoice->deliveryAddress->address3 = $data->Row['Shipping_Address_3'];
								$template->invoice->deliveryAddress->town = $data->Row['Shipping_City'];
								$template->invoice->deliveryAddress->postcode = $data->Row['Shipping_Zip'];
								$template->invoice->deliveryAddress->county = $data->Row['Shipping_Region'];
								$template->invoice->deliveryAddress->country = $data->Row['Shipping_Country_Code'];
								$template->invoice->carriage->quantity = 1;
								$template->invoice->carriage->price = $data->Row['Invoice_Shipping'];
								$template->invoice->carriage->taxRate = $data->Row['Tax_Rate'];
								$template->invoice->carriage->totalNet = $template->invoice->carriage->price * $template->invoice->carriage->quantity;
								$template->invoice->carriage->totalTax = $template->invoice->carriage->totalNet * ($data->Row['Tax_Rate'] / 100);
								$template->invoice->carriage->nominalCode = $GLOBALS['SAGE_DEFAULT_NOMINAL_CODE_CARRIAGE'];
								$template->invoice->carriage->department = $GLOBALS['SAGE_DEPARTMENT_INDEX'];
								$template->invoice->carriage->type = 'Service';
								$template->invoice->carriage->taxCode = $taxCode;
								$template->invoice->type = 'ProductInvoice';
								$template->invoice->nominalCode = $data->Row['Nominal_Code'];
								$template->invoice->details = sprintf('INV No. %d', $data->Row['Invoice_ID']);
								$template->invoice->taxCode = $taxCode;
								$template->invoice->department = $GLOBALS['SAGE_DEPARTMENT_INDEX'];
								$template->invoice->paymentReference = $data->Row['Reference'];
								$template->invoice->paymentAmount = 0;
								$template->invoice->bankAccount = $GLOBALS['SAGE_BANKACCOUNT'];
								$template->invoice->paymentType = 'SalesInvoice';
								$template->invoice->postedDate = date('c');
								$template->invoice->setParameter('type', 'orderinvoice');
								$template->invoice->setParameter('invoice_id', $data->Row['Invoice_ID']);

								$data2 = new DataQuery(sprintf("SELECT il.Invoice_Line_ID, (il.Line_Total / il.Quantity) AS Price, (il.Line_Discount / il.Quantity) AS Discount_Amount, (((il.Line_Discount / il.Quantity) / il.Price)*100) AS Discount_Percentage, il.Line_Total, il.Line_Discount, il.Line_Tax, il.Product_ID, il.Description, p.SKU, p.Product_Description FROM invoice_line AS il LEFT JOIN product AS p ON p.Product_ID=il.Product_ID WHERE il.Invoice_ID=%d", $data->Row['Invoice_ID']));
								while($data2->Row) {
									$item = new SageTemplateItemContainer();
									$item->item->id = $data2->Row['Invoice_Line_ID'];
									$item->item->sku = !empty($data2->Row['SKU']) ? str_replace(' ', '', $data2->Row['SKU']) : $item->item->id;
									$item->item->name = htmlspecialchars_decode($data2->Row['Description']);
									$item->item->description = htmlspecialchars_decode(strip_tags($data2->Row['Product_Description']));
									$item->item->quantity = $data2->Row['Quantity'];
									$item->item->price = $data2->Row['Price'];
									$item->item->discountAmount = $data2->Row['Discount_Amount'];
									$item->item->discountPercentage = $data2->Row['Discount_Percentage'];
									$item->item->reference = $data2->Row['SKU'];
									$item->item->taxRate = $data->Row['Tax_Rate'];
                                    $item->item->totalNet = $data2->Row['Line_Total'] - $data2->Row['Line_Discount'];
									$item->item->totalTax = $data2->Row['Line_Tax'];
									$item->item->nominalCode = $data->Row['Nominal_Code'];
									$item->item->department = $GLOBALS['SAGE_DEPARTMENT_INDEX'];
									$item->item->type = 'Stock';
									$item->item->taxCode = $taxCode;
									$item->item->setParameter('productId', $data2->Row['Product_ID']);

									$template->invoice->addItem($item);

									$data2->Next();
								}
								$data2->Disconnect();

								$sageTemplate->addInvoice($template);

								$print[$template->invoice->id] = $data->Row['Invoice_ID'];

								break;
							}
						}

						$data->Next();
					}
					$data->Disconnect();

                    $data = new DataQuery(sprintf("SELECT IF(c2.Contact_ID IS NOT NULL, c2.Contact_ID, c.Contact_ID) AS Contact_ID, cn.Credit_Note_ID, cn.Credit_Type, cn.TotalNet, cn.TotalShipping, cn.TotalTax, cn.Total, cn.Tax_Rate, o.Billing_Title, o.Billing_First_Name, o.Billing_Last_Name, o.Billing_Organisation_Name, o.Billing_Address_1, o.Billing_Address_2, o.Billing_Address_3, o.Billing_City, o.Billing_Country_ID, o.Billing_Region_ID, o.Billing_Zip, cn.Credited_On, oc.ISO_Code_2 AS Billing_Country_Code, orr.Region_Name AS Billing_Region, o.Order_ID, o.Custom_Order_No AS Custom_Reference, o.Shipping_Title, o.Shipping_First_Name, o.Shipping_Last_Name, o.Shipping_Organisation_Name, o.Shipping_Address_1, o.Shipping_Address_2, o.Shipping_Address_3, o.Shipping_City, o.Shipping_Zip, ors.Region_Name AS Shipping_Region, ocs.ISO_Code_2 AS Shipping_Country_Code, o.TaxExemptCode AS Tax_Exemption_Code, o.Nominal_Code, pm.Reference FROM credit_note AS cn INNER JOIN orders AS o ON o.Order_ID=cn.Order_ID INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Payment_Method_ID=%d LEFT JOIN countries AS oc ON oc.Country_ID=o.Billing_Country_ID LEFT JOIN regions AS orr ON orr.Region_ID=o.Billing_Region_ID LEFT JOIN countries AS ocs ON ocs.Country_ID=o.Shipping_Country_ID LEFT JOIN regions AS ors ON ors.Region_ID=o.Shipping_Region_ID INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID WHERE cn.Total>0 AND cn.Credited_On>='%s' AND cn.Tax_Rate<>20.0 AND cn.Integration_ID='' AND cn.TotalTax>0 AND o.Billing_Country_ID=222 GROUP BY cn.Credit_Note_ID", mysql_real_escape_string($form->GetValue('method')), mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE'])));
					while($data->Row) {
						for($i=0; $i<count($sageTemplate->customers); $i++) {
							if($sageTemplate->customers[$i]->customer->id == $data->Row['Contact_ID']) {
								$taxRate = number_format($data->Row['Tax_Rate'], 2, '.', '');
								$taxCode = $defaultTaxCode;

								$data2 = new DataQuery(sprintf("SELECT Integration_Reference FROM tax_code WHERE Rate=%f LIMIT 0, 1", mysql_real_escape_string($taxRate)));
								if($data2->TotalRows > 0) {
									$taxCode = $data2->Row['Integration_Reference'];
								}
								$data2->Disconnect();

                                if(!empty($data->Row['Tax_Exemption_Code'])) {
									$taxCode = $GLOBALS['SAGE_TAX_EXEMPT_CODE'];

									$country = new Country();

									if($country->Get($data->Row['Billing_Country_ID'])) {
										if($country->ExemptTaxCode->Get()) {
											$taxCode = $country->ExemptTaxCode->IntegrationReference;
										}
									}
								}

								$template = new SageTemplateInvoiceContainer();
								$template->invoice->id = sprintf('ordercredit-%s', $data->Row['Credit_Note_ID']);
								$template->invoice->customerId = $data->Row['Contact_ID'];
								$template->invoice->customerOrderNumber = $data->Row['Custom_Reference'];
								$template->invoice->accountReference = $sageTemplate->customers[$i]->customer->accountReference;
								$template->invoice->orderNumber = $data->Row['Order_ID'];
								$template->invoice->currency = 'GBP';
								$template->invoice->notes1 = $data->Row['Credit_Note_ID'];;
								$template->invoice->invoiceDate = date('c', strtotime($data->Row['Credited_On']));
                                $template->invoice->invoiceAddress->title = empty($data->Row['Billing_Organisation_Name']) ? $data->Row['Billing_Title'] : '';
								$template->invoice->invoiceAddress->forename = empty($data->Row['Billing_Organisation_Name']) ? $data->Row['Billing_First_Name'] : '';
								$template->invoice->invoiceAddress->surname = empty($data->Row['Billing_Organisation_Name']) ? $data->Row['Billing_Last_Name'] : '';
								$template->invoice->invoiceAddress->company = $data->Row['Billing_Organisation_Name'];
								$template->invoice->invoiceAddress->address1 = $data->Row['Billing_Address_1'];
								$template->invoice->invoiceAddress->address2 = $data->Row['Billing_Address_2'];
								$template->invoice->invoiceAddress->address3 = $data->Row['Billing_Address_3'];
								$template->invoice->invoiceAddress->town = $data->Row['Billing_City'];
								$template->invoice->invoiceAddress->postcode = $data->Row['Billing_Zip'];
								$template->invoice->invoiceAddress->county = $data->Row['Billing_Region'];
								$template->invoice->invoiceAddress->country = $data->Row['Billing_Country_Code'];
                                $template->invoice->deliveryAddress->title = empty($data->Row['Shipping_Organisation_Name']) ? $data->Row['Shipping_Title'] : '';
								$template->invoice->deliveryAddress->forename = empty($data->Row['Shipping_Organisation_Name']) ? $data->Row['Shipping_First_Name'] : '';
								$template->invoice->deliveryAddress->surname = empty($data->Row['Shipping_Organisation_Name']) ? $data->Row['Shipping_Last_Name'] : '';
								$template->invoice->deliveryAddress->company = $data->Row['Shipping_Organisation_Name'];
								$template->invoice->deliveryAddress->address1 = $data->Row['Shipping_Address_1'];
								$template->invoice->deliveryAddress->address2 = $data->Row['Shipping_Address_2'];
								$template->invoice->deliveryAddress->address3 = $data->Row['Shipping_Address_3'];
								$template->invoice->deliveryAddress->town = $data->Row['Shipping_City'];
								$template->invoice->deliveryAddress->postcode = $data->Row['Shipping_Zip'];
								$template->invoice->deliveryAddress->county = $data->Row['Shipping_Region'];
								$template->invoice->deliveryAddress->country = $data->Row['Shipping_Country_Code'];
                                $template->invoice->type = 'ProductCredit';
								$template->invoice->nominalCode = $data->Row['Nominal_Code'];
								$template->invoice->details = sprintf('CDT No. %d', $data->Row['Credit_Note_ID']);
								$template->invoice->taxCode = $taxCode;
								$template->invoice->department = $GLOBALS['SAGE_DEPARTMENT_INDEX'];
								$template->invoice->paymentReference = $data->Row['Reference'];
								$template->invoice->bankAccount = $GLOBALS['SAGE_BANKACCOUNT'];
								$template->invoice->paymentType = 'SalesCredit';
								$template->invoice->postedDate = date('c');
								$template->invoice->setParameter('type', 'ordercredit');
								$template->invoice->setParameter('credit_id', $data->Row['Credit_Note_ID']);

								$netApplied = 0;
								$lineCount = 0;

                                $data2 = new DataQuery(sprintf("SELECT cnl.Credit_Note_Line_ID, cnl.Quantity, cnl.Price, cnl.TotalNet, cnl.TotalTax, p.Product_ID, p.SKU, p.Product_Description FROM credit_note_line AS cnl LEFT JOIN product AS p ON p.Product_ID=cnl.Product_ID WHERE cnl.Credit_Note_ID=%d", $data->Row['Credit_Note_ID']));
	                            while($data2->Row) {
									$item = new SageTemplateItemContainer();
									$item->item->id = $data2->Row['Credit_Note_Line_ID'];
	                                $item->item->sku = !empty($data2->Row['SKU']) ? str_replace(' ', '', $data2->Row['SKU']) : $item->item->id;
									$item->item->name = htmlspecialchars_decode($data2->Row['Line_Description']);
									$item->item->description = htmlspecialchars_decode(strip_tags($data2->Row['Product_Description']));
									$item->item->quantity = $data2->Row['Quantity'];
									$item->item->price = $data2->Row['Price'];
									$item->item->reference = $data2->Row['SKU'];
									$item->item->taxRate = $data->Row['Tax_Rate'];
									$item->item->totalNet = $data2->Row['TotalNet'];
									$item->item->totalTax = $data2->Row['TotalTax'];
	                                $item->item->nominalCode = $data->Row['Nominal_Code'];
									$item->item->department = $GLOBALS['SAGE_DEPARTMENT_INDEX'];
									$item->item->type = 'Stock';
									$item->item->taxCode = $taxCode;
									$item->item->setParameter('productId', $data2->Row['Product_ID']);

									$netApplied += $data2->Row['TotalNet'];
									$lineCount++;

									$template->invoice->addItem($item);

									$data2->Next();
								}
								$data2->Disconnect();

                                if($data->Row['TotalShipping'] > 0) {
									$template->invoice->carriage->quantity = 1;
									$template->invoice->carriage->price = $data->Row['TotalShipping'];
									$template->invoice->carriage->taxRate = $data->Row['Tax_Rate'];
									$template->invoice->carriage->totalNet = $template->invoice->carriage->price * $template->invoice->carriage->quantity;
									$template->invoice->carriage->totalTax = $template->invoice->carriage->totalNet * ($data->Row['Tax_Rate'] / 100);
									$template->invoice->carriage->nominalCode = $GLOBALS['SAGE_DEFAULT_NOMINAL_CODE_CARRIAGE'];
									$template->invoice->carriage->department = $GLOBALS['SAGE_DEPARTMENT_INDEX'];
									$template->invoice->carriage->type = 'Service';
									$template->invoice->carriage->taxCode = $taxCode;

									$netApplied += $data->Row['TotalShipping'];
								}

								if($netApplied < $data->Row['TotalNet']) {
                                    $item = new SageTemplateItemContainer();
									$item->item->id = $data->Row['Credit_Note_ID'];
	                                $item->item->sku = $data->Row['Credit_Note_ID'];
									$item->item->name = 'Custom refund amount';
									$item->item->description = 'Custom refund amount';
									$item->item->quantity = 1;
									$item->item->price = $data->Row['TotalNet'] - $netApplied;
                                    $item->item->discountAmount = 0;
									$item->item->discountPercentage = 0;
									$item->item->reference = $data->Row['Credit_Note_ID'];
									$item->item->taxRate = $data->Row['Tax_Rate'];
									$item->item->totalNet = $item->item->price * $item->item->quantity;
									$item->item->totalTax = $item->item->totalNet * ($data->Row['Tax_Rate'] / 100);
	                                $item->item->nominalCode = $data->Row['Nominal_Code'];
									$item->item->department = $GLOBALS['SAGE_DEPARTMENT_INDEX'];
									$item->item->type = 'Stock';
									$item->item->taxCode = $taxCode;

									$lineCount++;

									$template->invoice->addItem($item);
								}

                                if($lineCount == 0) {
                                    $item = new SageTemplateItemContainer();
	                                $item->item->sku = 'M';
									$item->item->name = 'Shipping Refund Only';
									$item->item->type = 'FreeText';

									$template->invoice->addItem($item);
								}


								$sageTemplate->addInvoice($template);

								$print[$template->invoice->id] = $data->Row['Credit_Note_ID'];

								break;
							}
						}

						$data->Next();
					}
					$data->Disconnect();

					for($i=count($sageTemplate->invoices)-1; $i>=0; $i--) {
						if($form->GetValue(sprintf('invoice_submit_%s', $sageTemplate->invoices[$i]->invoice->id)) == 'N') {
							array_splice($sageTemplate->invoices, $i, 1);
							unset($print[$sageTemplate->invoices[$i]->invoice->id]);
						}
					}

					$products = array();

					$data = new DataQuery(sprintf("SELECT IF(c2.Contact_ID IS NOT NULL, c2.Contact_ID, c.Contact_ID) AS Contact_ID, il.Product_ID, il.Description, p.SKU, p.Product_Blurb, p.Product_Description, p.Weight, m.Manufacturer_Name FROM invoice AS i INNER JOIN invoice_line AS il ON i.Invoice_ID=il.Invoice_ID INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=i.Payment_Method_ID AND pm.Payment_Method_ID=%d INNER JOIN customer AS cu ON cu.Customer_ID=i.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN product AS p ON p.Product_ID=il.Product_ID LEFT JOIN manufacturer AS m ON m.Manufacturer_ID=p.Manufacturer_ID WHERE i.Invoice_Total>0 AND i.Created_On>='%s' AND i.Tax_Rate<>20.0 AND i.Integration_ID='' AND i.Invoice_Tax>0 AND i.Invoice_Country LIKE 'United Kingdom'", mysql_real_escape_string($form->GetValue('method')), mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE'])));
					while($data->Row) {
						for($i=0; $i<count($sageTemplate->customers); $i++) {
							if($sageTemplate->customers[$i]->customer->id == $data->Row['Contact_ID']) {
								for($j=0; $j<count($sageTemplate->invoices); $j++) {
									for($k=0; $k<count($sageTemplate->invoices[$j]->invoice->items); $k++) {
										if(!is_null($productId = $sageTemplate->invoices[$j]->invoice->items[$k]->item->getParameter('productId'))) {
											if($productId == $data->Row['Product_ID']) {
												$products[$data->Row['Product_ID']] = $data->Row;
											}
										}
									}
								}

								break;
							}
						}

						$data->Next();
					}
					$data->Disconnect();

                    $data = new DataQuery(sprintf("SELECT IF(c2.Contact_ID IS NOT NULL, c2.Contact_ID, c.Contact_ID) AS Contact_ID, cnl.Product_ID, cnl.Line_Description AS Description, p.SKU, p.Product_Blurb, p.Product_Description, p.Weight, m.Manufacturer_Name FROM credit_note AS cn INNER JOIN orders AS o ON o.Order_ID=cn.Order_ID INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Payment_Method_ID=%d INNER JOIN credit_note_line AS cnl ON cnl.Credit_Note_ID=cn.Credit_Note_ID INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN product AS p ON p.Product_ID=cnl.Product_ID LEFT JOIN manufacturer AS m ON m.Manufacturer_ID=p.Manufacturer_ID WHERE cn.Total>0 AND cn.Credited_On>='%s' AND cn.Tax_Rate<>20.0 AND cn.Integration_ID='' AND cn.TotalTax>0 AND o.Billing_Country_ID=222", mysql_real_escape_string($form->GetValue('method')), mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE'])));
					while($data->Row) {
						for($i=0; $i<count($sageTemplate->customers); $i++) {
							if($sageTemplate->customers[$i]->customer->id == $data->Row['Contact_ID']) {
								for($j=0; $j<count($sageTemplate->invoices); $j++) {
									for($k=0; $k<count($sageTemplate->invoices[$j]->invoice->items); $k++) {
										if(!is_null($productId = $sageTemplate->invoices[$j]->invoice->items[$k]->item->getParameter('productId'))) {
											if($productId == $data->Row['Product_ID']) {
												$products[$data->Row['Product_ID']] = $data->Row;
											}
										}
									}
								}

								break;
							}
						}

						$data->Next();
					}
					$data->Disconnect();
					
					$logs = array();

					for($i=count($sageTemplate->invoices)-1; $i>=0; $i--) {
						$log = new IntegrationSageLog();
						
						switch($sageTemplate->invoices[$i]->invoice->getParameter('type')) {
							case 'orderinvoice':
		                        $invoice = new Invoice();

								if($invoice->Get($sageTemplate->invoices[$i]->invoice->getParameter('invoice_id'))) {
									$invoice->IntegrationReference = $sageTemplate->invoices[$i]->invoice->id;
									$invoice->Update();
								}
								
								$log->type = 'Invoice';
								$log->referenceId = $invoice->ID;
								$log->accountReference = $sageTemplate->invoices[$i]->invoice->accountReference;
								$log->contactName = !empty($sageTemplate->invoices[$i]->invoice->invoiceAddress->company) ? $sageTemplate->invoices[$i]->invoice->invoiceAddress->company : sprintf('%s %s', $sageTemplate->invoices[$i]->invoice->invoiceAddress->forename, $sageTemplate->invoices[$i]->invoice->invoiceAddress->surname);
								$log->amount = $invoice->Total;

								break;

							case 'ordercredit':
                                $credit = new CreditNote();

								if($credit->Get($sageTemplate->invoices[$i]->invoice->getParameter('credit_id'))) {
									$credit->IntegrationReference = $sageTemplate->invoices[$i]->invoice->id;
									$credit->Update();
								}
								
								$log->type = 'Credit';
								$log->referenceId = $credit->ID;
								$log->accountReference = $sageTemplate->invoices[$i]->invoice->accountReference;
								$log->contactName = !empty($sageTemplate->invoices[$i]->invoice->invoiceAddress->company) ? $sageTemplate->invoices[$i]->invoice->invoiceAddress->company : sprintf('%s %s', $sageTemplate->invoices[$i]->invoice->invoiceAddress->forename, $sageTemplate->invoices[$i]->invoice->invoiceAddress->surname);
								$log->amount = $credit->Total * -1;

								break;
						}
						
						$logs[] = $log;
					}

					foreach($products as $product) {
						$template = new SageTemplateProductContainer();
						$template->product->id = $product['Product_ID'];
						$template->product->sku = !empty($product['SKU']) ? str_replace(' ', '', $product['SKU']) : $template->product->id;
						$template->product->name = htmlspecialchars_decode($product['Description']);
						$template->product->description = htmlspecialchars_decode(strip_tags($product['Product_Blurb']));
						$template->product->longDescription = htmlspecialchars_decode(strip_tags($product['Product_Description']));

						$data2 = new DataQuery(sprintf("SELECT Price_Base_Our FROM product_prices WHERE Product_ID=%d AND Price_Starts_On<NOW() ORDER BY Quantity DESC, Price_Starts_On DESC LIMIT 0, 1", mysql_real_escape_string($product['Product_ID'])));
						$template->product->price = ($data2->TotalRows > 0) ? $data2->Row['Price_Base_Our'] : 0;
						$data2->Disconnect();

						$template->product->weight = $product['Weight'];

						$data2 = new DataQuery(sprintf("SELECT SUM(ws.Quantity_In_Stock) AS Quantity FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B' WHERE ws.Product_ID=%d", mysql_real_escape_string($product['Product_ID'])));
						$template->product->quantityInStock = ($data2->TotalRows > 0) ? $data2->Row['Quantity'] : 0;
						$data2->Disconnect();

						$template->product->taxCode = 1;
						$template->product->publish = 0;
						$template->product->specialOffer = 0;
						$template->product->department = $GLOBALS['SAGE_DEPARTMENT_INDEX'];
						$template->product->manufacturer = $product['Manufacturer_Name'];
						$template->product->itemType = 'Stock';

						$sageTemplate->addProduct($template);
					}

					for($i=count($sageTemplate->customers)-1; $i>=0; $i--) {
						$isEmpty = true;
						$isLocked = false;

						for($j=count($sageTemplate->invoices)-1; $j>=0; $j--) {
							if($sageTemplate->invoices[$j]->invoice->customerId == $sageTemplate->customers[$i]->customer->id) {
								$isEmpty = false;
								break;
							}
						}

						if($sageTemplate->customers[$i]->customer->getParameter('locked') == 'Y') {
							$isLocked = true;
						}

						if($isEmpty || $isLocked) {
							array_splice($sageTemplate->customers, $i, 1);
						}
					}

					$xmlTemplate = $sageTemplate->buildXml();
					$xmlString = $sageTemplate->formatXml($xmlTemplate);
					
					$xmlData = preg_replace('/<Company.*?>/', '<Company>', $xmlTemplate, 1);
					$xmlData = xml2array($xmlData);

					$hasData = false;

					if(isset($xmlData['Company'][0])) {
						foreach($xmlData['Company'][0] as $key=>$value) {
							if(!empty($value) && is_array($value)) {
								$hasData = true;
								break;
							}
						}
					}

					if($hasData) {
						$fileName = md5(sprintf('export_%s', date('Ymd_His')));

						$fileHandler = fopen(sprintf('%sremote/sage/feeds/%s', $GLOBALS['DATA_DIR_FS'], $fileName), 'w');
						if($fileHandler) {
							$cipher = new Cipher($xmlString);
							$cipher->Encrypt();

							fwrite($fileHandler, $cipher->Value);
							fclose($fileHandler);

							$integration = new IntegrationSage();
							$integration->DataFeed = $fileName;
							$integration->Type = 'Export';
							$integration->Add();
							
							foreach($logs as $log) {
								$log->integrationSageId = $integration->ID;
								$log->add();
							}
						}
						
						$md5 = md5(serialize($print));
					
						$_SESSION['SageExport'][$md5] = $print;
	 
						redirect(sprintf("Location: %s?action=complete&documents=%s", $_SERVER['PHP_SELF'], $md5));
					}
				}

				redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
			}
		}
	}

	$page = new Page('Sage Export (Pre VAT Rise)', 'Integrate with Sage.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

    echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

    $window = new StandardWindow('Payment Methods');
	$webForm = new StandardForm();

    echo $window->Open();
	echo $window->AddHeader('Filter out payment methods');
	echo $window->OpenContent();
	echo $webForm->Open();
	?>

    <tr>
		<td class="label">Payment Methods</td>
		<td class="input" nowrap="nowrap">
			<?php
			for($i=1; $i<count($methods); $i++) {
				echo sprintf('%s %s<br />', $form->GetHTML('method', $i), $form->GetLabel('method', $i));
			}
			?>
		</td>
	</tr>

	<?php
	echo $webForm->AddRow('', sprintf('<input type="submit" name="filter" value="filter" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	if(count($sageTemplate->customers) > 0) {
		echo '<br />';

		$window = new StandardWindow('Sage Accounts');
		$webForm = new StandardForm();

		echo $window->Open();
		echo $window->AddHeader('Enter the account information for integrating.');
		echo $window->OpenContent();
		echo $webForm->Open();
		?>

		<tr>
			<td class="label">&nbsp;</td>
			<td class="input" nowrap="nowrap"><strong>Account Reference</strong></td>
			<td class="input" nowrap="nowrap" style="text-align: center;"><strong>New Account</strong></td>
			<td class="input" nowrap="nowrap"><strong>Nominal Code</strong></td>
			<td class="input" nowrap="nowrap"><strong>Credit Account</strong></td>
			<td class="input" nowrap="nowrap" style="text-align: center;"><strong>Locked</strong></td>
		</tr>

		<?php
		for($i=0; $i<count($sageTemplate->customers); $i++) {
			?>

			<tr>
				<td class="label"><?php echo sprintf('<a href="contact_profile.php?cid=%d" target="_blank">%s</a>', $sageTemplate->customers[$i]->customer->id, $sageTemplate->customers[$i]->customer->companyName); ?></td>
				<td class="input" nowrap="nowrap"><?php echo $form->GetHTML(sprintf('customer_account_%d', $sageTemplate->customers[$i]->customer->id)); ?></td>
				<td class="input" nowrap="nowrap" style="text-align: center;"><?php echo $form->GetHTML(sprintf('customer_new_%d', $sageTemplate->customers[$i]->customer->id)); ?></td>
				<td class="input" nowrap="nowrap"><?php echo $form->GetHTML(sprintf('customer_nominal_%d', $sageTemplate->customers[$i]->customer->id)); ?></td>
				<td class="input" nowrap="nowrap"><?php echo $form->GetHTML(sprintf('customer_credit_active_%d', $sageTemplate->customers[$i]->customer->id)); ?> <?php echo $form->GetHTML(sprintf('customer_credit_limit_%d', $sageTemplate->customers[$i]->customer->id)); ?></td>
				<td class="input" nowrap="nowrap" style="text-align: center;"><?php echo $form->GetHTML(sprintf('customer_locked_%d', $sageTemplate->customers[$i]->customer->id)); ?></td>
			</tr>
			<tr>
				<td class="label">&nbsp;</td>
				<td class="input" colspan="5" style="background-color: #fff; border: 1px solid #ccc;">

					<?php
					$entities = array();

					for($j=0; $j<count($invoices); $j++) {
						if($invoices[$j]->invoice->customerId == $sageTemplate->customers[$i]->customer->id) {
							$item = explode('-', $invoices[$j]->invoice->id);

							if(count($item) > 1) {
								$id = $item[1];
								$type = strtolower($item[0]);

								if(!isset($entities[$type])) {
									$entities[$type] = array();
								}

								$entities[$type][] = $invoices[$j]->invoice;
							}
						}
					}

					foreach($entities as $key=>$collection) {
						$linkString = '';

						switch($key) {
							case 'orderinvoice':
								echo '<strong>Order Invoices</strong><br /><br />';

								$linkString = '<a href="order_details.php?orderid=%d" target="_blank">%s</a>';
								break;

                            case 'ordercredit':
								echo '<strong>Order Credits</strong><br /><br />';

								$linkString = '<a href="order_details.php?orderid=%d" target="_blank">%s</a>';
								break;

							default:
								continue;
						}
						?>

						<table class="orderDetails">
							<tr>
                                <td width="1%">&nbsp;</td>
								<td width="17%"><strong>Parent</strong></td>
								<td width="17%"><strong>Entity</strong></td>
								<td width="13%"><strong>Date</strong></td>
								<td width="20%"><strong>Payment</strong></td>
								<td width="20%"><strong>Contact</strong></td>
								<td width="4%" align="right"><strong>Tax</strong></td>
								<td width="8%" align="right"><strong>Total</strong></td>
							</tr>

							<?php
							for($j=0; $j<count($collection); $j++) {
								$billingContact = array();

								if(!empty($collection[$j]->invoiceAddress->title)) {
									$billingContact[] = $collection[$j]->invoiceAddress->title;
								}

								if(!empty($collection[$j]->invoiceAddress->forename)) {
									$billingContact[] = $collection[$j]->invoiceAddress->forename;
								}

								if(!empty($collection[$j]->invoiceAddress->surname)) {
									$billingContact[] = $collection[$j]->invoiceAddress->surname;
								}

								switch($key) {
                                    case 'orderinvoice':
										$total = $totals['Invoices'][$collection[$j]->getParameter('invoiceId')];
										$colour = '00cc00';
										$sign = '+';
										break;

		                            case 'ordercredit':
										$total = $totals['Credits'][$collection[$j]->getParameter('creditId')];
										$colour = 'ff0000';
										$sign = '-';
										break;

									default:
										$total = 0;
                                        $colour = '000000';
										$sign = '';
								}
								?>

								<tr>
									<td><?php echo $form->GetHTML(sprintf('invoice_submit_%s', $collection[$j]->id)); ?></td>
									<td><?php echo sprintf($linkString, $collection[$j]->getParameter('parentId'), $collection[$j]->getParameter('parentNumber')); ?></td>
									<td><?php echo $collection[$j]->getParameter('entityNumber'); ?></td>
									<td><?php echo date('d/m/Y', strtotime($collection[$j]->invoiceDate)); ?></td>
									<td><?php echo $collection[$j]->getParameter('paymentMethod'); ?></td>
									<td><?php echo implode(' ', $billingContact); ?></td>
									<td align="right"><?php echo $collection[$j]->getParameter('taxRate'); ?>%</td>
									<td align="right" style="color: #<?php echo $colour; ?>;"><?php echo $sign;?><?php echo number_format(round($total, 2), 2, '.', ','); ?></td>
								</tr>

								<?php
							}
							?>

						</table>
						<br />

						<?php
					}
					?>

				</td>
			</tr>

			<?php
		}

		echo $webForm->AddRow('', sprintf('<input type="submit" name="continue" value="continue" class="btn" tabindex="%s">', $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
	}

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}