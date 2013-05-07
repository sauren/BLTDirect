<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecificationCombine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecificationCombineValue.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

if($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'removevalue') {
	$session->Secure(3);
	removeValue();
	exit;
} elseif($action == 'add') {
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'addvalue') {
	$session->Secure(3);
	addValue();
	exit;
} elseif($action == 'update') {
	$session->Secure(3);
	update();
	exit;
} elseif($action == 'viewvalues') {
	$session->Secure(3);
	viewValues();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	if(isset($_REQUEST['id'])) {
		$combine = new ProductSpecificationCombine();
		$combine->delete($_REQUEST['id']);
	}

	redirect('Location: ?action=view');
}

function removeValue() {
	if(isset($_REQUEST['vid'])) {
		$value = new ProductSpecificationCombineValue($_REQUEST['vid']);
		$value->delete();

		redirect(sprintf('Location: ?action=viewvalues&id=%d', $value->combineId));
	}

	redirect('Location: ?action=view');
}

function add() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('name', 'Name', 'text', '', 'paragraph', 1, 120);
	$form->AddField('group', 'Group', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('group', '', '');

	$data = new DataQuery(sprintf("SELECT * FROM product_specification_group ORDER BY Reference ASC"));
	while($data->Row) {
		$form->AddOption('group', $data->Row['Group_ID'], $data->Row['Reference']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])){
		if($form->Validate()){
			$combination = new ProductSpecificationCombine();
			$combination->name = $form->GetValue('name');
			$combination->group->ID = $form->GetValue('group');
			$combination->add();

			redirect('Location: ?action=view');
		}
	}

	$page = new Page('<a href="?action=view">Product Specification Combinations</a> &gt; Add Combination', 'Add new specification combination.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Add Specification Combination");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Add new combination');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('group'), $form->GetHTML('group') . $form->GetIcon('group'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=view\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function addValue() {
	$combination = new ProductSpecificationCombine();
	
	if(!isset($_REQUEST['id']) || !$combination->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'addvalue', 'alpha', 8, 8);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Combination ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('value', 'Value', 'selectmultiple', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('value', '', '');

	$data = new DataQuery(sprintf("SELECT * FROM product_specification_value WHERE Group_ID=%d ORDER BY Value ASC", mysql_real_escape_string($combination->group->ID)));
	while($data->Row) {
		$form->AddOption('value', $data->Row['Value_ID'], $data->Row['Value']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])){
		if($form->Validate()) {
			foreach($form->GetValue('value') as $valueId) {
				$value = new ProductSpecificationCombineValue();
				$value->combineId = $combination->id;
				$value->value->ID = $valueId;
				$value->add();
			}

			redirect(sprintf('Location: ?action=viewvalues&id=%d', $combination->id));
		}
	}

	$page = new Page('<a href="?action=view">Product Specification Combinations</a> &gt; Add Combination', 'Add new specification combination.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Add Combination Value");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Add new value');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('value'), $form->GetHTML('value') . $form->GetIcon('value'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=viewvalues&id=%d\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $combination->id, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	$combination = new ProductSpecificationCombine();
	
	if(!isset($_REQUEST['id']) || !$combination->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Combination ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', $combination->name, 'paragraph', 1, 120);
	$form->AddField('group', 'Group', 'select', $combination->group->ID, 'numeric_unsigned', 1, 11);
	$form->AddOption('group', '', '');

	$data = new DataQuery(sprintf("SELECT * FROM product_specification_group ORDER BY Reference ASC"));
	while($data->Row) {
		$form->AddOption('group', $data->Row['Group_ID'], $data->Row['Reference']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])){
		if($form->Validate()) {
			if($combination->group->ID != $form->GetValue('group')) {
				new DataQuery(sprintf("DELETE FROM product_specification_combine_value WHERE id=%d", mysql_real_escape_string($combination->id)));
			}
			
			$combination->name = $form->GetValue('name');
			$combination->group->ID = $form->GetValue('group');
			$combination->update();

			redirect('Location: ?action=view');
		}
	}

	$page = new Page('<a href="?action=view">Product Specification Combinations</a> &gt; Update Combination', 'Update existing specification combination.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Update Specification Combination");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Update existing combination');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('group'), $form->GetHTML('group') . $form->GetIcon('group'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=view\';" /> <input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function viewValues() {
	$combination = new ProductSpecificationCombine();
	
	if(!isset($_REQUEST['id']) || !$combination->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	$page = new Page('<a href="?action=view">Product Specification Combinations</a> &gt; Combination Values', 'Add values to this combination.');
	$page->Display('header');

	$table = new DataTable('combinationvalues');
	$table->SetSQL(sprintf("SELECT pscv.*, psv.Value AS valueName FROM product_specification_combine_value AS pscv INNER JOIN product_specification_value AS psv ON psv.Value_ID=pscv.productSpecificationValueId WHERE pscv.productSpecificationCombineId=%d", mysql_real_escape_string($combination->id)));
	$table->AddField('ID', 'id', 'left');
	$table->AddField('Value', 'valueName', 'left');
	$table->AddLink("javascript:confirmRequest('?action=removevalue&vid=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("valueName");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo sprintf('<input type="button" name="add" value="add new value" class="btn" onclick="window.location.href=\'?action=addvalue&id=%d\'" />', $combination->id);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	$page = new Page('Product Specification Combinations', 'Group specification values into one.');
	$page->Display('header');

	$table = new DataTable('combinations');
	$table->SetSQL(sprintf("SELECT psc.*, psg.Name AS groupName FROM product_specification_combine AS psc INNER JOIN product_specification_group AS psg ON psg.Group_ID=psc.productSpecificationGroupId"));
	$table->AddField('ID', 'id', 'left');
	$table->AddField('Name', 'name', 'left');
	$table->AddField('Group', 'groupName', 'left');
	$table->AddLink("?action=update&id=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "id");
	$table->AddLink("?action=viewvalues&id=%s", "<img src=\"images/folderopen.gif\" alt=\"Open\" border=\"0\">", "id");
	$table->AddLink("javascript:confirmRequest('?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("name");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo '<input type="button" name="add" value="add new combination" class="btn" onclick="window.location.href=\'?action=add\'" />';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}