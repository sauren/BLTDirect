<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/LibraryFileType.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

if($action == "add"){
	$session->Secure(3);
	add();
	exit;
} elseif($action == "update"){
	$session->Secure(3);
	update();
	exit;
} elseif($action == "remove"){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == "navigation"){
	$session->Secure(3);
	navigation();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function add() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('name', 'Name', 'text', '', 'paragraph', 1, 100);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$type = new LibraryFileType();
			$type->Name = $form->GetValue('name');
			$type->Reference = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $type->Name));
			$type->Add();

			redirect("Location: ?action=view");
		}
	}

	$page = new Page('<a href="?action=view">File Types</a> &gt; Add Type', 'Add a new item.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Add Type");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	
	echo $window->Open();
	echo $window->AddHeader('Required fields are marked with an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'?action=view\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update(){
	$type = new LibraryFileType();

	if(!isset($_REQUEST['id']) || !$type->Get($_REQUEST['id'])) {
		redirect("Location: ?action=view");
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'File Type ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', $type->Name, 'paragraph', 1, 100);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$type->Name = $form->GetValue('name');
			$type->Reference = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $type->Name));
			$type->Update();

			redirect("Location: ?action=view");
		}
	}

	$page = new Page('<a href="?action=view">File Types</a> &gt; Update Type', 'Update an existing item.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Update Type");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	
	echo $window->Open();
	echo $window->AddHeader('Required fields are marked with an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'?action=view\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function remove() {
	if(isset($_REQUEST['id'])) {
		$type = new LibraryFileType();
		$type->Delete($_REQUEST['id']);
	}

	redirect("Location: ?action=view");
}

function navigation() {
	$type = new LibraryFileType();

	if(!isset($_REQUEST['id']) || !$type->Get($_REQUEST['id'])) {
		redirect("Location: ?action=view");
	}
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'navigation', 'alpha', 10, 10);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'File Type ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('node', 'Node', 'hidden', '', 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			new DataQuery(sprintf("INSERT INTO treemenu (Parent_ID, Caption, Url, Target, Is_Active) values (%d, '%s', '%s', 'i_content_display', 'Y')", mysql_real_escape_string($form->GetValue('node')), mysql_real_escape_string(sprintf('File Library (%s)', mysql_real_escape_string($type->Name))), sprintf('library_file_directory.php?type=%d', $type->ID)));

			redirect("Location: ?action=view");
		}
	}
	
	$page = new Page('<a href="?action=view">File Types</a> &gt; New Navigation', 'Add to navigation structure.');
	$page->LinkScript('js/navigator_functions.js');
	$page->LinkScript('js/navigator_format.js');
	$page->LinkScript('js/navigator_classes.js');
	$page->LinkScript('js/HttpRequest.js');
	$page->LinkScript('js/TreeMenu.js');
	$page->AddDocType();
	$page->AddToHead('<link href="./css/NavigationMenu.css" rel="stylesheet" type="text/css" />');
	$page->AddToHead('<script language="javascript" type="text/javascript">var myTree = new TreeMenu(\'myTree\');</script>');
	$page->AddToHead(sprintf("<script>\nvar s_navTree = new treeMenuSettings;\n\n</script>"));
	$page->AddOnLoad('s_navTree.drawOptions();');
	$page->Display('header');
	?>
	
	<div class="window_1">
		Navigation Window Structure<br />
		<div class="treeCell" id="Navigation">
			<script>
				myTree.url = 'lib/util/loadNavigationChildren.php?function=s_navTree';
				myTree.loading = '<div class="treeIsLoading"><img src="images/TreeMenu/loading.gif" align="absmiddle" /> Loading...</div>';
				myTree.addClass('default', 'images/TreeMenu/page.gif', 'images/TreeMenu/folder.gif', 'images/TreeMenu/folderopen.gif');
				myTree.addNode(0, null, '_root', 'default', true, 'javascript:s_navTree.setNode(0, \'_root\')');
				myTree.build('Navigation');
			</script>
		</div>
		<div class="settings">
			<div id="optionAdd" class="treeOption">
				<a href="javascript:s_navTree.selectOption('?action=navigation&confirm=true&id=<?php echo $type->ID; ?>&node=');"><strong>Continue</strong><br />Insert navigation menu at the selected level.</a>
			</div>
		</div>
	</div>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	$page = new Page('File Types', '');
	$page->Display('header');

	$table = new DataTable('types');
	$table->SetSQL(sprintf("SELECT * FROM library_file_type"));
	$table->AddField('ID', 'File_Type_ID', 'left');
	$table->AddField('Name', 'Name', 'left');
	$table->AddLink("?action=update&id=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update Settings\" border=\"0\">", "File_Type_ID");
	$table->AddLink("?action=navigation&id=%s", "<img src=\"./images/folderopen.gif\" alt=\"New Navigation\" border=\"0\">", "File_Type_ID");
	$table->AddLink("javascript:confirmRequest('?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "File_Type_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Name");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
	
	echo '<br />';
	echo '<input type="button" name="add" value="add new type" class="btn" onclick="window.location.href = \'?action=add\';" />';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}