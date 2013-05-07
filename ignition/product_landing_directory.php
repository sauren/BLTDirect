<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/ProductLandingDirectory.php');
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
			myTree.url = 'lib/util/loadProductLandingDirectoryChildren.php';
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
	$form->AddField('id','Parent ID','hidden','','numeric_unsigned',0,11);
    $form->AddField('name','Name','text','','anything',0,100, true);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$directory = new ProductLandingDirectory();
			$directory->parentId = $form->GetValue('id');
			$directory->name = $form->GetValue('name');
			$directory->add();

			redirect(sprintf('Location: ?id=%d', $form->GetValue('id')));
		}
	}

	$page = new Page(sprintf('<a href="?id=%d">Product Landing Directory</a> &gt; Add Directory', $form->GetValue('id')), 'Here you can add a new product landing directory.');
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
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'),$form->GetHTML('name').$form->GetIcon('name'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?id=%d\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />',  $form->GetValue('id'), $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
}

function update() {
	$directory = new ProductLandingDirectory();

	if(!isset($_REQUEST['id'])) {
		redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
	}

	if(!$directory->get($_REQUEST['id'])) {
		redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action','Action','hidden','update','alpha',6,6);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('id','Product Landing Directory ID','hidden','','numeric_unsigned',0,11);
	$form->AddField('name','Name','text',$directory->name,'anything',0,100, true);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$directory->name = $form->GetValue('name');
			$directory->update();
			
			redirect(sprintf('Location: ?id=%d', $directory->parentId));
		}
	}

	$page = new Page(sprintf('<a href="?id=%d">Product Landing Directory</a> &gt; Update Directory', $directory->parentId), 'Here you can update an existing product landing directory.');
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
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?id=%d\';" /> <input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $directory->parentId, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
}

function remove() {
	if(isset($_REQUEST['id'])) {
		$directory = new ProductLandingDirectory($_REQUEST['id']);
		$directory->delete();

		redirectTo(sprintf('?id=%d', $directory->parentId));
	}

	redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
}

function view() {
	$directory = new ProductLandingDirectory();
	$directory->id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;

	if($directory->id > 0) {
		if(!$directory->get($_REQUEST['id'])) {
			redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
		}
	}

	$headerString = '';
	$currentId = $directory->id;

	while(true) {
		$data = new DataQuery(sprintf("SELECT id, parentId, name FROM product_landing_directory WHERE id=%d", mysql_real_escape_string($currentId)));
		if($data->TotalRows > 0) {
			if($directory->id == $data->Row['id']) {
				$headerString = sprintf(' &gt; %s%s', $data->Row['name'], $headerString);
			} else {
				$headerString = sprintf(' &gt; <a href="?id=%d">%s</a>%s', $data->Row['id'], $data->Row['name'], $headerString);
			}

			$currentId = $data->Row['parentId'];

			$data->Disconnect();
		} else {
			break;
		}
	}

	$page = new Page(sprintf('<a href="?action=view">Product Landing Directory</a>%s', $headerString), 'Here you can view the product landing directory structure.');
	$page->Display('header');

	$table = new DataTable(sprintf('directory_%s', $directory->id));
	$table->SetSQL(sprintf("SELECT * FROM product_landing_directory WHERE parentId=%d", mysql_real_escape_string($directory->id)));
	$table->AddField("ID#", "id");
	$table->AddField("Name", "name");
	$table->AddLink("?action=view&id=%s", "<img src=\"images/folderopen.gif\" alt=\"Open\" border=\"0\">", "id");
	$table->AddLink("?action=update&id=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "id");
	$table->AddLink("javascript:confirmRequest('?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/button-cross.gif\" alt=\"Remove\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("name");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo '<input name="add" type="button" value="add new directory" class="btn" onclick="window.location.href = \'?action=add&id='.$directory->id.'\'">';
	echo '<br /><br />';

	$table = new DataTable(sprintf('directory_items_%s', $directory->id));
	$table->SetSQL(sprintf("SELECT * FROM product_landing WHERE directoryId=%d", mysql_real_escape_string($directory->id)));
	$table->AddField("ID#", "id");
	$table->AddField("Name", "name", "left");
	$table->AddField("Hide Filter", "hideFilter", "center");
	$table->AddLink("product_landings.php?action=purgecache&id=%s","<img src=\"images/icon_help_1.gif\" alt=\"Purge Cache\" border=\"0\">", "id");
	$table->AddLink("product_landings.php?action=update&id=%s","<img src=\"images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "id");
	$table->AddLink("product_landings.php?action=open&id=%s","<img src=\"images/folderopen.gif\" alt=\"Open\" border=\"0\">", "id");
	$table->AddLink("product_landing_specifications.php?id=%s","<img src=\"images/icon_view_1.gif\" alt=\"Specifications\" border=\"0\">", "id");
	$table->AddLink("javascript:confirmRequest('product_landings.php?action=remove&id=%s','Are you sure you want to remove this item?');","<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("name");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" name="add" value="add new landing" class="btn" onclick="window.location.href=\'product_landings.php?action=add&did=%d\';" />', $directory->id);

	$page->Display('footer');
}