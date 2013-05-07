<?php
require_once('lib/common/app_header.php');

if($action == "add") {
	$session->Secure(3);
	add();
	exit;
} elseif($action == "addcategory") {
	$session->Secure(3);
	addcategory();
	exit;
} elseif($action == "addproduct") {
	$session->Secure(3);
	addproduct();
	exit;
} elseif($action == "update") {
	$session->Secure(3);
	update();
	exit;
} elseif($action == "remove") {
	$session->Secure(3);
	remove();
	exit;
} elseif($action == "removecategory") {
	$session->Secure(3);
	removecategory();
	exit;
} elseif($action == "removeproduct") {
	$session->Secure(3);
	removeproduct();
	exit;
} elseif($action == "categories") {
	$session->Secure(2);
	categories();
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

function remove() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailProductPool.php');

	if(isset($_REQUEST['id'])) {
		$pool = new EmailProductPool($_REQUEST['id']);
		$pool->Delete();
	}

	redirect(sprintf("Location: email_product_pools.php"));
}

function removecategory() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailProductPoolCategory.php');

	$category = new EmailProductPoolCategory();

	if(!isset($_REQUEST['id']) || !$category->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	if(isset($_REQUEST['id'])) {
		$category->Delete();
	}

	redirect(sprintf("Location: email_product_pools.php?action=categories&id=%d", $category->EmailProductPoolID));
}

