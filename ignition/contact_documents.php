<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactDocument.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$session->Secure(3);

if($action == "add"){
	$session->Secure(3);
	add();
} elseif($action == "remove"){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == "download"){
	$session->Secure(3);
	download();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	$document = new ContactDocument();
	
	if(isset($_REQUEST['documentid']) && $document->Get($_REQUEST['documentid'])) {
		$document->delete($_REQUEST['documentid']);

		redirectTo(sprintf('?cid=%d', $_REQUEST['cid']));
	}

	redirectTo('contact_search.php');
}

function add() {
    $contact = new Contact();

	if(!$contact->Get($_REQUEST['cid'])) {
		redirectTo('contact_search.php');
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cid', 'Contact ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', '', 'alpha_numeric', 1, 120);
	$form->AddField('file', 'Document', 'file', '', 'file', null, null);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$document = new ContactDocument();
			$document->contactId = $contact->ID;
			$document->name = $form->GetValue('name');
			$document->file->FileName = $fileName;

            if($document->add('file')) {
				redirectTo(sprintf('?cid=%d', $contact->ID));
			} else {
				for($i=0; $i<count($document->file->Errors); $i++) {
					$form->AddError($document->file->Errors[$i]);
				}
			}

			redirectTo(sprintf('?cid=%d', $contact->ID));
		}
	}

	if($contact->Type == "O"){
		$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s</a> &gt; <a href="?cid=%d">Documents</a> &gt; Add Document', $contact->ID, $contact->Organisation->Name, $contact->ID), 'Add document to this contact.');
		$page->Display('header');
	} else {
		$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s %s</a> &gt; <a href="?cid=%d">Documents</a> &gt; Add Document', $contact->ID, $contact->Person->Name, $contact->Person->LastName, $contact->ID), 'Add document to this contact.');
		$page->Display('header');
	}

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add document');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('cid');

	echo $window->Open();
	echo $window->AddHeader('Enter a name for this document.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('file'), $form->GetHTML('file') . $form->GetIcon('file'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'?cid=%d\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $contact->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function download() {
	$document = new ContactDocument();

	if(!$document->get($_REQUEST['documentid'])) {
		echo '<script language="javascript" type="text/javascript">alert(\'An error has occurred.\n\nPlease inform the system administrator that the download is missing.\'); window.close();</script>';
		require_once('lib/common/app_footer.php');
		exit;
	}

	$fileName = $document->file->FileName;
	$filePath = sprintf("%s%s", $GLOBALS['CONTACT_DOCUMENT_DIR_FS'], $fileName);
	$fileSize = filesize($filePath);

	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private", false);
	header("Content-Transfer-Encoding: binary");
	header("Content-Type: application/force-download");
	header(sprintf("Content-Length: %s", $fileSize));
	header(sprintf("Content-Disposition: attachment; filename=%s", $fileName));

	readfile($filePath);

	require_once('lib/common/app_footer.php');
}

function view() {
	$contact = new Contact($_REQUEST['cid']);

	if($contact->Type == "O"){
		$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s</a> &gt; Documents', $contact->ID, $contact->Organisation->Name), 'Contact documents allow you to keep track of important messages relating to contacts.');
		$page->Display('header');
	} else {
		$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s %s</a> &gt; Documents', $contact->ID, $contact->Person->Name, $contact->Person->LastName), 'Contact documents allow you to keep track of important messages relating to contacts.');
		$page->Display('header');
	}

	$table = new DataTable('documents');
	$table->SetSQL(sprintf("SELECT * FROM contact_document WHERE contactId=%d", $contact->ID));
	$table->AddField('ID#', 'id', 'left');
	$table->AddField('Date Created', 'createdOn', 'left');
	$table->AddField('Name', 'name', 'left');
	$table->AddField('File Name', 'fileName', 'left');
	$table->AddLink("?action=download&documentid=%s", "<img src=\"images/folderopen.gif\" alt=\"Download\" border=\"0\">", "id");
	$table->AddLink("javascript:confirmRequest('?action=remove&documentid=%s', 'Are you sure you want to remove this item?');", "<img src=\"images/button-cross.gif\" alt=\"Remove\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("createdOn");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" name="add" value="add document" class="btn" onclick="window.location.href=\'?action=add&cid=%d\'" />', $contact->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}