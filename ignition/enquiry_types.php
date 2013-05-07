<?php
require_once('lib/common/app_header.php');

if($action == 'add'){
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'update'){
	$session->Secure(3);
	update();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryType.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM enquiry WHERE Enquiry_Type_ID=%d", mysql_real_escape_string($_REQUEST['id'])));
		if($data->Row['Count'] == 0) {
			$type = new EnquiryType();
			$type->Delete($_REQUEST['id']);
		} else {
			redirect(sprintf("Location: %s?status=removeerror", $_SERVER['PHP_SELF']));
		}
		$data->Disconnect();
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryType.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('name', 'Name', 'text', '', 'anything', 1, 128, true);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$type = new EnquiryType();
			$type->Name = $form->GetValue('name');
			$type->Add();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page(sprintf('<a href="%s">Enquiry Types</a> &gt; Add Type', $_SERVER['PHP_SELF']), 'Add a new enquiry type here.');
	$page->AddOnLoad("document.getElementById('name').focus();");
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Adding an Enquiry Type');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Enter a enquiry type.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'enquiry_types.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryType.php');

	$type = new EnquiryType($_REQUEST['id']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', '', 'hidden', $type->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', $type->Name, 'anything', 1, 128, true);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$type->Name = $form->GetValue('name');
			$type->Update();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page(sprintf('<a href="%s">Enquiry Types</a> &gt; Edit Type', $_SERVER['PHP_SELF']), 'Edit this enquiry type here.');
	$page->AddOnLoad("document.getElementById('name').focus();");
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Editing an Enquiry Type');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Enter a enquiry type.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'enquiry_types.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$script = '';

	if(isset($_REQUEST['status']) && ($_REQUEST['status'] == 'removeerror')) {
		$script .= sprintf('<script language="javascript" type="text/javascript">
			window.onload = function() {
				alert("Could not remove type as it is associated with enquiries.");
			}
			</script>');
	}

	$page = new Page('Enquiry Types', 'Listing all available enquiry category types.');
	$page->AddToHead($script);
	$page->Display('header');

	$table = new DataTable('types');
	$table->SetSQL("SELECT * FROM enquiry_type");
	$table->AddField("ID#", "Enquiry_Type_ID");
	$table->AddField("Type", "Name", "left");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Name");
	$table->AddLink("enquiry_types.php?action=update&id=%s","<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">","Enquiry_Type_ID");
	$table->AddLink("javascript:confirmRequest('enquiry_types.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this item? This type cannot be removed if it is associated with any enquiries.');","<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">","Enquiry_Type_ID");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo '<input type="button" name="add" value="add new type" class="btn" onclick="window.location.href=\'enquiry_types.php?action=add\'">';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>