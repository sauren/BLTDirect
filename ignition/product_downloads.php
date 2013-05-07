<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductDownload.php');
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

function remove() {
	if(isset($_REQUEST['id'])) {
		$item = new ProductDownload();
		
		if($item->get($_REQUEST['id'])) {
			$item->delete();
		
			redirectTo(sprintf('product_downloads.php?pid=%d', $item->productId));
		}
	} 
	
	redirectTo('product_search.php');
}

function add() {
	if(!isset($_REQUEST['pid'])) {
		redirectTo('product_search.php');
	}
	
	$item = new ProductDownload();
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('pid', 'Product ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', '', 'paragraph', 1, 120);
	$form->AddField('description', 'Description', 'textarea', '', 'anything', null, null, false, 'rows="10" style="width: 300px;"');
	$form->AddField('file', 'File', 'file', '', 'file', null, null);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$item->productId = $form->GetValue('pid');
			$item->name = $form->GetValue('name');
			$item->description = $form->GetValue('description');
			
			if($item->add('file')) {
				redirectTo(sprintf('product_downloads.php?pid=%d', $item->productId));
			} else {
				for($i=0; $i<count($item->file->Errors); $i++) {
					$form->AddError($item->file->Errors[$i], 'file');
				}	
			}
		}
	}
	
	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="?pid=%s">Product Downloads</a> &gt; Add Download', $form->GetValue('pid'), $form->GetValue('pid')), 'Add new item.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}
	
	$window = new StandardWindow("Adding new record.");
	$webForm = new StandardForm();
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('pid');
	
	echo $window->Open();
	echo $window->AddHeader('Please complete the form below. If no Thumbnail is specified the Image will be used.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow($form->GetLabel('file'), $form->GetHTML('file') . $form->GetIcon('file'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?pid=%d\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $form->GetValue('pid'), $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	if(!isset($_REQUEST['id'])) {
		redirectTo('product_search.php');
	}
	
	$item = new ProductDownload();
	
	if(!$item->get($_REQUEST['id'])) {
		redirectTo('product_search.php');
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', $item->name, 'paragraph', 1, 120);
	$form->AddField('description', 'Description', 'textarea', $item->description, 'anything', null, null, false, 'rows="10" style="width: 300px;"');
	$form->AddField('file', 'File', 'file', '', 'file', null, null, false);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$item->name = $form->GetValue('name');
			$item->description = $form->GetValue('description');
			
			if($item->update('file')) {
				redirectTo(sprintf('product_downloads.php?pid=%d', $item->productId));
			} else {
				for($i=0; $i<count($item->file->Errors); $i++) {
					$form->AddError($item->file->Errors[$i], 'file');
				}	
			}
		}
	}
	
	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="?pid=%s">Product Downloads</a> &gt; Update Download', $item->productId, $item->productId), 'Update existing item.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}
	
	$window = new StandardWindow("Updating existing record.");
	$webForm = new StandardForm();
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	
	echo $window->Open();
	echo $window->AddHeader('Please complete the form below. If no Thumbnail is specified the Image will be used.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow($form->GetLabel('file'), $form->GetHTML('file') . $form->GetIcon('file'));
	
	if(!empty($item->file->FileName)) {
		echo $webForm->AddRow('Current File', sprintf('<a href="%s%s">%s</a>', $GLOBALS['PRODUCT_DOWNLOAD_DIR_WS'], $item->file->FileName, $item->file->FileName));	
	}
	
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?pid=%d\';" /> <input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $item->productId, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	if(!isset($_REQUEST['pid'])) {
		redirectTo('product_search.php');
	}
		
	$product = new Product();
	
	if(!$product->Get($_REQUEST['pid'])) {
		redirectTo('product_search.php');
	}
	
	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; Product Downloads', $product->ID), 'Listing all product downloads.');
	$page->Display('header');
	
	$table = new DataTable('records');
	$table->SetSQL(sprintf("SELECT * FROM product_download WHERE productId=%d", $product->ID));
	$table->AddField('ID#', 'id', 'left');
	$table->AddField('Name', 'name', 'left');
	$table->AddField('File', 'file', 'left');
	$table->AddLink("?action=update&id=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Update\" border=\"0\" />", "id");
	$table->AddLink("javascript:confirmRequest('?action=remove&id=%s', 'Are you sure you want to remove this item?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\" />", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("name");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
	
	echo '<br />';
	echo sprintf('<input type="button" name="add" value="add new item" class="btn" onclick="window.location.href=\'?action=add&pid=%d\'" />', $product->ID);
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}