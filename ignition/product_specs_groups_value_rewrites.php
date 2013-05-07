<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValue.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValueRewrite.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

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
	if(isset($_REQUEST['rewriteid']) && is_numeric($_REQUEST['rewriteid'])){
		$item = new ProductSpecValueRewrite($_REQUEST['rewriteid']);
		$item->delete();

		redirectTo(sprintf('?valueid=%d', $item->valueId));
	}

	redirectTo('product_spec_groups.php');
}

function add() {
	$value = new ProductSpecValue();
	
	if(!isset($_REQUEST['valueid']) || !$value->Get($_REQUEST['valueid'])) {
		redirectTo('product_spec_groups.php');
	}
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('valueid', 'Value ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('rewrite', 'Rewrite', 'text', '', 'paragraph', 0, 255, false);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$item = new ProductSpecValueRewrite();
			$item->value->ID = $value->ID;
			$item->rewrite = $form->GetValue('rewrite');
			$item->add();

			redirect(sprintf('Location: ?valueid=%d', $value->ID));
		}
	}

	$page = new Page(sprintf('<a href="product_specs_groups.php">Product Specification Groups</a> &gt; <a href="product_specs_groups_values.php?group=%d">Product Specification Group Values</a> &gt; <a href="?valueid=%d">Rewrites</a> &gt; Add Rewrite', $value->Group->ID, $value->ID), 'Add a rewrite to this specification value.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add rewrite');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('valueid');
	
	echo $window->Open();
	echo $window->AddHeader('Add specification group value');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('rewrite'), $form->GetHTML('rewrite'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?valueid=%d\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $value->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update(){
	$item = new ProductSpecValueRewrite();
	
	if(!isset($_REQUEST['rewriteid']) || !$item->Get($_REQUEST['rewriteid'])) {
		redirectTo('product_spec_groups.php');
	}
	
	$value = new ProductSpecValue();
	
	if(!$value->Get($item->valueId)) {
		redirectTo('product_spec_groups.php');
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('rewriteid', 'Rewrite ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('rewrite', 'Rewrite', 'text', $item->rewrite, 'paragraph', 0, 60, false);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$item->rewrite = $form->GetValue('rewrite');
			$item->update();

			redirect(sprintf('Location: ?valueid=%d', $item->valueId));
		}
	}

	$page = new Page(sprintf('<a href="product_specs_groups.php">Product Specification Groups</a> &gt; <a href="product_specs_groups_values.php?group=%d">Product Specification Group Values</a> &gt; <a href="?valueid=%d">Rewrites</a> &gt; Update Rewrite', $value->Group->ID, $value->ID), 'Update an existing rewrite for this specification value.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Update rewrite');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('rewriteid');
	
	echo $window->Open();
	echo $window->AddHeader('Update specification group value');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('rewrite'), $form->GetHTML('rewrite'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'?valueid=%d\';" /> <input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $item->valueId, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	$value = new ProductSpecValue();
	
	if(!isset($_REQUEST['valueid']) || !$value->Get($_REQUEST['valueid'])) {
		redirectTo('product_spec_groups.php');
	}

	$page = new Page(sprintf('<a href="product_specs_groups.php">Product Specification Groups</a> &gt; <a href="product_specs_groups_values.php?group=%d">Product Specification Group Values</a> &gt; Rewrites', $value->Group->ID), 'Manage rewrites for this specification group value.');
	$page->Display('header');

	$table = new DataTable('rewrites');
	$table->SetSQL(sprintf("SELECT * FROM product_specification_value_rewrite WHERE valueId=%d", $value->ID));
	$table->AddField('ID#', 'id', 'left');
	$table->AddField('Rewrite', 'valueRewrite', 'left');
	$table->AddLink("?action=update&rewriteid=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Update Value\" border=\"0\">", "id");
	$table->AddLink("javascript:confirmRequest('?action=remove&rewriteid=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy('valueRewrite');
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo sprintf('<input type="button" name="add" value="add rewrite" class="btn" onclick="window.location.href=\'?action=add&valueid=%d\'" /> ', $value->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}