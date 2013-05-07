<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderSuggestionQuantity.php');
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
		$quantity = new OrderSuggestionQuantity();
		$quantity->delete($_REQUEST['id']);
	}
	
	redirectTo('?action=view');
}

function add() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('quantitybreakpoint', 'Quantity Break Point', 'text', '', 'numeric_unsigned', 1, 11);
	$form->AddField('quantitycosted', 'Quantity Costed', 'text', '', 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$quantity = new OrderSuggestionQuantity();
			$quantity->quantityBreakPoint = $form->GetValue('quantitybreakpoint');
			$quantity->quantityCosted = $form->GetValue('quantitycosted');
			$quantity->add();

			redirectTo('?action=view');
		}
	}
	
	$page = new Page('<a href="?action=view">Order Suggestion Quantity</a> &gt; Add Quantity', 'Add new suggestion quantity.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}
	
	$window = new StandardWindow('Add Quantity.');
	$webForm = new StandardForm;
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	
	echo $window->Open();
	echo $window->AddHeader('Please complete the form below.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('quantitybreakpoint'), $form->GetHTML('quantitybreakpoint') . $form->GetIcon('quantitybreakpoint'));
	echo $webForm->AddRow($form->GetLabel('quantitycosted'), $form->GetHTML('quantitycosted') . $form->GetIcon('quantitycosted'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location = \'?action=view\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	$quantity = new OrderSuggestionQuantity();
	
	if(!isset($_REQUEST['id']) || !$quantity->get($_REQUEST['id'])) {
		redirectTo('?action=view');
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Suggestion Quantity ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('quantitybreakpoint', 'Quantity Break Point', 'text', $quantity->quantityBreakPoint, 'numeric_unsigned', 1, 11);
	$form->AddField('quantitycosted', 'Quantity Costed', 'text', $quantity->quantityCosted, 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$quantity->quantityBreakPoint = $form->GetValue('quantitybreakpoint');
			$quantity->quantityCosted = $form->GetValue('quantitycosted');
			$quantity->update();

			redirectTo('?action=view');
		}
	}
	
	$page = new Page('<a href="?action=view">Order Suggestion Quantity</a> &gt; Update Link', 'Update existing suggestion quantity.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}
	
	$window = new StandardWindow('Update Suggestion Quantity.');
	$webForm = new StandardForm;
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	
	echo $window->Open();
	echo $window->AddHeader('Please complete the form below.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('quantitybreakpoint'), $form->GetHTML('quantitybreakpoint') . $form->GetIcon('quantitybreakpoint'));
	echo $webForm->AddRow($form->GetLabel('quantitycosted'), $form->GetHTML('quantitycosted') . $form->GetIcon('quantitycosted'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location = \'?action=view\';" /> <input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	$page = new Page('Order Suggestion Quantity', 'Manage quantity options here.');
	$page->Display('header');

	$table = new DataTable('quantity');
	$table->SetSQL(sprintf("SELECT * FROM order_suggestion_quantity"));
	$table->AddField('ID', 'id', 'left');
	$table->AddField('Quantity Break Point', 'quantityBreakPoint', 'left');
	$table->AddField('Quantity Costed', 'quantityCosted', 'left');
	$table->AddLink('?action=update&id=%s', '<img src="images/icon_edit_1.gif" alt="Update" border="0" />', 'id');
	$table->AddLink('javascript:confirmRequest(\'?action=remove&id=%s\', \'Are you sure you want to remove this item?\');', '<img src="images/aztector_6.gif" alt="Remove" border="0" />', 'id');
	$table->SetMaxRows(25);
	$table->SetOrderBy('quantityBreakPoint');
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
	
	echo '<br />';
	echo sprintf('<input type="button" name="add" value="add new quantity" class="btn" onclick="window.location.href=\'?action=add\'" />');
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}