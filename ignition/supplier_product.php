<?php
require_once ('lib/common/app_header.php');

if ($action == 'add') {
	$session->Secure(3);
	add();
	exit();
} elseif ($action == 'update') {
	$session->Secure(3);
	update();
	exit();
} elseif ($action == 'remove') {
	$session->Secure(3);
	remove();
	exit();
} else {
	$session->Secure(2);
	view();
	exit();
}

function remove() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	if (isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true') && isset($_REQUEST['spd'])) {
		$data = new DataQuery(sprintf("delete from supplier_product where Supplier_Product_ID=%d", $_REQUEST['spd']));
	}

	redirect(sprintf("Location: supplier_product.php?pid=%d", $_REQUEST['pid']));
}

function view() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page(sprintf("<a href=product_profile.php?pid=%d>Product Profile</a> &gt Supplier information", $_REQUEST['pid']), "You add suppliers who provide this product, as well as the price they charge, here.");
	$page->Display('header');

	$sql = sprintf("SELECT sp.*, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Supplier, o.Org_Name FROM supplier_product sp INNER JOIN supplier s on sp.Supplier_ID = s.Supplier_ID INNER JOIN contact c on s.Contact_ID =  c.Contact_ID INNER JOIN person p on c.Person_ID = p.Person_ID LEFT JOIN contact c2 on c2.Contact_ID = c.Parent_Contact_ID LEFT JOIN organisation o on c2.Org_ID=o.Org_ID where sp.Product_ID = %d", mysql_real_escape_string($_REQUEST['pid']));
	$table = new DataTable("com");
	$table->SetSQL($sql);
	$table->AddField('Supplier', 'Supplier');
	$table->AddField('Organisation', 'Org_Name', 'left');
	$table->AddField('SKU', 'Supplier_SKU', 'left');
	$table->AddField('Preferred Supplier', 'Preferred_Supplier', 'center');
	$table->AddField('Is Supplied', 'Is_Supplied', 'center');
	$table->AddField('Cost', 'Cost', 'right');
	$table->AddField('Lead Days', 'Lead_Days', 'right');
	$table->AddField('Is Unavailable', 'IsUnavailable', 'center');
	$table->AddLink('supplier_product.php?action=update&spd=%s', "<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", 'Supplier_Product_ID');
	$table->AddLink("javascript:confirmRequest('supplier_product.php?action=remove&confirm=true&spd=%s','Are you sure you want to remove this supplier?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Supplier_Product_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy('Username');
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo sprintf('<input type="button" name="add" value="Add a new supplier" class="btn" onclick="window.location.href=\'supplier_product.php?action=add&pid=%d\'"> ', $_REQUEST['pid']);

	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}

