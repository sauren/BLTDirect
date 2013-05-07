<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');

$parentID = (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) ? $_REQUEST['id'] : 0;

if($action == "add") {
	$session->Secure(3);
	add($parentID);
} elseif($action == "update") {
	$session->Secure(3);
	update();
} elseif($action == "remove") {
	$session->Secure(3);
	remove($parentID);
} elseif($action == "getnode") {
	$session->Secure(2);
	getNode();
} elseif($action == "duplicate") {
	$session->Secure(3);
	duplicate($parentID);
} else {
	$session->Secure(2);
	view($parentID);
}

function getNode(){
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

		this.setNode = function(id, str) {
			window.opener.document.getElementById('parentCaption').innerHTML = str;
			window.opener.document.getElementById('parent').value = id;

			if(<?php print (isset($_REQUEST['callback']) && ($_REQUEST['callback'] == 'true')) ? 'true' : 'false'; ?>) {
				window.opener.getDocumentNodeCallback();
			}

			window.self.close();
		}
		</script>
	</head>
	<body id="Wrapper">

		<div id="Navigation"></div>

		<script>
		myTree.url = 'lib/util/loadDocumentChildren.php';
		myTree.loading = '<div class="treeIsLoading"><img src="images/TreeMenu/loading.gif" align="absmiddle" /> Loading...</div>';
		myTree.addClass('default', 'images/TreeMenu/page.gif', 'images/TreeMenu/folder.gif', 'images/TreeMenu/folderopen.gif');
		myTree.addNode(0, null, '_root', 'default', true, 'javascript:setNode(0, \'_root\')', null);
		myTree.build('Navigation');
	</script>

	</body>
	</html>
	<?php
}

function add($parentID = 0) {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Document.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardWindow.php');

	$form = new Form("documents.php?id=".$parentID);
	$form->AddField('action','Action','hidden','add','alpha',3,3);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('pid','Parent ID','hidden',$parentID,'numeric_unsigned',0,11);
	$form->AddField('title','Title','text','','anything',0,100, true, 'style="width:100%;"');
	$form->AddField('body','Body','textarea','','anything',0,1000000, false,'rows="30" style="width:100%;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if($form->Validate()) {
			$doc = new Document();
			$doc->Title = $form->GetValue('title');
			$doc->Body = $form->GetValue('body');
			$doc->Description = $form->GetValue('desc');
			$doc->ParentID = $form->GetValue('pid');
			$doc->Add();

			redirect('Location: documents.php?id='.$parentID);
		}
	}

	$page = new Page('<a href="documents.php?id='.$parentID.'">Documents</a> &gt; Add Document', 'Here you can add a new document');
	$page->SetEditor(true);
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow('Please enter the documents information');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('pid');
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'),$form->GetHTML('title').$form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('body'),$form->GetHTML('body').$form->GetIcon('body'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'documents.php?id=%d\';" />&nbsp;<input type="submit" name="add" value="add" class="btn" tabindex="%s">', $parentID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
}

