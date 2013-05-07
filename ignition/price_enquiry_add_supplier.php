<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiry.php');

$session->Secure(3);

$priceEnquiry = new PriceEnquiry($_REQUEST['id']);

if(strtolower($priceEnquiry->Status) == 'complete') {
	redirect(sprintf("Location: price_enquiry_details.php?id=%d", $priceEnquiry->ID));
}

$suppliers = array();

$data = new DataQuery(sprintf("SELECT s.Supplier_ID, s.Is_Favourite, o.Org_Name, TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)) AS Person_Name FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID ORDER BY o.Org_Name ASC, Person_Name ASC"));
while($data->Row) {
	if(!empty($data->Row['Org_Name']) && !empty($data->Row['Person_Name'])) {
		$supplierName = sprintf('%s (%s)', $data->Row['Org_Name'], $data->Row['Person_Name']);
	} elseif(!empty($data->Row['Org_Name'])) {
		$supplierName = $data->Row['Org_Name'];
	} else {
		$supplierName = $data->Row['Person_Name'];
	}

	$suppliers[$data->Row['Supplier_ID']] = $data->Row;
	$suppliers[$data->Row['Supplier_ID']]['Supplier_Name'] = $supplierName;

	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Supplier_ID FROM price_enquiry_supplier WHERE Price_Enquiry_ID=%d", mysql_real_escape_string($priceEnquiry->ID)));
while($data->Row) {
	if(isset($suppliers[$data->Row['Supplier_ID']])) {
		unset($suppliers[$data->Row['Supplier_ID']]);
	}

	$data->Next();
}
$data->Disconnect();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Price Enquiry ID', 'hidden', $priceEnquiry->ID, 'numeric_unsigned', 1, 11);
$form->AddField('supplier', 'Suppliers', 'selectmultiple', '', 'numeric_unsigned', 1, 11, true, 'size="20"');
$form->AddGroup('supplier', 'Y', 'Favourite Suppliers');
$form->AddGroup('supplier', 'N', 'Standard Suppliers');

foreach($suppliers as $supplierId=>$supplierData) {
	$form->AddOption('supplier', $supplierId, $supplierData['Supplier_Name'], $supplierData['Is_Favourite']);
}

if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')){
	if($form->Validate()) {
		foreach($form->GetValue('supplier') as $supplierId) {
			$priceEnquiry->AddSupplier($supplierId);
		}

		$priceEnquiry->Recalculate();

		redirect(sprintf("Location: price_enquiry_details.php?id=%d", $priceEnquiry->ID));
	}
}

$page = new Page(sprintf('<a href="price_enquiry_details.php?id=%d">[#%d] Price Enquiry Details</a> &gt; Add Suppliers', $priceEnquiry->ID, $priceEnquiry->ID), 'Add suppliers to this price enquiry for receiving updated pricing information from.');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('id');

$window = new StandardWindow("Select suppliers");
$webForm = new StandardForm();

echo $window->Open();
echo $window->AddHeader('Add additional suppliers to this price enquiry.');
echo $window->OpenContent();
echo $webForm->Open();

if(count($suppliers) > 0) {
	echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHTML('supplier'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location.href=\'price_enquiry_details.php?id=%d\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $priceEnquiry->ID, $form->GetTabIndex()));
} else {
	echo $webForm->AddRow('', 'There are no suppliers available for adding to this price enquiry.');
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location.href=\'price_enquiry_details.php?id=%d\';" />', $priceEnquiry->ID));
}

echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');