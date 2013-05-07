<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
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
	$form->AddField('supplier', 'Suppliers', 'select', isset($_SESSION['preferences']['supplier_invoice_queries_resolved']['supplier']) ? $_SESSION['preferences']['supplier_invoice_queries_resolved']['supplier'] : '0', 'numeric_unsigned', 1, 11, true);
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
				$_SESSION['preferences']['supplier_invoice_queries_resolved']['supplier'] = $form->GetValue('supplier');

				redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
			}
		}
	}

	$page = new Page('Resolved Supplier Invoice Queries', 'Listing all resolved supplier invoice queries.');
	$page->Display('header');

    if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

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

	echo $form->Close();

	echo '<br />';

	$table = new DataTable('queries');
    $table->SetSQL(sprintf("SELECT siq.*, DATE(siq.CreatedOn) AS CreatedDate, IF(siq.InvoiceDate<>'0000-00-00 00:00:00', DATE(siq.InvoiceDate), '') AS InvoiceDate, IF(LENGTH(TRIM(o.Org_Name))>0, TRIM(o.Org_Name), IF(LENGTH(TRIM(o.Org_Name))>0, TRIM(o.Org_Name), TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)))) AS SupplierName FROM supplier_invoice_query AS siq INNER JOIN supplier AS s ON s.Supplier_ID=siq.SupplierID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE siq.Status LIKE 'Resolved'%s GROUP BY siq.SupplierInvoiceQueryID", ($form->GetValue('supplier') > 0) ? sprintf(' AND siq.SupplierID=%d', mysql_real_escape_string($form->GetValue('supplier'))) : ''));
	$table->AddField('ID', 'SupplierInvoiceQueryID');
	$table->AddField('Query Date','CreatedDate');
	$table->AddField('Supplier','SupplierName');
    $table->AddField('Invoice Reference','InvoiceReference');
	$table->AddField('Invoice Date','InvoiceDate');
	$table->AddField('Invoice Amount','InvoiceAmount');
	$table->AddField('Description','Description');
	$table->AddField('Total', 'Total', 'right');
	$table->AddLink('supplier_invoice_query_details.php?queryid=%s', '<img src="images/folderopen.gif" alt="Open" border="0" />', 'SupplierInvoiceQueryID');
	$table->AddLink(sprintf('javascript:confirmRequest(\'%s?action=remove&id=%%s\', \'Are you sure you want to remove this item?\');', $_SERVER['PHP_SELF']), '<img src="images/aztector_6.gif" alt="Remove" border="0" />', 'SupplierInvoiceQueryID');
	$table->SetMaxRows(25);
	$table->SetOrderBy('CreatedDate');
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
}