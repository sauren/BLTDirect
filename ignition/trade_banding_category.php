<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TradeBandingCategory.php');

if($action == 'add') {
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'update') {
	$session->Secure(3);
	update();
	exit;
} elseif($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$banding = new TradeBandingCategory();
		$banding->delete($_REQUEST['id']);
	}

	redirect('Location: ?action=view');
}

function add() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('markup', 'Markup', 'text', '', 'numeric_unsigned', 1, 11);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$banding = new TradeBandingCategory();
			$banding->category->ID = $form->GetValue('parent');
			$banding->markup = $form->GetValue('markup');
			$banding->add();
			
			redirect('Location: ?action=view');
		}
	}

	$page = new Page('<a href="?action=view">Trade Banding Category</a> &gt; Add Category', 'Add a new category for trade banding exclusions.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Adding a category');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	
	echo $window->Open();
	echo $window->AddHeader('Enter category details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('parent') . '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', '<span id="parentCaption">None</span>' . $form->GetHTML('parent') . $form->GetIcon('parent'));
	echo $webForm->AddRow($form->GetLabel('markup'), $form->GetHTML('markup') . $form->GetIcon('markup'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=view\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	$banding = new TradeBandingCategory();
	
	if(isset($_REQUEST['id']) && !$banding->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	$banding->category->Get();
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Banding ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('parent', 'Category', 'hidden', $banding->category->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('markup', 'Markup', 'text', $banding->markup, 'numeric_unsigned', 1, 11);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$banding->category->ID = $form->GetValue('parent');
			$banding->markup = $form->GetValue('markup');
			$banding->update();
			
			redirect('Location: ?action=view');
		}
	}

	$page = new Page('<a href="?action=view">Trade Banding Category</a> &gt; Update Category', 'Update existing category for trade banding exclusions.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Update category');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	
	echo $window->Open();
	echo $window->AddHeader('Enter category details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('parent') . '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', '<span id="parentCaption">' . $banding->category->Name . '</span>' . $form->GetHTML('parent') . $form->GetIcon('parent'));
	echo $webForm->AddRow($form->GetLabel('markup'), $form->GetHTML('markup') . $form->GetIcon('markup'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=view\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	$page = new Page('Trade Banding Category', 'Listing category exclusions from trade banding.');
	$page->Display('header');

	$table = new DataTable('tradebanding');
	$table->SetSQL("SELECT tbc.*, pc.Category_Title FROM trade_banding_category AS tbc INNER JOIN product_categories AS pc ON pc.Category_ID=tbc.categoryId");
	$table->AddField("ID#", "id");
	$table->AddField("Category", "Category_Title");
	$table->AddField("Markup", "markup");
	$table->AddLink("?action=update&id=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "id");
	$table->AddLink("javascript:confirmRequest('?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Category_Title");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" name="add" value="add category" class="btn" onclick="window.location.href=\'?action=add\'" />';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}