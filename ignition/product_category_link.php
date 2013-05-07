<?php
require_once('lib/common/app_header.php');

if($action == "remove"){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == "add"){
	$session->Secure(3);
	add();
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CategoryLink.php');

	if(isset($_REQUEST['id'])) {
		$link = new CategoryLink();
		$link->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s?cat=%d", $_SERVER['PHP_SELF'], $_REQUEST['cat']));
}

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CategoryLink.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$category = new Category();

	if(!isset($_REQUEST['cat']) || !$category->Get($_REQUEST['cat'])) {
		redirect(sprintf("Location: product_categories.php"));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cat', 'cat', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('parent', 'Parent', 'hidden', '', 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$link = new CategoryLink();
			$link->CategoryID = $category->ID;
			$link->LinkedID = $form->GetValue('parent');
			$link->Add();

			redirect(sprintf("Location: %s?cat=%d", $_SERVER['PHP_SELF'], $category->ID));
		}
	}

	$page = new Page(sprintf('<a href="%s?cat=%d">Linked Categories: %s</a> &gt; Add Linked Category', $_SERVER['PHP_SELF'], $category->ID, $category->Name));
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
	echo $form->GetHTML('cat');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('parent') . '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', sprintf("<span id=\"parentCaption\"></span>") . $form->GetHTML('parent') . $form->GetIcon('parent'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_categories.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');

	$category = new Category();

	if(!isset($_REQUEST['cat']) || !$category->Get($_REQUEST['cat'])) {
		redirect(sprintf("Location: product_categories.php"));
	}

	$page = new Page(sprintf("Linked Categories: %s", $category->Name));
	$page->Display('header');

	$table = new DataTable('categories');
	$table->SetSQL(sprintf("SELECT pcl.*, pc.Category_Title FROM product_category_link AS pcl INNER JOIN product_categories AS pc ON pc.Category_ID=pcl.Linked_Category_ID WHERE pcl.Category_ID=%d", $category->ID));
	$table->AddField('ID#', 'Category_Link_ID', 'right');
	$table->AddField('Linked Category', 'Category_Title', 'left');
	$table->AddLink("javascript:confirmRequest('product_category_link.php?action=remove&cat=" . $category->ID . "&id=%s', 'Are you sure you want to remove this category?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Category_Link_ID");
	$table->SetMaxRows(25);
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" value="add category" class="btn" onclick="window.self.location.href = \'%s?action=add&cat=%d\';" name="link category" />', $_SERVER['PHP_SELF'], $category->ID);

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}
?>