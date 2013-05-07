<?php
require_once('lib/common/app_header.php');

if($action == "add"){
	$session->Secure(3);
	add();
	exit;
} elseif($action == "addbanner"){
	$session->Secure(3);
	addBanner();
	exit;
} elseif($action == "update"){
	$session->Secure(3);
	update();
	exit;
} elseif($action == "updatebanner"){
	$session->Secure(3);
	updateBanner();
	exit;
} elseif($action == "remove"){
	$session->Secure(3);
	remove();
	exit;
} elseif($action =="removebanner"){
	$session->Secure(3);
	removeBanner();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Pages.php');

	if(isset($_REQUEST['page'])) {
		$page = new Pages($_REQUEST['page']);
		$page->Delete();

		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}
}

function removeBanner(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Pages.php');

	if(isset($_REQUEST['page']) && isset($_REQUEST['banner'])){
		$banner = new PagesBanners($_REQUEST['banner']);
		$banner->Delete();

		redirect(sprintf("Location: %s?page=%s&action=update", $_SERVER['PHP_SELF'], $_REQUEST['page']));
	}
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Pages.php');

	$article = new Article;
	$article->Category->Get($_REQUEST['aci']);

	$form = new Form("articles.php");
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('aci', 'Article Category ID', 'hidden', $article->Category->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Title', 'text', '', 'anything', 1, 255);
	$form->AddField('description', 'Description', 'textarea', '', 'anything', 1, 2000, false, 'style="width:100%; height:250px;"');
	$form->AddField('metaTitle', 'Meta Title', 'text', '', 'anything', 1, 255, false);
	$form->AddField('metaKeywords', 'Meta Keywords', 'text', '', 'anything', 1, 255, false, 'style="width:100%;"');
	$form->AddField('metaDescription', 'Meta Description', 'textarea', '', 'anything', 1, 255, false, 'style="width:100%; height:150px;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$article->Description = $form->GetValue('description');
			$article->Name = $form->GetValue('title');
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

function addBanner(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Pages.php');

	$banner = new PagesBanners();

	$form = new Form("pages.php");
	$form->AddField('action', 'Action', 'hidden', 'addbanner', 'alpha', 9, 9);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('page', 'Page ID', 'hidden', $_REQUEST['page'], 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Title', 'text', '', 'anything', 1, 62);
	$form->AddField('file', 'File', 'file', '', 'file', NULL, NULL);
	$form->AddField('colour', 'Background Colour', 'text', '', 'anything', 3, 6);
	$form->AddField('link', 'Link', 'text', '', 'anything', 1, NULL);
	$form->AddField('startOn', 'Start Date (dd/mm/yyyy)', 'text', $date, 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('endOn', 'End Date (dd/mm/yyyy)', 'text', $date, 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');

	// Check if the form has been submitted
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			// Hurrah! Create a new entry.
			$banner->Title = $form->GetValue('title');
			$banner->Colour = $form->GetValue('colour');
			$banner->Link = $form->GetValue('link');
			$banner->PageID = $form->GetValue('page');
			$banner->StartOn = $form->GetValue('startOn');
			$banner->EndOn = $form->GetValue('endOn');

			if(empty($banner->StartOn)){
				$banner->StartOn = '0000-00-00';
			}
			if(empty($banner->EndOn)){
				$banner->EndOn = '0000-00-00';
			}

			if($banner->StartOn != '0000-00-00'){
				$banner->StartOn = date('Y-m-d', strtotime(str_replace('/', '-', $banner->StartOn)));
			}
			if($banner->EndOn != '0000-00-00'){
				$banner->EndOn = date('Y-m-d', strtotime(str_replace('/', '-', $banner->EndOn)));
			}
		
			if($banner->Add('file')) {
				redirect(sprintf("Location: pages.php?page=%s&action=update", $banner->PageID));
			} else {
				for($i = 0; $i < count($banner->File->Errors); $i++) {
					$form->AddError($banner->File->Errors[$i]);
				}
			}
		}
	}

	$page = new Page(sprintf('<a href="pages.php">My Website Pages</a> &gt; <a href="pages.php?action=update&page=%s">Update Article</a> &gt; Add Banner', $pages->ID),'Please complete the form below.');
	$page->LinkCSS('css/colorpicker.css');
	$page->LinkScript('../js/jquery.js');
	$page->LinkScript('../js/scw.js');
	$page->LinkScript('js/colorpicker.js');
	$script = '<script>
jQuery(function($) {
	// Color picker
	var picker = $("#colour");

	picker.ColorPicker({
		onSubmit: function(hsb, hex, rgb, el) {
			$(el).val(hex);
			$(el).ColorPickerHide();
		},
		onBeforeShow: function () {
			$(this).ColorPickerSetColor(this.value);
		},
		onChange: function(hsb, hex, rgb) {
			picker.val(hex);
		}
	});
});
</script>';
	$page->AddToHead($script);
	$page->Display('header');

	$script = sprintf('<script src="%s"></script>', $GLOBALS["DIR_WS_ROOT"] . 'js/jquery.js');
	$script .= sprintf('<script src="%s"></script>', $GLOBALS["DIR_WS_ADMIN"] . 'js/colorpicker.js');

	$page->AddToHead($script);

	// Show Error Report if Form Object validation fails
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Add');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('page');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('file'), $form->GetHTML('file') . $form->GetIcon('file'));
	echo $webForm->AddRow($form->GetLabel('colour'), $form->GetHTML('colour') . $form->GetIcon('colour'));
	echo $webForm->AddRow($form->GetLabel('link'), $form->GetHTML('link') . $form->GetIcon('link'));
	echo $webForm->AddRow($form->GetLabel('startOn'), $form->GetHTML('startOn') . $form->GetIcon('startOn'));
	echo $webForm->AddRow($form->GetLabel('endOn'), $form->GetHTML('endOn') . $form->GetIcon('endOn'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'pages.php?page=%s&action=update\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $pages->ID, $form->GetTabIndex()));
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Pages.php');

	$pages = new Pages($_REQUEST['page']);

	$form = new Form("pages.php");
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('page', 'Page ID', 'hidden', $pages->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Title', 'text', $pages->Name, 'anything', 1, 255);
	$form->AddField('description', 'Description', 'textarea', $pages->Description, 'anything', 1, 2000, false, 'style="width:100%; height:250px;"');
	$form->AddField('metaTitle', 'Meta Title', 'text', $pages->MetaTitle, 'anything', 1, 255, false);
	$form->AddField('metaKeywords', 'Meta Keywords', 'text', $pages->MetaKeywords, 'anything', 1, 255, false, 'style="width:100%;"');
	$form->AddField('metaDescription', 'Meta Description', 'textarea', $pages->MetaDescription, 'anything', 1, 255, false, 'style="width:100%; height:150px;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$pages->Name = $form->GetValue('title');
			$pages->Description = $form->GetValue('description');
			$pages->MetaTitle = $form->GetValue('metaTitle');
			$pages->MetaKeywords = $form->GetValue('metaKeywords');
			$pages->MetaDescription = $form->GetValue('metaDescription');
			$pages->Update();

			redirect("Location: pages.php");
		}
	}

	$page = new Page('<a href="pages.php">My Website Pages</a> &gt; Update Page','Please complete the form below.');
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
	echo $form->GetHTML('page');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $form->GetLabel('title') . $form->GetIcon('title') . "<br />" . $form->GetHTML('title') . "<br /><br />";
	echo $form->GetLabel('description') . $form->GetIcon('description') . "<br />" . $form->GetHTML('description') . "<br /><br />";
	echo $window->CloseContent();
	echo $window->AddHeader('Meta Information for this Page');
	echo $window->OpenContent();
	echo $form->GetLabel('metaTitle') . $form->GetIcon('metaTitle') . "<br />" . $form->GetHTML('metaTitle') . "<br /><br />";
	echo $form->GetLabel('metaKeywords') . $form->GetIcon('metaKeywords') . "<br />" . $form->GetHTML('metaKeywords') . "<br /><br />";
	echo $form->GetLabel('metaDescription') . $form->GetIcon('metaDescription') . "<br />" . $form->GetHTML('metaDescription') . "<br /><br />";

	echo sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'pages.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex());
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	echo '</td><td style="width:15px">&nbsp;</td><td valign="top" style="width:35%">';

	$dlWin = new StandardWindow('Banners for this Page');
	echo $dlWin->Open();
	echo $dlWin->AddHeader('Add, Edit or Remove');
	echo $dlWin->OpenContent();

	$pages->GetBanners();
	echo '<table border="0" cellpadding="5" cellspacing="0" width="100%" class="DataTable">';
	if(count($pages->Banners) > 0){
		for($i=0; $i < count($pages->Banners); $i++){
			echo sprintf('<tr>
				<td style="border-bottom:0px;">%s<br />(%s)</td>
				<td style="border-bottom:0px; width:40px;">
					<a href="pages.php?banner=%d&page=%d&action=updatebanner">
						<img src="./images/icon_edit_1.gif" alt="Update" border="0" />
					</a>
					<a href="javascript:confirmRequest(\'pages.php?banner=%d&page=%d&action=removebanner\', \'Are you sure you would like to remove this banner?\');">
						<img src="./images/aztector_6.gif" alt="Remove" border="0" />
					</a>
				</td>
			</tr>
			<tr><td colspan="2" style="padding-top:0px; border-top:0px;">[%s - %s]</td></tr>',
			$pages->Banners[$i]->Title,
			$pages->Banners[$i]->File->Name,
			$pages->Banners[$i]->ID,
			$pages->ID,
			$pages->Banners[$i]->ID,
			$pages->ID,
			$pages->Banners[$i]->StartOn,
			$pages->Banners[$i]->EndOn);
		}
	} else {
		echo "<p>No Downloads</p>";
	}
	echo '</table><br />';
	echo sprintf('<input type="button" name="add banner" value="add banner" class="btn" tabindex="%s" onclick="window.location.href=\'pages.php?action=addbanner&page=%s\';">', $form->GetTabIndex(), $pages->ID);


	echo $dlWin->CloseContent();
	echo $dlWin->Close();

	echo '</td></tr></table>';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function updateBanner(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Pages.php');

	$banner = new PagesBanners($_REQUEST['banner']);


	if($banner->StartOn == '0000-00-00'){
		$banner->StartOn = '';
	}
	if($banner->EndOn == '0000-00-00'){
		$banner->EndOn = '';
	}
	if($banner->StartOn != ''){
		$banner->StartOn = date('d/m/Y', strtotime(str_replace('-', '/', $banner->StartOn)));
	}
	if($banner->EndOn != ''){
		$banner->EndOn = date('d/m/Y', strtotime(str_replace('-', '/', $banner->EndOn)));
	}

	$form = new Form("pages.php");
	$form->AddField('action', 'Action', 'hidden', 'updatebanner', 'alpha', 12, 12);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('page', 'Page ID', 'hidden', $_REQUEST['page'], 'numeric_unsigned', 1, 11);
	$form->AddField('banner', 'Banner ID', 'hidden', $_REQUEST['banner'], 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Title', 'text', $banner->Title, 'anything', 1, 62);
	$form->AddField('oldFile', 'Old File', 'text', $banner->File->FileName, 'anything', NULL, NULL, false, 'disabled="disabled"');
	$form->AddField('file', 'File', 'file', $banner->File->FileName, 'file', NULL, NULL, false);
	$form->AddField('colour', 'Background Colour', 'text', $banner->Colour, 'anything', 3, 6);
	$form->AddField('link', 'Link', 'text', $banner->Link, 'anything', 1, NULL);
	$form->AddField('startOn', 'Start Date (dd/mm/yyyy)', 'text', $banner->StartOn, 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('endOn', 'End Date (dd/mm/yyyy)', 'text', $banner->EndOn, 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');

	// Check if the form has been submitted
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			// Hurrah! Create a new entry.
			$banner->Title = $form->GetValue('title');
			$banner->Colour = $form->GetValue('colour');
			$banner->Link = $form->GetValue('link');
			$banner->PageID = $form->GetValue('page');
			$banner->StartOn = $form->GetValue('startOn');
			$banner->EndOn = $form->GetValue('endOn');

			if(empty($banner->StartOn)){
				$banner->StartOn = '0000-00-00';
			}
			if(empty($banner->EndOn)){
				$banner->EndOn = '0000-00-00';
			}

			if($banner->StartOn != '0000-00-00'){
				$banner->StartOn = date('Y-m-d', strtotime(str_replace('/', '-', $banner->StartOn)));
			}
			if($banner->EndOn != '0000-00-00'){
				$banner->EndOn = date('Y-m-d', strtotime(str_replace('/', '-', $banner->EndOn)));
			}

			if($banner->Update('file')) {
				redirect(sprintf("Location: pages.php?page=%s&action=update", $banner->PageID));
			} else {
				for($i = 0; $i < count($banner->File->Errors); $i++) {
					$form->AddError($banner->File->Errors[$i]);
				}
			}
		}
	}


	$page = new Page(sprintf('<a href="pages.php">My Website Pages</a> &gt; <a href="pages.php?action=update&page=%s">Update Page</a> &gt; Update Banner', $pages->ID),'Please complete the form below.');
	$page->LinkCSS('css/colorpicker.css');
	$page->LinkScript('../js/jquery.js');
	$page->LinkScript('../js/scw.js');
	$page->LinkScript('js/colorpicker.js');
	$script = '<script>
jQuery(function($) {
	// Color picker
	var picker = $("#colour");

	picker.ColorPicker({
		onSubmit: function(hsb, hex, rgb, el) {
			$(el).val(hex);
			$(el).ColorPickerHide();
		},
		onBeforeShow: function () {
			$(this).ColorPickerSetColor(this.value);
		},
		onChange: function(hsb, hex, rgb) {
			picker.val(hex);
		}
	});
});
</script>';
	$page->AddToHead($script);
	$page->Display('header');

	// Show Error Report if Form Object validation fails
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Add');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('page');
	echo $form->GetHTML('banner');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('oldFile'), $form->GetHTML('oldFile') . $form->GetIcon('oldFile'));
	echo $webForm->AddRow($form->GetLabel('file'), $form->GetHTML('file') . $form->GetIcon('file'));
	echo $webForm->AddRow($form->GetLabel('colour'), $form->GetHTML('colour') . $form->GetIcon('colour'));
	echo $webForm->AddRow($form->GetLabel('link'), $form->GetHTML('link') . $form->GetIcon('link'));

	echo $webForm->AddRow($form->GetLabel('startOn'), $form->GetHTML('startOn') . $form->GetIcon('startOn'));
	echo $webForm->AddRow($form->GetLabel('endOn'), $form->GetHTML('endOn') . $form->GetIcon('endOn'));

	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'pages.php?page=%s&action=update\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $pages->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('<a href="#">My Website Pages</a> ','This area allows you to maintain web pages on your site.');
	$page->Display('header');
	$table = new DataTable('articles');
	$table->SetSQL("select * from pages");
	$table->AddField('ID#', 'Page_ID', 'right');
	$table->AddField('Page Title', 'Page_Title', 'left');
	$table->AddLink("pages.php?action=update&page=%s",
	"<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">",
	"Page_ID");
	$table->AddLink("javascript:confirmRequest('pages.php?action=remove&confirm=true&page=%s','Are you sure you want to remove this Page?');",
	"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
	"Page_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Page_Title");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo "<br>";
	echo sprintf('<input type="button" name="add" value="add a new page" class="btn" onclick="window.location.href=\'pages.php?action=add\'">', $ac->ID);
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>