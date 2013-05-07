<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SearchKeyword.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SearchKeywordCategory.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

if($action == "add") {
	$session->Secure(3);
	add();
	exit;
} elseif($action == "remove") {
	$session->Secure(3);
	remove();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	$category = new SearchKeywordCategory();

	if(!isset($_REQUEST['id']) || !$category->get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	if(isset($_REQUEST['id'])) {
		$category->delete();
	}

	redirect(sprintf("Location: ?cid=%d", $category->category->id));
}

function add() {
	$category = new Category();

	if(!isset($_REQUEST['cid']) || !$category->Get($_REQUEST['cid'])) {
		redirect(sprintf("Location: product_categories.php"));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cid', 'Category ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('term', 'Term', 'text', '', 'anything', 1, 240);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$search = new SearchKeyword();
			
			if(!$search->getByTerm($form->GetValue('term'))) {
				$search->add();
			}

			$searchCategory = new SearchKeywordCategory();
			$searchCategory->searchKeyword->id = $search->id;
			$searchCategory->category->id = $form->GetValue('cid');
			$searchCategory->add();

			redirect(sprintf('Location: ?cid=%d', $category->ID));
		}
	}

	$page = new Page(sprintf('<a href="product_categories.php">Product Categories</a> &gt; <a href="?cid=%d">Search Keywords for %s</a> &gt; Add Search Keyword', $category->ID, $category->Name), 'Please complete the form below.');
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
	echo $form->GetHTML('cid');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('term'), $form->GetHTML('term') . $form->GetIcon('term'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?cid=%d\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $category->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	$category = new Category();

	if(!isset($_REQUEST['cid']) || !$category->Get($_REQUEST['cid'])) {
		redirect(sprintf("Location: product_categories.php"));
	}

	$page = new Page(sprintf('<a href="product_categories.php">Product Categories</a> &gt; Search Keywords for %s', $category->Name), 'Manage search keywords for this category.');
	$page->Display('header');

	$table = new DataTable('keywords');
	$table->SetSQL(sprintf("SELECT skc.id, sk.term FROM search_keyword_category AS skc INNER JOIN search_keyword AS sk ON sk.id=skc.searchKeywordId WHERE skc.categoryId=%d", $category->ID));
	$table->AddField("ID#", "id");
	$table->AddField('Term', 'term', 'left');
	$table->SetMaxRows(25);
	$table->SetOrderBy("term");
	$table->AddLink("javascript:confirmRequest('?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "id");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" name="add" value="add term" class="btn" onclick="window.location.href=\'?action=add&cid=%d\'" />', $category->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}