<?php
require_once('lib/common/app_header.php');

if($action == 'add'){
	$session->Secure(3);
	if(isset($_REQUEST['node']) && is_numeric($_REQUEST['node'])){
		addSettings();
		exit;
	} else {
		redirect("Location: navigation.php");
		exit;
	}
} elseif($action == 'update'){
	$session->Secure(3);
	if(isset($_REQUEST['node']) && is_numeric($_REQUEST['node'])){
		updateSettings();
		exit;
	} else {
		redirect("Location: navigation.php");
		exit;
	}
} elseif($action == 'remove'){
	$session->Secure(3);
	if((isset($_REQUEST['node']) && is_numeric($_REQUEST['node']))){
		removeSettings($_REQUEST['node']);
		redirect("Location: navigation.php");
		exit;
	} else {
		redirect("Location: navigation.php");
		exit;
	}
} elseif($action == 'getnode'){
	$session->Secure(2);
	getNode();
	exit;
} else {
	$session->Secure(2);
	showSettings();
	exit;
}

function removeSettings($thisNode){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	// remove child nodes
	$getFromTree = new DataQuery(sprintf("select Node_ID from treemenu where Parent_ID=%d", mysql_real_escape_string($thisNode)));
	if($getFromTree->TotalRows > 0){
		do{
			removeSettings($getFromTree->Row['Node_ID']);
			$getFromTree->Next();
		}while($getFromTree->Row);
	}
	$getFromTree->Disconnect();

	$removeFromTree = new DataQuery(sprintf("delete from treemenu where Node_ID=%d", mysql_real_escape_string($thisNode)));
}

function getNode(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TreeMenu.php');

	$treeMenu = new TreeMenu('navTree');
	$treeMenu->SetParams('treemenu', 'Node_ID', 'Parent_ID', 'Caption');

	$page = new Page;
	$page->DisableTitle();
	$page->LinkScript('./js/navigator_functions.js');
	$page->LinkScript('./js/navigator_format.js');
	$page->LinkScript('./js/navigator_classes.js');
	$page->AddToHead(sprintf("<script>%s \n\nvar s_navTree = new treeMenuGetNode;\n</script>", $treeMenu->GetJS()));

	$page->Display('header');

?>
		<script language="JavaScript">
		var tree1 = new COOLjsTreePRO("tree1", navTree, TREE1_FORMAT);
		tree1.init();
		</script>
<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
}

