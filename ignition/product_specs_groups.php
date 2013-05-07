<?php
ini_set('max_execution_time', '86400');

require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpec.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'getnode'){
	$session->Secure(2);
	getNode();
	exit;
} elseif($action == 'add'){
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'update'){
	$session->Secure(3);
	update();
	exit;
} elseif($action == 'regenerate'){
	$session->Secure(3);
	regenerate();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function getNode(){
	if(!isset($_REQUEST['callback'])) {
		echo '<script language="javascript" type="text/javascript">alert(\'An error has occurred.\n\nPlease inform the system administrator that the callback function is absent.\'); window.close();</script>';
		require_once('lib/common/app_footer.php');
		exit;
	}
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Select Parent Node</title>
		<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>
		<script language="javascript" type="text/javascript" src="js/TreeMenu.js"></script>
		<link href="css/NavigationMenu.css" rel="stylesheet" type="text/css" />

		<script language="javascript" type="text/javascript">
		var myTree = new TreeMenu('myTree');

		var setNode = function(id, str) {
			window.opener.<?php echo $_REQUEST['callback']; ?>(id, str);
			window.self.close();
		}
		</script>
	</head>
	<body id="Wrapper">

		<div id="Navigation"></div>

		<script>
		myTree.url = 'lib/util/loadSpecGroupChildren.php';
		myTree.loading = '<div class="treeIsLoading"><img src="images/TreeMenu/loading.gif" align="absmiddle" /> Loading...</div>';
		myTree.addClass('default', 'images/TreeMenu/page.gif', 'images/TreeMenu/folder.gif', 'images/TreeMenu/folderopen.gif');
		myTree.addNode(0, null, '_root', 'default', true, 'javascript:setNode(0, \'None\');', null);
		myTree.build('Navigation');
	</script>

	</body>
	</html>
	<?php
	require_once('lib/common/app_footer.php');
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecGroup.php');

	if(isset($_REQUEST['group']) && is_numeric($_REQUEST['group'])){
		$group = new ProductSpecGroup();
		$group->Delete($_REQUEST['group']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecGroup.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Parent ID', 'hidden', '0', 'numeric_unsigned', 1, 11, false);
	$form->AddField('parentCaption', 'Parent Caption', 'text', 'None', 'anything', 0, 255, false, 'disabled="disabled"');
	$form->AddField('name', 'Name', 'text', '', 'anything', 1, 255);
	$form->AddField('reference', 'Reference', 'text', '', 'anything', 1, 255);
	$form->AddField('filterable', 'Is Filterable', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('visible', 'Is Visible', 'checkbox', 'Y', 'boolean', 1, 1, false);
	$form->AddField('hidden', 'Is Hidden', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('units', 'Units', 'text', '', 'anything', 0, 30, false);
	$form->AddField('datatype', 'Data Type', 'select', 'string', 'anything', 0, 30, false);
	$form->AddOption('datatype', 'string', 'string');
	$form->AddOption('datatype', 'numeric', 'numeric');
		
	if($form->GetValue('parent') > 0) {
		$parentGroup = new ProductSpecGroup();
		
		if($parentGroup->Get($form->GetValue('parent'))) {
			$form->SetValue('parentCaption', $parentGroup->Reference);
		}
	}

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()) {
			$group = new ProductSpecGroup();
			$group->ParentID = $form->GetValue('parent');
			$group->Name = $form->GetValue('name');
			$group->Reference = $form->GetValue('reference');
			$group->IsFilterable = $form->GetValue('filterable');
			$group->IsVisible = $form->GetValue('visible');
			$group->IsHidden = $form->GetValue('hidden');
			$group->Units = $form->GetValue('units');
			$group->DataType = $form->GetValue('datatype');
			$group->Add();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var foundGroup = function(id, str) {
			var parent = document.getElementById(\'parent\');
			if(parent) {
				parent.value = id;
			}
			
			var parentCaption = document.getElementById(\'parentCaption\');
			if(parentCaption) {
				parentCaption.value = str;
			}
		}
		</script>');
	
	$page = new Page('<a href="product_specs_groups.php">Product Specification Groups</a> &gt; Add Specification','Manage global specification groups for your products');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow("Add Product Specification Group.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('parent');
	echo $window->Open();
	echo $window->AddHeader('Add specification group');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('parent').sprintf(' <a href="javascript:popUrl(\'%s?action=getnode&callback=foundGroup\', 800, 600);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', $_SERVER['PHP_SELF']), $form->GetHTML('parentCaption'));
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('reference'), $form->GetHTML('reference') . $form->GetIcon('reference'));
	echo $webForm->AddRow($form->GetLabel('filterable'), $form->GetHTML('filterable') . $form->GetIcon('filterable'));
	echo $webForm->AddRow($form->GetLabel('visible'), $form->GetHTML('visible') . $form->GetIcon('visible'));
	echo $webForm->AddRow($form->GetLabel('hidden'), $form->GetHTML('hidden') . $form->GetIcon('hidden'));
	echo $webForm->AddRow($form->GetLabel('units'), $form->GetHTML('units') . $form->GetIcon('units'));
	echo $webForm->AddRow($form->GetLabel('datatype'), $form->GetHTML('datatype') . $form->GetIcon('datatype'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_specs_groups.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecGroup.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

	$group = new ProductSpecGroup($_REQUEST['group']);
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('group', 'Group ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('parent', 'Parent ID', 'hidden', $group->ParentID, 'numeric_unsigned', 1, 11, false);
	$form->AddField('parentCaption', 'Parent Caption', 'text', 'None', 'anything', 0, 255, false, 'disabled="disabled"');
	$form->AddField('name', 'Name', 'text', $group->Name, 'anything', 1, 255);
	$form->AddField('reference', 'Reference', 'text', $group->Reference, 'anything', 1, 255);
	$form->AddField('filterable', 'Is Filterable', 'checkbox', $group->IsFilterable, 'boolean', 1, 1, false);
	$form->AddField('visible', 'Is Visible', 'checkbox', $group->IsVisible, 'boolean', 1, 1, false);
	$form->AddField('hidden', 'Is Hidden', 'checkbox', $group->IsHidden, 'boolean', 1, 1, false);
	$form->AddField('units', 'Units', 'text', $group->Units, 'anything', 0, 30, false);
	$form->AddField('datatype', 'Data Type', 'select', $group->DataType, 'anything', 0, 30, false);
	$form->AddOption('datatype', 'string', 'string');
	$form->AddOption('datatype', 'numeric', 'numeric');
	
	if($form->GetValue('parent') > 0) {
		$parentGroup = new ProductSpecGroup();
		
		if($parentGroup->Get($form->GetValue('parent'))) {
			$form->SetValue('parentCaption', $parentGroup->Reference);
		}
	}

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()) {
			$group->ParentID = $form->GetValue('parent');
			$group->Name = $form->GetValue('name');
			$group->Reference = $form->GetValue('reference');
			$group->IsFilterable = $form->GetValue('filterable');
			$group->IsVisible = $form->GetValue('visible');
			$group->IsHidden = $form->GetValue('hidden');
			$group->Units = $form->GetValue('units');
			$group->DataType = $form->GetValue('datatype');
			$group->Update();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var foundGroup = function(id, str) {
			var parent = document.getElementById(\'parent\');
			if(parent) {
				parent.value = id;
			}
			
			var parentCaption = document.getElementById(\'parentCaption\');
			if(parentCaption) {
				parentCaption.value = str;
			}
		}
		</script>');
	
	$page = new Page('<a href="product_specs_groups.php">Product Specification Groups</a> &gt; Update Specification', 'Manage global specification groups for your products');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow("Update Product Specification Group.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('group');
	echo $form->GetHTML('parent');
	echo $window->Open();
	echo $window->AddHeader('Update specification group');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('parent').sprintf(' <a href="javascript:popUrl(\'%s?action=getnode&callback=foundGroup\', 800, 600);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', $_SERVER['PHP_SELF']), $form->GetHTML('parentCaption'));
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('reference'), $form->GetHTML('reference') . $form->GetIcon('reference'));
	echo $webForm->AddRow($form->GetLabel('filterable'), $form->GetHTML('filterable') . $form->GetIcon('filterable'));
	echo $webForm->AddRow($form->GetLabel('visible'), $form->GetHTML('visible') . $form->GetIcon('visible'));
	echo $webForm->AddRow($form->GetLabel('hidden'), $form->GetHTML('hidden') . $form->GetIcon('hidden'));
	echo $webForm->AddRow($form->GetLabel('units'), $form->GetHTML('units') . $form->GetIcon('units'));
	echo $webForm->AddRow($form->GetLabel('datatype'), $form->GetHTML('datatype') . $form->GetIcon('datatype'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_specs_groups.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function regenerate() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecGroup.php');

	ProductSpecGroup::regenerateRanges();
	
	redirectTo('?action=view');
}

function view() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecGroup.php');

	$form = new Form($_SERVER['PHP_SELF']);
	
	if(isset($_REQUEST['update'])) {
		$groupArr = array();

		foreach($_REQUEST as $key=>$value) {
			if(preg_match('/^(sequence_)(\d*)$/', $key, $matches)) {
				$group = new ProductSpecGroup();
				$group->Get($matches[2]);
				
				if(preg_match('/^(\d*)$/', $_REQUEST[$key])) {
					$group->SequenceNumber = $value;
				} else {
					$form->AddError(sprintf('Sequence for \'%s\' must be a number.', $group->Name), $key);
				}
				
				$groupArr[] = $group;
			}
		}

		if($form->Valid) {
			foreach($groupArr as $group) {
				$group->Update();
			}

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}
	
	$page = new Page('Product Specification Groups','Manage global specification groups for your products');
	$page->Display('header');
	
	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}
	
	echo $form->Open();
	
	echo sprintf('<input type="button" name="regenerate" value="regenerate lengths" class="btn" onclick="window.location.href=\'?action=regenerate\'" /> ');
	echo '<br /><br />';

	$table = new DataTable('groups');
	$table->SetSQL(sprintf("SELECT psg.*, psg2.Reference AS Parent_Reference FROM product_specification_group AS psg LEFT JOIN product_specification_group AS psg2 ON psg.Parent_ID=psg2.Group_ID"));
	$table->AddField('ID#', 'Group_ID', 'right');
	$table->AddField('Name', 'Name', 'left');
	$table->AddField('Reference', 'Reference', 'left');
	$table->AddField('Parent Group', 'Parent_Reference', 'left');
	$table->AddField('Filterable', 'Is_Filterable', 'center');
	$table->AddField('Visible', 'Is_Visible', 'center');
	$table->AddField('Hidden', 'Is_Hidden', 'center');
	$table->AddField('Units', 'Units', 'left');
	$table->AddField('Data Type', 'Data_Type', 'left');
	$table->AddLink("product_specs_groups.php?action=update&group=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "Group_ID");
	$table->AddLink("product_specs_groups_values.php?group=%s", "<img src=\"images/folderopen.gif\" alt=\"Values\" border=\"0\">", "Group_ID");
	$table->AddLink("product_specs_groups_units.php?group=%s", "<img src=\"images/icon_view_1.gif\" alt=\"Units\" border=\"0\">", "Group_ID");
	$table->AddLink("javascript:confirmRequest('product_specs_groups.php?action=remove&group=%s','Are you sure you want to remove this product specification title/value pair?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Group_ID");
	$table->AddInput('Sequence', 'Y', 'Sequence_Number', 'sequence', 'Group_ID', 'text', 'size="4"');
	$table->SetMaxRows(100);
	$table->SetOrderBy("Sequence_Number");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
	
	echo '<br />';
	echo sprintf('<input type="submit" name="update" value="update" class="btn" /> ');
	echo sprintf('<input type="button" name="add" value="add specification group" class="btn" onclick="window.location.href=\'product_specs_groups.php?action=add\'" /> ');
	
	echo $form->close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}