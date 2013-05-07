<?php
require_once('lib/common/app_header.php');

if($action == "add") {
	$session->Secure(3);
	add();
} elseif($action == "update") {
	$session->Secure(3);
	update();
} elseif($action == "remove") {
	$session->Secure(3);
	remove();
} elseif($action == "moveup") {
	$session->Secure(3);
	moveup();
} elseif($action == "movedown") {
	$session->Secure(3);
	movedown();
} else {
	$session->Secure(2);
	view();
}

function moveup() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/LampBase.php');

	$lampBase = new LampBase($_REQUEST['id']);

	$data = new DataQuery(sprintf("SELECT Lamp_Base_ID, Sequence_Number FROM lamp_base WHERE Sequence_Number<%d ORDER BY Sequence_Number DESC LIMIT 0, 1", mysql_real_escape_string($lampBase->SequenceNumber)));
	if($data->TotalRows > 0) {
		new DataQuery(sprintf("UPDATE lamp_base SET Sequence_Number=%d WHERE Lamp_Base_ID=%d", $data->Row['Sequence_Number'], mysql_real_escape_string($lampBase->ID)));
		new DataQuery(sprintf("UPDATE lamp_base SET Sequence_Number=%d WHERE Lamp_Base_ID=%d", mysql_real_escape_string($lampBase->SequenceNumber), $data->Row['Lamp_Base_ID']));
	}
	$data->Disconnect();

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function movedown() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/LampBase.php');

	$lampBase = new LampBase($_REQUEST['id']);

	$data = new DataQuery(sprintf("SELECT Lamp_Base_ID, Sequence_Number FROM lamp_base WHERE Sequence_Number>%d ORDER BY Sequence_Number ASC LIMIT 0, 1", mysql_real_escape_string($lampBase->SequenceNumber)));
	if($data->TotalRows > 0) {
		new DataQuery(sprintf("UPDATE lamp_base SET Sequence_Number=%d WHERE Lamp_Base_ID=%d", $data->Row['Sequence_Number'], mysql_real_escape_string($lampBase->ID)));
		new DataQuery(sprintf("UPDATE lamp_base SET Sequence_Number=%d WHERE Lamp_Base_ID=%d", mysql_real_escape_string($lampBase->SequenceNumber), $data->Row['Lamp_Base_ID']));
	}
	$data->Disconnect();

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/LampBase.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardWindow.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action','Action','hidden','add','alpha',3,3);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('name', 'Name', 'text', '', 'anything', 0, 120);
	$form->AddField('image', 'Image', 'file', '', 'file', NULL, NULL, false);
	$form->AddField('value', 'Specification', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('value', '', '');

	$data = new DataQuery(sprintf("SELECT * FROM product_specification_value WHERE Group_ID=30 ORDER BY Value ASC"));
	while($data->Row) {
		$form->AddOption('value', $data->Row['Value_ID'], $data->Row['Value']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()) {
			$lampBase = new LampBase();
			$lampBase->Name = $form->GetValue('name');
			$lampBase->Value->ID = $form->GetValue('value');
			$lampBase->Add('image');

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page(sprintf('<a href="%s">Lamp Bases</a> &gt; Add Base', $_SERVER['PHP_SELF']), 'Here you can add a new lamp base.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow('Please enter the lamp base information.');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');

	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'),$form->GetHTML('name').$form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('image'),$form->GetHTML('image').$form->GetIcon('image'));
	echo $webForm->AddRow($form->GetLabel('value'),$form->GetHTML('value').$form->GetIcon('value'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'%s\';" />&nbsp;<input type="submit" name="add" value="add" class="btn" tabindex="%s">', $_SERVER['PHP_SELF'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
}

function update() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/LampBase.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardWindow.php');

	if(!isset($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$lampBase = new LampBase();

	if(!$lampBase->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action','Action','hidden','update','alpha', 6, 6);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('id', 'Lamp Base ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', $lampBase->Name, 'anything', 0, 120);
	$form->AddField('image', 'Image', 'file', '', 'file', NULL, NULL, false);
	$form->AddField('value', 'Specification', 'select', $lampBase->Value->ID, 'numeric_unsigned', 1, 11);
	$form->AddOption('value', '', '');

	$data = new DataQuery(sprintf("SELECT * FROM product_specification_value WHERE Group_ID=30 ORDER BY Value ASC"));
	while($data->Row) {
		$form->AddOption('value', $data->Row['Value_ID'], $data->Row['Value']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()) {
			$lampBase->Name = $form->GetValue('name');
			$lampBase->Value->ID = $form->GetValue('value');
			$lampBase->Update('image');

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page(sprintf('<a href="%s">Lamp Bases</a> &gt; Update Base', $_SERVER['PHP_SELF']), 'Here you can update an existing lamp base.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow('Please enter the lamp base information.');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'),$form->GetHTML('name').$form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('image'),$form->GetHTML('image').$form->GetIcon('image'));

	if((strlen($lampBase->Image->FileName) > 0) && (file_exists('../images/bases/'.$lampBase->Image->FileName))) {
		echo $webForm->AddRow('Current Image','<img src="../images/bases/'.$lampBase->Image->FileName.'" />');
	}

	echo $webForm->AddRow($form->GetLabel('value'),$form->GetHTML('value').$form->GetIcon('value'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'%s\';" />&nbsp;<input type="submit" name="update" value="update" class="btn" tabindex="%s">', $_SERVER['PHP_SELF'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
}

function remove() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/LampBase.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$base = new LampBase();
		$base->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function view() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');

	$page = new Page("Lamp Bases", "Manage lamp bases for aiding product searching.");
	$page->Display('header');

	$table = new DataTable("com");
	$table->SetSQL("SELECT lb.*, psv.Value FROM lamp_base AS lb LEFT JOIN product_specification_value AS psv ON psv.Value_ID=lb.Specification_Value_ID");
	$table->AddField("Base ID#", "Lamp_Base_ID", 'left');
	$table->AddField("Name", "Name");
	$table->AddField("Specification Value", "Value");
	$table->AddLink("lamp_bases.php?action=moveup&id=%s", "<img src=\"images/aztector_3.gif\" alt=\"Move item up\" border=\"0\">",  "Lamp_Base_ID");
	$table->AddLink("lamp_bases.php?action=movedown&id=%s", "<img src=\"images/aztector_4.gif\" alt=\"Move item down\" border=\"0\">",  "Lamp_Base_ID");
	$table->AddLink("lamp_bases.php?action=update&id=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Update this item\" border=\"0\">",  "Lamp_Base_ID");
	$table->AddLink("javascript:confirmRequest('lamp_bases.php?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove this affiliate\" border=\"0\">", "Lamp_Base_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy('Sequence_Number');
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input name="add" type="submit" value="add new base" class="btn" onclick="window.location.href=\'lamp_bases.php?action=add\'">';

	$page->Display('footer');
}

require_once('lib/common/app_header.php');
?>