function update() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Document.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardWindow.php');

	$doc = new Document($_REQUEST['sid']);

	$form = new Form("documents.php");
	$form->AddField('action','Action','hidden','update','alpha',6,6);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('sid','Document ID','hidden',$doc->ID,'numeric_unsigned',0,11);
	$form->AddField('title','Title','text',$doc->Title,'anything',0,100, true, 'style="width:100%;"');
	$form->AddField('body','Body','textarea',$doc->Body,'anything',0,1000000, false,'rows="30" style="width:100%;"');
	$form->AddField('back','Back','hidden', isset($_REQUEST['back']) ? $_REQUEST['back'] : 'child', 'anything', 0, 20, false);
	$form->AddField('parent', '', 'hidden', $doc->ParentID, 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true")
	{
		if($form->GetValue('parent') == $doc->ID) {
			$form->AddError('You cannot move this document inside itself.', 'parent');
		}

		if($form->Validate()) {
			$doc->Body = $form->GetValue('body');
			$doc->Title = $form->GetValue('title');
			$doc->ParentID = $form->GetValue('parent');
			$doc->Update();

			$back = ($form->GetValue('back') == 'this') ? $doc->ID : $doc->ParentID;

			redirect('Location: documents.php?id='.$back);
		}
	}

	$id = ($form->GetValue('back') == 'this') ? $doc->ID : $doc->ParentID;

	$page = new Page('<a href="documents.php?id='.$id.'">Documents</a> &gt; Update Document','Here you can edit this document');
	$page->SetEditor(true);
	$page->Display('header');

	if(!$form->Valid)
	{
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow('Please edit the document information');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('sid');
	echo $form->GetHTML('back');
	echo $form->GetHTML('parent');
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();

	$tmpParentTxt = 'Parent Node <a href="javascript:popUrl(\'documents.php?action=getnode\', 600, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';

	$title = 'Unknown';

	if($doc->ParentID == 0) {
		$title = '_root';
	} else {
		$parentNode = new DataQuery(sprintf("SELECT Title FROM document WHERE Document_ID=%d", mysql_real_escape_string($doc->ParentID)));
		$title = $parentNode->Row['Title'];
		$parentNode->Disconnect();
	}

	echo $webForm->AddRow($tmpParentTxt, sprintf('<span id="parentCaption">%s</span>', $title));

	echo $webForm->AddRow($form->GetLabel('title'),$form->GetHTML('title').$form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('body'),$form->GetHTML('body').$form->GetIcon('body'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'documents.php?id=%d\';" />&nbsp;<input type="submit" name="update" value="update" class="btn" tabindex="%s">', ($form->GetValue('back') == 'this') ? $doc->ID : $doc->ParentID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
}

function remove($parentID = 0) {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Document.php');

	if(isset($_REQUEST['sid']) && is_numeric($_REQUEST['sid'])) {
		$doc = new Document($_REQUEST['sid']);
		$doc->Remove();
	}

	redirect("Location: documents.php?id=".$parentID.'&'.$_REQUEST['tableid'].'_Current='.$_REQUEST['current']);
}

function view($parentID = 0) {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Document.php');

	$headerString = '';
	$id = $parentID;

	while(true)
	{
		$data = new DataQuery(sprintf("SELECT Document_ID, Parent_ID, Title FROM document WHERE Document_ID=%d", mysql_real_escape_string($id)));

		if($data->TotalRows > 0)
		{
			$crumb = (strlen($data->Row['Title']) > 16) ? substr($data->Row['Title'], 0, 16).'...' : $data->Row['Title'];

			if($parentID == $data->Row['Document_ID']) {
				$headerString = sprintf('&nbsp;&gt;&nbsp;%s', ucwords($crumb)).$headerString;
			} else {
				$headerString = sprintf('&nbsp;&gt;&nbsp;<a href="documents.php?id=%d">%s</a>', $data->Row['Document_ID'], ucwords($crumb)).$headerString;
			}

			$id = $data->Row['Parent_ID'];

			$data->Disconnect();
		}
		else
		break;
	}

	$doc = new Document($parentID);

	$page = new Page('<a href="documents.php">Documents</a>'.$headerString, "Here you can view the Documents");
	$page->Display('header');

	$tableId = "com";
	$current = isset($_REQUEST[$tableId.'_Current']) ? $_REQUEST[$tableId.'_Current'] : 1;

	if($parentID > 0) {
		echo '<input name="update" type="button" value="update document" class="btn" onclick="window.location.href=\'./documents.php?action=update&back=this&sid='.$parentID.'\'"><br /><br />';
	}

	$table = new DataTable($tableId);
	$table->SetSQL(sprintf("SELECT * FROM document WHERE Parent_ID=%d", mysql_real_escape_string($parentID)));
	$table->AddField("ID#","Document_ID",'left');
	$table->AddField("Title","Title");
	$table->AddField("Created","Created_On",'left');
	$table->AddField("Modified","Modified_On");
	$table->AddLink("javascript:confirmRequest('documents.php?action=duplicate&id=".$parentID."&tableid=".$tableId."&current=".$current."&sid=%s','Are you sure you want to duplicate this document?');", "<img src=\"./images/icon_pages_1.gif\" alt=\"Duplicate this document\" border=\"0\">", "Document_ID");
	$table->AddLink("documents.php?action=view&id=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open and view sub content for this document\" border=\"0\">", "Document_ID");
	$table->AddLink("documents.php?action=update&sid=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update this document\" border=\"0\">",  "Document_ID");
	$table->AddLink("javascript:confirmRequest('documents.php?action=remove&id=".$parentID."&tableid=".$tableId."&current=".$current."&sid=%s','Are you sure you want to remove this document?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove this document\" border=\"0\">", "Document_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Title");
	$table->Finalise();
	$table->DisplayTable();

	echo "<br />";

	$table->DisplayNavigation();

	echo '<br />';
	echo '<input name="add" type="button" value="add a document" class="btn" onclick="window.location.href=\'./documents.php?action=add&id='.$parentID.'\'">';

	$page->Display('footer');
}

function duplicate($parentID = 0) {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Document.php');

	if(isset($_REQUEST['sid']) && is_numeric($_REQUEST['sid'])) {
		$doc = new Document($_REQUEST['sid']);
		$doc->Title = sprintf('Copy of %s', $doc->Title);
		$doc->Add();
	}

	redirect("Location: documents.php?id=".$parentID.'&'.$_REQUEST['tableid'].'_Current='.$_REQUEST['current']);
}
?>