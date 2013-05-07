<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderDocument.php');
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
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('restricttype', 'Restricted Type', 'hidden', '', 'alpha_numeric', 0, 60, false);
	
	$document = new OrderDocument();
	
	if(isset($_REQUEST['documentid']) && $document->Get($_REQUEST['documentid'])) {
		$document->delete($_REQUEST['documentid']);

		redirectTo(sprintf('?oid=%d&restricttype=%s', $_REQUEST['oid'], $form->GetValue('restricttype')));
	}

	redirectTo('order_search.php');
}

function add() {
    $order = new Order();

	if(!$order->Get($_REQUEST['oid'])) {
		redirectTo('order_search.php');
	}

	$order->Customer->Get();
	$order->Customer->Contact->Get();
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('restricttype', 'Restricted Type', 'hidden', '', 'alpha_numeric', 0, 60, false);
	$form->AddField('oid', 'Order ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('type', 'Type', 'text', $form->GetValue('restricttype'), 'alpha_numeric', 1, 60);
	$form->AddOption('type', '', '');
	$form->AddOption('type', 'Purchase Order', 'Purchase Order');
	$form->AddOption('type', 'Export Proof', 'Export Proof');
	$form->AddField('name', 'Name', 'text', '', 'alpha_numeric', 1, 120);
	$form->AddField('file', 'Document', 'file', '', 'file', null, null);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$document = new OrderDocument();
			$document->orderId = $order->ID;
			$document->type = (strlen($form->GetValue('restricttype')) == 0) ? $form->GetValue('type') : $form->GetValue('restricttype');
			$document->name = $form->GetValue('name');
			$document->file->FileName = $fileName;

            if($document->add('file')) {
				redirectTo(sprintf('?oid=%d&restricttype=%s', $order->ID, $form->GetValue('restricttype')));
			} else {
				for($i=0; $i<count($document->file->Errors); $i++) {
					$form->AddError($document->file->Errors[$i]);
				}
			}
		}
	}

	$page = new Page(sprintf('<a href="order_details.php?orderid=%d">%s%d Order Details for %s</a> &gt; <a href="?oid=%d&restricttype=%s">Documents</a> &gt; Add Document', $order->ID, $order->Prefix, $order->ID, $order->Customer->Contact->Person->GetFullName(), $order->ID, $form->GetValue('restricttype')), 'Add a new document to this order.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add document');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('oid');
	echo $form->GetHTML('restricttype');

	echo $window->Open();
	echo $window->AddHeader('Enter a name for this document.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('type'), (strlen($form->GetValue('restricttype')) == 0) ? $form->GetHTML('type') . $form->GetIcon('type') : $form->GetValue('restricttype'));
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('file'), $form->GetHTML('file') . $form->GetIcon('file'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?oid=%d\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $order->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function download() {
	$document = new OrderDocument();

	if(!$document->get($_REQUEST['documentid'])) {
		echo '<script language="javascript" type="text/javascript">alert(\'An error has occurred.\n\nPlease inform the system administrator that the download is missing.\'); window.close();</script>';
		require_once('lib/common/app_footer.php');
		exit;
	}

	$fileName = $document->file->FileName;
	$filePath = sprintf("%s%s", $GLOBALS['ORDER_DOCUMENT_DIR_FS'], $fileName);
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
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('restricttype', 'Restricted Type', 'hidden', '', 'alpha_numeric', 0, 60, false);
	
	$order = new Order($_REQUEST['oid']);
	$order->Customer->Get();
	$order->Customer->Contact->Get();

	$page = new Page(sprintf('<a href="order_details.php?orderid=%d">%s%d Order Details for %s</a> &gt; Documents', $order->ID, $order->Prefix, $order->ID, $order->Customer->Contact->Person->GetFullName()), 'Upload and download documents for this order.');
	$page->Display('header');

	$table = new DataTable('documents');
	$table->SetSQL(sprintf("SELECT * FROM order_document WHERE orderId=%d", $order->ID));
	$table->AddField('ID#', 'id', 'left');
	$table->AddField('Date Created', 'createdOn', 'left');
	$table->AddField('Type', 'type', 'left');
	$table->AddField('Name', 'name', 'left');
	$table->AddField('File Name', 'fileName', 'left');
	$table->AddLink(sprintf("?action=download&documentid=%%s&restricttype=%s", $form->GetValue('restricttype')), "<img src=\"images/folderopen.gif\" alt=\"Download\" border=\"0\">", "id");
	$table->AddLink(sprintf("javascript:confirmRequest('?action=remove&documentid=%%s&restricttype=%s', 'Are you sure you want to remove this item?');", $form->GetValue('restricttype')), "<img src=\"images/button-cross.gif\" alt=\"Remove\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("createdOn");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" name="add" value="add document" class="btn" onclick="window.location.href=\'?action=add&oid=%d&restricttype=%s\'" />', $order->ID, $form->GetValue('restricttype'));

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}