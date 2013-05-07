<?php
require_once('lib/common/app_header.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'add'){
	$session->Secure(3);
	add();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecGroupCategory.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$group = new ProductSpecGroupCategory();
		$group->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s?cat=%d", $_SERVER['PHP_SELF'], isset($_REQUEST['cat']) ? $_REQUEST['cat'] : 0));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecGroupCategory.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');

	$category = new Category();
	$category->ID = (isset($_REQUEST['cat']) && is_numeric($_REQUEST['cat'])) ? $_REQUEST['cat'] : 0;
	
	if($category->ID == 0) {
		redirect(sprintf("Location: product_categories.php"));
	}
	
	$category->Get();
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cat', 'Category ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('group', 'Group', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('group', '', '');
	
	$data = new DataQuery(sprintf("SELECT * FROM product_specification_group WHERE Is_Filterable='Y'"));
	while($data->Row) {
		$form->AddOption('group', $data->Row['Group_ID'], $data->Row['Name']);
		
		$data->Next();
	}
	$data->Disconnect();
	
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()) {
			$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM product_specification_group_category WHERE Category_ID=%d AND Group_ID=%d", mysql_real_escape_string($form->GetValue('cat')), mysql_real_escape_string($form->GetValue('group'))));
			if($data->Row['Counter'] > 0) {
				$form->AddError('An exclusion for this specification group already exists.', 'group');
			}
			$data->Disconnect();
			
			if($form->Valid) {
				$group = new ProductSpecGroupCategory();
				$group->GroupID = $form->GetValue('group');
				$group->CategoryID = $form->GetValue('cat');
				$group->Add();
	
				redirect(sprintf("Location: %s?cat=%d", $_SERVER['PHP_SELF'], $category->ID));
			}
		}
	}
	
	$page = new Page(sprintf('<a href="product_categories.php">%s</a> &gt; <a href="%s?cat=%d">Product Specification Group Category Exclusions</a> &gt; Add Exclusion', $category->Name, $_SERVER['PHP_SELF'], $category->ID), 'Add an exclusion for this category.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow("Add product specification group exclusion.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('cat');
	echo $window->Open();
	echo $window->AddHeader('Add spec group');
	echo $window->OpenContent();
	echo $webForm->Open();	
	echo $webForm->AddRow($form->GetLabel('group'), $form->GetHTML('group') . $form->GetIcon('group'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_specs_groups.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
	
	$category = new Category();
	$category->ID = (isset($_REQUEST['cat']) && is_numeric($_REQUEST['cat'])) ? $_REQUEST['cat'] : 0;
	
	if($category->ID == 0) {
		redirect(sprintf("Location: product_categories.php"));
	}
	
	$category->Get();
	
	$page = new Page(sprintf('<a href="product_categories.php">%s</a> &gt; Product Specification Group Category Exclusions', $category->Name), 'Manage exclusions of your product specification groups for this category from the specification filter.');
	$page->Display('header');
	
	$table = new DataTable("groups");
	$table->SetSQL(sprintf("SELECT psgc.Group_Category_ID, psg.Name, psg.Group_ID FROM product_specification_group AS psg INNER JOIN product_specification_group_category AS psgc ON psg.Group_ID=psgc.Group_ID WHERE psgc.Category_ID=%d", mysql_real_escape_string($category->ID)));
	$table->AddField('ID#', 'Group_ID', 'right');
	$table->AddField('Name', 'Name', 'left');
	$table->AddLink(sprintf("javascript:confirmRequest('product_specs_groups_categories.php?action=remove&cat=%d&id=%%s', 'Are you sure you want to remove this product specification group exclusion?');", $category->ID), "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Group_Category_ID");
	$table->SetMaxRows(100);
	$table->SetOrderBy("Name");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
	
	echo '<br />';
	echo sprintf('<input type="button" name="add" value="add group exclusion" class="btn" onclick="window.location.href=\'%s?action=add&cat=%d\'" />', $_SERVER['PHP_SELF'], $category->ID);
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>