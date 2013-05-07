<?php
require_once('lib/common/app_header.php');

if($action == "add"){
	$session->Secure(3);
	add();
	exit;
} elseif($action == "addproduct") {
	$session->Secure(3);
	addproduct();
	exit;
} elseif($action == "update"){
	$session->Secure(3);
	update();
	exit;
} elseif($action == "remove"){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == "removeproduct") {
	$session->Secure(3);
	removeproduct();
	exit;
} elseif($action == "products") {
	$session->Secure(2);
	products();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TestSupplier.php');

	if(isset($_REQUEST['id'])) {
		$supplier = new TestSupplier($_REQUEST['id']);
		$supplier->Delete();

		redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $supplier->TestID));
	}

	redirect(sprintf("Location: tests.php"));
}

function removeproduct() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TestSupplierProduct.php');

	if(isset($_REQUEST['id'])) {
		$product = new TestSupplierProduct($_REQUEST['id']);
		$product->Delete();

		redirect(sprintf("Location: %s?action=products&id=%d", $_SERVER['PHP_SELF'], $product->TestSupplierID));
	}

	redirect(sprintf("Location: tests.php"));
}

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Test.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TestSupplier.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$test = new Test();

	if(!$test->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: tests.php"));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Test ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('supplier', 'Supplier', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddGroup('supplier', 'Y', 'Favourite Suppliers');
	$form->AddGroup('supplier', 'N', 'Standard Suppliers');
	$form->AddOption('supplier', '', '');

	$data = new DataQuery(sprintf("SELECT s.Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Supplier_Name, s.Is_Favourite FROM supplier AS s INNER JOIN contact AS c ON s.Contact_ID=c.Contact_ID INNER JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID ORDER BY Supplier_Name ASC"));
	while($data->Row) {
		$form->AddOption('supplier', $data->Row['Supplier_ID'], $data->Row['Supplier_Name'], $data->Row['Is_Favourite']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('customer', 'Customer', 'select', '0', 'numeric_unsigned', 1, 11, false, 'onchange="propogateCustomerContacts(\'customerContact\', this.value);"');
	$form->AddOption('customer', '0', '');

	$data = new DataQuery(sprintf("SELECT cu.Customer_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Customer_Name FROM customer AS cu INNER JOIN contact AS c ON cu.Contact_ID=c.Contact_ID INNER JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID WHERE c.Is_Test='Y' ORDER BY Customer_Name ASC"));
	while($data->Row) {
		$form->AddOption('customer', $data->Row['Customer_ID'], $data->Row['Customer_Name']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('customerContact', 'Customer Address', 'select', '0', 'numeric_unsigned', 1, 11, false, ($form->GetValue('customer') > 0) ? '' : 'disabled="disabled"');
	$form->AddOption('customerContact', '0', 'Address');

	if($form->GetValue('customer') > 0) {
		$data = new DataQuery(sprintf("SELECT cc.Customer_Contact_ID, CONCAT_WS(' ', cc.Name_Title, cc.Name_First, cc.Name_Last) AS Contact_Name, TRIM(BOTH ',' FROM TRIM(REPLACE(CONCAT_WS(', ', a.Address_Line_1, a.Address_Line_2, a.Address_Line_3), ', , ', ''))) AS Address, a.City, UPPER(a.Zip) AS Zip, IF(LENGTH(a.Region_Name) > 0, a.Region_Name, r.Region_Name) AS Region, c.Country FROM customer_contact AS cc INNER JOIN address AS a ON cc.Address_ID=a.Address_ID LEFT JOIN regions AS r ON r.Region_ID=a.Region_ID LEFT JOIN countries AS c ON c.Country_ID=a.Country_ID WHERE cc.Customer_ID=%d", mysql_real_escape_string($form->GetValue('customer'))));
		while($data->Row) {
			$form->AddOption('customerContact', $data->Row['Customer_Contact_ID'], sprintf('%s, %s, %s, %s, %s', $data->Row['Contact_Name'], $data->Row['Address'], $data->Row['City'], $data->Row['Zip'], $data->Row['Region']));

			$data->Next();
		}
		$data->Disconnect();
	}

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$supplier = new TestSupplier();
			$supplier->TestID = $test->ID;
			$supplier->Supplier->ID = $form->GetValue('supplier');
			$supplier->Customer->ID = $form->GetValue('customer');
			$supplier->CustomerContact->ID = $form->GetValue('customerContact');
			$supplier->Add();

			redirect(sprintf("Location: test_suppliers.php?id=%d", $test->ID));
		}
	}

	$page = new Page(sprintf('<a href="test_profile.php?id=%d">Test Profile</a> &gt; <a href="%s?id=%d">Edit Suppliers</a> &gt; Add Supplier', $test->ID, $_SERVER['PHP_SELF'], $test->ID), 'Here you can add a supplier for this test.');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/testCustomerContacts.php"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add Supplier');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHTML('supplier') . $form->GetIcon('supplier'));
	echo $webForm->AddRow($form->GetLabel('customer'), $form->GetHTML('customer') . $form->GetIcon('customer'));
	echo $webForm->AddRow($form->GetLabel('customerContact'), $form->GetHTML('customerContact') . $form->GetIcon('customerContact'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'test_suppliers.php?id=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $test->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function addproduct() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TestSupplier.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TestSupplierProduct.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$supplier = new TestSupplier();

	if(!$supplier->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: tests.php"));
	}

	$supplier->Supplier->Get();
	$supplier->Supplier->Contact->Get();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'addproduct', 'alpha', 10, 10);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Test Supplier ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('product', 'Product', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddField('quantity', 'Quantity', 'text', '', 'numeric_unsigned', 1, 11);

	$data = new DataQuery(sprintf("SELECT sp.Cost, p.Product_ID, p.Product_Title, SUM(ol.Quantity) AS Quantity_Sold FROM supplier_product AS sp INNER JOIN supplier AS s ON s.Supplier_ID=sp.Supplier_ID INNER JOIN warehouse AS w ON w.Type_Reference_ID=s.Supplier_ID AND w.Type='S' INNER JOIN product AS p ON p.Product_ID=sp.Product_ID INNER JOIN order_line AS ol ON ol.Product_ID=p.Product_ID AND ol.Despatch_ID>0 AND ol.Despatch_From_ID=w.Warehouse_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -6 MONTH) AND o.Status LIKE 'Despatched' WHERE sp.Supplier_ID=%d AND sp.Cost>0 GROUP BY sp.Supplier_Product_ID ORDER BY Quantity_Sold DESC, p.Product_Title ASC LIMIT 0, 10", mysql_real_escape_string($supplier->Supplier->ID)));
	while($data->Row) {
		$form->AddOption('product', $data->Row['Product_ID'], sprintf('%d x %s (&pound;%s)', $data->Row['Quantity_Sold'], strip_tags($data->Row['Product_Title']), number_format($data->Row['Cost'], 2, '.', ',')));

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()){
			$product = new TestSupplierProduct();
			$product->TestSupplierID = $supplier->ID;
			$product->Product->ID = $form->GetValue('product');
			$product->Quantity = $form->GetValue('quantity');
			$product->Add();

			redirect(sprintf("Location: %s?action=products&id=%d", $_SERVER['PHP_SELF'], $supplier->ID));
		}
	}

	$page = new Page(sprintf('<a href="test_profile.php?id=%d">Test Profile</a> &gt; <a href="test_suppliers.php?id=%d">Edit Suppliers</a> &gt; <a href="test_suppliers.php?action=products&id=%d">Manage Products for %s</a> &gt; Add Product', $supplier->TestID, $supplier->TestID, $supplier->ID, $supplier->Supplier->Contact->Person->GetFullName()), 'Add a product to this supplier.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add Product');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('product'), $form->GetHTML('product').$form->GetIcon('product') . '<br />(Top 10 products sold within the last 6 months.)');
	echo $webForm->AddRow($form->GetLabel('quantity'), $form->GetHTML('quantity').$form->GetIcon('quantity'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'%s?action=products&id=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $_SERVER['PHP_SELF'], $supplier->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TestSupplier.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$supplier = new TestSupplier();

	if(!$supplier->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: tests.php"));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Test Supplier ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('supplier', 'Supplier', 'select', $supplier->Supplier->ID, 'numeric_unsigned', 1, 11);
	$form->AddGroup('supplier', 'Y', 'Favourite Suppliers');
	$form->AddGroup('supplier', 'N', 'Standard Suppliers');
	$form->AddOption('supplier', '', '');

	$data = new DataQuery(sprintf("SELECT s.Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Supplier_Name, s.Is_Favourite FROM supplier AS s INNER JOIN contact AS c ON s.Contact_ID=c.Contact_ID INNER JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID ORDER BY Supplier_Name ASC"));
	while($data->Row) {
		$form->AddOption('supplier', $data->Row['Supplier_ID'], $data->Row['Supplier_Name'], $data->Row['Is_Favourite']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('customer', 'Customer', 'select', $supplier->Customer->ID, 'numeric_unsigned', 1, 11, false, 'onchange="propogateCustomerContacts(\'customerContact\', this.value);"');
	$form->AddOption('customer', '0', '');

	$data = new DataQuery(sprintf("SELECT cu.Customer_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Customer_Name FROM customer AS cu INNER JOIN contact AS c ON cu.Contact_ID=c.Contact_ID INNER JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID WHERE c.Is_Test='Y' ORDER BY Customer_Name ASC"));
	while($data->Row) {
		$form->AddOption('customer', $data->Row['Customer_ID'], $data->Row['Customer_Name']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('customerContact', 'Customer Address', 'select', $supplier->CustomerContact->ID, 'numeric_unsigned', 1, 11, false, ($form->GetValue('customer') > 0) ? '' : 'disabled="disabled"');
	$form->AddOption('customerContact', '0', 'Address');

	if($form->GetValue('customer') > 0) {
		$data = new DataQuery(sprintf("SELECT cc.Customer_Contact_ID, CONCAT_WS(' ', cc.Name_Title, cc.Name_First, cc.Name_Last) AS Contact_Name, TRIM(BOTH ',' FROM TRIM(REPLACE(CONCAT_WS(', ', a.Address_Line_1, a.Address_Line_2, a.Address_Line_3), ', , ', ''))) AS Address, a.City, UPPER(a.Zip) AS Zip, IF(LENGTH(a.Region_Name) > 0, a.Region_Name, r.Region_Name) AS Region, c.Country FROM customer_contact AS cc INNER JOIN address AS a ON cc.Address_ID=a.Address_ID LEFT JOIN regions AS r ON r.Region_ID=a.Region_ID LEFT JOIN countries AS c ON c.Country_ID=a.Country_ID WHERE cc.Customer_ID=%d", mysql_real_escape_string($form->GetValue('customer'))));
		while($data->Row) {
			$form->AddOption('customerContact', $data->Row['Customer_Contact_ID'], sprintf('%s, %s, %s, %s, %s', $data->Row['Contact_Name'], $data->Row['Address'], $data->Row['City'], $data->Row['Zip'], $data->Row['Region']));

			$data->Next();
		}
		$data->Disconnect();
	}

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$supplier->Supplier->ID = $form->GetValue('supplier');
			$supplier->Customer->ID = $form->GetValue('customer');
			$supplier->CustomerContact->ID = $form->GetValue('customerContact');
			$supplier->Update();

			redirect(sprintf("Location: test_suppliers.php?id=%d", $supplier->TestID));
		}
	}

	$page = new Page(sprintf('<a href="test_profile.php?id=%d">Test Profile</a> &gt; <a href="%s?id=%d">Edit Suppliers</a> &gt; Update Supplier', $test->ID, $_SERVER['PHP_SELF'], $test->ID), 'Here you can edit an existing supplier for this test.');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/testCustomerContacts.php"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Update Supplier');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHTML('supplier') . $form->GetIcon('supplier'));
	echo $webForm->AddRow($form->GetLabel('customer'), $form->GetHTML('customer') . $form->GetIcon('customer'));
	echo $webForm->AddRow($form->GetLabel('customerContact'), $form->GetHTML('customerContact') . $form->GetIcon('customerContact'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'test_suppliers.php?id=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $test->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Test.php');

	$test = new Test();

	if(!$test->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: tests.php"));
	}

	$page = new Page(sprintf('<a href="test_profile.php?id=%d">Test Profile</a> &gt; Edit Suppliers', $test->ID), 'Here you can manage suppliers for this this.');
	$page->Display('header');

	$table = new DataTable('suppliers');
	$table->SetSQL(sprintf("SELECT ts.TestSupplierID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS SupplierName FROM test_supplier AS ts LEFT JOIN supplier AS s ON s.Supplier_ID=ts.SupplierID LEFT JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID WHERE ts.TestID=%d", mysql_real_escape_string($test->ID)));
	$table->AddField("ID#", "TestSupplierID");
	$table->AddField("Supplier", "SupplierName", "left");
	$table->AddLink("test_suppliers.php?action=products&id=%s", "<img src=\"images/page_blue_p.gif\" alt=\"Manage products\" border=\"0\">", "TestSupplierID");
	$table->AddLink("test_suppliers.php?action=update&id=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "TestSupplierID");
	$table->AddLink("javascript:confirmRequest('test_suppliers.php?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "TestSupplierID");
	$table->SetOrderBy('SupplierName');
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" name="add" value="add supplier" class="btn" onclick="window.location.href=\'test_suppliers.php?action=add&id=%d\'" />', $test->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function products() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TestSupplier.php');

	$supplier = new TestSupplier();

	if(!$supplier->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: tests.php"));
	}

	$supplier->Supplier->Get();
	$supplier->Supplier->Contact->Get();

	$page = new Page(sprintf('<a href="test_profile.php?id=%d">Test Profile</a> &gt; <a href="test_suppliers.php?id=%d">Edit Suppliers</a> &gt; Manage Products for %s', $supplier->TestID, $supplier->TestID, $supplier->Supplier->Contact->Person->GetFullName()), 'Here you can manage products for this supplier.');
	$page->Display('header');

	$table = new DataTable('products');
	$table->SetSQL(sprintf("SELECT tsp.TestSupplierProductID, tsp.Quantity, p.Product_ID, p.Product_Title FROM test_supplier_product AS tsp INNER JOIN product AS p ON p.Product_ID=tsp.ProductID WHERE tsp.TestSupplierID=%d", mysql_real_escape_string($supplier->ID)));
	$table->AddField("ID#", "TestSupplierProductID");
	$table->AddField('Quickfind ID', 'Product_ID', 'left');
	$table->AddField('Product', 'Product_Title', 'left');
	$table->AddField('Quantity', 'Quantity', 'left');
	$table->SetOrderBy("Product_Title");
	$table->AddLink("javascript:confirmRequest('test_suppliers.php?action=removeproduct&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "TestSupplierProductID");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" name="add" value="add product" class="btn" onclick="window.location.href=\'test_suppliers.php?action=addproduct&id=%d\'" />', $supplier->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>