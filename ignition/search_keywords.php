<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SearchKeyword.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SearchKeywordCategory.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SearchKeywordProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

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
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$search = new SearchKeyword();
		$search->delete($_REQUEST['id']);
	}

	redirect('Location: ?action=view');
}

function removecategory() {
	$category = new SearchKeywordCategory();

	if(!isset($_REQUEST['id']) || !$category->get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	if(isset($_REQUEST['id'])) {
		$category->delete();
	}

	redirect(sprintf("Location: ?action=categories&id=%d", $category->searchKeywordId));
}

function removeproduct() {
	$product = new SearchKeywordProduct();

	if(!isset($_REQUEST['id']) || !$product->get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	if(isset($_REQUEST['id'])) {
		$product->delete();
	}

	redirect(sprintf("Location: ?action=products&id=%d", $product->searchKeywordId));
}

function add() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('term', 'Term', 'text', '', 'anything', 1, 240);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$search = new SearchKeyword();
			$search->term = $form->GetValue('term');
			$search->add();

			redirect('Location: ?action=view');
		}
	}

	$page = new Page('<a href="?action=view">Search Keywords</a> &gt; Add Keyword', 'Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add Keyword');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('term'), $form->GetHTML('term') . $form->GetIcon('term'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=view\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}


function addcategory() {
	$search = new SearchKeyword();

	if(!isset($_REQUEST['id']) || !$search->get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'addcategory', 'alpha', 11, 11);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Search Keyword ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()){
			if($form->GetValue('parent') == 0) {
				$form->AddError('Please select a category.', 'parent');
			}

			if($form->Valid) {
				$category = new SearchKeywordCategory();
				$category->searchKeyword->id = $search->id;
				$category->category->id = $form->GetValue('parent');
				$category->add();

				redirect(sprintf("Location: %s?action=categories&id=%d", $_SERVER['PHP_SELF'], $search->id));
			}
		}
	}

	$page = new Page(sprintf('<a href="?action=view">Search Keywords</a> &gt; <a href="?action=categories&id=%d">Categories</a> &gt; Add Category', $search->id), 'Add a category.');
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
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=categories&id=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $search->id, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function addproduct() {
	$search = new SearchKeyword();

	if(!isset($_REQUEST['id']) || !$search->get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'addproduct', 'alpha', 10, 10);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Search Keyword ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('product', 'Product ID', 'text', '', 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()){
			$product = new Product();

			if(!$product->Get($form->GetValue('product'))) {
				$form->AddError('Product ID does not exists.', 'product');
			}

			if($form->Valid) {
				$keywordProduct = new SearchKeywordProduct();
				$keywordProduct->searchKeyword->id = $search->id;
				$keywordProduct->product->id = $product->ID;
				$keywordProduct->add();

				redirect(sprintf("Location: %s?action=products&id=%d", $_SERVER['PHP_SELF'], $search->id));
			}
		}
	}

	$page = new Page(sprintf('<a href="?action=view">Search Keywords</a> &gt; <a href="?action=products&id=%d">Products</a> &gt; Add Product', $search->id), 'Add a product.');
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
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=products&id=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $search->id, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	$search = new SearchKeyword();

	if(!isset($_REQUEST['id']) || !$search->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('term', 'Term', 'text', $search->term, 'anything', 1, 240);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$search->term = $form->GetValue('term');
			$search->update();

			redirect('Location: ?action=view');
		}
	}

	$page = new Page('<a href="?action=view">Search Keywords</a> &gt; Update Keyword', 'Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Update Keyword');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('term'), $form->GetHTML('term') . $form->GetIcon('term'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=view\';" /> <input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	$page = new Page('Search Keywords', 'Listing all search keywords.');
	$page->Display('header');

	$table = new DataTable('keywords');
	$table->SetSQL('SELECT * FROM search_keyword');
	$table->AddField('ID#', 'id', 'left');
	$table->AddField('Term', 'term', 'left');
	$table->AddLink("?action=update&id=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "id");
	$table->AddLink("?action=categories&id=%s", "<img src=\"images/page_red_c.gif\" alt=\"View Categories\" border=\"0\">", "id");
	$table->AddLink("?action=products&id=%s", "<img src=\"images/page_red_p.gif\" alt=\"View Products\" border=\"0\">", "id");
	$table->AddLink("javascript:confirmRequest('?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("term");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
	
	echo '<br />';
	echo '<input type="button" name="add" value="add keyword" class="btn" onclick="window.location.href=\'?action=add\'" />';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function categories() {
	$search = new SearchKeyword();

	if(!isset($_REQUEST['id']) || !$search->get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$page = new Page('<a href="?action=view">Search Keywords</a> &gt; Categories', 'Manage categories.');
	$page->Display('header');

	$table = new DataTable('categories');
	$table->SetSQL(sprintf("SELECT skc.id, pc.Category_Title FROM search_keyword_category AS skc INNER JOIN product_categories AS pc ON pc.Category_ID=skc.categoryId WHERE skc.searchKeywordId=%d", $search->id));
	$table->AddField("ID#", "id");
	$table->AddField('Category', 'Category_Title', 'left');
	$table->SetMaxRows(25);
	$table->SetOrderBy("Category_Title");
	$table->AddLink("javascript:confirmRequest('?action=removecategory&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "id");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" name="add" value="add category" class="btn" onclick="window.location.href=\'?action=addcategory&id=%d\'" />', $search->id);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function products() {
	$search = new SearchKeyword();

	if(!isset($_REQUEST['id']) || !$search->get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$page = new Page('<a href="?action=view">Search Keywords</a> &gt; Products', 'Manage products.');
	$page->Display('header');

	$table = new DataTable('products');
	$table->SetSQL(sprintf("SELECT skp.id, p.Product_ID, p.Product_Title FROM search_keyword_product AS skp INNER JOIN product AS p ON p.Product_ID=skp.ProductID WHERE skp.searchKeywordId=%d", $search->id));
	$table->AddField("ID#", "id");
	$table->AddField('Quickfind ID', 'Product_ID', 'left');
	$table->AddField('Product', 'Product_Title', 'left');
	$table->SetMaxRows(25);
	$table->SetOrderBy("Product_Title");
	$table->AddLink("javascript:confirmRequest('?action=removeproduct&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "id");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" name="add" value="add product" class="btn" onclick="window.location.href=\'?action=addproduct&id=%d\'" />', $search->id);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}