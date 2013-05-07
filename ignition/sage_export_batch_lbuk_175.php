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
	$page = new Page(sprintf('<a href="%s">Sage Export Batch (LBUK)</a> &gt; Integration Locked', $_SERVER['PHP_SELF']), 'Sage integration locked.');
	$page->Display('header');

	echo '<p>There are outstanding Sage export related data feeds awaiting execution.<br />Allowing more than one consecutive integration session may cause duplicate data to appear within Sage and compromise the referential integrity of any unconfirmed integration associations.</p>';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function complete() {
	$page = new Page(sprintf('<a href="%s">Sage Export Batch (LBUK)</a> &gt; Integration Complete', $_SERVER['PHP_SELF']), 'Sage integration complete.');
    $page->AddToHead(sprintf('<script language="javascript" type="text/javascript">%s</script>', isset($_REQUEST['documents']) ? sprintf('popUrl(\'sage_export_batch_print.php?documents=%s\', 800, 600);', $_REQUEST['documents']) : ''));
	$page->Display('header');

	echo '<p>Sage integration was completed succesfully.<br />Please allow for the next integration iteration before data is available within Sage.</p>';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function integrate() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IntegrationSage.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IntegrationSageLog.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateCompany.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateInvoiceContainer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateItemContainer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateProductContainer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM integration_sage WHERE (Type LIKE 'Export' OR Type LIKE 'Confirmation') AND Is_Synchronised='N'"));
	if($data->Row['Count'] > 0) {
		redirect(sprintf("Location: %s?action=locked", $SERVER['PHP_SELF']));
	}
	$data->Disconnect();
	
	$connection = new MySQLConnection($GLOBALS['SYNC_DB_HOST'][0], $GLOBALS['SYNC_DB_NAME'][0], $GLOBALS['SYNC_DB_USERNAME'][0], $GLOBALS['SYNC_DB_PASSWORD'][0]);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'step1', 'alpha_numeric', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('account', 'Account Reference', 'text', 'BLTLIGHT', 'anything', 1, 8, false, 'style="font-family: arial, sans-serif;"');

    $totals = array('Invoices' => array(), 'Credits' => array());
	$invoices = array();
	$scriptDates = array();

	$data = new DataQuery(sprintf("SELECT DATE(i.Created_On) AS Date_Created, IF(c2.Contact_ID IS NOT NULL, c2.Contact_ID, c.Contact_ID) AS Contact_ID, i.Invoice_ID, i.Invoice_Net, i.Invoice_Shipping, i.Invoice_Discount, i.Invoice_Tax, i.Invoice_Total, ROUND((ROUND((i.Invoice_Tax / (i.Invoice_Net + i.Invoice_Shipping - i.Invoice_Discount)) * 100 * 2) / 2), 1) AS Tax_Rate, i.Is_Paid, i.Invoice_Title, i.Invoice_First_Name, i.Invoice_Last_Name, i.Invoice_Organisation, i.Invoice_Address_1, i.Invoice_Address_2, i.Invoice_Address_3, i.Invoice_City, ic.Country_ID AS Invoice_Country_ID, ir.Region_ID AS Invoice_Region_ID, i.Invoice_Zip, i.Created_On, ic.ISO_Code_2 AS Invoice_Country_Code, ir.Region_Name AS Invoice_Region, o.Order_ID, o.Custom_Order_No AS Custom_Reference, o.Shipping_Title, o.Shipping_First_Name, o.Shipping_Last_Name, o.Shipping_Organisation_Name, o.Shipping_Address_1, o.Shipping_Address_2, o.Shipping_Address_3, o.Shipping_City, o.Shipping_Zip, irs.Region_Name AS Shipping_Region, ics.ISO_Code_2 AS Shipping_Country_Code, o.TaxExemptCode AS Tax_Exemption_Code FROM invoice AS i LEFT JOIN countries AS ic ON ic.Country LIKE i.Invoice_Country LEFT JOIN regions AS ir ON ir.Region_Name LIKE i.Invoice_Region AND ir.Country_ID=ic.Country_ID INNER JOIN customer AS cu ON cu.Customer_ID=i.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID INNER JOIN orders AS o ON o.Order_ID=i.Order_ID LEFT JOIN countries AS ics ON ics.Country_ID=o.Shipping_Country_ID LEFT JOIN regions AS irs ON irs.Region_ID=o.Shipping_Region_ID AND irs.Country_ID=ics.Country_ID WHERE i.Invoice_Total>0 AND i.Is_Paid='Y' AND i.Created_On>='%s' AND i.Created_On<'2011-01-04 00:00:00' AND i.Integration_ID='' GROUP BY i.Invoice_ID HAVING Tax_Rate>0 ORDER BY Date_Created ASC, Tax_Rate ASC", mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE_BATCH_LBUK'])), $connection);
	while($data->Row) {
		$taxRate = number_format($data->Row['Tax_Rate'], 2, '.', '');
		
		$template = new SageTemplateInvoiceContainer();
		$template->invoice->id = sprintf('orderinvoice-%s', $data->Row['Invoice_ID']);
		$template->invoice->invoiceDate = date('c', strtotime($data->Row['Created_On']));
		$template->invoice->invoiceAddress->title = $data->Row['Invoice_Title'];
		$template->invoice->invoiceAddress->forename = $data->Row['Invoice_First_Name'];
		$template->invoice->invoiceAddress->surname = $data->Row['Invoice_Last_Name'];
		$template->invoice->carriage->quantity = 1;
		$template->invoice->carriage->price = $data->Row['Invoice_Shipping'];
		$template->invoice->carriage->taxRate = $taxRate;
		$template->invoice->carriage->totalNet = $template->invoice->carriage->price * $template->invoice->carriage->quantity;
		$template->invoice->carriage->totalTax = $template->invoice->carriage->totalNet * ($template->invoice->carriage->taxRate / 100);
		$template->invoice->setParameter('entityNumber', sprintf('Invoice %s', $data->Row['Invoice_ID']));
		$template->invoice->setParameter('parentNumber', sprintf('Order %s', $data->Row['Order_ID']));
		$template->invoice->setParameter('parentId', $data->Row['Order_ID']);
		$template->invoice->setParameter('invoiceId', $data->Row['Invoice_ID']);
		$template->invoice->setParameter('taxRate', $taxRate);

		$data2 = new DataQuery(sprintf("SELECT il.Quantity, (il.Line_Total / il.Quantity) AS Price, (il.Line_Discount / il.Quantity) AS Discount_Amount, (((il.Line_Discount / il.Quantity) / il.Price)*100) AS Discount_Percentage, il.Line_Total, il.Line_Discount, il.Line_Tax FROM invoice_line AS il WHERE il.Invoice_ID=%d", $data->Row['Invoice_ID']), $connection);
		while($data2->Row) {
			$item = new SageTemplateItemContainer();
			$item->item->quantity = $data2->Row['Quantity'];
			$item->item->price = $data2->Row['Price'];
			$item->item->discountAmount = $data2->Row['Discount_Amount'];
			$item->item->discountPercentage = $data2->Row['Discount_Percentage'];
			$item->item->taxRate = $taxRate;
			$item->item->totalNet = $data2->Row['Line_Total'] - $data2->Row['Line_Discount'];
			$item->item->totalTax = $data2->Row['Line_Tax'];

			$template->invoice->addItem($item);

			$data2->Next();
		}
		$data2->Disconnect();

		if(!isset($invoices[$data->Row['Date_Created']])) {
			$invoices[$data->Row['Date_Created']] = array();
		}

        if(!isset($invoices[$data->Row['Date_Created']][$taxRate])) {
			$invoices[$data->Row['Date_Created']][$taxRate] = array();
		}

		$totals['Invoices'][$data->Row['Invoice_ID']] = $data->Row['Invoice_Total'];
		$invoices[$data->Row['Date_Created']][$taxRate][] = $template;

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

		$defaultValue = (strtotime($data->Row['Date_Created']) < strtotime(date('Y-m-d 00:00:00'))) ? 'Y' : 'N';

		$form->AddField(sprintf('invoice_submit_%s', $template->invoice->id), sprintf('Submit Entity for \'%s\'', implode(' ', $billingContact)), 'checkbox', $defaultValue, 'boolean', 1, 1, false);

		if(!isset($scriptDates[$data->Row['Date_Created']])) {
			$scriptDates[$data->Row['Date_Created']] = array();
		}

		$scriptDates[$data->Row['Date_Created']][] = sprintf('invoice_submit_%s', $template->invoice->id);

		$data->Next();
	}
	$data->Disconnect();
	
    $data = new DataQuery(sprintf("SELECT DATE(cn.Credited_On) AS Date_Created, IF(c2.Contact_ID IS NOT NULL, c2.Contact_ID, c.Contact_ID) AS Contact_ID, cn.Credit_Note_ID, cn.TotalNet, cn.TotalShipping, cn.TotalTax, cn.Total, ROUND((ROUND((cn.TotalTax / cn.TotalNet) * 100 * 2) / 2), 1) AS Tax_Rate, o.Billing_Title, o.Billing_First_Name, o.Billing_Last_Name, o.Billing_Organisation_Name, o.Billing_Address_1, o.Billing_Address_2, o.Billing_Address_3, o.Billing_City, o.Billing_Country_ID, o.Billing_Region_ID, o.Billing_Zip, cn.Credited_On, oc.ISO_Code_2 AS Billing_Country_Code, orr.Region_Name AS Billing_Region, o.Order_ID, o.Custom_Order_No AS Custom_Reference, o.Shipping_Title, o.Shipping_First_Name, o.Shipping_Last_Name, o.Shipping_Organisation_Name, o.Shipping_Address_1, o.Shipping_Address_2, o.Shipping_Address_3, o.Shipping_City, o.Shipping_Zip, ors.Region_Name AS Shipping_Region, ocs.ISO_Code_2 AS Shipping_Country_Code, o.TaxExemptCode AS Tax_Exemption_Code FROM credit_note AS cn INNER JOIN orders AS o ON o.Order_ID=cn.Order_ID LEFT JOIN countries AS oc ON oc.Country_ID=o.Billing_Country_ID LEFT JOIN regions AS orr ON orr.Region_ID=o.Billing_Region_ID LEFT JOIN countries AS ocs ON ocs.Country_ID=o.Shipping_Country_ID LEFT JOIN regions AS ors ON ors.Region_ID=o.Shipping_Region_ID INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID WHERE cn.Total>0 AND cn.Credit_Type LIKE 'Card Refund' AND cn.Credited_On>='%s' AND cn.Credited_On<'2011-01-04 00:00:00' AND cn.Integration_ID='' GROUP BY cn.Credit_Note_ID HAVING Tax_Rate>0 ORDER BY Date_Created ASC, Tax_Rate ASC", mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE_BATCH_LBUK'])), $connection);
	while($data->Row) {
        $taxRate = number_format($data->Row['Tax_Rate'], 2, '.', '');
        
        $template = new SageTemplateInvoiceContainer();
		$template->invoice->id = sprintf('ordercredit-%s', $data->Row['Credit_Note_ID']);
		$template->invoice->invoiceDate = date('c', strtotime($data->Row['Credited_On']));
		$template->invoice->invoiceAddress->title = $data->Row['Billing_Title'];
		$template->invoice->invoiceAddress->forename = $data->Row['Billing_First_Name'];
		$template->invoice->invoiceAddress->surname = $data->Row['Billing_Last_Name'];
		$template->invoice->carriage->quantity = 1;
		$template->invoice->carriage->price = $data->Row['TotalShipping'];
		$template->invoice->carriage->taxRate = $taxRate;
		$template->invoice->carriage->totalNet = $template->invoice->carriage->price * $template->invoice->carriage->quantity;
		$template->invoice->carriage->totalTax = $template->invoice->carriage->totalNet * ($taxRate / 100);
		$template->invoice->setParameter('entityNumber', sprintf('Credit %s', $data->Row['Credit_Note_ID']));
		$template->invoice->setParameter('parentNumber', sprintf('Order %s', $data->Row['Order_ID']));
		$template->invoice->setParameter('parentId', $data->Row['Order_ID']);
		$template->invoice->setParameter('creditId', $data->Row['Credit_Note_ID']);
		$template->invoice->setParameter('taxRate', $taxRate);

		$data2 = new DataQuery(sprintf("SELECT cnl.Quantity, cnl.Price, cnl.TotalNet, cnl.TotalTax FROM credit_note_line AS cnl WHERE cnl.Credit_Note_ID=%d", $data->Row['Credit_Note_ID']), $connection);
		while($data2->Row) {
			$item = new SageTemplateItemContainer();
			$item->item->quantity = $data2->Row['Quantity'];
			$item->item->price = $data2->Row['Price'];
			$item->item->taxRate = $taxRate;
			$item->item->totalNet = $data2->Row['TotalNet'];
			$item->item->totalTax = $data2->Row['TotalTax'];

			$template->invoice->addItem($item);

			$data2->Next();
		}
		$data2->Disconnect();

		if(!isset($invoices[$data->Row['Date_Created']])) {
			$invoices[$data->Row['Date_Created']] = array();
		}

        if(!isset($invoices[$data->Row['Date_Created']][$taxRate])) {
			$invoices[$data->Row['Date_Created']][$taxRate] = array();
		}

		$totals['Credits'][$data->Row['Credit_Note_ID']] = $data->Row['Total'];
		$invoices[$data->Row['Date_Created']][$taxRate][] = $template;

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

		$defaultValue = (strtotime($data->Row['Date_Created']) < strtotime(date('Y-m-d 00:00:00'))) ? 'Y' : 'N';

		$form->AddField(sprintf('invoice_submit_%s', $template->invoice->id), sprintf('Submit Entity for \'%s\'', implode(' ', $billingContact)), 'checkbox', $defaultValue, 'boolean', 1, 1, false);

        if(!isset($scriptDates[$data->Row['Date_Created']])) {
			$scriptDates[$data->Row['Date_Created']] = array();
		}

		$scriptDates[$data->Row['Date_Created']][] = sprintf('invoice_submit_%s', $template->invoice->id);

		$data->Next();
	}
	$data->Disconnect();
	
	ksort($invoices);

	foreach($invoices as $date=>$invoiceData) {
		$defaultValue = (strtotime($date) < strtotime(date('Y-m-d 00:00:00'))) ? 'Y' : 'N';

        $form->AddField(sprintf('select_date_%s', $date), 'Date', 'checkbox', $defaultValue, 'boolean', 1, 1, false, sprintf('onclick="toggleDate(this, \'%s\');"', $date));
	}

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if(isset($_REQUEST['continue'])) {
			if($form->Validate()) {
				$print = array();
				
				$sageTemplate = new SageTemplateCompany();

                $defaultTaxCode = 6;

				$invoices = array('Invoices' => array(), 'Credits' => array());

				$data = new DataQuery(sprintf("SELECT DATE(i.Created_On) AS Date_Created, IF(c2.Contact_ID IS NOT NULL, c2.Contact_ID, c.Contact_ID) AS Contact_ID, i.Invoice_ID, i.Invoice_Net, i.Invoice_Shipping, i.Invoice_Discount, i.Invoice_Tax, i.Invoice_Total, ROUND((ROUND((i.Invoice_Tax / (i.Invoice_Net + i.Invoice_Shipping - i.Invoice_Discount)) * 100 * 2) / 2), 1) AS Tax_Rate, i.Is_Paid, i.Invoice_Title, i.Invoice_First_Name, i.Invoice_Last_Name, i.Invoice_Organisation, i.Invoice_Address_1, i.Invoice_Address_2, i.Invoice_Address_3, i.Invoice_City, ic.Country_ID AS Invoice_Country_ID, ir.Region_ID AS Invoice_Region_ID, i.Invoice_Zip, i.Created_On, ic.ISO_Code_2 AS Invoice_Country_Code, ir.Region_Name AS Invoice_Region, o.Order_ID, o.Custom_Order_No AS Custom_Reference, o.Shipping_Title, o.Shipping_First_Name, o.Shipping_Last_Name, o.Shipping_Organisation_Name, o.Shipping_Address_1, o.Shipping_Address_2, o.Shipping_Address_3, o.Shipping_City, o.Shipping_Zip, irs.Region_Name AS Shipping_Region, ics.ISO_Code_2 AS Shipping_Country_Code, o.TaxExemptCode AS Tax_Exemption_Code FROM invoice AS i LEFT JOIN countries AS ic ON ic.Country LIKE i.Invoice_Country LEFT JOIN regions AS ir ON ir.Region_Name LIKE i.Invoice_Region AND ir.Country_ID=ic.Country_ID INNER JOIN customer AS cu ON cu.Customer_ID=i.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID INNER JOIN orders AS o ON o.Order_ID=i.Order_ID LEFT JOIN countries AS ics ON ics.Country_ID=o.Shipping_Country_ID LEFT JOIN regions AS irs ON irs.Region_ID=o.Shipping_Region_ID AND irs.Country_ID=ics.Country_ID WHERE i.Invoice_Total>0 AND i.Is_Paid='Y' AND i.Created_On>='%s' AND i.Created_On<'2011-01-04 00:00:00' AND i.Integration_ID='' GROUP BY i.Invoice_ID HAVING Tax_Rate>0 ORDER BY Date_Created ASC, Tax_Rate ASC", mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE_BATCH_LBUK'])), $connection);
				while($data->Row) {
					if($form->GetValue(sprintf('invoice_submit_orderinvoice-%s', $data->Row['Invoice_ID'])) == 'Y') {
						$taxRate = number_format($data->Row['Tax_Rate'], 2, '.', '');

                        if(!isset($invoices['Invoices'][$data->Row['Date_Created']])) {
							$invoices['Invoices'][$data->Row['Date_Created']] = array();
						}

				        if(!isset($invoices['Invoices'][$data->Row['Date_Created']][$taxRate])) {
							$invoices['Invoices'][$data->Row['Date_Created']][$taxRate] = array();
						}

						$invoices['Invoices'][$data->Row['Date_Created']][$taxRate][] = $data->Row;
					}

					$data->Next();
				}
				$data->Disconnect();

				$data = new DataQuery(sprintf("SELECT DATE(cn.Credited_On) AS Date_Created, IF(c2.Contact_ID IS NOT NULL, c2.Contact_ID, c.Contact_ID) AS Contact_ID, cn.Credit_Note_ID, cn.TotalNet, cn.TotalShipping, cn.TotalTax, cn.Total, ROUND((ROUND((cn.TotalTax / cn.TotalNet) * 100 * 2) / 2), 1) AS Tax_Rate, o.Billing_Title, o.Billing_First_Name, o.Billing_Last_Name, o.Billing_Organisation_Name, o.Billing_Address_1, o.Billing_Address_2, o.Billing_Address_3, o.Billing_City, o.Billing_Country_ID, o.Billing_Region_ID, o.Billing_Zip, cn.Credited_On, oc.ISO_Code_2 AS Billing_Country_Code, orr.Region_Name AS Billing_Region, o.Order_ID, o.Custom_Order_No AS Custom_Reference, o.Shipping_Title, o.Shipping_First_Name, o.Shipping_Last_Name, o.Shipping_Organisation_Name, o.Shipping_Address_1, o.Shipping_Address_2, o.Shipping_Address_3, o.Shipping_City, o.Shipping_Zip, ors.Region_Name AS Shipping_Region, ocs.ISO_Code_2 AS Shipping_Country_Code, o.TaxExemptCode AS Tax_Exemption_Code FROM credit_note AS cn INNER JOIN orders AS o ON o.Order_ID=cn.Order_ID LEFT JOIN countries AS oc ON oc.Country_ID=o.Billing_Country_ID LEFT JOIN regions AS orr ON orr.Region_ID=o.Billing_Region_ID LEFT JOIN countries AS ocs ON ocs.Country_ID=o.Shipping_Country_ID LEFT JOIN regions AS ors ON ors.Region_ID=o.Shipping_Region_ID INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID WHERE cn.Total>0 AND cn.Credit_Type LIKE 'Card Refund' AND cn.Credited_On>='%s' AND cn.Credited_On<'2011-01-04 00:00:00' AND cn.Integration_ID='' GROUP BY cn.Credit_Note_ID HAVING Tax_Rate>0 ORDER BY Date_Created ASC, Tax_Rate ASC", mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE_BATCH_LBUK'])), $connection);
				while($data->Row) {
					if($form->GetValue(sprintf('invoice_submit_ordercredit-%s', $data->Row['Credit_Note_ID'])) == 'Y') {
						$taxRate = number_format($data->Row['Tax_Rate'], 2, '.', '');

                        if(!isset($invoices['Credits'][$data->Row['Date_Created']])) {
							$invoices['Credits'][$data->Row['Date_Created']] = array();
						}

				        if(!isset($invoices['Credits'][$data->Row['Date_Created']][$taxRate])) {
							$invoices['Credits'][$data->Row['Date_Created']][$taxRate] = array();
						}

						$invoices['Credits'][$data->Row['Date_Created']][$taxRate][] = $data->Row;
					}

					$data->Next();
				}
				$data->Disconnect();
				
				$logs = array();

                foreach($invoices['Invoices'] as $date=>$invoiceDate) {
					foreach($invoiceDate as $taxRate=>$invoiceData) {
						$log = new IntegrationSageLog();
						$log->type = 'Invoice';
	                        
	                    $taxCode = $defaultTaxCode;

						$data = new DataQuery(sprintf("SELECT Integration_Reference FROM tax_code WHERE Rate=%f LIMIT 0, 1", mysql_real_escape_string($taxRate)));
						if($data->TotalRows > 0) {
							$taxCode = $data->Row['Integration_Reference'];
						}
						$data->Disconnect();

	                    $template = new SageTemplateInvoiceContainer();
						$template->invoice->id = sprintf('batchinvoice_lbuk-%s-%s', str_replace('-', '', $date), $taxRate);
						$template->invoice->accountReference = $form->GetValue('account');
						$template->invoice->currency = 'GBP';
						$template->invoice->invoiceAddress->company = 'Light Bulbs UK Daily Sales Gateway';
						$template->invoice->invoiceDate = date('c', strtotime($date . ' 00:00:00'));
						$template->invoice->type = 'ProductInvoice';
						$template->invoice->nominalCode = $GLOBALS['SAGE_DEFAULT_NOMINAL_CODE_LBUK'];
						$template->invoice->taxCode = $taxCode;
						$template->invoice->department = $GLOBALS['SAGE_DEPARTMENT_INDEX_LBUK'];
						$template->invoice->paymentReference = 'Credit/Debit Card';
						$template->invoice->paymentAmount = 0;
						$template->invoice->bankAccount = $GLOBALS['SAGE_BANKACCOUNT'];
						$template->invoice->paymentType = 'SalesInvoice';
						$template->invoice->postedDate = date('c');
						
						$log->accountReference = $template->invoice->accountReference;
						$log->contactName = $template->invoice->invoiceAddress->company;

						$print[$template->invoice->id] = array();
						
						$shippingTotal = 0;							

						for($j=0; $j<count($invoiceData); $j++) {
							$shippingTotal += $invoiceData[$j]['Invoice_Shipping'];
							
							new DataQuery(sprintf("UPDATE invoice SET Integration_Reference='%s' WHERE Invoice_ID=%d", mysql_real_escape_string($template->invoice->id), $invoiceData[$j]['Invoice_ID']), $connection);

							$contact = array();

							if(!empty($invoiceData[$j]['Invoice_Title'])) {
								$contact[] = $invoiceData[$j]['Invoice_Title'];
							}

	                        if(!empty($invoiceData[$j]['Invoice_First_Name'])) {
								$contact[] = $invoiceData[$j]['Invoice_First_Name'];
							}

	                        if(!empty($invoiceData[$j]['Invoice_Last_Name'])) {
								$contact[] = $invoiceData[$j]['Invoice_Last_Name'];
							}

	                        if(!empty($invoiceData[$j]['Invoice_Organisation'])) {
								$contact[] = sprintf('(%s)', $invoiceData[$j]['Invoice_Organisation']);
							}

	                        $item = new SageTemplateItemContainer();
							$item->item->id = 'LBUK'.$invoiceData[$j]['Invoice_ID'];
							$item->item->sku = 'LBUK'.$invoiceData[$j]['Invoice_ID'];
							$item->item->name = htmlspecialchars_decode(implode(' ', $contact));
							$item->item->description = htmlspecialchars_decode(sprintf('Invoice %s for %s', $invoiceData[$j]['Invoice_ID'], implode(' ', $contact)));
							$item->item->quantity = 1;
							$item->item->price = $invoiceData[$j]['Invoice_Net'] - $invoiceData[$j]['Invoice_Discount'];
	                        $item->item->discountAmount = 0;
							$item->item->discountPercentage = 0;
							$item->item->reference = $invoiceData[$j]['Invoice_ID'];
							$item->item->taxRate = $invoiceData[$j]['Tax_Rate'];
							$item->item->totalNet = $item->item->price * $item->item->quantity;
							$item->item->totalTax = $item->item->totalNet * ($item->item->taxRate / 100);
							$item->item->nominalCode = $GLOBALS['SAGE_DEFAULT_NOMINAL_CODE_LBUK'];
							$item->item->department = $GLOBALS['SAGE_DEPARTMENT_INDEX_LBUK'];
							$item->item->type = 'Stock';
							$item->item->taxCode = $taxCode;

							$template->invoice->addItem($item);
							
							$print[$template->invoice->id][] = $invoiceData[$j]['Invoice_ID'];
							
							$log->amount += $item->item->totalNet + $item->item->totalTax;
						}
						
						if($shippingTotal > 0) {
							$template->invoice->carriage->quantity = 1;
							$template->invoice->carriage->price = $shippingTotal;
							$template->invoice->carriage->taxRate = $taxRate;
							$template->invoice->carriage->totalNet = $template->invoice->carriage->price * $template->invoice->carriage->quantity;
							$template->invoice->carriage->totalTax = $template->invoice->carriage->totalNet * ($template->invoice->carriage->taxRate / 100);
							$template->invoice->carriage->nominalCode = $GLOBALS['SAGE_DEFAULT_NOMINAL_CODE_CARRIAGE_LBUK'];
							$template->invoice->carriage->department = $GLOBALS['SAGE_DEPARTMENT_INDEX_LBUK'];
							$template->invoice->carriage->type = 'Service';
							$template->invoice->carriage->taxCode = $taxCode;
							
							$log->amount += $template->invoice->carriage->totalNet + $template->invoice->carriage->totalTax;
						}
						
						$sageTemplate->addInvoice($template);
						
						$logs[] = $log;
					}
				}

                foreach($invoices['Credits'] as $date=>$invoiceDate) {
					foreach($invoiceDate as $taxRate=>$invoiceData) {
						$log = new IntegrationSageLog();
	                    $log->type = 'Credit';
	                        
	                    $taxCode = $defaultTaxCode;

						$data = new DataQuery(sprintf("SELECT Integration_Reference FROM tax_code WHERE Rate=%f LIMIT 0, 1", mysql_real_escape_string($taxRate)));
						if($data->TotalRows > 0) {
							$taxCode = $data->Row['Integration_Reference'];
						}
						$data->Disconnect();

	                    $template = new SageTemplateInvoiceContainer();
						$template->invoice->id = sprintf('batchcredit_lbuk-%s-%s', str_replace('-', '', $date), $taxRate);
						$template->invoice->accountReference = $form->GetValue('account');
						$template->invoice->currency = 'GBP';
						$template->invoice->invoiceAddress->company = 'Light Bulbs UK Daily Sales Gateway';
						$template->invoice->invoiceDate = date('c', strtotime($date . ' 00:00:00'));
						$template->invoice->type = 'ProductCredit';
						$template->invoice->nominalCode = $GLOBALS['SAGE_DEFAULT_NOMINAL_CODE_LBUK'];
						$template->invoice->taxCode = $taxCode;
						$template->invoice->department = $GLOBALS['SAGE_DEPARTMENT_INDEX_LBUK'];
						$template->invoice->paymentReference = 'Credit/Debit Card';
						$template->invoice->paymentAmount = 0;
						$template->invoice->bankAccount = $GLOBALS['SAGE_BANKACCOUNT'];
						$template->invoice->paymentType = 'SalesCredit';
						$template->invoice->postedDate = date('c');
						
						$log->accountReference = $template->invoice->accountReference;
						$log->contactName = $template->invoice->invoiceAddress->company;

						$print[$template->invoice->id] = array();
						
						$shippingTotal = 0;	
						
						for($j=0; $j<count($invoiceData); $j++) {
							$shippingTotal += $invoiceData[$j]['TotalShipping'];
							
							new DataQuery(sprintf("UPDATE credit_note SET Integration_Reference='%s' WHERE Credit_Note_ID=%d", mysql_real_escape_string($template->invoice->id), $invoiceData[$j]['Credit_Note_ID']), $connection);
							
							$contact = array();

							if(!empty($invoiceData[$j]['Billing_Title'])) {
								$contact[] = $invoiceData[$j]['Billing_Title'];
							}

	                        if(!empty($invoiceData[$j]['Billing_First_Name'])) {
								$contact[] = $invoiceData[$j]['Billing_First_Name'];
							}

	                        if(!empty($invoiceData[$j]['Billing_Last_Name'])) {
								$contact[] = $invoiceData[$j]['Billing_Last_Name'];
							}

	                        if(!empty($invoiceData[$j]['Billing_Organisation_Name'])) {
								$contact[] = sprintf('(%s)', $invoiceData[$j]['Billing_Organisation_Name']);
							}
							
	                        $item = new SageTemplateItemContainer();
							$item->item->id = 'LBUK'.$invoiceData[$j]['Credit_Note_ID'];
							$item->item->sku = 'LBUK'.$invoiceData[$j]['Credit_Note_ID'];
							$item->item->name = htmlspecialchars_decode(implode(' ', $contact));
							$item->item->description = htmlspecialchars_decode(sprintf('Credit %s for %s', $invoiceData[$j]['Credit_Note_ID'], implode(' ', $contact)));
							$item->item->quantity = 1;
							$item->item->price = $invoiceData[$j]['TotalNet'] - $invoiceData[$j]['TotalShipping'];
	                        $item->item->discountAmount = 0;
							$item->item->discountPercentage = 0;
							$item->item->reference = $invoiceData[$j]['Credit_Note_ID'];
							$item->item->taxRate = $invoiceData[$j]['Tax_Rate'];
							$item->item->totalNet = $item->item->price * $item->item->quantity;
							$item->item->totalTax = $item->item->totalNet * ($item->item->taxRate / 100);
							$item->item->nominalCode = $GLOBALS['SAGE_DEFAULT_NOMINAL_CODE_LBUK'];
							$item->item->department = $GLOBALS['SAGE_DEPARTMENT_INDEX_LBUK'];
							$item->item->type = 'Stock';
							$item->item->taxCode = $taxCode;

							$template->invoice->addItem($item);
							
							$print[$template->invoice->id][] = $invoiceData[$j]['Credit_Note_ID'];
							
							$log->amount += ($item->item->totalNet + $item->item->totalTax) * -1;
						}
						
						if($shippingTotal > 0) {
							$template->invoice->carriage->quantity = 1;
							$template->invoice->carriage->price = $shippingTotal;
							$template->invoice->carriage->taxRate = $taxRate;
							$template->invoice->carriage->totalNet = $template->invoice->carriage->price * $template->invoice->carriage->quantity;
							$template->invoice->carriage->totalTax = $template->invoice->carriage->totalNet * ($template->invoice->carriage->taxRate / 100);
							$template->invoice->carriage->nominalCode = $GLOBALS['SAGE_DEFAULT_NOMINAL_CODE_CARRIAGE_LBUK'];
							$template->invoice->carriage->department = $GLOBALS['SAGE_DEPARTMENT_INDEX_LBUK'];
							$template->invoice->carriage->type = 'Service';
							$template->invoice->carriage->taxCode = $taxCode;
							
							$log->amount += ($template->invoice->carriage->totalNet + $template->invoice->carriage->totalTax) * -1;
						}

						$sageTemplate->addInvoice($template);
						
						$logs[] = $log;
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
					
					redirectTo(sprintf('?action=complete&documents=%s', $md5));
				}

				redirectTo(sprintf('?action=view'));
			}
		}
	}

	$dateString = '';

	foreach($scriptDates as $date=>$dateItems) {
		$dateString .= 'dateArr = new Array();';

		foreach($dateItems as $dateItem) {
			$dateString .= sprintf('dateArr.push(\'%s\');', $dateItem);
		}

		$dateString .= sprintf('dates.push(\'%s\', dateArr);', $date);
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var dates = new Array();
		var dateArr = null;

		%s

		var toggleDate = function(obj, date) {
			var e = null;

			for(var i=0; i<dates.length; i=i+2) {
				if(dates[i] == date) {
					for(var j=0; j<dates[i+1].length; j++) {
						e = document.getElementById(dates[i+1][j]);

						if(e) {
							if(obj.checked) {
								e.checked = true;
							} else {
								e.checked = false;
							}
						}
					}
				}
			}
		}
		</script>', $dateString);

	$page = new Page('Sage Export Batch (LBUK) (Pre VAT Rise)', 'Integrate with Sage.');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

    echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	if(count($invoices) > 0) {
		$window = new StandardWindow('Available Dates');
		$webForm = new StandardForm();

		echo $window->Open();
		echo $window->AddHeader('Select items for the following dates.');
		echo $window->OpenContent();
		echo $webForm->Open();

        $dates = '';
		$boxes = '';

		foreach($invoices as $date=>$invoiceData) {
			echo $webForm->AddRow($date, $form->GetHTML(sprintf('select_date_%s', $date)));
		}

		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();

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
		</tr>
		<tr>
			<td class="label">Batch Invoices</td>
			<td class="input" nowrap="nowrap"><?php echo $form->GetHTML('account'); ?></td>
		</tr>

		<?php
		foreach($invoices as $date=>$invoiceDate) {
			foreach($invoiceDate as $taxRate=>$invoiceData) {
				?>

		        <tr>
					<td class="label">&nbsp;</td>
					<td class="input" nowrap="nowrap"><strong><u><?php echo $date; ?></u> (<?php echo $taxRate; ?>% VAT)</strong></td>
				</tr>
				<tr>
					<td class="label">&nbsp;</td>
					<td class="input" style="background-color: #fff; border: 1px solid #ccc;">

						<?php
						$entities = array();

						for($j=0; $j<count($invoiceData); $j++) {
							$item = explode('-', $invoiceData[$j]->invoice->id);

							if(count($item) > 1) {
								$type = strtolower($item[0]);

								if(!isset($entities[$type])) {
									$entities[$type] = array();
								}

								$entities[$type][] = $invoiceData[$j]->invoice;
							}
						}

						foreach($entities as $key=>$collection) {
							switch($key) {
								case 'orderinvoice':
									echo '<strong>Sales Invoices</strong><br /><br />';
									break;

								case 'ordercredit':
									echo '<strong>Order Credits</strong><br /><br />';
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
									<td width="20%"><strong>Contact</strong></td>
                                    <td width="4%" align="right"><strong>Tax</strong></td>
									<td width="8%" align="right"><strong>Total</strong></td>
								</tr>

								<?php
								$batchTotal = 0;

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
										<td><?php echo $collection[$j]->getParameter('parentNumber'); ?></td>
										<td><?php echo $collection[$j]->getParameter('entityNumber'); ?></td>
										<td><?php echo date('d/m/Y', strtotime($collection[$j]->invoiceDate)); ?></td>
										<td><?php echo implode(' ', $billingContact); ?></td>
										<td align="right"><?php echo $collection[$j]->getParameter('taxRate'); ?>%</td>
										<td align="right" style="color: #<?php echo $colour; ?>;"><?php echo $sign;?><?php echo number_format(round($total, 2), 2, '.', ','); ?></td>
									</tr>

									<?php
									$batchTotal += $total;
								}
								?>

		                        <tr>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td align="right" style="color: #<?php echo $colour; ?>;"><strong><?php echo $sign;?><?php echo number_format(round($batchTotal, 2), 2, '.', ','); ?></strong></td>
								</tr>
							</table>
							<br />

							<?php
						}
						?>

					</td>
				</tr>

				<?php
			}
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