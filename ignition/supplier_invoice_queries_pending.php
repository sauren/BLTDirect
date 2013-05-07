<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierInvoiceQuery.php');

if($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	if(isset($_REQUEST['id'])) {
		$query = new SupplierInvoiceQuery();
		$query->Delete($_REQUEST['id']);
	}

	redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
}

function view() {
    $form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('supplier', 'Suppliers', 'select', isset($_SESSION['preferences']['supplier_invoice_queries_pending']['supplier']) ? $_SESSION['preferences']['supplier_invoice_queries_pending']['supplier'] : '0', 'numeric_unsigned', 1, 11, true);
	$form->AddGroup('supplier', 'Y', 'Favourite Suppliers');
	$form->AddGroup('supplier', 'N', 'Standard Suppliers');
	$form->AddOption('supplier', '0', '');

    $data = new DataQuery(sprintf("SELECT s.Supplier_ID, IF((LENGTH(TRIM(o.Org_Name)) > 0) AND (LENGTH(TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last))) > 0), CONCAT_WS(' ', TRIM(o.Org_Name), CONCAT('(', TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)), ')')), IF(LENGTH(TRIM(o.Org_Name)) > 0, TRIM(o.Org_Name), TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)))) AS Supplier_Name FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID ORDER BY Supplier_Name ASC"));
	while($data->Row) {
		$form->AddOption('supplier', $data->Row['Supplier_ID'], $data->Row['Supplier_Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])) {
		if(isset($_REQUEST['filter'])) {
			if($form->Validate()) {
				$_SESSION['preferences']['supplier_invoice_queries_pending']['supplier'] = $form->GetValue('supplier');

				redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
			}
		} elseif(isset($_REQUEST['email'])) {
    		$queries = array();

			foreach($_REQUEST as $text=>$value) {
				if(preg_match('/selected_([0-9]+)/', $text, $matches)) {
					$query = new SupplierInvoiceQuery();

					if($query->Get($matches[1])) {
						if(!isset($queries[$query->Supplier->ID])) {
							$queries[$query->Supplier->ID] = array();
						}

						$queries[$query->Supplier->ID][] = $query;
					}
				}
			}

			foreach($queries as $supplierId=>$queryData) {
				$query = $queryData[0];
				$query->Supplier->Get();
				$query->Supplier->Contact->Get();

				$lines = '';

	            foreach($queryData as $queryItem) {
					$lines .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%d</td><td>%s</td><td>%s</td><td align="right">&pound;%s</td><td align="right">&pound;%s</td><td align="right">&pound;%s</td><td align="right">&pound;%s</td></tr>', $queryItem->ID, $queryItem->InvoiceReference, ($queryItem->InvoiceDate != '0000-00-00 00:00:00') ? cDatetime($queryItem->InvoiceDate, 'shordate') : '', $queryItem->Quantity, $queryItem->Description, ($queryItem->Product->ID > 0) ? $queryItem->Product->ID : '', number_format($queryItem->ChargeStandard, 2, '.', ','), number_format($queryItem->ChargeReceived, 2, '.', ','), number_format($queryItem->Cost, 2, '.', ','), number_format($queryItem->Total, 2, '.', ','));
				}

				$findReplace = new FindReplace();
				$findReplace->Add('/\[SUPPLIER_DETAILS\]/', $query->GetSupplierAddress());
				$findReplace->Add('/\[SUPPLIER_QUERY_LINES\]/', $lines);

				$importTemplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/supplier_invoice_queries.tpl");
				$importHtml = '';

				for($i=0; $i<count($importTemplate); $i++){
					$importHtml .= $findReplace->Execute($importTemplate[$i]);
				}

				$findReplace = new FindReplace();
				$findReplace->Add('/\[BODY\]/', $importHtml);
				$findReplace->Add('/\[NAME\]/', $query->Supplier->Contact->Person->GetFullName());

				$standardTemplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
				$standardHtml = '';

				for($i=0; $i<count($standardTemplate); $i++){
					$standardHtml .= $findReplace->Execute($standardTemplate[$i]);
				}

	            $queue = new EmailQueue();
				$queue->GetModuleID('supplierinvoicequeries');
				$queue->Subject = sprintf("%s - Invoice Queries", $GLOBALS['COMPANY']);
				$queue->Body = $standardHtml;
				$queue->ToAddress = $query->Supplier->GetEmail();
				$queue->Priority = 'H';
				$queue->Type = 'H';
				$queue->Add();
			}
		}
	}

    $page = new Page('Pending Supplier Invoice Queries', 'Listing all pending supplier invoice queries.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}
    ?>

	<table width="100%">
		<tr>
			<td width="65%" valign="top" style="padding: 0 5px 0 0;">

				<?php
				echo $form->Open();
				echo $form->GetHTML('confirm');

				$window = new StandardWindow("Filter invoice queries");
				$webForm = new StandardForm();

				echo $window->Open();
				echo $window->AddHeader('Select filter criteria.');
				echo $window->OpenContent();
				echo $webForm->Open();
				echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHTML('supplier'));
				echo $webForm->AddRow('', sprintf('<input type="submit" name="filter" value="filter" class="btn" tabindex="%s" />', $form->GetTabIndex()));
				echo $webForm->Close();
				echo $window->CloseContent();
				echo $window->Close();
                ?>

			</td>
			<td width="35%" valign="top" style="padding: 0 0 0 5px;">

                <?php
                $data = new DataQuery(sprintf("SELECT SUM(siq.Total) AS Total FROM supplier_invoice_query AS siq WHERE siq.Status LIKE 'Pending'%s", ($form->GetValue('supplier') > 0) ? sprintf(' AND siq.SupplierID=%d', mysql_real_escape_string($form->GetValue('supplier'))) : ''));
                $disputeAmount = $data->Row['Total'];
                $data->Disconnect();
                
                $data = new DataQuery(sprintf("SELECT SUM(siq.InvoiceAmount) AS Total FROM (SELECT SupplierID, InvoiceAmount FROM supplier_invoice_query WHERE Status LIKE 'Pending' AND InvoiceReference<>'' GROUP BY SupplierID, InvoiceReference) AS siq WHERE TRUE%s", ($form->GetValue('supplier') > 0) ? sprintf(' AND siq.SupplierID=%d', mysql_real_escape_string($form->GetValue('supplier'))) : ''));
                $invoiceAmount = $data->Row['Total'];
                $data->Disconnect();

				$window = new StandardWindow("Invoice query statistics");
				$webForm = new StandardForm();

				echo $window->Open();
				echo $window->AddHeader('Details for the invoice queries listed below');
				echo $window->OpenContent();
				echo $webForm->Open();
				echo $webForm->AddRow('Dispute Amount', sprintf('&pound;%s', number_format($disputeAmount, 2, '.', ',')));
				echo $webForm->AddRow('Invoice Amount', sprintf('&pound;%s', number_format($invoiceAmount, 2, '.', ',')));
				echo $webForm->Close();
				echo $window->CloseContent();
				echo $window->Close();
                ?>

			</td>
		</tr>
	</table>
	<br />

	<?php
	$table = new DataTable('queries');
	$table->SetSQL(sprintf("SELECT siq.*, DATE(siq.CreatedOn) AS CreatedDate, IF(siq.InvoiceDate<>'0000-00-00 00:00:00', DATE(siq.InvoiceDate), '') AS InvoiceDate, IF(LENGTH(TRIM(o.Org_Name))>0, TRIM(o.Org_Name), IF(LENGTH(TRIM(o.Org_Name))>0, TRIM(o.Org_Name), TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)))) AS SupplierName FROM supplier_invoice_query AS siq INNER JOIN supplier AS s ON s.Supplier_ID=siq.SupplierID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE siq.Status LIKE 'Pending'%s GROUP BY siq.SupplierInvoiceQueryID", ($form->GetValue('supplier') > 0) ? sprintf(' AND siq.SupplierID=%d', mysql_real_escape_string($form->GetValue('supplier'))) : ''));
	$table->AddField('ID', 'SupplierInvoiceQueryID');
	$table->AddField('Query Date','CreatedDate');
	$table->AddField('Supplier','SupplierName');
	$table->AddField('Invoice Reference','InvoiceReference');
	$table->AddField('Invoice Date','InvoiceDate');
	$table->AddField('Invoice Amount','InvoiceAmount');
	$table->AddField('Description','Description');
	$table->AddField('Total', 'Total', 'right');
	$table->AddInput('', 'N', 'N', 'selected', 'SupplierInvoiceQueryID', 'checkbox');
	$table->AddLink('supplier_invoice_query_details.php?queryid=%s', '<img src="images/folderopen.gif" alt="Open" border="0" />', 'SupplierInvoiceQueryID');
	$table->AddLink(sprintf('javascript:confirmRequest(\'%s?action=remove&id=%%s\', \'Are you sure you want to remove this item?\');', $_SERVER['PHP_SELF']), '<img src="images/aztector_6.gif" alt="Remove" border="0" />', 'SupplierInvoiceQueryID');
	$table->SetMaxRows(25);
	$table->SetOrderBy('CreatedDate');
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

    echo '<br />';
	echo '<input type="submit" name="email" value="email" class="btn" />';

	echo $form->Close();

	$page->Display('footer');
}