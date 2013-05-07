<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierReturnRequest.php');

$form = new Form($_SERVER['PHP_SELF'], 'GET');
$form->AddField('action', 'Action', 'hidden', 'find', 'alpha', 4, 4);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('orderid', 'Order ID', 'text', '', 'numeric_unsigned', 1, 11);
$form->AddField('supplier', 'Supplier', 'select', '', 'anything', 1, 11);
$form->AddOption('supplier', '', '');

$data = new DataQuery(sprintf("SELECT s.Supplier_ID, IF((LENGTH(TRIM(o.Org_Name)) > 0) AND (LENGTH(TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last))) > 0), CONCAT_WS(' ', TRIM(o.Org_Name), CONCAT('(', TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)), ')')), IF(LENGTH(TRIM(o.Org_Name)) > 0, TRIM(o.Org_Name), TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)))) AS Supplier_Name FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID ORDER BY o.Org_Name ASC, Supplier_Name ASC"));
while($data->Row) {
	$form->AddOption('supplier', $data->Row['Supplier_ID'], $data->Row['Supplier_Name']);

	$data->Next();
}
$data->Disconnect();

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$returnRequest = new SupplierReturnRequest();

		if(!$returnRequest->Order->Get($form->GetValue('orderid'))) {
			$form->AddError('The selected Order ID does not exist.', 'orderid');
		} else {
			$returnRequest->Supplier->ID = $form->GetValue('supplier');
			$returnRequest->Status = 'Pending';
			$returnRequest->Add();

			redirect(sprintf('Location: supplier_return_request_details.php?requestid=%d', $returnRequest->ID));
		}
	}
}

$page = new Page('Create New Supplier Return Request', '');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHtml('action');
echo $form->GetHtml('confirm');

$window = new StandardWindow("Select request details.");
$webForm = new StandardForm();

echo $window->Open();
echo $window->AddHeader('Create a supplier return request for the following order.');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('orderid'), $form->GetHTML('orderid'));
echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHTML('supplier'));
echo $webForm->AddRow('', '<input type="submit" name="create" value="create" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');