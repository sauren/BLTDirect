<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderWarehouseNoteType.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'add'){
	$session->Secure(3);
	add();
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
	if(isset($_REQUEST['id'])){
		$type = new OrderWarehouseNoteType();
		$type->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add(){
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha_numeric', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('name', 'Type', 'text', '', 'anything', 1, 128);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$type = new OrderWarehouseNoteType();
			$type->Name = $form->GetValue('name');
			$type->Add();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('<a href="order_warehouse_note_types.php">Warehouse Note Types</a> &gt; Add Type', 'Add a warehouse note type here.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Add a type.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $window->Open();
	echo $window->AddHeader('Enter your type details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow('', '<input type="submit" name="add" value="add" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function update(){
	$type = new OrderWarehouseNoteType($_REQUEST['id']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha_numeric', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Type ID', 'hidden', $type->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Type', 'text', $type->Name, 'anything', 1, 128);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$type->Name = $form->GetValue('name');
			$type->Update();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('<a href="order_warehouse_note_types.php">Warehouse Note Types</a> &gt; Edit Type', 'Edit a warehouse note type here.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Edit a type.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Enter your type details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow('', '<input type="submit" name="update" value="update" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function view() {
	$page = new Page('Warehouse Note Types', 'Manage types for your order warehouse notes here.');
	$page->Display('header');

	$table = new DataTable('types');
	$table->SetSQL("SELECT * FROM order_warehouse_note_type");
	$table->AddField('ID#', 'Order_Warehouse_Note_Type_ID', 'right');
	$table->AddField('Type', 'Name', 'left');
	$table->AddLink("order_warehouse_note_types.php?action=update&id=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update Type\" border=\"0\">",  "Order_Warehouse_Note_Type_ID");
	$table->AddLink("javascript:confirmRequest('order_warehouse_note_types.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this type?');","<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Order_Warehouse_Note_Type_ID");
	$table->SetMaxRows(25);
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";

	echo '<input type="button" class="btn" value="add type" onclick="window.self.location.href=\'order_warehouse_note_types.php?action=add\'" />';

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}
?>