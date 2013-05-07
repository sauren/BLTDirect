<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/LibraryFile.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/LibraryFileDirectory.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardWindow.php');

if($action == 'getnode'){
	$session->Secure(2);
	getNode();
	exit;
} elseif($action == "add") {
	$session->Secure(3);
	add();
} elseif($action == "update") {
	$session->Secure(3);
	update();
} elseif($action == "remove") {
	$session->Secure(3);
	remove();
} else {
	$session->Secure(2);
	view();
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

		this.setNode = function(id, str) {
			window.opener.<?php echo $_REQUEST['callback']; ?>(id, str);
			window.self.close();
		}
		</script>
	</head>
	<body id="Wrapper">

		<div id="Navigation"></div>

		<script>
			myTree.url = 'lib/util/loadLibraryFileDirectoryChildren.php?type=<?php echo isset($_REQUEST['type']) ? $_REQUEST['type'] : 0; ?>';
			myTree.loading = '<div class="treeIsLoading"><img src="images/TreeMenu/loading.gif" align="absmiddle" /> Loading...</div>';
			myTree.addClass('default', 'images/TreeMenu/page.gif', 'images/TreeMenu/folder.gif', 'images/TreeMenu/folderopen.gif');
			myTree.addNode(0, null, '<em>None</em>', 'default', true, 'javascript:setNode(0, \'<em>None</em>\');', null);
			myTree.build('Navigation');
		</script>

	</body>
	</html>
	<?php
}

function add() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action','Action','hidden','add','alpha',3,3);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('type', 'Type','hidden','0','numeric_unsigned', 0,11);
	$form->AddField('id','Parent ID','hidden','','numeric_unsigned',0,11);
    $form->AddField('name','Name','text','','anything',0,100, true);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$directory = new LibraryFileDirectory();
			$directory->FileType->ID = $form->GetValue('type');
			$directory->ParentID = $form->GetValue('id');
			$directory->Name = $form->GetValue('name');
			$directory->Add();

			redirect(sprintf('Location: ?type=%d&id=%d', $form->GetValue('type'), $form->GetValue('id')));
		}
	}

	$page = new Page(sprintf('<a href="?type=%d&id=%d">File Directory</a> &gt; Add Directory', $form->GetValue('type'), $form->GetValue('id')), 'Here you can add a new file directory.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Please enter the documents information');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('type');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'),$form->GetHTML('name').$form->GetIcon('name'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?type=%d&id=%d\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $form->GetValue('type'), $form->GetValue('id'), $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
}

function update() {
	$directory = new LibraryFileDirectory();

	if(!isset($_REQUEST['id'])) {
		redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
	}

	if(!$directory->Get($_REQUEST['id'])) {
		redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action','Action','hidden','update','alpha',6,6);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('id','File Directory ID','hidden','','numeric_unsigned',0,11);
	$form->AddField('name','Name','text',$directory->Name,'anything',0,100, true);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$directory->Name = $form->GetValue('name');
			$directory->Update();

			redirect(sprintf('Location: ?type=%d&id=%d', $directory->FileType->ID, $directory->ParentID));
		}
	}

	$page = new Page(sprintf('<a href="?type=%d&id=%d">File Directory</a> &gt; Update Directory', $directory->FileType->ID, $directory->ParentID), 'Here you can update an existing file directory.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Please edit the document information');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'),$form->GetHTML('name').$form->GetIcon('name'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?type=%d&id=%d\';" /> <input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $directory->FileType->ID, $directory->ParentID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
}

function remove() {
	if(isset($_REQUEST['id'])) {
		$directory = new LibraryFileDirectory($_REQUEST['id']);
		$directory->Delete();

		redirectTo(sprintf('?type=%d&id=%d', $directory->FileType->ID, $directory->ParentID));
	}

	redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
}