function add() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('pid', 'Product ID', 'hidden', $_REQUEST['pid'], 'numeric_unsigned', 1, 11);
	$form->AddField('supplier', 'Supplier', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('supplier', '0', '');
	$form->AddField('cost', 'Cost', 'text', '', 'float', 1, 11);
	$form->AddField('reason', 'Reason', 'text', '', 'paragraph', 1, 240);
	$form->AddField('leaddays', 'Lead Days', 'text', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('isunavailable', 'Is Unavailable', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('sku', 'Supplier Part Number', 'text', '', 'anything', 1, 30, false);
	$form->AddField('preferred', 'Preferred Supplier', 'select', 'N', 'alpha', 1, 4);
	$form->AddOption('preferred', 'N', 'No');
	$form->AddOption('preferred', 'Y', 'Yes');
	$form->AddField('issupplied', 'Is Supplied', 'checkbox', 'Y', 'boolean', 1, 1, false);

	$sql = sprintf("SELECT s.Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Name, o.Org_Name FROM supplier AS s
					INNER JOIN contact AS c on s.Contact_ID=c.Contact_ID
					INNER JOIN person AS p on c.Person_ID=p.Person_ID
					LEFT JOIN contact AS c2 on c2.Contact_ID=c.Parent_Contact_ID
					LEFT JOIN organisation AS o on c2.Org_ID=o.Org_ID ORDER BY o.Org_Name ASC, Name ASC");

	$data = new DataQuery($sql);
	while($data->Row) {
		$form->AddOption('supplier', $data->Row['Supplier_ID'], !empty($data->Row['Org_Name']) ? $data->Row['Org_Name'] : $data->Row['Name']);
		
		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])) {
		if ($form->Validate()) {
			$supplierProduct = new SupplierProduct();
			$supplierProduct->Product->ID = $form->GetValue('pid');
			$supplierProduct->Supplier->ID = $form->GetValue('supplier');
			$supplierProduct->Cost = $form->GetValue('cost');
			$supplierProduct->Reason = $form->GetValue('reason');
			$supplierProduct->LeadDays = $form->GetValue('leaddays');
			$supplierProduct->IsUnavailable = $form->GetValue('isunavailable');
			$supplierProduct->PreferredSup = $form->GetValue('preferred');
			$supplierProduct->SKU = $form->GetValue('sku');
			$supplierProduct->IsSupplied = $form->GetValue('issupplied');

			if (!$supplierProduct->IsUnique()) {
				$form->AddError('This supplier already supplies this product.', 'supplier');
			}

			if ($supplierProduct->Supplier->ID == 0) {
				$form->AddError('Please choose a supplier.', 'supplier');
			}

			if ($form->Valid) {
				$supplierProduct->Add();

				redirect(sprintf("Location: supplier_product.php?pid=%d", $form->GetValue('pid')));
			}
		}
	}

	$page = new Page(sprintf("<a href=product_profile.php?pid=%d>Product Profile</a> &gt <a href=supplier_product.php?pid=%d> Supplier Information </a> &gt Add New Supplier", $_REQUEST['pid'], $_REQUEST['pid']), "Add a new supplier who will supply this product");
	$page->Display('header');

	if (!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Add a supplier of the product');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('pid');

	echo $window->Open();
	echo $window->AddHeader('Supplier cost price and reason.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHTML('supplier') . $form->GetIcon('supplier'));
	echo $webForm->AddRow($form->GetLabel('cost'), $form->GetHTML('cost') . $form->GetIcon('cost'));
	echo $webForm->AddRow($form->GetLabel('reason'), $form->GetHTML('reason') . $form->GetIcon('reason'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Supplementary supplier data.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('sku'), $form->GetHTML('sku') . $form->GetIcon('sku'));
	echo $webForm->AddRow($form->GetLabel('leaddays'), $form->GetHTML('leaddays') . $form->GetIcon('leaddays'));
	echo $webForm->AddRow($form->GetLabel('isunavailable'), $form->GetHTML('isunavailable') . $form->GetIcon('isunavailable'));
	echo $webForm->AddRow($form->GetLabel('preferred'), $form->GetHTML('preferred') . $form->GetIcon('preferred'));
	echo $webForm->AddRow($form->GetLabel('issupplied'), $form->GetHTML('issupplied') . $form->GetIcon('issupplied'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'supplier_product.php?pid=%s\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $_REQUEST['pid'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}

function update() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$supplierProduct = new SupplierProduct($_REQUEST['spd']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('spd', 'Supplier Product ID', 'hidden', $_REQUEST['spd'], 'numeric_unsigned', 1, 11);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('pid', 'Product ID', 'hidden', $_REQUEST['pid'], 'numeric_unsigned', 1, 11);
	$form->AddField('cost', 'Cost', 'text', $supplierProduct->Cost, 'float', 1, 11);
	$form->AddField('reason', 'Reason', 'text', '', 'paragraph', 1, 240);
	$form->AddField('leaddays', 'Lead Days', 'text', $supplierProduct->LeadDays, 'numeric_unsigned', 1, 11);
	$form->AddField('isunavailable', 'Is Unavailable', 'checkbox', $supplierProduct->IsUnavailable, 'boolean', 1, 1, false);
	$form->AddField('sku', 'Supplier Part Number', 'text', $supplierProduct->SKU, 'anything', 1, 30, false);
	$form->AddField('preferred', 'Preferred Supplier', 'select', $supplierProduct->PreferredSup, 'alpha', 1, 4);
	$form->AddOption('preferred', 'Y', 'Yes');
	$form->AddOption('preferred', 'N', 'No');
	$form->AddField('issupplied', 'Is Supplied', 'checkbox', $supplierProduct->IsSupplied, 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		$form->InputFields['reason']->Required = !($supplierProduct->Cost == $form->GetValue('cost'));

		if($form->Validate()) {
			$supplierProduct->Cost = $form->GetValue('cost');
			$supplierProduct->Reason = $form->GetValue('reason');
			$supplierProduct->LeadDays = $form->GetValue('leaddays');
			$supplierProduct->IsUnavailable = $form->GetValue('isunavailable');
			$supplierProduct->PreferredSup = $form->GetValue('preferred');
			$supplierProduct->SKU = $form->GetValue('sku');
			$supplierProduct->IsSupplied = $form->GetValue('issupplied');
			$supplierProduct->Update();

			redirect(sprintf("Location: supplier_product.php?pid=%d", $form->GetValue('pid')));
		}
	}

	$page = new Page(sprintf("<a href=product_profile.php?pid=%d>Product Profile</a> &gt <a href=supplier_product.php?pid=%d> Supplier Information </a> &gt Product Supplier Details", $_REQUEST['pid'], $_REQUEST['pid']), "Edit the details of the supplier who supplies this product");
	$page->Display('header');

	if (!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}
	$window = new StandardWindow('Add a supplier of the product');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('pid');
	echo $form->GetHTML('spd');

	echo $window->Open();
	echo $window->AddHeader('Supplier cost price and reason.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('cost'), $form->GetHTML('cost') . $form->GetIcon('cost'));
	echo $webForm->AddRow($form->GetLabel('reason'), $form->GetHTML('reason') . $form->GetIcon('reason'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Supplementary supplier data.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('leaddays'), $form->GetHTML('leaddays') . $form->GetIcon('leaddays'));
	echo $webForm->AddRow($form->GetLabel('isunavailable'), $form->GetHTML('isunavailable') . $form->GetIcon('isunavailable'));
	echo $webForm->AddRow($form->GetLabel('preferred'), $form->GetHTML('preferred') . $form->GetIcon('preferred'));
	echo $webForm->AddRow($form->GetLabel('sku'), $form->GetHTML('sku') . $form->GetIcon('sku'));
	echo $webForm->AddRow($form->GetLabel('issupplied'), $form->GetHTML('issupplied') . $form->GetIcon('issupplied'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'supplier_product.php?pid=%s\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $_REQUEST['pid'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}