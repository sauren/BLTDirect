<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/LibraryFile.php');
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
} elseif($action == "download") {
	$session->Secure(2);
	download();
	exit;
} elseif($action == "remove") {
	$session->Secure(3);
	remove();
	exit;
}

function download() {
	$file = new LibraryFile();

	if(isset($_REQUEST['id']) && $file->Get($_REQUEST['id'])) {
		$fileName = $file->Src->FileName;
		$filePath = sprintf("%s%s", $GLOBALS['FILE_DIR_FS'], $fileName);
		$fileSize = filesize($filePath);

		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: private', false);
		header('Content-Transfer-Encoding: binary');
		header('Content-Type: application/force-download');
		header('Content-Length: ' . $fileSize);
		header('Content-Disposition: attachment; filename=' . $fileName);
		
		readfile($filePath);
		
		exit;
	}
}

function add() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('type', 'Type', 'hidden', '0', 'numeric_unsigned', 1, 11, false);
	$form->AddField('did', 'File Directory ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Title', 'text', '', 'paragraph', 1, 100, true);
	$form->AddField('file', 'File', 'file', '', 'file', NULL, NULL, true);
	$form->AddField('description', 'Description', 'textarea', '', 'anything', 1, 1024, false, 'rows="5"');

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$file = new LibraryFile();
			$file->FileType->ID = $form->GetValue('type');
			$file->FileDirectory->ID = $form->GetValue('did');
			$file->Title = $form->GetValue('title');
			$file->Description = $form->GetValue('description');

			if($file->Add('file')) {
				redirectTo(sprintf("library_file_directory.php?type=%d&id=%d", $form->GetValue('type'), $form->GetValue('did')));
			} else {
				for($i=0; $i<count($file->Src->Errors); $i++) {
					$form->AddError($file->Src->Errors[$i]);
				}
			}
		}
	}

	$page = new Page(sprintf('<a href="library_file_directory.php?type=%d&id=%d">File Library</a> &gt; Add File', $form->GetValue('type'), $form->GetValue('did')), 'Add your file details below.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Add file");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('type');
	echo $form->GetHTML('did');
	echo $window->Open();
	echo $window->AddHeader('Please complete the form below.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title'));
	echo $webForm->AddRow($form->GetLabel('file'), $form->GetHTML('file'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'library_file_directory.php?type=%d&id=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetValue('type'), $form->GetValue('did'), $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_header.php');
}

function update() {
	$file = new LibraryFile();

	if(!$file->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}
	
	if($file->FileDirectory->ID > 0) {
		$file->FileDirectory->Get();
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', '', 'hidden', $file->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('did', 'Directory ID', 'hidden', $file->FileDirectory->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('type', 'Type', 'select', $file->FileType->ID, 'numeric_unsigned', 1, 11);
	$form->AddOption('type', '0', '');
	
	$data = new DataQuery(sprintf("SELECT File_Type_ID, Name FROM library_file_type ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('type', $data->Row['File_Type_ID'], $data->Row['Name']);
		
		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('title', 'Title', 'text', $file->Title, 'paragraph', 1, 100, true);
	$form->AddField('file', 'File', 'file', '', 'file', NULL, NULL, false);
	$form->AddField('description', 'Description', 'textarea', $file->Description, 'anything', 1, 1024, false, 'rows="5"');

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$file->FileDirectory->ID = $form->GetValue('did');
			
			if($file->FileType->ID != $form->GetValue('type')) {
				$file->FileDirectory->ID = 0;
			}
			
			$file->FileType->ID = $form->GetValue('type');
			$file->Title = $form->GetValue('title');
			$file->Description = $form->GetValue('description');

			if($file->Update('file')) {
				redirectTo(sprintf("library_file_directory.php?type=%d&id=%d", $file->FileType->ID, $file->FileDirectory->ID));
			} else {
				for($i=0; $i<count($file->Src->Errors); $i++) {
					$form->AddError($file->Src->Errors[$i]);
				}
			}
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var foundDirectory = function(id, str) {
			var e = null;

			e = document.getElementById(\'did\');
			if(e) {
				e.value = id;
			}

			e = document.getElementById(\'directoryCaption\');
			if(e) {
				e.innerHTML = (id > 0) ? str : \'<em>None</em>\';
			}
		}
		</script>');
		
	$page = new Page(sprintf('<a href="library_file_directory.php?type=%d&id=%d">File Library</a> &gt; Update File', $file->FileType->ID, $file->FileDirectory->ID), 'Update your file details below.');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Update file");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $form->GetHTML('did');
	
	echo $window->Open();
	echo $window->AddHeader('Please complete the form below.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow(sprintf('File Directory <a href="javascript:popUrl(\'library_file_directory.php?action=getnode&callback=foundDirectory&type=%d\', 650, 500);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', $file->FileType->ID), sprintf('<span id="directoryCaption">%s</span>', ($file->FileDirectory->ID > 0) ? $file->FileDirectory->Name : '<em>None</em>'));
	echo $webForm->AddRow($form->GetLabel('type'), $form->GetHTML('type'));
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title'));

	if($file->Src->Exists()) {
		echo $webForm->AddRow('Current File', sprintf('<a href="%s%s" target="_blank">%s</a> (Click to download)', $GLOBALS['FILE_DIR_WS'], $file->Src->FileName, $file->Title));
	}

	echo $webForm->AddRow($form->GetLabel('file'), $form->GetHTML('file'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'library_file_directory.php?type=%d&id=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $file->FileType->ID, $file->FileDirectory->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_header.php');
}

function remove() {
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$file = new LibraryFile($_REQUEST['id']);
		$file->Remove();
		
		redirectTo(sprintf('library_file_directory.php?type=%d&id=%d', $file->FileType->ID, $file->FileDirectory->ID));
	}

	redirectTo('library_file_directory.php');
}