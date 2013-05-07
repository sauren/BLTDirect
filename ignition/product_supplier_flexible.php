<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSupplierFlexible.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

if ($action == 'add') {
	$session->Secure(3);
	add();
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
	if(isset($_REQUEST['id'])) {
		$flex = new ProductSupplierFlexible($_REQUEST['id']);
		$flex->Delete();
		
		redirect(sprintf('Location: ?pid=%d', $flex->Product->ID));
	}

	redirect(sprintf('Location: product_search.php'));
}

function add() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('pid', 'Product ID', 'hidden', $_REQUEST['pid'], 'numeric_unsigned', 1, 11);
	$form->AddField('supplier', 'Supplier', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddGroup('supplier', 'Y', 'Favourites');
	$form->AddGroup('supplier', 'N', 'Non-Favourites');
	$form->AddOption('supplier', '0', '');

	$data = new DataQuery(sprintf("SELECT s.Supplier_ID, s.Is_Favourite, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Name, o.Org_Name, sp.Cost FROM supplier AS s LEFT JOIN supplier_product AS sp ON sp.Supplier_ID=s.Supplier_ID AND sp.Product_ID=%d INNER JOIN contact AS c on s.Contact_ID=c.Contact_ID INNER JOIN person AS p on c.Person_ID=p.Person_ID LEFT JOIN contact AS c2 on c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o on c2.Org_ID=o.Org_ID ORDER BY o.Org_Name ASC, Name ASC", mysql_real_escape_string($form->GetValue('pid'))));
	while($data->Row) {
		$form->AddOption('supplier', $data->Row['Supplier_ID'], sprintf('%s%s', !empty($data->Row['Org_Name']) ? $data->Row['Org_Name'] : $data->Row['Name'], ($data->Row['Cost'] > 0) ? sprintf(' [&pound;%s]', $data->Row['Cost']) : ''), $data->Row['Is_Favourite']);
		
		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$flex = new ProductSupplierFlexible();
			$flex->Product->ID = $form->GetValue('pid');
			$flex->Supplier->ID = $form->GetValue('supplier');
			$flex->Add();

			redirect(sprintf('Location: ?pid=%d', $form->GetValue('pid')));
		}
	}

	$page = new Page(sprintf('<a href="product_profile.php?pid=%d">Product Profile</a> &gt; <a href="?pid=%d">Product Flexible Suppliers</a> &gt; Add New Supplier', $form->GetValue('pid'), $form->GetValue('pid')), 'Add a new supplier.');
	$page->Display('header');

	if (!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add a flexible supplier');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('pid');
	
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHTML('supplier') . $form->GetIcon('supplier'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location.href=\'?pid=%s\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $form->GetValue('pid'), $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}

function view() {
	$page = new Page(sprintf("<a href=product_profile.php?pid=%d>Product Profile</a> &gt; Product Flexible Suppliers", $_REQUEST['pid']), "Add flexible suppliers here.");
	$page->Display('header');

	$table = new DataTable('suppliers');
	$table->SetSQL(sprintf("SELECT psf.*, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Supplier, o.Org_Name, sp.Cost FROM product_supplier_flexible AS psf INNER JOIN supplier AS s ON s.Supplier_ID=psf.SupplierID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID LEFT JOIN supplier_product AS sp ON sp.Supplier_ID=s.Supplier_ID AND sp.Product_ID=psf.ProductID WHERE psf.ProductID=%d", mysql_real_escape_string($_REQUEST['pid'])));
	$table->AddField('Supplier', 'Supplier');
	$table->AddField('Organisation', 'Org_Name');
	$table->AddField('Cost', 'Cost', 'right');
	$table->AddLink("javascript:confirmRequest('?action=remove&id=%s','Are you sure you want to remove this supplier?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "ProductSupplierFlexibleID");
	$table->SetMaxRows(25);
	$table->SetOrderBy('Supplier');
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
	
	echo '<br />';
	echo sprintf('<input type="button" name="add" value="add new supplier" class="btn" onclick="window.location.href=\'?action=add&pid=%d\'"> ', $_REQUEST['pid']);

	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}