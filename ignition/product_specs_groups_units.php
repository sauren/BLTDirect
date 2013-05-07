<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecGroupUnit.php');
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
	if(isset($_REQUEST['unitid']) && is_numeric($_REQUEST['unitid'])) {
		$unit = new ProductSpecGroupUnit($_REQUEST['unitid']);
		$unit->delete();

		redirect(sprintf("Location: ?group=%d", $unit->group->ID));
	}

	redirect(sprintf("Location: product_specs_groups.php"));
}

function add(){
	$group = new ProductSpecGroup($_REQUEST['group']);
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('group', 'Group ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('unit', 'Unit', 'text', '', 'anything', 1, 60);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$unit = new ProductSpecGroupUnit();
			$unit->group->ID = $form->GetValue('group');
			$unit->unit = $form->GetValue('unit');
			$unit->add();
			
			redirect(sprintf('Location: ?group=%d', $unit->group->ID));
		}
	}

	$page = new Page(sprintf('<a href="product_specs_groups.php">Product Specification Groups</a> &gt; <a href="?group=%d">Product Specification Group Units</a> &gt; Add Unit', $group->ID), 'Add a new alternative unit to this specification group.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Add Unit.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('group');

	echo $window->Open();
	echo $window->AddHeader('Add specification group unit');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('unit'), $form->GetHTML('unit'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'?group=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $group->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update(){
	$unit = new ProductSpecGroupUnit($_REQUEST['valueid']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('valueid', 'Value ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('unit', 'Unit', 'text', $unit->unit, 'anything', 1, 60);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$unit->unit = $form->GetValue('unit');
			$unit->update();
			
			redirect(sprintf('Location: ?group=%d', $unit->group->ID));
		}
	}

	$page = new Page(sprintf('<a href="product_specs_groups.php">Product Specification Groups</a> &gt; <a href="?group=%d">Product Specification Group Units</a> &gt; Update Unit', $unit->group->ID), 'Update an existing alternative specification unit.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow("Update Unit.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('valueid');

	echo $window->Open();
	echo $window->AddHeader('Update specification group unit');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('unit'), $form->GetHTML('unit'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?group=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $unit->group->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function products(){
	if(!isset($_REQUEST['group']) || ($_REQUEST['group'] == 0)) {
		redirect("Location: product_specs_groups.php");
	}

	if(!isset($_REQUEST['value']) || ($_REQUEST['value'] == 0)) {
		redirect("Location: product_specs_groups.php");
	}

	$page = new Page(sprintf('<a href="product_specs_groups.php">Product Specification Groups</a> &gt; <a href="%s?group=%d">Product Specification Group Values</a> &gt; Products', $_SERVER['PHP_SELF'], $_REQUEST['group']), 'Products associated with this specirfication group.');
	$page->Display('header');

	$table = new DataTable("products");
	$table->SetExtractVars();
	$table->SetSQL(sprintf("SELECT p.Product_ID, p.Product_Title FROM product AS p INNER JOIN product_specification AS ps ON ps.Product_ID=p.Product_ID WHERE ps.Value_ID=%d", mysql_real_escape_string($_REQUEST['value'])));
	$table->AddField('ID#', 'Product_ID', 'right');
	$table->AddField('Product', 'Product_Title', 'left');
	$table->AddLink("product_profile.php?pid=%s", "<img src=\"./images/folderopen.gif\" alt=\"View Product\" border=\"0\">", "Product_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Product_Title");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo sprintf('<input type="button" name="manage" value="manage specification values" class="btn" onclick="window.location.href=\'%s?action=matrix&group=%d&value=%d\'" /> ', $_SERVER['PHP_SELF'], $_REQUEST['group'], $_REQUEST['value']);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	if(!isset($_REQUEST['group'])) {
		redirect("Location: product_specs_groups.php");
	}

	$page = new Page('<a href="product_specs_groups.php">Product Specification Groups</a> &gt; Product Specification Group Units', 'Manage alternative units for this specification group.');
	$page->Display('header');

	$table = new DataTable('units');
	$table->SetSQL(sprintf("SELECT * FROM product_specification_group_unit WHERE groupId=%d", mysql_real_escape_string($_REQUEST['group'])));
	$table->AddField('ID#', 'id', 'left');
	$table->AddField('Unit', 'unit', 'left');
	$table->AddLink("?action=update&unitid=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update Value\" border=\"0\">", "id");
	$table->AddLink("javascript:confirmRequest('?action=remove&unitid=%s','Are you sure you want to remove this item?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy('unit');
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo sprintf('<input type="button" name="add" value="add unit" class="btn" onclick="window.location.href=\'?action=add&group=%d\'" /> ', $_REQUEST['group']);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}