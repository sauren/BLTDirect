<?php
require_once('lib/common/app_header.php');

if($action == "add"){
	$session->Secure(3);
	add();
} elseif($action == "remove"){
	$session->Secure(3);
	remove();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserDocument.php');

	$document = new UserDocument();

	if(isset($_REQUEST['documentid']) && $document->Get($_REQUEST['documentid'])) {
		$document->Delete();

		redirect(sprintf("Location: user_documents.php?userid=%d", $document->UserID));
	}

	redirect(sprintf("Location: users.php"));
}

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserDocument.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');

    $user = new User();

	if(!$user->Get($_REQUEST['userid'])) {
		redirect(sprintf("Location: users.php"));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('userid', 'User ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Title', 'text', '', 'alpha_numeric', 1, 32);
	$form->AddField('file', 'Document', 'file', '', 'file', NULL, NULL, false);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$document = new UserDocument();
			$document->UserID = $user->ID;
			$document->Title = $form->GetValue('title');
			$document->File->FileName = $fileName;

            if($document->Add('file')) {
				redirect(sprintf("Location: %s?userid=%d", $_SERVER['PHP_SELF'], $user->ID));
			} else {
				for($i=0; $i<count($document->File->Errors); $i++) {
					$form->AddError($document->File->Errors[$i]);
				}
			}

			redirect(sprintf("Location: user_documents.php?userid=%d", $user->ID));
		}
	}

    $page = new Page(sprintf('<a href="users.php">Users</a> &gt; <a href="%s?userid=%d">Documents</a> &gt; Add Document', $_SERVER['PHP_SELF'], $user->ID), 'Add documents for this user.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add documents');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('userid');

	echo $window->Open();
	echo $window->AddHeader('Enter a title for this document.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('file'), $form->GetHTML('file') . $form->GetIcon('file'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'user_documents.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

	$user = new User();

	if(!$user->Get($_REQUEST['userid'])) {
		redirect(sprintf("Location: users.php"));
	}

	$page = new Page('<a href="users.php">Users</a> &gt; Documents', 'This area allows you to manage user documents.');
	$page->Display('header');

	$table = new DataTable('documents');
	$table->SetSQL(sprintf("SELECT * FROM user_document WHERE User_ID=%d", $user->ID));
	$table->AddField('ID#', 'User_Document_ID', 'left');
	$table->AddField('Date Created', 'Created_On', 'left');
	$table->AddField('Title', 'Title', 'left');
	$table->AddField('File Name', 'File_Name', 'left');
	$table->AddLink("user_download.php?documentid=%s", "<img src=\"images/folderopen.gif\" alt=\"Download\" border=\"0\">", "User_Document_ID");
	$table->AddLink("javascript:confirmRequest('user_documents.php?action=remove&documentid=%s', 'Are you sure you want to remove this item?');", "<img src=\"images/button-cross.gif\" alt=\"Remove\" border=\"0\">", "User_Document_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Created_On");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" name="add" value="add document" class="btn" onclick="window.location.href=\'user_documents.php?action=add&userid=%d\'" />', $user->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}