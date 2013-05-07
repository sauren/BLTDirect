<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/RowSet.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CreditNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IntegrationSage.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IntegrationSageLog.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Invoice.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateCompany.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateInvoiceContainer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateItemContainer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateProductContainer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TaxCalculator.php');

ini_set("display_errors", true);
error_reporting(E_ALL);

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
	start();
	exit;
}

function locked() {
	$page = new Page(sprintf('<a href="%s">Sage Export Batch</a> &gt; Integration Locked', $_SERVER['PHP_SELF']), 'Sage integration locked.');
	$page->Display('header');

	echo '<p>There are outstanding Sage export related data feeds awaiting execution.<br />Allowing more than one consecutive integration session may cause duplicate data to appear within Sage and compromise the referential integrity of any unconfirmed integration associations.</p>';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function errorPage($message) {
	$page = new Page(sprintf('<a href="%s">Sage Export Batch</a> &gt; Error', $_SERVER['PHP_SELF']), '');
	$page->Display('header');

	echo $message;

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function complete() {
	$page = new Page(sprintf('<a href="%s">Sage Export Batch</a> &gt; Integration Complete', $_SERVER['PHP_SELF']), 'Sage integration complete.');
	$page->AddToHead(sprintf('<script language="javascript" type="text/javascript">%s</script>', isset($_REQUEST['documents']) ? sprintf('popUrl(\'sage_export_batch_print.php?documents=%s\', 800, 600);', $_REQUEST['documents']) : ''));
	$page->Display('header');

	echo '<p>Sage integration was completed succesfully.<br />Please allow for the next integration iteration before data is available within Sage.</p>';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function getCardTypes() {
	return new RowSet(<<<SQL
SELECT
	o.Card_Type
FROM invoice AS i
INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=i.Payment_Method_ID
INNER JOIN orders AS o ON o.Order_ID=i.Order_ID
WHERE
	i.Invoice_Total>0
	AND i.Is_Paid='Y'
	AND i.Created_On>='2010-05-01 00:00:00'
	AND i.Tax_Rate=20.0
	AND i.Integration_ID=''
	AND pm.Reference NOT LIKE 'google'
	AND i.Invoice_Country LIKE 'United Kingdom'
GROUP BY o.Card_Type
SQL
	);
}


function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	if (DataQuery::FetchOne(sprintf("SELECT COUNT(*) AS Count FROM integration_sage WHERE (Type LIKE 'Export' OR Type LIKE 'Confirmation') AND Is_Synchronised='N'")) > 0)
	{
		redirect(sprintf("Location: %s?action=locked", $_SERVER['PHP_SELF']));
	}

	$page = new Page('Sage Batch Export', '');
	$page->AddToHead('<script language="javascript" src="/js/jquery.js" type="text/javascript"></script>');

	$year = cDatetime(getDatetime(), 'y');
	$form = new Form($_SERVER['PHP_SELF'], 'get');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

	$form->AddField('preset', 'Preset', 'select', 'none', 'alpha_numeric', 0, 32);
	$form->AddOption('preset', 'none', 'None (Custom)');
	$form->AddOption('preset', 'ecommerce', 'E-Commerce');
	$form->AddOption('preset', 'moto', 'Moto');
	$form->AddOption('preset', 'amex', 'Amex');

	$form->AddField('commerceType_website', 'E-Commerce', 'checkbox', 'N', 'boolean', 1, 1, false, 'class="ecommerce amex"');
	$form->AddField('commerceType_moto', 'Moto', 'checkbox', 'N', 'boolean', 1, 1, false, 'class="moto amex"');

	$cardTypes = getCardTypes();

	foreach ($cardTypes as $type) {
		$cls = $type->Card_Type == "American Express" ? "amex" : "ecommerce moto";
		$form->AddField(sprintf('cardType_%s', $type->Card_Type), $type->Card_Type, 'checkbox', 'N', 'boolean', 1, 1, false, "class=\"{$cls}\"");
	}


	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if(isset($_REQUEST['continue']) && strtolower($_REQUEST['continue']) == "continue"){
			exportToSage();
		}
		else {
			$selectedCards = array();
			$selectedTransactions = array();

			foreach ($cardTypes as $type) {
				$cardStr = sprintf('cardType_%s', str_replace(" ", "_", $type->Card_Type));

				if (isset($_REQUEST[$cardStr]) && $_REQUEST[$cardStr] == "Y") {
					$selectedCards[] = $type->Card_Type;
				}
			}

			$selectedTransactions["web"] = ($form->GetValue('commerceType_website') == "Y");
			$selectedTransactions["moto"] = ($form->GetValue('commerceType_moto') == "Y");

			integrate($selectedCards, $selectedTransactions);
			exit;
		}
	}

	$page->Display('header');
	
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select a preset for common exports.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('preset'), $form->GetHTML('preset'));
?>
	<script>
		jQuery(function($) {
			$("#preset").change(function() {
				var val = $(this).val();
				$(":checkbox").attr("checked", false);
				$("input." + val).attr("checked", true);
			});

			$(":checkbox").change(function() {
				$("#preset").val("none");
			});
		});
	</script>
<?php

	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Or configure exactly what you want to export.');
	echo $window->OpenContent();
	echo '<div style="float: left; width: 50%;">';
	echo $webForm->Open();

	foreach ($cardTypes as $type) {
		echo $webForm->AddRow($form->GetLabel(sprintf('cardType_%s', $type->Card_Type)), $form->GetHTML(sprintf('cardType_%s', $type->Card_Type)));
	}

	echo $webForm->Close();
	echo '</div>';
	echo '<div style="float: left; width: 50%;">';
	echo $webForm->Open();

	echo $webForm->AddRow($form->GetLabel("commerceType_website"), $form->GetHTML("commerceType_website"));
	echo $webForm->AddRow($form->GetLabel("commerceType_moto"), $form->GetHTML("commerceType_moto"));

	echo $webForm->Close();
	echo '</div>';
	echo $window->CloseContent();
	echo $window->AddHeader('Click below to submit your request');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('&nbsp;', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}


function exportToSage() {
	$print = array();

	if (!isset($_REQUEST["account"]) || strlen(trim($_REQUEST["account"])) < 1) {
		errorPage('<p>You have not provided a sage integration reference. Please go back and add one.</p>');
		exit;
	}

	$paymentMethods = DataQuery::FetchPairs(sprintf("SELECT Payment_Method_ID, Method FROM payment_method WHERE Reference NOT LIKE 'google'"));

	$sageTemplate = new SageTemplateCompany();

	$data = new DataQuery(sprintf("SELECT Integration_Reference, Rate FROM tax_code WHERE Is_Default='Y' LIMIT 0, 1"));
	$defaultTaxCode = $data->Row['Integration_Reference'];
	$data->Disconnect();

	$invoices = array('Invoices' => array(), 'Credits' => array());

	$data = new DataQuery(sprintf("SELECT DATE(i.Created_On) AS Date_Created, IF(c2.Contact_ID IS NOT NULL, c2.Contact_ID, c.Contact_ID) AS Contact_ID, i.Invoice_ID, i.Invoice_Net, i.Invoice_Shipping, i.Invoice_Discount, i.Invoice_Tax, i.Invoice_Total, ROUND((ROUND((i.Invoice_Tax / (i.Invoice_Net + i.Invoice_Shipping - i.Invoice_Discount)) * 100 * 2) / 2), 1) AS Tax_Rate, i.Is_Paid, i.Invoice_Title, i.Invoice_First_Name, i.Invoice_Last_Name, i.Invoice_Organisation, i.Invoice_Address_1, i.Invoice_Address_2, i.Invoice_Address_3, i.Invoice_City, ic.Country_ID AS Invoice_Country_ID, ir.Region_ID AS Invoice_Region_ID, i.Invoice_Zip, i.Created_On, ic.ISO_Code_2 AS Invoice_Country_Code, ir.Region_Name AS Invoice_Region, o.Order_ID, o.Custom_Order_No AS Custom_Reference, o.Shipping_Title, o.Shipping_First_Name, o.Shipping_Last_Name, o.Shipping_Organisation_Name, o.Shipping_Address_1, o.Shipping_Address_2, o.Shipping_Address_3, o.Shipping_City, o.Shipping_Zip, irs.Region_Name AS Shipping_Region, ics.ISO_Code_2 AS Shipping_Country_Code, o.TaxExemptCode AS Tax_Exemption_Code, pm.Payment_Method_ID, pm.Method AS Payment_Method FROM invoice AS i INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=i.Payment_Method_ID LEFT JOIN countries AS ic ON ic.Country LIKE i.Invoice_Country LEFT JOIN regions AS ir ON ir.Region_Name LIKE i.Invoice_Region AND ir.Country_ID=ic.Country_ID INNER JOIN customer AS cu ON cu.Customer_ID=i.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID INNER JOIN orders AS o ON o.Order_ID=i.Order_ID LEFT JOIN countries AS ics ON ics.Country_ID=o.Shipping_Country_ID LEFT JOIN regions AS irs ON irs.Region_ID=o.Shipping_Region_ID AND irs.Country_ID=ics.Country_ID WHERE i.Invoice_Total>0 AND i.Is_Paid='Y' AND i.Created_On>='%s' AND i.Tax_Rate=20.0 AND i.Integration_ID='' AND pm.Reference NOT LIKE 'google' AND i.Invoice_Country LIKE 'United Kingdom' GROUP BY i.Invoice_ID HAVING Tax_Rate>0 ORDER BY Date_Created ASC, Tax_Rate ASC", mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE_BATCH'])));
	while($data->Row) {
		if(isset($_REQUEST[sprintf('invoice_submit_orderinvoice-%s', $data->Row['Invoice_ID'])])) {
			$taxRate = number_format($data->Row['Tax_Rate'], 2, '.', '');

			if(!isset($invoices['Invoices'][$data->Row['Date_Created']])) {
				$invoices['Invoices'][$data->Row['Date_Created']] = array();
			}

			if(!isset($invoices['Invoices'][$data->Row['Date_Created']][$taxRate])) {
				$invoices['Invoices'][$data->Row['Date_Created']][$taxRate] = array();
			}

			if(!isset($invoices['Invoices'][$data->Row['Date_Created']][$taxRate][$data->Row['Payment_Method_ID']])) {
				$invoices['Invoices'][$data->Row['Date_Created']][$taxRate][$data->Row['Payment_Method_ID']] = array();
			}

			$invoices['Invoices'][$data->Row['Date_Created']][$taxRate][$data->Row['Payment_Method_ID']][] = $data->Row;
		}

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT DATE(cn.Credited_On) AS Date_Created, IF(c2.Contact_ID IS NOT NULL, c2.Contact_ID, c.Contact_ID) AS Contact_ID, cn.Credit_Note_ID, cn.TotalNet, cn.TotalShipping, cn.TotalTax, cn.Total, cn.Tax_Rate, o.Billing_Title, o.Billing_First_Name, o.Billing_Last_Name, o.Billing_Organisation_Name, o.Billing_Address_1, o.Billing_Address_2, o.Billing_Address_3, o.Billing_City, o.Billing_Country_ID, o.Billing_Region_ID, o.Billing_Zip, cn.Credited_On, oc.ISO_Code_2 AS Billing_Country_Code, orr.Region_Name AS Billing_Region, o.Order_ID, o.Custom_Order_No AS Custom_Reference, o.Shipping_Title, o.Shipping_First_Name, o.Shipping_Last_Name, o.Shipping_Organisation_Name, o.Shipping_Address_1, o.Shipping_Address_2, o.Shipping_Address_3, o.Shipping_City, o.Shipping_Zip, ors.Region_Name AS Shipping_Region, ocs.ISO_Code_2 AS Shipping_Country_Code, o.TaxExemptCode AS Tax_Exemption_Code, pm.Payment_Method_ID, pm.Method AS Payment_Method FROM credit_note AS cn INNER JOIN orders AS o ON o.Order_ID=cn.Order_ID INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID LEFT JOIN countries AS oc ON oc.Country_ID=o.Billing_Country_ID LEFT JOIN regions AS orr ON orr.Region_ID=o.Billing_Region_ID LEFT JOIN countries AS ocs ON ocs.Country_ID=o.Shipping_Country_ID LEFT JOIN regions AS ors ON ors.Region_ID=o.Shipping_Region_ID INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID WHERE cn.Total>0 AND cn.Credit_Type LIKE 'Card Refund' AND cn.Credited_On>='%s' AND cn.Tax_Rate=20.0 AND cn.Integration_ID='' AND cn.Tax_Rate>0 AND pm.Reference NOT LIKE 'google' AND o.Billing_Country_ID = 222 GROUP BY cn.Credit_Note_ID ORDER BY Date_Created ASC, Tax_Rate ASC", mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE_BATCH'])));
	while($data->Row) {
		if(isset($_REQUEST[sprintf('invoice_submit_ordercredit-%s', $data->Row['Credit_Note_ID'])])) {
			$taxRate = number_format($data->Row['Tax_Rate'], 2, '.', '');

			if(!isset($invoices['Credits'][$data->Row['Date_Created']])) {
				$invoices['Credits'][$data->Row['Date_Created']] = array();
			}

			if(!isset($invoices['Credits'][$data->Row['Date_Created']][$taxRate])) {
				$invoices['Credits'][$data->Row['Date_Created']][$taxRate] = array();
			}

			if(!isset($invoices['Credits'][$data->Row['Date_Created']][$taxRate][$data->Row['Payment_Method_ID']])) {
				$invoices['Credits'][$data->Row['Date_Created']][$taxRate][$data->Row['Payment_Method_ID']] = array();
			}

			$invoices['Credits'][$data->Row['Date_Created']][$taxRate][$data->Row['Payment_Method_ID']][] = $data->Row;
		}

		$data->Next();
	}
	$data->Disconnect();

	$logs = array();

	foreach($invoices['Invoices'] as $date=>$invoiceDate) {
		foreach($invoiceDate as $taxRate=>$invoiceMethod) {
			foreach($invoiceMethod as $paymentMethodId=>$invoiceData) {
				$log = new IntegrationSageLog();
				$log->type = 'Invoice';
				
				$taxCode = $defaultTaxCode;

				$data = new DataQuery(sprintf("SELECT Integration_Reference FROM tax_code WHERE Rate=%f LIMIT 0, 1", mysql_real_escape_string($taxRate)));
				if($data->TotalRows > 0) {
					$taxCode = $data->Row['Integration_Reference'];
				}
				$data->Disconnect();

				$template = new SageTemplateInvoiceContainer();
				$template->invoice->id = sprintf('batchinvoice-%s-%d-%s', str_replace('-', '', $date), $paymentMethodId, $taxRate);
				$template->invoice->accountReference = $_REQUEST['account'];
				$template->invoice->currency = 'GBP';
				$template->invoice->invoiceAddress->company = sprintf('BLT Daily Sales Gateway (%s)', $paymentMethods[$paymentMethodId]);
				$template->invoice->invoiceDate = date('c', strtotime($date . ' 00:00:00'));
				$template->invoice->type = 'ProductInvoice';
				$template->invoice->nominalCode = $GLOBALS['SAGE_DEFAULT_NOMINAL_CODE'];
				$template->invoice->taxCode = $taxCode;
				$template->invoice->department = $GLOBALS['SAGE_DEPARTMENT_INDEX'];
				$template->invoice->paymentReference = $paymentMethods[$paymentMethodId];
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
					
					$invoice = new Invoice();

					if($invoice->Get($invoiceData[$j]['Invoice_ID'])) {
						$invoice->IntegrationReference = $template->invoice->id;
						$invoice->Update();
					}

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
					$item->item->id = $invoiceData[$j]['Invoice_ID'];
					$item->item->sku = $invoiceData[$j]['Invoice_ID'];
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
					$item->item->nominalCode = $GLOBALS['SAGE_DEFAULT_NOMINAL_CODE'];
					$item->item->department = $GLOBALS['SAGE_DEPARTMENT_INDEX'];
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
					$template->invoice->carriage->nominalCode = $GLOBALS['SAGE_DEFAULT_NOMINAL_CODE_CARRIAGE'];
					$template->invoice->carriage->department = $GLOBALS['SAGE_DEPARTMENT_INDEX'];
					$template->invoice->carriage->type = 'Service';
					$template->invoice->carriage->taxCode = $taxCode;
					
					$log->amount += $template->invoice->carriage->totalNet + $template->invoice->carriage->totalTax;
				}
				
				$sageTemplate->addInvoice($template);
				
				$logs[] = $log;
			}
		}
	}

	foreach($invoices['Credits'] as $date=>$invoiceDate) {
		foreach($invoiceDate as $taxRate=>$invoiceMethod) {
			foreach($invoiceMethod as $paymentMethodId=>$invoiceData) {
				$log = new IntegrationSageLog();
				$log->type = 'Credit';
				
				$taxCode = $defaultTaxCode;

				$data = new DataQuery(sprintf("SELECT Integration_Reference FROM tax_code WHERE Rate=%f LIMIT 0, 1", mysql_real_escape_string($taxRate)));
				if($data->TotalRows > 0) {
					$taxCode = $data->Row['Integration_Reference'];
				}
				$data->Disconnect();

				$template = new SageTemplateInvoiceContainer();
				$template->invoice->id = sprintf('batchcredit-%s-%d-%s', str_replace('-', '', $date), $paymentMethodId, $taxRate);
				$template->invoice->accountReference = $_REQUEST['account'];
				$template->invoice->currency = 'GBP';
				$template->invoice->invoiceAddress->company = sprintf('BLT Daily Sales Gateway (%s)', $paymentMethods[$paymentMethodId]);
				$template->invoice->invoiceDate = date('c', strtotime($date . ' 00:00:00'));
				$template->invoice->type = 'ProductCredit';
				$template->invoice->nominalCode = $GLOBALS['SAGE_DEFAULT_NOMINAL_CODE'];
				$template->invoice->taxCode = $taxCode;
				$template->invoice->department = $GLOBALS['SAGE_DEPARTMENT_INDEX'];
				$template->invoice->paymentReference = $paymentMethods[$paymentMethodId];
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
					
					$credit = new CreditNote();

					if($credit->Get($invoiceData[$j]['Credit_Note_ID'])) {
						$credit->IntegrationReference = $template->invoice->id;
						$credit->Update();
					}

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
					$item->item->id = $invoiceData[$j]['Credit_Note_ID'];
					$item->item->sku = $invoiceData[$j]['Credit_Note_ID'];
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
					$item->item->nominalCode = $GLOBALS['SAGE_DEFAULT_NOMINAL_CODE'];
					$item->item->department = $GLOBALS['SAGE_DEPARTMENT_INDEX'];
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
					$template->invoice->carriage->nominalCode = $GLOBALS['SAGE_DEFAULT_NOMINAL_CODE_CARRIAGE'];
					$template->invoice->carriage->department = $GLOBALS['SAGE_DEPARTMENT_INDEX'];
					$template->invoice->carriage->type = 'Service';
					$template->invoice->carriage->taxCode = $taxCode;
					
					$log->amount += ($template->invoice->carriage->totalNet + $template->invoice->carriage->totalTax) * -1;
				}

				$sageTemplate->addInvoice($template);
				
				$logs[] = $log;
			}
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

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function buildInvoiceTemplate(&$templates, $invoiceRow, $type="invoice") {
	$taxRate = number_format($invoiceRow->Tax_Rate, 2, '.', '');
	
	$template = new SageTemplateInvoiceContainer();
	$template->invoice->id = sprintf('order%s-%s', $type, $invoiceRow->itemId);
	$template->invoice->customerId = $invoiceRow->Contact_ID;
	$template->invoice->invoiceDate = date('c', strtotime($invoiceRow->itemDate));
	$template->invoice->invoiceAddress->title = $invoiceRow->Billing_Title;
	$template->invoice->invoiceAddress->forename = $invoiceRow->Billing_First_Name;
	$template->invoice->invoiceAddress->surname = $invoiceRow->Billing_Last_Name;
	$template->invoice->carriage->quantity = 1;
	$template->invoice->carriage->price = $invoiceRow->TotalShipping;
	$template->invoice->carriage->taxRate = $taxRate;
	$template->invoice->carriage->totalNet = $template->invoice->carriage->price * $template->invoice->carriage->quantity;
	$template->invoice->carriage->totalTax = $template->invoice->carriage->totalNet * ($taxRate / 100);
	$template->invoice->setParameter('entityNumber', sprintf('%s %s', ucwords($type), $invoiceRow->itemId));
	$template->invoice->setParameter('parentNumber', sprintf('Order %s', $invoiceRow->Order_ID));
	$template->invoice->setParameter('parentId', $invoiceRow->Order_ID);
	$template->invoice->setParameter('paymentMethod', $invoiceRow->Payment_Method);
	$template->invoice->setParameter(sprintf('%sId', $type), $invoiceRow->itemId);
	$template->invoice->setParameter('taxRate', $taxRate);

	$templates[$invoiceRow->itemId] = $template;
}


function outputSectionTable($templates, $invoices, $linkString, $key) {
?>
	<table class="orderDetails">
		<tr>
			<td width="1%">&nbsp;</td>
			<td width="17%"><strong>Parent</strong></td>
			<td width="17%"><strong>Entity</strong></td>
			<td width="13%"><strong>Date</strong></td>
			<td width="20%"><strong>Payment</strong></td>
			<td width="20%"><strong>Billing Contact</strong></td>
			<td width="4%" align="right"><strong>Tax</strong></td>
			<td width="8%" align="right"><strong>Total</strong></td>
		</tr>

		<?php
		$batchTotal = 0;

		foreach($invoices as $invoice) {

			if (!isset($templates[$invoice->itemId])) {
				trigger_error("Missing template for item ID #{$invoice->itemId}");
			}

			$template = $templates[$invoice->itemId]->invoice;

			$billingContact = array();

			if(!empty($template->invoiceAddress->title)) {
				$billingContact[] = $template->invoiceAddress->title;
			}

			if(!empty($template->invoiceAddress->forename)) {
				$billingContact[] = $template->invoiceAddress->forename;
			}

			if(!empty($template->invoiceAddress->surname)) {
				$billingContact[] = $template->invoiceAddress->surname;
			}

			switch($key) {
				case 'orderinvoice':
					$total = $invoice->Total;
					$colour = '00cc00';
					$sign = '+';
					break;
					
				case 'ordercredit':
					$total = $invoice->Total;
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
				<td><input type="checkbox" name="<?php echo sprintf('invoice_submit_%s', $template->id); ?>" data-date="<?php echo $invoice->Date_Created ?>" /></td>
				<td><?php echo sprintf($linkString, $template->getParameter('parentId'), $template->getParameter('parentNumber')); ?></td>
				<td><?php echo $template->getParameter('entityNumber'); ?></td>
				<td><?php echo date('d/m/Y', strtotime($template->invoiceDate)); ?></td>
				<td><?php echo $template->getParameter('paymentMethod'); ?></td>
				<td><?php echo implode(' ', $billingContact); ?></td>
				<td align="right"><?php echo $template->getParameter('taxRate'); ?>%</td>
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
			<td>&nbsp;</td>
			<td align="right" style="color: #<?php echo $colour; ?>;"><strong><?php echo $sign;?><?php echo number_format(round($batchTotal, 2), 2, '.', ','); ?></strong></td>
		</tr>
	</table>
	<?php

	return $batchTotal;
}


function integrate($selectedCards, $selectedTransactions) {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'step1', 'alpha_numeric', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('account', 'Account Reference', 'text', 'BLTSALES', 'anything', 1, 8, false, 'style="font-family: arial, sans-serif;"');

	$paymentMethods = DataQuery::FetchPairs(sprintf("SELECT Payment_Method_ID, Method FROM payment_method WHERE Reference NOT LIKE 'google'"));

	$totals = array('Invoices' => array(), 'Credits' => array());
	$templates = array();
	$scriptDates = array();

	if ($selectedTransactions["web"] && $selectedTransactions["moto"]) {
		$prefixWhere = "";
	}
	else if ($selectedTransactions["web"]) {
		$prefixWhere = "AND o.Order_Prefix != 'T'";
	}
	else {
		$prefixWhere = "AND o.Order_Prefix = 'T'";
	}

	$cardsWhere = array("''");
	foreach ($selectedCards as $selectedCard) {
		$cardsWhere[] = "'" . mysql_real_escape_string($selectedCard) . "'";
	}
	$cardsWhere = "AND o.Card_Type IN (" . join(", ", $cardsWhere) . ")";


	//
	// Invoice data
	// 
	$invoices = new RowSet(sprintf(<<<SQL
SELECT
	DATE(i.Created_On) AS Date_Created,
	IF(c2.Contact_ID IS NOT NULL, c2.Contact_ID, c.Contact_ID) AS Contact_ID,
	i.Invoice_ID AS itemId,
	i.Invoice_Shipping AS TotalShipping,
	i.Invoice_Total AS Total,
	ROUND((ROUND((i.Invoice_Tax / (i.Invoice_Net + i.Invoice_Shipping - i.Invoice_Discount)) * 100 * 2) / 2), 1) AS Tax_Rate,
	i.Invoice_Title AS Billing_Title,
	i.Invoice_First_Name AS Billing_First_Name,
	i.Invoice_Last_Name AS Billing_Last_Name,
	i.Invoice_Organisation,
	i.Invoice_Address_1,
	i.Invoice_Address_2,
	i.Invoice_Address_3,
	i.Invoice_City,
	ic.Country_ID AS Invoice_Country_ID,
	ir.Region_ID AS Invoice_Region_ID,
	i.Invoice_Zip,
	i.Created_On as itemDate,
	ic.ISO_Code_2 AS Invoice_Country_Code,
	ir.Region_Name AS Invoice_Region,
	o.Order_ID,
	o.Custom_Order_No AS Custom_Reference,
	o.Shipping_Title,
	o.Shipping_First_Name,
	o.Shipping_Last_Name,
	o.Shipping_Organisation_Name,
	o.Shipping_Address_1,
	o.Shipping_Address_2,
	o.Shipping_Address_3,
	o.Shipping_City,
	o.Shipping_Zip,
	irs.Region_Name AS Shipping_Region,
	ics.ISO_Code_2 AS Shipping_Country_Code,
	o.TaxExemptCode AS Tax_Exemption_Code,
	pm.Payment_Method_ID,
	pm.Method AS Payment_Method,
	IF(o.Card_Type = 'American Express', 'American Express', 'Standard') as Card_Type,
	IF(o.Order_Prefix = 'T', 'Moto', 'e-Commerce') as Prefix
FROM invoice AS i
INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=i.Payment_Method_ID
LEFT JOIN countries AS ic ON ic.Country LIKE i.Invoice_Country
LEFT JOIN regions AS ir ON ir.Region_Name LIKE i.Invoice_Region AND ir.Country_ID=ic.Country_ID
INNER JOIN customer AS cu ON cu.Customer_ID=i.Customer_ID
INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID
LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID
INNER JOIN orders AS o ON o.Order_ID=i.Order_ID
LEFT JOIN countries AS ics ON ics.Country_ID=o.Shipping_Country_ID
LEFT JOIN regions AS irs ON irs.Region_ID=o.Shipping_Region_ID AND irs.Country_ID=ics.Country_ID
WHERE
	i.Invoice_Total>0
	AND i.Is_Paid='Y'
	AND i.Created_On>='%s'
	AND i.Tax_Rate=20.0
	AND i.Integration_ID=''
	AND pm.Reference NOT LIKE 'google'
	AND i.Invoice_Country LIKE 'United Kingdom'
	%s
	%s
GROUP BY i.Invoice_ID
HAVING Tax_Rate>0
ORDER BY Date_Created ASC, Tax_Rate ASC
SQL
	, mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE_BATCH']), $prefixWhere, $cardsWhere));

	foreach ($invoices as $invoiceRow) {
		buildInvoiceTemplate($templates, $invoiceRow, "invoice");
	}


	//
	// Credit note data
	//
	$creditNotes = new RowSet(sprintf(<<<SQL
SELECT
	DATE(cn.Credited_On) AS Date_Created,
	IF(c2.Contact_ID IS NOT NULL, c2.Contact_ID, c.Contact_ID) AS Contact_ID,
	cn.Credit_Note_ID AS itemId,
	cn.TotalShipping,
	cn.Total,
	ROUND(cn.Tax_Rate, 1) AS Tax_Rate,
	o.Billing_Title,
	o.Billing_First_Name,
	o.Billing_Last_Name,
	o.Billing_Organisation_Name,
	o.Billing_Address_1,
	o.Billing_Address_2,
	o.Billing_Address_3,
	o.Billing_City,
	o.Billing_Country_ID,
	o.Billing_Region_ID,
	o.Billing_Zip,
	cn.Credited_On AS itemDate,
	oc.ISO_Code_2 AS Billing_Country_Code,
	orr.Region_Name AS Billing_Region,
	o.Order_ID,
	o.Custom_Order_No AS Custom_Reference,
	o.Shipping_Title,
	o.Shipping_First_Name,
	o.Shipping_Last_Name,
	o.Shipping_Organisation_Name,
	o.Shipping_Address_1,
	o.Shipping_Address_2,
	o.Shipping_Address_3,
	o.Shipping_City,
	o.Shipping_Zip,
	ors.Region_Name AS Shipping_Region,
	ocs.ISO_Code_2 AS Shipping_Country_Code,
	o.TaxExemptCode AS Tax_Exemption_Code,
	pm.Payment_Method_ID,
	pm.Method AS Payment_Method,
	IF(o.Card_Type = 'American Express', 'American Express', 'Standard') as Card_Type,
	IF(o.Order_Prefix = 'T', 'Moto', 'e-Commerce') as Prefix
FROM credit_note AS cn
INNER JOIN orders AS o ON o.Order_ID=cn.Order_ID
INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID
LEFT JOIN countries AS oc ON oc.Country_ID=o.Billing_Country_ID
LEFT JOIN regions AS orr ON orr.Region_ID=o.Billing_Region_ID
LEFT JOIN countries AS ocs ON ocs.Country_ID=o.Shipping_Country_ID
LEFT JOIN regions AS ors ON ors.Region_ID=o.Shipping_Region_ID
INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID
INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID
LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID
WHERE
	cn.Total>0
	AND cn.Credit_Type LIKE 'Card Refund'
	AND cn.Credited_On>='%s'
	AND cn.Tax_Rate=20.0
	AND cn.Integration_ID=''
	AND cn.Tax_Rate>0
	AND pm.Reference NOT LIKE 'google'
	AND o.Billing_Country_ID = 222
	%s
	%s
GROUP BY cn.Credit_Note_ID
ORDER BY Date_Created ASC, Tax_Rate ASC
SQL
	, mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE_BATCH']), $prefixWhere, $cardsWhere));

	foreach ($creditNotes as $invoiceRow) {
		buildInvoiceTemplate($templates, $invoiceRow, "credit");
	}

	$page = new Page('Sage Batch Export', '');
	$page->AddToHead('<script language="javascript" src="/js/jquery.js" type="text/javascript"></script>');
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

		foreach($invoices->by("Date_Created") as $date=>$_) {
			echo $webForm->AddRow(date("jS M Y", strtotime($date)), '<input type="checkbox" class="dateSelect" data-date="' . $date . '" />');
		}
		?>
<script>
	jQuery(function($) {
		$(".dateSelect").change(function() {
			var checked = $(this).is(':checked');

			$(":input[data-date=" + $(this).attr("data-date") + "]").prop("checked", checked);
		});
	});
</script>
		<?php

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

		$grandTotal = 0;

		foreach($invoices->byGroup("Prefix") as $prefixedInvoices) {
			$prefixTotal = 0;
		?>
		<tr>
			<td style="background-color: #c8c8c8; border: 1px solid #ccc;">
				<strong><?php echo $prefixedInvoices->firstValue("Prefix") ?></strong>
				<br /><br />
				<table style="width: 100%; border-collapse: collapse;">
		<?php
			foreach($prefixedInvoices->byGroup("Date_Created") as $datedInvoices) {
				foreach($datedInvoices->byGroup("Tax_Rate") as $outInvoices) {
							?>

							<tr>
								<td class="input" nowrap="nowrap"><strong><u><?php echo $outInvoices->firstValue("Date_Created"); ?>:</u> <?php echo $outInvoices->firstValue("Card_Type") ?> <?php echo $paymentMethods[$outInvoices->firstValue("Payment_Method_ID")]; ?> (<?php echo $outInvoices->firstValue("Tax_Rate"); ?>% VAT)</strong></td>
							</tr>
							<tr>
								<td class="input" style="background-color: #fff; border: 1px solid #ccc;">
									<?php
										echo '<strong>Sales Invoices</strong><br /><br />';
										$linkString = '<a href="order_details.php?orderid=%d" target="_blank">%s</a>';
										$batchTotal = outputSectionTable($templates, $outInvoices, $linkString, "orderinvoice");

										$prefixTotal += $batchTotal;
										$grandTotal += $batchTotal;


										$creditGroup = $creditNotes->byGroup("Prefix", $outInvoices->firstValue("Prefix"));
										$creditGroup = $creditGroup ? $creditGroup->byGroup("Date_Created", $outInvoices->firstValue("Date_Created")) : null;
										$creditGroup = $creditGroup ? $creditGroup->byGroup("Tax_Rate", $outInvoices->firstValue("Tax_Rate")) : null;

										if ($creditGroup) {
											echo '<strong>Order Credits</strong><br /><br />';
											$linkString = '<a href="order_details.php?orderid=%d" target="_blank">%s</a>';
											$batchTotal = outputSectionTable($templates, $creditGroup, $linkString, "ordercredit");

											$prefixTotal -= $batchTotal;
											$grandTotal -= $batchTotal;
										}
									?>

								</td>
							</tr>

							<?php
				}
			}
		?>
				</table>

				<div style="padding: 10px 6px 0 6px; font-weight: bold;"><?php echo $prefixedInvoices->firstValue("Prefix") ?> Total: <?php echo number_format(round($prefixTotal, 2), 2, '.', ',') ?></div>
			</td>
		</tr>
		<tr>
			<td>
				<br />
			</td>
		</tr>
		<?php
		}

		echo '<tr><td><strong>Grand Total &nbsp; ' . number_format(round($grandTotal, 2), 2, '.', ',') . '</strong></td></tr>';
		echo '<tr><td><strong>Sage Account Reference &nbsp; <input type="text" name="account" /></strong></td></tr>';
		echo '<tr><td><input type="submit" name="continue" value="continue" class="btn"></td></tr>';
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
	}

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