function removeproduct() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailProductPoolProduct.php');

	$product = new EmailProductPoolProduct();

	if(!isset($_REQUEST['id']) || !$product->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	if(isset($_REQUEST['id'])) {
		$product->Delete();
	}

	redirect(sprintf("Location: email_product_pools.php?action=products&id=%d", $product->EmailProductPoolID));
}

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailProductPool.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('name', 'Name', 'text', '', 'anything', 1, 64);

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()){
			$pool = new EmailProductPool();
			$pool->Name = $form->GetValue('name');
			$pool->Add();

			redirect(sprintf("Location: email_product_pools.php"));
		}
	}

	$page = new Page('<a href="email_product_pools.php">Email Product Pools</a> &gt; Add Product Pool', 'Here you can add a product pool for emails.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add Product Pool');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name').$form->GetIcon('name'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'email_product_pools.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailProductPool.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$pool = new EmailProductPool();

	if(!isset($_REQUEST['id']) || !$pool->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Email Product Pool ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', $pool->Name, 'anything', 1, 64);

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()){
			$pool->Name = $form->GetValue('name');
			$pool->Update();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('<a href="email_product_pools.php">Email Product Pools</a> &gt; Update Product Pool', 'Here you can update an existing product pool for emails.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Update Product Pool');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name').$form->GetIcon('name'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'email_product_pools.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Email Product Pools', 'Here you can manage product pools emails.');
	$page->Display('header');

	$table = new DataTable('pools');
	$table->SetSQL(sprintf("SELECT * FROM email_product_pool"));
	$table->AddField("ID#", "EmailProductPoolID");
	$table->AddField('Name', 'Name', 'left');
	$table->SetMaxRows(25);
	$table->SetOrderBy("Name");
	$table->AddLink("email_product_pools.php?action=categories&id=%s", "<img src=\"images/page_red_c.gif\" alt=\"Manage categories\" border=\"0\">", "EmailProductPoolID");
	$table->AddLink("email_product_pools.php?action=products&id=%s", "<img src=\"images/page_red_p.gif\" alt=\"Manage products\" border=\"0\">", "EmailProductPoolID");
	$table->AddLink("email_product_pools.php?action=update&id=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "EmailProductPoolID");
	$table->AddLink("javascript:confirmRequest('email_product_pools.php?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "EmailProductPoolID");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" name="add" value="add product pool" class="btn" onclick="window.location.href=\'email_product_pools.php?action=add\'" />';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function categories() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailProductPool.php');

	$pool = new EmailProductPool();

	if(!isset($_REQUEST['id']) || !$pool->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$page = new Page('<a href="email_product_pools.php">Email Product Pools</a> &gt; Pool Categories', 'Manage categories for this product pool');
	$page->Display('header');

	$table = new DataTable('categories');
	$table->SetSQL(sprintf("SELECT eppc.EmailProductPoolCategoryID, pc.Category_Title FROM email_product_pool_category AS eppc INNER JOIN product_categories AS pc ON pc.Category_ID=eppc.CategoryID WHERE eppc.EmailProductPoolID=%d", $pool->ID));
	$table->AddField("ID#", "EmailProductPoolCategoryID");
	$table->AddField('Category', 'Category_Title', 'left');
	$table->SetMaxRows(25);
	$table->SetOrderBy("Category_Title");
	$table->AddLink("javascript:confirmRequest('email_product_pools.php?action=removecategory&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "EmailProductPoolCategoryID");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" name="add" value="add category" class="btn" onclick="window.location.href=\'email_product_pools.php?action=addcategory&id=%d\'" />', $pool->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function products() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailProductPool.php');

	$pool = new EmailProductPool();

	if(!isset($_REQUEST['id']) || !$pool->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$page = new Page('<a href="email_product_pools.php">Email Product Pools</a> &gt; Pool Products', 'Manage products for this product pool.');
	$page->Display('header');

	$table = new DataTable('products');
	$table->SetSQL(sprintf("SELECT eppp.EmailProductPoolProductID, p.Product_ID, p.Product_Title FROM email_product_pool_product AS eppp INNER JOIN product AS p ON p.Product_ID=eppp.ProductID WHERE eppp.EmailProductPoolID=%d", $pool->ID));
	$table->AddField("ID#", "EmailProductPoolProductID");
	$table->AddField('Quickfind ID', 'Product_ID', 'left');
	$table->AddField('Product', 'Product_Title', 'left');
	$table->SetMaxRows(25);
	$table->SetOrderBy("Product_Title");
	$table->AddLink("javascript:confirmRequest('email_product_pools.php?action=removeproduct&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "EmailProductPoolProductID");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" name="add" value="add product" class="btn" onclick="window.location.href=\'email_product_pools.php?action=addproduct&id=%d\'" />', $pool->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function addcategory() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailProductPool.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailProductPoolCategory.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$pool = new EmailProductPool();

	if(!isset($_REQUEST['id']) || !$pool->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'addcategory', 'alpha', 11, 11);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Email Product Pool ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()){
			if($form->GetValue('parent') == 0) {
				$form->AddError('Please select a category.', 'parent');
			}

			if($form->Valid) {
				$category = new EmailProductPoolCategory();
				$category->EmailProductPoolID = $pool->ID;
				$category->CategoryID = $form->GetValue('parent');
				$category->Add();

				redirect(sprintf("Location: %s?action=categories&id=%d", $_SERVER['PHP_SELF'], $pool->ID));
			}
		}
	}

	$page = new Page(sprintf('<a href="email_product_pools.php">Email Product Pools</a> &gt; <a href="email_product_pools.php?action=categories&id=%d">Pool Categories</a> &gt; Add Pool Category', $pool->ID), 'Add a category to this product pool.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add Category');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $form->GetHTML('parent');

	$category = new Category();
	$category->Name = '<em>Select Category</em>';

	if($form->GetValue('parent') > 0) {
		$category->Get($form->GetValue('parent'));
	}

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('parent') . ' <a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search" /></a>', sprintf('<span id="parentCaption">%s</span>', $category->Name));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'email_product_pools.php?action=categories&id=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $pool->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function addproduct() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailProductPool.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailProductPoolProduct.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$pool = new EmailProductPool();

	if(!isset($_REQUEST['id']) || !$pool->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'addproduct', 'alpha', 10, 10);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Email Product Pool ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('product', 'Product ID', 'text', '', 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()){
			$product = new Product();

			if(!$product->Get($form->GetValue('product'))) {
				$form->AddError('Product ID does not exists.', 'product');
			}

			if($form->Valid) {
				$productPool = new EmailProductPoolProduct();
				$productPool->EmailProductPoolID = $pool->ID;
				$productPool->ProductID = $product->ID;
				$productPool->Add();

				redirect(sprintf("Location: %s?action=products&id=%d", $_SERVER['PHP_SELF'], $pool->ID));
			}
		}
	}

	$page = new Page(sprintf('<a href="email_product_pools.php">Email Product Pools</a> &gt; <a href="email_product_pools.php?action=products&id=%d">Pool Products</a> &gt; Add Pool Product', $pool->ID), 'Add a product to this product pool.');
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
	echo $webForm->AddRow($form->GetLabel('product'), $form->GetHTML('product').$form->GetIcon('product'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'email_product_pools.php?action=products&id=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $pool->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>