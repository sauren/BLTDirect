<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductLinkLibrary.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'add'){
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'update'){
	$session->Secure(3);
	update();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	if(isset($_REQUEST['id'])) {
		$link = new ProductLinkLibrary();
		$link->delete($_REQUEST['id']);
	}
	
	redirectTo('?action=view');
}

function add() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('name', 'Name', 'text', '', 'paragraph', 1, 240);
	$form->AddField('url', 'URL', 'text', '', 'anything', null, null);
	$form->AddField('image', 'Image', 'file', '', 'file', null, null);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$link = new ProductLinkLibrary();
			$link->name = $form->GetValue('name');
			$link->url = $form->GetValue('url');

			if($link->attach('image')) {
				$link->add();

		   		redirectTo('?action=view');
			} else {
				for($i=0; $i<count($link->image->Errors); $i++) {
					$form->AddError($link->image->Errors[$i]);
				}
			}
		}
	}
	
	$page = new Page('<a href="?action=view">Product Link Library</a> &gt; Add Link', 'Add new product link.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}
	
	$window = new StandardWindow('Add Product Link.');
	$webForm = new StandardForm;
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	
	echo $window->Open();
	echo $window->AddHeader('Please complete the form below.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('url'), $form->GetHTML('url') . $form->GetIcon('url'));
	echo $webForm->AddRow($form->GetLabel('image'), $form->GetHTML('image') . $form->GetIcon('image'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location = \'?action=view\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	$link = new ProductLinkLibrary();
	
	if(!isset($_REQUEST['id']) || !$link->get($_REQUEST['id'])) {
		redirectTo('?action=view');
	}

	$link->asset->getMeta();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Product Link ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', $link->name, 'paragraph', 1, 240);
	$form->AddField('url', 'URL', 'text', $link->url, 'anything', null, null);
	$form->AddField('image', 'Image', 'file', '', 'file', null, null);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$link->name = $form->GetValue('name');
			$link->url = $form->GetValue('url');

			if($link->update('image')) {
		   		redirect(sprintf("Location: ?pid=%d", $link->product->ID));
			} else {
				for($i=0; $i<count($link->image->Errors); $i++) {
					$form->AddError($link->image->Errors[$i]);
				}
			}
		}
	}
	
	$page = new Page('<a href="?action=view">Product Link Library</a> &gt; Update Link', 'Update existing product link.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}
	
	$window = new StandardWindow('Update Product Link.');
	$webForm = new StandardForm;
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	
	echo $window->Open();
	echo $window->AddHeader('Please complete the form below. If no Thumbnail is specified the Image will be used.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('url'), $form->GetHTML('url') . $form->GetIcon('url'));
	
	if(!empty($link->asset->id)) {
		echo $webForm->AddRow('Current Image', sprintf('<img src="asset.php?hash=%s" />', $link->asset->hash));
	}
	
	echo $webForm->AddRow($form->GetLabel('image'), $form->GetHTML('image') . $form->GetIcon('image'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location = \'?action=view\';" /> <input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	$page = new Page('Product Link Library', 'Manage library product links here.');
	$page->Display('header');

	$table = new DataTable('links');
	$table->SetSQL(sprintf("SELECT * FROM product_link_library"));
	$table->AddField('Name', 'name', 'left');
	$table->AddField('URL', 'url', 'left');
	$table->AddLink('?action=update&id=%s', '<img src="images/icon_edit_1.gif" alt="Update" border="0" />', 'id');
	$table->AddLink('javascript:confirmRequest(\'?action=remove&id=%s\', \'Are you sure you want to remove this item?\');', '<img src="images/aztector_6.gif" alt="Remove" border="0" />', 'id');
	$table->SetMaxRows(25);
	$table->SetOrderBy('name');
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
	
	echo '<br />';
	echo sprintf('<input type="button" name="add" value="add new link" class="btn" onclick="window.location.href=\'?action=add\'" />');
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}