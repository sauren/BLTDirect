<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/htmlMimeMail5.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Document.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');

if($action == 'email') {
	$session->Secure(2);
	email();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function email() {
	$customer = new Customer($_REQUEST['customer']);
	$customer->Contact->Get();

	$findReplace = new FindReplace();
	$findReplace->Add('/\[CUSTOMER\]/', sprintf("%s%s %s %s<br />%s", ($customer->Contact->Parent->ID > 0) ? sprintf('%s<br />', $customer->Contact->Parent->Organisation->Name) : '', $customer->Contact->Person->Title, $customer->Contact->Person->Name, $customer->Contact->Person->LastName, $customer->Contact->Person->Address->GetLongString()));

	$mail = new htmlMimeMail5();

	$document = new Document(14);

	$pdf = new AffiliatePDF();
	$pdf->AddPage();
	$pdf->WriteHTML(sprintf("&#012;%s", $findReplace->Execute($document->Body)));

	$fileName = $pdf->Output(sprintf('affiliate_leaflet_%d.pdf', $customer->ID), 'F', false, $GLOBALS['AFFILIATE_DOCUMENT_DIR_FS']);
	$filePath = sprintf("%s%s", $GLOBALS['AFFILIATE_DOCUMENT_DIR_FS'], $fileName);

	$mail->addAttachment(new fileAttachment($filePath));

	$document = new Document(13);

	$pdf = new AffiliatePDF();
	$pdf->AddPage();
	$pdf->WriteHTML(sprintf("&#012;%s", $findReplace->Execute($document->Body)));

	$fileName = $pdf->Output(sprintf('affiliate_website_%d.pdf', $customer->ID), 'F', false, $GLOBALS['AFFILIATE_DOCUMENT_DIR_FS']);
	$filePath = sprintf("%s%s", $GLOBALS['AFFILIATE_DOCUMENT_DIR_FS'], $fileName);

	$mail->addAttachment(new fileAttachment($filePath));

	$affiliateEmail = file(sprintf("%slib/templates/email/affiliate_documents.tpl", $GLOBALS["DIR_WS_ADMIN"]));
	$affiliateHtml = '';

	for($i=0; $i < count($affiliateEmail); $i++){
		$affiliateHtml .= $affiliateEmail[$i];
	}

	$findReplace = new FindReplace();
	$findReplace->Add('/\[BODY\]/', $affiliateHtml);
	$findReplace->Add('/\[NAME\]/', $customer->Contact->Person->GetFullName());

	$templateEmail = file(sprintf("%slib/templates/email/template_standard.tpl", $GLOBALS["DIR_WS_ADMIN"]));
	$templateHtml = '';

	for($i=0; $i < count($templateEmail); $i++){
		$templateHtml .= $findReplace->Execute($templateEmail[$i]);
	}

	$mail->setFrom($GLOBALS['EMAIL_FROM']);
	$mail->setSubject(sprintf("Affiliate Documents from %s", $GLOBALS['COMPANY']));
	$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
	$mail->setHTML($templateHtml);
	$mail->send(array($customer->Contact->Person->Email));

	redirect(sprintf("Location: contact_profile.php?cid=%d", $customer->Contact->ID));
}

function view() {
	$customer = new Customer($_REQUEST['customer']);
	$customer->Contact->Get();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('customer', 'Is Customer', 'hidden', $customer->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('affiliate', 'Is Affiliate', 'checkbox', $customer->IsAffiliate, 'boolean', 1, 1, false);
	$form->AddField('commission', 'Commission (%)', 'text', $customer->AffiliateCommissionRate, 'float', 1, 11, true);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()) {
			$customer->IsAffiliate = $form->GetValue('affiliate');
			$customer->AffiliateCommissionRate = $form->GetValue('commission');
			$customer->Update();

			redirect(sprintf("Location: contact_profile.php?cid=%d", $customer->Contact->ID));
		}
	}

	if($customer->Contact->HasParent) {
		$tempHeader = sprintf("<a href=\"contact_profile.php?cid=%d\">%s</a> &gt; ", $customer->Contact->Parent->ID, $customer->Contact->Parent->Organisation->Name);
	}

	$tempHeader .= sprintf("<a href=\"contact_profile.php?cid=%d\">%s %s</a> &gt;", $customer->Contact->ID, $customer->Contact->Person->Name, $customer->Contact->Person->LastName);

	$page = new Page(sprintf('%s Affiliate Information for %s', $tempHeader, $customer->Contact->Person->GetFullName()), sprintf('Below is the affiliate information for %s only.', $customer->Contact->Person->GetFullName()));
	$page->Display('header');

	$period = isset($_REQUEST['period']) ? $_REQUEST['period'] : 0;

	$window = new StandardWindow('Edit the security details');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('customer');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('affiliate'), $form->GetHTML('affiliate').$form->GetIcon('affiliate'));
	echo $webForm->AddRow($form->GetLabel('commission'), $form->GetHTML('commission').$form->GetIcon('commission'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'contact_profile.php?cid=%s\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $customer->Contact->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Send affiliate information pack via email');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="email" value="email affiliate documents" class="btn" onclick="window.self.location=\'%s?customer=%d&action=email\';" />', $_SERVER['PHP_SELF'], $customer->ID));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$start = date('Y-m-d', mktime(0, 0, 0, date('m') + $period, 1, date('Y')));
	$end = date('Y-m-d', mktime(0, 0, 0, date('m') + 1 + $period, 1, date('Y')));

	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM customer_session WHERE Affiliate_ID=%d AND Created_On BETWEEN '%s' AND '%s'", mysql_real_escape_string($customer->ID), mysql_real_escape_string($start), mysql_real_escape_string($end)));
	$clicks = $data->Row['Count'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count, SUM(SubTotal) AS SubTotal FROM orders WHERE Affiliate_ID=%d AND Created_On BETWEEN '%s' AND '%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')", mysql_real_escape_string($customer->ID), mysql_real_escape_string($start), mysql_real_escape_string($end)));
	$salesCount = $data->Row['Count'];
	$salesTotal = $data->Row['SubTotal'];
	$data->Disconnect();
	?>

	<br />
	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
		<thead>
			<tr>
				<th style="text-align: left;"><a href="<?php print $_SERVER['PHP_SELF']; ?>?period=<?php print ($period - 1); ?>&customer=<?php echo $customer->ID; ?>">Previous Month</a></th>
				<th style="text-align: right;"><a href="<?php print $_SERVER['PHP_SELF']; ?>?period=<?php print ($period + 1); ?>&customer=<?php echo $customer->ID; ?>">Next Month</a></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td width="50%"><strong>Period</strong></td>
				<td><?php print cDatetime($start, 'shortdate'); ?> - <?php print cDatetime($end, 'shortdate'); ?></td>
			</tr>
			<tr>
				<td><strong>Click Throughs</strong></td>
				<td><?php print $clicks; ?></td>
			</tr>
			<tr>
				<td><strong>Sales Count</strong></td>
				<td><?php print $salesCount; ?></td>
			</tr>
			<tr>
				<td><strong>Sales Total</strong></td>
				<td>&pound;<?php print number_format($salesTotal, 2, '.', ','); ?></td>
			</tr>
			<tr>
				<td><strong>Commission Rate</strong></td>
				<td><?php print $customer->AffiliateCommissionRate; ?>%</td>
			</tr>
			<tr>
				<td><strong>Commission Earnt</strong></td>
				<td>&pound;<?php print number_format(($salesTotal / 100) * $customer->AffiliateCommissionRate, 2, '.', ','); ?></td>
			</tr>
		</tbody>
	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>