function view() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action','Action','hidden','view','alpha',4,4);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('type', 'Type','hidden','0','numeric_unsigned', 0,11);
	$form->AddField('id','Parent ID','hidden','0','numeric_unsigned',0,11);
    
    if(isset($_REQUEST['confirm'])) {
    	if($form->Validate()) {
    		foreach($_REQUEST as $key=>$value) {
    			if(preg_match('/select_([0-9]+)/', $key, $matches)) {
    				$directory = new LibraryFile($matches[1]);
					$directory->Remove();

					redirectTo(sprintf('?type=%d&id=%d', $form->GetValue('type'), $form->GetValue('id')));
				}
			}    	
		}	
	}
	
	$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 0;

	$directory = new LibraryFileDirectory();
	$directory->ID = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;

	if($directory->ID > 0) {
		if(!$directory->Get($_REQUEST['id'])) {
			redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
		}
		
		$type = $directory->FileType->ID;
	}

	$headerString = '';
	$currentId = $directory->ID;

	while(true) {
		$data = new DataQuery(sprintf("SELECT File_Directory_ID, Parent_ID, Name FROM library_file_directory WHERE File_Directory_ID=%d", mysql_real_escape_string($currentId)));
		if($data->TotalRows > 0) {
			if($directory->ID == $data->Row['File_Directory_ID']) {
				$headerString = sprintf(' &gt; %s%s', $data->Row['Name'], $headerString);
			} else {
				$headerString = sprintf(' &gt; <a href="?type=%d&id=%d">%s</a>%s', $type, $data->Row['File_Directory_ID'], $data->Row['Name'], $headerString);
			}

			$currentId = $data->Row['Parent_ID'];

			$data->Disconnect();
		} else {
			break;
		}
	}

	$page = new Page(sprintf('<a href="?type=%d">File Directory</a>%s', $type, $headerString), 'Here you can view the file directory structure.');
	$page->Display('header');
	
	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $form->GetHTML('type');

	$table = new DataTable(sprintf('directory_%s', $directory->ID));
	$table->SetSQL(sprintf("SELECT * FROM library_file_directory WHERE Parent_ID=%d AND File_Type_ID=%d", mysql_real_escape_string($directory->ID), mysql_real_escape_string($type)));
	$table->AddField("ID#", "File_Directory_ID");
	$table->AddField("Name", "Name");
	$table->AddLink("?action=view&id=%s", "<img src=\"images/folderopen.gif\" alt=\"Open\" border=\"0\">", "File_Directory_ID");
	$table->AddLink("?action=update&id=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "File_Directory_ID");
	$table->AddLink("javascript:confirmRequest('?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/button-cross.gif\" alt=\"Remove\" border=\"0\">", "File_Directory_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Name");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo '<input name="add" type="button" value="add directory" class="btn" onclick="window.location.href = \'?action=add&type=' .$type. '&id='.$directory->ID.'\'">';
	echo '<br /><br />';

	echo '<table class="DataTable"><thead><tr><th width="1%" nowrap="nowrap">&nbsp;</th><th>Type</th><th>Title</th><th>Description</th><th>File Name</th><th width="1%" nowrap="nowrap">&nbsp;</th></tr></thead><tbody>';

	$data = new DataQuery(sprintf("SELECT lf.*, lft.Name AS Type FROM library_file AS lf LEFT JOIN library_file_type AS lft ON lft.File_Type_ID=lf.File_Type_ID WHERE lf.File_Directory_ID=%d AND lf.File_Type_ID=%d", mysql_real_escape_string($directory->ID), mysql_real_escape_string($type)));
	if($data->TotalRows == 0) {
		echo '<tr><td align="middle" colspan="6">There are currently no items to view.</td></tr>';
	} else {
		while($data->Row){
			echo sprintf('<tr><td><input type="checkbox" value="Y" name="select_%d" /></td><td>%s</td><td>%s</td><td>%s&nbsp;</td><td>%s</td><td nowrap="nowrap"><a href="library_file.php?action=download&id=%d"><img src="./images/folderopen.gif" alt="Open File" border="0" /></a> <a href="library_file.php?action=update&id=%d&did=%d"><img src="./images/icon_edit_1.gif" alt="Update File" border="0" /></a> <a href="javascript:confirmRequest(\'library_file.php?action=remove&id=%d&did=%d\',\'Are you sure you want to remove this item?\');"><img src="images/button-cross.gif" alt="Remove" border="0"></a></td></tr>', $data->Row['File_ID'], $data->Row['Type'], $data->Row['Title'], $data->Row['Description'], $data->Row['SRC'], $data->Row['File_ID'], $data->Row['File_ID'], $directory->ID, $data->Row['File_ID'], $directory->ID);

			$data->Next();
		}
	}
	$data->Disconnect();

	echo '</tbody></table>';

	echo '<br />';
	echo sprintf('<input type="button" name="add" value="add new file" class="btn" onclick="window.location.href=\'library_file.php?action=add&type=%d&did=%d\';" /> ', $type, $directory->ID);
	echo sprintf('<input type="submit" name="removeselected" value="remove selected" class="btn" /> ');

	echo $form->Close();
	
	$page->Display('footer');
}