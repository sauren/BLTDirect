<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierReturnRequest.php');

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
		$request = new SupplierReturnRequest();
		$request->Delete($_REQUEST['id']);
	}

	redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
}

function view() {
    $form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('supplier', 'Suppliers', 'select', '0', 'numeric_unsigned', 1, 11, true);
	$form->AddGroup('supplier', 'Y', 'Favourite Suppliers');
	$form->AddGroup('supplier', 'N', 'Standard Suppliers');
	$form->AddOption('supplier', '0', '');

    $data = new DataQuery(sprintf("SELECT s.Supplier_ID, IF((LENGTH(TRIM(o.Org_Name)) > 0) AND (LENGTH(TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last))) > 0), CONCAT_WS(' ', TRIM(o.Org_Name), CONCAT('(', TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)), ')')), IF(LENGTH(TRIM(o.Org_Name)) > 0, TRIM(o.Org_Name), TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)))) AS Supplier_Name FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID ORDER BY Supplier_Name ASC"));
	while($data->Row) {
		$form->AddOption('supplier', $data->Row['Supplier_ID'], $data->Row['Supplier_Name']);

		$data->Next();
	}
	$data->Disconnect();

    $script = '';

    if(isset($_REQUEST['confirm'])) {
    	if(isset($_REQUEST['print'])) {
    		$requests = array();

			foreach($_REQUEST as $text=>$value) {
				if(preg_match('/selected_([0-9]+)/', $text, $matches)) {
					$requests[] = $matches[1];
				}
			}

			$script .= sprintf('<script language="javascript" type="text/javascript">
					popUrl(\'supplier_return_request_print_list.php?requestid=%s\', 800, 600);
				</script>', implode(',', $requests));
		}
	}

	$page = new Page('Confirmed Supplier Return Requests', 'Listing all confirmed supplier return requests.');
	$page->AddToHead($script);
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

	echo '<br />';

	$table = new DataTable('requests');
	$table->SetSQL(sprintf("SELECT srr.*, srrl.Quantity, p2.Product_Title, IF((LENGTH(TRIM(o.Org_Name)) > 0) AND (LENGTH(TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last))) > 0), CONCAT_WS(' ', TRIM(o.Org_Name), CONCAT('(', TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)), ')')), IF(LENGTH(TRIM(o.Org_Name)) > 0, TRIM(o.Org_Name), TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)))) AS SupplierName FROM supplier_return_request AS srr INNER JOIN supplier_return_request_line AS srrl ON srrl.SupplierReturnRequestID=srr.SupplierReturnRequestID INNER JOIN product AS p2 ON p2.Product_ID=srrl.ProductID INNER JOIN supplier AS s ON s.Supplier_ID=srr.SupplierID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE srr.Status LIKE 'Confirmed'%s", ($form->GetValue('supplier') > 0) ? sprintf(' AND srr.SupplierID=%d', mysql_real_escape_string($form->GetValue('supplier'))) : ''));
	$table->AddField('ID', 'SupplierReturnRequestID');
	$table->AddField('Request Date','CreatedOn');
	$table->AddField('Supplier','SupplierName');
	$table->AddField('Authorisation', 'AuthorisationNumber');
	$table->AddField('Quantity','Quantity');
	$table->AddField('Product','Product_Title');
	$table->AddField('Printed', 'IsPrinted', 'center');
	$table->AddField('Total', 'Total', 'right');
	$table->AddInput('', 'N', 'N', 'selected', 'SupplierReturnRequestID', 'checkbox');
	$table->AddLink('supplier_return_request_details.php?requestid=%s', '<img src="images/folderopen.gif" alt="Open" border="0" />', 'SupplierReturnRequestID');
	$table->AddLink(sprintf('javascript:confirmRequest(\'%s?action=remove&id=%%s\', \'Are you sure you want to remove this item?\');', $_SERVER['PHP_SELF']), '<img src="images/aztector_6.gif" alt="Remove" border="0" />', 'SupplierReturnRequestID');
	$table->SetMaxRows(25);
	$table->SetOrderBy('CreatedOn');
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo '<input type="submit" name="print" value="print" class="btn" />';

    echo $form->Close();

	$page->Display('footer');
}