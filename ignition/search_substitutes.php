<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SearchSubstitute.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

if($action == "add") {
	$session->Secure(3);
	add();
	exit;
} elseif($action == "update") {
	$session->Secure(3);
	update();
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
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$search = new SearchSubstitute();
		$search->delete($_REQUEST['id']);
	}

	redirect('Location: ?action=view');
}

function add() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('term', 'Term', 'text', '', 'anything', 1, 240);
	$form->AddField('replacement', 'Replacement', 'text', '', 'anything', 1, 240);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$search = new SearchSubstitute();
			$search->term = $form->GetValue('term');
			$search->replacement = $form->GetValue('replacement');
			$search->add();

			redirect('Location: ?action=view');
		}
	}

	$page = new Page('<a href="?action=view">Search Substitutes</a> &gt; Add Substitute', 'Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add Substitute');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('term'), $form->GetHTML('term') . $form->GetIcon('term'));
	echo $webForm->AddRow($form->GetLabel('replacement'), $form->GetHTML('replacement') . $form->GetIcon('replacement'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=view\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	$search = new SearchSubstitute();

	if(!isset($_REQUEST['id']) || !$search->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('term', 'Term', 'text', $search->term, 'anything', 1, 240);
	$form->AddField('replacement', 'Replacement', 'text', $search->replacement, 'anything', 1, 240);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$search->term = $form->GetValue('term');
			$search->replacement = $form->GetValue('replacement');
			$search->update();

			redirect('Location: ?action=view');
		}
	}

	$page = new Page('<a href="?action=view">Search Substitutes</a> &gt; Update Substitute', 'Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Update Substitute');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('term'), $form->GetHTML('term') . $form->GetIcon('term'));
	echo $webForm->AddRow($form->GetLabel('replacement'), $form->GetHTML('replacement') . $form->GetIcon('replacement'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=view\';" /> <input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	$page = new Page('Search Substitute', 'Listing all search substitutes.');
	$page->Display('header');

	$table = new DataTable('substitutes');
	$table->SetSQL('SELECT * FROM search_substitute');
	$table->AddField('ID#', 'id', 'left');
	$table->AddField('Term', 'term', 'left');
	$table->AddField('Replacement', 'replacement', 'left');
	$table->AddLink("?action=update&id=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "id");
	$table->AddLink("javascript:confirmRequest('?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("term");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
	
	echo '<br />';
	echo '<input type="button" name="add" value="add new substitute" class="btn" onclick="window.location.href=\'?action=add\'" />';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}