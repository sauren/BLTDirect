<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

$product = new Product($_REQUEST['pid']);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('pid', 'Product ID', 'hidden', $product->ID, 'numeric_unsigned', 1, 11);
$form->AddField('supplier', 'Supplier', 'select', $product->LockedSupplierID, 'numeric_unsigned', 1, 11);
$form->AddGroup('supplier', 'Y', 'Favourite Suppliers');
$form->AddGroup('supplier', 'N', 'Standard Suppliers');
$form->AddOption('supplier', '0', '');

$data = new DataQuery(sprintf("SELECT s.Supplier_ID, s.Is_Favourite, o.Org_Name, TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)) AS Person_Name, sp.Cost FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN supplier_product AS sp ON sp.Supplier_ID=s.Supplier_ID AND sp.Product_ID=%d AND sp.Cost>0 ORDER BY o.Org_Name ASC, Person_Name ASC", mysql_real_escape_string($form->GetValue('pid'))));
while($data->Row) {
	if(!empty($data->Row['Org_Name']) && !empty($data->Row['Person_Name'])) {
		$name = sprintf('%s (%s)', $data->Row['Org_Name'], $data->Row['Person_Name']);
	} elseif(!empty($data->Row['Org_Name'])) {
		$name = $data->Row['Org_Name'];
	} else {
		$name = $data->Row['Person_Name'];
	}

	$form->AddOption('supplier', $data->Row['Supplier_ID'], sprintf('%s%s', $name, (($data->Row['Cost'] > 0) ? sprintf(' [&pound;%s]', $data->Row['Cost']) : '')), $data->Row['Is_Favourite']);
	
	$data->Next();
}
$data->Disconnect();

if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
	if($form->Validate()){
		$product->LockedSupplierID = $form->GetValue('supplier');
		$product->Update();

		redirect(sprintf("Location: product_profile.php?pid=%d", $product->ID));
	}
}

$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; Edit Locked Supplier', $product->ID), 'Select a locked supplier for this product.');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Update');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
echo $form->GetHTML('pid');
echo $window->Open();
echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHTML('supplier') . $form->GetIcon('supplier'));
echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_profile.php?pid=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $product->ID, $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');