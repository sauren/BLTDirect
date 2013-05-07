<?php
require_once ('lib/common/app_header.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierReturnRequest.php');

$form = new Form($_SERVER['PHP_SELF'], 'GET');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha_numeric', 4, 4);

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$request = new SupplierReturnRequest();

		foreach($_REQUEST as $key=>$value) {
			if(strlen($value) > 0) {
				if(strlen($key) >= 12) {
					if(substr($key, 0, 12) == 'integration_') {
						$id = substr($key, 12);

						if(is_numeric($id)) {
							$request->Get($id);
							$request->IntegrationID = $value;
							$request->Update();
						}
					}
				}
			}
		}

		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}
}

$page = new Page('Awaiting Credit', 'Below is a list of all supplier return requests missing transaction references.');
$page->Display('header');

echo $form->Open();
echo $form->GetHTML('confirm');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

$table = new DataTable('requests');
$table->SetSQL(sprintf("SELECT srr.*, IF((LENGTH(TRIM(o.Org_Name)) > 0) AND (LENGTH(TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last))) > 0), CONCAT_WS(' ', TRIM(o.Org_Name), CONCAT('(', TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)), ')')), IF(LENGTH(TRIM(o.Org_Name)) > 0, TRIM(o.Org_Name), TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)))) AS SupplierName FROM supplier_return_request AS srr INNER JOIN supplier AS s ON s.Supplier_ID=srr.SupplierID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE srr.Status LIKE 'Confirmed' AND srr.IntegrationID='' AND srr.IsPrinted='Y'"));
$table->AddField('ID', 'SupplierReturnRequestID');
$table->AddField('Request Date','CreatedOn');
$table->AddField('Supplier','SupplierName');
$table->AddField('Authorisation', 'AuthorisationNumber');
$table->AddField('Total', 'Total', 'right');
$table->AddInput('Integration ID', 'Y', 'IntegrationID', 'integration', 'SupplierReturnRequestID', 'text');
$table->AddLink('supplier_return_request_details.php?requestid=%s', '<img src="images/folderopen.gif" alt="Open" border="0" />', 'SupplierReturnRequestID');
$table->SetMaxRows(25);
$table->SetOrderBy('CreatedOn');
$table->Order = 'DESC';
$table->Finalise();
$table->DisplayTable();
echo '<br />';
$table->DisplayNavigation();

echo '<br />';
echo '<input type="submit" name="update" value="update" class="btn">';

echo $form->Close();

$page->Display('footer');
require_once ('lib/common/app_footer.php');