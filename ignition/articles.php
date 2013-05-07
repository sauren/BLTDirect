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
	if(isset($_REQUEST['article'])) {
		$data = new DataQuery(sprintf("SELECT Article_Category_ID FROM article WHERE Article_ID=%d", mysql_real_escape_string($_REQUEST['article'])));
		$aci = $data->Row['Article_Category_ID'];
		$data->Disconnect();

		$data = new DataQuery(sprintf("DELETE FROM article WHERE Article_ID=%d LIMIT 1", mysql_real_escape_string($_REQUEST['article'])));
		$data->Disconnect();

		redirect(sprintf("Location: %s?aci=%d", $_SERVER['PHP_SELF'], $aci));
	}
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Article.php');

	$article = new Article;
	$article->Category->Get($_REQUEST['aci']);

	$form = new Form("articles.php");
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('aci', 'Article Category ID', 'hidden', $article->Category->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Title', 'text', '', 'anything', 1, 255);
	$form->AddField('description', 'Description', 'textarea', '', 'anything', 1, 2000, true, 'style="width:100%; height:250px;"');

	$form->AddField('active', 'Display Article', 'checkbox', 'Y', 'boolean', 1, 1, false);
	$form->AddField('metaTitle', 'Meta Title', 'text', '', 'anything', 1, 255, false);
	$form->AddField('metaKeywords', 'Meta Keywords', 'text', '', 'anything', 1, 255, false, 'style="width:100%;"');
	$form->AddField('metaDescription', 'Meta Description', 'textarea', '', 'anything', 1, 255, false, 'style="width:100%; height:150px;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$article->Description = $form->GetValue('description');
			$article->Name = $form->GetValue('title');
			$article->IsActive = $form->GetValue('active');
			$article->MetaTitle = $form->GetValue('metaTitle');
			$article->MetaKeywords = $form->GetValue('metaKeywords');
			$article->MetaDescription = $form->GetValue('metaDescription');
			$article->Add();

			redirect("Location: articles.php?aci=". $article->Category->ID);
		}
	}

	$page = new Page(sprintf('<a href="article_categories.php">My Website Article Categories</a> &gt; <a href="articles.php?aci=%s">%s</a> &gt; Add Article', $article->Category->ID, $article->Category->Name),'Please complete the form below.');
	$page->SetEditor(true);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo $form->GetValue('confirm');
		echo "<br>";
	}

	$window = new StandardWindow('Add');
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
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->AddHeader('Meta Information for this Article');
	echo $window->OpenContent();
	echo $webForm->Open();
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Article.php');

	$article = new Article($_REQUEST['article']);
	$article->Category->Get();

	$form = new Form("articles.php");
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('aci', 'Article Category ID', 'hidden', $article->Category->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('article', 'Article ID', 'hidden', $article->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Title', 'text', $article->Name, 'anything', 1, 255);
	$form->AddField('active', 'Display Article', 'checkbox', $article->IsActive, 'boolean', 1, 1, false);
	$form->AddField('description', 'Description', 'textarea', $article->Description, 'anything', 1, 2000, true, 'style="width:100%; height:250px;"');
	$form->AddField('metaTitle', 'Meta Title', 'text', $article->MetaTitle, 'anything', 1, 255, false);
	$form->AddField('metaKeywords', 'Meta Keywords', 'text', $article->MetaKeywords, 'anything', 1, 255, false, 'style="width:100%;"');
	$form->AddField('metaDescription', 'Meta Description', 'textarea', $article->MetaDescription, 'anything', 1, 255, false, 'style="width:100%; height:150px;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$article->Description = $form->GetValue('description');
			$article->Name = $form->GetValue('title');
			$article->IsActive = $form->GetValue('active');
			$article->MetaTitle = $form->GetValue('metaTitle');
			$article->MetaKeywords = $form->GetValue('metaKeywords');
			$article->MetaDescription = $form->GetValue('metaDescription');
			$article->Update();

			redirect("Location: articles.php?aci=". $article->Category->ID);
		}
	}

	$page = new Page(sprintf('<a href="article_categories.php">My Website Article Categories</a> &gt; <a href="articles.php?aci=%s">%s</a> &gt; Update Article', $article->Category->ID, $article->Category->Name),'Please complete the form below.');
	$page->SetEditor(true);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo $form->GetValue('confirm');
		echo "<br>";
	}

	$window = new StandardWindow('Update');

	echo '<table border="0" cellspacing="0" cellpadding="0" style="width:100%"><tr><td>';
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('aci');
	echo $form->GetHTML('article');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $form->GetLabel('title') . $form->GetIcon('title') . "<br />" . $form->GetHTML('title') . "<br /><br />";
	echo $form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'). "<br /><br />";
	echo $form->GetLabel('description') . $form->GetIcon('description') . "<br />" . $form->GetHTML('description') . "<br /><br />";
	echo $window->CloseContent();
	echo $window->AddHeader('Meta Information for this Article');
	echo $window->OpenContent();
	echo $form->GetLabel('metaTitle') . $form->GetIcon('metaTitle') . "<br />" . $form->GetHTML('metaTitle') . "<br /><br />";
	echo $form->GetLabel('metaKeywords') . $form->GetIcon('metaKeywords') . "<br />" . $form->GetHTML('metaKeywords') . "<br /><br />";
	echo $form->GetLabel('metaDescription') . $form->GetIcon('metaDescription') . "<br />" . $form->GetHTML('metaDescription') . "<br /><br />";

	echo sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'articles.php?aci=%s\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $article->Category->ID, $form->GetTabIndex());
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	echo '</td><td style="width:15px">&nbsp;</td><td valign="top" style="width:35%">';

	$dlWin = new StandardWindow('Downloads for this Article');
	echo $dlWin->Open();
	echo $dlWin->AddHeader('Add, Edit or Remove');
	echo $dlWin->OpenContent();

	$article->GetDownloads();
	echo '<table border="0" cellpadding="5" cellspacing="0" width="100%" class="DataTable">';
	if(count($article->Download) > 0){
		for($i=0; $i < count($article->Download); $i++){
			echo sprintf('<tr><td>%s<br />(%s)</td><td><a href="javascript:confirmRequest(\'article_downloads.php?download=%d&action=remove\', \'Are you sure you would like to remove this download?\');"><img src="./images/aztector_6.gif" alt="Remove" border="0" /></a></td></tr>',
			$article->Download[$i]->Name,
			$article->Download[$i]->File->FileName,
			$article->Download[$i]->ID);
		}
	} else {
		echo "<p>No Downloads</p>";
	}
	echo '</table><br />';
	echo sprintf('<input type="button" name="add download" value="add download" class="btn" tabindex="%s" onclick="window.location.href=\'article_downloads.php?action=add&article=%s\';">', $form->GetTabIndex(), $article->ID);


	echo $dlWin->CloseContent();
	echo $dlWin->Close();

	echo '</td></tr></table>';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ArticleCategory.php');

	$ac = new ArticleCategory($_REQUEST['aci']);

	$page = new Page(sprintf('<a href="article_categories.php">My Website Article Categories</a> &gt; %s', $ac->Name),'This area allows you to maintain articles published within this category.');
	$page->Display('header');
	$table = new DataTable('articles');
	$table->SetSQL("select * from article WHERE Article_Category_ID=" . $_REQUEST['aci']);
	$table->AddField('ID#', 'Article_ID', 'right');
	$table->AddField('Article Title', 'Article_Title', 'left');
	$table->AddField('Displayed', 'Is_Active', 'left');
	$table->AddLink("articles.php?action=update&article=%s",
	"<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">",
	"Article_ID");
	$table->AddLink("javascript:confirmRequest('articles.php?action=remove&confirm=true&article=%s','Are you sure you want to remove this Article?');",
	"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
	"Article_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Article_Title");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo "<br>";
	echo sprintf('<input type="button" name="add" value="add a new article" class="btn" onclick="window.location.href=\'articles.php?action=add&aci=%s\'">', $ac->ID);
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>