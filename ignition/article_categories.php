<?php
require_once('lib/common/app_header.php');

if($action == "add"){
	$session->Secure(3);
	add();
	exit;
} elseif($action == "update"){
	$session->Secure(3);
	update();
	exit;
} elseif($action == "remove"){
	$session->Secure(3);
	remove();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}


function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ArticleCategory.php');

	$ac = new ArticleCategory;
	$ac->Delete($_REQUEST['aci']);

	redirect("Location: article_categories.php");
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ArticleCategory.php');

	$form = new Form("article_categories.php");
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('title', 'Title', 'text', '', 'anything', 1, 62);
	$form->AddField('active', 'Display Article Category', 'checkbox', 'Y', 'boolean', 1, 1, false);
	$form->AddField('metaTitle', 'Meta Title', 'text', '', 'anything', 1, 255, false);
	$form->AddField('metaKeywords', 'Meta Keywords', 'text', '', 'anything', 1, 255, false, 'style="width:100%;"');
	$form->AddField('metaDescription', 'Meta Description', 'textarea', '', 'anything', 1, 255, false, 'style="width:100%; height:150px;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$ac = new ArticleCategory;
			$ac->Name = $form->GetValue('title');
			$ac->MetaTitle = $form->GetValue('metaTitle');
			$ac->MetaKeywords = $form->GetValue('metaKeywords');
			$ac->MetaDescription = $form->GetValue('metaDescription');
			$ac->IsActive = $form->GetValue('active');
			$ac->Add();

			redirect("Location: article_categories.php");
		}
	}

	$page = new Page('Add Article Category','Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Add');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
	echo $webForm->AddRow($form->GetLabel('metaTitle'), $form->GetHTML('metaTitle') . $form->GetIcon('metaTitle'));
	echo $webForm->AddRow($form->GetLabel('metaKeywords'), $form->GetHTML('metaKeywords') . $form->GetIcon('metaKeywords'));
	echo $webForm->AddRow($form->GetLabel('metaDescription'), $form->GetHTML('metaDescription') . $form->GetIcon('metaDescription'));

	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'article_categories.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ArticleCategory.php');

	$ac = new ArticleCategory($_REQUEST['aci']);

	$form = new Form("article_categories.php");
	$form->AddField('action', 'Action', 'hidden', 'update', 'update', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('aci', 'Article Category ID', 'hidden', 'true', 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Title', 'text', $ac->Name, 'anything', 1, 255);
	$form->AddField('active', 'Display Article Category', 'checkbox', $ac->IsActive, 'boolean', 1, 1, false);
	$form->AddField('metaTitle', 'Meta Title', 'text', $ac->MetaTitle, 'anything', 1, 255, false);
	$form->AddField('metaKeywords', 'Meta Keywords', 'text', $ac->MetaKeywords, 'anything', 1, 255, false, 'style="width:100%;"');
	$form->AddField('metaDescription', 'Meta Description', 'textarea', $ac->MetaDescription, 'anything', 1, 255, false, 'style="width:100%; height:150px;"');

	// Check if the form has been submitted
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			// Hurrah! Create a new entry.
			$ac->Name = $form->GetValue('title');
			$ac->MetaTitle = $form->GetValue('metaTitle');
			$ac->MetaKeywords = $form->GetValue('metaKeywords');
			$ac->MetaDescription = $form->GetValue('metaDescription');
			$ac->IsActive = $form->GetValue('active');
			$ac->Update();
			redirect("Location: article_categories.php");
			exit;
		}
	}

	$page = new Page('Update Article Category','Please complete the form below.');
	$page->Display('header');

	// Show Error Report if Form Object validation fails
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Update');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('aci');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
	echo $webForm->AddRow($form->GetLabel('metaTitle'), $form->GetHTML('metaTitle') . $form->GetIcon('metaTitle'));
	echo $webForm->AddRow($form->GetLabel('metaKeywords'), $form->GetHTML('metaKeywords') . $form->GetIcon('metaKeywords'));
	echo $webForm->AddRow($form->GetLabel('metaDescription'), $form->GetHTML('metaDescription') . $form->GetIcon('metaDescription'));

	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'article_categories.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
	require_once('lib/common/app_footer.php');

}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('My Website Article Categories','This area allows you to maintain articles published on your website under one or more categories.');
	$page->Display('header');
	$table = new DataTable('categories');
	$table->SetSQL("select * from article_category");
	$table->AddField('ID#', 'Article_Category_ID', 'right');
	$table->AddField('Category', 'Category_Title', 'left');
	$table->AddField('Displayed', 'Is_Active', 'left');

	$table->AddLink("articles.php?action=view&aci=%s",
	"<img src=\"./images/folderopen.gif\" alt=\"Open and View Articles for this Category\" border=\"0\">",
	"Article_Category_ID");
	$table->AddLink("article_categories.php?action=update&aci=%s",
	"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Settings\" border=\"0\">",
	"Article_Category_ID");
	$table->AddLink("javascript:confirmRequest('article_categories.php?action=remove&confirm=true&aci=%s','Are you sure you want to remove this Article Category?');",
	"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
	"Article_Category_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Category_Title");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo "<br>";
	echo '<input type="button" name="add" value="add a new category" class="btn" onclick="window.location.href=\'article_categories.php?action=add\'">';
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>