function updateSettings(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$getNode = new DataQuery(sprintf("select * from treemenu where Node_ID=%d", mysql_real_escape_string($_REQUEST['node'])));

	if($getNode->TotalRows == 0){
		$page = new Page('Error','unable to continue because an invalid node id was received.');
		$page->Display('header');
		$page->Display('footer');
		require_once('lib/common/app_footer.php');
		$getNode->Disconnect();
		exit;
	}

	$form = new Form("navigation.php");
	$form->AddField('action', '', 'hidden', 'update', 'alpha', 3, 10);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('node', '', 'hidden', $getNode->Row['Node_ID'], 'numeric_unsigned', 1, 11);
	$form->AddField('parent', '', 'hidden', $getNode->Row['Parent_ID'], 'numeric_unsigned', 1, 11);
	$form->AddField('caption', 'Menu Name/Caption', 'text', $getNode->Row['Caption'], 'paragraph', 3, 50, true, 'class="inputTxt"');
	$form->AddField('url', 'URL', 'text', $getNode->Row['Url'], 'link_relative', 5, 150, false, 'class="inputTxt"');
	$form->AddField('user', 'User', 'select', $getNode->Row['User_ID'], 'numeric_unsigned', 1, 11);
	$form->AddOption('user', '0', '');

	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Name FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('user', $data->Row['User_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('target', 'Target', 'select', $getNode->Row['Target'], 'alpha_numeric', 3, 20, false);
	$form->AddOption('target', 'i_content_display', 'Main Igntion Display');
	$form->AddOption('target', '', 'None (Directories Only)');
	$form->AddOption('target', 'i_hlp_content', 'Ignition\'s Help Window');
	$form->AddOption('target', '_top', '_top');
	$form->AddOption('target', '_self', '_self');
	$form->AddOption('target', '_blank', '_blank');
	$form->AddField('class', 'Class', 'text', $getNode->Row['Class'], 'alpha_numeric', 3, 20, false, 'class="inputTxt"');
	$form->AddField('is_active', 'Active', 'checkbox', $getNode->Row['Is_Active'], 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$formUpdate = new DataQuery(sprintf("update treemenu set Parent_ID=%d, User_ID=%d, Caption='%s', Url='%s', Target='%s', Class='%s', Is_Active='%s' where Node_ID=%d",
			mysql_real_escape_string($form->InputFields['parent']->Value),
			mysql_real_escape_string($form->InputFields['user']->Value),
			addslashes($form->InputFields['caption']->Value),
			mysql_real_escape_string($form->InputFields['url']->Value),
			mysql_real_escape_string($form->InputFields['target']->Value),
			mysql_real_escape_string($form->InputFields['class']->Value),
			mysql_real_escape_string($form->InputFields['is_active']->Value),
			mysql_real_escape_string($form->InputFields['node']->Value)));

			redirect("Location: navigation.php");
		}
	}

	$page = new Page(sprintf('Update %s in Navigation Window', $form->InputFields['caption']->Value),
	sprintf('You are editing the %s node from the Navigation Window. Please follow the instructions on the form below.', $form->InputFields['caption']->Value));
	$page->Display('header');

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('node');
	echo $form->GetHTML('parent');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Edit Navigation Window Node");
	echo $window->Open();
	echo $window->AddHeader('Please complete the following required fields. Denoted by an asterisk (<span class="required">*</span>).');
	echo $window->OpenContent();

	$webForm = new StandardForm;
	echo $webForm->Open();

	$tmpParentTxt = 'Parent Node<a href="javascript:popUrl(\'navigation.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	if($form->InputFields['parent']->Value == 0){
		echo $webForm->AddRow($tmpParentTxt, '<span id="parentCaption">_root</span>');
	} else {
		$parentNode = new DataQuery(sprintf("select Caption from treemenu where Node_ID=%d", mysql_real_escape_string($form->InputFields['parent']->Value)));
		echo $webForm->AddRow($tmpParentTxt, sprintf('<span id="parentCaption">%s</span>', $parentNode->Row['Caption']));
		$parentNode->Disconnect();
	}
	echo $webForm->AddRow($form->GetLabel('caption'), $form->GetHTML('caption') . $form->GetIcon('caption'));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->AddHeader("The following fields are optional. They have been set to default values for your convenience.");
	echo $window->OpenContent();

	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('url'), $form->GetHTML('url') . $form->GetIcon('url'));
	echo $webForm->AddRow($form->GetLabel('user'), $form->GetHTML('user') . $form->GetIcon('user'));
	echo $webForm->AddRow($form->GetLabel('target'), $form->GetHTML('target') . $form->GetIcon('target'));
	echo $webForm->AddRow($form->GetLabel('class'), $form->GetHTML('class') . $form->GetIcon('class'));
	echo $webForm->AddRow($form->GetLabel('is_active'), $form->GetHTML('is_active') . $form->GetIcon('is_active'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'navigation.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function addSettings(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form("navigation.php");
	$form->AddField('action', '', 'hidden', 'add', 'alpha', 3, 10);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('node', '', 'hidden', 0, 'numeric_unsigned', 1, 11);
	$form->AddField('caption', 'Menu Name/Caption', 'text', '', 'paragraph', 3, 50, true, 'class="inputTxt"');
	$form->AddField('url', 'URL', 'text', '', 'link_relative', 5, 150, false, 'class="inputTxt"');
	$form->AddField('user', 'User', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('user', '0', '');

	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Name FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('user', $data->Row['User_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('target', 'Target', 'select', 'i_content_display', 'alpha_numeric', 3, 20, false);
	$form->AddOption('target', 'i_content_display', 'Main Ignition Display');
	$form->AddOption('target', '', 'None (Directories Only)');
	$form->AddOption('target', 'i_hlp_content', 'Ignition\'s Help Window');
	$form->AddOption('target', '_top', '_top');
	$form->AddOption('target', '_self', '_self');
	$form->AddOption('target', '_blank', '_blank');
	$form->AddField('class', 'Class', 'text', '', 'alpha_numeric', 3, 20, false, 'class="inputTxt"');
	$form->AddField('is_active', 'Active', 'checkbox', 'Y', 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$formInsert = new DataQuery(sprintf("insert into treemenu (Parent_ID, User_ID, Caption, Url, Target, Class, Is_Active) values (%d, %d, '%s', '%s', '%s', '%s', '%s')",
			mysql_real_escape_string($form->InputFields['node']->Value),
			mysql_real_escape_string($form->InputFields['user']->Value),
			addslashes($form->InputFields['caption']->Value),
			mysql_real_escape_string($form->InputFields['url']->Value),
			mysql_real_escape_string($form->InputFields['target']->Value),
			mysql_real_escape_string($form->InputFields['class']->Value),
			mysql_real_escape_string($form->InputFields['is_active']->Value)));

			redirect("Location: navigation.php");
		}
	}

	$page = new Page('Adding to the Navigation Window',
	'The form below will allow you to add a Node to the Navigation Window tree menu.');
	$page->AddOnLoad("document.getElementById('caption').focus();");
	$page->Display('header');

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('node');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Add Navigation Window Node");
	echo $window->Open();
	echo $window->AddHeader('Please complete the following required fields. Denoted by an asterisk (<span class="required">*</span>).');
	echo $window->OpenContent();

	$webForm = new StandardForm;
	echo $webForm->Open();

	if($form->InputFields['node']->Value == 0){
		echo $webForm->AddRow('Parent Node', '_root');
	} else {
		$parentNode = new DataQuery(sprintf("select Caption from treemenu where Node_ID=%d", $form->InputFields['node']->Value));
		echo $webForm->AddRow('Parent Node', $parentNode->Row['Caption']);
		$parentNode->Disconnect();
	}
	echo $webForm->AddRow($form->GetLabel('caption'), $form->GetHTML('caption') . $form->GetIcon('caption'));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->AddHeader("The following fields are optional. They have been set to default values for your convenience.");
	echo $window->OpenContent();

	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('url'), $form->GetHTML('url') . $form->GetIcon('url'));
	echo $webForm->AddRow($form->GetLabel('user'), $form->GetHTML('user') . $form->GetIcon('user'));
	echo $webForm->AddRow($form->GetLabel('target'), $form->GetHTML('target') . $form->GetIcon('target'));
	echo $webForm->AddRow($form->GetLabel('class'), $form->GetHTML('class') . $form->GetIcon('class'));
	echo $webForm->AddRow($form->GetLabel('is_active'), $form->GetHTML('is_active') . $form->GetIcon('is_active'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'navigation.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));

	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function showSettings(){
	$page = new Page('Ignition Navigation Window Settings',
	'Here you are able to add, remove or update available nodes on
						 Ignition\'s Navigation Window. Only developers should have access
						 to this area. If you are not a developer you should contact your
						 administrator.');

	$page->LinkScript('./js/navigator_functions.js');
	$page->LinkScript('./js/navigator_format.js');
	$page->LinkScript('./js/navigator_classes.js');
	$page->LinkScript('./js/HttpRequest.js');
	$page->LinkScript('./js/TreeMenu.js');
	$page->AddDocType();
	$page->AddToHead('<link href="./css/NavigationMenu.css" rel="stylesheet" type="text/css" />');
	$page->AddToHead('<script language="javascript" type="text/javascript">var myTree = new TreeMenu(\'myTree\');</script>');
	$page->AddToHead(sprintf("<script>\nvar s_navTree = new treeMenuSettings;\n\n</script>"));
	$page->AddOnLoad('s_navTree.drawOptions();');
	$page->Display('header');
?>
		<div class="window_1">
			Navigation Window Structure<br>
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
				<strong>Editing Options</strong><br>
				Select a Node from the Navigation Window Structure to the left. With a node selected please
				choose one of the following options:
				<br>
				<br>
				<div id="optionAdd" class="treeOption">
					<a href="javascript:s_navTree.selectOption('<?php echo $_SERVER['PHP_SELF'] ?>?action=add&node=');"><strong>Add</strong><br>
					a child to the selected node.</a>
				</div>
				<br>
				<div id="optionUpdate" class="treeOption">
					<a href="javascript:s_navTree.selectOption('<?php echo $_SERVER['PHP_SELF'] ?>?action=update&node=');"><strong>Update</strong><br>
					settings &amp;/or permissions for this node.</a>
				</div>
				<br>
				<div id="optionRemove" class="treeOption">
					<a href="javascript:s_navTree.remove('<?php echo $_SERVER['PHP_SELF'] ?>?action=remove&node=');"><strong>Remove</strong><br>
					this node and associated child nodes.</a>
				</div>
				<br>
			</div>
		</div>
<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
}
?>